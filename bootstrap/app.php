<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

if (!function_exists('formatBytes')) {
    function formatBytes(int|float $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) { return '0 B'; }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / pow(1024, $index);
        return round($value, $precision) . ' ' . $units[$index];
    }
}

if (!function_exists('formatUptime')) {
    function formatUptime(int|float $seconds): string
    {
        $days = (int) floor($seconds / 86400);
        $hours = (int) floor(($seconds % 86400) / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        if ($days > 0) { return "$daysd {$hours}h"; }
        if ($hours > 0) { return "{$hours}h {$minutes}m"; }
        return "{$minutes}m";
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/v1/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
