<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Models\Enterprise\RateLimitConfig;
use App\Models\Enterprise\ApiQuota;
use App\Services\Enterprise\RateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RateLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RateLimitService::class);
        Cache::flush();
    }

    public function test_allows_requests_within_limit(): void
    {
        RateLimitConfig::create([
            'tenant_id' => 1,
            'key' => 'api',
            'max_requests' => 10,
            'window_seconds' => 60,
            'is_active' => true,
        ]);

        $result = $this->service->attempt('api', 1);

        $this->assertTrue($result->allowed);
        $this->assertEquals(9, $result->remaining);
    }

    public function test_blocks_requests_over_limit(): void
    {
        RateLimitConfig::create([
            'tenant_id' => 1,
            'key' => 'api',
            'max_requests' => 2,
            'window_seconds' => 60,
            'is_active' => true,
        ]);

        $this->service->attempt('api', 1);
        $this->service->attempt('api', 1);
        $result = $this->service->attempt('api', 1);

        $this->assertFalse($result->allowed);
        $this->assertGreaterThan(0, $result->retryAfter);
    }

    public function test_allows_all_when_no_config(): void
    {
        $result = $this->service->attempt('api', 999);

        $this->assertTrue($result->allowed);
    }

    public function test_inactive_config_allows_all(): void
    {
        RateLimitConfig::create([
            'tenant_id' => 1,
            'key' => 'api',
            'max_requests' => 1,
            'window_seconds' => 60,
            'is_active' => false,
        ]);

        $result = $this->service->attempt('api', 1);

        $this->assertTrue($result->allowed);
    }

    public function test_quota_check_allows_within_limit(): void
    {
        ApiQuota::create([
            'tenant_id' => 1,
            'resource' => 'api_calls',
            'limit' => 1000,
            'used' => 500,
            'period' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        $result = $this->service->checkQuota(1, 'api_calls');

        $this->assertTrue($result->allowed);
        $this->assertEquals(1000, $result->limit);
        $this->assertEquals(500, $result->used);
    }

    public function test_quota_check_blocks_when_exhausted(): void
    {
        ApiQuota::create([
            'tenant_id' => 1,
            'resource' => 'api_calls',
            'limit' => 1000,
            'used' => 1000,
            'period' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'overage_allowed' => false,
        ]);

        $result = $this->service->checkQuota(1, 'api_calls');

        $this->assertFalse($result->allowed);
    }

    public function test_quota_allows_overage_when_enabled(): void
    {
        ApiQuota::create([
            'tenant_id' => 1,
            'resource' => 'api_calls',
            'limit' => 1000,
            'used' => 1500,
            'period' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'overage_allowed' => true,
        ]);

        $result = $this->service->checkQuota(1, 'api_calls');

        $this->assertTrue($result->allowed);
    }

    public function test_consume_quota_increments_usage(): void
    {
        $quota = ApiQuota::create([
            'tenant_id' => 1,
            'resource' => 'api_calls',
            'limit' => 1000,
            'used' => 0,
            'period' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        $this->service->consumeQuota(1, 'api_calls', 5);

        $quota->refresh();
        $this->assertEquals(5, $quota->used);
    }

    public function test_get_rate_limit_headers(): void
    {
        RateLimitConfig::create([
            'tenant_id' => 1,
            'key' => 'api',
            'max_requests' => 100,
            'window_seconds' => 60,
            'is_active' => true,
        ]);

        $headers = $this->service->getHeaders('api', 1);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertEquals(100, $headers['X-RateLimit-Limit']);
    }

    public function test_reset_clears_bucket(): void
    {
        RateLimitConfig::create([
            'tenant_id' => 1,
            'key' => 'api',
            'max_requests' => 5,
            'window_seconds' => 60,
            'is_active' => true,
        ]);

        // Consume some tokens
        $this->service->attempt('api', 1);
        $this->service->attempt('api', 1);

        // Reset
        $this->service->reset('api', 1);

        // Should have full tokens again
        $result = $this->service->attempt('api', 1);
        $this->assertEquals(4, $result->remaining);
    }

    public function test_get_quota_report(): void
    {
        ApiQuota::create([
            'tenant_id' => 1,
            'resource' => 'api_calls',
            'limit' => 1000,
            'used' => 800,
            'period' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        ApiQuota::create([
            'tenant_id' => 1,
            'resource' => 'storage_gb',
            'limit' => 10,
            'used' => 5,
            'period' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        $report = $this->service->getQuotaReport(1);

        $this->assertCount(2, $report);
        $this->assertEquals('api_calls', $report[0]['resource']);
        $this->assertEquals(80, $report[0]['percentage']);
    }

    public function test_initialize_quotas_creates_all_resources(): void
    {
        $this->service->initializeQuotas(1, 'starter');

        $quotas = ApiQuota::where('tenant_id', 1)->get();

        $this->assertGreaterThan(0, $quotas->count());
        $this->assertTrue($quotas->contains('resource', 'api_calls'));
        $this->assertTrue($quotas->contains('resource', 'storage_gb'));
    }
}
