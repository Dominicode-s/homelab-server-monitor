<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use App\Models\MetricDisk;
use App\Models\MonitoredService;

class AlertEvaluator
{
    public function evaluate(): void
    {
        $servers = Server::whereNotNull('last_seen_at')->get();

        foreach ($servers as $server) {
            $this->evaluateCpuAlerts($server);
            $this->evaluateMemoryAlerts($server);
            $this->evaluateDiskAlerts($server);
            $this->evaluateServiceAlerts($server);
        }

        $this->evaluateHostOfflineAlerts($servers);
    }

    protected function evaluateCpuAlerts(Server $server): void
    {
        $rule = AlertRule::where('type', 'cpu_usage')
            ->where(function ($q) use ($server) {
                $q->whereNull('server_id')->orWhere('server_id', $server->id);
            })
            ->where('is_enabled', true)
            ->first();

        if (! $rule) {
            return;
        }

        $threshold = (float) $rule->threshold;
        $durationMinutes = (int) $rule->duration_minutes;

        if ($durationMinutes === 0) {
            // Immediate check - no duration requirement
            $latest = MetricCpu::where('server_id', $server->id)
                ->latest('collected_at')
                ->first();

            if ($latest && $latest->utilisation_percent !== null && $latest->utilisation_percent >= $threshold) {
                $this->triggerOrMaintainAlert($server, $rule, 'High CPU Usage', [
                    'metric_value' => $latest->utilisation_percent,
                    'threshold' => $threshold,
                ]);
            } else {
                $this->maybeResolveAlert($server, $rule, ['metric_value' => $latest?->utilisation_percent]);
            }
        } else {
            // Duration-based check
            $cutoff = now()->subMinutes($durationMinutes);
            $violations = MetricCpu::where('server_id', $server->id)
                ->where('collected_at', '>=', $cutoff)
                ->whereNotNull('utilisation_percent')
                ->where('utilisation_percent', '>=', $threshold)
                ->count();

            $totalPoints = MetricCpu::where('server_id', $server->id)
                ->where('collected_at', '>=', $cutoff)
                ->count();

            if ($totalPoints > 0 && ($violations / $totalPoints) > 0.5) {
                $latest = MetricCpu::where('server_id', $server->id)->latest('collected_at')->first();
                $this->triggerOrMaintainAlert($server, $rule, 'High CPU Usage', [
                    'metric_value' => $latest?->utilisation_percent,
                    'threshold' => $threshold,
                    'violation_rate' => round(($violations / $totalPoints) * 100, 1),
                ]);
            } else {
                $this->maybeResolveAlert($server, $rule);
            }
        }
    }

    protected function evaluateMemoryAlerts(Server $server): void
    {
        $rule = AlertRule::where('type', 'memory_usage')
            ->where(function ($q) use ($server) {
                $q->whereNull('server_id')->orWhere('server_id', $server->id);
            })
            ->where('is_enabled', true)
            ->first();

        if (! $rule) {
            return;
        }

        $threshold = (float) $rule->threshold;
        $durationMinutes = (int) $rule->duration_minutes;

        if ($durationMinutes === 0) {
            $latest = MetricMemory::where('server_id', $server->id)
                ->latest('collected_at')
                ->first();

            if ($latest && $latest->usage_percent !== null && $latest->usage_percent >= $threshold) {
                $this->triggerOrMaintainAlert($server, $rule, 'High Memory Usage', [
                    'metric_value' => $latest->usage_percent,
                    'threshold' => $threshold,
                ]);
            } else {
                $this->maybeResolveAlert($server, $rule);
            }
        } else {
            $cutoff = now()->subMinutes($durationMinutes);
            $violations = MetricMemory::where('server_id', $server->id)
                ->where('collected_at', '>=', $cutoff)
                ->whereNotNull('usage_percent')
                ->where('usage_percent', '>=', $threshold)
                ->count();

            $totalPoints = MetricMemory::where('server_id', $server->id)
                ->where('collected_at', '>=', $cutoff)
                ->count();

            if ($totalPoints > 0 && ($violations / $totalPoints) > 0.5) {
                $latest = MetricMemory::where('server_id', $server->id)->latest('collected_at')->first();
                $this->triggerOrMaintainAlert($server, $rule, 'High Memory Usage', [
                    'metric_value' => $latest?->usage_percent,
                    'threshold' => $threshold,
                ]);
            } else {
                $this->maybeResolveAlert($server, $rule);
            }
        }
    }

