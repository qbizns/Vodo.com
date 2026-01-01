<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use VodoCommerce\Auth\CommerceScopes;
use VodoCommerce\Auth\OAuthAuthorizationService;
use VodoCommerce\Auth\OAuthException;

/**
 * OAuthAuthorizationController - Handles OAuth 2.0 authorization flow.
 *
 * Implements RFC 6749 Authorization Code Grant with PKCE (RFC 7636).
 *
 * Flow:
 * 1. Client redirects user to GET /oauth/authorize with params
 * 2. User sees consent screen with requested scopes
 * 3. User approves/denies
 * 4. User is redirected back to client with auth code or error
 */
class OAuthAuthorizationController extends Controller
{
    public function __construct(
        protected OAuthAuthorizationService $oauthService
    ) {
    }

    /**
     * Start the authorization flow - validate and show consent screen.
     *
     * GET /oauth/authorize
     *
     * Required params:
     * - client_id: Application client ID
     * - redirect_uri: Where to redirect after authorization
     * - response_type: Must be "code"
     * - scope: Space-separated list of scopes
     *
     * Optional params:
     * - state: Client state (returned unchanged)
     * - code_challenge: PKCE code challenge
     * - code_challenge_method: S256 or plain (default: S256)
     */
    public function authorize(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'redirect_uri' => 'required|url',
            'response_type' => 'required|in:code',
            'scope' => 'required|string',
            'state' => 'nullable|string|max:512',
            'code_challenge' => 'nullable|string|min:43|max:128',
            'code_challenge_method' => 'nullable|in:S256,plain',
        ]);

        $scopes = explode(' ', $validated['scope']);
        $redirectUri = $validated['redirect_uri'];
        $state = $validated['state'] ?? null;

        try {
            $authData = $this->oauthService->startAuthorization(
                clientId: $validated['client_id'],
                redirectUri: $redirectUri,
                scopes: $scopes,
                state: $state,
                codeChallenge: $validated['code_challenge'] ?? null,
                codeChallengeMethod: $validated['code_challenge_method'] ?? 'S256'
            );

            // Store auth request in session for form submission
            session()->put('oauth_request', [
                'client_id' => $validated['client_id'],
                'application_id' => $authData['application']['id'],
                'redirect_uri' => $redirectUri,
                'scopes' => $scopes,
                'state' => $state,
                'code_challenge' => $validated['code_challenge'] ?? null,
                'code_challenge_method' => $validated['code_challenge_method'] ?? 'S256',
            ]);

            // Return consent screen view
            return view('vodo-commerce::oauth.authorize', [
                'application' => $authData['application'],
                'scopes' => $authData['scopes'],
                'scopeGroups' => CommerceScopes::grouped(),
                'redirectUri' => $redirectUri,
                'state' => $state,
            ]);
        } catch (OAuthException $e) {
            return $this->redirectWithError($redirectUri, $e->getErrorCode(), $e->getMessage(), $state);
        } catch (\Exception $e) {
            Log::error('OAuth authorization failed', ['error' => $e->getMessage()]);

            return $this->redirectWithError($redirectUri, 'server_error', 'An unexpected error occurred', $state);
        }
    }

    /**
     * Process user consent and generate authorization code.
     *
     * POST /oauth/authorize
     */
    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:approve,deny',
        ]);

        $oauthRequest = session()->pull('oauth_request');

        if (!$oauthRequest) {
            return redirect()->route('home')->with('error', 'Invalid authorization request');
        }

        $redirectUri = $oauthRequest['redirect_uri'];
        $state = $oauthRequest['state'];

        // User denied access
        if ($validated['decision'] === 'deny') {
            return $this->redirectWithError($redirectUri, 'access_denied', 'User denied the authorization request', $state);
        }

        // Get current store context
        $storeId = $this->getCurrentStoreId($request);

        if (!$storeId) {
            return $this->redirectWithError($redirectUri, 'invalid_request', 'Store context required', $state);
        }

        try {
            // Generate authorization code
            $code = $this->oauthService->authorize(
                applicationId: $oauthRequest['application_id'],
                storeId: $storeId,
                scopes: $oauthRequest['scopes'],
                redirectUri: $redirectUri,
                state: $state,
                codeChallenge: $oauthRequest['code_challenge'],
                codeChallengeMethod: $oauthRequest['code_challenge_method']
            );

            // Build redirect URL with code
            $params = ['code' => $code];
            if ($state) {
                $params['state'] = $state;
            }

            $redirectUrl = $redirectUri . '?' . http_build_query($params);

            Log::info('OAuth authorization granted', [
                'application_id' => $oauthRequest['application_id'],
                'store_id' => $storeId,
                'scopes' => $oauthRequest['scopes'],
            ]);

            return redirect()->away($redirectUrl);
        } catch (OAuthException $e) {
            return $this->redirectWithError($redirectUri, $e->getErrorCode(), $e->getMessage(), $state);
        }
    }

    /**
     * Get available OAuth scopes.
     *
     * GET /oauth/scopes
     */
    public function scopes(): JsonResponse
    {
        return response()->json([
            'scopes' => CommerceScopes::all(),
            'grouped' => CommerceScopes::grouped(),
            'presets' => CommerceScopes::getPresets(),
        ]);
    }

    /**
     * OAuth 2.0 server metadata (RFC 8414).
     *
     * GET /.well-known/oauth-authorization-server
     */
    public function metadata(): JsonResponse
    {
        $baseUrl = config('app.url');

        return response()->json([
            'issuer' => $baseUrl,
            'authorization_endpoint' => $baseUrl . '/oauth/authorize',
            'token_endpoint' => $baseUrl . '/oauth/token',
            'revocation_endpoint' => $baseUrl . '/oauth/revoke',
            'introspection_endpoint' => $baseUrl . '/oauth/introspect',
            'scopes_supported' => array_keys(CommerceScopes::all()),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'code_challenge_methods_supported' => ['S256', 'plain'],
            'service_documentation' => $baseUrl . '/api/docs/commerce',
        ]);
    }

    /**
     * Redirect with OAuth error.
     */
    protected function redirectWithError(
        string $redirectUri,
        string $error,
        string $description,
        ?string $state
    ): RedirectResponse {
        $params = [
            'error' => $error,
            'error_description' => $description,
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return redirect()->away($redirectUri . '?' . http_build_query($params));
    }

    /**
     * Get current store ID from session/context.
     */
    protected function getCurrentStoreId(Request $request): ?int
    {
        // From authenticated user's current store
        if (Auth::check() && Auth::user()->current_store_id) {
            return Auth::user()->current_store_id;
        }

        // From session
        if (session()->has('current_store_id')) {
            return (int) session()->get('current_store_id');
        }

        // From header
        if ($request->hasHeader('X-Store-Id')) {
            return (int) $request->header('X-Store-Id');
        }

        return null;
    }
}
