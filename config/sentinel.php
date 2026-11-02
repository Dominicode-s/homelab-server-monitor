<?php

return [
    'security' => [
        'max_payload_size_kb' => 512,
        'telemetry_ttl_hours' => 24,
        'retention_days' => [
            'detailed' => 7,
            'hourly_aggregate' => 90,
        ],
        'offline_threshold_seconds' => 120,
        'rate_limit' => [
            'telemetry_per_minute' => 120,
            'api_per_minute' => 300,
        ],
    ],
    'agent' => [
        'default_interval' => 30,
        'min_interval' => 10,
        'max_interval' => 300,
    ],
    'alerts' => [
        'evaluation_interval_seconds' => 60,
        'cpu_usage_percent' => 90,
        'cpu_duration_minutes' => 5,
        'memory_usage_percent' => 90,
        'memory_duration_minutes' => 5,
        'disk_usage_percent' => 85,
        'host_offline_minutes' => 2,
    ],
    'notifications' => [
        'admin_email' => env('SENTINEL_ADMIN_EMAIL'),
        'enabled' => (bool) env('SENTINEL_NOTIFICATIONS_ENABLED', false),
    ],
];