    protected function evaluateDiskAlerts(Server $server): void
    {
        $rule = AlertRule::where('type', 'disk_usage')
            ->where(function ($q) use ($server) {
                $q->whereNull('server_id')->orWhere('server_id', $server->id);
            })
            ->where('is_enabled', true)
            ->first();

        if (! $rule) {
            return;
        }

        $threshold = (float) $rule->threshold;

        $latestDisks = MetricDisk::where('server_id', $server->id)
            ->latest('collected_at')
            ->get()
            ->groupBy('mount_point');

        foreach ($latestDisks as $mountPoint => $disks) {
            $latest = $disks->first();
            if ($latest && $latest->usage_percent !== null && $latest->usage_percent >= $threshold) {
                $this->triggerOrMaintainAlert($server, $rule, "High Disk Usage ({$mountPoint})", [
                    'mount_point' => $mountPoint,
                    'metric_value' => $latest->usage_percent,
                    'threshold' => $threshold,
                ]);
            } else {
                $this->maybeResolveAlert($server, $rule, ['mount_point' => $mountPoint]);
            }
        }
    }

    protected function evaluateServiceAlerts(Server $server): void
    {
        $rule = AlertRule::where('type', 'service_down')
            ->where(function ($q) use ($server) {
                $q->whereNull('server_id')->orWhere('server_id', $server->id);
            })
            ->where('is_enabled', true)
            ->first();

        if (! $rule) {
            return;
        }

        // Get the latest snapshot per service name for this server
        $latestServices = MonitoredService::where('server_id', $server->id)
            ->latest('collected_at')
            ->get()
            ->groupBy('service_name');

        foreach ($latestServices as $serviceName => $services) {
            $latest = $services->first();
            if ($latest && in_array($latest->state, ['failed', 'stopped'])) {
                $this->triggerOrMaintainAlert($server, $rule, "Service Down: {$serviceName}", [
                    'service_name' => $serviceName,
                    'state' => $latest->state,
                ]);
            } else {
                $this->maybeResolveAlert($server, $rule, ['service_name' => $serviceName]);
            }
        }
    }

    protected function evaluateHostOfflineAlerts(iterable $servers): void
    {
        $rule = AlertRule::where('type', 'host_offline')
            ->where('is_enabled', true)
            ->first();

        if (! $rule) {
            return;
        }

        $offlineThreshold = config('sentinel.security.offline_threshold_seconds', 120);
        $cutoff = now()->subSeconds($offlineThreshold);

        foreach ($servers as $server) {
            if ($server->last_seen_at < $cutoff) {
                $this->triggerOrMaintainAlert(
                    $server,
                    $rule,
                    'Host Offline',
                    [
                        'last_seen' => $server->last_seen_at?->toISOString(),
                        'offline_duration_seconds' => now()->diffInSeconds($server->last_seen_at),
                    ]
                );
            }
        }
    }

    protected function triggerOrMaintainAlert(
        Server $server,
        AlertRule $rule,
        string $title,
        array $metadata
    ): void {
        // Check for existing active alert with same rule and similar metadata
        $existingActive = Alert::where('server_id', $server->id)
            ->where('rule_id', $rule->id)
            ->where('state', 'active')
            ->first();

        if ($existingActive) {
            return; // Already active
        }

        Alert::create([
            'server_id' => $server->id,
            'rule_id' => $rule->id,
            'title' => $title,
            'description' => $rule->description,
            'severity' => str_contains(strtolower($title), 'critical') ? 'critical' : 'warning',
            'state' => 'active',
            'metadata' => $metadata,
            'triggered_at' => now(),
        ]);
    }

    protected function maybeResolveAlert(Server $server, AlertRule $rule, ?array $extraMetadata = null): void
    {
        $query = Alert::where('server_id', $server->id)
            ->where('rule_id', $rule->id)
            ->where('state', 'active');

        // Scope resolution by metadata context (mount_point, service_name, etc.)
        if ($extraMetadata) {
            foreach ($extraMetadata as $key => $value) {
                $query->where('metadata->' . $key, $value);
            }
        }

        $activeAlert = $query->first();

        if ($activeAlert) {
            $activeAlert->resolve();
        }
    }
}
