<?php

namespace App\Console\Commands;

use App\Services\Marketplace\PluginManager;
use Illuminate\Console\Command;

class PluginInstallCommand extends Command
{
    protected $signature = 'plugin:install
                            {source : Marketplace ID or path to zip file}
                            {--license= : License key for premium plugins}';
    protected $description = 'Install a plugin';

    public function handle(PluginManager $manager): int
    {
        $source = $this->argument('source');
        $license = $this->option('license');

        if (file_exists($source)) {
            $this->info('Installing from package...');
            $result = $manager->installFromPackage($source);
        } else {
            $this->info('Installing from marketplace...');
            $result = $manager->installFromMarketplace($source, $license);
        }

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['error']);
        return 1;
    }
}
