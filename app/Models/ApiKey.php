<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'key',
        'secret_hash',
        'user_id',
        'plugin_slug',
        'scopes',
        'allowed_endpoints',
        'allowed_ips',
        'rate_limit',
        'is_active',
        'expires_at',
        'last_used_at',
        'request_count',
    ];

    protected $casts = [
        'scopes' => 'array',
        'allowed_endpoints' => 'array',
        'allowed_ips' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'key',
        'secret_hash',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestLogs(): HasMany
    {
        return $this->hasMany(ApiRequestLog::class);
    }

    // =========================================================================
    // Key Generation
    // =========================================================================

    /**
     * Generate a new API key
     */
    public static function generateKey(): string
    {
        return 'pk_' . Str::random(32);
    }

    /**
     * Generate a secret key
     */
    public static function generateSecret(): string
    {
        return 'sk_' . Str::random(48);
    }

    /**
     * Create a new API key with secret
     */
    public static function createWithSecret(array $attributes): array
    {
        $key = static::generateKey();
        $secret = static::generateSecret();
        
        $apiKey = static::create(array_merge($attributes, [
            'key' => $key,
            'secret_hash' => hash('sha256', $secret),
        ]));

        return [
            'api_key' => $apiKey,
            'key' => $key,
            'secret' => $secret, // Only returned once!
        ];
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Check if API key is valid
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
     * Check if API key can access endpoint
     */
    public function canAccessEndpoint(ApiEndpoint $endpoint): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check allowed endpoints
        if ($this->allowed_endpoints) {
            if (!in_array($endpoint->id, $this->allowed_endpoints) &&
                !in_array($endpoint->slug, $this->allowed_endpoints)) {
                return false;
            }
        }

        // Check scopes
        if ($this->scopes && $endpoint->permissions) {
            $hasScope = false;
            foreach ($endpoint->permissions as $permission) {
                if (in_array($permission, $this->scopes)) {
                    $hasScope = true;
                    break;
                }
            }
            if (!$hasScope) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if IP is allowed
     */
    public function isIpAllowed(string $ip): bool
    {
        if (!$this->allowed_ips || empty($this->allowed_ips)) {
            return true;
        }

        foreach ($this->allowed_ips as $allowedIp) {
            if ($allowedIp === $ip) {
                return true;
            }
            
            // CIDR support
            if (str_contains($allowedIp, '/')) {
                if ($this->ipInCidr($ip, $allowedIp)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);
        
        return ($ip & $mask) === ($subnet & $mask);
    }

    /**
     * Verify secret
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->secret_hash, hash('sha256', $secret));
    }

    // =========================================================================
    // Usage Tracking
    // =========================================================================

    /**
     * Record API key usage
     */
    public function recordUsage(): void
    {
        $this->increment('request_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(int $days = 30): array
    {
        $since = now()->subDays($days);
        
        $logs = $this->requestLogs()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(response_time_ms) as avg_time')
            ->groupBy('date')
            ->get();

        return [
            'total_requests' => $this->request_count,
            'last_used' => $this->last_used_at,
            'daily_stats' => $logs,
        ];
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Find by key
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Find active by key
     */
    public static function findActiveByKey(string $key): ?self
    {
        return static::active()->where('key', $key)->first();
    }
}
