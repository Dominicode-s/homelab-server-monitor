#!/usr/bin/env python3
"""
Homelab Sentinel - Linux Monitoring Agent

Collects system metrics and reports them to the central Sentinel API.
Designed to run as a systemd service with minimal resource usage.
"""

import argparse
import logging
import os
import platform
import socket
import sys
import time
import json
import signal
import hashlib
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Optional

import psutil
import requests
from requests.exceptions import ConnectionError, RequestException, Timeout

# Default configuration
DEFAULT_CONFIG_PATH = "/etc/homelab-sentinel/config.json"
DEFAULT_INTERVAL = 30
DEFAULT_TIMEOUT = 10
DEFAULT_BACKOFF_MAX = 300
DEFAULT_BACKOFF_FACTOR = 1.5

logger = logging.getLogger("homelab-sentinel")


class Config:
    """Configuration manager for the monitoring agent."""

    def __init__(self, config_path: str = DEFAULT_CONFIG_PATH):
        self.config_path = Path(config_path)
        self.api_url: str = ""
        self.api_token: str = ""
        self.interval: int = DEFAULT_INTERVAL
        self.timeout: int = DEFAULT_TIMEOUT
        self.host_id: Optional[str] = None

        self._load()

    def _load(self) -> None:
        """Load configuration from file."""
        if not self.config_path.exists():
            logger.warning(f"Config file not found: {self.config_path}")
            return

        try:
            with open(self.config_path) as f:
                data = json.load(f)

            self.api_url = data.get("api_url", "")
            self.api_token = data.get("api_token", "")
            self.interval = int(data.get("interval", DEFAULT_INTERVAL))
            self.timeout = int(data.get("timeout", DEFAULT_TIMEOUT))
            self.host_id = data.get("host_id")

            # Validate
            if not self.api_url:
                raise ValueError("api_url is required")
            if not self.api_token:
                raise ValueError("api_token is required")

            # Clamp interval
            self.interval = max(10, min(self.interval, 300))
            self.timeout = max(1, min(self.timeout, 30))

            logger.info(f"Configuration loaded from {self.config_path}")

        except Exception as e:
            logger.error(f"Failed to load config: {e}")
            raise

    def save_host_id(self, host_id: str) -> None:
        """Save the server-assigned host ID back to config."""
        self.host_id = host_id
        try:
            with open(self.config_path, "w") as f:
                json.dump({
                    "api_url": self.api_url,
                    "api_token": self.api_token,
                    "interval": self.interval,
                    "timeout": self.timeout,
                    "host_id": host_id,
                }, f, indent=2)
        except Exception as e:
            logger.error(f"Failed to save host_id: {e}")


