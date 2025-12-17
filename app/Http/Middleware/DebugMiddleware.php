<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Debugging\TracingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DebugMiddleware - Automatic request tracing.
 * 
 * Enable via query parameter: ?_debug=1
 * Or header: X-Debug: 1
 * Or for specific users via config
 */
class DebugMiddleware
{
    public function __construct(
        protected TracingService $tracer
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if debug mode should be enabled
        if (!$this->shouldEnableDebug($request)) {
            return $next($request);
        }

        // Enable tracing
        $this->tracer->enable();

        // Start request trace
        $traceId = $this->tracer->startTrace(
            'request',
            $request->method() . ' ' . $request->path(),
            [
                'method' => $request->method(),
                'path' => $request->path(),
                'query' => $request->query(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            [
                'url' => $request->fullUrl(),
                'route' => $request->route()?->getName(),
            ]
        );

        try {
            $response = $next($request);

            // End request trace
            $this->tracer->endTrace($traceId, [
                'status' => $response->getStatusCode(),
            ]);

            // Attach debug info to response if requested
            if ($this->shouldAttachDebugInfo($request)) {
                $response = $this->attachDebugInfo($response);
            }

            return $response;

        } catch (\Throwable $e) {
            $this->tracer->endTrace($traceId, null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if debug mode should be enabled.
     */
    protected function shouldEnableDebug(Request $request): bool
    {
        // Check query parameter
        if ($request->has('_debug') && $request->get('_debug')) {
            return $this->isDebugAllowed($request);
        }

        // Check header
        if ($request->header('X-Debug')) {
            return $this->isDebugAllowed($request);
        }

        // Check if user has debug enabled
        if ($request->user() && method_exists($request->user(), 'hasDebugEnabled')) {
            return $request->user()->hasDebugEnabled();
        }

        return false;
    }

    /**
     * Check if debug is allowed for this request.
     */
    protected function isDebugAllowed(Request $request): bool
    {
        // In production, only allow for specific users or IPs
        if (app()->environment('production')) {
            // Check if user has debug permission
            if ($request->user() && method_exists($request->user(), 'can')) {
                return $request->user()->can('debug');
            }

            // Check IP whitelist
            $allowedIps = config('debugging.allowed_ips', []);
            if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
                return false;
            }

            return false;
        }

        return true;
    }

    /**
     * Check if debug info should be attached to response.
     */
    protected function shouldAttachDebugInfo(Request $request): bool
    {
        return $request->has('_debug') && $request->get('_debug') === 'full';
    }

    /**
     * Attach debug info to response.
     */
    protected function attachDebugInfo(Response $response): Response
    {
        $debugInfo = $this->tracer->export();

        // For JSON responses, add debug info
        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
            if (is_array($content)) {
                $content['_debug'] = $debugInfo;
                $response->setContent(json_encode($content));
            }
        } else {
            // Add as response header (summarized)
            $summary = $this->tracer->getSummary();
            $response->headers->set('X-Debug-Request-Id', $summary['request_id'] ?? '');
            $response->headers->set('X-Debug-Duration-Ms', (string) ($summary['total_duration_ms'] ?? 0));
            $response->headers->set('X-Debug-Queries', (string) ($summary['total_queries'] ?? 0));
            $response->headers->set('X-Debug-Errors', (string) ($summary['errors'] ?? 0));
        }

        return $response;
    }
}
