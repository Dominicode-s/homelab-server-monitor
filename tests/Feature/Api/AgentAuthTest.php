<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Server;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgentAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_authentication_requires_token(): void
    {
        $response = $this->postJson('/api/v1/agents/auth', []);

        $response->assertStatus(422);
    }

    public function test_agent_authentication_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/agents/auth', [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error', 'Invalid or revoked credentials');
    }

    public function test_agent_authentication_with_revoked_token(): void
    {
        $server = Server::factory()->create(['hostname' => 'test-host']);
        $credential = Credential::factory()->create([
            'server_id' => $server->id,
            'token' => 'valid-token',
            'revoked' => true,
        ]);

        $response = $this->postJson('/api/v1/agents/auth', [
            'token' => 'valid-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_agent_can_authenticate_with_valid_token(): void
    {
        $server = Server::factory()->create(['hostname' => 'test-host']);
        $token = Credential::generateToken();
        Credential::factory()->create([
            'server_id' => $server->id,
            'token' => $token,
        ]);

        $response = $this->postJson('/api/v1/agents/auth', [
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('server_id', $server->id);
    }
}
