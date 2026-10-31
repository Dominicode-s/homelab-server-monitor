<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\AlertRule;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Admin::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@localhost')],
            [
                'name' => 'Administrator',
                'password' => bcrypt(env('ADMIN_PASSWORD', 'changeme')),
            ]
        );

        // Default alert rules
        $defaults = [
            [
                'name' => 'High CPU Usage',
                'description' => 'CPU utilisation above 90% for 5 minutes',
                'type' => 'cpu_usage',
                'threshold' => 90,
                'duration_minutes' => 5,
            ],
            [
                'name' => 'High Memory Usage',
                'description' => 'Memory utilisation above 90% for 5 minutes',
                'type' => 'memory_usage',
                'threshold' => 90,
                'duration_minutes' => 5,
            ],
            [
                'name' => 'High Disk Usage',
                'description' => 'Disk usage above 85%',
                'type' => 'disk_usage',
                'threshold' => 85,
                'duration_minutes' => 0,
            ],
            [
                'name' => 'Host Offline',
                'description' => 'Host has not reported in 2 minutes',
                'type' => 'host_offline',
                'threshold' => 2,
                'duration_minutes' => 2,
            ],
            [
                'name' => 'Service Down',
                'description' => 'A monitored systemd service has stopped or failed',
                'type' => 'service_down',
                'threshold' => null,
                'duration_minutes' => 0,
            ],
        ];

        foreach ($defaults as $rule) {
            AlertRule::firstOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
