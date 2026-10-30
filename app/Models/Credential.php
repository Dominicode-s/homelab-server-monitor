<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'token',
        'expires_at',
        'revoked',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function isValid(): bool
    {
        return ! $this->revoked
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function revoke(): void
    {
        $this->update([
            'revoked' => true,
            'revoked_at' => now(),
        ]);
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
