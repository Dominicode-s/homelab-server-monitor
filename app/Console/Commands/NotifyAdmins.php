<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Notifications\AlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyAdmins extends Command
{
    protected $signature = 'sentinel:notify-admins';

    protected $description = 'Send email notifications for newly triggered alerts';

    public function handle(): int
    {
        if (! config('sentinel.notifications.enabled', false)) {
            $this->info('Email notifications are disabled.');

            return Command::SUCCESS;
        }

        $adminEmail = config('sentinel.notifications.admin_email');
        if (empty($adminEmail)) {
            $this->error('SENTINEL_ADMIN_EMAIL is not configured.');

            return Command::FAILURE;
        }

        // Find active alerts that haven't been notified via email yet
        $unnotifiedAlerts = Alert::where('state', 'active')
            ->where(function ($query): void {
                $query->whereNull('notification_channels')
                    ->orWhereNull('notification_channels->email');
            })
            ->whereNotNull('triggered_at')
            ->orderBy('triggered_at')
            ->get();

        if ($unnotifiedAlerts->isEmpty()) {
            $this->info('No unnotified alerts to process.');

            return Command::SUCCESS;
        }

        $sent = 0;
        foreach ($unnotifiedAlerts as $alert) {
            try {
                Notification::route('mail', $adminEmail)->notify(new AlertNotification($alert));

                // Mark as notified
                $channels = $alert->notification_channels ?? [];
                if (! is_array($channels)) {
                    $channels = [];
                }
                $channels['email'] = now()->toISOString();
                $alert->update(['notification_channels' => $channels]);

                $this->info("Notified admin about alert #{$alert->id}: {$alert->title}");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Failed to send notification for alert #{$alert->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} email notification(s).");

        return Command::SUCCESS;
    }
}
