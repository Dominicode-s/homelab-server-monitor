<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitTelemetry
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'telemetry:' . ($request->input('server_id') ?? $request->ip());
        $limit = config('sentinel.security.rate_limit.telemetry_per_minute', 120);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'error' => 'Too many requests. Please reduce your reporting frequency.',
            ], 429);
        }

        RateLimiter::hit($key);

        return $next($request);
    }
}
