@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-white">Dashboard</h2>
        <div class="text-sm text-dashboard-muted">
            Last updated: <span id="last-update">{{ now()->format('H:i:s') }}</span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Servers -->
        <div class="bg-dashboard-surface rounded-xl p-6 border border-dashboard-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-dashboard-muted">Total Servers</p>
                    <p class="text-3xl font-bold text-white mt-2">{{ $totalServers }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                </div>
            </div>
        </div>

        <!-- Online -->
        <div class="bg-dashboard-surface rounded-xl p-6 border border-dashboard-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-dashboard-muted">Online</p>
                    <p class="text-3xl font-bold text-green-400 mt-2">{{ $onlineServers }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Offline -->
        <div class="bg-dashboard-surface rounded-xl p-6 border border-dashboard-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-dashboard-muted">Offline</p>
                    <p class="text-3xl font-bold text-red-400 mt-2">{{ $offlineServers }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-red-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Active Alerts -->
        <div class="bg-dashboard-surface rounded-xl p-6 border border-dashboard-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-dashboard-muted">Active Alerts</p>
                    <p class="text-3xl font-bold {{ $activeAlerts > 0 ? 'text-yellow-400' : 'text-green-400' }} mt-2">{{ $activeAlerts }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Fleet Health -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Server Cards -->
        <div class="lg:col-span-2 bg-dashboard-surface rounded-xl border border-dashboard-border">
            <div class="p-6 border-b border-dashboard-border flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Servers</h3>
                <a href="{{ route('servers.index') }}" class="text-sm text-primary-400 hover:text-primary-300">View all</a>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($servers->take(4) as $server)
                    <a href="{{ route('servers.show', $server) }}" class="block p-4 rounded-lg border border-dashboard-border hover:border-primary-500 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-white">{{ $server->friendly_name ?? $server->hostname }}</h4>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $server->isOnline() ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $server->isOnline() ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                {{ ucfirst($server->status) }}
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <div class="flex justify-between text-xs text-dashboard-muted mb-1">
                                    <span>CPU</span>
                                    <span>{{ $server->metricsCpu->last()?->utilisation_percent ? number_format($server->metricsCpu->last()->utilisation_percent, 1) . '%' : 'N/A' }}</span>
                                </div>
                                <div class="w-full bg-dashboard-bg rounded-full h-1.5">
                                    <div class="bg-primary-500 h-1.5 rounded-full {{ $server->metricsCpu->last()?->utilisation_percent ? 'transition-all' : '' }}" style="width: {{ $server->metricsCpu->last()?->utilisation_percent ?? 0 }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs text-dashboard-muted mb-1">
                                    <span>Memory</span>
                                    <span>{{ $server->metricsMemory->last()?->usage_percent ? number_format($server->metricsMemory->last()->usage_percent, 1) . '%' : 'N/A' }}</span>
                                </div>
                                <div class="w-full bg-dashboard-bg rounded-full h-1.5">
                                    <div class="bg-purple-500 h-1.5 rounded-full {{ $server->metricsMemory->last()?->usage_percent ? 'transition-all' : '' }}" style="width: {{ $server->metricsMemory->last()?->usage_percent ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-dashboard-muted">
                            Last seen: {{ $server->last_seen_at ? $server->last_seen_at->diffForHumans() : 'Never' }}
                        </div>
                    </a>
                @empty
                    <div class="col-span-2 text-center py-8 text-dashboard-muted">
                        No servers registered yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Alerts -->
        <div class="bg-dashboard-surface rounded-xl border border-dashboard-border">
            <div class="p-6 border-b border-dashboard-border flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Recent Alerts</h3>
                <a href="{{ route('alerts.index') }}" class="text-sm text-primary-400 hover:text-primary-300">View all</a>
            </div>
            <div class="p-4 space-y-3 max-h-[500px] overflow-y-auto">
                @forelse($recentAlerts as $alert)
                    <div class="p-3 rounded-lg {{ $alert->state === 'active' ? 'bg-yellow-500/10 border border-yellow-500/30' : 'bg-green-500/10 border border-green-500/30' }}">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium {{ $alert->state === 'active' ? 'text-yellow-300' : 'text-green-300' }}">{{ $alert->title }}</p>
                                <p class="text-xs text-dashboard-muted mt-1">
                                    {{ $alert->server?->friendly_name ?? $alert->server?->hostname ?? 'Unknown' }}
                                    &middot; {{ $alert->triggered_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $alert->severity === 'critical' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                                {{ $alert->severity }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-center py-8 text-dashboard-muted text-sm">No alerts. Everything is healthy!</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 30 seconds — partial DOM updates to preserve Alpine state
async function refreshDashboard() {
    try {
        const response = await fetch('{{ route('dashboard') }}', {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
        });
        if (!response.ok) return;

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Update stat numbers
        const statCards = doc.querySelectorAll('.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 > div');
        const currentCards = document.querySelectorAll('.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 > div');
        statCards.forEach((card, i) => {
            if (currentCards[i]) {
                const numEl = card.querySelector('.text-3xl');
                const currentNum = currentCards[i].querySelector('.text-3xl');
                if (numEl && currentNum) currentNum.textContent = numEl.textContent.trim();
            }
        });

        // Update timestamp
        const tsEl = doc.querySelector('#last-update');
        if (tsEl) document.getElementById('last-update').textContent = tsEl.textContent.trim();

        // Update server cards
        const newServerCards = doc.querySelectorAll('.lg\\:col-span-2 .grid-cols-1.md\\:grid-cols-2 > a');
        const currentServerCards = document.querySelectorAll('.lg\\:col-span-2 .grid-cols-1.md\\:grid-cols-2 > a');
        newServerCards.forEach((card, i) => {
            if (currentServerCards[i]) currentServerCards[i].replaceWith(card.cloneNode(true));
        });

        // Update recent alerts
        const newAlerts = doc.querySelectorAll('.p-4.space-y-3 > div');
        const currentAlerts = document.querySelector('.p-4.space-y-3');
        if (newAlerts.length > 0 && currentAlerts) {
            currentAlerts.innerHTML = '';
            newAlerts.forEach(a => currentAlerts.appendChild(a.cloneNode(true)));
        } else if (newAlerts.length === 0 && currentAlerts) {
            const emptyMsg = doc.querySelector('.p-4.space-y-3 p');
            if (emptyMsg) {
                currentAlerts.innerHTML = '<p class="text-center py-8 text-dashboard-muted text-sm">No alerts. Everything is healthy!</p>';
            }
        }

        document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
    } catch (e) {
        // Silently fail — next interval will retry
    }
}

setInterval(refreshDashboard, 30000);
</script>
@endsection