class MetricsCollector:
    """Collects system metrics using psutil."""

    @staticmethod
    def get_system_info() -> dict[str, Any]:
        """Gather basic system identification info."""
        return {
            "hostname": socket.gethostname(),
            "os": f"{platform.system()} {platform.release()}",
            "kernel_version": platform.version(),
            "architecture": platform.machine(),
            "agent_version": "1.0.0",
        }

    @staticmethod
    def get_cpu_metrics() -> dict[str, Any]:
        """Collect CPU metrics."""
        cpu_percent = psutil.cpu_percent(interval=0)
        cores = psutil.cpu_count(logical=False) or 0
        loads = os.getloadavg() if hasattr(os, 'getloadavg') else [0.0, 0.0, 0.0]

        metrics: dict[str, Any] = {
            "utilisation_percent": float(cpu_percent),
            "core_count": cores,
            "load_1m": round(loads[0], 2),
            "load_5m": round(loads[1], 2),
            "load_15m": round(loads[2], 2),
        }

        # Try to get temperature (optional, may not be available)
        try:
            temps = psutil.sensors_temperatures()
            if temps:
                # Get first available temperature
                for name, entries in temps.items():
                    if entries:
                        metrics["temperature_celsius"] = round(entries[0].current, 1)
                        break
        except Exception:
            pass  # Temperature not available

        return metrics

    @staticmethod
    def get_memory_metrics() -> dict[str, Any]:
        """Collect memory metrics."""
        mem = psutil.virtual_memory()
        swap = psutil.swap_memory()

        return {
            "total_bytes": mem.total,
            "used_bytes": mem.used,
            "usage_percent": round(mem.percent, 2),
            "swap_total_bytes": swap.total,
            "swap_used_bytes": swap.used,
            "swap_usage_percent": round(swap.percent, 2),
        }

    @staticmethod
    def get_disk_metrics() -> list[dict[str, Any]]:
        """Collect disk usage metrics for all mounted filesystems."""
        disks = []
        for partition in psutil.disk_partitions(all=False):
            try:
                usage = psutil.disk_usage(partition.mountpoint)
                disks.append({
                    "mount_point": partition.mountpoint,
                    "total_bytes": usage.total,
                    "used_bytes": usage.used,
                    "usage_percent": round(usage.percent, 2),
                })
            except PermissionError:
                logger.debug(f"Permission denied reading disk: {partition.mountpoint}")
            except OSError:
                pass  # Skip inaccessible mounts
        return disks

    @staticmethod
    def get_network_metrics() -> list[dict[str, Any]]:
        """Collect network interface metrics."""
        net = psutil.net_io_counters(pernic=True)
        interfaces = []

        for iface, counters in net.items():
            # Skip loopback
            if iface == "lo":
                continue

            interfaces.append({
                "interface_name": iface,
                "bytes_sent": counters.bytes_sent,
                "bytes_received": counters.bytes_recv,
                "status": "up",  # Best effort - actual status requires additional checks
            })

        return interfaces

    @staticmethod
    def get_system_metrics() -> dict[str, Any]:
        """Collect system-level metrics."""
        return {
            "uptime_seconds": int(time.time() - psutil.boot_time()),
        }

    @staticmethod
    def get_monitored_services() -> list[dict[str, Any]]:
        """
        Collect status of systemd services.
        This requires root access or appropriate permissions.
        Falls back gracefully if systemd is not available.
        """
        services = []
        try:
            import subprocess
            result = subprocess.run(
                ["systemctl", "list-units", "--type=service", "--state=running,stopped,failed",
                 "--no-pager", "--no-legend", "--plain"],
                capture_output=True, text=True, timeout=10
            )

            if result.returncode == 0:
                for line in result.stdout.strip().split("\n"):
                    parts = line.split()
                    if len(parts) >= 3:
                        service_name = parts[0].replace(".service", "")
                        state_str = parts[2]

                        if "running" in state_str:
                            state = "running"
                        elif "failed" in state_str:
                            state = "failed"
                        else:
                            state = "stopped"

                        services.append({
                            "name": service_name,
                            "state": state,
                            "is_active": state == "running",
                        })
        except (FileNotFoundError, subprocess.TimeoutExpired, Exception) as e:
            logger.debug(f"Could not collect systemd services: {e}")

        return services

    @staticmethod
    def get_docker_containers() -> list[dict[str, Any]]:
        """
        Collect Docker container information.
        Requires Docker CLI to be available and accessible.
        Returns empty list if Docker is not available.
        """
        containers = []
        try:
            import subprocess
            result = subprocess.run(
                ["docker", "ps", "-a", "--format",
                 '{"Name":"{{.Names}}","ID":"{{.ID}}","Image":"{{.Image}}",'
                 '"State":"{{.State}}","Status":"{{.Status}}",'
                 '"RestartCount":{{.RestartCount}},"Labels":"{{.Labels}}"}'],
                capture_output=True, text=True, timeout=10
            )

            if result.returncode == 0:
                for line in result.stdout.strip().split("\n"):
                    if not line:
                        continue
                    try:
                        data = json.loads(line)
                        state = data.get("State", "unknown")

                        # Normalize state
                        if "running" in state.lower():
                            normalized_state = "running"
                        elif "paused" in state.lower():
                            normalized_state = "paused"
                        elif "restarting" in state.lower():
                            normalized_state = "restarting"
                        elif "dead" in state.lower():
                            normalized_state = "dead"
                        else:
                            normalized_state = "exited"

                        containers.append({
                            "container_name": data["Name"],
                            "container_id": data["ID"][:12],
                            "image": data["Image"],
                            "state": normalized_state,
                            "health": None,  # Docker health requires additional inspection
                            "restart_count": data.get("RestartCount", 0),
                            "status_text": data.get("Status"),
                        })
                    except (json.JSONDecodeError, KeyError):
                        continue
        except (FileNotFoundError, subprocess.TimeoutExpired, Exception):
            pass  # Docker not available

        return containers


