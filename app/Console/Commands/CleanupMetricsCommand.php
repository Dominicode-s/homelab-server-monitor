<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use App\Models\MetricDisk;
use App\Models\MetricNetwork;
use App\Models\MetricSystem;

class CleanupMetricsCommand extends Command
{
    protected $signature = 'sentinel:cleanup-metrics';

    protected $description = 'Remove telemetry data past retention period';

    public function handle(): int
    {
        $retentionDays = config('sentinel.security.retention_days.detailed', 7);

        $this->info("Cleaning up metrics older than {$retentionDays} days...");

        $deleted = [
            'CPU' => MetricCpu::cleanupOldMetrics($retentionDays),
            'Memory' => MetricMemory::cleanupOldMetrics($retentionDays),
            'Disk' => MetricDisk::cleanupOldMetrics($retentionDays),
            'Network' => MetricNetwork::cleanupOldMetrics($retentionDays),
            'System' => MetricSystem::cleanupOldMetrics($retentionDays),
        ];

        foreach ($deleted as $type => $count) {
            if ($count > 0) {
                $this->line("  - {$type}: {$count} records removed");
            }
        }

        $total = array_sum($deleted);
        $this->info("Cleanup complete. {$total} total records removed.");

        return Command::SUCCESS;
    }
}
