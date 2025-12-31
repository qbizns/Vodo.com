<?php

declare(strict_types=1);

namespace Tests\Feature\Marketplace;

use App\Enums\MarketplaceStatus;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceVersion;
use App\Models\Marketplace\MarketplaceInstallation;
use App\Models\Marketplace\MarketplaceSubscription;
use App\Services\Marketplace\InstallationManager;
use App\Services\Marketplace\VersionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallationManagerTest extends TestCase
{
    use RefreshDatabase;

    protected InstallationManager $manager;
    protected MarketplaceListing $listing;
    protected MarketplaceVersion $version;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(InstallationManager::class);

        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        $this->listing = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'test-plugin',
            'name' => 'Test Plugin',
            'short_description' => 'A test plugin',
            'description' => 'A test plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        $this->version = MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now(),
        ]);
    }

    public function test_install_creates_installation(): void
    {
        $installation = $this->manager->install($this->listing, 1);

        $this->assertDatabaseHas('marketplace_installations', [
            'listing_id' => $this->listing->id,
            'tenant_id' => 1,
            'installed_version' => '1.0.0',
            'status' => 'active',
        ]);

        $this->assertEquals('active', $installation->status);
        $this->assertEquals('1.0.0', $installation->installed_version);
    }

    public function test_install_throws_if_already_installed(): void
    {
        $this->manager->install($this->listing, 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Plugin is already installed');

        $this->manager->install($this->listing, 1);
    }

    public function test_install_reactivates_uninstalled(): void
    {
        $installation = $this->manager->install($this->listing, 1);
        $this->manager->uninstall($installation);

        $newInstallation = $this->manager->install($this->listing, 1);

        $this->assertEquals('active', $newInstallation->status);
        $this->assertEquals($installation->id, $newInstallation->id);
    }

    public function test_uninstall_marks_as_uninstalled(): void
    {
        $installation = $this->manager->install($this->listing, 1);
        $this->manager->uninstall($installation);

        $installation->refresh();
        $this->assertEquals('uninstalled', $installation->status);
        $this->assertNotNull($installation->uninstalled_at);
    }

    public function test_activate_activates_inactive_installation(): void
    {
        $installation = $this->manager->install($this->listing, 1);
        $this->manager->deactivate($installation);

        $installation->refresh();
        $this->assertEquals('inactive', $installation->status);

        $this->manager->activate($installation);

        $installation->refresh();
        $this->assertEquals('active', $installation->status);
    }

    public function test_deactivate_deactivates_active_installation(): void
    {
        $installation = $this->manager->install($this->listing, 1);

        $this->manager->deactivate($installation);

        $installation->refresh();
        $this->assertEquals('inactive', $installation->status);
    }

    public function test_update_updates_to_latest_version(): void
    {
        $installation = $this->manager->install($this->listing, 1);

        // Create a newer version
        $newVersion = MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.1.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Update',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now(),
        ]);

        $this->manager->update($installation);

        $installation->refresh();
        $this->assertEquals('1.1.0', $installation->installed_version);
        $this->assertEquals($newVersion->id, $installation->version_id);
    }

    public function test_is_installed_returns_true_for_active_installation(): void
    {
        $this->manager->install($this->listing, 1);

        $this->assertTrue($this->manager->isInstalled('test-plugin', 1));
    }

    public function test_is_installed_returns_false_for_uninstalled(): void
    {
        $installation = $this->manager->install($this->listing, 1);
        $this->manager->uninstall($installation);

        $this->assertFalse($this->manager->isInstalled('test-plugin', 1));
    }

    public function test_is_installed_returns_false_for_never_installed(): void
    {
        $this->assertFalse($this->manager->isInstalled('test-plugin', 1));
    }

    public function test_get_stats_returns_correct_counts(): void
    {
        // Create multiple installations
        $installation1 = $this->manager->install($this->listing, 1);

        $category2 = MarketplaceCategory::create([
            'name' => 'Test2',
            'slug' => 'test2',
            'description' => 'Test category 2',
        ]);

        $listing2 = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category2->id,
            'plugin_slug' => 'test-plugin-2',
            'name' => 'Test Plugin 2',
            'short_description' => 'Another test plugin',
            'description' => 'Another test plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        MarketplaceVersion::create([
            'listing_id' => $listing2->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now(),
        ]);

        $installation2 = $this->manager->install($listing2, 1);
        $this->manager->deactivate($installation2);

        $stats = $this->manager->getStats(1);

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['active']);
        $this->assertEquals(1, $stats['inactive']);
    }

    public function test_suspend_all_installations(): void
    {
        // Create multiple installations for same listing
        $this->manager->install($this->listing, 1);
        $this->manager->install($this->listing, 2);
        $this->manager->install($this->listing, 3);

        $count = $this->manager->suspendAllInstallations($this->listing, 'Security issue');

        $this->assertEquals(3, $count);

        $suspended = MarketplaceInstallation::where('listing_id', $this->listing->id)
            ->where('status', 'suspended')
            ->count();

        $this->assertEquals(3, $suspended);
    }

    public function test_install_starts_trial_for_paid_plugin(): void
    {
        $this->listing->update([
            'pricing_model' => 'subscription',
            'price' => 29.99,
            'trial_days' => 14,
        ]);

        $installation = $this->manager->install($this->listing, 1);

        $this->assertTrue($installation->is_trial);
        $this->assertNotNull($installation->trial_expires_at);
    }

    public function test_process_expired_trials(): void
    {
        $this->listing->update([
            'pricing_model' => 'subscription',
            'price' => 29.99,
            'trial_days' => 14,
        ]);

        $installation = $this->manager->install($this->listing, 1);

        // Manually expire the trial
        $installation->update([
            'trial_expires_at' => now()->subDay(),
        ]);

        $count = $this->manager->processExpiredTrials();

        $this->assertEquals(1, $count);

        $installation->refresh();
        $this->assertEquals('inactive', $installation->status);
        $this->assertFalse($installation->is_trial);
    }

    public function test_rollback_to_previous_version(): void
    {
        $installation = $this->manager->install($this->listing, 1);

        // Create a newer version and update
        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.1.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Update',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now(),
        ]);

        $this->manager->update($installation);
        $installation->refresh();
        $this->assertEquals('1.1.0', $installation->installed_version);

        // Rollback to original version
        $this->manager->rollback($installation, '1.0.0');

        $installation->refresh();
        $this->assertEquals('1.0.0', $installation->installed_version);
    }

    public function test_get_installation_returns_correct_installation(): void
    {
        $installed = $this->manager->install($this->listing, 1);

        $found = $this->manager->getInstallation('test-plugin', 1);

        $this->assertNotNull($found);
        $this->assertEquals($installed->id, $found->id);
    }

    public function test_get_installations_needing_update(): void
    {
        $installation = $this->manager->install($this->listing, 1);

        // No update available yet
        $needing = $this->manager->getInstallationsNeedingUpdate(1);
        $this->assertCount(0, $needing);

        // Create a newer version
        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.1.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Update',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now(),
        ]);

        $needing = $this->manager->getInstallationsNeedingUpdate(1);
        $this->assertCount(1, $needing);
        $this->assertEquals($installation->id, $needing->first()->id);
    }

    public function test_update_all_updates_multiple_plugins(): void
    {
        $installation1 = $this->manager->install($this->listing, 1);

        $category2 = MarketplaceCategory::create([
            'name' => 'Test2',
            'slug' => 'test2',
            'description' => 'Test category 2',
        ]);

        $listing2 = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category2->id,
            'plugin_slug' => 'test-plugin-2',
            'name' => 'Test Plugin 2',
            'short_description' => 'Another test plugin',
            'description' => 'Another test plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        MarketplaceVersion::create([
            'listing_id' => $listing2->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now()->subDay(),
        ]);

        $installation2 = $this->manager->install($listing2, 1);

        // Create newer versions for both
        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.1.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Update',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now(),
        ]);

        MarketplaceVersion::create([
            'listing_id' => $listing2->id,
            'version' => '1.1.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Update',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now(),
        ]);

        $results = $this->manager->updateAll(1);

        $this->assertCount(2, $results);
        $this->assertTrue($results['test-plugin']['success']);
        $this->assertTrue($results['test-plugin-2']['success']);
        $this->assertEquals('1.1.0', $results['test-plugin']['version']);
        $this->assertEquals('1.1.0', $results['test-plugin-2']['version']);
    }
}
