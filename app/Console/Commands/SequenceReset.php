<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Sequence\SequenceService;

class SequenceReset extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sequence:reset 
                            {name : The sequence name to reset}
                            {--value= : Set to a specific value (default: start value)}
                            {--tenant= : Tenant ID for multi-tenant}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Reset a sequence to its start value or a specific value';

    /**
     * Execute the console command.
     */
    public function handle(SequenceService $sequenceService): int
    {
        $name = $this->argument('name');
        $value = $this->option('value');
        $tenantId = $this->option('tenant') ? (int)$this->option('tenant') : null;

        $current = $sequenceService->current($name, $tenantId);

        $this->info("Sequence: {$name}");
        $this->info("Current value: " . ($current ?? 'Not initialized'));

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to reset this sequence?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        if ($value !== null) {
            $sequenceService->set($name, (int)$value, $tenantId);
            $this->info("Sequence set to: {$value}");
        } else {
            $sequenceService->reset($name, $tenantId);
            $this->info("Sequence reset to start value.");
        }

        $newValue = $sequenceService->preview($name, $tenantId);
        $this->info("Next value will be: {$newValue}");

        return Command::SUCCESS;
    }
}
