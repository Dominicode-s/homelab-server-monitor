<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Server;
use App\Models\Credential;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class DegradedStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_is_online_when_reporting_freshly(): void
    {
        $server = Server::create([
            'hostname' => 'test-host',
            'last_seen_at' => now()->subSeconds(30),
        ]);

        $this->assertTrue($server->isOnline());
        $this->assertFalse($server->isOffline());
        $this->assertFalse($server->isDegraded());
    }

    public function test_server_is_offline_when_stale(): void
    {
        $server = Server::create([
            'hostname' => 'test-host',
            'last_seen_at' => now()->subMinutes(5),
        ]);

        $this->assertFalse($server->isOnline());
        $this->assertTrue($server->isOffline());
    }

    public function test_server_becomes_degraded_when_cpu_high(): void
    {
        config(['sentinel.alerts.cpu_usage_percent' => 90]);

        $server = Server::create([
            'hostname' => 'test-host',
            'last_seen_at' => now()->subSeconds(30),
        ]);

        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now()->subSeconds(10),
            'utilisation_percent' => 95.0,
        ]);

        $server->refresh();
        $this->assertTrue($server->isDegraded());
    }

    public function test_server_becomes_degraded_when_memory_high(): void
    {
        config(['sentinel.alerts.memory_usage_percent' => 90]);

        $server = Server::create([
            'hostname' => 'test-host',
            'last_seen_at' => now()->subSeconds(30),
        ]);

        MetricMemory::create([
            'server_id' => $server->id,
            'collected_at' => now()->subSeconds(10),
            'usage_percent' => 95.0,
        ]);

        $server->refresh();
        $this->assertTrue($server->isDegraded());
    }

    public function test_update_status_sets_degraded_correctly(): void
    {
        config(['sentinel.alerts.cpu_usage_percent' => 90]);

        $server = Server::create([
            'hostname' => 'test-host',
            'status' => 'online',
            'last_seen_at' => now()->subSeconds(30),
        ]);

        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'utilisation_percent' => 95.0,
        ]);

        $server->updateStatus();
        $this->assertEquals('degraded', $server->status);
    }

    public function test_degraded_overrides_online(): void
    {
        config(['sentinel.alerts.cpu_usage_percent' => 90]);

        $server = Server::create([
            'hostname' => 'test-host',
            'status' => 'online',
            'last_seen_at' => now()->subSeconds(30),
        ]);

        // High CPU makes it degraded
        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'utilisation_percent' => 95.0,
        ]);

        $server->updateStatus();
        $this->assertEquals('degraded', $server->status);

        // CPU returns to normal
        MetricCpu::create([
            'server_id' => $server->id,
            'collected_at' => now(),
            'utilisation_percent' => 45.0,
        ]);

        $server->updateStatus();
        $this->assertEquals('online', $server->status);
    }

    public function test_credential_token_is_hashed_on_creation(): void
    {
        $plainToken = Credential::generateToken();

        // The token should be stored as a hash, not plaintext
        $credential = \App\Models\Credential::first();
        $this->assertNotNull($credential->token_hash);
        $this->assertEmpty($credential->token); // plain token should not exist on model

        // Hash check should pass
        $this->assertTrue($credential->verifyToken($plainToken));

        // Wrong token should fail
        $this->assertFalse($credential->verifyToken('wrong-token'));
    }

    public function test_credential_prefix_is_stored_for_lookup(): void
    {
        $plainToken = Credential::generateToken();
        $credential = \App\Models\Credential::first();

        $this->assertEquals(substr($plainToken, 0, 8), $credential->token_prefix);
        $this->assertNotEmpty($credential->token_prefix);
    }

    public function test_authenticate_agent_uses_hash_verification(): void
    {
        $server = \App\Models\Server::factory()->create(['hostname' => 'test-host']);
        $plainToken = Credential::generateToken();

        $response = $this->postJson('/api/v1/agents/auth', [
            'token' => $plainToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('authenticated', true);
    }

    public function test_revoked_credential_fails_hash_verification(): void
    {
        $server = \App\Models\Server::factory()->create(['hostname' => 'test-host']);
        $plainToken = Credential::generateToken();
        $credential = \App\Models\Credential::first();
        $credential->revoke();

        $response = $this->postJson('/api/v1/agents/auth', [
            'token' => $plainToken,
        ]);

        $response->assertStatus(401);
    }
}
