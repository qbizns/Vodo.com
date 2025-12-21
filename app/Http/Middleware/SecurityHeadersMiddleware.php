<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 *
 * Adds essential HTTP security headers to all responses to protect against
 * common web vulnerabilities including XSS, clickjacking, and MIME sniffing.
 */
class SecurityHeadersMiddleware
{
    /**
     * Headers to remove (potentially unsafe or leaking info)
     */
    protected array $removeHeaders = [
        'X-Powered-By',
        'Server',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate CSP nonce for this request
        $nonce = $this->generateCspNonce();
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        // Remove potentially unsafe headers
        foreach ($this->removeHeaders as $header) {
            $response->headers->remove($header);
        }

        // Add security headers
        $this->addSecurityHeaders($response, $request);

        return $response;
    }

    /**
     * Generate a cryptographically secure nonce for CSP.
     */
    protected function generateCspNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Add security headers to the response
     */
    protected function addSecurityHeaders(Response $response, Request $request): void
    {
        // Prevent clickjacking attacks
        // DENY = page cannot be displayed in a frame
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        // Browsers should strictly follow Content-Type header
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS filter in browsers (legacy, but still useful)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information sent with requests
        // strict-origin-when-cross-origin: Send full URL for same-origin, only origin for cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features/APIs that can be used
        $response->headers->set('Permissions-Policy', $this->getPermissionsPolicy());

        // Force HTTPS (only in production with HTTPS enabled)
        if ($this->shouldEnableHsts($request)) {
            // max-age=31536000 = 1 year, includeSubDomains covers all subdomains
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Content Security Policy
        if ($csp = $this->getContentSecurityPolicy($request)) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Cross-Origin policies for enhanced isolation
        // Only send COOP/CORP headers over HTTPS or in production to avoid browser warnings
        if ($this->shouldEnableCrossOriginPolicies($request)) {
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
            $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        }

        // Cache control for sensitive pages
        if ($this->isSensitivePage($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }

    /**
     * Determine if HSTS should be enabled
     */
    protected function shouldEnableHsts(Request $request): bool
    {
        // Only enable HSTS in production and when using HTTPS
        return app()->isProduction()
            && $request->secure()
            && config('app.force_https', true);
    }

    /**
     * Determine if Cross-Origin policies (COOP/CORP) should be enabled
     * 
     * These headers are ignored by browsers on non-secure origins (HTTP),
     * causing console warnings. Only send them when appropriate.
     */
    protected function shouldEnableCrossOriginPolicies(Request $request): bool
    {
        // Enable in production or when using HTTPS
        return app()->isProduction() || $request->secure();
    }

    /**
     * Get Permissions-Policy header value
     */
    protected function getPermissionsPolicy(): string
    {
        $policies = [
            'accelerometer' => '()',
            'camera' => '()',
            'geolocation' => '()',
            'gyroscope' => '()',
            'magnetometer' => '()',
            'microphone' => '()',
            'payment' => '()',
            'usb' => '()',
            'interest-cohort' => '()', // Opt out of FLoC
        ];

        return collect($policies)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode(', ');
    }

    /**
     * Get Content Security Policy header value
     */
    protected function getContentSecurityPolicy(Request $request): ?string
    {
        // Use report-only mode in development for debugging
        $isProduction = app()->isProduction();

        // Get the CSP nonce for this request
        $nonce = $request->attributes->get('csp_nonce', '');

        // Define CSP directives
        $directives = [
            // Default fallback for all resource types
            "default-src" => "'self'",

            // Scripts: Use nonce-based CSP for better XSS protection
            // In production: use 'strict-dynamic' for better security (disables 'self')
            // In development: keep 'self' + nonce + 'unsafe-eval' for easier debugging
            "script-src" => $isProduction
                ? "'self' 'nonce-{$nonce}' 'strict-dynamic'"
                : "'self' 'nonce-{$nonce}' 'unsafe-eval'",

            // Styles: self + inline for dynamic styles (nonce for inline styles is complex)
            "style-src" => "'self' 'unsafe-inline' https://fonts.googleapis.com",

            // Images: self + data URIs + HTTPS
            "img-src" => "'self' data: https: blob:",

            // Fonts: self + Google Fonts
            "font-src" => "'self' https://fonts.gstatic.com data:",

            // Connections: self + localhost for dev
            "connect-src" => $isProduction
                ? "'self'"
                : "'self' ws://localhost:* http://localhost:*",

            // Forms: self only
            "form-action" => "'self'",

            // Frame ancestors: none (similar to X-Frame-Options DENY)
            "frame-ancestors" => "'none'",

            // Base URI: self only
            "base-uri" => "'self'",

            // Object/embed: none
            "object-src" => "'none'",

            // Upgrade insecure requests in production
            "upgrade-insecure-requests" => $isProduction ? "" : null,
        ];

        // Remove null entries and build the header
        $policy = collect($directives)
            ->filter(fn($value) => $value !== null)
            ->map(fn($value, $key) => $value === "" ? $key : "{$key} {$value}")
            ->implode('; ');

        return $policy ?: null;
    }

    /**
     * Get CSP nonce for use in Blade templates.
     * 
     * Usage in Blade: <script nonce="{{ csp_nonce() }}">...</script>
     */
    public static function getNonce(): string
    {
        return request()->attributes->get('csp_nonce', '');
    }

    /**
     * Check if the current request is for a sensitive page
     */
    protected function isSensitivePage(Request $request): bool
    {
        $sensitivePaths = [
            'login',
            'register',
            'password',
            'admin',
            'owner',
            'console',
            'settings',
            'api/v1/auth',
        ];

        $path = $request->path();

        foreach ($sensitivePaths as $sensitivePath) {
            if (str_starts_with($path, $sensitivePath)) {
                return true;
            }
        }

        return false;
    }
}
