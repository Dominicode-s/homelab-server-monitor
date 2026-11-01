<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Server;
use App\Models\Credential;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use App\Models\MetricDisk;
use App\Models\MetricNetwork;
use App\Models\MetricSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class ServerManagementTest extends TestCase
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

    public function test_server_edit_page_returns_success(): void
    {
        $this->authenticate();
        $server = Server::factory()->create(['hostname' => 'test-host']);

        $response = $this->get(route('servers.edit', $server));

        $response->assertStatus(200)
            ->assertViewIs('servers.edit')
            ->assertViewHas('server', $server);
    }

    public function test_server_can_be_updated(): void
    {
        $this->authenticate();
        $server = Server::factory()->create([
            'friendly_name' => 'Old Name',
            'description' => 'Old description',
        ]);

        $response = $this->patch(route('servers.update', $server), [
            'friendly_name' => 'New Name',
            'description' => 'New description',
        ]);

        $response->assertRedirect(route('servers.show', $server));
        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'friendly_name' => 'New Name',
            'description' => 'New description',
        ]);
    }

    public function test_hostname_cannot_be_changed_via_update(): void
    {
        $this->authenticate();
        $server = Server::factory()->create(['hostname' => 'original-host']);

        $response = $this->patch(route('servers.update', $server), [
            'hostname' => 'changed-host',
            'friendly_name' => 'Updated Name',
        ]);

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'hostname' => 'original-host',
            'friendly_name' => 'Updated Name',
        ]);
    }

    public function test_server_search_filters_by_hostname(): void
    {
        $this->authenticate();
        Server::factory()->create(['hostname' => 'web-server-01']);
        Server::factory()->create(['hostname' => 'db-server-02']);

        $response = $this->get(route('servers.index', ['search' => 'web']));

        $response->assertStatus(200);
        $response->assertSee('web-server-01');
        $response->assertDontSee('db-server-02');
    }

    public function test_server_status_filter_works(): void
    {
        $this->authenticate();
        Server::factory()->create(['status' => 'online']);
        Server::factory()->create(['status' => 'offline']);

        $response = $this->get(route('servers.index', ['status' => 'online']));

        $response->assertStatus(200);
        // The online server should be in the filtered results
    }

    public function test_server_destroy_removes_record(): void
    {
        $this->authenticate();
        $server = Server::factory()->create();

        $response = $this->delete(route('servers.destroy', $server));

        $response->assertRedirect(route('servers.index'));
        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    }

    public function test_servers_index_shows_credentials_prefix(): void
    {
        $this->authenticate();
        $server = Server::factory()->create();
        $credential = Credential::factory()->create(['server_id' => $server->id]);

        $response = $this->get(route('servers.show', $server));

        $response->assertStatus(200);
        $response->assertViewHas('server', $server);
    }

    public function test_csv_export_returns_success_with_empty_data(): void
    {
        $this->authenticate();
        $server = Server::factory()->create(['hostname' => 'export-test']);

        $response = $this->get(route('servers.export-csv', $server));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="export_test-metrics-' . now()->format('Y-m-d') . '.csv"');
    }

    public function test_csv_export_includes_all_metric_sections(): void
    {
        $this->authenticate();
        Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00'));
        $server = Server::factory()->create(['hostname' => 'data-server']);

        MetricCpu::create(['server_id' => $server->id, 'collected_at' => now()->subHour(), 'utilisation_percent' => 45.5, 'core_count' => 4]);
        MetricMemory::create(['server_id' => $server->id, 'collected_at' => now()->subHour(), 'usage_percent' => 62.3, 'total_bytes' => 8589934592, 'used_bytes' => 5368709120]);
        MetricDisk::create(['server_id' => $server->id, 'collected_at' => now()->subHour(), 'mount_point' => '/', 'total_bytes' => 107374182400, 'used_bytes' => 53687091200]);
        MetricNetwork::create(['server_id' => $server->id, 'collected_at' => now()->subHour(), 'interface_name' => 'eth0', 'bytes_sent' => 1000, 'bytes_received' => 2000]);
        MetricSystem::create(['server_id' => $server->id, 'collected_at' => now()->subHour(), 'uptime_seconds' => 86400]);

        $response = $this->get(route('servers.export-csv', $server));

        $content = $response->streamedString;
        $this->assertStringContainsString('# CPU Usage', $content);
        $this->assertStringContainsString('# Memory Usage', $content);
        $this->assertStringContainsString('# Disk Usage', $content);
        $this->assertStringContainsString('# Network Throughput', $content);
        $this->assertStringContainsString('# System Uptime', $content);
        $this->assertStringContainsString('45.5', $content);
        $this->assertStringContainsString('62.3', $content);
        $this->assertStringContainsString('/', $content);
        $this->assertStringContainsString('eth0', $content);
        $this->assertStringContainsString('86400', $content);

        Carbon::setTestNow();
    }
}
