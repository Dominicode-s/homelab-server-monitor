<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Server;
use App\Models\Credential;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function authenticate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $credential = Credential::with('server')
            ->where('token', $validated['token'])
            ->first();

        if (! $credential || ! $credential->isValid()) {
            return response()->json([
                'error' => 'Invalid or revoked credentials',
            ], 401);
        }

        return response()->json([
            'authenticated' => true,
            'server_id' => $credential->server_id,
        ]);
    }
}
