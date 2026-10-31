@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <a href="{{ route('alerts.index') }}" class="text-dashboard-muted hover:text-dashboard-text">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="text-2xl font-bold text-white">{{ $alert->title }}</h2>
            <span class="text-xs px-2 py-0.5 rounded-full {{ $alert->severity === 'critical' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                {{ $alert->severity }}
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full {{ $alert->state === 'active' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-green-500/20 text-green-400' }}">
                {{ ucfirst($alert->state) }}
            </span>
        </div>
        @if($alert->state === 'active')
            <form method="POST" action="{{ route('alerts.resolve', $alert) }}">
                @csrf
                <button type="submit" class="ml-4 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                    Resolve Alert
                </button>
            </form>
        @endif
    </div>

    <!-- Alert Details -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Details</h3>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-dashboard-muted">Server</dt>
                <dd class="text-white mt-1">
                    @if($alert->server)
                        <a href="{{ route('servers.show', $alert->server) }}" class="text-primary-400 hover:text-primary-300">
                            {{ $alert->server->friendly_name ?? $alert->server->hostname }}
                        </a>
                    @else
                        <span class="text-dashboard-muted">Unknown</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Rule</dt>
                <dd class="text-white mt-1">{{ $alert->rule->name ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Triggered</dt>
                <dd class="text-white mt-1">{{ $alert->triggered_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-dashboard-muted">Resolved</dt>
                <dd class="text-white mt-1">{{ $alert->resolved_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
            </div>
        </dl>

        @if($alert->description)
            <div class="mt-4">
                <dt class="text-sm text-dashboard-muted">Description</dt>
                <dd class="text-dashboard-text mt-1">{{ $alert->description }}</dd>
            </div>
        @endif
    </div>

    <!-- Metadata -->
    @if($alert->metadata)
        <div class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Alert Data</h3>
            <pre class="text-sm text-dashboard-text bg-dashboard-bg p-4 rounded-lg overflow-x-auto">{{ json_encode($alert->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>
@endsection
