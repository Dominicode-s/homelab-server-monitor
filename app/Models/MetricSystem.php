<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricSystem extends Model
{
    use HasFactory;

    protected $table = 'metrics_system';

    protected $fillable = [
        'server_id',
        'collected_at',
        'uptime_seconds',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'uptime_seconds' => 'integer',
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
