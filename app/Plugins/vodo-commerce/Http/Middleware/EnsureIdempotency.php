<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use VodoCommerce\Models\IdempotencyKey;
use VodoCommerce\Models\Store;

/**
 * EnsureIdempotency - Prevents duplicate operations from network retries.
 *
 * Following Stripe's approach:
 * - Client provides Idempotency-Key header
 * - If key exists with completed status, return cached response
 * - If key is processing/failed, handle appropriately
 * - Creates new key for new requests
 *
 * Usage in routes:
 * Route::post('/checkout')->middleware(EnsureIdempotency::class);
 */
class EnsureIdempotency
{
    /**
     * Idempotency key header name.
     */
    public const IDEMPOTENCY_HEADER = 'Idempotency-Key';

    /**
     * Maximum age for idempotency keys in hours.
     */
    public const KEY_EXPIRY_HOURS = 24;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header(self::IDEMPOTENCY_HEADER);

        // Idempotency is optional - if no key provided, proceed normally
        if (!$idempotencyKey) {
            return $next($request);
        }

        // Validate key format (should be UUID or similar)
        if (strlen($idempotencyKey) < 10 || strlen($idempotencyKey) > 255) {
            return $this->errorResponse(
                'Invalid idempotency key format',
                'INVALID_IDEMPOTENCY_KEY',
                400
            );
        }

        // Get store context
        $storeId = $this->getStoreId($request);

        if (!$storeId) {
            return $this->errorResponse(
                'Store context required for idempotent requests',
                'STORE_REQUIRED',
                400
            );
        }

        // Generate request hash to detect different payloads with same key
        $requestPath = $request->path();
        $requestHash = $this->generateRequestHash($request);

        // Find existing key
        $existing = IdempotencyKey::valid()
            ->where('key', $idempotencyKey)
            ->where('store_id', $storeId)
            ->first();

        if ($existing) {
            return $this->handleExistingKey($existing, $requestPath, $requestHash, $request, $next);
        }

        // Create new idempotency key
        $key = IdempotencyKey::create([
            'key' => $idempotencyKey,
            'store_id' => $storeId,
            'request_path' => $requestPath,
            'request_hash' => $requestHash,
            'status' => IdempotencyKey::STATUS_PROCESSING,
            'expires_at' => now()->addHours(self::KEY_EXPIRY_HOURS),
        ]);

        // Store key in request for later use
        $request->attributes->set('idempotency_key', $key);

        try {
            $response = $next($request);

            // Record the response
            $this->recordResponse($key, $response);

            return $response;
        } catch (\Throwable $e) {
            // Record failure
            $key->markFailed(500, [
                'error' => $e->getMessage(),
                'code' => 'INTERNAL_ERROR',
            ]);

            throw $e;
        }
    }

    /**
     * Handle an existing idempotency key.
     */
    protected function handleExistingKey(
        IdempotencyKey $key,
        string $requestPath,
        string $requestHash,
        Request $request,
        Closure $next
    ): Response {
        // Check if request parameters match
        if (!$key->validateRequest($requestPath, $requestHash)) {
            Log::warning('Idempotency key reused with different parameters', [
                'key' => $key->key,
                'original_path' => $key->request_path,
                'new_path' => $requestPath,
            ]);

            return $this->errorResponse(
                'Idempotency key was used with different request parameters',
                'IDEMPOTENCY_KEY_MISMATCH',
                400
            );
        }

        // If completed, return cached response
        if ($key->isCompleted()) {
            Log::info('Returning cached idempotent response', [
                'key' => $key->key,
                'resource_type' => $key->resource_type,
                'resource_id' => $key->resource_id,
            ]);

            return new JsonResponse(
                $key->response_body,
                $key->response_code,
                ['Idempotent-Replayed' => 'true']
            );
        }

        // If can retry (failed or stuck), reset and proceed
        if ($key->canRetry()) {
            Log::info('Retrying idempotent request', [
                'key' => $key->key,
                'previous_status' => $key->status,
            ]);

            $key->markProcessing();
            $request->attributes->set('idempotency_key', $key);

            try {
                $response = $next($request);
                $this->recordResponse($key, $response);

                return $response;
            } catch (\Throwable $e) {
                $key->markFailed(500, [
                    'error' => $e->getMessage(),
                    'code' => 'INTERNAL_ERROR',
                ]);

                throw $e;
            }
        }

        // Still processing - return conflict
        return $this->errorResponse(
            'A request with this idempotency key is currently being processed',
            'IDEMPOTENCY_KEY_IN_USE',
            409
        );
    }

    /**
     * Record the response for the idempotency key.
     */
    protected function recordResponse(IdempotencyKey $key, Response $response): void
    {
        $statusCode = $response->getStatusCode();
        $body = [];

        if ($response instanceof JsonResponse) {
            $body = json_decode($response->getContent(), true) ?? [];
        }

        // Determine success/failure based on status code
        if ($statusCode >= 200 && $statusCode < 400) {
            // Extract resource info from response if available
            $resourceType = $body['resource_type'] ?? $body['type'] ?? null;
            $resourceId = $body['resource_id'] ?? $body['id'] ?? $body['data']['id'] ?? null;

            $key->markCompleted($statusCode, $body, $resourceType, $resourceId);
        } else {
            $key->markFailed($statusCode, $body);
        }
    }

    /**
     * Get store ID from request context.
     */
    protected function getStoreId(Request $request): ?int
    {
        // From route parameter
        $store = $request->route('store');

        if ($store instanceof Store) {
            return $store->id;
        }

        if (is_numeric($store)) {
            return (int) $store;
        }

        // From header
        if ($request->hasHeader('X-Store-Id')) {
            return (int) $request->header('X-Store-Id');
        }

        // From session
        if ($request->session()->has('current_store_id')) {
            return (int) $request->session()->get('current_store_id');
        }

        return null;
    }

    /**
     * Generate a hash of the request for comparison.
     */
    protected function generateRequestHash(Request $request): string
    {
        $data = [
            'method' => $request->method(),
            'body' => $request->all(),
        ];

        return hash('sha256', json_encode($data));
    }

    /**
     * Create an error response.
     */
    protected function errorResponse(string $message, string $code, int $status): JsonResponse
    {
        return new JsonResponse([
            'error' => $message,
            'code' => $code,
        ], $status);
    }
}
