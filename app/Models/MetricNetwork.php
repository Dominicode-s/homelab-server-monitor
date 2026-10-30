<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricNetwork extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'metrics_network';

    protected $fillable = [
        'server_id',
        'collected_at',
        'interface_name',
        'bytes_sent',
        'bytes_received',
        'status',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'bytes_sent' => 'integer',
        'bytes_received' => 'integer',
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
