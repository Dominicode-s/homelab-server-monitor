<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('servers', function (Blueprint $table): void {
            $table->id();
            $table->string('friendly_name')->nullable();
            $table->string('hostname')->unique();
            $table->string('description')->nullable();
            $table->string('os')->nullable();
            $table->string('kernel_version')->nullable();
            $table->string('architecture')->nullable();
            $table->string('agent_version')->nullable();
            $table->enum('status', ['online', 'offline', 'degraded'])->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'last_seen_at']);
        });

        Schema::create('credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['token']);
        });

        Schema::create('metrics_cpu', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->timestamp('collected_at');
            $table->decimal('utilisation_percent', 5, 2)->nullable();
            $table->integer('core_count')->nullable();
            $table->decimal('load_1m', 5, 2)->nullable();
            $table->decimal('load_5m', 5, 2)->nullable();
            $table->decimal('load_15m', 5, 2)->nullable();
            $table->decimal('temperature_celsius', 5, 2)->nullable();
            $table->index(['server_id', 'collected_at']);
        });

        Schema::create('metrics_memory', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->timestamp('collected_at');
            $table->unsignedBigInteger('total_bytes')->nullable();
            $table->unsignedBigInteger('used_bytes')->nullable();
            $table->decimal('usage_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('swap_total_bytes')->nullable();
            $table->unsignedBigInteger('swap_used_bytes')->nullable();
            $table->decimal('swap_usage_percent', 5, 2)->nullable();
            $table->index(['server_id', 'collected_at']);
        });

        Schema::create('metrics_disk', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->timestamp('collected_at');
            $table->string('mount_point');
            $table->unsignedBigInteger('total_bytes')->nullable();
            $table->unsignedBigInteger('used_bytes')->nullable();
            $table->decimal('usage_percent', 5, 2)->nullable();
            $table->index(['server_id', 'collected_at', 'mount_point']);
        });

        Schema::create('metrics_network', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->timestamp('collected_at');
            $table->string('interface_name');
            $table->unsignedBigInteger('bytes_sent')->default(0);
            $table->unsignedBigInteger('bytes_received')->default(0);
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->index(['server_id', 'collected_at', 'interface_name']);
        });

        Schema::create('metrics_system', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->timestamp('collected_at');
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->index(['server_id', 'collected_at']);
        });

        Schema::create('monitored_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->timestamp('collected_at');
            $table->string('service_name');
            $table->enum('state', ['running', 'stopped', 'failed', 'unknown'])->default('unknown');
            $table->boolean('is_active')->default(true);
            $table->text('status_details')->nullable();
            $table->unique(['server_id', 'service_name', 'collected_at'], 'srv_collected');
            $table->index(['server_id', 'collected_at']);
        });

        Schema::create('docker_containers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->timestamp('collected_at');
            $table->string('container_id')->nullable();
            $table->string('container_name');
            $table->string('image')->nullable();
            $table->enum('state', ['running', 'stopped', 'paused', 'restarting', 'dead', 'exited'])->default('unknown');
            $table->string('health')->nullable();
            $table->integer('restart_count')->default(0);
            $table->text('status_text')->nullable();
            $table->index(['server_id', 'collected_at']);
        });

        Schema::create('alert_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['cpu_usage', 'memory_usage', 'disk_usage', 'host_offline', 'service_down']);
            $table->unsignedInteger('server_id')->nullable(); // null = applies to all servers
            $table->decimal('threshold', 8, 2)->nullable();
            $table->unsignedInteger('duration_minutes')->default(5);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_enabled']);
        });

        Schema::create('alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rule_id')->constrained('alert_rules');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('severity', ['warning', 'critical'])->default('warning');
            $table->enum('state', ['active', 'resolved'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'server_id']);
            $table->index(['triggered_at']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index()->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('docker_containers');
        Schema::dropIfExists('monitored_services');
        Schema::dropIfExists('metrics_network');
        Schema::dropIfExists('metrics_disk');
        Schema::dropIfExists('metrics_memory');
        Schema::dropIfExists('metrics_cpu');
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('admins');
    }
};
