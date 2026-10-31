<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Server;
use App\Models\Credential;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use App\Models\MetricDisk;
use App\Models\MetricNetwork;
use App\Models\MetricSystem;
use App\Models\MonitoredService;
use App\Models\DockerContainer;
use App\Models\Alert;

class ServerController extends Controller
{
    public function index(): View
    {
        $query = Server::with([
            'latestCpuMetric',
            'latestMemoryMetric',
            'latestDiskMetrics',
        ]);

        // Search/filter support
        $search = request('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                  ->orWhere('friendly_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        $status = request('status');
        if (in_array($status, ['online', 'offline', 'degraded'])) {
            $query->where('status', $status);
        }

        $servers = $query->orderBy('friendly_name')->get();

        return view('servers.index', compact('servers'));
    }

    public function show(Server $server): View
    {
        $server->load([
            'metricsCpu' => fn ($q) => $q->latest()->limit(500),
            'metricsMemory' => fn ($q) => $q->latest()->limit(500),
            'metricsDisk' => fn ($q) => $q->latest()->limit(500),
            'metricsNetwork' => fn ($q) => $q->latest()->limit(500),
            'metricsSystem' => fn ($q) => $q->latest()->limit(100),
            'monitoredServices' => fn ($q) => $q->latest()->limit(50),
            'dockerContainers' => fn ($q) => $q->latest()->limit(100),
            'alerts' => fn ($q) => $q->with('rule')->latest()->limit(50),
        ]);

        return view('servers.show', compact('server'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'friendly_name' => ['sometimes', 'string', 'max:255'],
            'hostname' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $server = Server::create($validated);

        // Generate and return credential token
        $plainToken = Credential::generateToken();

        return redirect()->route('servers.show', $server)
            ->with('success', "Server registered successfully. Token: {$plainToken}");
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        $validated = $request->validate([
            'friendly_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $server->update($validated);

        return redirect()->route('servers.show', $server)
            ->with('success', 'Server updated successfully.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        $server->delete();

        return redirect()->route('servers.index')
            ->with('success', 'Server removed successfully.');
    }

    public function toggleCredential(Server $server): RedirectResponse
    {
        $credential = $server->credentials;
        if ($credential) {
            $credential->revoke();
            // Generate new token
            $newToken = Credential::generateToken();
            return back()->with('success', "Credentials rotated. New token: {$newToken}");
        } else {
            Credential::generateToken();
            return back()->with('success', 'Credentials generated successfully.');
        }
    }
}
