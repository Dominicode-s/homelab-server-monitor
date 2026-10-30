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

        $credential = Credential::with('server')
            ->where('token', $token)
            ->first();

        if (! $credential || ! $credential->isValid()) {
            return response()->json(['error' => 'Invalid or revoked agent credentials'], 401);
        }

        $request->attributes->set('agent_credential', $credential);

        return $next($request);
    }
}
