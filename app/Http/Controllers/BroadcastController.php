<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Server;
use App\Models\Alert;

/**
 * Server-Sent Events endpoint for real-time dashboard updates.
 *
 * Streams JSON-encoded snapshots to connected clients at a
 * configurable interval. Clients diff snapshots to detect changes
 * and update the DOM accordingly — no external dependencies required.
 */
class BroadcastController extends Controller
{
    /**
     * Stream SSE snapshots to the client.
     */
    public function events(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Poll interval derived from agent reporting interval.
        // Clients check every ~1/3 of the agent interval for near-real-time updates.
        $pollSeconds = max(5, (int) round(config('sentinel.agent.default_interval', 30) / 3));

        return response()->stream(function () use ($pollSeconds) {
            // Send initial connection ping
            $this->sendEvent('connected', json_encode([
                'timestamp' => now()->toIso8601String(),
            ]));

            // Send initial snapshot immediately
            $snapshot = $this->buildSnapshot();
            $this->sendEvent('snapshot', json_encode($snapshot));

            // Continuously stream updated snapshots
            while (true) {
                // Abort if client disconnected
                if (connection_aborted()) {
                    break;
                }

                sleep($pollSeconds);

                $snapshot = $this->buildSnapshot();
                $this->sendEvent('snapshot', json_encode($snapshot));
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Send a single SSE event line.
     */
    private function sendEvent(string $event, string $data): void
    {
        echo "event: {$event}\n";
        echo "data: {$data}\n\n";
        ob_flush();
        flush();
    }

    /**
     * Build a full dashboard state snapshot.
     */
    private function buildSnapshot(): array
    {
        $servers = Server::with([
            'latestCpuMetric',
            'latestMemoryMetric',
            'latestDiskMetrics',
        ])
            ->orderBy('friendly_name')
            ->get()
            ->map(fn (Server $s) => [
                'id' => $s->id,
                'friendly_name' => $s->friendly_name,
                'hostname' => $s->hostname,
                'status' => $s->status,
                'is_online' => $s->isOnline(),
                'cpu_percent' => $s->metricsCpu->last()?->utilisation_percent,
                'memory_percent' => $s->metricsMemory->last()?->usage_percent,
                'last_seen_at' => $s->last_seen_at?->diffForHumans() ?? 'Never',
            ]);

        $activeAlerts = Alert::where('state', 'active')->count();

        $recentAlerts = Alert::with(['server', 'rule'])
            ->where('state', 'active')
            ->orderBy('triggered_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (Alert $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'severity' => $a->severity,
                'state' => $a->state,
                'server_name' => $a->server?->friendly_name ?? $a->server?->hostname ?? 'Unknown',
                'triggered_at' => $a->triggered_at?->diffForHumans() ?? '',
            ]);

        return [
            'total_servers' => Server::count(),
            'online_servers' => Server::where('status', 'online')->count(),
            'offline_servers' => Server::where('status', 'offline')->count(),
            'degraded_servers' => Server::where('status', 'degraded')->count(),
            'active_alerts' => $activeAlerts,
            'servers' => $servers,
            'alerts' => $recentAlerts,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
