<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Permission extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'group',
        'category',
        'plugin_slug',
        'is_system',
        'is_active',
        'priority',
        'meta',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Common permission groups
     */
    public const GROUP_GENERAL = 'general';
    public const GROUP_CONTENT = 'content';
    public const GROUP_USERS = 'users';
    public const GROUP_SETTINGS = 'settings';
    public const GROUP_SYSTEM = 'system';
    public const GROUP_PLUGINS = 'plugins';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('granted')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('auth.providers.users.model', 'App\Models\User'), 'user_permissions')
            ->withPivot('granted', 'scope_type', 'scope_id', 'expires_at')
            ->withTimestamps();
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'permission_dependencies',
            'permission_id',
            'requires_permission_id'
        );
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'permission_dependencies',
            'requires_permission_id',
            'permission_id'
        );
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('group')->orderBy('priority')->orderBy('name');
    }

    public function scopeMatching(Builder $query, string $pattern): Builder
    {
        // Support wildcard matching: 'posts.*'
        $pattern = str_replace(['*', '.'], ['%', '\.'], $pattern);
        return $query->where('slug', 'like', $pattern);
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function findOrCreate(string $slug, array $attributes = []): self
    {
        return static::firstOrCreate(
            ['slug' => $slug],
            array_merge([
                'name' => static::slugToName($slug),
                'group' => static::slugToGroup($slug),
            ], $attributes)
        );
    }

    public static function slugToName(string $slug): string
    {
        // 'posts.create' -> 'Create Posts'
        $parts = explode('.', $slug);
        if (count($parts) >= 2) {
            return ucfirst($parts[1]) . ' ' . ucfirst($parts[0]);
        }
        return ucwords(str_replace(['.', '_', '-'], ' ', $slug));
    }

    public static function slugToGroup(string $slug): string
    {
        // 'posts.create' -> 'posts'
        $parts = explode('.', $slug);
        return $parts[0] ?? self::GROUP_GENERAL;
    }

    public static function getGroups(): array
    {
        return [
            self::GROUP_GENERAL => 'General',
            self::GROUP_CONTENT => 'Content',
            self::GROUP_USERS => 'Users',
            self::GROUP_SETTINGS => 'Settings',
            self::GROUP_SYSTEM => 'System',
            self::GROUP_PLUGINS => 'Plugins',
        ];
    }

    public static function getGrouped(): Collection
    {
        return static::active()->ordered()->get()->groupBy('group');
    }

    // =========================================================================
    // Instance Methods
    // =========================================================================

    /**
     * Check if all dependencies are met for a user
     */
    public function dependenciesMet($user): bool
    {
        foreach ($this->dependencies as $dependency) {
            if (!$user->hasPermission($dependency->slug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions that depend on this one
     */
    public function getDependentPermissions(): Collection
    {
        return $this->dependents;
    }

    /**
     * Add a dependency
     */
    public function requires(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = static::findBySlug($permission);
        }

        if ($permission && $permission->id !== $this->id) {
            $this->dependencies()->syncWithoutDetaching([$permission->id]);
        }

        return $this;
    }

    /**
     * Export to array for documentation
     */
    public function toDocumentation(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'group' => $this->group,
            'category' => $this->category,
            'dependencies' => $this->dependencies->pluck('slug')->toArray(),
        ];
    }
}
