<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\Security\SecurityException;

/**
 * Rate limiting middleware with configurable limits per route/action.
 */
class RateLimitMiddleware
{
    /**
     * Default rate limit (requests per minute).
     */
    protected const DEFAULT_LIMIT = 60;

    /**
     * Default time window in seconds.
     */
    protected const DEFAULT_WINDOW = 60;

    /**
     * Predefined rate limit profiles.
     */
    protected array $profiles = [
        'api' => ['limit' => 60, 'window' => 60],
        'plugin_install' => ['limit' => 5, 'window' => 300],
        'plugin_activate' => ['limit' => 10, 'window' => 60],
        'entity_create' => ['limit' => 30, 'window' => 60],
        'upload' => ['limit' => 10, 'window' => 60],
        'auth' => ['limit' => 5, 'window' => 60],
        'search' => ['limit' => 30, 'window' => 60],
        'export' => ['limit' => 5, 'window' => 300],
        // Commerce rate limit profiles
        'storefront' => ['limit' => 120, 'window' => 60],     // Generous for browsing
        'cart' => ['limit' => 60, 'window' => 60],            // Cart operations
        'checkout' => ['limit' => 30, 'window' => 60],        // Checkout steps
        'checkout_order' => ['limit' => 5, 'window' => 60],   // Order placement (strict)
        'product_search' => ['limit' => 40, 'window' => 60],  // Product search
        'webhook' => ['limit' => 100, 'window' => 60],        // Webhooks (generous for retries)
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string $profile = 'api'): Response
    {
        $config = $this->getProfileConfig($profile);
        $key = $this->resolveRateLimitKey($request, $profile);
        
        $currentCount = (int) Cache::get($key, 0);
        
        if ($currentCount >= $config['limit']) {
            $this->logRateLimitExceeded($request, $profile, $key);
            
            $retryAfter = Cache::get("{$key}:expires", $config['window']);
            
            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $config['limit'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => time() + $retryAfter,
                'Retry-After' => $retryAfter,
            ]);
        }

        // Increment the counter
        if ($currentCount === 0) {
            Cache::put($key, 1, $config['window']);
            Cache::put("{$key}:expires", $config['window'], $config['window']);
        } else {
            Cache::increment($key);
        }

        $response = $next($request);

        // Add rate limit headers to response
        return $response->withHeaders([
            'X-RateLimit-Limit' => $config['limit'],
            'X-RateLimit-Remaining' => max(0, $config['limit'] - $currentCount - 1),
            'X-RateLimit-Reset' => time() + (Cache::get("{$key}:expires", $config['window'])),
        ]);
    }

    /**
     * Get configuration for a rate limit profile.
     */
    protected function getProfileConfig(string $profile): array
    {
        if (isset($this->profiles[$profile])) {
            return $this->profiles[$profile];
        }

        // Check for custom config
        $customConfig = config("ratelimit.profiles.{$profile}");
        if ($customConfig) {
            return $customConfig;
        }

        // Parse profile string (format: "limit:window")
        if (str_contains($profile, ':')) {
            [$limit, $window] = explode(':', $profile);
            return [
                'limit' => (int) $limit,
                'window' => (int) $window,
            ];
        }

        return [
            'limit' => self::DEFAULT_LIMIT,
            'window' => self::DEFAULT_WINDOW,
        ];
    }

    /**
     * Resolve the rate limit key for this request.
     */
    protected function resolveRateLimitKey(Request $request, string $profile): string
    {
        $identifier = $this->resolveIdentifier($request);
        $routeKey = $request->route()?->getName() ?? $request->path();
        
        return "rate_limit:{$profile}:{$identifier}:" . md5($routeKey);
    }

    /**
     * Resolve the identifier for rate limiting (user ID, API key, or IP).
     */
    protected function resolveIdentifier(Request $request): string
    {
        // Priority: User ID > API Key > IP Address
        if ($user = $request->user()) {
            return "user:{$user->id}";
        }

        if ($apiKey = $request->header('X-Api-Key')) {
            return "api:" . substr(hash('sha256', $apiKey), 0, 16);
        }

        return "ip:{$request->ip()}";
    }

    /**
     * Log rate limit exceeded event.
     */
    protected function logRateLimitExceeded(Request $request, string $profile, string $key): void
    {
        Log::warning('Rate limit exceeded', [
            'profile' => $profile,
            'key' => $key,
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'path' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
