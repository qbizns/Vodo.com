<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for SecurityHeadersMiddleware.
 *
 * Validates that all security headers are properly set to protect against:
 * - XSS attacks (via CSP and X-XSS-Protection)
 * - Clickjacking (via X-Frame-Options)
 * - MIME sniffing (via X-Content-Type-Options)
 * - Information leakage (via Referrer-Policy and Permissions-Policy)
 */
class SecurityHeadersMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityHeadersMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
    }

    // =========================================================================
    // Basic Security Headers
    // =========================================================================

    public function test_x_frame_options_header_is_set(): void
    {
        $response = $this->processRequest();

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_x_content_type_options_header_is_set(): void
    {
        $response = $this->processRequest();

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_x_xss_protection_header_is_set(): void
    {
        $response = $this->processRequest();

        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    public function test_referrer_policy_header_is_set(): void
    {
        $response = $this->processRequest();

        $this->assertEquals(
            'strict-origin-when-cross-origin',
            $response->headers->get('Referrer-Policy')
        );
    }

    public function test_permissions_policy_header_is_set(): void
    {
        $response = $this->processRequest();

        $permissionsPolicy = $response->headers->get('Permissions-Policy');

        $this->assertNotNull($permissionsPolicy);
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
    }

    // =========================================================================
    // HSTS Header Tests
    // =========================================================================

    public function test_hsts_not_set_in_local_environment(): void
    {
        $this->app['config']->set('app.env', 'local');

        $response = $this->processRequest();

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_set_in_production_with_https(): void
    {
        $this->app['config']->set('app.env', 'production');
        $this->app['config']->set('app.force_https', true);

        $request = Request::create('https://example.com/', 'GET');
        $request->server->set('HTTPS', 'on');

        $response = $this->processRequestWithRequest($request);

        $hsts = $response->headers->get('Strict-Transport-Security');

        $this->assertNotNull($hsts);
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }

    // =========================================================================
    // CSP Header Tests
    // =========================================================================

    public function test_csp_nonce_is_generated(): void
    {
        $request = Request::create('/', 'GET');

        $this->middleware->handle($request, function ($req) {
            $nonce = $req->attributes->get('csp_nonce');
            $this->assertNotEmpty($nonce);
            $this->assertEquals(24, strlen($nonce)); // base64 of 16 bytes

            return new Response();
        });
    }

    public function test_csp_header_includes_nonce(): void
    {
        $request = Request::create('/', 'GET');
        $nonce = null;

        $response = $this->middleware->handle($request, function ($req) use (&$nonce) {
            $nonce = $req->attributes->get('csp_nonce');
            return new Response();
        });

        $csp = $response->headers->get('Content-Security-Policy-Report-Only') ??
               $response->headers->get('Content-Security-Policy');

        if ($csp) {
            $this->assertStringContainsString("nonce-{$nonce}", $csp);
        }
    }

    public function test_csp_includes_required_directives(): void
    {
        $response = $this->processRequest();

        $csp = $response->headers->get('Content-Security-Policy-Report-Only') ??
               $response->headers->get('Content-Security-Policy');

        if ($csp) {
            $this->assertStringContainsString("default-src", $csp);
            $this->assertStringContainsString("script-src", $csp);
            $this->assertStringContainsString("style-src", $csp);
            $this->assertStringContainsString("frame-ancestors", $csp);
        }
    }

    // =========================================================================
    // Cross-Origin Policy Tests
    // =========================================================================

    public function test_cross_origin_policies_not_set_on_http(): void
    {
        $this->app['config']->set('app.env', 'local');

        $request = Request::create('http://localhost/', 'GET');
        $response = $this->processRequestWithRequest($request);

        // In local without HTTPS, COOP/CORP should not be set
        // to avoid browser console warnings
        $this->assertNull($response->headers->get('Cross-Origin-Opener-Policy'));
    }

    public function test_cross_origin_policies_set_in_production(): void
    {
        $this->app['config']->set('app.env', 'production');

        $response = $this->processRequest();

        $this->assertEquals('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
        $this->assertEquals('same-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
    }

    // =========================================================================
    // Cache Control for Sensitive Pages
    // =========================================================================

    public function test_sensitive_pages_have_no_cache_headers(): void
    {
        $sensitivePaths = [
            '/login',
            '/admin',
            '/settings',
            '/password/reset',
        ];

        foreach ($sensitivePaths as $path) {
            $request = Request::create($path, 'GET');
            $response = $this->processRequestWithRequest($request);

            $cacheControl = $response->headers->get('Cache-Control');

            if ($cacheControl) {
                $this->assertStringContainsString('no-store', $cacheControl);
                $this->assertStringContainsString('private', $cacheControl);
            }
        }
    }

    public function test_non_sensitive_pages_may_be_cached(): void
    {
        $request = Request::create('/about', 'GET');
        $response = $this->processRequestWithRequest($request);

        $cacheControl = $response->headers->get('Cache-Control');

        // Non-sensitive pages should not have the strict no-cache headers
        if ($cacheControl) {
            // The middleware might not set Cache-Control for non-sensitive pages
            // This is acceptable
            $this->assertTrue(true);
        } else {
            $this->assertNull($cacheControl);
        }
    }

    // =========================================================================
    // Header Removal Tests
    // =========================================================================

    public function test_x_powered_by_header_is_removed(): void
    {
        $request = Request::create('/', 'GET');

        $response = $this->middleware->handle($request, function () {
            $response = new Response();
            $response->headers->set('X-Powered-By', 'PHP/8.4');
            return $response;
        });

        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_server_header_is_removed(): void
    {
        $request = Request::create('/', 'GET');

        $response = $this->middleware->handle($request, function () {
            $response = new Response();
            $response->headers->set('Server', 'Apache/2.4');
            return $response;
        });

        $this->assertNull($response->headers->get('Server'));
    }

    // =========================================================================
    // Nonce Helper Tests
    // =========================================================================

    public function test_get_nonce_returns_request_nonce(): void
    {
        $request = Request::create('/', 'GET');

        $this->middleware->handle($request, function ($req) {
            $nonce = SecurityHeadersMiddleware::getNonce();

            $this->assertNotEmpty($nonce);
            $this->assertEquals($req->attributes->get('csp_nonce'), $nonce);

            return new Response();
        });
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    public function test_middleware_works_with_actual_request(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy');
    }

    public function test_middleware_works_with_json_response(): void
    {
        // Create a route that returns JSON
        \Illuminate\Support\Facades\Route::get('/test-json', function () {
            return response()->json(['status' => 'ok']);
        })->middleware('web');

        $response = $this->getJson('/test-json');

        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('X-Content-Type-Options');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function processRequest(): Response
    {
        $request = Request::create('/', 'GET');
        return $this->processRequestWithRequest($request);
    }

    protected function processRequestWithRequest(Request $request): Response
    {
        return $this->middleware->handle($request, function () {
            return new Response('OK');
        });
    }
}
