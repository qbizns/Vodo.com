<?php

declare(strict_types=1);

namespace VodoCommerce\Console\Commands;

use Illuminate\Console\Command;
use VodoCommerce\Services\SandboxStoreProvisioner;

/**
 * Cleanup Expired Sandbox Stores Command
 *
 * Removes sandbox stores that have passed their expiration date.
 */
class CleanupSandboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commerce:sandbox:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired sandbox stores';

    public function __construct(
        protected SandboxStoreProvisioner $provisioner
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run mode - no stores will be deleted');
            $this->newLine();

            $expiredStores = \VodoCommerce\Models\Store::where('is_sandbox', true)
                ->where('expires_at', '<', now())
                ->get();

            if ($expiredStores->isEmpty()) {
                $this->info('No expired sandbox stores found.');
                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Name', 'Owner', 'Expired At'],
                $expiredStores->map(fn ($store) => [
                    $store->id,
                    $store->name,
                    $store->owner_email,
                    $store->expires_at->toDateTimeString(),
                ])
            );

            $this->newLine();
            $this->info('Run without --dry-run to delete these stores.');

            return self::SUCCESS;
        }

        $this->info('Cleaning up expired sandbox stores...');

        $count = $this->provisioner->cleanupExpired();

        if ($count === 0) {
            $this->info('No expired sandbox stores to clean up.');
        } else {
            $this->info("Deleted {$count} expired sandbox store(s).");
        }

        return self::SUCCESS;
    }
}
