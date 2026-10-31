<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Server;
use App\Models\AlertRule;
use App\Models\Alert;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use App\Models\MonitoredService;
use App\Services\AlertEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServiceDownAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_failed_service_triggers_alert(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MonitoredService::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'service_name' => 'nginx',
            'state' => 'failed',
            'is_active' => false,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'title' => 'Service Down: nginx',
            'state' => 'active',
        ]);
    }

    public function test_stopped_service_triggers_alert(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MonitoredService::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'service_name' => 'postgresql',
            'state' => 'stopped',
            'is_active' => false,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'title' => 'Service Down: postgresql',
            'state' => 'active',
        ]);
    }

    public function test_running_service_does_not_trigger_alert(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MonitoredService::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'service_name' => 'nginx',
            'state' => 'running',
            'is_active' => true,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseMissing('alerts', [
            'server_id' => $server->id,
            'title' => 'Service Down: nginx',
        ]);
    }

    public function test_service_alert_resolves_when_service_recovers(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        // First: failed service triggers alert
        MonitoredService::create([
            'server_id' => $server->id,
            'collected_at' => now()->subMinute(),
            'service_name' => 'redis',
            'state' => 'failed',
            'is_active' => false,
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'title' => 'Service Down: redis',
            'state' => 'active',
        ]);

        // Second: service recovers
        MonitoredService::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'service_name' => 'redis',
            'state' => 'running',
            'is_active' => true,
        ]);

        $evaluator->evaluate();

        $alert = Alert::where('server_id', $server->id)
            ->where('title', 'Service Down: redis')
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals('resolved', $alert->state);
    }

    public function test_no_duplicate_service_alerts(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(1),
        ]);

        MonitoredService::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'service_name' => 'nginx',
            'state' => 'failed',
        ]);

        $evaluator = app(AlertEvaluator::class);
        $evaluator->evaluate();
        $evaluator->evaluate(); // Run twice

        $serviceAlerts = Alert::where('server_id', $server->id)
            ->where('title', 'Service Down: nginx')
            ->where('state', 'active')
            ->count();

        $this->assertEquals(1, $serviceAlerts);
    }
}
