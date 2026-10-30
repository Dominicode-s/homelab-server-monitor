<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'friendly_name',
        'hostname',
        'description',
        'os',
        'kernel_version',
        'architecture',
        'agent_version',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'registered_at' => 'datetime',
    ];

    public function credentials(): HasOne
    {
        return $this->hasOne(Credential::class);
    }

    public function metricsCpu(): HasMany
    {
        return $this->hasMany(MetricCpu::class);
    }

    public function metricsMemory(): HasMany
    {
        return $this->hasMany(MetricMemory::class);
    }

    public function metricsDisk(): HasMany
    {
        return $this->hasMany(MetricDisk::class);
    }

    public function metricsNetwork(): HasMany
    {
        return $this->hasMany(MetricNetwork::class);
    }

    public function metricsSystem(): HasMany
    {
        return $this->hasMany(MetricSystem::class);
    }

    public function monitoredServices(): HasMany
    {
        return $this->hasMany(MonitoredService::class);
    }

    public function dockerContainers(): HasMany
    {
        return $this->hasMany(DockerContainer::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function latestCpuMetric(): HasOne
    {
        return $this->hasOne(MetricCpu::class)->latest('collected_at');
    }

    public function latestMemoryMetric(): HasOne
    {
        return $this->hasOne(MetricMemory::class)->latest('collected_at');
    }

    public function latestDiskMetrics(): HasMany
    {
        return $this->hasMany(MetricDisk::class)->latest('collected_at');
    }

    public function isOnline(): bool
    {
        $offlineThreshold = config('sentinel.security.offline_threshold_seconds', 120);
        return $this->last_seen_at?->isAfter(now()->subSeconds($offlineThreshold)) ?? false;
    }

    public function isOffline(): bool
    {
        return ! $this->isOnline();
    }

    public function updateStatus(): void
    {
        if ($this->isOnline()) {
            $this->status = 'online';
        } else {
            $this->status = 'offline';
        }
        $this->save();
    }
}
