<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Server;
use App\Models\AlertRule;
use App\Models\Alert;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use App\Models\MetricDisk;
use App\Services\AlertEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AlertEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create default alert rules
        AlertRule::firstOrCreate(
            ['name' => 'High CPU Usage'],
            [
                'type' => 'cpu_usage',
                'threshold' => 90,
                'duration_minutes' => 5,
                'is_enabled' => true,
            ]
        );

        AlertRule::firstOrCreate(
            ['name' => 'High Memory Usage'],
            [
                'type' => 'memory_usage',
                'threshold' => 90,
                'duration_minutes' => 5,
                'is_enabled' => true,
            ]
        );

        AlertRule::firstOrCreate(
            ['name' => 'High Disk Usage'],
            [
                'type' => 'disk_usage',
                'threshold' => 85,
                'duration_minutes' => 0,
                'is_enabled' => true,
            ]
        );

        AlertRule::firstOrCreate(
            ['name' => 'Host Offline'],
            [
                'type' => 'host_offline',
                'threshold' => 2,
                'duration_minutes' => 2,
                'is_enabled' => true,
            ]
        );

        AlertRule::firstOrCreate(
            ['name' => 'Service Down'],
            [
                'type' => 'service_down',
                'threshold' => null,
                'duration_minutes' => 0,
                'is_enabled' => true,
            ]
        );
    }

    public function test_high_cpu_triggers_alert(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'utilisation_percent' => 95.5,
            'core_count' => 4,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'title' => 'High CPU Usage',
            'state' => 'active',
        ]);
    }

    public function test_normal_cpu_does_not_trigger_alert(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'utilisation_percent' => 45.0,
            'core_count' => 4,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseMissing('alerts', [
            'server_id' => $server->id,
            'title' => 'High CPU Usage',
        ]);
    }

    public function test_high_memory_triggers_alert(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MetricMemory::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'usage_percent' => 95.0,
            'total_bytes' => 8589934592,
            'used_bytes' => 8160437862,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'title' => 'High Memory Usage',
            'state' => 'active',
        ]);
    }

    public function test_high_disk_triggers_alert(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MetricDisk::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'mount_point' => '/',
            'usage_percent' => 92.0,
            'total_bytes' => 107374182400,
            'used_bytes' => 98784247808,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'title' => 'High Disk Usage (/)',
            'state' => 'active',
        ]);
    }

    public function test_no_duplicate_alerts_for_same_condition(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'utilisation_percent' => 95.0,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();
        $evaluator->evaluate(); // Run twice

        $cpuAlerts = Alert::where('server_id', $server->id)
            ->where('title', 'High CPU Usage')
            ->where('state', 'active')
            ->count();

        $this->assertEquals(1, $cpuAlerts);
    }

    public function test_alert_resolves_when_metric_returns_to_normal(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        // First: high CPU triggers alert
        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now()->subMinute(),
            'utilisation_percent' => 95.0,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'title' => 'High CPU Usage',
            'state' => 'active',
        ]);

        // Second: CPU returns to normal
        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'utilisation_percent' => 30.0,
        ]);

        $evaluator->evaluate();

        $alert = Alert::where('server_id', $server->id)
            ->where('title', 'High CPU Usage')
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals('resolved', $alert->state);
    }

    public function test_offline_host_triggers_alert(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(5),
            'status' => 'offline',
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'title' => 'Host Offline',
            'state' => 'active',
        ]);
    }
}
