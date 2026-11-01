<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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

    public function exportCsv(Server $server): Response
    {
        $cpu = $server->metricsCpu()->orderBy('collected_at')->get();
        $memory = $server->metricsMemory()->orderBy('collected_at')->get();
        $disk = $server->metricsDisk()->orderBy('collected_at')->get();
        $network = $server->metricsNetwork()->orderBy('collected_at')->get();
        $system = $server->metricsSystem()->orderBy('collected_at')->get();

        $filename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $server->hostname)) . '-metrics-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($cpu, $memory, $disk, $network, $system) {
            $fp = fopen('php://output', 'w');

            // CPU section
            fputcsv($fp, ['# CPU Usage']);
            fputcsv($fp, ['collected_at', 'utilisation_percent', 'core_count', 'load_1m', 'load_5m', 'load_15m', 'temperature_celsius']);
            foreach ($cpu as $row) {
                fputcsv($fp, [$row->collected_at, $row->utilisation_percent, $row->core_count, $row->load_1m, $row->load_5m, $row->load_15m, $row->temperature_celsius]);
            }
            fputcsv($fp, []);

            // Memory section
            fputcsv($fp, ['# Memory Usage']);
            fputcsv($fp, ['collected_at', 'total_bytes', 'used_bytes', 'usage_percent', 'swap_total_bytes', 'swap_used_bytes', 'swap_usage_percent']);
            foreach ($memory as $row) {
                fputcsv($fp, [$row->collected_at, $row->total_bytes, $row->used_bytes, $row->usage_percent, $row->swap_total_bytes, $row->swap_used_bytes, $row->swap_usage_percent]);
            }
            fputcsv($fp, []);

            // Disk section
            fputcsv($fp, ['# Disk Usage']);
            fputcsv($fp, ['collected_at', 'mount_point', 'total_bytes', 'used_bytes', 'usage_percent']);
            foreach ($disk as $row) {
                fputcsv($fp, [$row->collected_at, $row->mount_point, $row->total_bytes, $row->used_bytes, $row->usage_percent]);
            }
            fputcsv($fp, []);

            // Network section
            fputcsv($fp, ['# Network Throughput']);
            fputcsv($fp, ['collected_at', 'interface_name', 'bytes_sent', 'bytes_received', 'status']);
            foreach ($network as $row) {
                fputcsv($fp, [$row->collected_at, $row->interface_name, $row->bytes_sent, $row->bytes_received, $row->status]);
            }
            fputcsv($fp, []);

            // System section
            fputcsv($fp, ['# System Uptime']);
            fputcsv($fp, ['collected_at', 'uptime_seconds']);
            foreach ($system as $row) {
                fputcsv($fp, [$row->collected_at, $row->uptime_seconds]);
            }

            fclose($fp);
        }, $filename, ['Content-Type' => 'text/csv']);
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
