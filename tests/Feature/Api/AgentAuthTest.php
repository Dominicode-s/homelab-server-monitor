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
            'token' => 'invalid-token-xxxxxxxx',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error', 'Invalid or revoked credentials');
    }

    public function test_agent_authentication_with_revoked_token(): void
    {
        $server = Server::factory()->create(['hostname' => 'test-host']);
        $plainToken = Credential::generateToken();
        $credential = Credential::where('server_id', $server->id)->first();
        $credential->revoke();

        $response = $this->postJson('/api/v1/agents/auth', [
            'token' => $plainToken,
        ]);

        $response->assertStatus(401);
    }

    public function test_agent_can_authenticate_with_valid_token(): void
    {
        $server = Server::factory()->create(['hostname' => 'test-host']);
        $plainToken = Credential::generateToken();

        $response = $this->postJson('/api/v1/agents/auth', [
            'token' => $plainToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('server_id', $server->id);
    }

    public function test_token_hash_not_exposed_in_responses(): void
    {
        $server = Server::factory()->create(['hostname' => 'test-host']);
        $plainToken = Credential::generateToken();

        $response = $this->postJson('/api/v1/agents/auth', [
            'token' => $plainToken,
        ]);

        $response->assertStatus(200);
        // Response should not contain the full token
        $body = $response->getContent();
        $this->assertStringNotContainsString(substr($plainToken, 8), $body);
    }
}
