<?php

namespace Database\Seeders;

use App\Models\Plugin;
use App\Models\PluginUpdate;
use Illuminate\Database\Seeder;

class PluginUpdatesSeeder extends Seeder
{
    /**
     * Seed the plugin_updates table with mock update data.
     * 
     * This allows testing the Updates screen (Screen 6) as shown in SCREENS.md
     */
    public function run(): void
    {
        // Clear existing updates
        PluginUpdate::truncate();

        // Get all installed plugins
        $plugins = Plugin::all();

        if ($plugins->isEmpty()) {
            $this->command->warn('No plugins found in database. Please install/activate plugins first.');
            $this->command->info('You can activate the hello-world plugin from the Plugins page.');
            return;
        }

        // Mock updates data - maps plugin slug to update info
        $mockUpdates = [
            'hello-world' => [
                'latest_version' => '1.1.0',
                'changelog' => "• Added: Multi-language greeting support\n• Added: Customizable greeting message\n• Fixed: Compatibility with PHP 8.3\n• Improved: Performance optimizations",
                'is_security_update' => false,
                'is_breaking_change' => false,
                'requires_php_version' => '8.1',
                'requires_system_version' => '1.0.0',
            ],
            'invoice-manager' => [
                'latest_version' => '2.2.0',
                'changelog' => "• Added: Stripe payment integration\n• Added: Recurring invoice templates\n• Fixed: PDF export memory issue on large invoices\n• Improved: Dashboard widget performance\n• Improved: Invoice list loading speed",
                'is_security_update' => false,
                'is_breaking_change' => false,
                'requires_php_version' => '8.1',
                'requires_system_version' => '1.0.0',
            ],
            'hr-management' => [
                'latest_version' => '3.1.0',
                'changelog' => "• Added: Employee self-service portal\n• Added: Automated leave approval workflow\n• Breaking: Requires PHP 8.2+\n• Breaking: Database migration required\n• Security: Fixed XSS vulnerability in employee profiles",
                'is_security_update' => true,
                'is_breaking_change' => true,
                'requires_php_version' => '8.2',
                'requires_system_version' => '2.0.0',
            ],
            'inventory-control' => [
                'latest_version' => '1.3.0',
                'changelog' => "• Added: Barcode scanner support\n• Added: Stock level alerts and notifications\n• Added: Batch inventory updates\n• Fixed: Stock calculation rounding errors",
                'is_security_update' => false,
                'is_breaking_change' => false,
                'requires_php_version' => '8.1',
                'requires_system_version' => '1.0.0',
            ],
            'crm-suite' => [
                'latest_version' => '2.5.1',
                'changelog' => "• Security: Critical fix for data exposure vulnerability\n• Fixed: Contact import CSV parsing\n• Improved: Search performance",
                'is_security_update' => true,
                'is_breaking_change' => false,
                'requires_php_version' => '8.1',
                'requires_system_version' => '1.0.0',
            ],
        ];

        $createdCount = 0;

        foreach ($plugins as $plugin) {
            // Check if we have mock update data for this plugin
            if (isset($mockUpdates[$plugin->slug])) {
                $updateData = $mockUpdates[$plugin->slug];
            } else {
                // Generate a generic update for any other plugin
                $currentParts = explode('.', $plugin->version);
                $patchVersion = isset($currentParts[2]) ? (int)$currentParts[2] + 1 : 1;
                $latestVersion = ($currentParts[0] ?? '1') . '.' . ($currentParts[1] ?? '0') . '.' . $patchVersion;

                $updateData = [
                    'latest_version' => $latestVersion,
                    'changelog' => "• Minor bug fixes and improvements\n• Updated dependencies\n• Performance optimizations",
                    'is_security_update' => false,
                    'is_breaking_change' => false,
                    'requires_php_version' => '8.1',
                    'requires_system_version' => '1.0.0',
                ];
            }

            // Only create update if latest version is greater than current
            if (version_compare($updateData['latest_version'], $plugin->version, '>')) {
                PluginUpdate::create([
                    'plugin_id' => $plugin->id,
                    'current_version' => $plugin->version,
                    'latest_version' => $updateData['latest_version'],
                    'changelog' => $updateData['changelog'],
                    'is_security_update' => $updateData['is_security_update'],
                    'is_breaking_change' => $updateData['is_breaking_change'],
                    'requires_php_version' => $updateData['requires_php_version'],
                    'requires_system_version' => $updateData['requires_system_version'],
                    'release_date' => now()->subDays(rand(1, 7)),
                    'checked_at' => now(),
                ]);

                $createdCount++;
                $this->command->info("Created update for {$plugin->name}: {$plugin->version} → {$updateData['latest_version']}");
            } else {
                $this->command->line("Skipped {$plugin->name}: already at latest version");
            }
        }

        $this->command->newLine();
        $this->command->info("✓ Created {$createdCount} plugin update record(s)");
        $this->command->info("Visit /admin/system/plugins/updates to see the updates");
    }
}

