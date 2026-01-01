<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use VodoCommerce\Auth\CommerceScopes;
use VodoCommerce\Auth\OAuthAuthorizationService;
use VodoCommerce\Models\OAuthAccessToken;

/**
 * ValidateOAuthToken - Middleware for validating OAuth 2.0 access tokens.
 *
 * Usage in routes:
 * Route::middleware(['oauth'])->group(...);               // Just validate token
 * Route::middleware(['oauth:commerce.orders.read'])->... // Require specific scope
 */
class ValidateOAuthToken
{
    public function __construct(
        protected OAuthAuthorizationService $oauthService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @param string ...$requiredScopes Required scopes (any one grants access)
     */
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        // Extract bearer token
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return $this->unauthorized('Missing authorization header');
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Invalid authorization header format');
        }

        $accessToken = substr($authHeader, 7);

        if (empty($accessToken)) {
            return $this->unauthorized('Empty access token');
        }

        // Validate token
        $token = $this->oauthService->validateToken($accessToken);

        if (!$token) {
            return $this->unauthorized('Invalid or expired access token');
        }

        // Check application is still active
        if (!$token->application || !$token->application->isActive()) {
            return $this->unauthorized('Application has been deactivated');
        }

        // Check required scopes
        if (!empty($requiredScopes)) {
            $hasRequiredScope = false;

            foreach ($requiredScopes as $scope) {
                if ($token->hasScope($scope)) {
                    $hasRequiredScope = true;
                    break;
                }
            }

            if (!$hasRequiredScope) {
                return $this->forbidden(
                    'Insufficient scope. Required: ' . implode(' OR ', $requiredScopes),
                    $requiredScopes
                );
            }
        }

        // Attach token and context to request
        $request->attributes->set('oauth_token', $token);
        $request->attributes->set('oauth_application', $token->application);
        $request->attributes->set('oauth_store_id', $token->store_id);
        $request->attributes->set('oauth_scopes', $token->scopes);

        // Set store context for BelongsToStore trait
        \VodoCommerce\Models\Store::setCurrentStoreId($token->store_id);

        $response = $next($request);

        // Add rate limit headers based on application
        $response->headers->set('X-OAuth-App-Id', $token->application->client_id);

        return $response;
    }

    /**
     * Return 401 Unauthorized response.
     */
    protected function unauthorized(string $message): Response
    {
        Log::debug('OAuth authentication failed', ['message' => $message]);

        return response()->json([
            'error' => 'unauthorized',
            'error_description' => $message,
        ], 401, [
            'WWW-Authenticate' => 'Bearer realm="commerce-api"',
        ]);
    }

    /**
     * Return 403 Forbidden response (insufficient scope).
     */
    protected function forbidden(string $message, array $requiredScopes): Response
    {
        Log::debug('OAuth scope check failed', [
            'message' => $message,
            'required_scopes' => $requiredScopes,
        ]);

        return response()->json([
            'error' => 'insufficient_scope',
            'error_description' => $message,
            'required_scopes' => $requiredScopes,
        ], 403, [
            'WWW-Authenticate' => 'Bearer realm="commerce-api", scope="' . implode(' ', $requiredScopes) . '"',
        ]);
    }
}
