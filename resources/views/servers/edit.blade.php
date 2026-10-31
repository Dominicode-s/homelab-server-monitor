@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-white">Edit Server</h2>
        <a href="{{ route('servers.show', $server) }}" class="text-dashboard-muted hover:text-dashboard-text">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
    </div>

    <form method="POST" action="{{ route('servers.update', $server) }}" class="bg-dashboard-surface rounded-xl border border-dashboard-border p-6 max-w-lg">
        @csrf
        @method('PATCH')

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-dashboard-muted mb-1">Hostname</label>
                <input type="text" value="{{ $server->hostname }}" disabled class="w-full px-3 py-2 bg-dashboard-bg/50 border border-dashboard-border rounded-lg text-dashboard-muted cursor-not-allowed">
                <p class="text-xs text-dashboard-muted mt-1">Hostname cannot be changed.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-dashboard-muted mb-1">Friendly Name</label>
                <input type="text" name="friendly_name" value="{{ old('friendly_name', $server->friendly_name) }}" class="w-full px-3 py-2 bg-dashboard-bg border border-dashboard-border rounded-lg text-dashboard-text focus:outline-none focus:ring-2 focus:ring-primary-500">
                @error('friendly_name')
                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-dashboard-muted mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 bg-dashboard-bg border border-dashboard-border rounded-lg text-dashboard-text focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('description', $server->description) }}</textarea>
                @error('description')
                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <a href="{{ route('servers.show', $server) }}" class="px-4 py-2 text-dashboard-muted hover:text-dashboard-text">Cancel</a>
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg">Save Changes</button>
        </div>
    </form>

    <!-- Danger Zone -->
    <div class="bg-dashboard-surface rounded-xl border border-red-800/50 p-6 max-w-lg">
        <h3 class="text-lg font-semibold text-red-400 mb-4">Danger Zone</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-dashboard-text">Delete this server</p>
                <p class="text-xs text-dashboard-muted mt-1">All associated metrics, alerts, and credentials will be permanently removed.</p>
            </div>
            <form method="POST" action="{{ route('servers.destroy', $server) }}" onsubmit="return confirm('Are you sure you want to delete this server? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Delete Server</button>
            </form>
        </div>
    </div>
</div>
@endsection
