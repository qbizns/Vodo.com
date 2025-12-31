<?php

declare(strict_types=1);

namespace App\Services\Plugins\Security;

use App\Models\PluginApiKey;
use App\Models\PluginAuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Plugin API Key Manager - Manages plugin API authentication.
 *
 * This service is responsible for:
 * - Creating and revoking API keys for plugins
 * - Authenticating API requests
 * - Rate limiting per API key
 * - Tracking API key usage
 */
class PluginApiKeyManager
{
    /**
     * Cache prefix for rate limiting.
     */
    protected const RATE_LIMIT_PREFIX = 'api_key_rate:';

    /**
     * Cache TTL for key validation.
     */
    protected const KEY_CACHE_TTL = 300;

    // =========================================================================
    // Key Management
    // =========================================================================

    /**
     * Create a new API key for a plugin.
     *
     * @return array{key: string, model: PluginApiKey}
     */
    public function createKey(
        string $pluginSlug,
        string $name,
        array $scopes = [],
        ?array $allowedIps = null,
        ?array $allowedDomains = null,
        ?\DateTimeInterface $expiresAt = null,
        ?int $rateLimitPerMinute = null,
        ?int $rateLimitPerHour = null,
        ?int $rateLimitPerDay = null
    ): array {
        $result = PluginApiKey::createForPlugin(
            $pluginSlug,
            $name,
            $scopes,
            $allowedIps,
            $allowedDomains,
            $expiresAt
        );

        $model = $result['model'];

        // Set custom rate limits if provided
        if ($rateLimitPerMinute !== null || $rateLimitPerHour !== null || $rateLimitPerDay !== null) {
            $model->update(array_filter([
                'rate_limit_per_minute' => $rateLimitPerMinute,
                'rate_limit_per_hour' => $rateLimitPerHour,
                'rate_limit_per_day' => $rateLimitPerDay,
            ]));
        }

        // Log the creation
        PluginAuditLog::security(
            $pluginSlug,
            PluginAuditLog::EVENT_API_KEY_CREATED,
            "API key created: {$name}",
            [
                'key_id' => $model->key_id,
                'scopes' => $scopes,
                'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
            ]
        );

        Log::info("Plugin API key created", [
            'plugin' => $pluginSlug,
            'key_name' => $name,
            'key_id' => $model->key_id,
        ]);

        return [
            'key' => $result['key'],
            'model' => $model,
        ];
    }

    /**
     * Revoke an API key.
     */
    public function revokeKey(int $keyId): bool
    {
        $key = PluginApiKey::find($keyId);

        if (!$key) {
            return false;
        }

        $key->revoke();

        // Clear cache
        $this->clearKeyCache($key->key_id);

        // Log the revocation
        PluginAuditLog::security(
            $key->plugin_slug,
            PluginAuditLog::EVENT_API_KEY_REVOKED,
            "API key revoked: {$key->name}",
            [
                'key_id' => $key->key_id,
            ]
        );

        Log::info("Plugin API key revoked", [
            'plugin' => $key->plugin_slug,
            'key_name' => $key->name,
            'key_id' => $key->key_id,
        ]);

        return true;
    }

    /**
     * Rotate an API key.
     *
     * @return string|null The new API key, or null if key not found
     */
    public function rotateKey(int $keyId): ?string
    {
        $key = PluginApiKey::find($keyId);

        if (!$key) {
            return null;
        }

        $oldKeyId = $key->key_id;
        $newKey = $key->rotate();

        // Clear old cache
        $this->clearKeyCache($oldKeyId);

        Log::info("Plugin API key rotated", [
            'plugin' => $key->plugin_slug,
            'key_name' => $key->name,
            'old_key_id' => $oldKeyId,
            'new_key_id' => $key->key_id,
        ]);

        return $newKey;
    }

