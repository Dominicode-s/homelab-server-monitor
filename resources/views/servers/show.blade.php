@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    refreshInterval: null,
    timeRange: '1h',
    cpuData: @json($server->metricsCpu->sortBy('collected_at')->values()),
    memoryData: @json($server->metricsMemory->sortBy('collected_at')->values()),
    networkData: @json($server->metricsNetwork->sortBy('collected_at')->values()),
}">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl font-bold text-white">{{ $server->friendly_name ?? $server->hostname }}</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $server->isOnline() ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $server->isOnline() ? 'bg-green-400 animate-pulse' : 'bg-red-400' }}"></span>
                    {{ ucfirst($server->status) }}
                </span>
            </div>
            <p class="text-sm text-dashboard-muted mt-1">{{ $server->description ?? $server->hostname }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('servers.index') }}" class="text-dashboard-muted hover:text-dashboard-text">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-dashboard-surface rounded-xl p-4 border border-dashboard-border">
            <p class="text-xs text-dashboard-muted">Uptime</p>
            <p class="text-xl font-semibold text-white mt-1">
                {{ $server->metricsSystem->last()?->uptime_seconds ? formatUptime($server->metricsSystem->last()->uptime_seconds) : 'N/A' }}
            </p>
        </div>
        <div class="bg-dashboard-surface rounded-xl p-4 border border-dashboard-border">
            <p class="text-xs text-dashboard-muted">CPU</p>
            <p class="text-xl font-semibold text-white mt-1">
                {{ $server->metricsCpu->last()?->utilisation_percent ? number_format($server->metricsCpu->last()->utilisation_percent, 1) . '%' : 'N/A' }}
            </p>
        </div>
        <div class="bg-dashboard-surface rounded-xl p-4 border border-dashboard-border">
            <p class="text-xs text-dashboard-muted">Memory</p>
            <p class="text-xl font-semibold text-white mt-1">
                {{ $server->metricsMemory->last()?->usage_percent ? number_format($server->metricsMemory->last()->usage_percent, 1) . '%' : 'N/A' }}
            </p>
        </div>
        <div class="bg-dashboard-surface rounded-xl p-4 border border-dashboard-border">
            <p class="text-xs text-dashboard-muted">Last Seen</p>
            <p class="text-xl font-semibold text-white mt-1">
                {{ $server->last_seen_at ? $server->last_seen_at->diffForHumans() : 'Never' }}
            </p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- CPU Chart -->
        <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
            <h3 class="text-lg font-semibold text-white mb-4">CPU Usage</h3>
            <canvas id="cpuChart" height="200"></canvas>
        </div>

        <!-- Memory Chart -->
        <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Memory Usage</h3>
            <canvas id="memoryChart" height="200"></canvas>
        </div>
    </div>

    <!-- Disk Usage -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Disk Usage</h3>
        @forelse($server->metricsDisk->groupBy('mount_point')->map(fn($d) => $d->last())->sortBy('mount_point') as $disk)
            <div class="mb-4 last:mb-0">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-dashboard-muted">{{ $disk->mount_point }}</span>
                    <span class="text-white">{{ number_format($disk->usage_percent, 1) }}%</span>
                </div>
                <div class="w-full bg-dashboard-bg rounded-full h-2">
                    <div class="h-2 rounded-full {{ $disk->usage_percent > 90 ? 'bg-red-500' : ($disk->usage_percent > 75 ? 'bg-yellow-500' : 'bg-primary-500') }}" style="width: {{ $disk->usage_percent }}%"></div>
                </div>
                <div class="text-xs text-dashboard-muted mt-1">
                    {{ formatBytes($disk->used_bytes) }} / {{ formatBytes($disk->total_bytes) }}
                </div>
            </div>
        @empty
            <p class="text-dashboard-muted text-sm">No disk data available.</p>
        @endforelse
    </div>

    <!-- Network Throughput -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Network Throughput</h3>
        <canvas id="networkChart" height="200"></canvas>
        @if($server->metricsNetwork->isEmpty())
            <p class="text-dashboard-muted text-sm mt-2">No network data available.</p>
        @endif
    </div>

    <!-- System Info -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">System Information</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-dashboard-muted">Hostname</dt>
                <dd class="text-white mt-1">{{ $server->hostname }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Operating System</dt>
                <dd class="text-white mt-1">{{ $server->os ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Kernel</dt>
                <dd class="text-white mt-1">{{ $server->kernel_version ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Architecture</dt>
                <dd class="text-white mt-1">{{ $server->architecture ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Agent Version</dt>
                <dd class="text-white mt-1">{{ $server->agent_version ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Registered</dt>
                <dd class="text-white mt-1">{{ $server->registered_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</dd>
            </div>
        </dl>
    </div>

    <!-- Services -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Monitored Services</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-dashboard-border">
                        <th class="px-4 py-2 text-left text-xs font-medium text-dashboard-muted uppercase">Service</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-dashboard-muted uppercase">State</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-dashboard-muted uppercase">Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dashboard-border">
                    @forelse($server->monitoredServices->unique('service_name') as $service)
                        <tr>
                            <td class="px-4 py-2 text-sm text-white">{{ $service->service_name }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $service->state === 'running' ? 'bg-green-500/20 text-green-400' :
                                       ($service->state === 'stopped' ? 'bg-gray-500/20 text-gray-400' :
                                       'bg-red-500/20 text-red-400') }}">
                                    {{ ucfirst($service->state) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-dashboard-muted">
                                {{ $service->is_active ? 'Yes' : 'No' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-4 text-center text-dashboard-muted text-sm">No services monitored</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Docker Containers -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Docker Containers</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-dashboard-border">
                        <th class="px-4 py-2 text-left text-xs font-medium text-dashboard-muted uppercase">Container</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-dashboard-muted uppercase">Image</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-dashboard-muted uppercase">State</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-dashboard-muted uppercase">Health</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-dashboard-muted uppercase">Restarts</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dashboard-border">
                    @forelse($server->dockerContainers->unique('container_name') as $container)
                        <tr>
                            <td class="px-4 py-2 text-sm text-white">{{ $container->container_name }}</td>
                            <td class="px-4 py-2 text-sm text-dashboard-muted">{{ $container->image ?? '-' }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $container->state === 'running' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ ucfirst($container->state) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-dashboard-muted">{{ $container->health ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-dashboard-muted">{{ $container->restart_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-4 text-center text-dashboard-muted text-sm">No Docker data or Docker not available</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Recent Alerts</h3>
        <div class="space-y-3">
            @forelse($server->alerts->take(10) as $alert)
                <div class="flex items-start justify-between p-3 rounded-lg {{ $alert->state === 'active' ? 'bg-yellow-500/10 border border-yellow-500/30' : 'bg-green-500/10 border border-green-500/30' }}">
                    <div>
                        <p class="text-sm font-medium {{ $alert->state === 'active' ? 'text-yellow-300' : 'text-green-300' }}">{{ $alert->title }}</p>
                        <p class="text-xs text-dashboard-muted mt-1">{{ $alert->triggered_at->format('M d, Y H:i') }} &middot; {{ $alert->rule->name ?? 'Unknown rule' }}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $alert->severity === 'critical' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                        {{ $alert->severity }}
                    </span>
                </div>
            @empty
                <p class="text-center py-4 text-dashboard-muted text-sm">No alerts for this server.</p>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CPU Chart
    const cpuCtx = document.getElementById('cpuChart').getContext('2d');
    new Chart(cpuCtx, {
        type: 'line',
        data: {
            labels: @json($server->metricsCpu->sortBy('collected_at')->pluck('collected_at')->map(fn($d) => $d instanceof \Carbon\Carbon ? $d->format('H:i') : date('H:i', strtotime($d)))->toArray()),
            datasets: [{
                label: 'CPU %',
                data: @json($server->metricsCpu->sortBy('collected_at')->pluck('utilisation_percent')->toArray()),
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { display: true, grid: { color: '#334155' }, ticks: { color: '#94a3b8', maxTicksLimit: 6 } },
                y: { display: true, grid: { color: '#334155' }, ticks: { color: '#94a3b8' }, min: 0, max: 100 }
            }
        }
    });

    // Memory Chart
    const memCtx = document.getElementById('memoryChart').getContext('2d');
    new Chart(memCtx, {
        type: 'line',
        data: {
            labels: @json($server->metricsMemory->sortBy('collected_at')->pluck('collected_at')->map(fn($d) => $d instanceof \Carbon\Carbon ? $d->format('H:i') : date('H:i', strtotime($d)))->toArray()),
            datasets: [{
                label: 'Memory %',
                data: @json($server->metricsMemory->sortBy('collected_at')->pluck('usage_percent')->toArray()),
                borderColor: '#a855f7',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { display: true, grid: { color: '#334155' }, ticks: { color: '#94a3b8', maxTicksLimit: 6 } },
                y: { display: true, grid: { color: '#334155' }, ticks: { color: '#94a3b8' }, min: 0, max: 100 }
            }
        }
    });

    // Network Chart
    const netCtx = document.getElementById('networkChart');
    if (netCtx && !@json($server->metricsNetwork->isEmpty())) {
        const netData = @json($server->metricsNetwork->sortBy('collected_at')->values());
        new Chart(netCtx, {
            type: 'line',
            data: {
                labels: netData.map(d => d.collected_at instanceof String ? new Date(d.collected_at).toLocaleTimeString() : new Date(d.collected_at).toLocaleTimeString()),
                datasets: [
                    {
                        label: 'RX',
                        data: netData.map(d => (d.bytes_received || 0) / 1048576),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                    },
                    {
                        label: 'TX',
                        data: netData.map(d => (d.bytes_sent || 0) / 1048576),
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, labels: { color: '#94a3b8' } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' MB';
                            }
                        }
                    }
                },
                scales: {
                    x: { display: true, grid: { color: '#334155' }, ticks: { color: '#94a3b8', maxTicksLimit: 6 } },
                    y: { display: true, grid: { color: '#334155' }, ticks: { color: '#94a3b8' }, title: { display: true, text: 'MB', color: '#94a3b8' } }
                }
            }
        });
    }
});

function formatUptime(seconds) {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    return days > 0 ? `${days}d ${hours}h` : `${hours}h ${Math.floor((seconds % 3600) / 60)}m`;
}

function formatBytes(bytes) {
    if (!bytes) return 'N/A';
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
}

window.formatUptime = formatUptime;
window.formatBytes = formatBytes;
</script>
@endsection
