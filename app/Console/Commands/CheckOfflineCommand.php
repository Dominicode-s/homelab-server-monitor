<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Server;

class CheckOfflineCommand extends Command
{
    protected $signature = 'sentinel:check-offline';

    protected $description = 'Check for offline servers and update their status';

    public function handle(): int
    {
        $thresholdSeconds = config('sentinel.security.offline_threshold_seconds', 120);
        $cutoff = now()->subSeconds($thresholdSeconds);

        $offlineServers = Server::whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', $cutoff)
            ->where('status', '!=', 'offline')
            ->update(['status' => 'offline']);

        if ($offlineServers > 0) {
            $this->info("Marked {$offlineServers} server(s) as offline.");
        } else {
            $this->info('All servers are online.');
        }

        return Command::SUCCESS;
    }
}
