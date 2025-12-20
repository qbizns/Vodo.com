<?php

namespace App\Console\Commands;

use App\Models\InstalledPlugin;
use App\Services\Marketplace\LicenseManager;
use Illuminate\Console\Command;

class LicenseActivateCommand extends Command
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
