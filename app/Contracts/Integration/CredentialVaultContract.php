<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

use Illuminate\Support\Collection;

/**
 * Contract for Credential Vault.
 *
 * Securely stores and manages credentials for external service connections.
 * All credentials are encrypted at rest and decrypted only when needed.
 *
 * Security Features:
 * - AES-256-GCM encryption
 * - Per-tenant encryption keys
 * - Automatic token refresh
 * - Access audit logging
 * - Credential rotation support
 *
 * @example Store credentials
 * ```php
 * $vault->store('my-telegram-bot', 'telegram', [
 *     'bot_token' => 'xxx:yyy',
 * ], $userId);
 * ```
 *
 * @example Retrieve credentials
 * ```php
 * $credentials = $vault->retrieve($connectionId);
 * ```
 */
interface CredentialVaultContract
{
    // =========================================================================
    // STORAGE
    // =========================================================================

    /**
     * Store credentials for a connection.
     *
     * @param string $name Connection name/label
     * @param string $connectorName Connector identifier
     * @param array $credentials Raw credentials to encrypt
     * @param int|null $userId Owner user ID (null for shared)
     * @param array $options Additional options
     * @return string Connection ID
     */
    public function store(
        string $name,
        string $connectorName,
        array $credentials,
        ?int $userId = null,
        array $options = []
    ): string;

    /**
     * Update credentials for a connection.
     *
     * @param string $connectionId Connection ID
     * @param array $credentials New credentials
     * @return bool
     */
    public function update(string $connectionId, array $credentials): bool;

    /**
     * Retrieve decrypted credentials.
     *
     * @param string $connectionId Connection ID
     * @return array Decrypted credentials
     * @throws \App\Exceptions\Integration\CredentialNotFoundException
     * @throws \App\Exceptions\Integration\CredentialDecryptionException
     */
    public function retrieve(string $connectionId): array;

    /**
     * Delete a connection and its credentials.
     *
     * @param string $connectionId Connection ID
     * @return bool
     */
    public function delete(string $connectionId): bool;

    // =========================================================================
    // CONNECTIONS
    // =========================================================================

    /**
     * Get a connection by ID.
     *
     * @param string $connectionId Connection ID
     * @return array|null Connection data (without decrypted credentials)
     */
    public function getConnection(string $connectionId): ?array;

    /**
     * Get all connections for a user.
     *
     * @param int|null $userId User ID (null for shared)
     * @return Collection
     */
    public function getConnections(?int $userId = null): Collection;

    /**
     * Get connections for a specific connector.
     *
     * @param string $connectorName Connector identifier
     * @param int|null $userId User ID
     * @return Collection
     */
    public function getConnectionsForConnector(string $connectorName, ?int $userId = null): Collection;

    /**
     * Check if connection exists.
     *
     * @param string $connectionId Connection ID
     * @return bool
     */
    public function exists(string $connectionId): bool;

    // =========================================================================
    // OAUTH TOKEN MANAGEMENT
    // =========================================================================

    /**
     * Store OAuth tokens.
     *
     * @param string $connectionId Connection ID
     * @param array $tokens ['access_token', 'refresh_token', 'expires_at', ...]
     * @return bool
     */
    public function storeOAuthTokens(string $connectionId, array $tokens): bool;

    /**
     * Get OAuth tokens.
     *
     * @param string $connectionId Connection ID
     * @return array|null
     */
    public function getOAuthTokens(string $connectionId): ?array;

    /**
     * Refresh OAuth token if expired.
     *
     * @param string $connectionId Connection ID
     * @return array New tokens
     * @throws \App\Exceptions\Integration\TokenRefreshException
     */
    public function refreshOAuthToken(string $connectionId): array;

    /**
     * Check if OAuth token is expired or expiring soon.
     *
     * @param string $connectionId Connection ID
     * @param int $buffer Seconds before expiry to consider expired
     * @return bool
     */
    public function isTokenExpired(string $connectionId, int $buffer = 300): bool;

    // =========================================================================
    // VALIDATION & TESTING
    // =========================================================================

    /**
     * Test if stored credentials are valid.
     *
     * @param string $connectionId Connection ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(string $connectionId): array;

    /**
     * Mark connection as verified/working.
     *
     * @param string $connectionId Connection ID
     * @return void
     */
    public function markAsVerified(string $connectionId): void;

    /**
     * Mark connection as failed.
     *
     * @param string $connectionId Connection ID
     * @param string $reason Failure reason
     * @return void
     */
    public function markAsFailed(string $connectionId, string $reason): void;

    // =========================================================================
    // SECURITY
    // =========================================================================

    /**
     * Rotate encryption key for a connection.
     *
     * @param string $connectionId Connection ID
     * @return bool
     */
    public function rotateEncryption(string $connectionId): bool;

    /**
     * Get credential access audit log.
     *
     * @param string $connectionId Connection ID
     * @param int $limit Limit results
     * @return Collection
     */
    public function getAccessLog(string $connectionId, int $limit = 50): Collection;

    /**
     * Revoke all access to a connection.
     *
     * @param string $connectionId Connection ID
     * @return bool
     */
    public function revokeAccess(string $connectionId): bool;

    // =========================================================================
    // SHARING
    // =========================================================================

    /**
     * Share connection with another user.
     *
     * @param string $connectionId Connection ID
     * @param int $userId User to share with
     * @param array $permissions Permissions to grant
     * @return bool
     */
    public function shareWith(string $connectionId, int $userId, array $permissions = []): bool;

    /**
     * Remove sharing for a user.
     *
     * @param string $connectionId Connection ID
     * @param int $userId User to remove
     * @return bool
     */
    public function unshareWith(string $connectionId, int $userId): bool;

    /**
     * Get users a connection is shared with.
     *
     * @param string $connectionId Connection ID
     * @return Collection
     */
    public function getSharedUsers(string $connectionId): Collection;
}
