<?php

namespace App\Console\Commands;

use App\Models\InstalledPlugin;
use App\Services\Marketplace\UpdateManager;
use Illuminate\Console\Command;

class PluginUpdateCommand extends Command
{
    protected $signature = 'plugin:update {slug? : Plugin to update, or all if omitted} {--check : Only check for updates}';
    protected $description = 'Update plugins';

    public function handle(UpdateManager $manager): int
    {
        if ($this->option('check')) {
            $this->info('Checking for updates...');
            $updates = $manager->checkAll();

            if (empty($updates)) {
                $this->info('All plugins are up to date.');
                return 0;
            }

            foreach ($updates as $update) {
                $this->line("  {$update['slug']}: {$update['current_version']} → {$update['version']}");
            }
            return 0;
        }

        $slug = $this->argument('slug');

        if ($slug) {
            $plugin = InstalledPlugin::findBySlug($slug);
            if (!$plugin) {
                $this->error('Plugin not found.');
                return 1;
            }

            $result = $manager->install($plugin);
        } else {
            $this->info('Updating all plugins...');
            $results = $manager->updateAll();

            $success = count(array_filter($results, fn($r) => $r['success']));
            $failed = count($results) - $success;

            $this->info("Updated: {$success}, Failed: {$failed}");
            return $failed > 0 ? 1 : 0;
        }

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['error']);
        return 1;
    }
}
