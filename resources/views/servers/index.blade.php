@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-white">Servers</h2>
        <button onclick="document.getElementById('addServerModal').classList.remove('hidden')"
            class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-4 py-2 rounded-lg transition-colors">
            Add Server
        </button>
    </div>

    <!-- Server List -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border overflow-hidden">
        <table class="min-w-full divide-y divide-dashboard-border">
            <thead class="bg-dashboard-surface">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-dashboard-muted uppercase tracking-wider">Server</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-dashboard-muted uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-dashboard-muted uppercase tracking-wider">CPU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-dashboard-muted uppercase tracking-wider">Memory</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-dashboard-muted uppercase tracking-wider">Disk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-dashboard-muted uppercase tracking-wider">Last Seen</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-dashboard-muted uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-dashboard-border">
                @forelse($servers as $server)
                    <tr class="hover:bg-dashboard-bg/50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-white">{{ $server->friendly_name ?? $server->hostname }}</div>
                            <div class="text-sm text-dashboard-muted">{{ $server->hostname }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $server->isOnline() ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $server->isOnline() ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                {{ ucfirst($server->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php $cpu = $server->metricsCpu->last(); @endphp
                            <div class="flex items-center">
                                <div class="w-16 bg-dashboard-bg rounded-full h-1.5 mr-2">
                                    <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ $cpu?->utilisation_percent ?? 0 }}%"></div>
                                </div>
                                <span class="text-sm text-dashboard-muted">{{ $cpu?->utilisation_percent ? number_format($cpu->utilisation_percent, 1) . '%' : '-' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php $mem = $server->metricsMemory->last(); @endphp
                            <div class="flex items-center">
                                <div class="w-16 bg-dashboard-bg rounded-full h-1.5 mr-2">
                                    <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ $mem?->usage_percent ?? 0 }}%"></div>
                                </div>
                                <span class="text-sm text-dashboard-muted">{{ $mem?->usage_percent ? number_format($mem->usage_percent, 1) . '%' : '-' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php $disks = $server->metricsDisk->where('collected_at', $server->metricsDisk->max('collected_at')); @endphp
                            @php $rootDisk = $disks->firstWhere('mount_point', '/'); @endphp
                            @if($rootDisk)
                                <span class="text-sm text-dashboard-muted">{{ number_format($rootDisk->usage_percent, 1) }}%</span>
                            @else
                                <span class="text-sm text-dashboard-muted">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-dashboard-muted">
                            {{ $server->last_seen_at ? $server->last_seen_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('servers.show', $server) }}" class="text-primary-400 hover:text-primary-300 mr-3">View</a>
                            <form method="POST" action="{{ route('servers.destroy', $server) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-dashboard-muted">
                            No servers registered yet. Click "Add Server" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Add Server Modal -->
    <div id="addServerModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
        <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-white mb-4">Register New Server</h3>
            <form method="POST" action="{{ route('servers.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-dashboard-muted mb-1">Hostname *</label>
                        <input type="text" name="hostname" required class="w-full px-3 py-2 bg-dashboard-bg border border-dashboard-border rounded-lg text-dashboard-text focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dashboard-muted mb-1">Friendly Name</label>
                        <input type="text" name="friendly_name" class="w-full px-3 py-2 bg-dashboard-bg border border-dashboard-border rounded-lg text-dashboard-text focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dashboard-muted mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 bg-dashboard-bg border border-dashboard-border rounded-lg text-dashboard-text focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('addServerModal').classList.add('hidden')"
                        class="px-4 py-2 text-dashboard-muted hover:text-dashboard-text">Cancel</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg">Register</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
