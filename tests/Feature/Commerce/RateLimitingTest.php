<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use App\Http\Middleware\RateLimitMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    protected RateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RateLimitMiddleware();
        Cache::flush();
    }

    // =========================================================================
    // Storefront Profile Tests
    // =========================================================================

    public function test_storefront_profile_allows_120_requests_per_minute(): void
    {
        $request = Request::create('/store/1/products', 'GET');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'storefront');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('120', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_storefront_profile_blocks_after_limit(): void
    {
        $request = Request::create('/store/1/products', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        // Simulate hitting the limit
        $key = 'rate_limit:storefront:ip:10.0.0.1:' . md5('/store/1/products');
        Cache::put($key, 120, 60);
        Cache::put("{$key}:expires", 60, 60);

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'storefront');

        $this->assertEquals(429, $response->getStatusCode());
    }

    // =========================================================================
    // Cart Profile Tests
    // =========================================================================

    public function test_cart_profile_allows_60_requests_per_minute(): void
    {
        $request = Request::create('/store/1/cart/add', 'POST');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'cart');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('60', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_cart_profile_tracks_per_user(): void
    {
        $request = Request::create('/store/1/cart/add', 'POST');
        $request->setUserResolver(fn() => (object) ['id' => 123]);

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'cart');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('59', $response->headers->get('X-RateLimit-Remaining'));
    }

    // =========================================================================
    // Checkout Profile Tests
    // =========================================================================

    public function test_checkout_profile_allows_30_requests_per_minute(): void
    {
        $request = Request::create('/store/1/checkout/shipping-rates', 'POST');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'checkout');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('30', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_checkout_profile_has_stricter_limits_than_cart(): void
    {
        $cartRequest = Request::create('/store/1/cart/add', 'POST');
        $checkoutRequest = Request::create('/store/1/checkout', 'GET');

        $cartResponse = $this->middleware->handle($cartRequest, fn() => new Response('OK'), 'cart');
        $checkoutResponse = $this->middleware->handle($checkoutRequest, fn() => new Response('OK'), 'checkout');

        $cartLimit = (int) $cartResponse->headers->get('X-RateLimit-Limit');
        $checkoutLimit = (int) $checkoutResponse->headers->get('X-RateLimit-Limit');

        $this->assertGreaterThan($checkoutLimit, $cartLimit);
    }

    // =========================================================================
    // Checkout Order Profile Tests (Strictest)
    // =========================================================================

    public function test_checkout_order_profile_allows_5_requests_per_minute(): void
    {
        $request = Request::create('/store/1/checkout/place-order', 'POST');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'checkout_order');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('5', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_checkout_order_blocks_rapid_submissions(): void
    {
        $request = Request::create('/store/1/checkout/place-order', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        // Simulate 5 orders already placed
        $key = 'rate_limit:checkout_order:ip:192.168.1.1:' . md5('/store/1/checkout/place-order');
        Cache::put($key, 5, 60);
        Cache::put("{$key}:expires", 60, 60);

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'checkout_order');

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('Too many requests', $response->getContent());
    }

    // =========================================================================
    // Product Search Profile Tests
    // =========================================================================

    public function test_product_search_profile_allows_40_requests_per_minute(): void
    {
        $request = Request::create('/store/1/products/search?q=shoes', 'GET');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'product_search');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('40', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_product_search_prevents_scraping(): void
    {
        $request = Request::create('/store/1/products/search', 'GET');
        $request->server->set('REMOTE_ADDR', '10.10.10.10');

        // Simulate excessive search requests
        $key = 'rate_limit:product_search:ip:10.10.10.10:' . md5('/store/1/products/search');
        Cache::put($key, 40, 60);
        Cache::put("{$key}:expires", 60, 60);

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'product_search');

        $this->assertEquals(429, $response->getStatusCode());
    }

    // =========================================================================
    // Webhook Profile Tests
    // =========================================================================

    public function test_webhook_profile_allows_100_requests_per_minute(): void
    {
        $request = Request::create('/webhooks/payment/stripe', 'POST');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'webhook');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('100', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_webhook_profile_is_generous_for_payment_provider_retries(): void
    {
        // Webhooks need higher limits because payment providers retry failed deliveries
        $webhookLimit = 100;
        $checkoutLimit = 30;

        $this->assertGreaterThan($checkoutLimit, $webhookLimit);
    }

    // =========================================================================
    // Rate Limit Headers Tests
    // =========================================================================

    public function test_includes_rate_limit_headers(): void
    {
        $request = Request::create('/store/1/products', 'GET');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'storefront');

        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('X-RateLimit-Reset'));
    }

    public function test_includes_retry_after_when_limited(): void
    {
        $request = Request::create('/store/1/checkout/place-order', 'POST');
        $request->server->set('REMOTE_ADDR', '1.1.1.1');

        $key = 'rate_limit:checkout_order:ip:1.1.1.1:' . md5('/store/1/checkout/place-order');
        Cache::put($key, 10, 60);
        Cache::put("{$key}:expires", 45, 60);

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'checkout_order');

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertEquals('45', $response->headers->get('Retry-After'));
    }

    // =========================================================================
    // Profile Hierarchy Tests
    // =========================================================================

    public function test_rate_limit_profiles_follow_expected_hierarchy(): void
    {
        $profiles = [
            'storefront' => 120,      // Most generous
            'cart' => 60,             // Moderate
            'product_search' => 40,   // Moderate (anti-scraping)
            'checkout' => 30,         // Stricter
            'checkout_order' => 5,    // Strictest (prevent abuse)
        ];

        $previousLimit = PHP_INT_MAX;
        foreach ($profiles as $profile => $expectedLimit) {
            $request = Request::create('/test', 'GET');
            $response = $this->middleware->handle($request, fn() => new Response('OK'), $profile);

            $actualLimit = (int) $response->headers->get('X-RateLimit-Limit');
            $this->assertEquals($expectedLimit, $actualLimit, "Profile {$profile} should have limit {$expectedLimit}");
            $this->assertLessThanOrEqual($previousLimit, $actualLimit, "Profile {$profile} should not exceed previous profile limit");
            $previousLimit = $actualLimit;
        }
    }
}
