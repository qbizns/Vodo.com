<?php

declare(strict_types=1);

namespace App\Services\Integration\Auth;

use App\Contracts\Integration\AuthProviderContract;
use App\Contracts\Integration\CredentialVaultContract;
use App\Contracts\Integration\ConnectorRegistryContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Authentication Manager
 *
 * Central service for managing authentication flows across all connectors.
 * Handles OAuth redirects, token refresh, and credential validation.
 */
class AuthenticationManager
{
    /**
     * Registered auth providers.
     *
     * @var array<string, AuthProviderContract>
     */
    protected array $providers = [];

    public function __construct(
        protected CredentialVaultContract $vault,
        protected ConnectorRegistryContract $registry
    ) {
        $this->registerDefaultProviders();
    }

    /**
     * Register default authentication providers.
     */
    protected function registerDefaultProviders(): void
    {
        $this->registerProvider(new OAuth2Provider());
        $this->registerProvider(new ApiKeyProvider());
        $this->registerProvider(new BasicAuthProvider());
    }

    /**
     * Register an authentication provider.
     */
    public function registerProvider(AuthProviderContract $provider): self
    {
        $this->providers[$provider->getType()] = $provider;
        return $this;
    }

    /**
     * Get an authentication provider.
     */
    public function getProvider(string $type): ?AuthProviderContract
    {
        return $this->providers[$type] ?? null;
    }

    /**
     * Get all registered providers.
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    // =========================================================================
    // CONNECTION FLOW
    // =========================================================================

    /**
     * Initiate connection for a connector.
     * Returns either credentials form fields or OAuth redirect URL.
     */
    public function initiateConnection(string $connectorName, string $tenantId): array
    {
        $connector = $this->registry->get($connectorName);

        if (!$connector) {
            throw new \InvalidArgumentException("Connector not found: {$connectorName}");
        }

        $authType = $connector->getAuthType();
        $authConfig = $connector->getAuthConfig();

        // If OAuth, generate authorization URL
        if ($authType === 'oauth2' || $authType === 'oauth1') {
            $state = $this->generateOAuthState($connectorName, $tenantId);
            $redirectUri = $this->getOAuthRedirectUri();

            $provider = $this->getProvider($authType);
            $oauthConfig = $authConfig['oauth'] ?? $connector->getOAuthConfig();

            return [
                'type' => 'redirect',
                'auth_type' => $authType,
                'url' => $provider->getAuthorizationUrl($oauthConfig, $state, $redirectUri),
                'state' => $state,
            ];
        }

        // Return form fields for manual credential entry
        return [
            'type' => 'form',
            'auth_type' => $authType,
            'fields' => $authConfig['fields'] ?? $this->getProvider($authType)?->getFields() ?? [],
        ];
    }

