<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Plugins\PluginScopeException;
use App\Exceptions\Plugins\SandboxViolationException;
use App\Services\Plugins\Security\PluginApiKeyManager;
use App\Services\Plugins\Security\PluginSandbox;
use App\Services\Plugins\Security\ScopeValidator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plugin Security Middleware - Enforces plugin security at the request level.
 *
 * This middleware:
 * - Authenticates plugin API requests
 * - Sets up the security context
 * - Enforces sandbox limits
 * - Validates scope access
 */
class PluginSecurityMiddleware
{
    public function __construct(
        protected PluginApiKeyManager $apiKeyManager,
        protected ScopeValidator $scopeValidator,
        protected PluginSandbox $sandbox
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $requiredScope = null): Response
    {
        // Extract plugin context from API key
        $apiKey = $this->apiKeyManager->extractFromRequest($request);
        $pluginSlug = null;

        if ($apiKey) {
            $authResult = $this->apiKeyManager->authenticate(
                $apiKey,
                $request->ip(),
                $request->getHost()
            );

            if (!$authResult['valid']) {
                return response()->json([
                    'error' => 'Authentication failed',
                    'message' => $authResult['error'],
                ], 401);
            }

            $pluginSlug = $authResult['key']->plugin_slug;

            // Check scope if API key has scopes and a scope is required
            if ($requiredScope && !$authResult['key']->hasScope($requiredScope)) {
                return response()->json([
                    'error' => 'Insufficient permissions',
                    'message' => "API key does not have scope: {$requiredScope}",
                    'required_scope' => $requiredScope,
                ], 403);
            }

            // Store the API key in the request for later use
            $request->attributes->set('plugin_api_key', $authResult['key']);
        }

        // Get plugin slug from route if not from API key
        if (!$pluginSlug) {
            $pluginSlug = $request->route('plugin') ?? $request->route('pluginSlug');
        }

        if ($pluginSlug) {
            // Set up security context
            $this->scopeValidator->setPluginContext($pluginSlug);
            $request->attributes->set('plugin_slug', $pluginSlug);

            // Check sandbox before executing
            if ($this->sandbox->isEnabled()) {
                if ($this->sandbox->isBlocked($pluginSlug)) {
                    return response()->json([
                        'error' => 'Plugin blocked',
                        'message' => 'This plugin is temporarily blocked due to violations',
                    ], 503);
                }

                try {
                    $this->sandbox->enforceLimits($pluginSlug);
                } catch (SandboxViolationException $e) {
                    return response()->json([
                        'error' => 'Sandbox violation',
                        'message' => $e->getMessage(),
                        'violation_type' => $e->getViolationType(),
                    ], $e->getCode());
                }
            }
        }

        // Check required scope if specified
        if ($requiredScope && $pluginSlug) {
            try {
                $this->scopeValidator->assertCanAccess($requiredScope);
            } catch (PluginScopeException $e) {
                return response()->json([
                    'error' => 'Permission denied',
                    'message' => $e->getMessage(),
                    'required_scope' => $requiredScope,
                ], 403);
            }
        }

        // Execute the request
        $response = $next($request);

        // Clear context
        $this->scopeValidator->setPluginContext(null);

        return $response;
    }
}
