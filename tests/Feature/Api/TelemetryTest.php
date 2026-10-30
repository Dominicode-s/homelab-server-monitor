<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Server;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TelemetryTest extends TestCase
{
    use RefreshDatabase;

    private function getValidPayload(array $overrides = []): array
    {
        return array_merge([
            'server_info' => [
                'hostname' => 'test-server-' . uniqid(),
                'os' => 'Ubuntu 22.04 LTS',
                'kernel_version' => '5.15.0-generic',
                'architecture' => 'x86_64',
                'agent_version' => '1.0.0',
            ],
            'cpu' => [
                'utilisation_percent' => 45.5,
                'core_count' => 4,
                'load_1m' => 1.2,
                'load_5m' => 0.8,
                'load_15m' => 0.5,
            ],
            'memory' => [
                'total_bytes' => 8589934592,
                'used_bytes' => 4294967296,
                'usage_percent' => 50.0,
                'swap_total_bytes' => 2147483648,
                'swap_used_bytes' => 0,
                'swap_usage_percent' => 0.0,
            ],
            'disks' => [
                [
                    'mount_point' => '/',
                    'total_bytes' => 107374182400,
                    'used_bytes' => 53687091200,
                    'usage_percent' => 50.0,
                ],
            ],
            'network' => [
                [
                    'interface_name' => 'eth0',
                    'bytes_sent' => 1000000,
                    'bytes_received' => 2000000,
                    'status' => 'up',
                ],
            ],
            'system' => [
                'uptime_seconds' => 86400,
            ],
        ], $overrides);
    }

    public function test_telemetry_ingestion_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/telemetry', $this->getValidPayload());

        $response->assertStatus(401);
    }

    public function test_agent_can_submit_telemetry(): void
    {
        $server = Server::factory()->create(['hostname' => 'existing-server']);
        $credential = Credential::factory()->create([
            'server_id' => $server->id,
            'token' => 'test-token',
        ]);

        $payload = $this->getValidPayload([
            'server_info' => [
                'hostname' => 'existing-server',
            ],
        ]);

        $response = $this->withHeader('X-Agent-Token', 'test-token')
            ->postJson('/api/v1/telemetry', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        // Verify server was updated
        $server->refresh();
        $this->assertNotNull($server->last_seen_at);
        $this->assertEquals('online', $server->status);

        // Verify metrics were stored
        $this->assertDatabaseHas('metrics_cpu', [
            'server_id' => $server->id,
            'utilisation_percent' => 45.5,
        ]);

        $this->assertDatabaseHas('metrics_memory', [
            'server_id' => $server->id,
            'usage_percent' => 50.0,
        ]);

        $this->assertDatabaseHas('metrics_disk', [
            'server_id' => $server->id,
            'mount_point' => '/',
        ]);
    }

    public function test_new_server_is_created_on_telemetry(): void
    {
        $newHostname = 'brand-new-server-' . uniqid();
        $token = Credential::generateToken();
        $serverId = \DB::table('servers')->insertGetId([
            'hostname' => $newHostname,
            'status' => 'offline',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('credentials')->insert([
            'server_id' => $serverId,
            'token' => $token,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->getValidPayload([
            'server_info' => [
                'hostname' => $newHostname,
                'os' => 'Ubuntu 22.04 LTS',
            ],
        ]);

        $response = $this->withHeader('X-Agent-Token', $token)
            ->postJson('/api/v1/telemetry', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('servers', [
            'hostname' => $newHostname,
            'os' => 'Ubuntu 22.04 LTS',
        ]);
    }

    public function test_invalid_payload_is_rejected(): void
    {
        $server = Server::factory()->create();
        $credential = Credential::factory()->create([
            'server_id' => $server->id,
            'token' => 'test-token',
        ]);

        $response = $this->withHeader('X-Agent-Token', 'test-token')
            ->postJson('/api/v1/telemetry', [
                'server_info' => [
                    'hostname' => '',  // Invalid - empty
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_cpu_percent_must_be_between_0_and_100(): void
    {
        $server = Server::factory()->create();
        $credential = Credential::factory()->create([
            'server_id' => $server->id,
            'token' => 'test-token',
        ]);

        $payload = $this->getValidPayload([
            'cpu' => ['utilisation_percent' => 150.0],
        ]);

        $response = $this->withHeader('X-Agent-Token', 'test-token')
            ->postJson('/api/v1/telemetry', $payload);

        $response->assertStatus(422);
    }
}
