<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Credential;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Agent-Token')
            ?? $request->input('token');

        if (empty($token)) {
            return response()->json(['error' => 'Agent token required'], 401);
        }

        // Find credential by prefix for efficient lookup, then verify hash
        $prefix = substr($token, 0, 8);
        $credential = Credential::where('token_prefix', $prefix)
            ->with('server')
            ->first();

        if (! $credential || ! $credential->verifyToken($token)) {
            return response()->json(['error' => 'Invalid or revoked agent credentials'], 401);
        }

        $request->attributes->set('agent_credential', $credential);

        return $next($request);
    }
}
