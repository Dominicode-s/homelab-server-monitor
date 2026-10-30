<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DockerContainer extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'collected_at',
        'container_id',
        'container_name',
        'image',
        'state',
        'health',
        'restart_count',
        'status_text',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'restart_count' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
