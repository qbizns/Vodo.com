<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use App\Services\View\ViewExtensionRegistry;
use App\Services\View\ViewExtender;

class ProcessViewExtensions
{
    protected ViewExtender $extender;
    protected ViewExtensionRegistry $registry;

    public function __construct(ViewExtender $extender, ViewExtensionRegistry $registry)
    {
        $this->extender = $extender;
        $this->registry = $registry;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return SymfonyResponse
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        // Only process HTML responses
        if (!$this->shouldProcess($response)) {
            return $response;
        }

        // Get the rendered view name from the request
        $viewName = $request->attributes->get('_rendered_view');
        
        if (!$viewName) {
            // Try to get from route
            $viewName = $this->getViewNameFromRoute($request);
        }

        // If we have extensions for this view, process them
        /*
        if ($viewName && $this->registry->hasExtensions($viewName)) {
            $content = $response->getContent();
            $processedContent = $this->extender->process($viewName, $content);
            $response->setContent($processedContent);
        }
        */

        return $response;
    }

    /**
     * Determine if the response should be processed.
     */
    protected function shouldProcess(SymfonyResponse $response): bool
    {
        // Must be a Response with content
        if (!$response instanceof Response) {
            return false;
        }

        // Must have content
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }

        // Must be HTML content type
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html') && !empty($contentType)) {
            return false;
        }

        // Skip if explicitly disabled
        if ($response->headers->has('X-Skip-View-Extensions')) {
            return false;
        }

        return true;
    }

    /**
     * Try to get view name from route configuration.
     */
    protected function getViewNameFromRoute(Request $request): ?string
    {
        $route = $request->route();
        
        if (!$route) {
            return null;
        }

        // Check for view name in route action
        $action = $route->getAction();
        
        if (isset($action['view'])) {
            return $action['view'];
        }

        // Check for convention-based view name
        // e.g., admin.users.index based on route name
        $routeName = $route->getName();
        if ($routeName) {
            return str_replace(['-', ':'], '.', $routeName);
        }

        return null;
    }
}
