<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Audit\AuditService;

class AuditCleanup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:cleanup 
                            {--days= : Days to keep (default from config)}
                            {--dry-run : Show what would be deleted without deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old audit log entries';

    /**
     * Execute the console command.
     */
    public function handle(AuditService $auditService): int
    {
        $days = $this->option('days') ?? config('audit.retention_days', 90);
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up audit logs older than {$days} days...");

        if ($dryRun) {
            $count = \DB::table('audit_logs')
                ->where('created_at', '<', now()->subDays($days))
                ->count();

            $this->info("Would delete {$count} records (dry run)");
            return Command::SUCCESS;
        }

        $deleted = $auditService->cleanup($days);

        $this->info("Deleted {$deleted} old audit log entries.");

        return Command::SUCCESS;
    }
}
