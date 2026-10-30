<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AlertEvaluator;
use App\Models\MetricCpu;
use App\Models\MetricMemory;
use App\Models\MetricDisk;
use App\Models\MetricNetwork;
use App\Models\MetricSystem;

class EvaluateAlertsCommand extends Command
{
    protected $signature = 'sentinel:evaluate-alerts';

    protected $description = 'Evaluate alert rules against current metrics';

    public function handle(): int
    {
        $this->info('Starting alert evaluation...');

        try {
            $evaluator = app(AlertEvaluator::class);
            $evaluator->evaluate();

            $this->info('Alert evaluation completed.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Alert evaluation failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
