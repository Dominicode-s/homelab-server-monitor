<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Homelab Sentinel') }} - Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-dashboard-bg text-dashboard-text font-sans antialiased flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white">{{ config('app.name', 'Homelab Sentinel') }}</h1>
            <p class="text-dashboard-muted mt-2">Sign in to your dashboard</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-dashboard-muted mb-1">Email</label>
                <input type="email" name="email" id="email" required
                    class="w-full px-3 py-2 bg-dashboard-surface border border-dashboard-border rounded-lg text-dashboard-text placeholder-dashboard-muted focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    value="{{ old('email') }}" autofocus>
                @error('email')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-dashboard-muted mb-1">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 bg-dashboard-surface border border-dashboard-border rounded-lg text-dashboard-text placeholder-dashboard-muted focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                @error('password')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="mr-2 rounded border-dashboard-border text-primary-600 focus:ring-primary-500">
                    <span class="text-sm text-dashboard-muted">Remember me</span>
                </label>
            </div>

            <button type="submit"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                Sign in
            </button>
        </form>
    </div>
</body>
</html>
