<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Credential;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_credential_token_is_generated_correctly(): void
    {
        $token = Credential::generateToken();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function test_valid_credential_passes_validation(): void
    {
        $credential = Credential::factory()->create([
            'revoked' => false,
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertTrue($credential->isValid());
    }

    public function test_revoked_credential_fails_validation(): void
    {
        $credential = Credential::factory()->create([
            'revoked' => true,
        ]);

        $this->assertFalse($credential->isValid());
    }

    public function test_expired_credential_fails_validation(): void
    {
        $credential = Credential::factory()->create([
            'expires_at' => now()->subDays(1),
        ]);

        $this->assertFalse($credential->isValid());
    }

    public function test_credential_can_be_revoked(): void
    {
        $credential = Credential::factory()->create([
            'revoked' => false,
            'revoked_at' => null,
        ]);

        $credential->revoke();

        $this->assertTrue($credential->refresh()->revoked);
        $this->assertNotNull($credential->refresh()->revoked_at);
    }
}
