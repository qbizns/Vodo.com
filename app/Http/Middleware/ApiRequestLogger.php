<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiEndpoint;
use App\Models\ApiRequestLog;
use App\Models\ApiKey;

/**
 * API Request Logging Middleware
 * 
 * Logs all API requests for analytics and debugging.
 * Also handles request validation if endpoint has rules.
 */
class ApiRequestLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Find matching endpoint
        $endpoint = $this->findEndpoint($request);
        
        // Validate request if endpoint has rules
        if ($endpoint && $endpoint->request_rules) {
            $validation = $this->validateRequest($request, $endpoint);
            if ($validation !== true) {
                return $validation;
            }
        }

        // Store endpoint for later use
        $request->attributes->set('api_endpoint', $endpoint);

        // Process request
        $response = $next($request);

        // Log request (async if configured)
        if (config('api-endpoints.logging.enabled', true)) {
            $this->logRequest($request, $response, $endpoint, $startTime);
        }

        return $response;
    }

    /**
     * Find matching endpoint
     */
    protected function findEndpoint(Request $request): ?ApiEndpoint
    {
        $path = $request->path();
        
        // Parse path to extract version and endpoint path
        if (preg_match('#^api/(v\d+)/(.+)$#', $path, $matches)) {
            $version = $matches[1];
            $endpointPath = '/' . $matches[2];
            
            return ApiEndpoint::findByRoute($request->method(), $endpointPath, $version);
        }
        
        // Try without version
        if (preg_match('#^api/(.+)$#', $path, $matches)) {
            $endpointPath = '/' . $matches[1];
            return ApiEndpoint::findByRoute($request->method(), $endpointPath, 'v1');
        }

        return null;
    }

    /**
     * Validate request against endpoint rules
     */
    protected function validateRequest(Request $request, ApiEndpoint $endpoint): Response|bool
    {
        $rules = $endpoint->getValidationRules();
        $messages = $endpoint->getValidationMessages();

        if (empty($rules)) {
            return true;
        }

        $validator = validator($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        return true;
    }

    /**
     * Log the request
     */
    protected function logRequest(
        Request $request,
        Response $response,
        ?ApiEndpoint $endpoint,
        float $startTime
    ): void {
        // Skip if logging disabled for this endpoint
        if ($endpoint && ($endpoint->meta['skip_logging'] ?? false)) {
            return;
        }

        // Get API key if present
        $apiKey = $request->attributes->get('api_key');
        $userId = auth()->id() ?? $apiKey?->user_id;

        // Determine if we should log (sampling)
        $sampleRate = config('api-endpoints.logging.sample_rate', 100);
        if ($sampleRate < 100 && mt_rand(1, 100) > $sampleRate) {
            return;
        }

        // Async logging if configured
        if (config('api-endpoints.logging.async', false)) {
            dispatch(function () use ($request, $response, $endpoint, $apiKey, $userId, $startTime) {
                ApiRequestLog::logRequest($request, $response, $endpoint, $apiKey, $userId, $startTime);
            })->afterResponse();
        } else {
            ApiRequestLog::logRequest($request, $response, $endpoint, $apiKey, $userId, $startTime);
        }
    }
}