class SentinelAgent:
    """Main monitoring agent that coordinates collection and reporting."""

    def __init__(self, config: Config):
        self.config = config
        self.collector = MetricsCollector()
        self.running = False
        self.server_id: Optional[str] = None
        self.backoff_attempts = 0

        # Setup signal handlers
        signal.signal(signal.SIGINT, self._handle_signal)
        signal.signal(signal.SIGTERM, self._handle_signal)

    def _handle_signal(self, signum, frame) -> None:
        """Handle shutdown signals gracefully."""
        logger.info(f"Received signal {signum}, shutting down...")
        self.running = False

    def authenticate(self) -> bool:
        """Authenticate with the Sentinel API and register this host."""
        try:
            response = requests.post(
                f"{self.config.api_url}/api/v1/agents/auth",
                json={"token": self.config.api_token},
                timeout=self.config.timeout,
            )

            if response.status_code == 200:
                data = response.json()
                self.server_id = str(data.get("server_id"))
                self.config.save_host_id(self.server_id)
                logger.info(f"Authenticated successfully (server_id: {self.server_id})")
                return True
            else:
                logger.error(f"Authentication failed: {response.status_code}")
                return False

        except RequestException as e:
            logger.error(f"Authentication error: {e}")
            return False

    def report_telemetry(self, system_info: dict[str, Any]) -> bool:
        """Send collected telemetry to the Sentinel API."""
        payload = {
            "server_info": system_info,
            "cpu": self.collector.get_cpu_metrics(),
            "memory": self.collector.get_memory_metrics(),
            "disks": self.collector.get_disk_metrics(),
            "network": self.collector.get_network_metrics(),
            "system": self.collector.get_system_metrics(),
            "services": self.collector.get_monitored_services(),
            "docker": self.collector.get_docker_containers(),
        }

        try:
            response = requests.post(
                f"{self.config.api_url}/api/v1/telemetry",
                json=payload,
                headers={
                    "X-Agent-Token": self.config.api_token,
                    "Content-Type": "application/json",
                },
                timeout=self.config.timeout,
            )

            if response.status_code == 200:
                self.backoff_attempts = 0  # Reset backoff on success
                logger.debug("Telemetry reported successfully")
                return True
            else:
                logger.error(f"Telemetry upload failed: {response.status_code}")
                return False

        except RequestException as e:
            logger.error(f"Telemetry error: {e}")
            return False

    def get_backoff_delay(self) -> int:
        """Calculate exponential backoff delay."""
        delay = min(
            DEFAULT_INTERVAL * (DEFAULT_BACKOFF_FACTOR ** self.backoff_attempts),
            DEFAULT_BACKOFF_MAX,
        )
        return int(delay)

    def run_once(self) -> None:
        """Execute one complete collection and reporting cycle."""
        try:
            # Authenticate first
            if not self.authenticate():
                delay = self.get_backoff_delay()
                logger.warning(f"Authentication failed, retrying in {delay}s...")
                time.sleep(delay)
                return

            # Collect all metrics
            system_info = self.collector.get_system_info()

            # Report telemetry
            success = self.report_telemetry(system_info)

            if not success:
                self.backoff_attempts += 1
                delay = self.get_backoff_delay()
                logger.warning(f"Report failed, retrying in {delay}s...")
                time.sleep(delay)

        except Exception as e:
            logger.error(f"Unexpected error in run cycle: {e}", exc_info=True)
            time.sleep(DEFAULT_INTERVAL)

    def run(self) -> None:
        """Main loop - collect and report metrics continuously."""
        self.running = True
        logger.info(f"Agent started, reporting every {self.config.interval}s")
        logger.info(f"API endpoint: {self.config.api_url}")

        while self.running:
            self.run_once()

            if self.running:  # Check again in case signal was received
                logger.debug(f"Sleeping for {self.config.interval}s...")
                time.sleep(self.config.interval)

        logger.info("Agent stopped.")


def setup_logging(verbose: bool = False) -> None:
    """Configure logging for the agent."""
    level = logging.DEBUG if verbose else logging.INFO
    handler = logging.StreamHandler(sys.stdout)
    handler.setFormatter(logging.Formatter(
        "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
    ))

    logger.setLevel(level)
    logger.addHandler(handler)

    # Reduce noise from requests library
    logging.getLogger("requests").setLevel(logging.WARNING)


def create_default_config(config_path: str) -> None:
    """Create a default configuration file if it doesn't exist."""
    path = Path(config_path)
    if path.exists():
        print(f"Config file already exists: {path}")
        return

    path.parent.mkdir(parents=True, exist_ok=True)

    config = {
        "api_url": "http://localhost:8000",
        "api_token": "",
        "interval": 30,
        "timeout": 10,
    }

    with open(path, "w") as f:
        json.dump(config, f, indent=2)

    print(f"Default config created at: {path}")
    print("Please edit this file with your Sentinel API URL and token.")


def main() -> None:
    """Entry point for the monitoring agent."""
    parser = argparse.ArgumentParser(description="Homelab Sentinel Monitoring Agent")
    parser.add_argument(
        "-c", "--config",
        default=DEFAULT_CONFIG_PATH,
        help=f"Path to config file (default: {DEFAULT_CONFIG_PATH})",
    )
    parser.add_argument(
        "-v", "--verbose",
        action="store_true",
        help="Enable verbose logging",
    )
    parser.add_argument(
        "--init-config",
        action="store_true",
        help="Create a default configuration file and exit",
    )

    args = parser.parse_args()

    if args.init_config:
        create_default_config(args.config)
        return

    setup_logging(args.verbose)

    try:
        config = Config(args.config)
        agent = SentinelAgent(config)
        agent.run()
    except KeyboardInterrupt:
        logger.info("Agent stopped by user.")
    except Exception as e:
        logger.error(f"Agent failed to start: {e}", exc_info=True)
        sys.exit(1)


if __name__ == "__main__":
    main()
