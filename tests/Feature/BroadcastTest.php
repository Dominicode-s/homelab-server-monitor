<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Server;
use App\Models\Credential;
use App\Models\Alert;
use App\Models\AlertRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for authentication
        $this->admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_sse_endpoint_requires_authentication(): void
    {
        $response = $this->get('/broadcast/events');
        $response->assertRedirect('/login');
    }

    public function test_sse_endpoint_returns_correct_headers(): void
    {
        $admin = $this->admin;

        $response = $this->withSession(['login_' . config('auth.guard', 'web') => $admin->getKey()])
            ->get('/broadcast/events');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');
        $response->assertHeader('Cache-Control', 'no-cache');
    }

    public function test_sse_stream_contains_initial_events(): void
    {
        $admin = $this->admin;

        $response = $this->withSession(['login_' . config('auth.guard', 'web')])
            ->get('/broadcast/events');

        $response->assertStatus(200);
        $content = $response->streamedContent ?? '';

        // Should contain initial connection event
        $this->assertStringContainsString('event: connected', $content);
        // Should contain initial snapshot event
        $this->assertStringContainsString('event: snapshot', $content);
    }

    public function test_sse_snapshot_contains_server_data(): void
    {
        $admin = $this->admin;

        $server = Server::factory()->create([
            'hostname' => 'test-host',
            'friendly_name' => 'Test Server',
            'status' => 'online',
        ]);

        Credential::factory()->for($server)->create();

        $response = $this->withSession(['login_' . config('auth.guard', 'web')])
            ->get('/broadcast/events');

        $response->assertStatus(200);
        $content = $response->streamedContent ?? '';

        $this->assertStringContainsString('"total_servers":1', $content);
        $this->assertStringContainsString('"online_servers":1', $content);
    }

    public function test_sse_snapshot_contains_alert_count(): void
    {
        $admin = $this->admin;

        $server = Server::factory()->create();
        Alert::factory()->for($server)->create([
            'state' => 'active',
        ]);

        Alert::factory()->for($server)->create([
            'state' => 'resolved',
        ]);

        $response = $this->withSession(['login_' . config('auth.guard', 'web')])
            ->get('/broadcast/events');

        $response->assertStatus(200);
        $content = $response->streamedContent ?? '';

        // Only active alerts should be counted
        $this->assertStringContainsString('"active_alerts":1', $content);
    }

    public function test_sse_snapshot_empty_when_no_servers(): void
    {
        $admin = $this->admin;

        $response = $this->withSession(['login_' . config('auth.guard', 'web')])
            ->get('/broadcast/events');

        $response->assertStatus(200);
        $content = $response->streamedContent ?? '';

        $this->assertStringContainsString('"total_servers":0', $content);
    }
}
