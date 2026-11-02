<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Ensure default alert rules exist
        AlertRule::firstOrCreate(
            ['name' => 'High CPU'],
            [
                'type' => 'cpu_usage',
                'threshold' => 90,
                'duration_minutes' => 5,
                'is_enabled' => true,
            ]
        );

        AlertRule::firstOrCreate(
            ['name' => 'Host Offline'],
            [
                'type' => 'host_offline',
                'threshold' => null,
                'duration_minutes' => 2,
                'is_enabled' => true,
            ]
        );
    }

    public function test_command_returns_success_when_notifications_disabled(): void
    {
        Config::set('sentinel.notifications.enabled', false);

        $this->artisan('sentinel:notify-admins')
            ->expectsOutput('Email notifications are disabled.')
            ->assertExitCode(0);
    }

    public function test_command_returns_failure_when_no_admin_email(): void
    {
        Config::set('sentinel.notifications.enabled', true);
        Config::set('sentinel.notifications.admin_email', null);

        $this->artisan('sentinel:notify-admins')
            ->expectsOutput('SENTINEL_ADMIN_EMAIL is not configured.')
            ->assertExitCode(1);
    }

    public function test_command_sends_email_for_unnotified_active_alert(): void
    {
        Config::set('sentinel.notifications.enabled', true);
        Config::set('sentinel.notifications.admin_email', 'admin@example.com');

        $server = Server::factory()->create([
            'friendly_name' => 'web-server-01',
            'hostname' => 'web01.local',
        ]);

        $rule = AlertRule::firstOrCreate(
            ['name' => 'High CPU'],
            [
                'type' => 'cpu_usage',
                'threshold' => 90,
                'duration_minutes' => 5,
                'is_enabled' => true,
            ]
        );

        $alert = Alert::create([
            'server_id' => $server->id,
            'rule_id' => $rule->id,
            'title' => 'High CPU Usage',
            'description' => 'CPU usage exceeded 90%',
            'severity' => 'warning',
            'state' => 'active',
            'metadata' => ['metric_value' => 95.5],
            'triggered_at' => now()->subMinutes(5),
        ]);

        $this->artisan('sentinel:notify-admins');

        Notification::assertSentTo(
            ['admin@example.com' => 'mail'],
            \App\Notifications\AlertNotification::class,
            function ($notification, $channels) use ($alert) {
                return $notification->alert->id === $alert->id;
            }
        );

        $alert->refresh();
        $this->assertNotNull($alert->notification_channels);
        $this->assertArrayHasKey('email', $alert->notification_channels);
    }

    public function test_command_skips_already_notified_alerts(): void
    {
        Config::set('sentinel.notifications.enabled', true);
        Config::set('sentinel.notifications.admin_email', 'admin@example.com');

        $server = Server::factory()->create([
            'friendly_name' => 'db-server-01',
            'hostname' => 'db01.local',
        ]);

        $rule = AlertRule::firstOrCreate(
            ['name' => 'High CPU'],
            [
                'type' => 'cpu_usage',
                'threshold' => 90,
                'duration_minutes' => 5,
                'is_enabled' => true,
            ]
        );

        Alert::create([
            'server_id' => $server->id,
            'rule_id' => $rule->id,
            'title' => 'High CPU Usage',
            'severity' => 'warning',
            'state' => 'active',
            'metadata' => [],
            'triggered_at' => now()->subMinutes(10),
            'notification_channels' => ['email' => now()->subMinute()->toISOString()],
        ]);

        $this->artisan('sentinel:notify-admins')
            ->expectsOutput('No unnotified alerts to process.');

        Notification::assertNotSentTo(
            ['admin@example.com' => 'mail'],
            \App\Notifications\AlertNotification::class
        );
    }

    public function test_command_skips_resolved_alerts(): void
    {
        Config::set('sentinel.notifications.enabled', true);
        Config::set('sentinel.notifications.admin_email', 'admin@example.com');

        Alert::create([
            'server_id' => null,
            'rule_id' => AlertRule::first()->id,
            'title' => 'Test Alert',
            'severity' => 'warning',
            'state' => 'resolved',
            'metadata' => [],
            'triggered_at' => now()->subHour(),
        ]);

        $this->artisan('sentinel:notify-admins')
            ->expectsOutput('No unnotified alerts to process.');
    }

    public function test_command_sends_nothing_when_no_active_alerts(): void
    {
        Config::set('sentinel.notifications.enabled', true);
        Config::set('sentinel.notifications.admin_email', 'admin@example.com');

        $this->artisan('sentinel:notify-admins')
            ->expectsOutput('No unnotified alerts to process.');
    }

    public function test_notification_email_contains_server_and_severity_info(): void
    {
        Config::set('sentinel.notifications.enabled', true);
        Config::set('sentinel.notifications.admin_email', 'admin@example.com');

        $server = Server::factory()->create([
            'friendly_name' => 'api-server-01',
            'hostname' => 'api01.local',
        ]);

        $rule = AlertRule::firstOrCreate(
            ['name' => 'Disk Usage'],
            [
                'type' => 'disk_usage',
                'threshold' => 85,
                'duration_minutes' => 5,
                'is_enabled' => true,
            ]
        );

        $alert = Alert::create([
            'server_id' => $server->id,
            'rule_id' => $rule->id,
            'title' => 'High Disk Usage (/data)',
            'description' => 'Disk usage exceeded 85%',
            'severity' => 'critical',
            'state' => 'active',
            'metadata' => ['mount_point' => '/data', 'metric_value' => 92.3],
            'triggered_at' => now()->subMinutes(3),
        ]);

        $this->artisan('sentinel:notify-admins');

        Notification::assertSentTo(
            ['admin@example.com' => 'mail'],
            \App\Notifications\AlertNotification::class,
            function ($notification, $channels) use ($alert) {
                $mailMessage = $notification->toMail(null);

                return str($mailMessage->subject)->contains('CRITICAL')
                    && str($mailMessage->subject)->contains('High Disk Usage')
                    && str($mailMessage->body)->contains('api-server-01')
                    && str($mailMessage->body)->contains('Critical')
                    && str($mailMessage->body)->contains('/data');
            }
        );
    }
}
