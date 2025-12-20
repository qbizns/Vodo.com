<?php

namespace App\Console\Commands;

use App\Services\Marketplace\MarketplaceClient;
use Illuminate\Console\Command;

class MarketplaceSyncCommand extends Command
{
    protected $signature = 'marketplace:sync';
    protected $description = 'Sync plugins from marketplace';

    public function handle(MarketplaceClient $client): int
    {
        $this->info('Syncing from marketplace...');

        try {
            $count = $client->syncPlugins();
            $this->info("Synced {$count} plugins.");
            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
