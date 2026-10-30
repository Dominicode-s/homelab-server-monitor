<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePayloadSize
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxSizeKb = config('sentinel.security.max_payload_size_kb', 512);
        $maxSizeBytes = $maxSizeKb * 1024;

        if ($request->server('CONTENT_LENGTH') && (int) $request->server('CONTENT_LENGTH') > $maxSizeBytes) {
            return response()->json([
                'error' => 'Payload too large. Maximum size is ' . $maxSizeKb . 'KB.',
            ], 413);
        }

        return $next($request);
    }
}
