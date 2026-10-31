<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AlertRule;
use App\Models\Server;

class AlertRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['cpu_usage', 'memory_usage', 'disk_usage', 'host_offline', 'service_down']),
            'server_id' => null, // null = applies to all servers
            'threshold' => $this->faker->numberBetween(70, 95),
            'duration_minutes' => $this->faker->randomElement([0, 5, 10, 15]),
            'is_enabled' => $this->faker->boolean(80),
        ];
    }
}
