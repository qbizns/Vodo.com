<?php

declare(strict_types=1);

namespace App\Services\Integration\Credential;

use App\Contracts\Integration\CredentialVaultContract;
use App\Contracts\Integration\ConnectorRegistryContract;
use App\Models\Integration\Connection;
use App\Models\Integration\ConnectionShare;
use App\Models\Integration\CredentialAccessLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Credential Vault
 *
 * Securely stores and manages credentials for external service connections.
 * All credentials are encrypted at rest using AES-256-GCM.
 *
 * Security Features:
 * - Encryption at rest
 * - Access audit logging
 * - Token refresh automation
 * - Credential rotation
 */
class CredentialVault implements CredentialVaultContract
{
    public function __construct(
        protected ConnectorRegistryContract $connectorRegistry
    ) {}

    public function store(
        string $name,
        string $connectorName,
        array $credentials,
        ?int $userId = null,
        array $options = []
    ): string {
        // Validate connector exists
        $connector = $this->connectorRegistry->get($connectorName);
        if (!$connector) {
            throw new \InvalidArgumentException("Connector not found: {$connectorName}");
        }

        $connectionId = $options['id'] ?? Str::uuid()->toString();

        // Encrypt credentials
        $encryptedCredentials = $this->encrypt($credentials);

        // Create connection record
        $connection = Connection::create([
            'id' => $connectionId,
            'name' => $name,
            'connector_name' => $connectorName,
            'credentials' => $encryptedCredentials,
            'user_id' => $userId,
            'is_shared' => $options['shared'] ?? false,
            'status' => 'pending',
            'metadata' => $options['metadata'] ?? [],
        ]);

        // Log access
        $this->logAccess($connectionId, 'created');

        // Fire hook
        do_action('connection_created', $connection);

        return $connectionId;
    }

    public function update(string $connectionId, array $credentials): bool
    {
        $connection = Connection::find($connectionId);

        if (!$connection) {
            return false;
        }

        // Encrypt new credentials
        $encryptedCredentials = $this->encrypt($credentials);

        $connection->update([
            'credentials' => $encryptedCredentials,
            'updated_at' => now(),
        ]);

        // Log access
        $this->logAccess($connectionId, 'updated');

        return true;
    }

    public function retrieve(string $connectionId): array
    {
        $connection = Connection::findOrFail($connectionId);

        // Check access permissions
        $this->checkAccess($connection);

        // Log access
        $this->logAccess($connectionId, 'retrieved');

        // Decrypt and return
        return $this->decrypt($connection->credentials);
    }

    public function delete(string $connectionId): bool
    {
        $connection = Connection::find($connectionId);

        if (!$connection) {
            return false;
        }

        // Notify connector of disconnect
        $connector = $this->connectorRegistry->get($connection->connector_name);
        if ($connector) {
            try {
                $credentials = $this->decrypt($connection->credentials);
                $connector->onDisconnect($credentials);
            } catch (\Exception $e) {
                // Log but don't fail
            }
        }

        // Delete shares
        ConnectionShare::where('connection_id', $connectionId)->delete();

        // Delete access logs (optional - might want to keep for audit)
        // CredentialAccessLog::where('connection_id', $connectionId)->delete();

        // Delete connection
        $connection->delete();

        // Fire hook
        do_action('connection_deleted', $connectionId);

        return true;
    }

