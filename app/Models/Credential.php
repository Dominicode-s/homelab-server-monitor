<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'token_hash',
        'token_prefix',
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

    /**
     * Verify a plain-text token against the stored hash.
     */
    public function verifyToken(string $token): bool
    {
        return ! empty($this->token_hash) && Hash::check($token, $this->token_hash);
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

    /**
     * Generate a new random token and return the plain value.
     * Stores a bcrypt hash in the database, not the plaintext token.
     */
    public static function generateToken(): string
    {
        $plain = bin2hex(random_bytes(32));
        $prefix = substr($plain, 0, 8);
        $hash = Hash::make($plain);

        self::create([
            'token_hash' => $hash,
            'token_prefix' => $prefix,
        ]);

        return $plain;
    }

    /**
     * Display-friendly prefix of the token for UI display.
     */
    public function getShortTokenAttribute(): string
    {
        return $this->token_prefix ?? '—';
    }
}
