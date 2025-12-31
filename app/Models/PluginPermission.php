<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PluginScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plugin Permission - Defines what a plugin can access.
 *
 * @property int $id
 * @property string $plugin_slug
 * @property string $scope
 * @property string|null $resource
 * @property string $access_level
 * @property array|null $constraints
 * @property bool $is_granted
 * @property \Carbon\Carbon|null $granted_at
 * @property int|null $granted_by
 * @property \Carbon\Carbon|null $revoked_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PluginPermission extends Model
{
    public const ACCESS_READ = 'read';
    public const ACCESS_WRITE = 'write';
    public const ACCESS_DELETE = 'delete';
    public const ACCESS_ADMIN = 'admin';

    protected $fillable = [
        'plugin_slug',
        'scope',
        'resource',
        'access_level',
        'constraints',
        'is_granted',
        'granted_at',
        'granted_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'constraints' => 'array',
            'is_granted' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class, 'plugin_slug', 'slug');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeGranted($query)
    {
        return $query->where('is_granted', true)->whereNull('revoked_at');
    }

    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    public function scopeForPlugin($query, string $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeForScope($query, string|PluginScope $scope)
    {
        $scopeValue = $scope instanceof PluginScope ? $scope->value : $scope;
        return $query->where('scope', $scopeValue);
    }

    public function scopeForResource($query, ?string $resource)
    {
        if ($resource === null) {
            return $query->whereNull('resource');
        }
        return $query->where('resource', $resource);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Get the PluginScope enum for this permission.
     */
    public function getPluginScope(): ?PluginScope
    {
        return PluginScope::tryFrom($this->scope);
    }

    /**
     * Check if this permission is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_granted && $this->revoked_at === null;
    }

    /**
     * Grant this permission.
     */
    public function grant(?int $grantedBy = null): bool
    {
        return $this->update([
            'is_granted' => true,
            'granted_at' => now(),
            'granted_by' => $grantedBy,
            'revoked_at' => null,
        ]);
    }

    /**
     * Revoke this permission.
     */
    public function revoke(): bool
    {
        return $this->update([
            'is_granted' => false,
            'revoked_at' => now(),
        ]);
    }

    /**
     * Check if this permission allows a specific action.
     */
    public function allows(string $action): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $hierarchy = [
            self::ACCESS_READ => 1,
            self::ACCESS_WRITE => 2,
            self::ACCESS_DELETE => 3,
            self::ACCESS_ADMIN => 4,
        ];

        $requiredLevel = $hierarchy[$action] ?? 0;
        $grantedLevel = $hierarchy[$this->access_level] ?? 0;

        return $grantedLevel >= $requiredLevel;
    }

    /**
     * Check if constraints are satisfied.
     */
    public function checkConstraints(array $context = []): bool
    {
        if (empty($this->constraints)) {
            return true;
        }

        foreach ($this->constraints as $key => $value) {
            if (!isset($context[$key]) || $context[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