    /**
     * Complete connection with provided credentials.
     */
    public function completeConnection(
        string $connectorName,
        string $tenantId,
        array $credentials,
        ?string $label = null
    ): array {
        $connector = $this->registry->get($connectorName);

        if (!$connector) {
            throw new \InvalidArgumentException("Connector not found: {$connectorName}");
        }

        $authType = $connector->getAuthType();
        $provider = $this->getProvider($authType);

        // Validate credentials
        if ($provider) {
            $errors = $provider->validate($credentials);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors,
                ];
            }
        }

        // Test connection
        $testResult = $connector->testConnection($credentials);

        if (!$testResult['success']) {
            return [
                'success' => false,
                'errors' => ['connection' => $testResult['message'] ?? 'Connection test failed'],
            ];
        }

        // Store credentials
        $connectionId = $this->vault->store($tenantId, $connectorName, $credentials, [
            'label' => $label ?? $connector->getDisplayName(),
            'user_info' => $testResult['user'] ?? null,
        ]);

        // Fire lifecycle hook
        $connector->onConnect($credentials);

        // Fire action hook
        do_action('integration_connected', $connectorName, $connectionId, $tenantId);

        return [
            'success' => true,
            'connection_id' => $connectionId,
            'user' => $testResult['user'] ?? null,
        ];
    }

    // =========================================================================
    // OAUTH CALLBACKS
    // =========================================================================

    /**
     * Handle OAuth callback.
     */
    public function handleOAuthCallback(string $code, string $state): array
    {
        // Retrieve state data
        $stateData = $this->retrieveOAuthState($state);

        if (!$stateData) {
            throw new \App\Exceptions\Integration\OAuthException('Invalid or expired OAuth state');
        }

        $connectorName = $stateData['connector'];
        $tenantId = $stateData['tenant_id'];

        $connector = $this->registry->get($connectorName);

        if (!$connector) {
            throw new \InvalidArgumentException("Connector not found: {$connectorName}");
        }

        $authConfig = $connector->getAuthConfig();
        $oauthConfig = $authConfig['oauth'] ?? $connector->getOAuthConfig();

        $provider = $this->getProvider($connector->getAuthType());
        $redirectUri = $this->getOAuthRedirectUri();

        // Exchange code for tokens
        $tokens = $provider->exchangeCode($oauthConfig, $code, $redirectUri);

        // Complete the connection
        return $this->completeConnection(
            $connectorName,
            $tenantId,
            $tokens,
            $stateData['label'] ?? null
        );
    }

    /**
     * Generate OAuth state for CSRF protection.
     */
    protected function generateOAuthState(string $connectorName, string $tenantId, ?string $label = null): string
    {
        $state = Str::random(40);

        Cache::put("oauth_state:{$state}", [
            'connector' => $connectorName,
            'tenant_id' => $tenantId,
            'label' => $label,
            'created_at' => now()->timestamp,
        ], now()->addMinutes(15));

        return $state;
    }

    /**
     * Retrieve OAuth state data.
     */
    protected function retrieveOAuthState(string $state): ?array
    {
        $data = Cache::pull("oauth_state:{$state}");

        if (!$data) {
            return null;
        }

        // Verify not expired (15 minutes)
        if (now()->timestamp - $data['created_at'] > 900) {
            return null;
        }

        return $data;
    }

    /**
     * Get OAuth redirect URI.
     */
    protected function getOAuthRedirectUri(): string
    {
        return route('integration.oauth.callback');
    }

    // =========================================================================
    // TOKEN MANAGEMENT
    // =========================================================================

    /**
     * Get valid credentials for a connection.
     * Automatically refreshes tokens if needed.
     */
    public function getCredentials(string $connectionId): array
    {
        $credentials = $this->vault->retrieve($connectionId);

        if (!$credentials) {
            throw new \App\Exceptions\Integration\CredentialNotFoundException(
                "Credentials not found: {$connectionId}"
            );
        }

        // Check if OAuth tokens need refresh
        if ($this->needsTokenRefresh($credentials)) {
            $credentials = $this->refreshTokens($connectionId, $credentials);
        }

        return $credentials;
    }

    /**
     * Check if OAuth tokens need refresh.
     */
    protected function needsTokenRefresh(array $credentials): bool
    {
        if (empty($credentials['refresh_token'])) {
            return false;
        }

        if (empty($credentials['expires_at'])) {
            return false;
        }

        // Refresh 5 minutes before expiration
        return $credentials['expires_at'] < (now()->timestamp + 300);
    }

    /**
     * Refresh OAuth tokens.
     */
    public function refreshTokens(string $connectionId, ?array $credentials = null): array
    {
        $credentials = $credentials ?? $this->vault->retrieve($connectionId);
        $metadata = $this->vault->getMetadata($connectionId);

        if (!$credentials || !$metadata) {
            throw new \App\Exceptions\Integration\CredentialNotFoundException(
                "Credentials not found: {$connectionId}"
            );
        }

        $connectorName = $metadata['connector'];
        $connector = $this->registry->get($connectorName);

        if (!$connector) {
            throw new \InvalidArgumentException("Connector not found: {$connectorName}");
        }

        if (empty($credentials['refresh_token'])) {
            throw new \App\Exceptions\Integration\TokenRefreshException(
                'No refresh token available'
            );
        }

        $authConfig = $connector->getAuthConfig();
        $oauthConfig = $authConfig['oauth'] ?? $connector->getOAuthConfig();

        $provider = $this->getProvider($connector->getAuthType());
        $newTokens = $provider->refreshToken($oauthConfig, $credentials['refresh_token']);

        // Update stored credentials
        $updatedCredentials = array_merge($credentials, $newTokens);
        $this->vault->update($connectionId, $updatedCredentials);

        // Fire hook
        do_action('integration_token_refreshed', $connectorName, $connectionId);

        return $updatedCredentials;
    }

    // =========================================================================
    // DISCONNECTION
    // =========================================================================

    /**
     * Disconnect and revoke credentials.
     */
    public function disconnect(string $connectionId): bool
    {
        $credentials = $this->vault->retrieve($connectionId);
        $metadata = $this->vault->getMetadata($connectionId);

        if (!$credentials || !$metadata) {
            return false;
        }

        $connectorName = $metadata['connector'];
        $connector = $this->registry->get($connectorName);

        if ($connector) {
            // Try to revoke tokens
            $authConfig = $connector->getAuthConfig();
            $oauthConfig = $authConfig['oauth'] ?? $connector->getOAuthConfig();

            if ($oauthConfig && !empty($credentials['access_token'])) {
                $provider = $this->getProvider($connector->getAuthType());
                try {
                    $provider->revokeTokens($oauthConfig, $credentials);
                } catch (\Exception $e) {
                    // Log but don't fail
                }
            }

            // Fire lifecycle hook
            $connector->onDisconnect($credentials);
        }

        // Delete from vault
        $this->vault->delete($connectionId);

        // Fire action hook
        do_action('integration_disconnected', $connectorName, $connectionId);

        return true;
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    /**
     * Validate credentials without storing.
     */
    public function validateCredentials(string $connectorName, array $credentials): array
    {
        $connector = $this->registry->get($connectorName);

        if (!$connector) {
            return [
                'valid' => false,
                'errors' => ['connector' => 'Connector not found'],
            ];
        }

        $authType = $connector->getAuthType();
        $provider = $this->getProvider($authType);

        // Provider validation
        if ($provider) {
            $errors = $provider->validate($credentials);
            if (!empty($errors)) {
                return [
                    'valid' => false,
                    'errors' => $errors,
                ];
            }
        }

        // Connection test
        $testResult = $connector->testConnection($credentials);

        return [
            'valid' => $testResult['success'],
            'errors' => $testResult['success'] ? [] : ['connection' => $testResult['message']],
            'user' => $testResult['user'] ?? null,
        ];
    }

    /**
     * Build auth headers/params for an HTTP request.
     */
    public function buildAuthForRequest(string $connectionId, array $config = []): array
    {
        $credentials = $this->getCredentials($connectionId);
        $metadata = $this->vault->getMetadata($connectionId);

        if (!$metadata) {
            return ['headers' => [], 'query' => []];
        }

        $connectorName = $metadata['connector'];
        $connector = $this->registry->get($connectorName);

        if (!$connector) {
            return ['headers' => [], 'query' => []];
        }

        $provider = $this->getProvider($connector->getAuthType());

        if (!$provider) {
            return ['headers' => [], 'query' => []];
        }

        $authConfig = array_merge($connector->getAuthConfig(), $config);

        return $provider->buildAuth($credentials, $authConfig);
    }
}
