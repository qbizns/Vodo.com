<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use VodoCommerce\Auth\CommerceScopes;

/**
 * OAuthApplication - Third-party application registered for OAuth access.
 *
 * @property int $id
 * @property int $store_id
 * @property string $name
 * @property string $client_id
 * @property string $client_secret_hash
 * @property string $redirect_uris JSON array of allowed redirect URIs
 * @property array $scopes Granted OAuth scopes
 * @property string $status active, suspended, revoked
 * @property string|null $description
 * @property string|null $website
 * @property string|null $logo_url
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OAuthApplication extends Model
{
    protected $table = 'commerce_oauth_applications';

    protected $fillable = [
        'store_id',
        'name',
        'client_id',
        'client_secret_hash',
        'redirect_uris',
        'scopes',
        'status',
        'description',
        'website',
        'logo_url',
        'metadata',
        'approved_at',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'scopes' => 'array',
        'metadata' => 'array',
        'approved_at' => 'datetime',
    ];

    protected $hidden = [
        'client_secret_hash',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REVOKED = 'revoked';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(OAuthAccessToken::class, 'application_id');
    }

    public function authorizationCodes(): HasMany
    {
        return $this->hasMany(OAuthAuthorizationCode::class, 'application_id');
    }

    // =========================================================================
    // Key Generation
    // =========================================================================

    /**
     * Generate a new client ID.
     */
    public static function generateClientId(): string
    {
        return 'app_' . Str::random(24);
    }

    /**
     * Generate a client secret.
     */
    public static function generateClientSecret(): string
    {
        return 'secret_' . Str::random(48);
    }

    /**
     * Create a new OAuth application with credentials.
     *
     * @return array{application: OAuthApplication, client_id: string, client_secret: string}
     */
    public static function createWithCredentials(array $attributes): array
    {
        $clientId = static::generateClientId();
        $clientSecret = static::generateClientSecret();

        $app = static::create(array_merge($attributes, [
            'client_id' => $clientId,
            'client_secret_hash' => hash('sha256', $clientSecret),
            'status' => self::STATUS_ACTIVE,
        ]));

        return [
            'application' => $app,
            'client_id' => $clientId,
            'client_secret' => $clientSecret, // Only returned once!
        ];
    }

    /**
     * Regenerate client secret.
     *
     * @return string The new secret (only returned once)
     */
    public function regenerateSecret(): string
    {
        $newSecret = static::generateClientSecret();
        $this->update(['client_secret_hash' => hash('sha256', $newSecret)]);

        // Revoke all existing tokens
        $this->accessTokens()->update(['revoked' => true]);

        return $newSecret;
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Verify client secret.
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->client_secret_hash, hash('sha256', $secret));
    }

    /**
     * Check if the application is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if redirect URI is allowed.
     */
    public function isRedirectUriAllowed(string $uri): bool
    {
        if (empty($this->redirect_uris)) {
            return false;
        }

        // Exact match
        if (in_array($uri, $this->redirect_uris, true)) {
            return true;
        }

        // Check for localhost variations (development)
        $parsed = parse_url($uri);
        if (in_array($parsed['host'] ?? '', ['localhost', '127.0.0.1'], true)) {
            // Allow any port on localhost if localhost is in allowed URIs
            foreach ($this->redirect_uris as $allowedUri) {
                $allowedParsed = parse_url($allowedUri);
                if (in_array($allowedParsed['host'] ?? '', ['localhost', '127.0.0.1'], true)) {
                    // Same scheme and path
                    if (($parsed['scheme'] ?? 'http') === ($allowedParsed['scheme'] ?? 'http') &&
                        ($parsed['path'] ?? '/') === ($allowedParsed['path'] ?? '/')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if the application has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        return CommerceScopes::hasScope($this->scopes ?? [], $scope);
    }

    /**
     * Validate requested scopes against granted scopes.
     *
     * @param array $requestedScopes
     * @return array Invalid scopes
     */
    public function validateScopes(array $requestedScopes): array
    {
        $invalid = [];
        foreach ($requestedScopes as $scope) {
            if (!$this->hasScope($scope)) {
                $invalid[] = $scope;
            }
        }
        return $invalid;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Find by client ID.
     */
    public static function findByClientId(string $clientId): ?self
    {
        return static::where('client_id', $clientId)->first();
    }

    /**
     * Find active by client ID.
     */
    public static function findActiveByClientId(string $clientId): ?self
    {
        return static::active()->where('client_id', $clientId)->first();
    }
}
