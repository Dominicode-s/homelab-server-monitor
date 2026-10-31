<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Agent\AuthController;
use App\Http\Controllers\Api\V1\Agent\TelemetryController;
use App\Http\Controllers\Api\V1\Agent\ServerController as AgentServerController;
use App\Http\Middleware\AuthenticateAgent;
use App\Http\Middleware\ValidatePayloadSize;
use App\Http\Middleware\RateLimitTelemetry;

Route::prefix('v1')->group(function (): void {
    // Agent authentication (unauthenticated)
    Route::post('/agents/auth', [AuthController::class, 'authenticate']);

    // Protected agent routes
    Route::middleware([
        AuthenticateAgent::class,
        ValidatePayloadSize::class,
        RateLimitTelemetry::class,
    ])->group(function (): void {
        // Server info registration/update
        Route::post('/servers', [AgentServerController::class, 'registerOrUpdate']);

        // Telemetry reporting
        Route::post('/telemetry', [TelemetryController::class, 'ingest']);
    });
});
