<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'rule_id',
        'title',
        'description',
        'severity',
        'state',
        'metadata',
        'triggered_at',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'rule_id');
    }

    public function resolve(): void
    {
        $this->update([
            'state' => 'resolved',
            'resolved_at' => now(),
        ]);
    }
}
