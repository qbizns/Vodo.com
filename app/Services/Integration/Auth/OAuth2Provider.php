<?php

declare(strict_types=1);

namespace App\Services\Integration\Auth;

use App\Contracts\Integration\AuthProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * OAuth 2.0 Authentication Provider
 *
 * Handles OAuth 2.0 authorization code flow.
 */
class OAuth2Provider implements AuthProviderContract
{
    public function getType(): string
    {
        return 'oauth2';
    }

    public function getFields(): array
    {
        return []; // OAuth doesn't require manual field input
    }

    public function validate(array $credentials): array
    {
        $errors = [];

        if (empty($credentials['access_token'])) {
            $errors['access_token'] = 'Access token is required';
        }

        return $errors;
    }

    public function buildAuth(array $credentials): array
    {
        $tokenType = $credentials['token_type'] ?? 'Bearer';

        return [
            'headers' => [
                'Authorization' => "{$tokenType} {$credentials['access_token']}",
            ],
            'query' => [],
        ];
    }

    public function requiresRedirect(): bool
    {
        return true;
    }

    public function getAuthorizationUrl(array $config, string $state, string $redirectUri): ?string
    {
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
        ];

        if (!empty($config['scope'])) {
            $params['scope'] = is_array($config['scope'])
                ? implode(' ', $config['scope'])
                : $config['scope'];
        }

        // Add PKCE if supported
        if ($config['pkce'] ?? false) {
            $codeVerifier = Str::random(64);
            $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

            $params['code_challenge'] = $codeChallenge;
            $params['code_challenge_method'] = 'S256';

            // Store code verifier in session for later
            session(['oauth_code_verifier_' . $state => $codeVerifier]);
        }

        // Add any extra parameters
        if (!empty($config['auth_params'])) {
            $params = array_merge($params, $config['auth_params']);
        }

        return $config['authorization_url'] . '?' . http_build_query($params);
    }

    public function exchangeCode(array $config, string $code, string $redirectUri): array
    {
        $params = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ];

        // Add PKCE verifier if used
        $state = request()->input('state');
        $codeVerifier = session('oauth_code_verifier_' . $state);
        if ($codeVerifier) {
            $params['code_verifier'] = $codeVerifier;
            session()->forget('oauth_code_verifier_' . $state);
        }

        $response = Http::asForm()->post($config['token_url'], $params);

        if ($response->failed()) {
            throw new \App\Exceptions\Integration\OAuthException(
                $response->json('error_description') ?? $response->json('error') ?? 'Token exchange failed'
            );
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'token_type' => $data['token_type'] ?? 'Bearer',
            'expires_at' => isset($data['expires_in'])
                ? now()->addSeconds($data['expires_in'])->timestamp
                : null,
            'scope' => $data['scope'] ?? null,
        ];
    }

    public function refreshToken(array $config, string $refreshToken): array
    {
        $params = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $response = Http::asForm()->post($config['token_url'], $params);

        if ($response->failed()) {
            throw new \App\Exceptions\Integration\TokenRefreshException(
                $response->json('error_description') ?? $response->json('error') ?? 'Token refresh failed'
            );
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $refreshToken, // Keep old if not returned
            'token_type' => $data['token_type'] ?? 'Bearer',
            'expires_at' => isset($data['expires_in'])
                ? now()->addSeconds($data['expires_in'])->timestamp
                : null,
            'scope' => $data['scope'] ?? null,
        ];
    }

    public function revokeTokens(array $config, array $tokens): bool
    {
        if (empty($config['revoke_url'])) {
            return false;
        }

        $response = Http::asForm()->post($config['revoke_url'], [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'token' => $tokens['access_token'],
        ]);

        return $response->successful();
    }
}
