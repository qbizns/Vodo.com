<?php

namespace App\Console\Commands;

use App\Models\InstalledPlugin;
use App\Services\Marketplace\PluginManager;
use Illuminate\Console\Command;

class PluginUninstallCommand extends Command
{
    protected $signature = 'plugin:uninstall {slug} {--delete-data : Also delete plugin data}';
    protected $description = 'Uninstall a plugin';

    public function handle(PluginManager $manager): int
    {
        $plugin = InstalledPlugin::findBySlug($this->argument('slug'));

        if (!$plugin) {
            $this->error('Plugin not found.');
            return 1;
        }

        if (!$this->confirm("Are you sure you want to uninstall {$plugin->name}?")) {
            return 0;
        }

        $result = $manager->uninstall($plugin, $this->option('delete-data'));

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['error']);
        return 1;
    }
}
