<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\AlertRule;
use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AlertManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): Admin
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('Password123!'),
        ]);

        $this->actingAs($admin);
        return $admin;
    }

    public function test_alert_rules_are_displayed_on_index(): void
    {
        $this->authenticate();
        AlertRule::factory()->create([
            'name' => 'High CPU Usage',
            'type' => 'cpu_usage',
            'is_enabled' => true,
        ]);

        $response = $this->get(route('alerts.index'));

        $response->assertStatus(200)
            ->assertViewHas('rules')
            ->assertSee('High CPU Usage');
    }

    public function test_alert_rule_can_be_toggled(): void
    {
        $this->authenticate();
        $rule = AlertRule::factory()->create(['is_enabled' => true]);

        $response = $this->post(route('alerts.toggle-rule', $rule));

        $response->assertRedirect();
        $this->assertFalse(AlertRule::find($rule->id)->is_enabled);

        // Toggle back on
        $response = $this->post(route('alerts.toggle-rule', $rule));
        $this->assertTrue(AlertRule::find($rule->id)->is_enabled);
    }

    public function test_alert_show_page_returns_success(): void
    {
        $this->authenticate();
        $server = \App\Models\Server::factory()->create();
        $rule = AlertRule::factory()->create();
        $alert = Alert::create([
            'server_id' => $server->id,
            'rule_id' => $rule->id,
            'title' => 'High CPU Usage',
            'severity' => 'warning',
            'state' => 'active',
            'metadata' => ['metric_value' => 95.0],
            'triggered_at' => now(),
        ]);

        $response = $this->get(route('alerts.show', $alert));

        $response->assertStatus(200)
            ->assertViewIs('alerts.show')
            ->assertViewHas('alert', $alert);
    }

    public function test_alert_can_be_resolved(): void
    {
        $this->authenticate();
        $server = \App\Models\Server::factory()->create();
        $rule = AlertRule::factory()->create();
        $alert = Alert::create([
            'server_id' => $server->id,
            'rule_id' => $rule->id,
            'title' => 'High CPU Usage',
            'severity' => 'warning',
            'state' => 'active',
            'triggered_at' => now(),
        ]);

        $response = $this->post(route('alerts.resolve', $alert));

        $response->assertRedirect();
        $this->assertEquals('resolved', Alert::find($alert->id)->state);
        $this->assertNotNull(Alert::find($alert->id)->resolved_at);
    }

    public function test_resolving_already_resolved_alert_is_safe(): void
    {
        $this->authenticate();
        $server = \App\Models\Server::factory()->create();
        $rule = AlertRule::factory()->create();
        $alert = Alert::create([
            'server_id' => $server->id,
            'rule_id' => $rule->id,
            'title' => 'High CPU Usage',
            'state' => 'resolved',
            'triggered_at' => now()->subHour(),
            'resolved_at' => now()->subMinute(),
        ]);

        $response = $this->post(route('alerts.resolve', $alert));

        // Should not error
        $response->assertRedirect();
        $this->assertEquals('resolved', Alert::find($alert->id)->state);
    }

    public function test_settings_page_returns_success(): void
    {
        $this->authenticate();

        $response = $this->get(route('settings.index'));

        $response->assertStatus(200)
            ->assertViewIs('settings.index');
    }

    public function test_dashboard_shows_degraded_count(): void
    {
        $this->authenticate();
        config(['sentinel.alerts.cpu_usage_percent' => 90]);

        $online = \App\Models\Server::factory()->create(['status' => 'online']);
        $offline = \App\Models\Server::factory()->create(['status' => 'offline']);
        $degraded = \App\Models\Server::factory()->create(['status' => 'degraded']);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertViewHas('totalServers')
            ->assertViewHas('onlineServers')
            ->assertViewHas('offlineServers')
            ->assertViewHas('degradedServers');
    }
}
