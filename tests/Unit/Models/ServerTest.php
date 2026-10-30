<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Server;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_can_be_created(): void
    {
        $server = Server::create([
            'hostname' => 'test-host',
            'friendly_name' => 'Test Server',
            'description' => 'A test server',
            'os' => 'Ubuntu 22.04',
            'architecture' => 'x86_64',
        ]);

        $this->assertDatabaseHas('servers', [
            'hostname' => 'test-host',
            'friendly_name' => 'Test Server',
        ]);
    }

    public function test_server_has_unique_hostname(): void
    {
        Server::create(['hostname' => 'unique-host']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Server::create(['hostname' => 'unique-host']);
    }

    public function test_server_status_updates_correctly(): void
    {
        $server = Server::create([
            'hostname' => 'test-host',
            'last_seen_at' => now()->subMinutes(1),
        ]);

        $server->updateStatus();
        $this->assertEquals('online', $server->status);

        $server->last_seen_at = now()->subHours(1);
        $server->save();
        $server->updateStatus();
        $this->assertEquals('offline', $server->status);
    }

    public function test_server_relationships_exist(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(Credential::class, $server->credentials()->firstOrNew());
        $this->assertTrue($server->metricsCpu()->getResults()->isEmpty());
        $this->assertTrue($server->metricsMemory()->getResults()->isEmpty());
    }
}
