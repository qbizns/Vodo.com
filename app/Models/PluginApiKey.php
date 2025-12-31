<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Plugin API Key - Authentication credentials for plugin API access.
 *
 * @property int $id
 * @property string $plugin_slug
 * @property string $name
 * @property string $key_id
 * @property string $key_hash
 * @property string $key_prefix
 * @property array|null $scopes
 * @property array|null $allowed_ips
 * @property array|null $allowed_domains
 * @property int $rate_limit_per_minute
 * @property int $rate_limit_per_hour
 * @property int $rate_limit_per_day
 * @property int $total_requests
 * @property \Carbon\Carbon|null $last_used_at
 * @property string|null $last_used_ip
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PluginApiKey extends Model
{
    protected $fillable = [
        'plugin_slug',
        'name',
        'key_id',
        'key_hash',
        'key_prefix',
        'scopes',
        'allowed_ips',
        'allowed_domains',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'total_requests',
        'last_used_at',
        'last_used_ip',
        'expires_at',
        'is_active',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'allowed_ips' => 'array',
            'allowed_domains' => 'array',
            'total_requests' => 'integer',
            'rate_limit_per_minute' => 'integer',
            'rate_limit_per_hour' => 'integer',
            'rate_limit_per_day' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class, 'plugin_slug', 'slug');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForPlugin($query, string $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    // =========================================================================
    // Key Generation & Validation
    // =========================================================================

    /**
     * Generate a new API key.
     *
     * @return array{key_id: string, key_secret: string, key_full: string}
     */
    public static function generateKey(): array
    {
        $keyId = Str::random(32);
        $keySecret = Str::random(64);
        $keyFull = "vodo_{$keyId}_{$keySecret}";

        return [
            'key_id' => $keyId,
            'key_secret' => $keySecret,
            'key_full' => $keyFull,
        ];
    }

    /**
     * Create a new API key for a plugin.
     *
     * @return array{model: PluginApiKey, key: string}
     */
    public static function createForPlugin(
        string $pluginSlug,
        string $name,
        array $scopes = [],
        ?array $allowedIps = null,
        ?array $allowedDomains = null,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $keyData = self::generateKey();

        $model = static::create([
            'plugin_slug' => $pluginSlug,
            'name' => $name,
            'key_id' => $keyData['key_id'],
            'key_hash' => hash('sha256', $keyData['key_secret']),
            'key_prefix' => substr($keyData['key_id'], 0, 8),
            'scopes' => $scopes,
            'allowed_ips' => $allowedIps,
            'allowed_domains' => $allowedDomains,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return [
            'model' => $model,
            'key' => $keyData['key_full'],
        ];
    }

    /**
     * Parse an API key string.
     *
     * @return array{key_id: string|null, key_secret: string|null}
     */
    public static function parseKey(string $apiKey): array
    {
        if (!str_starts_with($apiKey, 'vodo_')) {
            return ['key_id' => null, 'key_secret' => null];
        }

        $parts = explode('_', substr($apiKey, 5), 2);

        if (count($parts) !== 2) {
            return ['key_id' => null, 'key_secret' => null];
        }

        return [
            'key_id' => $parts[0],
            'key_secret' => $parts[1],
        ];
    }

    /**
     * Find and validate an API key.
     */
    public static function findByKey(string $apiKey): ?self
    {
        $parsed = self::parseKey($apiKey);

        if ($parsed['key_id'] === null) {
            return null;
        }

        $model = static::active()
            ->where('key_id', $parsed['key_id'])
            ->first();

        if (!$model) {
            return null;
        }

        if (!$model->validateSecret($parsed['key_secret'])) {
            return null;
        }

        return $model;
    }

    /**
     * Validate the key secret.
     */
    public function validateSecret(string $secret): bool
    {
        return hash_equals($this->key_hash, hash('sha256', $secret));
    }

    // =========================================================================
    // Status & Validation
    // =========================================================================

    /**
     * Check if this key is valid for use.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the key is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if an IP is allowed.
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips, true);
    }

    /**
     * Check if a domain is allowed.
     */
    public function isDomainAllowed(string $domain): bool
    {
        if (empty($this->allowed_domains)) {
            return true;
        }

        foreach ($this->allowed_domains as $allowed) {
            if ($domain === $allowed) {
                return true;
            }

            if (str_starts_with($allowed, '*.') && str_ends_with($domain, substr($allowed, 1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this key has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return false;
        }

        if (in_array('*', $this->scopes, true)) {
            return true;
        }

        return in_array($scope, $this->scopes, true);
    }

    // =========================================================================
    // Usage Tracking
    // =========================================================================

    /**
     * Record usage of this key.
     */
    public function recordUsage(string $ip): void
    {
        $this->increment('total_requests');
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ]);
    }

    /**
     * Rotate this API key (generate new secret).
     *
     * @return string The new full API key
     */
    public function rotate(): string
    {
        $keyData = self::generateKey();

        $this->update([
            'key_id' => $keyData['key_id'],
            'key_hash' => hash('sha256', $keyData['key_secret']),
            'key_prefix' => substr($keyData['key_id'], 0, 8),
        ]);

        return $keyData['key_full'];
    }

    /**
     * Revoke this API key.
     */
    public function revoke(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Get a masked version of the key for display.
     */
    public function getMaskedKey(): string
    {
        return "vodo_{$this->key_prefix}..." . str_repeat('*', 8);
    }
}
