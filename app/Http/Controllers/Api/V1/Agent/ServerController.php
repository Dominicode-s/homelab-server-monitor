<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Server;

class ServerController extends Controller
{
    public function registerOrUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hostname' => ['required', 'string', 'max:255'],
            'friendly_name' => ['sometimes', 'string', 'max:255'],
            'os' => ['nullable', 'string', 'max:255'],
            'kernel_version' => ['nullable', 'string', 'max:255'],
            'architecture' => ['nullable', 'string', 'max:50'],
            'agent_version' => ['nullable', 'string', 'max:50'],
        ]);

        $server = Server::updateOrCreate(
            ['hostname' => $validated['hostname']],
            [
                'friendly_name' => $validated['friendly_name'] ?? $validated['hostname'],
                'os' => $validated['os'] ?? null,
                'kernel_version' => $validated['kernel_version'] ?? null,
                'architecture' => $validated['architecture'] ?? null,
                'agent_version' => $validated['agent_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        $server->updateStatus();

        return response()->json([
            'status' => 'ok',
            'server_id' => $server->id,
        ]);
    }
}
