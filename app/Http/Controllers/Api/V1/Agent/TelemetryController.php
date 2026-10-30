<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Server;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use App\Models\MetricDisk;
use App\Models\MetricNetwork;
use App\Models\MetricSystem;
use App\Models\MonitoredService;
use App\Models\DockerContainer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TelemetryController extends Controller
{
    public function ingest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_info' => ['required', 'array'],
            'server_info.hostname' => ['required', 'string', 'max:255'],
            'server_info.os' => ['nullable', 'string', 'max:255'],
            'server_info.kernel_version' => ['nullable', 'string', 'max:255'],
            'server_info.architecture' => ['nullable', 'string', 'max:50'],
            'server_info.agent_version' => ['nullable', 'string', 'max:50'],
            'cpu' => ['sometimes', 'array'],
            'cpu.utilisation_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cpu.core_count' => ['nullable', 'integer', 'min:1'],
            'cpu.load_1m' => ['nullable', 'numeric'],
            'cpu.load_5m' => ['nullable', 'numeric'],
            'cpu.load_15m' => ['nullable', 'numeric'],
            'cpu.temperature_celsius' => ['nullable', 'numeric'],
            'memory' => ['sometimes', 'array'],
            'memory.total_bytes' => ['nullable', 'integer', 'min:0'],
            'memory.used_bytes' => ['nullable', 'integer', 'min:0'],
            'memory.usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'memory.swap_total_bytes' => ['nullable', 'integer', 'min:0'],
            'memory.swap_used_bytes' => ['nullable', 'integer', 'min:0'],
            'memory.swap_usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'disks' => ['sometimes', 'array'],
            'disks.*.mount_point' => ['required_with:disks', 'string', 'max:255'],
            'disks.*.total_bytes' => ['nullable', 'integer', 'min:0'],
            'disks.*.used_bytes' => ['nullable', 'integer', 'min:0'],
            'disks.*.usage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'network' => ['sometimes', 'array'],
            'network.*.interface_name' => ['required_with:network', 'string', 'max:255'],
            'network.*.bytes_sent' => ['nullable', 'integer', 'min:0'],
            'network.*.bytes_received' => ['nullable', 'integer', 'min:0'],
            'network.*.status' => ['nullable', Rule::in(['up', 'down', 'unknown'])],
            'system' => ['sometimes', 'array'],
            'system.uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'services' => ['sometimes', 'array'],
            'services.*.name' => ['required_with:services', 'string', 'max:255'],
            'services.*.state' => ['nullable', Rule::in(['running', 'stopped', 'failed', 'unknown'])],
            'services.*.is_active' => ['nullable', 'boolean'],
            'services.*.status_details' => ['nullable', 'string'],
            'docker' => ['sometimes', 'array'],
            'docker.*.container_name' => ['required_with:docker', 'string', 'max:255'],
            'docker.*.container_id' => ['nullable', 'string', 'max:255'],
            'docker.*.image' => ['nullable', 'string', 'max:255'],
            'docker.*.state' => ['nullable', Rule::in(['running', 'stopped', 'paused', 'restarting', 'dead', 'exited'])],
            'docker.*.health' => ['nullable', 'string', 'max:50'],
            'docker.*.restart_count' => ['nullable', 'integer', 'min:0'],
            'docker.*.status_text' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            // Update or create server
            $server = Server::updateOrCreate(
                ['hostname' => $validated['server_info']['hostname']],
                [
                    'friendly_name' => $validated['server_info']['hostname'],
                    'os' => $validated['server_info']['os'] ?? null,
                    'kernel_version' => $validated['server_info']['kernel_version'] ?? null,
                    'architecture' => $validated['server_info']['architecture'] ?? null,
                    'agent_version' => $validated['server_info']['agent_version'] ?? null,
                    'last_seen_at' => now(),
                ]
            );

            $collectedAt = now();

            // Store CPU metrics
            if (isset($validated['cpu'])) {
                MetricCpu::create(array_merge([
                    'server_id' => $server->id,
                    'collected_at' => $collectedAt,
                ], $validated['cpu']));
            }

            // Store memory metrics
            if (isset($validated['memory'])) {
                MetricMemory::create(array_merge([
                    'server_id' => $server->id,
                    'collected_at' => $collectedAt,
                ], $validated['memory']));
            }

            // Store disk metrics
            if (isset($validated['disks'])) {
                foreach ($validated['disks'] as $disk) {
                    MetricDisk::create(array_merge([
                        'server_id' => $server->id,
                        'collected_at' => $collectedAt,
                    ], $disk));
                }
            }

            // Store network metrics
            if (isset($validated['network'])) {
                foreach ($validated['network'] as $iface) {
                    MetricNetwork::create(array_merge([
                        'server_id' => $server->id,
                        'collected_at' => $collectedAt,
                    ], $iface));
                }
            }

            // Store system metrics
            if (isset($validated['system'])) {
                MetricSystem::create(array_merge([
                    'server_id' => $server->id,
                    'collected_at' => $collectedAt,
                ], $validated['system']));
            }

            // Store monitored services
            if (isset($validated['services'])) {
                foreach ($validated['services'] as $service) {
                    MonitoredService::create(array_merge([
                        'server_id' => $server->id,
                        'collected_at' => $collectedAt,
                        'service_name' => $service['name'],
                    ], array_filter($service, fn ($key) => $key !== 'name', ARRAY_FILTER_USE_KEY)));
                }
            }

            // Store Docker container info (optional)
            if (isset($validated['docker'])) {
                foreach ($validated['docker'] as $container) {
                    DockerContainer::create(array_merge([
                        'server_id' => $server->id,
                        'collected_at' => $collectedAt,
                    ], $container));
                }
            }

            // Update server status
            $server->updateStatus();

            DB::commit();

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to process telemetry',
            ], 500);
        }
    }
}
