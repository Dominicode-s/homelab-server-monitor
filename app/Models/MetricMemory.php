<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricMemory extends Model
{
    use HasFactory;

    protected $table = 'metrics_memory';

    protected $fillable = [
        'server_id',
        'collected_at',
        'total_bytes',
        'used_bytes',
        'usage_percent',
        'swap_total_bytes',
        'swap_used_bytes',
        'swap_usage_percent',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'total_bytes' => 'integer',
        'used_bytes' => 'integer',
        'usage_percent' => 'decimal:2',
        'swap_total_bytes' => 'integer',
        'swap_used_bytes' => 'integer',
        'swap_usage_percent' => 'decimal:2',
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
