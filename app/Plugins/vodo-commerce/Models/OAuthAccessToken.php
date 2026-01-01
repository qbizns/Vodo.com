<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use VodoCommerce\Auth\CommerceScopes;

/**
 * OAuthAccessToken - Access token for OAuth-authenticated API requests.
 *
 * @property int $id
 * @property int $application_id
 * @property int $store_id
 * @property string $token_hash
 * @property string|null $refresh_token_hash
 * @property array $scopes Scopes granted to this token
 * @property bool $revoked
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $refresh_expires_at
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OAuthAccessToken extends Model
{
    protected $table = 'commerce_oauth_access_tokens';

    protected $fillable = [
        'application_id',
        'store_id',
        'token_hash',
        'refresh_token_hash',
        'scopes',
        'revoked',
        'expires_at',
        'refresh_expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'token_hash',
        'refresh_token_hash',
    ];

    /** Access token lifetime in seconds (1 hour) */
    public const TOKEN_LIFETIME = 3600;

    /** Refresh token lifetime in seconds (30 days) */
    public const REFRESH_TOKEN_LIFETIME = 2592000;

    // =========================================================================
    // Relationships
    // =========================================================================

    public function application(): BelongsTo
    {
        return $this->belongsTo(OAuthApplication::class, 'application_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // =========================================================================
    // Token Generation
    // =========================================================================

    /**
     * Generate an access token.
     */
    public static function generateAccessToken(): string
    {
        return 'at_' . Str::random(64);
    }

    /**
     * Generate a refresh token.
     */
    public static function generateRefreshToken(): string
    {
        return 'rt_' . Str::random(64);
    }

    /**
     * Create a new access token with refresh token.
     *
     * @return array{token: OAuthAccessToken, access_token: string, refresh_token: string}
     */
    public static function createTokenPair(int $applicationId, int $storeId, array $scopes): array
    {
        $accessToken = static::generateAccessToken();
        $refreshToken = static::generateRefreshToken();

        $token = static::create([
            'application_id' => $applicationId,
            'store_id' => $storeId,
            'token_hash' => hash('sha256', $accessToken),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => now()->addSeconds(self::TOKEN_LIFETIME),
            'refresh_expires_at' => now()->addSeconds(self::REFRESH_TOKEN_LIFETIME),
        ]);

        return [
            'token' => $token,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::TOKEN_LIFETIME,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Refresh the access token using refresh token.
     *
     * @return array{token: OAuthAccessToken, access_token: string, refresh_token: string}
     */
    public function refresh(): array
    {
        if ($this->revoked) {
            throw new \RuntimeException('Token has been revoked');
        }

        if ($this->refresh_expires_at && $this->refresh_expires_at->isPast()) {
            throw new \RuntimeException('Refresh token has expired');
        }

        // Generate new tokens
        $accessToken = static::generateAccessToken();
        $refreshToken = static::generateRefreshToken();

        // Revoke old token
        $this->update(['revoked' => true]);

        // Create new token
        $newToken = static::create([
            'application_id' => $this->application_id,
            'store_id' => $this->store_id,
            'token_hash' => hash('sha256', $accessToken),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'scopes' => $this->scopes,
            'revoked' => false,
            'expires_at' => now()->addSeconds(self::TOKEN_LIFETIME),
            'refresh_expires_at' => now()->addSeconds(self::REFRESH_TOKEN_LIFETIME),
        ]);

        return [
            'token' => $newToken,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::TOKEN_LIFETIME,
            'token_type' => 'Bearer',
        ];
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Check if the token is valid (not expired and not revoked).
     */
    public function isValid(): bool
    {
        if ($this->revoked) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verify an access token.
     */
    public static function verifyAccessToken(string $token): ?self
    {
        $hash = hash('sha256', $token);
        $tokenModel = static::where('token_hash', $hash)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($tokenModel) {
            $tokenModel->recordUsage();
        }

        return $tokenModel;
    }

    /**
     * Find by refresh token.
     */
    public static function findByRefreshToken(string $refreshToken): ?self
    {
        $hash = hash('sha256', $refreshToken);
        return static::where('refresh_token_hash', $hash)
            ->where('revoked', false)
            ->where('refresh_expires_at', '>', now())
            ->first();
    }

    /**
     * Check if token has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        return CommerceScopes::hasScope($this->scopes ?? [], $scope);
    }

    // =========================================================================
    // Usage Tracking
    // =========================================================================

    /**
     * Record token usage.
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke this token.
     */
    public function revoke(): void
    {
        $this->update(['revoked' => true]);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('revoked', false)
            ->where('expires_at', '>', now());
    }

    public function scopeForApplication(Builder $query, int $applicationId): Builder
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }
}
