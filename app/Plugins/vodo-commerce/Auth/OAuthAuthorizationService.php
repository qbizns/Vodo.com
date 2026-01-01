<?php

declare(strict_types=1);

namespace VodoCommerce\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use VodoCommerce\Models\OAuthAccessToken;
use VodoCommerce\Models\OAuthApplication;

/**
 * OAuthAuthorizationService - Handles OAuth 2.0 authorization code flow.
 *
 * Implements:
 * - Authorization code grant
 * - PKCE (Proof Key for Code Exchange)
 * - Token refresh
 * - Token revocation
 */
class OAuthAuthorizationService
{
    /** Authorization code lifetime in seconds (10 minutes) */
    public const CODE_LIFETIME = 600;

    /**
     * Start the authorization flow.
     *
     * @param string $clientId Application client ID
     * @param string $redirectUri Redirect URI after authorization
     * @param array $scopes Requested scopes
     * @param string|null $state Client state parameter
     * @param string|null $codeChallenge PKCE code challenge
     * @param string $codeChallengeMethod PKCE method (S256 or plain)
     * @return array Authorization data for user consent
     * @throws OAuthException
     */
    public function startAuthorization(
        string $clientId,
        string $redirectUri,
        array $scopes,
        ?string $state = null,
        ?string $codeChallenge = null,
        string $codeChallengeMethod = 'S256'
    ): array {
        // Find and validate application
        $app = OAuthApplication::findActiveByClientId($clientId);
        if (!$app) {
            throw new OAuthException('invalid_client', 'Unknown client identifier');
        }

        // Validate redirect URI
        if (!$app->isRedirectUriAllowed($redirectUri)) {
            throw new OAuthException('invalid_redirect_uri', 'Redirect URI not allowed');
        }

        // Validate scopes
        $invalidScopes = $app->validateScopes($scopes);
        if (!empty($invalidScopes)) {
            throw new OAuthException(
                'invalid_scope',
                'Invalid or unauthorized scopes: ' . implode(', ', $invalidScopes)
            );
        }

        // Validate PKCE for public clients
        if ($codeChallenge && !in_array($codeChallengeMethod, ['S256', 'plain'], true)) {
            throw new OAuthException('invalid_request', 'Invalid code_challenge_method');
        }

        return [
            'application' => [
                'id' => $app->id,
                'name' => $app->name,
                'description' => $app->description,
                'website' => $app->website,
                'logo_url' => $app->logo_url,
            ],
            'scopes' => $this->formatScopesForDisplay($scopes),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
        ];
    }

    /**
     * Authorize the application and generate authorization code.
     *
     * @param int $applicationId Application ID
     * @param int $storeId Store granting authorization
     * @param array $scopes Approved scopes
     * @param string $redirectUri Redirect URI
     * @param string|null $state Client state
     * @param string|null $codeChallenge PKCE code challenge
     * @param string $codeChallengeMethod PKCE method
     * @return string Authorization code
     */
    public function authorize(
        int $applicationId,
        int $storeId,
        array $scopes,
        string $redirectUri,
        ?string $state = null,
        ?string $codeChallenge = null,
        string $codeChallengeMethod = 'S256'
    ): string {
        $code = 'code_' . Str::random(48);

        // Store authorization code with metadata
        Cache::put("oauth_code:{$code}", [
            'application_id' => $applicationId,
            'store_id' => $storeId,
            'scopes' => $scopes,
            'redirect_uri' => $redirectUri,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'created_at' => now()->timestamp,
        ], self::CODE_LIFETIME);

        Log::info('OAuth authorization code issued', [
            'application_id' => $applicationId,
            'store_id' => $storeId,
            'scopes' => $scopes,
        ]);

        return $code;
    }

