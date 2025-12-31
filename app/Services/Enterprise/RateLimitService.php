<?php

declare(strict_types=1);

namespace App\Services\Enterprise;

use App\Models\Enterprise\RateLimitConfig;
use App\Models\Enterprise\ApiQuota;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Rate Limit Service
 *
 * Manages API rate limiting using token bucket algorithm and quota tracking.
 */
class RateLimitService
{
    protected string $cachePrefix = 'rate_limit:';

    /**
     * Check if a request is allowed.
     */
    public function attempt(string $key, int $tenantId, ?int $userId = null): RateLimitResult
    {
        $config = $this->getConfig($key, $tenantId);

        if (!$config || !$config->is_active) {
            return RateLimitResult::allowed();
        }

        $bucketKey = $this->getBucketKey($key, $tenantId, $userId);

        return $this->checkTokenBucket(
            $bucketKey,
            $config->max_requests,
            $config->window_seconds,
            $config->burst_limit
        );
    }

    /**
     * Check quota for a resource.
     */
    public function checkQuota(int $tenantId, string $resource, int $amount = 1): QuotaResult
    {
        $quota = ApiQuota::byTenant($tenantId)
            ->byResource($resource)
            ->current()
            ->first();

        if (!$quota) {
            // No quota defined, allow
            return QuotaResult::allowed(null, null);
        }

        if ($quota->isExhausted() && !$quota->overage_allowed) {
            return QuotaResult::exceeded($quota->limit, $quota->used);
        }

        return QuotaResult::allowed($quota->limit, $quota->used);
    }

    /**
     * Consume quota.
     */
    public function consumeQuota(int $tenantId, string $resource, int $amount = 1): bool
    {
        $quota = ApiQuota::byTenant($tenantId)
            ->byResource($resource)
            ->current()
            ->first();

        if (!$quota) {
            return true;
        }

        if ($quota->isExhausted() && !$quota->overage_allowed) {
            return false;
        }

        $quota->increment($amount);

        // Log if near limit
        if ($quota->isNearLimit(90)) {
            Log::warning('Quota near limit', [
                'tenant_id' => $tenantId,
                'resource' => $resource,
                'usage' => $quota->getUsagePercentage(),
            ]);
        }

        return true;
    }

    /**
     * Get rate limit headers for response.
     */
    public function getHeaders(string $key, int $tenantId, ?int $userId = null): array
    {
        $config = $this->getConfig($key, $tenantId);

        if (!$config) {
            return [];
        }

        $bucketKey = $this->getBucketKey($key, $tenantId, $userId);
        $bucket = $this->getBucket($bucketKey);

        $remaining = $bucket['tokens'] ?? $config->max_requests;
        $resetAt = $bucket['reset_at'] ?? now()->addSeconds($config->window_seconds)->timestamp;

        return [
            'X-RateLimit-Limit' => $config->max_requests,
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset' => $resetAt,
            'X-RateLimit-Window' => $config->window_seconds,
        ];
    }

    /**
     * Reset rate limit for a key.
     */
    public function reset(string $key, int $tenantId, ?int $userId = null): void
    {
        $bucketKey = $this->getBucketKey($key, $tenantId, $userId);
        Cache::forget($this->cachePrefix . $bucketKey);
    }

    /**
     * Get quota usage report.
     */
    public function getQuotaReport(int $tenantId): array
    {
        $quotas = ApiQuota::byTenant($tenantId)->current()->get();

        return $quotas->map(fn($q) => [
            'resource' => $q->resource,
            'limit' => $q->limit,
            'used' => $q->used,
            'remaining' => $q->getRemaining(),
            'percentage' => $q->getUsagePercentage(),
            'period' => $q->period,
            'period_end' => $q->period_end->toDateString(),
            'overage_allowed' => $q->overage_allowed,
            'overage_cost' => $q->calculateOverageCost(),
        ])->all();
    }