    public function getConnection(string $connectionId): ?array
    {
        $connection = Connection::find($connectionId);

        if (!$connection) {
            return null;
        }

        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'connector_name' => $connection->connector_name,
            'user_id' => $connection->user_id,
            'is_shared' => $connection->is_shared,
            'status' => $connection->status,
            'verified_at' => $connection->verified_at,
            'last_used_at' => $connection->last_used_at,
            'error_message' => $connection->error_message,
            'metadata' => $connection->metadata,
            'created_at' => $connection->created_at,
            'updated_at' => $connection->updated_at,
        ];
    }

    public function getConnections(?int $userId = null): Collection
    {
        $query = Connection::query();

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('is_shared', true)
                    ->orWhereHas('shares', fn($sq) => $sq->where('user_id', $userId));
            });
        }

        return $query->orderBy('name')->get()->map(fn($c) => $this->getConnection($c->id));
    }

    public function getConnectionsForConnector(string $connectorName, ?int $userId = null): Collection
    {
        $query = Connection::where('connector_name', $connectorName);

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('is_shared', true);
            });
        }

        return $query->get()->map(fn($c) => $this->getConnection($c->id));
    }

    public function exists(string $connectionId): bool
    {
        return Connection::where('id', $connectionId)->exists();
    }

    // =========================================================================
    // OAUTH TOKEN MANAGEMENT
    // =========================================================================

    public function storeOAuthTokens(string $connectionId, array $tokens): bool
    {
        $connection = Connection::findOrFail($connectionId);

        $credentials = $this->decrypt($connection->credentials);
        $credentials = array_merge($credentials, [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'token_type' => $tokens['token_type'] ?? 'Bearer',
            'expires_at' => $tokens['expires_at'] ?? null,
            'scope' => $tokens['scope'] ?? null,
        ]);

        return $this->update($connectionId, $credentials);
    }

    public function getOAuthTokens(string $connectionId): ?array
    {
        $credentials = $this->retrieve($connectionId);

        if (!isset($credentials['access_token'])) {
            return null;
        }

        return [
            'access_token' => $credentials['access_token'],
            'refresh_token' => $credentials['refresh_token'] ?? null,
            'token_type' => $credentials['token_type'] ?? 'Bearer',
            'expires_at' => $credentials['expires_at'] ?? null,
            'scope' => $credentials['scope'] ?? null,
        ];
    }

    public function refreshOAuthToken(string $connectionId): array
    {
        $connection = Connection::findOrFail($connectionId);
        $credentials = $this->decrypt($connection->credentials);

        if (!isset($credentials['refresh_token'])) {
            throw new \App\Exceptions\Integration\TokenRefreshException(
                'No refresh token available'
            );
        }

        $connector = $this->connectorRegistry->get($connection->connector_name);
        if (!$connector || $connector->getAuthType() !== 'oauth2') {
            throw new \App\Exceptions\Integration\TokenRefreshException(
                'Connector does not support OAuth'
            );
        }

        // Get OAuth config and refresh
        $oauthConfig = $connector->getOAuthConfig();

        // Use auth provider to refresh
        $authProvider = app(\App\Contracts\Integration\AuthProviderContract::class);
        $newTokens = $authProvider->refreshToken($oauthConfig, $credentials['refresh_token']);

        // Store new tokens
        $this->storeOAuthTokens($connectionId, $newTokens);

        return $newTokens;
    }

    public function isTokenExpired(string $connectionId, int $buffer = 300): bool
    {
        $tokens = $this->getOAuthTokens($connectionId);

        if (!$tokens || !isset($tokens['expires_at'])) {
            return false; // No expiry info, assume not expired
        }

        $expiresAt = is_numeric($tokens['expires_at'])
            ? \Carbon\Carbon::createFromTimestamp($tokens['expires_at'])
            : \Carbon\Carbon::parse($tokens['expires_at']);

        return $expiresAt->subSeconds($buffer)->isPast();
    }

    // =========================================================================
    // VALIDATION & TESTING
    // =========================================================================

    public function testConnection(string $connectionId): array
    {
        $connection = Connection::findOrFail($connectionId);
        $credentials = $this->decrypt($connection->credentials);

        $connector = $this->connectorRegistry->get($connection->connector_name);
        if (!$connector) {
            return [
                'success' => false,
                'message' => 'Connector not found',
            ];
        }

        $result = $connector->testConnection($credentials);

        // Update connection status
        if ($result['success']) {
            $this->markAsVerified($connectionId);
        } else {
            $this->markAsFailed($connectionId, $result['message'] ?? 'Unknown error');
        }

        return $result;
    }

    public function markAsVerified(string $connectionId): void
    {
        Connection::where('id', $connectionId)->update([
            'status' => 'active',
            'verified_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $connectionId, string $reason): void
    {
        Connection::where('id', $connectionId)->update([
            'status' => 'error',
            'error_message' => $reason,
        ]);
    }

    // =========================================================================
    // SECURITY
    // =========================================================================

    public function rotateEncryption(string $connectionId): bool
    {
        $connection = Connection::findOrFail($connectionId);

        // Decrypt with current key
        $credentials = $this->decrypt($connection->credentials);

        // Re-encrypt (will use current app key)
        $newEncrypted = $this->encrypt($credentials);

        $connection->update([
            'credentials' => $newEncrypted,
            'encryption_rotated_at' => now(),
        ]);

        $this->logAccess($connectionId, 'encryption_rotated');

        return true;
    }

    public function getAccessLog(string $connectionId, int $limit = 50): Collection
    {
        return CredentialAccessLog::where('connection_id', $connectionId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function revokeAccess(string $connectionId): bool
    {
        // Delete all shares
        ConnectionShare::where('connection_id', $connectionId)->delete();

        // Mark as revoked
        Connection::where('id', $connectionId)->update([
            'status' => 'revoked',
        ]);

        $this->logAccess($connectionId, 'access_revoked');

        return true;
    }

    // =========================================================================
    // SHARING
    // =========================================================================

    public function shareWith(string $connectionId, int $userId, array $permissions = []): bool
    {
        ConnectionShare::updateOrCreate(
            ['connection_id' => $connectionId, 'user_id' => $userId],
            ['permissions' => $permissions]
        );

        $this->logAccess($connectionId, 'shared', ['user_id' => $userId]);

        return true;
    }

    public function unshareWith(string $connectionId, int $userId): bool
    {
        ConnectionShare::where('connection_id', $connectionId)
            ->where('user_id', $userId)
            ->delete();

        $this->logAccess($connectionId, 'unshared', ['user_id' => $userId]);

        return true;
    }

    public function getSharedUsers(string $connectionId): Collection
    {
        return ConnectionShare::where('connection_id', $connectionId)
            ->with('user')
            ->get()
            ->map(fn($share) => [
                'user_id' => $share->user_id,
                'user' => $share->user,
                'permissions' => $share->permissions,
                'shared_at' => $share->created_at,
            ]);
    }

    // =========================================================================
    // ENCRYPTION HELPERS
    // =========================================================================

    /**
     * Encrypt credentials.
     */
    protected function encrypt(array $credentials): string
    {
        return Crypt::encryptString(json_encode($credentials));
    }

    /**
     * Decrypt credentials.
     */
    protected function decrypt(string $encrypted): array
    {
        $decrypted = Crypt::decryptString($encrypted);

        return json_decode($decrypted, true) ?? [];
    }

    /**
     * Check if current user has access to connection.
     */
    protected function checkAccess(Connection $connection): void
    {
        $userId = auth()->id();

        if ($connection->is_shared) {
            return; // Shared connections are accessible
        }

        if ($connection->user_id === $userId) {
            return; // Owner has access
        }

        // Check shares
        $hasShare = ConnectionShare::where('connection_id', $connection->id)
            ->where('user_id', $userId)
            ->exists();

        if (!$hasShare) {
            throw new \App\Exceptions\Integration\UnauthorizedAccessException(
                'You do not have access to this connection'
            );
        }
    }

    /**
     * Log credential access.
     */
    protected function logAccess(string $connectionId, string $action, array $context = []): void
    {
        CredentialAccessLog::create([
            'connection_id' => $connectionId,
            'user_id' => auth()->id(),
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
        ]);
    }
}
