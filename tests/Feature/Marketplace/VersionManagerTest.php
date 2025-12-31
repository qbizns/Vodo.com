<?php

declare(strict_types=1);

namespace Tests\Feature\Marketplace;

use App\Enums\SubmissionStatus;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceSubmission;
use App\Models\Marketplace\MarketplaceVersion;
use App\Models\Marketplace\MarketplaceInstallation;
use App\Services\Marketplace\VersionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class VersionManagerTest extends TestCase
{
    use RefreshDatabase;

    protected VersionManager $manager;
    protected MarketplaceListing $listing;
    protected string $testStoragePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(VersionManager::class);
        $this->testStoragePath = storage_path('app/test-versions');

        File::ensureDirectoryExists($this->testStoragePath);

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
            'status' => 'draft',
            'pricing_model' => 'free',
        ]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testStoragePath)) {
            File::deleteDirectory($this->testStoragePath);
        }
        parent::tearDown();
    }

    public function test_create_version_from_submission(): void
    {
        $submission = MarketplaceSubmission::create([
            'listing_id' => $this->listing->id,
            'developer_id' => 1,
            'version' => '1.0.0',
            'plugin_slug' => 'test-plugin',
            'status' => SubmissionStatus::Approved,
            'changelog' => 'Initial release',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
        ]);

        $version = $this->manager->createVersion($this->listing, $submission);

        $this->assertEquals('1.0.0', $version->version);
        $this->assertEquals('Initial release', $version->changelog);
        $this->assertEquals('8.1', $version->php_min_version);
        $this->assertEquals($this->listing->id, $version->listing_id);
    }

    public function test_get_latest_version_for_channel(): void
    {
        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'published_at' => now()->subDays(10),
        ]);

        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.1.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Feature update',
            'published_at' => now()->subDays(5),
        ]);

        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '2.0.0-beta.1',
            'channel' => 'beta',
            'status' => 'published',
            'changelog' => 'Beta release',
            'published_at' => now(),
        ]);

        $stable = $this->manager->getLatestVersion($this->listing, 'stable');
        $this->assertEquals('1.1.0', $stable->version);

        $beta = $this->manager->getLatestVersion($this->listing, 'beta');
        $this->assertEquals('2.0.0-beta.1', $beta->version);
    }

    public function test_has_update_returns_true_when_newer_version_available(): void
    {
        $version = MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'published_at' => now()->subDays(10),
        ]);

        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.1.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Update',
            'published_at' => now(),
        ]);

        $installation = MarketplaceInstallation::create([
            'listing_id' => $this->listing->id,
            'tenant_id' => 1,
            'version_id' => $version->id,
            'installed_version' => '1.0.0',
            'status' => 'active',
            'installed_at' => now(),
            'update_channel' => 'stable',
        ]);

        $this->assertTrue($this->manager->hasUpdate($installation));
    }

    public function test_has_update_returns_false_when_on_latest(): void
    {
        $version = MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'published_at' => now(),
        ]);

        $installation = MarketplaceInstallation::create([
            'listing_id' => $this->listing->id,
            'tenant_id' => 1,
            'version_id' => $version->id,
            'installed_version' => '1.0.0',
            'status' => 'active',
            'installed_at' => now(),
            'update_channel' => 'stable',
        ]);

        $this->assertFalse($this->manager->hasUpdate($installation));
    }

    public function test_compare_versions(): void
    {
        $this->assertEquals(-1, $this->manager->compareVersions('1.0.0', '1.0.1'));
        $this->assertEquals(1, $this->manager->compareVersions('2.0.0', '1.9.9'));
        $this->assertEquals(0, $this->manager->compareVersions('1.0.0', '1.0.0'));
        $this->assertEquals(-1, $this->manager->compareVersions('1.0.0', '1.0.0-beta.1'));
    }

    public function test_yank_version_marks_as_yanked(): void
    {
        $version = MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'published_at' => now(),
        ]);

        $this->manager->yankVersion($version, 'Security vulnerability');

        $version->refresh();
        $this->assertEquals('yanked', $version->status);
        $this->assertNotNull($version->yanked_at);
        $this->assertEquals('Security vulnerability', $version->yank_reason);
    }

    public function test_get_version_history(): void
    {
        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'published_at' => now()->subDays(10),
        ]);

        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.1.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Feature update',
            'published_at' => now()->subDays(5),
        ]);

        MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.2.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Another update',
            'published_at' => now(),
        ]);

        $history = $this->manager->getVersionHistory($this->listing);

        $this->assertCount(3, $history);
        // Should be ordered newest first
        $this->assertEquals('1.2.0', $history->first()->version);
        $this->assertEquals('1.0.0', $history->last()->version);
    }

    public function test_is_compatible_checks_php_version(): void
    {
        $version = MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'php_min_version' => '8.1',
            'php_max_version' => '8.4',
            'platform_min_version' => '1.0.0',
        ]);

        $compatible = $this->manager->isCompatible($version, '8.2.0', '1.0.0');
        $this->assertTrue($compatible['compatible']);

        $incompatible = $this->manager->isCompatible($version, '7.4.0', '1.0.0');
        $this->assertFalse($incompatible['compatible']);
        $this->assertContains('PHP version 7.4.0 is below minimum required 8.1', $incompatible['issues']);
    }

    public function test_is_compatible_checks_platform_version(): void
    {
        $version = MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'php_min_version' => '8.1',
            'platform_min_version' => '2.0.0',
        ]);

        $compatible = $this->manager->isCompatible($version, '8.2.0', '2.1.0');
        $this->assertTrue($compatible['compatible']);

        $incompatible = $this->manager->isCompatible($version, '8.2.0', '1.5.0');
        $this->assertFalse($incompatible['compatible']);
        $this->assertContains('Platform version 1.5.0 is below minimum required 2.0.0', $incompatible['issues']);
    }

    public function test_get_available_update_returns_newer_compatible_version(): void
    {
        $oldVersion = MarketplaceVersion::create([
            'listing_id' => $this->listing->id,
            'version' => '1.0.0',
            'channel' => 'stable',
            'status' => 'published',
            'changelog' => 'Initial release',
            'php_min_version' => '8.1',
            'platform_min_version' => '1.0.0',
            'published_at' => now()->subDays(10),
        ]);

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

        $installation = MarketplaceInstallation::create([
            'listing_id' => $this->listing->id,
            'tenant_id' => 1,
            'version_id' => $oldVersion->id,
            'installed_version' => '1.0.0',
            'status' => 'active',
            'installed_at' => now(),
            'update_channel' => 'stable',
        ]);

        $update = $this->manager->getAvailableUpdate($installation);

        $this->assertNotNull($update);
        $this->assertEquals('1.1.0', $update->version);
    }
}
