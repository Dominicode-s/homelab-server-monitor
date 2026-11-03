# Homelab Sentinel

Self-hosted Linux Server Monitoring Platform

A self-hosted monitoring solution consisting of a Laravel-based web dashboard and lightweight Python agents that collect and report system metrics from your homelab servers.

## Features

- **Real-time Dashboard** — Fleet overview with CPU, memory, disk, and network visualizations
- **Multi-Server Support** — Monitor 5–20+ Linux machines from a single interface
- **Historical Charts** — View metric trends over configurable time ranges
- **Alert Engine** — Configurable thresholds for CPU, memory, disk, host offline, and service failures
- **Service Monitoring** — Track systemd service states across all monitored hosts
- **Docker Integration** — Optional container monitoring where Docker is available
- **Dark Mode Default** — Modern, responsive UI with professional design
- **SQLite by Default** — Portable database, migratable to MySQL

## Architecture

```
┌─────────────────────────────────────────┐
│           Homelab Sentinel              │
│  ┌─────────────┐   ┌─────────────────┐ │
│  │  Dashboard   │   │   REST API      │ │
│  │  (Blade +    │◄──►│   (/api/v1/)    │ │
│  │   Alpine.js) │   │                 │ │
│  └─────────────┘   └────────┬────────┘ │
│                             │           │
│                     ┌───────▼───────┐   │
│                     │  Alert Evaluator│  │
│                     │  Metrics Store │   │
│                     │  (SQLite)      │   │
│                     └───────────────┘   │
└─────────────────────────────────────────┘
          ▲             ▲             ▲
          │             │             │
    ┌─────┴─────┐ ┌────┴────┐ ┌─────┴─────┐
    │ Agent 1   │ │ Agent 2 │ │ Agent N   │
    │ (Python)  │ │(Python)  │ │ (Python)  │
    └───────────┘ └─────────┘ └───────────┘
```

## Quick Start

### Prerequisites

- PHP 8.3+ with extensions: `pdo`, `sqlite3`, `mbstring`, `tokenizer`, `json`, `bcmath`, `ctype`, `fileinfo`, `opcache`
- Composer
- Node.js 18+ & npm (for frontend assets)
- SQLite (usually included with PHP)

### Installation

```bash
# Clone the repository
git clone https://github.com/homelab/sentinel.git
cd sentinel

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci

# Build frontend assets
npm run build

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Seed default data (creates admin user + alert rules)
php artisan db:seed

# Start the development server
php artisan serve --host=0.0.0.0 --port=8000
```

### Docker Compose Deployment

```bash
# Create environment file
cp .env.example .env

# Generate an app key (replace the placeholder)
php artisan key:generate --show

# Update .env with the generated key

# Start with Docker Compose
docker compose up -d
```

The dashboard will be available at `http://localhost:8000`.

### First Login

Default credentials (change immediately):
- **Email:** `admin@localhost`
- **Password:** `changeme`

## Configuration

### Environment Variables

| Variable | Description | Default |
|---|---|---|
| `APP_KEY` | Application encryption key | *(required)* |
| `APP_URL` | Public URL of the application | `http://localhost:8000` |
| `DB_CONNECTION` | Database driver | `sqlite` |
| `DB_DATABASE` | SQLite database path | `storage/database/database.sqlite` |
| `ADMIN_EMAIL` | Initial admin email | `admin@localhost` |
| `ADMIN_PASSWORD` | Initial admin password | `changeme` |

### Sentinel Configuration

Edit `config/sentinel.php` to adjust:

```php
'security' => [
    'offline_threshold_seconds' => 120,  // Time before marking host offline
    'retention_days' => [
        'detailed' => 7,                  // Days to keep raw metrics
        'hourly_aggregate' => 90,         // Days to keep aggregated data
    ],
],
'alerts' => [
    'cpu_usage_percent' => 90,            // CPU threshold %
    'memory_usage_percent' => 90,         // Memory threshold %
    'disk_usage_percent' => 85,           // Disk threshold %
    'host_offline_minutes' => 2,          // Minutes before offline alert
],
```

## Monitoring Agent Setup

### Installation

```bash
# On the target Linux server:

# 1. Install Python dependencies
pip install -r /opt/homelab-sentinel/requirements.txt

# 2. Create configuration directory and file
sudo mkdir -p /etc/homelab-sentinel
sudo tee /etc/homelab-sentinel/config.json > /dev/null <<EOF
{
    "api_url": "http://your-sentinel-server:8000",
    "api_token": "YOUR_AGENT_TOKEN",
    "interval": 30,
    "timeout": 10
}
EOF

# 3. Install systemd service
sudo cp homelab-sentinel.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now homelab-sentinel

# 4. Check status
sudo journalctl -u homelab-sentinel -f
```

