<?php

namespace App\Console\Commands;

use App\Models\InstalledPlugin;
use App\Services\Marketplace\PluginManager;
use App\Services\Marketplace\LicenseManager;
use App\Services\Marketplace\UpdateManager;
use App\Services\Marketplace\MarketplaceClient;
use Illuminate\Console\Command;

class PluginList extends Command
{
    protected $signature = 'plugin:list {--status= : Filter by status}';
    protected $description = 'List installed plugins';

    public function handle(): int
    {
        $query = InstalledPlugin::with('license');

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $plugins = $query->get();

        if ($plugins->isEmpty()) {
            $this->info('No plugins installed.');
            return 0;
        }

        $this->table(
            ['Slug', 'Name', 'Version', 'Status', 'License', 'Update'],
            $plugins->map(fn($p) => [
                $p->slug,
                $p->name,
                $p->version,
                $p->status,
                $p->getLicenseStatus(),
                $p->hasUpdate() ? '✓' : '-',
            ])
        );

        return 0;
    }
}

class PluginActivate extends Command
{
    protected $signature = 'plugin:activate {slug}';
    protected $description = 'Activate a plugin';

    public function handle(PluginManager $manager): int
    {
        $plugin = InstalledPlugin::findBySlug($this->argument('slug'));

        if (!$plugin) {
            $this->error('Plugin not found.');
            return 1;
        }

        $result = $manager->activate($plugin);

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['error']);
        return 1;
    }
}

class PluginDeactivate extends Command
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

class PluginInstall extends Command
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

class PluginUninstall extends Command
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

class PluginUpdate extends Command
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

class LicenseActivate extends Command
{
    protected $signature = 'license:activate {slug} {key} {email}';
    protected $description = 'Activate a license for a plugin';

    public function handle(LicenseManager $manager): int
    {
        $plugin = InstalledPlugin::findBySlug($this->argument('slug'));

        if (!$plugin) {
            $this->error('Plugin not found.');
            return 1;
        }

        $result = $manager->activate(
            $plugin,
            $this->argument('key'),
            $this->argument('email')
        );

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['error']);
        return 1;
    }
}

class LicenseVerify extends Command
{
    protected $signature = 'license:verify {slug?}';
    protected $description = 'Verify plugin licenses';

    public function handle(LicenseManager $manager): int
    {
        $slug = $this->argument('slug');

        if ($slug) {
            $plugin = InstalledPlugin::findBySlug($slug);
            if (!$plugin) {
                $this->error('Plugin not found.');
                return 1;
            }

            $result = $manager->verify($plugin);
            $this->line($result['valid'] ? '✓ License valid' : '✗ ' . ($result['error'] ?? 'Invalid'));
            return $result['valid'] ? 0 : 1;
        }

        $this->info('Verifying all licenses...');
        $results = $manager->verifyAll();

        foreach ($results as $pluginSlug => $result) {
            $status = $result['valid'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line("  {$status} {$pluginSlug}");
        }

        return 0;
    }
}

class MarketplaceSync extends Command
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
