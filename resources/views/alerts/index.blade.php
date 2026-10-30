@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-white">Alerts</h2>
    </div>

    <!-- Active Alerts -->
    <div class="bg-dashboard-surface rounded-xl border border-dashboard-border overflow-hidden">
        <div class="p-6 border-b border-dashboard-border">
            <h3 class="text-lg font-semibold text-white">All Alerts</h3>
        </div>

        <div class="divide-y divide-dashboard-border">
            @forelse($alerts as $alert)
                <div class="p-6 {{ $alert->state === 'active' ? 'bg-yellow-500/5' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-sm font-medium {{ $alert->state === 'active' ? 'text-yellow-300' : 'text-green-300' }}">{{ $alert->title }}</h4>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $alert->severity === 'critical' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                                    {{ $alert->severity }}
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $alert->state === 'active' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-green-500/20 text-green-400' }}">
                                    {{ ucfirst($alert->state) }}
                                </span>
                            </div>
                            <p class="text-sm text-dashboard-muted mt-1">
                                Server: {{ $alert->server?->friendly_name ?? $alert->server?->hostname ?? 'Unknown' }}
                                &middot; Rule: {{ $alert->rule->name ?? 'N/A' }}
                            </p>
                            <p class="text-xs text-dashboard-muted mt-1">
                                Triggered: {{ $alert->triggered_at?->format('Y-m-d H:i:s') ?? 'N/A' }}
                                @if($alert->resolved_at)
                                    &middot; Resolved: {{ $alert->resolved_at->format('Y-m-d H:i:s') }}
                                @endif
                            </p>
                            @if($alert->description)
                                <p class="text-sm text-dashboard-muted mt-2">{{ $alert->description }}</p>
                            @endif
                        </div>
                        @if($alert->state === 'active')
                            <form method="POST" action="{{ route('alerts.resolve', $alert) }}">
                                @csrf
                                <button type="submit" class="ml-4 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                                    Resolve
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-dashboard-muted">
                    No alerts. Everything is healthy!
                </div>
            @endforelse
        </div>

        @if($alerts->hasPages())
            <div class="p-4 border-t border-dashboard-border">
                {{ $alerts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
