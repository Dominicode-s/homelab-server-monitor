<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Credential;
use App\Models\Server;

class CredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'token' => Credential::generateToken(),
            'expires_at' => now()->addDays($this->faker->numberBetween(1, 365)),
            'revoked' => false,
        ];
    }
}
