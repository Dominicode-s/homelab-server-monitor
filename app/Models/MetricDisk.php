<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricDisk extends Model
{
    use HasFactory;

    protected $table = 'metrics_disk';

    protected $fillable = [
        'server_id',
        'collected_at',
        'mount_point',
        'total_bytes',
        'used_bytes',
        'usage_percent',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'total_bytes' => 'integer',
        'used_bytes' => 'integer',
        'usage_percent' => 'decimal:2',
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
