<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

/**
 * Contract for Authentication Providers.
 *
 * Different auth types (OAuth2, API Key, Basic Auth) implement this.
 * Handles the authentication flow for connecting to external services.
 */
interface AuthProviderContract
{
    /**
     * Get authentication type identifier.
     *
     * @return string e.g., 'oauth2', 'api_key', 'basic', 'custom'
     */
    public function getType(): string;

    /**
     * Get fields required for this auth type.
     *
     * @return array Field definitions
     */
    public function getFields(): array;

    /**
     * Validate provided credentials format.
     *
     * @param array $credentials Credentials to validate
     * @return array Validation errors (empty if valid)
     */
    public function validate(array $credentials): array;

    /**
     * Build authorization header/parameters.
     *
     * @param array $credentials Decrypted credentials
     * @return array ['headers' => [], 'query' => []]
     */
    public function buildAuth(array $credentials): array;

    /**
     * Does this auth type require a redirect flow?
     *
     * @return bool
     */
    public function requiresRedirect(): bool;

    /**
     * Get authorization URL (for OAuth).
     *
     * @param array $config OAuth config
     * @param string $state State parameter
     * @param string $redirectUri Callback URL
     * @return string|null
     */
    public function getAuthorizationUrl(array $config, string $state, string $redirectUri): ?string;

    /**
     * Exchange authorization code for tokens.
     *
     * @param array $config OAuth config
     * @param string $code Authorization code
     * @param string $redirectUri Callback URL
     * @return array Tokens
     */
    public function exchangeCode(array $config, string $code, string $redirectUri): array;

    /**
     * Refresh access token.
     *
     * @param array $config OAuth config
     * @param string $refreshToken Refresh token
     * @return array New tokens
     */
    public function refreshToken(array $config, string $refreshToken): array;

    /**
     * Revoke tokens.
     *
     * @param array $config OAuth config
     * @param array $tokens Tokens to revoke
     * @return bool
     */
    public function revokeTokens(array $config, array $tokens): bool;
}
