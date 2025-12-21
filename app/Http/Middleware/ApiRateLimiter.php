<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Rate Limiter Middleware - Configurable rate limiting for API endpoints.
 *
 * Features:
 * - User-based and IP-based limiting
 * - Different limits per user role
 * - Endpoint-specific limits
 * - Rate limit headers in response
 * - Bypass for trusted IPs
 */
class ApiRateLimiter
{
    public function __construct(protected RateLimiter $limiter)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $limitName = null): Response
    {
        if (!config('api.rate_limiting.enabled', true)) {
            return $next($request);
        }

        // Check if IP is in bypass list
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        // Determine the rate limit key
        $key = $this->resolveKey($request);

        // Get the appropriate limit
        $maxAttempts = $this->resolveLimit($request, $limitName);

        // Check the rate limit
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        // Record the attempt
        $this->limiter->hit($key, 60); // 1 minute window

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            $this->limiter->remaining($key, $maxAttempts),
            $this->limiter->availableIn($key)
        );
    }

    /**
     * Check if request should bypass rate limiting.
     */
    protected function shouldBypass(Request $request): bool
    {
        $bypassIps = config('api.rate_limiting.bypass_ips', []);

        return in_array($request->ip(), $bypassIps, true);
    }

    /**
     * Resolve the rate limit key for the request.
     */
    protected function resolveKey(Request $request): string
    {
        $prefix = 'api_rate_limit';

        // Use user ID if authenticated, otherwise use IP
        if ($user = $request->user()) {
            return "{$prefix}:user:{$user->id}";
        }

        return "{$prefix}:ip:{$request->ip()}";
    }

    /**
     * Resolve the rate limit for the request.
     */
    protected function resolveLimit(Request $request, ?string $limitName): int
    {
        $limits = config('api.rate_limiting.limits', []);
        $endpointLimits = config('api.rate_limiting.endpoints', []);

        // Check for endpoint-specific limit
        $routeName = $request->route()?->getName();
        if ($routeName) {
            // Check exact match
            if (isset($endpointLimits[$routeName])) {
                return $endpointLimits[$routeName];
            }

            // Check wildcard matches
            foreach ($endpointLimits as $pattern => $limit) {
                if (str_contains($pattern, '*')) {
                    $regex = str_replace('.', '\.', str_replace('*', '.*', $pattern));
                    if (preg_match("/^{$regex}$/", $routeName)) {
                        return $limit;
                    }
                }
            }
        }

        // Check for named limit
        if ($limitName && isset($limits[$limitName])) {
            return $limits[$limitName];
        }

        // Use role-based limit
        if ($user = $request->user()) {
            if ($user->isAdmin() && isset($limits['admin'])) {
                return $limits['admin'];
            }

            if (isset($limits['authenticated'])) {
                return $limits['authenticated'];
            }
        }

        // Default limit
        return $limits['default'] ?? 60;
    }

    /**
     * Build response for too many attempts.
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'success' => false,
            'error' => 'rate_limit_exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429, [
            config('api.rate_limiting.headers.limit', 'X-RateLimit-Limit') => $maxAttempts,
            config('api.rate_limiting.headers.remaining', 'X-RateLimit-Remaining') => 0,
            config('api.rate_limiting.headers.reset', 'X-RateLimit-Reset') => time() + $retryAfter,
            'Retry-After' => $retryAfter,
        ]);
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addRateLimitHeaders(Response $response, int $maxAttempts, int $remaining, int $retryAfter): Response
    {
        $headers = config('api.rate_limiting.headers', [
            'limit' => 'X-RateLimit-Limit',
            'remaining' => 'X-RateLimit-Remaining',
            'reset' => 'X-RateLimit-Reset',
        ]);

        $response->headers->set($headers['limit'], (string) $maxAttempts);
        $response->headers->set($headers['remaining'], (string) max(0, $remaining));
        $response->headers->set($headers['reset'], (string) (time() + $retryAfter));

        return $response;
    }
}
