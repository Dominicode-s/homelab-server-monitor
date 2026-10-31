@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-white">Settings</h2>
    </div>

    <!-- Application Info -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Application</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-dashboard-muted">Version</dt>
                <dd class="text-white mt-1">1.0.0</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Environment</dt>
                <dd class="text-white mt-1">{{ config('app.env') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Database</dt>
                <dd class="text-white mt-1">{{ config('database.default') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">PHP Version</dt>
                <dd class="text-white mt-1">{{ PHP_VERSION }}</dd>
            </div>
        </dl>
    </div>

    <!-- Retention Configuration -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Retention</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-dashboard-muted">Detailed Metrics Retention</dt>
                <dd class="text-white mt-1">{{ config('sentinel.security.retention_days.detailed', 7) }} days</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Offline Threshold</dt>
                <dd class="text-white mt-1">{{ config('sentinel.security.offline_threshold_seconds', 120) }} seconds</dd>
            </div>
        </dl>
    </div>

    <!-- Scheduled Tasks -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Scheduled Tasks</h3>
        <p class="text-sm text-dashboard-muted mb-4">Configure these in your crontab or Docker Compose supervisor:</p>
        <div class="space-y-3">
            <div class="bg-dashboard-bg p-3 rounded-lg">
                <code class="text-sm text-primary-400">* * * * * php artisan sentinel:check-offline</code>
                <p class="text-xs text-dashboard-muted mt-1">Check for offline servers (every minute)</p>
            </div>
            <div class="bg-dashboard-bg p-3 rounded-lg">
                <code class="text-sm text-primary-400">*/5 * * * * php artisan sentinel:evaluate-alerts</code>
                <p class="text-xs text-dashboard-muted mt-1">Evaluate alert rules (every 5 minutes)</p>
            </div>
            <div class="bg-dashboard-bg p-3 rounded-lg">
                <code class="text-sm text-primary-400">0 3 * * * php artisan sentinel:cleanup-metrics</code>
                <p class="text-xs text-dashboard-muted mt-1">Clean up old metrics (daily at 3 AM)</p>
            </div>
        </div>
    </div>

    <!-- Admin Profile -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Admin Account</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-dashboard-muted">Name</dt>
                <dd class="text-white mt-1">{{ auth()->user()->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Email</dt>
                <dd class="text-white mt-1">{{ auth()->user()->email }}</dd>
            </div>
        </dl>
        <p class="text-xs text-dashboard-muted mt-4">To change your password, update the admin record directly in the database or implement a password change form.</p>
    </div>
</div>
@endsection
