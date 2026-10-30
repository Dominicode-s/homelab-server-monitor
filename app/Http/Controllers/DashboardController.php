<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Server;
use App\Models\Alert;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalServers = Server::count();
        $onlineServers = Server::where('status', 'online')->count();
        $offlineServers = Server::where('status', 'offline')->count();
        $degradedServers = Server::where('status', 'degraded')->count();

        $activeAlerts = Alert::where('state', 'active')->count();

        $recentAlerts = Alert::with(['server', 'rule'])
            ->orderBy('triggered_at', 'desc')
            ->limit(10)
            ->get();

        $servers = Server::with([
            'metricsCpu.latestCpuMetric',
            'metricsMemory.latestMemoryMetric',
            'metricsDisk.latestDiskMetrics',
        ])
            ->orderBy('friendly_name')
            ->get();

        // Calculate overall health score (0-100)
        $healthScore = $totalServers > 0
            ? (int) round(($onlineServers / $totalServers) * 100)
            : 0;

        return view('dashboard.index', compact(
            'totalServers',
            'onlineServers',
            'offlineServers',
            'degradedServers',
            'activeAlerts',
            'recentAlerts',
            'servers',
            'healthScore'
        ));
    }
}
