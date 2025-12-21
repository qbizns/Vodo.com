<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Version Middleware - Handles API versioning via headers or URL prefix.
 *
 * Supports:
 * - URL prefix versioning (default): /api/v1/..., /api/v2/...
 * - Header versioning: Accept: application/vnd.vodo.v1+json
 * - Query parameter: ?api-version=1
 *
 * @example
 * Route::middleware('api.version:1,2')->group(function() { ... });
 */
class ApiVersionMiddleware
{
    /**
     * Currently supported API versions.
     */
    public const SUPPORTED_VERSIONS = ['1', '2'];

    /**
     * Default API version when not specified.
     */
    public const DEFAULT_VERSION = '1';

    /**
     * Latest stable API version.
     */
    public const LATEST_VERSION = '1';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$allowedVersions): Response
    {
        // Determine version from request
        $version = $this->resolveVersion($request);

        // Validate version is supported
        if (!in_array($version, self::SUPPORTED_VERSIONS, true)) {
            return response()->json([
                'success' => false,
                'error' => 'unsupported_api_version',
                'message' => "API version '{$version}' is not supported.",
                'supported_versions' => self::SUPPORTED_VERSIONS,
                'latest_version' => self::LATEST_VERSION,
            ], 400);
        }

        // Check if version is allowed for this route
        if (!empty($allowedVersions) && !in_array($version, $allowedVersions, true)) {
            return response()->json([
                'success' => false,
                'error' => 'version_not_available',
                'message' => "This endpoint is not available in API version {$version}.",
                'available_versions' => $allowedVersions,
            ], 400);
        }

        // Add version info to request for controllers
        $request->merge(['_api_version' => $version]);
        $request->headers->set('X-API-Version', $version);

        $response = $next($request);

        // Add version headers to response
        if ($response instanceof Response) {
            $response->headers->set('X-API-Version', $version);
            $response->headers->set('X-API-Supported-Versions', implode(', ', self::SUPPORTED_VERSIONS));
            $response->headers->set('X-API-Latest-Version', self::LATEST_VERSION);

            // Add deprecation warning for older versions
            if ($version !== self::LATEST_VERSION) {
                $response->headers->set('X-API-Deprecation-Warning',
                    "API v{$version} is deprecated. Please migrate to v" . self::LATEST_VERSION);
            }
        }

        return $response;
    }

    /**
     * Resolve API version from request.
     */
    protected function resolveVersion(Request $request): string
    {
        // 1. Check URL path for version prefix
        if (preg_match('/\/api\/v(\d+)\//', $request->getPathInfo(), $matches)) {
            return $matches[1];
        }

        // 2. Check Accept header for versioned media type
        $accept = $request->header('Accept', '');
        if (preg_match('/application\/vnd\.vodo\.v(\d+)\+json/', $accept, $matches)) {
            return $matches[1];
        }

        // 3. Check X-API-Version header
        if ($apiVersion = $request->header('X-API-Version')) {
            return (string) $apiVersion;
        }

        // 4. Check query parameter
        if ($queryVersion = $request->query('api-version')) {
            return (string) $queryVersion;
        }

        // 5. Use default version
        return self::DEFAULT_VERSION;
    }
}
