<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Credential;
use App\Models\Server;
use Illuminate\Support\Facades\Hash;

class CredentialFactory extends Factory
{
    public function definition(): array
    {
        $token = Credential::generateToken();
        $prefix = substr($token, 0, 8);

        return [
            'server_id' => Server::factory(),
            'token_hash' => Hash::make($token),
            'token_prefix' => $prefix,
            'expires_at' => now()->addDays($this->faker->numberBetween(1, 365)),
            'revoked' => false,
        ];
    }
}
