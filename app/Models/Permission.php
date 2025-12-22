<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Permission extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'slug',
        'name',
        'label',
        'description',
        'group',
        'group_id',
        'category',
        'plugin_slug',
        'is_system',
        'is_active',
        'is_dangerous',
        'priority',
        'meta',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'is_dangerous' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
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

    public function permissionGroup(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('granted', 'granted_at', 'granted_by')
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

    public function scopeDangerous(Builder $query): Builder
    {
        return $query->where('is_dangerous', true);
    }

    public function scopeInPermissionGroup(Builder $query, int $groupId): Builder
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('slug', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('label', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
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
            'label' => $this->label,
            'description' => $this->description,
            'group' => $this->group,
            'category' => $this->category,
            'is_dangerous' => $this->is_dangerous,
            'dependencies' => $this->dependencies->pluck('slug')->toArray(),
        ];
    }

    // =========================================================================
    // Validation Methods
    // =========================================================================

    /**
     * Validate permission name/slug format
     * Format: module.action or module.submodule.action
     */
    public static function validateSlug(string $slug): bool
    {
        // Allow wildcard permissions like "invoices.*"
        if (str_ends_with($slug, '.*')) {
            $prefix = rtrim($slug, '.*');
            return (bool) preg_match('/^[a-z][a-z0-9]*(\.[a-z][a-z0-9_]*)*$/', $prefix);
        }

        return (bool) preg_match('/^[a-z][a-z0-9]*(\.[a-z][a-z0-9_]*)+$/', $slug);
    }

    /**
     * Check if this is a wildcard permission
     */
    public function isWildcard(): bool
    {
        return str_ends_with($this->slug, '.*');
    }

    /**
     * Get all permissions that this wildcard covers
     */
    public function getWildcardMatches(): Collection
    {
        if (!$this->isWildcard()) {
            return collect([$this]);
        }

        $prefix = rtrim($this->slug, '.*');

        return static::where('slug', 'like', $prefix . '.%')
            ->where('slug', '!=', $this->slug)
            ->get();
    }

    /**
     * Get all required dependencies for this permission (recursive)
     */
    public function getAllDependencies(): Collection
    {
        $dependencies = collect();
        $toProcess = $this->dependencies;
        $processed = collect([$this->id]);

        while ($toProcess->isNotEmpty()) {
            $current = $toProcess->shift();

            if ($processed->contains($current->id)) {
                continue;
            }

            $processed->push($current->id);
            $dependencies->push($current);

            $toProcess = $toProcess->merge($current->dependencies);
        }

        return $dependencies;
    }

    /**
     * Get the label or generate one from slug
     */
    public function getLabelAttribute($value): string
    {
        return $value ?? self::slugToName($this->slug);
    }

    /**
     * Get grouped permissions for UI display
     */
    public static function getGroupedForUI(): array
    {
        $groups = PermissionGroup::active()
            ->with(['permissions' => fn($q) => $q->active()->orderBy('priority')->orderBy('name')])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $groups->map(fn($group) => [
            'id' => $group->id,
            'slug' => $group->slug,
            'label' => $group->name,
            'icon' => $group->icon,
            'plugin' => $group->plugin_slug,
            'permissions' => $group->permissions->map(fn($p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'label' => $p->label,
                'description' => $p->description,
                'is_dangerous' => $p->is_dangerous,
            ])->toArray(),
        ])->toArray();
    }
}
