<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use VodoCommerce\Auth\OAuthAuthorizationService;
use VodoCommerce\Auth\OAuthException;

/**
 * OAuthTokenController - Handles OAuth 2.0 token operations.
 *
 * Implements:
 * - Token endpoint (RFC 6749 Section 4.1.3)
 * - Token refresh (RFC 6749 Section 6)
 * - Token revocation (RFC 7009)
 * - Token introspection (RFC 7662)
 */
class OAuthTokenController extends Controller
{
    public function __construct(
        protected OAuthAuthorizationService $oauthService
    ) {
    }

    /**
     * Token endpoint - exchange authorization code for tokens or refresh tokens.
     *
     * POST /oauth/token
     *
     * For authorization_code grant:
     * - grant_type: authorization_code
     * - code: Authorization code
     * - redirect_uri: Must match authorization request
     * - client_id: Application client ID
     * - client_secret: Application client secret
     * - code_verifier: PKCE code verifier (if code_challenge was used)
     *
     * For refresh_token grant:
     * - grant_type: refresh_token
     * - refresh_token: The refresh token
     * - client_id: Application client ID
     * - client_secret: Application client secret
     * - scope: Optional reduced scope
     */
    public function token(Request $request): JsonResponse
    {
        // Extract client credentials from header or body
        $credentials = $this->extractClientCredentials($request);

        if (!$credentials) {
            return $this->errorResponse('invalid_client', 'Client authentication required');
        }

        $grantType = $request->input('grant_type');

        try {
            return match ($grantType) {
                'authorization_code' => $this->handleAuthorizationCodeGrant($request, $credentials),
                'refresh_token' => $this->handleRefreshTokenGrant($request, $credentials),
                default => $this->errorResponse('unsupported_grant_type', "Grant type '{$grantType}' is not supported"),
            };
        } catch (OAuthException $e) {
            return $this->errorResponse($e->getErrorCode(), $e->getMessage());
        } catch (\Exception $e) {
            Log::error('OAuth token error', ['error' => $e->getMessage()]);

            return $this->errorResponse('server_error', 'An unexpected error occurred');
        }
    }

    /**
     * Handle authorization_code grant type.
     *
     * @param array{client_id: string, client_secret: string} $credentials
     */
    protected function handleAuthorizationCodeGrant(Request $request, array $credentials): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'redirect_uri' => 'required|string',
            'code_verifier' => 'nullable|string|min:43|max:128',
        ]);

        $tokenData = $this->oauthService->exchangeCode(
            code: $validated['code'],
            clientId: $credentials['client_id'],
            clientSecret: $credentials['client_secret'],
            redirectUri: $validated['redirect_uri'],
            codeVerifier: $validated['code_verifier'] ?? null
        );

        return response()->json($tokenData);
    }

    /**
     * Handle refresh_token grant type.
     *
     * @param array{client_id: string, client_secret: string} $credentials
     */
    protected function handleRefreshTokenGrant(Request $request, array $credentials): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => 'required|string',
            'scope' => 'nullable|string',
        ]);

        $scopes = $validated['scope'] ? explode(' ', $validated['scope']) : null;

        $tokenData = $this->oauthService->refreshToken(
            refreshToken: $validated['refresh_token'],
            clientId: $credentials['client_id'],
            clientSecret: $credentials['client_secret'],
            scopes: $scopes
        );

        return response()->json($tokenData);
    }

    /**
     * Token revocation endpoint (RFC 7009).
     *
     * POST /oauth/revoke
     *
     * - token: The token to revoke (access or refresh)
     * - token_type_hint: access_token or refresh_token
     * - client_id: Application client ID
     * - client_secret: Application client secret
     */
    public function revoke(Request $request): JsonResponse
    {
        $credentials = $this->extractClientCredentials($request);

        if (!$credentials) {
            return $this->errorResponse('invalid_client', 'Client authentication required');
        }

        $validated = $request->validate([
            'token' => 'required|string',
            'token_type_hint' => 'nullable|in:access_token,refresh_token',
        ]);

        try {
            $this->oauthService->revokeToken(
                token: $validated['token'],
                clientId: $credentials['client_id'],
                clientSecret: $credentials['client_secret'],
                tokenTypeHint: $validated['token_type_hint'] ?? 'access_token'
            );

            // RFC 7009: Always return 200 OK regardless of whether token existed
            return response()->json([], 200);
        } catch (OAuthException $e) {
            return $this->errorResponse($e->getErrorCode(), $e->getMessage());
        }
    }

    /**
     * Token introspection endpoint (RFC 7662).
     *
     * POST /oauth/introspect
     *
     * - token: The token to introspect
     * - token_type_hint: access_token or refresh_token
     * - client_id: Application client ID
     * - client_secret: Application client secret
     */
    public function introspect(Request $request): JsonResponse
    {
        $credentials = $this->extractClientCredentials($request);

        if (!$credentials) {
            return $this->errorResponse('invalid_client', 'Client authentication required');
        }

        $validated = $request->validate([
            'token' => 'required|string',
            'token_type_hint' => 'nullable|in:access_token,refresh_token',
        ]);

        try {
            $tokenModel = $this->oauthService->validateToken($validated['token']);

            if (!$tokenModel) {
                // Token is not active
                return response()->json(['active' => false]);
            }

            // Verify the introspecting client owns this token
            $app = \VodoCommerce\Models\OAuthApplication::findActiveByClientId($credentials['client_id']);
            if (!$app || !$app->verifySecret($credentials['client_secret'])) {
                return $this->errorResponse('invalid_client', 'Client authentication failed');
            }

            // Only allow introspection of own tokens
            if ($tokenModel->application_id !== $app->id) {
                return response()->json(['active' => false]);
            }

            return response()->json([
                'active' => true,
                'scope' => implode(' ', $tokenModel->scopes ?? []),
                'client_id' => $app->client_id,
                'token_type' => 'Bearer',
                'exp' => $tokenModel->expires_at?->timestamp,
                'iat' => $tokenModel->created_at?->timestamp,
                'sub' => (string) $tokenModel->store_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Token introspection failed', ['error' => $e->getMessage()]);

            return response()->json(['active' => false]);
        }
    }

    /**
     * Extract client credentials from request.
     *
     * Supports:
     * - HTTP Basic Auth (preferred)
     * - Request body parameters
     *
     * @return array{client_id: string, client_secret: string}|null
     */
    protected function extractClientCredentials(Request $request): ?array
    {
        // Try HTTP Basic Auth first (preferred per RFC 6749)
        $authorization = $request->header('Authorization', '');

        if (str_starts_with($authorization, 'Basic ')) {
            $decoded = base64_decode(substr($authorization, 6));
            if ($decoded && str_contains($decoded, ':')) {
                [$clientId, $clientSecret] = explode(':', $decoded, 2);

                return [
                    'client_id' => urldecode($clientId),
                    'client_secret' => urldecode($clientSecret),
                ];
            }
        }

        // Fallback to request body
        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');

        if ($clientId && $clientSecret) {
            return [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ];
        }

        return null;
    }

    /**
     * Create OAuth error response per RFC 6749.
     */
    protected function errorResponse(string $error, string $description, int $status = 400): JsonResponse
    {
        $statusCode = match ($error) {
            'invalid_client' => 401,
            'server_error' => 500,
            default => $status,
        };

        return response()->json([
            'error' => $error,
            'error_description' => $description,
        ], $statusCode);
    }
}
