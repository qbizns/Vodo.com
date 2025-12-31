<?php

declare(strict_types=1);

namespace Tests\Feature\Marketplace;

use App\Enums\MarketplaceStatus;
use App\Enums\SubmissionStatus;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceSubmission;
use App\Models\Marketplace\MarketplaceVersion;
use App\Models\Marketplace\MarketplaceInstallation;
use App\Models\Marketplace\MarketplaceReview;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MarketplaceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketplaceService::class);
    }

    public function test_get_listings_returns_published_only_by_default(): void
    {
        $category = MarketplaceCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test category description',
        ]);

        // Create published listing
        $published = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'published-plugin',
            'name' => 'Published Plugin',
            'short_description' => 'A published plugin',
            'description' => 'A published plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        // Create draft listing
        MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'draft-plugin',
            'name' => 'Draft Plugin',
            'short_description' => 'A draft plugin',
            'description' => 'A draft plugin for testing',
            'status' => MarketplaceStatus::Draft,
            'pricing_model' => 'free',
        ]);

        $listings = $this->service->getListings();

        $this->assertCount(1, $listings);
        $this->assertEquals($published->id, $listings->first()->id);
    }

    public function test_get_listings_filters_by_category(): void
    {
        $category1 = MarketplaceCategory::create([
            'name' => 'Sales',
            'slug' => 'sales',
            'description' => 'Sales plugins',
        ]);

        $category2 = MarketplaceCategory::create([
            'name' => 'Marketing',
            'slug' => 'marketing',
            'description' => 'Marketing plugins',
        ]);

        MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category1->id,
            'plugin_slug' => 'sales-plugin',
            'name' => 'Sales Plugin',
            'short_description' => 'A sales plugin',
            'description' => 'A sales plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category2->id,
            'plugin_slug' => 'marketing-plugin',
            'name' => 'Marketing Plugin',
            'short_description' => 'A marketing plugin',
            'description' => 'A marketing plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        $listings = $this->service->getListings(['category' => 'sales']);

        $this->assertCount(1, $listings);
        $this->assertEquals('sales-plugin', $listings->first()->plugin_slug);
    }

    public function test_get_listings_filters_free_plugins(): void
    {
        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'free-plugin',
            'name' => 'Free Plugin',
            'short_description' => 'A free plugin',
            'description' => 'A free plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'paid-plugin',
            'name' => 'Paid Plugin',
            'short_description' => 'A paid plugin',
            'description' => 'A paid plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'one_time',
            'price' => 99.00,
        ]);

        $listings = $this->service->getListings(['pricing' => 'free']);

        $this->assertCount(1, $listings);
        $this->assertEquals('free-plugin', $listings->first()->plugin_slug);
    }

    public function test_get_listing_by_slug(): void
    {
        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        $listing = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'my-plugin',
            'name' => 'My Plugin',
            'short_description' => 'My plugin',
            'description' => 'My plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        $found = $this->service->getListing('my-plugin');

        $this->assertNotNull($found);
        $this->assertEquals($listing->id, $found->id);
    }

    public function test_create_submission_creates_draft(): void
    {
        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        $listing = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'my-plugin',
            'name' => 'My Plugin',
            'short_description' => 'My plugin',
            'description' => 'My plugin for testing',
            'status' => MarketplaceStatus::Draft,
            'pricing_model' => 'free',
        ]);

        $submission = $this->service->createSubmission($listing, '1.0.0', [
            'changelog' => 'Initial release',
            'package_path' => '/tmp/plugin.zip',
        ]);

        $this->assertEquals(SubmissionStatus::Draft, $submission->status);
        $this->assertEquals('1.0.0', $submission->version);
        $this->assertEquals($listing->id, $submission->listing_id);
    }

    public function test_submit_for_review_changes_status(): void
    {
        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        $listing = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'my-plugin',
            'name' => 'My Plugin',
            'short_description' => 'My plugin',
            'description' => 'My plugin for testing',
            'status' => MarketplaceStatus::Draft,
            'pricing_model' => 'free',
        ]);

        $submission = MarketplaceSubmission::create([
            'listing_id' => $listing->id,
            'developer_id' => 1,
            'version' => '1.0.0',
            'plugin_slug' => 'my-plugin',
            'status' => SubmissionStatus::Draft,
            'changelog' => 'Initial release',
        ]);

        $this->service->submitForReview($submission);

        $submission->refresh();
        $this->assertEquals(SubmissionStatus::Submitted, $submission->status);
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_add_review_creates_review_and_updates_rating(): void
    {
        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        $listing = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'my-plugin',
            'name' => 'My Plugin',
            'short_description' => 'My plugin',
            'description' => 'My plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        $installation = MarketplaceInstallation::create([
            'listing_id' => $listing->id,
            'tenant_id' => 1,
            'installed_version' => '1.0.0',
            'status' => 'active',
            'installed_at' => now(),
        ]);

        $review = $this->service->addReview($installation, 1, 5, 'Great plugin!', 'Excellent functionality');

        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Great plugin!', $review->title);

        $listing->refresh();
        $this->assertEquals(5.0, $listing->rating);
        $this->assertEquals(1, $listing->review_count);
    }

    public function test_cannot_review_without_installation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No active installation found');

        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        $listing = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'my-plugin',
            'name' => 'My Plugin',
            'short_description' => 'My plugin',
            'description' => 'My plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        $installation = MarketplaceInstallation::create([
            'listing_id' => $listing->id,
            'tenant_id' => 1,
            'installed_version' => '1.0.0',
            'status' => 'uninstalled',
            'installed_at' => now(),
        ]);

        $this->service->addReview($installation, 1, 5, 'Great!', 'Testing');
    }

    public function test_listing_search_works(): void
    {
        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'invoice-generator',
            'name' => 'Invoice Generator',
            'short_description' => 'Generate beautiful invoices',
            'description' => 'A plugin for generating PDF invoices',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'crm-integration',
            'name' => 'CRM Integration',
            'short_description' => 'Integrate with CRM systems',
            'description' => 'Connect to various CRM platforms',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'free',
        ]);

        $listings = $this->service->getListings(['search' => 'invoice']);

        $this->assertCount(1, $listings);
        $this->assertEquals('invoice-generator', $listings->first()->plugin_slug);
    }
}
