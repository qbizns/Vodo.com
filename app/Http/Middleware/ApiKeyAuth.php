<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;
use App\Models\ApiEndpoint;
use App\Services\Api\ApiRegistry;

/**
 * API Key Authentication Middleware
 * 
 * Validates API key from header or query parameter.
 * Supports optional secret-based signing for enhanced security.
 */
class ApiKeyAuth
{
    protected ApiRegistry $registry;

    public function __construct(ApiRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->extractApiKey($request);
        
        if (!$key) {
            return $this->unauthorized('API key required');
        }

        $apiKey = ApiKey::findActiveByKey($key);

        if (!$apiKey) {
            return $this->unauthorized('Invalid API key');
        }

        // Check IP restriction
        if (!$apiKey->isIpAllowed($request->ip())) {
            return $this->forbidden('IP address not allowed');
        }

        // Check if secret signing is required
        if ($apiKey->secret_hash && config('api-endpoints.require_signed_requests', false)) {
            if (!$this->verifySignature($request, $apiKey)) {
                return $this->unauthorized('Invalid signature');
            }
        }

        // Find matching endpoint and check access
        $endpoint = $this->findEndpoint($request);
        if ($endpoint && !$apiKey->canAccessEndpoint($endpoint)) {
            return $this->forbidden('Access denied to this endpoint');
        }

        // Record usage
        $apiKey->recordUsage();

        // Store API key in request for later use
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_key_id', $apiKey->id);

        // Set user if API key is linked to a user
        if ($apiKey->user_id) {
            auth()->loginUsingId($apiKey->user_id);
        }

        return $next($request);
    }

    /**
     * Extract API key from request
     */
    protected function extractApiKey(Request $request): ?string
    {
        // Check header first
        $headerNames = config('api-endpoints.api_key_header', ['X-API-Key', 'Authorization']);
        
        foreach ((array) $headerNames as $header) {
            $value = $request->header($header);
            
            if ($value) {
                // Handle "Bearer pk_xxx" format
                if (str_starts_with($value, 'Bearer ')) {
                    return substr($value, 7);
                }
                // Handle "ApiKey pk_xxx" format
                if (str_starts_with($value, 'ApiKey ')) {
                    return substr($value, 7);
                }
                return $value;
            }
        }

        // Check query parameter
        $paramName = config('api-endpoints.api_key_param', 'api_key');
        if ($key = $request->query($paramName)) {
            return $key;
        }

        return null;
    }

    /**
     * Verify request signature
     *
     * IMPORTANT: This method safely retrieves the request body content.
     * Laravel's Request object (via Symfony HttpFoundation) internally caches
     * the content, so calling getContent() multiple times is safe.
     */
    protected function verifySignature(Request $request, ApiKey $apiKey): bool
    {
        $providedSignature = $request->header('X-API-Signature');

        if (!$providedSignature) {
            return false;
        }

        // Build signature payload
        $timestamp = $request->header('X-API-Timestamp');
        if (!$timestamp) {
            return false;
        }

        // Check timestamp is within acceptable window (5 minutes)
        $timestampAge = abs(time() - (int)$timestamp);
        if ($timestampAge > 300) {
            return false;
        }

        // Get request body content safely
        // Laravel/Symfony caches the content internally after first read
        $content = $this->getRequestBodySafely($request);

        // Build signature string
        $signaturePayload = implode("\n", [
            $request->method(),
            $request->path(),
            $timestamp,
            $content,
        ]);

        // Verify HMAC using timing-safe comparison
        $expectedSignature = hash_hmac('sha256', $signaturePayload, $apiKey->secret_hash);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Get request body content safely.
     *
     * This method ensures the request body can be read multiple times by
     * caching it in request attributes. While Symfony's getContent() already
     * caches internally, this provides an additional safety layer and makes
     * the caching behavior explicit.
     */
    protected function getRequestBodySafely(Request $request): string
    {
        // Check if we already cached the body in request attributes
        $cacheKey = '_api_signature_body';
        if ($request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        // Get content - Symfony caches this internally after first read
        $content = $request->getContent();

        // Store in request attributes for explicit caching
        $request->attributes->set($cacheKey, $content);

        return $content;
    }

    /**
     * Find matching endpoint for request
     */
    protected function findEndpoint(Request $request): ?ApiEndpoint
    {
        // Extract version from path
        $path = $request->path();
        preg_match('#^api/(v\d+)/(.+)$#', $path, $matches);
        
        $version = $matches[1] ?? 'v1';
        $endpointPath = isset($matches[2]) ? '/' . $matches[2] : '/' . $path;

        return ApiEndpoint::findByRoute($request->method(), $endpointPath, $version);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'UNAUTHORIZED',
        ], 401);
    }

    /**
     * Return forbidden response
     */
    protected function forbidden(string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'FORBIDDEN',
        ], 403);
    }
}
