<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoredService extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'collected_at',
        'service_name',
        'state',
        'is_active',
        'status_details',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
