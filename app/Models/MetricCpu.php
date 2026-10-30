<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricCpu extends Model
{
    use HasFactory;

    protected $table = 'metrics_cpu';

    protected $fillable = [
        'server_id',
        'collected_at',
        'utilisation_percent',
        'core_count',
        'load_1m',
        'load_5m',
        'load_15m',
        'temperature_celsius',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'utilisation_percent' => 'decimal:2',
        'core_count' => 'integer',
        'load_1m' => 'decimal:2',
        'load_5m' => 'decimal:2',
        'load_15m' => 'decimal:2',
        'temperature_celsius' => 'decimal:2',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function cleanupOldMetrics(int $days = 7): int
    {
        return static::where('collected_at', '<', now()->subDays($days))->delete();
    }
}
