<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Converts plaintext token storage to bcrypt hashes for defense-in-depth.
     * Existing credentials are migrated automatically.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('credentials', 'token_hash')) {
            Schema::table('credentials', function (Blueprint $table): void {
                $table->string('token_hash')->nullable()->after('server_id');
                $table->string('token_prefix')->nullable()->after('token_hash');
            });

            // Migrate existing tokens to hashes
            $rows = DB::table('credentials')->select('id', 'token')->get();
            foreach ($rows as $row) {
                if ($row->token && ! $row->token_hash) {
                    DB::table('credentials')->where('id', $row->id)->update([
                        'token_hash' => Hash::make($row->token),
                        'token_prefix' => substr($row->token, 0, 8),
                    ]);
                }
            }

            // Drop old index and column, create new index
            Schema::table('credentials', function (Blueprint $table): void {
                $table->dropIndex(['token']);
            });
            Schema::table('credentials', function (Blueprint $table): void {
                $table->dropColumn('token');
            });
            Schema::table('credentials', function (Blueprint $table): void {
                $table->index('token_hash');
            });
        }
    }

    /**
     * Reverse the migration.
     * 
     * WARNING: This cannot recover plaintext tokens from bcrypt hashes.
     * Only use this in development/testing environments.
     */
    public function down(): void
    {
        if (Schema::hasColumn('credentials', 'token_hash')) {
            Schema::table('credentials', function (Blueprint $table): void {
                $table->dropIndex(['token_hash']);
                $table->string('token')->nullable()->after('server_id');
                $table->dropColumn(['token_hash', 'token_prefix']);
                $table->index('token');
            });
        }
    }
};