### Agent Configuration

| Field | Description | Default |
|---|---|---|
| `api_url` | URL of your Sentinel dashboard | `http://localhost:8000` |
| `api_token` | Authentication token from dashboard | — |
| `interval` | Reporting interval in seconds | `30` |
| `timeout` | HTTP request timeout in seconds | `10` |

### Getting an Agent Token

1. Log into the Sentinel dashboard
2. Register a new server under **Servers**
3. The system generates a unique authentication token
4. Copy the token into the agent's `config.json`

### Manual Test

```bash
python3 /opt/homelab-sentinel/sentinel_agent.py --config /etc/homelab-sentinel/config.json --verbose
```

## Scheduled Tasks

Add to your crontab (`crontab -e`) for background tasks:

```cron
# Every minute: check for offline servers
* * * * * cd /var/www/html && php artisan sentinel:check-offline >> /dev/null 2>&1

# Every 5 minutes: evaluate alert rules
*/5 * * * * cd /var/www/html && php artisan sentinel:evaluate-alerts >> /dev/null 2>&1

# Daily: clean up old metrics
0 3 * * * cd /var/www/html && php artisan sentinel:cleanup-metrics >> /dev/null 2>&1
```

Or use Laravel's scheduler within the application (recommended for Docker).

## API Reference

### Agent Endpoints

#### Authenticate

```
POST /api/v1/agents/auth
Content-Type: application/json

{
    "token": "your-agent-token"
}
```

#### Report Telemetry

```
POST /api/v1/telemetry
X-Agent-Token: your-agent-token
Content-Type: application/json

{
    "server_info": {
        "hostname": "my-server",
        "os": "Ubuntu 22.04 LTS",
        "kernel_version": "5.15.0-generic",
        "architecture": "x86_64",
        "agent_version": "1.0.0"
    },
    "cpu": {
        "utilisation_percent": 45.5,
        "core_count": 4,
        "load_1m": 1.2,
        "load_5m": 0.8,
        "load_15m": 0.5,
        "temperature_celsius": 55.0
    },
    "memory": {
        "total_bytes": 8589934592,
        "used_bytes": 4294967296,
        "usage_percent": 50.0,
        "swap_total_bytes": 2147483648,
        "swap_used_bytes": 0,
        "swap_usage_percent": 0.0
    },
    "disks": [
        {
            "mount_point": "/",
            "total_bytes": 107374182400,
            "used_bytes": 53687091200,
            "usage_percent": 50.0
        }
    ],
    "network": [
        {
            "interface_name": "eth0",
            "bytes_sent": 1000000,
            "bytes_received": 2000000,
            "status": "up"
        }
    ],
    "system": {
        "uptime_seconds": 86400
    },
    "services": [
        {
            "name": "nginx",
            "state": "running",
            "is_active": true
        }
    ],
    "docker": [
        {
            "container_name": "webapp",
            "container_id": "abc123def456",
            "image": "nginx:latest",
            "state": "running",
            "restart_count": 0,
            "status_text": "Up 2 days"
        }
    ]
}
```

### Error Responses

All API errors return consistent JSON:

```json
{
    "error": "Error message describing what went wrong"
}
```

HTTP Status Codes:
- `401` — Invalid or missing credentials
- `422` — Validation error on payload
- `429` — Rate limit exceeded
- `500` — Server error

## Testing

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Feature

# Run with coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html=coverage
```

## Database

### SQLite (Default)

Database file is stored at `storage/database/database.sqlite`.

### Migrating to MySQL

```bash
# 1. Update .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=homelab_sentinel
DB_USERNAME=homelab
DB_PASSWORD=your_secure_password

# 2. Run migrations
php artisan migrate --force
```

### Backup & Restore

```bash
# Backup
cp storage/database/database.sqlite backup-$(date +%Y%m%d).sqlite

# Restore
cp backup-20260101.sqlite storage/database/database.sqlite
```

## Troubleshooting

### Agent won't start

```bash
# Check configuration file permissions
ls -la /etc/homelab-sentinel/config.json

# Test agent manually
python3 /opt/homelab-sentinel/sentinel_agent.py --config /etc/homelab-sentinel/config.json --verbose

# Check systemd status
sudo journalctl -u homelab-sentinel -n 50
```

### Dashboard shows "Something went wrong"

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Verify database file permissions
ls -la storage/database/database.sqlite

# Re-run migrations if needed
php artisan migrate --force
```

### Agent reports but server stays offline

Check the offline threshold in `config/sentinel.php`:
```php
'offline_threshold_seconds' => 120, // Default: 2 minutes
```

The agent must report at least once within this window for a host to remain online.

## License

MIT