    /**
     * Exchange authorization code for access token.
     *
     * @param string $code Authorization code
     * @param string $clientId Client ID
     * @param string $clientSecret Client secret
     * @param string $redirectUri Redirect URI (must match authorization request)
     * @param string|null $codeVerifier PKCE code verifier
     * @return array Token response
     * @throws OAuthException
     */
    public function exchangeCode(
        string $code,
        string $clientId,
        string $clientSecret,
        string $redirectUri,
        ?string $codeVerifier = null
    ): array {
        // Get and validate authorization code
        $codeData = Cache::get("oauth_code:{$code}");
        if (!$codeData) {
            throw new OAuthException('invalid_grant', 'Authorization code is invalid or expired');
        }

        // Immediately invalidate code (one-time use)
        Cache::forget("oauth_code:{$code}");

        // Validate client
        $app = OAuthApplication::findActiveByClientId($clientId);
        if (!$app || $app->id !== $codeData['application_id']) {
            throw new OAuthException('invalid_client', 'Client authentication failed');
        }

        // Verify client secret
        if (!$app->verifySecret($clientSecret)) {
            throw new OAuthException('invalid_client', 'Client authentication failed');
        }

        // Validate redirect URI matches
        if ($redirectUri !== $codeData['redirect_uri']) {
            throw new OAuthException('invalid_grant', 'Redirect URI mismatch');
        }

        // Validate PKCE if code challenge was provided
        if ($codeData['code_challenge']) {
            if (!$codeVerifier) {
                throw new OAuthException('invalid_grant', 'Code verifier required');
            }

            $valid = $this->verifyCodeChallenge(
                $codeVerifier,
                $codeData['code_challenge'],
                $codeData['code_challenge_method']
            );

            if (!$valid) {
                throw new OAuthException('invalid_grant', 'Code verifier validation failed');
            }
        }

        // Create access token
        $tokenData = OAuthAccessToken::createTokenPair(
            $codeData['application_id'],
            $codeData['store_id'],
            $codeData['scopes']
        );

        Log::info('OAuth access token issued', [
            'application_id' => $codeData['application_id'],
            'store_id' => $codeData['store_id'],
            'token_id' => $tokenData['token']->id,
        ]);

        return [
            'access_token' => $tokenData['access_token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'refresh_token' => $tokenData['refresh_token'],
            'scope' => implode(' ', $codeData['scopes']),
        ];
    }

    /**
     * Refresh an access token.
     *
     * @param string $refreshToken Refresh token
     * @param string $clientId Client ID
     * @param string $clientSecret Client secret
     * @param array|null $scopes Optionally reduce scope
     * @return array New token response
     * @throws OAuthException
     */
    public function refreshToken(
        string $refreshToken,
        string $clientId,
        string $clientSecret,
        ?array $scopes = null
    ): array {
        // Find token
        $token = OAuthAccessToken::findByRefreshToken($refreshToken);
        if (!$token) {
            throw new OAuthException('invalid_grant', 'Refresh token is invalid or expired');
        }

        // Validate client
        $app = OAuthApplication::findActiveByClientId($clientId);
        if (!$app || $app->id !== $token->application_id) {
            throw new OAuthException('invalid_client', 'Client authentication failed');
        }

        // Verify client secret
        if (!$app->verifySecret($clientSecret)) {
            throw new OAuthException('invalid_client', 'Client authentication failed');
        }

        // Validate scopes if reducing
        $newScopes = $scopes ?? $token->scopes;
        if ($scopes) {
            // Can only reduce scopes, not expand
            foreach ($scopes as $scope) {
                if (!in_array($scope, $token->scopes, true)) {
                    throw new OAuthException('invalid_scope', 'Cannot expand scopes on refresh');
                }
            }
        }

        // Refresh the token
        $tokenData = $token->refresh();

        // Update scopes if reduced
        if ($scopes && $scopes !== $token->scopes) {
            $tokenData['token']->update(['scopes' => $newScopes]);
        }

        Log::info('OAuth access token refreshed', [
            'application_id' => $token->application_id,
            'store_id' => $token->store_id,
            'new_token_id' => $tokenData['token']->id,
        ]);

        return [
            'access_token' => $tokenData['access_token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'refresh_token' => $tokenData['refresh_token'],
            'scope' => implode(' ', $newScopes),
        ];
    }

    /**
     * Revoke a token.
     *
     * @param string $token Access token or refresh token
     * @param string $clientId Client ID
     * @param string $clientSecret Client secret
     * @param string $tokenTypeHint 'access_token' or 'refresh_token'
     */
    public function revokeToken(
        string $token,
        string $clientId,
        string $clientSecret,
        string $tokenTypeHint = 'access_token'
    ): void {
        // Validate client
        $app = OAuthApplication::findActiveByClientId($clientId);
        if (!$app || !$app->verifySecret($clientSecret)) {
            throw new OAuthException('invalid_client', 'Client authentication failed');
        }

        $hash = hash('sha256', $token);

        // Try to find and revoke the token
        if ($tokenTypeHint === 'refresh_token') {
            $tokenModel = OAuthAccessToken::where('refresh_token_hash', $hash)
                ->where('application_id', $app->id)
                ->first();
        } else {
            $tokenModel = OAuthAccessToken::where('token_hash', $hash)
                ->where('application_id', $app->id)
                ->first();
        }

        if ($tokenModel) {
            $tokenModel->revoke();

            Log::info('OAuth token revoked', [
                'application_id' => $app->id,
                'token_id' => $tokenModel->id,
            ]);
        }

        // Always succeed per RFC 7009
    }

    /**
     * Verify PKCE code challenge.
     */
    protected function verifyCodeChallenge(
        string $codeVerifier,
        string $codeChallenge,
        string $method
    ): bool {
        if ($method === 'plain') {
            return hash_equals($codeChallenge, $codeVerifier);
        }

        // S256: BASE64URL(SHA256(code_verifier))
        $computed = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        return hash_equals($codeChallenge, $computed);
    }

    /**
     * Format scopes for display in consent screen.
     */
    protected function formatScopesForDisplay(array $scopes): array
    {
        $allScopes = CommerceScopes::all();
        $formatted = [];

        foreach ($scopes as $scope) {
            if (isset($allScopes[$scope])) {
                $formatted[] = [
                    'scope' => $scope,
                    'description' => $allScopes[$scope]['description'],
                    'category' => $allScopes[$scope]['category'],
                ];
            } else {
                $formatted[] = [
                    'scope' => $scope,
                    'description' => $scope,
                    'category' => 'custom',
                ];
            }
        }

        return $formatted;
    }

    /**
     * Validate an access token and return token data if valid.
     *
     * @param string $accessToken Bearer token
     * @return OAuthAccessToken|null
     */
    public function validateToken(string $accessToken): ?OAuthAccessToken
    {
        // Strip 'Bearer ' prefix if present
        if (str_starts_with($accessToken, 'Bearer ')) {
            $accessToken = substr($accessToken, 7);
        }

        return OAuthAccessToken::verifyAccessToken($accessToken);
    }

    /**
     * Check if token has required scope.
     *
     * @param OAuthAccessToken $token
     * @param string $requiredScope
     * @return bool
     */
    public function tokenHasScope(OAuthAccessToken $token, string $requiredScope): bool
    {
        return $token->hasScope($requiredScope);
    }
}

/**
 * OAuth exception with error code.
 */
class OAuthException extends \RuntimeException
{
    protected string $errorCode;

    public function __construct(string $errorCode, string $message, int $code = 400)
    {
        parent::__construct($message, $code);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function toArray(): array
    {
        return [
            'error' => $this->errorCode,
            'error_description' => $this->getMessage(),
        ];
    }
}
