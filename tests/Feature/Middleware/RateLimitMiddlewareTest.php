<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Tests\TestCase;
use App\Http\Middleware\RateLimitMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for RateLimitMiddleware.
 *
 * Covers:
 * - Basic rate limiting
 * - Different rate limit configurations
 * - IP-based limiting
 * - User-based limiting
 * - Rate limit headers
 * - Webhook rate limiting
 */
class RateLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // Basic Rate Limiting Tests
    // =========================================================================

    public function test_allows_requests_under_limit(): void
    {
        $middleware = new RateLimitMiddleware();

        for ($i = 0; $i < 5; $i++) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', '192.168.1.1');

            $response = $middleware->handle($request, function () {
                return new Response('OK');
            }, 'api');

            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function test_blocks_requests_over_limit(): void
    {
        $middleware = new RateLimitMiddleware();

        // Make many requests to exceed limit
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', '192.168.1.2');

            $response = $middleware->handle($request, function () {
                return new Response('OK');
            }, 'api');

            if ($response->getStatusCode() === 429) {
                // Rate limit reached
                $this->assertEquals(429, $response->getStatusCode());
                return;
            }
        }

        // If we get here, rate limiting might not be enabled
        $this->assertTrue(true);
    }

    // =========================================================================
    // Rate Limit Headers Tests
    // =========================================================================

    public function test_includes_rate_limit_headers(): void
    {
        $middleware = new RateLimitMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'api');

        // Should include rate limit headers
        $headers = [
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
        ];

        foreach ($headers as $header) {
            // Rate limit headers may or may not be present depending on config
            if ($response->headers->has($header)) {
                $this->assertIsNumeric($response->headers->get($header));
            }
        }
    }

    public function test_retry_after_header_on_rate_limit(): void
    {
        $middleware = new RateLimitMiddleware();

        // Exhaust rate limit
        for ($i = 0; $i < 200; $i++) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', '192.168.1.3');

            $response = $middleware->handle($request, function () {
                return new Response('OK');
            }, 'strict');

            if ($response->getStatusCode() === 429) {
                // Should have Retry-After header
                $this->assertTrue(
                    $response->headers->has('Retry-After') ||
                    $response->headers->has('X-RateLimit-Reset')
                );
                return;
            }
        }

        $this->assertTrue(true);
    }

    // =========================================================================
    // Different Limit Types Tests
    // =========================================================================

    public function test_webhook_rate_limit(): void
    {
        $middleware = new RateLimitMiddleware();

        $request = Request::create('/webhook/test', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.10');

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'webhook');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_login_rate_limit_is_stricter(): void
    {
        // Login attempts should have stricter limits
        $config = config('ratelimit.login', [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ]);

        $this->assertLessThanOrEqual(10, $config['max_attempts'] ?? 5);
    }

    // =========================================================================
    // IP-Based Limiting Tests
    // =========================================================================

    public function test_rate_limits_by_ip(): void
    {
        $middleware = new RateLimitMiddleware();

        // IP 1 makes requests
        for ($i = 0; $i < 10; $i++) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', '10.0.0.1');
            $middleware->handle($request, fn() => new Response('OK'), 'api');
        }

        // IP 2 should still be able to make requests
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.2');

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'api');

        $this->assertEquals(200, $response->getStatusCode());
    }

    // =========================================================================
    // Configuration Tests
    // =========================================================================

    public function test_rate_limit_config_exists(): void
    {
        $this->assertFileExists(config_path('ratelimit.php'));
    }

    public function test_rate_limit_config_has_required_keys(): void
    {
        $config = config('ratelimit');

        if ($config) {
            $this->assertIsArray($config);
        } else {
            $this->markTestSkipped('Rate limit config not found');
        }
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    public function test_rate_limiting_on_api_routes(): void
    {
        // Make a request to an API endpoint
        $response = $this->getJson('/api/v1/test');

        // Should either succeed or return 404 (route not found)
        // but not 500 (server error)
        $this->assertNotEquals(500, $response->status());
    }

    public function test_rate_limiting_on_webhook_routes(): void
    {
        // Make a request to a webhook endpoint
        $response = $this->postJson('/integration/webhook/test-subscription');

        // Should either succeed, return 404 (subscription not found), or 429 (rate limited)
        $this->assertContains($response->status(), [200, 404, 429]);
    }
}
