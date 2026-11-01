<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\AlertController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Logout (accessible by both guests and authenticated users)
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Authentication routes
Route::middleware('auth')->group(function (): void {
    // Real-time dashboard updates via Server-Sent Events
    Route::get('/broadcast/events', [BroadcastController::class, 'events'])
        ->name('broadcast.events');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Server management
    Route::resource('servers', ServerController::class)->except(['show']);
    Route::get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::get('/servers/{server}/export-csv', [ServerController::class, 'exportCsv'])->name('servers.export-csv');

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/{alert}', [AlertController::class, 'show'])->name('alerts.show');
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::post('/alerts/rules/{rule}/toggle', [AlertController::class, 'toggleRule'])->name('alerts.toggle-rule');

    // Settings
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings.index');
});

// Session management
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});
