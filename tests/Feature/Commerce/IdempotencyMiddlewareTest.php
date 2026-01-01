<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use VodoCommerce\Http\Middleware\EnsureIdempotency;
use VodoCommerce\Models\IdempotencyKey;

class IdempotencyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected EnsureIdempotency $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureIdempotency();
        Cache::flush();
    }

    public function test_allows_request_without_idempotency_key(): void
    {
        $request = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);

        $response = $this->middleware->handle($request, fn() => new Response('Order created', 201));

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Order created', $response->getContent());
    }

    public function test_processes_new_idempotency_key(): void
    {
        $key = 'test-idempotency-key-' . uniqid();
        $request = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request->headers->set('Idempotency-Key', $key);

        $responseBody = json_encode(['order_id' => 123]);
        $response = $this->middleware->handle($request, fn() => new Response($responseBody, 201, ['Content-Type' => 'application/json']));

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($responseBody, $response->getContent());

        // Verify the idempotency key was stored
        $storedKey = IdempotencyKey::where('key', $key)->first();
        $this->assertNotNull($storedKey);
        $this->assertEquals('completed', $storedKey->status);
    }

    public function test_returns_cached_response_for_duplicate_request(): void
    {
        $key = 'duplicate-key-' . uniqid();
        $originalResponse = json_encode(['order_id' => 456]);

        // First request
        $request1 = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request1->headers->set('Idempotency-Key', $key);
        $this->middleware->handle($request1, fn() => new Response($originalResponse, 201, ['Content-Type' => 'application/json']));

        // Second request with same key
        $request2 = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request2->headers->set('Idempotency-Key', $key);
        $response = $this->middleware->handle($request2, function () {
            // This should not be called
            $this->fail('Handler should not be called for duplicate idempotency key');
            return new Response('New order', 201);
        });

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($originalResponse, $response->getContent());
        $this->assertEquals('true', $response->headers->get('X-Idempotent-Replayed'));
    }

    public function test_rejects_request_if_key_in_processing_state(): void
    {
        $key = 'processing-key-' . uniqid();
        $requestHash = hash('sha256', 'POST:/checkout/place-order:{"cart_id":1}');

        // Create a key in processing state
        IdempotencyKey::create([
            'key' => $key,
            'request_hash' => $requestHash,
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        $request = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request->headers->set('Idempotency-Key', $key);

        $response = $this->middleware->handle($request, fn() => new Response('OK', 200));

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertStringContainsString('in progress', strtolower($response->getContent()));
    }

    public function test_allows_retry_for_failed_request(): void
    {
        $key = 'failed-key-' . uniqid();
        $requestHash = hash('sha256', 'POST:/checkout/place-order:' . json_encode(['cart_id' => 2]));

        // Create a key in failed state
        IdempotencyKey::create([
            'key' => $key,
            'request_hash' => $requestHash,
            'status' => 'failed',
            'expires_at' => now()->addHours(24),
        ]);

        $request = Request::create('/checkout/place-order', 'POST', ['cart_id' => 2]);
        $request->headers->set('Idempotency-Key', $key);

        $response = $this->middleware->handle($request, fn() => new Response(json_encode(['order_id' => 789]), 201));

        $this->assertEquals(201, $response->getStatusCode());

        // Verify the key was updated to completed
        $updatedKey = IdempotencyKey::where('key', $key)->first();
        $this->assertEquals('completed', $updatedKey->status);
    }

    public function test_rejects_mismatched_request_hash(): void
    {
        $key = 'mismatch-key-' . uniqid();

        // First request
        $request1 = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1, 'items' => [1, 2, 3]]);
        $request1->headers->set('Idempotency-Key', $key);
        $this->middleware->handle($request1, fn() => new Response('Created', 201));

        // Second request with same key but different payload
        $request2 = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1, 'items' => [4, 5, 6]]);
        $request2->headers->set('Idempotency-Key', $key);

        $response = $this->middleware->handle($request2, fn() => new Response('OK', 200));

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('different request', strtolower($response->getContent()));
    }

    public function test_marks_key_as_failed_on_exception(): void
    {
        $key = 'exception-key-' . uniqid();
        $request = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request->headers->set('Idempotency-Key', $key);

        try {
            $this->middleware->handle($request, function () {
                throw new \Exception('Payment failed');
            });
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Payment failed', $e->getMessage());
        }

        // Verify the key was marked as failed
        $storedKey = IdempotencyKey::where('key', $key)->first();
        $this->assertNotNull($storedKey);
        $this->assertEquals('failed', $storedKey->status);
    }

    public function test_respects_expiration_time(): void
    {
        $key = 'expired-key-' . uniqid();
        $requestHash = hash('sha256', 'POST:/checkout/place-order:' . json_encode(['cart_id' => 1]));

        // Create an expired key
        IdempotencyKey::create([
            'key' => $key,
            'request_hash' => $requestHash,
            'status' => 'completed',
            'response_code' => 201,
            'response_body' => json_encode(['order_id' => 999]),
            'expires_at' => now()->subHour(), // Expired 1 hour ago
        ]);

        $request = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request->headers->set('Idempotency-Key', $key);

        $newResponse = json_encode(['order_id' => 1000]);
        $response = $this->middleware->handle($request, fn() => new Response($newResponse, 201));

        // Should process as new request since the key expired
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($newResponse, $response->getContent());
    }

    public function test_validates_idempotency_key_format(): void
    {
        $request = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request->headers->set('Idempotency-Key', ''); // Empty key

        $response = $this->middleware->handle($request, fn() => new Response('OK', 200));

        // Should process normally since empty key is treated as no key
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_handles_json_response_correctly(): void
    {
        $key = 'json-key-' . uniqid();
        $responseData = ['order_id' => 123, 'status' => 'paid', 'items' => [1, 2, 3]];

        $request = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request->headers->set('Idempotency-Key', $key);

        $this->middleware->handle($request, fn() => new Response(json_encode($responseData), 201, ['Content-Type' => 'application/json']));

        // Replay
        $request2 = Request::create('/checkout/place-order', 'POST', ['cart_id' => 1]);
        $request2->headers->set('Idempotency-Key', $key);
        $response = $this->middleware->handle($request2, fn() => new Response('Should not call', 500));

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(json_encode($responseData), $response->getContent());
    }
}
