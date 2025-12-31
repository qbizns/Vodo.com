<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Enums\MarketplaceStatus;
use App\Enums\SubmissionStatus;
use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceVersion;
use App\Models\Marketplace\MarketplaceInstallation;
use App\Models\Marketplace\MarketplaceSubmission;
use App\Models\Marketplace\MarketplaceReview;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceAnalytics;
use App\Services\PluginSDK\PluginManifest;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Marketplace Service
 *
 * Core service for marketplace operations.
 */
class MarketplaceService
{
    public function __construct(
        protected ReviewPipeline $reviewPipeline,
        protected VersionManager $versionManager,
        protected InstallationManager $installationManager
    ) {}

    // =========================================================================
    // Listing Operations
    // =========================================================================

    /**
     * Get published listings with filtering and sorting.
     */
    public function getListings(array $filters = []): LengthAwarePaginator
    {
        $query = MarketplaceListing::query()
            ->published()
            ->with(['category']);

        // Apply filters
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['pricing'])) {
            match ($filters['pricing']) {
                'free' => $query->free(),
                'paid' => $query->paid(),
                default => null,
            };
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['tags'])) {
            foreach ((array) $filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Apply sorting
        $sort = $filters['sort'] ?? 'popular';
        match ($sort) {
            'popular' => $query->popular(),
            'rating' => $query->topRated(),
            'recent' => $query->recent(),
            'name' => $query->orderBy('name'),
            default => $query->popular(),
        };

        $perPage = min($filters['per_page'] ?? 20, 100);

        return $query->paginate($perPage);
    }

    /**
     * Get a single listing by slug.
     */
    public function getListing(string $slug): ?MarketplaceListing
    {
        $listing = MarketplaceListing::where('plugin_slug', $slug)
            ->published()
            ->with(['versions' => fn($q) => $q->published()->latest('published_at')])
            ->first();

        if ($listing) {
            // Record view
            MarketplaceAnalytics::record($listing->id, 'view');
        }

        return $listing;
    }

    /**
     * Get featured listings.
     */
    public function getFeaturedListings(int $limit = 10): Collection
    {
        return MarketplaceListing::published()
            ->whereHas('featured', fn($q) => $q->where('is_active', true))
            ->limit($limit)
            ->get();
    }

    /**
     * Get listings by category.
     */
    public function getListingsByCategory(string $category, int $limit = 20): Collection
    {
        return MarketplaceListing::published()
            ->byCategory($category)
            ->popular()
            ->limit($limit)
            ->get();
    }

    // =========================================================================
    // Submission Operations
    // =========================================================================

    /**
     * Create a new plugin submission.
     */
    public function createSubmission(array $data, int $submitterId): MarketplaceSubmission
    {
        return DB::transaction(function () use ($data, $submitterId) {
            // Validate manifest
            $manifest = PluginManifest::fromFile($data['package_path'] . '/plugin.json');
            $manifest->validate();

            if (!$manifest->isValid()) {
                throw new \InvalidArgumentException(
                    'Invalid manifest: ' . implode(', ', $manifest->getErrors())
                );
            }

            // Check if listing exists for updates
            $existingListing = MarketplaceListing::where('plugin_slug', $manifest->getIdentifier())
                ->first();

            // Determine submission type
            $type = $existingListing ? 'update' : 'new';

            // Verify ownership for updates
            if ($existingListing && $existingListing->publisher_id !== $submitterId) {
                throw new \InvalidArgumentException('You are not the owner of this plugin');
            }

            // Create submission
            $submission = MarketplaceSubmission::create([
                'listing_id' => $existingListing?->id,
                'submitter_id' => $submitterId,
                'plugin_slug' => $manifest->getIdentifier(),
                'type' => $type,
                'version' => $manifest->getVersion(),
                'package_path' => $data['package_path'],
                'package_hash' => hash_file('sha256', $data['package_path'] . '/plugin.json'),
                'package_size' => $this->calculatePackageSize($data['package_path']),
                'manifest' => $manifest->toArray(),
                'status' => SubmissionStatus::Draft,
            ]);

            Log::info("Plugin submission created", [
                'submission_id' => $submission->id,
                'plugin' => $manifest->getIdentifier(),
                'version' => $manifest->getVersion(),
                'type' => $type,
            ]);

            return $submission;
        });
    }

    /**
     * Submit a draft submission for review.
     */
    public function submitForReview(MarketplaceSubmission $submission): bool
    {
        if (!$submission->submit()) {
            return false;
        }

        // Start automated review asynchronously
        // In production, this would be dispatched to a queue
        dispatch(function () use ($submission) {
            $this->reviewPipeline->run($submission);
        })->afterCommit();

        return true;
    }

    /**
     * Publish an approved submission.
     */
    public function publishSubmission(MarketplaceSubmission $submission): MarketplaceListing
    {
        if ($submission->status !== SubmissionStatus::Approved) {
            throw new \InvalidArgumentException('Only approved submissions can be published');
        }

        return DB::transaction(function () use ($submission) {
            $manifest = new PluginManifest($submission->manifest);

            // Create or update listing
            $listing = $submission->listing ?? new MarketplaceListing();

            $listing->fill([
                'plugin_slug' => $submission->plugin_slug,
                'name' => $manifest->getName(),
                'description' => $manifest->getDescription(),
                'category' => $manifest->getCategory(),
                'tags' => $manifest->get('keywords', []),
                'publisher_id' => $submission->submitter_id,
                'publisher_name' => $manifest->getAuthorName(),
                'current_version' => $manifest->getVersion(),
                'pricing_model' => $manifest->get('marketplace.pricing', 'free'),
                'trial_days' => $manifest->get('marketplace.trial_days', 0),
                'status' => MarketplaceStatus::Published,
                'published_at' => now(),
            ]);

            $listing->save();

            // Create version record
            $version = $this->versionManager->createVersion($listing, $submission);

            // Update listing with version reference
            $listing->update(['current_version_id' => $version->id]);

            // Mark submission as published
            $submission->publish();
            $submission->update([
                'listing_id' => $listing->id,
                'version_id' => $version->id,
            ]);

            // Update category count
            $listing->category()->first()?->recountListings();

            Log::info("Plugin published", [
                'listing_id' => $listing->id,
                'version' => $version->version,
            ]);

            return $listing;
        });
    }

    // =========================================================================
    // Installation Operations
    // =========================================================================

    /**
     * Install a plugin for a tenant.
     */
    public function install(
        MarketplaceListing $listing,
        int $tenantId,
        ?string $channel = 'stable'
    ): MarketplaceInstallation {
        return $this->installationManager->install($listing, $tenantId, $channel);
    }

    /**
     * Uninstall a plugin.
     */
    public function uninstall(MarketplaceInstallation $installation): void
    {
        $this->installationManager->uninstall($installation);
    }

    /**
     * Update a plugin to latest version.
     */
    public function update(MarketplaceInstallation $installation): void
    {
        $this->installationManager->update($installation);
    }

    /**
     * Get installations for a tenant.
     */
    public function getTenantInstallations(int $tenantId): Collection
    {
        return MarketplaceInstallation::byTenant($tenantId)
            ->with(['listing', 'version'])
            ->get();
    }

    // =========================================================================
    // Review Operations
    // =========================================================================

    /**
     * Add a review for a listing.
     */
    public function addReview(
        MarketplaceListing $listing,
        int $tenantId,
        int $userId,
        array $data
    ): MarketplaceReview {
        // Check if user already reviewed
        $existing = MarketplaceReview::where('listing_id', $listing->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException('You have already reviewed this plugin');
        }

        // Check if user has installed the plugin
        $installation = MarketplaceInstallation::where('listing_id', $listing->id)
            ->where('tenant_id', $tenantId)
            ->first();

        $review = MarketplaceReview::create([
            'listing_id' => $listing->id,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'installation_id' => $installation?->id,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'is_verified_purchase' => $installation !== null,
            'status' => 'pending',
        ]);

        return $review;
    }

    /**
     * Get reviews for a listing.
     */
    public function getReviews(
        MarketplaceListing $listing,
        array $filters = []
    ): LengthAwarePaginator {
        $query = MarketplaceReview::where('listing_id', $listing->id)
            ->approved()
            ->with(['installation']);

        // Filter by rating
        if (!empty($filters['rating'])) {
            $query->byRating((int) $filters['rating']);
        }

        // Filter by verified purchase
        if (!empty($filters['verified_only'])) {
            $query->verified();
        }

        // Sort
        $sort = $filters['sort'] ?? 'recent';
        match ($sort) {
            'recent' => $query->recent(),
            'helpful' => $query->helpful(),
            'rating_high' => $query->orderByDesc('rating'),
            'rating_low' => $query->orderBy('rating'),
            default => $query->recent(),
        };

        return $query->paginate($filters['per_page'] ?? 10);
    }

    // =========================================================================
    // Category Operations
    // =========================================================================

    /**
     * Get all categories.
     */
    public function getCategories(): Collection
    {
        return MarketplaceCategory::rootLevel()
            ->ordered()
            ->with(['children'])
            ->get();
    }

    /**
     * Get a category with listings.
     */
    public function getCategory(string $slug): ?MarketplaceCategory
    {
        return MarketplaceCategory::where('slug', $slug)
            ->with(['children', 'listings' => fn($q) => $q->published()->popular()->limit(20)])
            ->first();
    }

    // =========================================================================
    // Analytics Operations
    // =========================================================================

    /**
     * Get analytics for a listing.
     */
    public function getListingAnalytics(MarketplaceListing $listing, string $period = 'month'): array
    {
        return [
            'views' => MarketplaceAnalytics::byListing($listing->id)
                ->byEvent('view')
                ->{"this" . ucfirst($period)}()
                ->count(),
            'installs' => MarketplaceAnalytics::byListing($listing->id)
                ->byEvent('install')
                ->{"this" . ucfirst($period)}()
                ->count(),
            'uninstalls' => MarketplaceAnalytics::byListing($listing->id)
                ->byEvent('uninstall')
                ->{"this" . ucfirst($period)}()
                ->count(),
            'active_installs' => $listing->active_install_count,
            'average_rating' => $listing->average_rating,
            'review_count' => $listing->review_count,
        ];
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function calculatePackageSize(string $path): int
    {
        if (is_file($path)) {
            return filesize($path);
        }

        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
