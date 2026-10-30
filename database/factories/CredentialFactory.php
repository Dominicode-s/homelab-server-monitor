<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Credential;

class CredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'token' => Credential::generateToken(),
            'expires_at' => $this->faker->optional()->dateTimeBetween(0, 365 * 365),
            'revoked' => false,
        ];
    }
}
