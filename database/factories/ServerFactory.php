<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Server;

class ServerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hostname' => $this->faker->unique()->word() . '-' . $this->faker->numberBetween(1, 999),
            'friendly_name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'os' => $this->faker->randomElement(['Ubuntu 22.04 LTS', 'Debian 12', 'Arch Linux', 'CentOS Stream 9']),
            'kernel_version' => $this->faker->numerify('5.#.###-generic'),
            'architecture' => $this->faker->randomElement(['x86_64', 'aarch64']),
            'agent_version' => '1.0.0',
            'status' => $this->faker->randomElement(['online', 'offline', 'degraded']),
            'last_seen_at' => now()->subSeconds($this->faker->numberBetween(0, 60)),
        ];
    }
}
