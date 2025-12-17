<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\InputSanitizationMiddleware;
use App\Http\Middleware\PluginCsrfMiddleware;
use App\Exceptions\Security\SecurityException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class MiddlewareTest extends TestCase
{
    // =========================================================================
    // Rate Limit Middleware Tests
    // =========================================================================

    public function test_rate_limit_allows_requests_under_limit(): void
    {
        $middleware = new RateLimitMiddleware();
        $request = Request::create('/api/test', 'GET');
        
        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'api');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }

    public function test_rate_limit_blocks_when_exceeded(): void
    {
        Cache::flush();
        
        $middleware = new RateLimitMiddleware();
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        // Simulate hitting the limit
        $key = 'rate_limit:strict:ip:192.168.1.100:' . md5('/api/test');
        Cache::put($key, 100, 300); // Set count way over limit
        Cache::put("{$key}:expires", 300, 300);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'strict');

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function test_rate_limit_uses_profile_settings(): void
    {
        $middleware = new RateLimitMiddleware();
        $request = Request::create('/api/upload', 'POST');

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'upload');

        // Upload profile has limit of 10
        $this->assertEquals('10', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_rate_limit_parses_custom_profile(): void
    {
        $middleware = new RateLimitMiddleware();
        $request = Request::create('/api/custom', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, '100:120'); // Custom: 100 requests per 120 seconds

        $this->assertEquals('100', $response->headers->get('X-RateLimit-Limit'));
    }

    // =========================================================================
    // Input Sanitization Middleware Tests
    // =========================================================================

    public function test_sanitization_removes_null_bytes(): void
    {
        $middleware = new InputSanitizationMiddleware();
        $request = Request::create('/test', 'POST', [
            'name' => "Hello\0World",
        ]);

        $middleware->handle($request, function ($req) {
            $this->assertStringNotContainsString("\0", $req->input('name'));
            return new Response('OK');
        });
    }

    public function test_sanitization_normalizes_line_endings(): void
    {
        $middleware = new InputSanitizationMiddleware();
        $request = Request::create('/test', 'POST', [
            'text' => "Line1\r\nLine2\rLine3",
        ]);

        $middleware->handle($request, function ($req) {
            $text = $req->input('text');
            $this->assertStringNotContainsString("\r\n", $text);
            $this->assertStringNotContainsString("\r", $text);
            $this->assertStringContainsString("\n", $text);
            return new Response('OK');
        });
    }

    public function test_sanitization_excludes_password_fields(): void
    {
        $middleware = new InputSanitizationMiddleware();
        $password = "Test\0Password<script>";
        $request = Request::create('/test', 'POST', [
            'password' => $password,
        ]);

        $middleware->handle($request, function ($req) use ($password) {
            // Password should remain unchanged
            $this->assertEquals($password, $req->input('password'));
            return new Response('OK');
        });
    }

    public function test_sanitization_blocks_path_traversal(): void
    {
        $middleware = new InputSanitizationMiddleware();
        $request = Request::create('/test', 'POST', [
            'file' => '../../../etc/passwd',
        ]);

        $this->expectException(SecurityException::class);

        $middleware->handle($request, function () {
            return new Response('OK');
        });
    }

    public function test_sanitization_blocks_php_injection(): void
    {
        $middleware = new InputSanitizationMiddleware();
        $request = Request::create('/test', 'POST', [
            'input' => '<?php eval($_GET["cmd"]); ?>',
        ]);

        $this->expectException(SecurityException::class);

        $middleware->handle($request, function () {
            return new Response('OK');
        });
    }

    public function test_sanitization_allows_safe_content(): void
    {
        $middleware = new InputSanitizationMiddleware();
        $request = Request::create('/test', 'POST', [
            'title' => 'Hello World',
            'description' => 'A normal description with numbers 123.',
            'email' => 'test@example.com',
        ]);

        $response = $middleware->handle($request, function ($req) {
            $this->assertEquals('Hello World', $req->input('title'));
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    // =========================================================================
    // CSRF Middleware Tests
    // =========================================================================

    public function test_csrf_skips_safe_methods(): void
    {
        $middleware = new PluginCsrfMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'plugin.activate');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_csrf_validates_nonce_for_protected_actions(): void
    {
        $middleware = new PluginCsrfMiddleware();
        
        // Generate a valid nonce
        $nonce = PluginCsrfMiddleware::createNonce('plugin.activate');
        
        $request = Request::create('/test', 'POST');
        $request->headers->set('X-Plugin-Nonce', $nonce);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'plugin.activate');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_csrf_rejects_invalid_nonce(): void
    {
        $middleware = new PluginCsrfMiddleware();
        
        $request = Request::create('/test', 'POST');
        $request->headers->set('X-Plugin-Nonce', 'invalid-nonce');

        $this->expectException(SecurityException::class);

        $middleware->handle($request, function () {
            return new Response('OK');
        }, 'plugin.activate');
    }

    public function test_csrf_rejects_missing_nonce(): void
    {
        $middleware = new PluginCsrfMiddleware();
        
        $request = Request::create('/test', 'POST');

        $this->expectException(SecurityException::class);

        $middleware->handle($request, function () {
            return new Response('OK');
        }, 'plugin.activate');
    }

    public function test_csrf_nonce_can_be_passed_in_request(): void
    {
        $middleware = new PluginCsrfMiddleware();
        
        $nonce = PluginCsrfMiddleware::createNonce('plugin.install');
        
        $request = Request::create('/test', 'POST', [
            '_plugin_nonce' => $nonce,
        ]);

        $response = $middleware->handle($request, function () {
            return new Response('OK');
        }, 'plugin.install');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_csrf_protects_entity_operations(): void
    {
        $middleware = new PluginCsrfMiddleware();
        $protectedActions = $middleware->getProtectedActions();

        $this->assertContains('entity.create', $protectedActions);
        $this->assertContains('entity.update', $protectedActions);
        $this->assertContains('entity.delete', $protectedActions);
    }

    public function test_csrf_create_nonce_is_deterministic_within_time_window(): void
    {
        $nonce1 = PluginCsrfMiddleware::createNonce('test_action');
        $nonce2 = PluginCsrfMiddleware::createNonce('test_action');

        $this->assertEquals($nonce1, $nonce2);
    }

    public function test_csrf_different_actions_produce_different_nonces(): void
    {
        $nonce1 = PluginCsrfMiddleware::createNonce('action_one');
        $nonce2 = PluginCsrfMiddleware::createNonce('action_two');

        $this->assertNotEquals($nonce1, $nonce2);
    }
}
