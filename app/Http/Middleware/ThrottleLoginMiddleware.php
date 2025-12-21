<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttle Login Middleware
 *
 * Rate limits authentication attempts to prevent brute force attacks.
 * Uses a combination of IP address and email to track attempts.
 */
class ThrottleLoginMiddleware
{
    /**
     * Maximum login attempts before lockout
     */
    protected int $maxAttempts = 5;

    /**
     * Lockout duration in minutes
     */
    protected int $decayMinutes = 15;

    /**
     * The rate limiter instance
     */
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;

        // Allow configuration override
        $this->maxAttempts = (int) config('auth.throttle.max_attempts', 5);
        $this->decayMinutes = (int) config('auth.throttle.decay_minutes', 15);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only throttle POST requests (actual login attempts)
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $key = $this->throttleKey($request);

        // Check if too many attempts
        if ($this->limiter->tooManyAttempts($key, $this->maxAttempts)) {
            $this->logLockout($request, $key);
            return $this->buildLockoutResponse($request, $key);
        }

        // Process the request
        $response = $next($request);

        // If login failed (redirect back or 422 status), increment attempts
        if ($this->isFailedLogin($response, $request)) {
            $this->limiter->hit($key, $this->decayMinutes * 60);
            $this->logFailedAttempt($request);
        } else {
            // Clear attempts on successful login
            $this->limiter->clear($key);
        }

        return $response;
    }

    /**
     * Generate the throttle key for the request
     */
    protected function throttleKey(Request $request): string
    {
        $email = strtolower((string) $request->input('email', ''));
        $ip = $request->ip();

        // Combine email + IP to prevent both:
        // 1. Same IP trying multiple emails
        // 2. Same email from multiple IPs (credential stuffing)
        return 'login_throttle:' . sha1($email . '|' . $ip);
    }

    /**
     * Determine if the response indicates a failed login
     */
    protected function isFailedLogin(Response $response, Request $request): bool
    {
        // Check for redirect back (typical failed login)
        if ($response->isRedirection()) {
            $targetUrl = $response->headers->get('Location', '');
            $currentUrl = $request->url();

            // If redirecting back to the same page (or login page), it's a failure
            if (str_contains($targetUrl, 'login') || $targetUrl === $currentUrl) {
                return true;
            }
        }

        // Check for validation error response (422)
        if ($response->getStatusCode() === 422) {
            return true;
        }

        // Check for unauthorized response (401)
        if ($response->getStatusCode() === 401) {
            return true;
        }

        return false;
    }

    /**
     * Build the lockout response
     */
    protected function buildLockoutResponse(Request $request, string $key): Response
    {
        $seconds = $this->limiter->availableIn($key);
        $minutes = ceil($seconds / 60);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => 'Too many login attempts. Please try again later.',
                'code' => 'TOO_MANY_ATTEMPTS',
                'retry_after' => $seconds,
            ], 429)->withHeaders([
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => $this->maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($seconds)->timestamp,
            ]);
        }

        // For web requests, redirect back with error
        return redirect()
            ->back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => "Too many login attempts. Please try again in {$minutes} minute(s).",
            ]);
    }

    /**
     * Log a failed login attempt
     */
    protected function logFailedAttempt(Request $request): void
    {
        Log::warning('Failed login attempt', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log an account lockout
     */
    protected function logLockout(Request $request, string $key): void
    {
        $seconds = $this->limiter->availableIn($key);

        Log::warning('Account locked out due to too many login attempts', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'lockout_remaining_seconds' => $seconds,
            'max_attempts' => $this->maxAttempts,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