    /**
     * Revoke all keys for a plugin.
     */
    public function revokeAllForPlugin(string $pluginSlug): int
    {
        $keys = PluginApiKey::forPlugin($pluginSlug)->active()->get();
        $count = 0;

        foreach ($keys as $key) {
            $key->revoke();
            $this->clearKeyCache($key->key_id);
            $count++;
        }

        if ($count > 0) {
            PluginAuditLog::security(
                $pluginSlug,
                PluginAuditLog::EVENT_API_KEY_REVOKED,
                "All API keys revoked ({$count} total)"
            );
        }

        return $count;
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    /**
     * Authenticate a request using an API key.
     *
     * @return array{valid: bool, key: ?PluginApiKey, error: ?string}
     */
    public function authenticate(string $apiKey, ?string $ip = null, ?string $domain = null): array
    {
        // Parse the key
        $parsed = PluginApiKey::parseKey($apiKey);

        if ($parsed['key_id'] === null) {
            return [
                'valid' => false,
                'key' => null,
                'error' => 'Invalid API key format',
            ];
        }

        // Look up the key (with caching)
        $model = $this->findKeyById($parsed['key_id']);

        if (!$model) {
            return [
                'valid' => false,
                'key' => null,
                'error' => 'API key not found',
            ];
        }

        // Validate the secret
        if (!$model->validateSecret($parsed['key_secret'])) {
            return [
                'valid' => false,
                'key' => null,
                'error' => 'Invalid API key',
            ];
        }

        // Check if key is valid
        if (!$model->isValid()) {
            $error = $model->isExpired() ? 'API key has expired' : 'API key is inactive';
            return [
                'valid' => false,
                'key' => $model,
                'error' => $error,
            ];
        }

        // Check IP restriction
        if ($ip && !$model->isIpAllowed($ip)) {
            PluginAuditLog::security(
                $model->plugin_slug,
                PluginAuditLog::EVENT_PERMISSION_DENIED,
                "API key used from unauthorized IP: {$ip}",
                ['key_id' => $model->key_id, 'ip' => $ip],
                PluginAuditLog::SEVERITY_WARNING
            );

            return [
                'valid' => false,
                'key' => $model,
                'error' => 'IP address not allowed',
            ];
        }

        // Check domain restriction
        if ($domain && !$model->isDomainAllowed($domain)) {
            return [
                'valid' => false,
                'key' => $model,
                'error' => 'Domain not allowed',
            ];
        }

        // Check rate limits
        $rateLimitResult = $this->checkRateLimit($model);
        if (!$rateLimitResult['allowed']) {
            return [
                'valid' => false,
                'key' => $model,
                'error' => "Rate limit exceeded: {$rateLimitResult['limit_type']}",
            ];
        }

        // Record usage
        $model->recordUsage($ip ?? 'unknown');

        // Log successful authentication
        PluginAuditLog::access(
            $model->plugin_slug,
            PluginAuditLog::EVENT_API_KEY_USED,
            "API key used: {$model->name}",
            [
                'key_id' => $model->key_id,
                'ip' => $ip,
            ],
            PluginAuditLog::SEVERITY_DEBUG
        );

        return [
            'valid' => true,
            'key' => $model,
            'error' => null,
        ];
    }

    /**
     * Extract API key from request headers.
     */
    public function extractFromRequest(\Illuminate\Http\Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        $authHeader = $request->header('Authorization', '');
        if (str_starts_with($authHeader, 'Bearer vodo_')) {
            return substr($authHeader, 7);
        }

        // Check X-API-Key header
        $apiKeyHeader = $request->header('X-API-Key', '');
        if (str_starts_with($apiKeyHeader, 'vodo_')) {
            return $apiKeyHeader;
        }

        // Check query parameter (not recommended for security)
        $queryKey = $request->query('api_key', '');
        if (is_string($queryKey) && str_starts_with($queryKey, 'vodo_')) {
            Log::warning("API key passed via query parameter - this is insecure");
            return $queryKey;
        }

        return null;
    }

    // =========================================================================
    // Rate Limiting
    // =========================================================================

    /**
     * Check if a request is within rate limits.
     *
     * @return array{allowed: bool, limit_type: ?string, current: int, limit: int}
     */
    public function checkRateLimit(PluginApiKey $key): array
    {
        $checks = [
            'minute' => [
                'limit' => $key->rate_limit_per_minute,
                'window' => 60,
            ],
            'hour' => [
                'limit' => $key->rate_limit_per_hour,
                'window' => 3600,
            ],
            'day' => [
                'limit' => $key->rate_limit_per_day,
                'window' => 86400,
            ],
        ];

        foreach ($checks as $type => $config) {
            $cacheKey = self::RATE_LIMIT_PREFIX . "{$key->key_id}:{$type}";
            $current = (int) Cache::get($cacheKey, 0);

            if ($current >= $config['limit']) {
                return [
                    'allowed' => false,
                    'limit_type' => "per_{$type}",
                    'current' => $current,
                    'limit' => $config['limit'],
                ];
            }
        }

        // Increment counters
        foreach ($checks as $type => $config) {
            $cacheKey = self::RATE_LIMIT_PREFIX . "{$key->key_id}:{$type}";
            $current = (int) Cache::get($cacheKey, 0);
            Cache::put($cacheKey, $current + 1, $config['window']);
        }

        return [
            'allowed' => true,
            'limit_type' => null,
            'current' => 0,
            'limit' => 0,
        ];
    }

    /**
     * Get current rate limit status for a key.
     */
    public function getRateLimitStatus(PluginApiKey $key): array
    {
        return [
            'minute' => [
                'current' => (int) Cache::get(self::RATE_LIMIT_PREFIX . "{$key->key_id}:minute", 0),
                'limit' => $key->rate_limit_per_minute,
            ],
            'hour' => [
                'current' => (int) Cache::get(self::RATE_LIMIT_PREFIX . "{$key->key_id}:hour", 0),
                'limit' => $key->rate_limit_per_hour,
            ],
            'day' => [
                'current' => (int) Cache::get(self::RATE_LIMIT_PREFIX . "{$key->key_id}:day", 0),
                'limit' => $key->rate_limit_per_day,
            ],
        ];
    }

    /**
     * Reset rate limits for a key.
     */
    public function resetRateLimits(PluginApiKey $key): void
    {
        Cache::forget(self::RATE_LIMIT_PREFIX . "{$key->key_id}:minute");
        Cache::forget(self::RATE_LIMIT_PREFIX . "{$key->key_id}:hour");
        Cache::forget(self::RATE_LIMIT_PREFIX . "{$key->key_id}:day");
    }

    // =========================================================================
    // Query Methods
    // =========================================================================

    /**
     * Get all keys for a plugin.
     */
    public function getKeysForPlugin(string $pluginSlug): Collection
    {
        return PluginApiKey::forPlugin($pluginSlug)->get();
    }

    /**
     * Get active keys for a plugin.
     */
    public function getActiveKeysForPlugin(string $pluginSlug): Collection
    {
        return PluginApiKey::forPlugin($pluginSlug)->active()->get();
    }

    /**
     * Find a key by ID.
     */
    public function findKeyById(string $keyId): ?PluginApiKey
    {
        // Check cache first
        $cacheKey = "api_key:{$keyId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached ?: null;
        }

        $key = PluginApiKey::where('key_id', $keyId)->first();

        // Cache the result (even if null)
        Cache::put($cacheKey, $key ?: false, self::KEY_CACHE_TTL);

        return $key;
    }

    /**
     * Get usage statistics for a key.
     */
    public function getKeyStats(PluginApiKey $key): array
    {
        return [
            'key_id' => $key->key_id,
            'name' => $key->name,
            'plugin' => $key->plugin_slug,
            'total_requests' => $key->total_requests,
            'last_used_at' => $key->last_used_at?->toIso8601String(),
            'last_used_ip' => $key->last_used_ip,
            'rate_limits' => $this->getRateLimitStatus($key),
            'is_active' => $key->is_active,
            'expires_at' => $key->expires_at?->toIso8601String(),
            'scopes' => $key->scopes,
        ];
    }

    // =========================================================================
    // Cache Management
    // =========================================================================

    /**
     * Clear the cache for a key.
     */
    protected function clearKeyCache(string $keyId): void
    {
        Cache::forget("api_key:{$keyId}");
        Cache::forget(self::RATE_LIMIT_PREFIX . "{$keyId}:minute");
        Cache::forget(self::RATE_LIMIT_PREFIX . "{$keyId}:hour");
        Cache::forget(self::RATE_LIMIT_PREFIX . "{$keyId}:day");
    }
}