    /**
     * Initialize quotas for a new billing period.
     */
    public function initializeQuotas(int $tenantId, string $plan): void
    {
        $limits = $this->getPlanLimits($plan);

        foreach ($limits as $resource => $limit) {
            ApiQuota::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'resource' => $resource,
                    'period_start' => now()->startOfMonth(),
                ],
                [
                    'limit' => $limit,
                    'used' => 0,
                    'period' => 'monthly',
                    'period_end' => now()->endOfMonth(),
                    'overage_allowed' => $plan !== 'free',
                    'overage_rate' => $this->getOverageRate($resource),
                ]
            );
        }
    }

    /**
     * Token bucket rate limiting.
     */
    protected function checkTokenBucket(
        string $key,
        int $maxTokens,
        int $windowSeconds,
        ?int $burstLimit = null
    ): RateLimitResult {
        $cacheKey = $this->cachePrefix . $key;
        $now = microtime(true);
        $burstLimit = $burstLimit ?? $maxTokens;

        $bucket = Cache::get($cacheKey, [
            'tokens' => $maxTokens,
            'last_refill' => $now,
        ]);

        // Calculate token refill
        $elapsed = $now - $bucket['last_refill'];
        $refillRate = $maxTokens / $windowSeconds;
        $tokensToAdd = $elapsed * $refillRate;

        $bucket['tokens'] = min($burstLimit, $bucket['tokens'] + $tokensToAdd);
        $bucket['last_refill'] = $now;

        // Check if we can consume a token
        if ($bucket['tokens'] < 1) {
            $retryAfter = (1 - $bucket['tokens']) / $refillRate;

            Cache::put($cacheKey, $bucket, $windowSeconds * 2);

            return RateLimitResult::limited(
                (int) ceil($retryAfter),
                0,
                $maxTokens
            );
        }

        // Consume token
        $bucket['tokens'] -= 1;
        Cache::put($cacheKey, $bucket, $windowSeconds * 2);

        return RateLimitResult::allowed(
            (int) $bucket['tokens'],
            $maxTokens
        );
    }

    /**
     * Get bucket data.
     */
    protected function getBucket(string $key): array
    {
        return Cache::get($this->cachePrefix . $key, []);
    }

    /**
     * Get rate limit config.
     */
    protected function getConfig(string $key, int $tenantId): ?RateLimitConfig
    {
        // Try tenant-specific first
        $config = RateLimitConfig::forTenant($tenantId)
            ->forKey($key)
            ->active()
            ->first();

        if ($config) {
            return $config;
        }

        // Fall back to default (no tenant)
        return RateLimitConfig::whereNull('tenant_id')
            ->forKey($key)
            ->active()
            ->first();
    }

    /**
     * Get bucket key.
     */
    protected function getBucketKey(string $key, int $tenantId, ?int $userId): string
    {
        if ($userId) {
            return "user:{$userId}:{$key}";
        }

        return "tenant:{$tenantId}:{$key}";
    }

    /**
     * Get plan limits.
     */
    protected function getPlanLimits(string $plan): array
    {
        return match ($plan) {
            'free' => [
                'api_calls' => 10000,
                'storage_gb' => 1,
                'plugins' => 5,
                'users' => 3,
                'webhooks' => 5,
            ],
            'starter' => [
                'api_calls' => 100000,
                'storage_gb' => 10,
                'plugins' => 20,
                'users' => 10,
                'webhooks' => 20,
            ],
            'professional' => [
                'api_calls' => 1000000,
                'storage_gb' => 100,
                'plugins' => 50,
                'users' => 50,
                'webhooks' => 100,
            ],
            'enterprise' => [
                'api_calls' => PHP_INT_MAX,
                'storage_gb' => 1000,
                'plugins' => PHP_INT_MAX,
                'users' => PHP_INT_MAX,
                'webhooks' => PHP_INT_MAX,
            ],
            default => [
                'api_calls' => 10000,
                'storage_gb' => 1,
                'plugins' => 5,
                'users' => 3,
                'webhooks' => 5,
            ],
        };
    }

    /**
     * Get overage rate for a resource.
     */
    protected function getOverageRate(string $resource): float
    {
        return match ($resource) {
            'api_calls' => 0.0001, // $0.0001 per call
            'storage_gb' => 0.10, // $0.10 per GB
            'plugins' => 5.00, // $5 per additional plugin
            'users' => 2.00, // $2 per additional user
            'webhooks' => 1.00, // $1 per additional webhook
            default => 0,
        };
    }
}

/**
 * Rate Limit Result
 */
class RateLimitResult
{
    public function __construct(
        public bool $allowed,
        public int $remaining,
        public int $limit,
        public int $retryAfter = 0,
    ) {}

    public static function allowed(int $remaining = 0, int $limit = 0): self
    {
        return new self(true, $remaining, $limit);
    }

    public static function limited(int $retryAfter, int $remaining, int $limit): self
    {
        return new self(false, $remaining, $limit, $retryAfter);
    }
}

/**
 * Quota Result
 */
class QuotaResult
{
    public function __construct(
        public bool $allowed,
        public ?int $limit,
        public ?int $used,
    ) {}

    public static function allowed(?int $limit, ?int $used): self
    {
        return new self(true, $limit, $used);
    }

    public static function exceeded(int $limit, int $used): self
    {
        return new self(false, $limit, $used);
    }
}
