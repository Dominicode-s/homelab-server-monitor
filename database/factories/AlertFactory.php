<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Alert;
use App\Models\Server;
use App\Models\AlertRule;

class AlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'rule_id' => AlertRule::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'severity' => $this->faker->randomElement(['warning', 'critical']),
            'state' => $this->faker->randomElement(['active', 'resolved']),
            'metadata' => [],
            'notification_channels' => null,
            'triggered_at' => now()->subMinutes($this->faker->numberBetween(1, 120)),
            'resolved_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['state' => 'active']);
    }

    public function resolved(): static
    {
        return $this->state(function () {
            return [
                'state' => 'resolved',
                'resolved_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
            ];
        });
    }
}
