<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertNotification extends Notification
{
    use Queueable;

    public function __construct(protected Alert $alert)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(Alert $notifiable): MailMessage
    {
        $severityLabel = match ($this->alert->severity) {
            'critical' => '🔴 CRITICAL',
            'warning' => '🟡 WARNING',
            default => strtoupper($this->alert->severity),
        };

        $serverName = $this->alert->server?->friendly_name ?? $this->alert->server?->hostname ?? 'Unknown';

        $mailMessage = (new MailMessage())
            ->subject("[{$severityLabel}] Homelab Sentinel Alert: {$this->alert->title}")
            ->subject('[Homelab Sentinel Alert] ' . $this->alert->title)
            ->line("Server: {$serverName}")
            ->line("Severity: {$severityLabel}")
            ->line("Title: {$this->alert->title}");

        if ($this->alert->description) {
            $mailMessage->line("Description: {$this->alert->description}");
        }

        $metadata = $this->alert->metadata ?? [];
        if (! empty($metadata)) {
            $mailMessage->line('Details:')
                ->line('```')
                ->line(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                ->line('```');
        }

        $mailMessage->line("Triggered at: {$this->alert->triggered_at->timezone('UTC')}")
            ->action('View in Dashboard', url('/alerts'))
            ->line('---')
            ->line('Homelab Sentinel Monitoring System');

        return $mailMessage;
    }

    public function routeNotificationForMail(): array|string|null
    {
        return config('sentinel.notifications.admin_email');
    }
}
