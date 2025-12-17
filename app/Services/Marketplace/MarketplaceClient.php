<?php

namespace App\Services\Marketplace;

use App\Models\MarketplacePlugin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class MarketplaceClient
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('marketplace.api_url', 'https://marketplace.example.com/api/v1');
        $this->apiKey = config('marketplace.api_key');
        $this->timeout = config('marketplace.timeout', 30);
    }

    // =========================================================================
    // Plugin Discovery
    // =========================================================================

    /**
     * Search marketplace plugins
     */
    public function search(string $query, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $response = $this->request('GET', '/plugins', [
            'query' => $query,
            'page' => $page,
            'per_page' => $perPage,
            ...$filters,
        ]);

        return $response;
    }

    /**
     * Get featured plugins
     */
    public function getFeatured(int $limit = 10): array
    {
        return Cache::remember('marketplace:featured', 3600, function () use ($limit) {
            return $this->request('GET', '/plugins/featured', ['limit' => $limit]);
        });
    }

    /**
     * Get popular plugins
     */
    public function getPopular(int $limit = 10): array
    {
        return Cache::remember('marketplace:popular', 3600, function () use ($limit) {
            return $this->request('GET', '/plugins/popular', ['limit' => $limit]);
        });
    }

    /**
     * Get new plugins
     */
    public function getNew(int $limit = 10): array
    {
        return Cache::remember('marketplace:new', 3600, function () use ($limit) {
            return $this->request('GET', '/plugins/new', ['limit' => $limit]);
        });
    }

    /**
     * Get plugin details
     */
    public function getPlugin(string $marketplaceId): ?array
    {
        try {
            return $this->request('GET', "/plugins/{$marketplaceId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get plugin versions
     */
    public function getVersions(string $marketplaceId): array
    {
        return $this->request('GET', "/plugins/{$marketplaceId}/versions");
    }

    /**
     * Get categories
     */
    public function getCategories(): array
    {
        return Cache::remember('marketplace:categories', 86400, function () {
            return $this->request('GET', '/categories');
        });
    }

    // =========================================================================
    // License Operations
    // =========================================================================

    /**
     * Verify a license key
     */
    public function verifyLicense(string $licenseKey, string $pluginSlug, string $domain): array
    {
        return $this->request('POST', '/licenses/verify', [
            'license_key' => $licenseKey,
            'plugin_slug' => $pluginSlug,
            'domain' => $domain,
            'instance_id' => $this->getInstanceId(),
        ]);
    }

    /**
     * Activate a license
     */
    public function activateLicense(string $licenseKey, string $pluginSlug, string $email): array
    {
        return $this->request('POST', '/licenses/activate', [
            'license_key' => $licenseKey,
            'plugin_slug' => $pluginSlug,
            'email' => $email,
            'domain' => config('app.url'),
            'instance_id' => $this->getInstanceId(),
        ]);
    }

    /**
     * Deactivate a license
     */
    public function deactivateLicense(string $licenseKey, string $instanceId): array
    {
        return $this->request('POST', '/licenses/deactivate', [
            'license_key' => $licenseKey,
            'instance_id' => $instanceId,
        ]);
    }

    /**
     * Get license details
     */
    public function getLicenseInfo(string $licenseKey): ?array
    {
        try {
            return $this->request('GET', '/licenses/info', [
                'license_key' => $licenseKey,
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    // =========================================================================
    // Update Operations
    // =========================================================================

    /**
     * Check for updates for multiple plugins
     */
    public function checkUpdates(array $plugins): array
    {
        return $this->request('POST', '/updates/check', [
            'plugins' => $plugins,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    }

    /**
     * Get download URL for an update
     */
    public function getDownloadUrl(string $marketplaceId, string $version, ?string $licenseKey = null): ?string
    {
        try {
            $response = $this->request('POST', '/downloads/url', [
                'plugin_id' => $marketplaceId,
                'version' => $version,
                'license_key' => $licenseKey,
                'instance_id' => $this->getInstanceId(),
            ]);

            return $response['download_url'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Download a plugin package
     */
    public function downloadPackage(string $url, string $destination): bool
    {
        try {
            $response = Http::timeout($this->timeout * 3)
                ->withHeaders($this->getHeaders())
                ->get($url);

            if ($response->successful()) {
                file_put_contents($destination, $response->body());
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // Sync Operations
    // =========================================================================

    /**
     * Sync marketplace plugins to local cache
     */
    public function syncPlugins(int $page = 1, int $perPage = 100): int
    {
        $synced = 0;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->request('GET', '/plugins', [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $plugins = $response['data'] ?? [];

            foreach ($plugins as $pluginData) {
                $this->syncPlugin($pluginData);
                $synced++;
            }

            $hasMore = count($plugins) === $perPage;
            $page++;
        }

        return $synced;
    }

    /**
     * Sync single plugin to local cache
     */
    public function syncPlugin(array $data): MarketplacePlugin
    {
        return MarketplacePlugin::updateOrCreate(
            ['marketplace_id' => $data['id']],
            [
                'slug' => $data['slug'],
                'name' => $data['name'],
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'author' => $data['author'] ?? null,
                'author_url' => $data['author_url'] ?? null,
                'is_verified_author' => $data['is_verified_author'] ?? false,
                'latest_version' => $data['version'] ?? $data['latest_version'] ?? '1.0.0',
                'requires_php' => $data['requires_php'] ?? null,
                'requires_laravel' => $data['requires_laravel'] ?? null,
                'price' => $data['price'] ?? 0,
                'currency' => $data['currency'] ?? 'USD',
                'is_free' => ($data['price'] ?? 0) == 0,
                'pricing_tiers' => $data['pricing_tiers'] ?? null,
                'downloads' => $data['downloads'] ?? 0,
                'active_installs' => $data['active_installs'] ?? 0,
                'rating' => $data['rating'] ?? 0,
                'rating_count' => $data['rating_count'] ?? 0,
                'categories' => $data['categories'] ?? [],
                'tags' => $data['tags'] ?? [],
                'icon_url' => $data['icon_url'] ?? null,
                'screenshots' => $data['screenshots'] ?? [],
                'is_featured' => $data['is_featured'] ?? false,
                'is_verified' => $data['is_verified'] ?? false,
                'last_updated' => $data['updated_at'] ?? null,
                'synced_at' => now(),
            ]
        );
    }

    // =========================================================================
    // HTTP Helpers
    // =========================================================================

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $http = Http::timeout($this->timeout)
            ->withHeaders($this->getHeaders());

        $response = match (strtoupper($method)) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url, $data),
            default => throw new \InvalidArgumentException("Invalid HTTP method: {$method}"),
        };

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Marketplace API error: " . ($response->json()['message'] ?? $response->status())
            );
        }

        return $response->json();
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Site-URL' => config('app.url'),
        ];

        if ($this->apiKey) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        return $headers;
    }

    protected function getInstanceId(): string
    {
        return Cache::rememberForever('marketplace:instance_id', function () {
            return hash('sha256', config('app.url') . '|' . config('app.key'));
        });
    }

    // =========================================================================
    // Cache Management
    // =========================================================================

    public function clearCache(): void
    {
        Cache::forget('marketplace:featured');
        Cache::forget('marketplace:popular');
        Cache::forget('marketplace:new');
        Cache::forget('marketplace:categories');
    }
}
