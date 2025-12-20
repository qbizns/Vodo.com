<?php

namespace App\Console\Commands;

use App\Models\InstalledPlugin;
use App\Services\Marketplace\LicenseManager;
use Illuminate\Console\Command;

class LicenseVerifyCommand extends Command
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
