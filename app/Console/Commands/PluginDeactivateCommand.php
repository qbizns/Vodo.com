<?php

namespace App\Console\Commands;

use App\Models\InstalledPlugin;
use App\Services\Marketplace\PluginManager;
use Illuminate\Console\Command;

class PluginDeactivateCommand extends Command
{
    protected $signature = 'plugin:deactivate {slug}';
    protected $description = 'Deactivate a plugin';

    public function handle(PluginManager $manager): int
    {
        $plugin = InstalledPlugin::findBySlug($this->argument('slug'));

        if (!$plugin) {
            $this->error('Plugin not found.');
            return 1;
        }

        $result = $manager->deactivate($plugin);

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['error']);
        return 1;
    }
}
