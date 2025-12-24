<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'level',
        'parent_id',
        'is_default',
        'is_system',
        'is_active',
        'plugin_slug',
        'tenant_id',
        'color',
        'icon',
        'position',
        'meta',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    protected $attributes = [
        'color' => '#6B7280',
        'icon' => 'shield',
        'position' => 0,
    ];

    /**
     * Built-in roles
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_AUTHOR = 'author';
    public const ROLE_SUBSCRIBER = 'subscriber';
    public const ROLE_GUEST = 'guest';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('granted', 'granted_at', 'granted_by')
            ->withTimestamps();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function grantedPermissions(): BelongsToMany
    {
        return $this->permissions()->wherePivot('granted', true);
    }

    public function deniedPermissions(): BelongsToMany
    {
        return $this->permissions()->wherePivot('granted', false);
    }

    /**
     * Get all users with this role
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('auth.providers.users.model', 'App\Models\User'), 'user_roles')
            ->withPivot('scope_type', 'scope_id', 'expires_at', 'assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    /**
     * Get active users with this role (non-expired)
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()
            ->where(function ($query) {
                $query->whereNull('user_roles.expires_at')
                    ->orWhere('user_roles.expires_at', '>', now());
            });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // =========================================================================
    // Permission Management
    // =========================================================================

    /**
     * Grant a permission to this role
     */
    public function grantPermission(string|Permission|array $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::findOrCreate($permission);
            }

            $this->permissions()->syncWithoutDetaching([
                $permission->id => ['granted' => true]
            ]);
        }

        return $this;
    }

    /**
     * Deny a permission for this role
     */
    public function denyPermission(string|Permission|array $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::findBySlug($permission);
                if (!$permission) continue;
            }

            $this->permissions()->syncWithoutDetaching([
                $permission->id => ['granted' => false]
            ]);
        }

        return $this;
    }

    /**
     * Revoke a permission (remove entirely)
     */
    public function revokePermission(string|Permission|array $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::findBySlug($permission);
                if (!$permission) continue;
            }

            $this->permissions()->detach($permission->id);
        }

        return $this;
    }

    /**
     * Sync permissions (replace all)
     */
    public function syncPermissions(array $permissions): self
    {
        $syncData = [];

        foreach ($permissions as $permission => $granted) {
            if (is_int($permission)) {
                $permission = $granted;
                $granted = true;
            }

            if (is_string($permission)) {
                $perm = Permission::findOrCreate($permission);
                $permission = $perm->id;
            } elseif ($permission instanceof Permission) {
                $permission = $permission->id;
            }

            $syncData[$permission] = ['granted' => (bool) $granted];
        }

        $this->permissions()->sync($syncData);

        return $this;
    }

    /**
     * Check if role has a permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Check direct permission
        $permission = $this->permissions()
            ->where('slug', $permissionSlug)
            ->first();

        if ($permission) {
            return $permission->pivot->granted;
        }

        // Check parent role
        if ($this->parent) {
            return $this->parent->hasPermission($permissionSlug);
        }

        return false;
    }

    /**
     * Check if role has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if role has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions (including inherited)
     */
    public function getAllPermissions(): Collection
    {
        $permissions = $this->grantedPermissions;

        if ($this->parent) {
            $parentPermissions = $this->parent->getAllPermissions();
            $permissions = $permissions->merge($parentPermissions)->unique('id');
        }

        return $permissions;
    }

    /**
     * Get all permission slugs
     */
    public function getAllPermissionSlugs(): array
    {
        return $this->getAllPermissions()->pluck('slug')->toArray();
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

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeByLevel(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('level', $direction);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('level', 'desc')->orderBy('name');
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
                'name' => ucwords(str_replace(['_', '-'], ' ', $slug)),
            ], $attributes)
        );
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public static function getSuperAdmin(): ?self
    {
        return static::where('slug', self::ROLE_SUPER_ADMIN)->first();
    }

    // =========================================================================
    // Hierarchy
    // =========================================================================

    /**
     * Check if this role is higher than another
     */
    public function isHigherThan(Role $role): bool
    {
        return $this->level > $role->level;
    }

    /**
     * Check if this role is at least as high as another
     */
    public function isAtLeast(Role $role): bool
    {
        return $this->level >= $role->level;
    }

    /**
     * Get all ancestor roles
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendant roles
     */
    public function getDescendants(): Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    // =========================================================================
    // Scopes (Additional)
    // =========================================================================

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeForTenant(Builder $query, ?int $tenantId = null): Builder
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')
              ->orWhere('tenant_id', $tenantId);
        });
    }

    // =========================================================================
    // Caching
    // =========================================================================

    protected function getPermissionCacheKey(): string
    {
        $tenantId = $this->tenant_id ?? 'global';
        return "tenant.{$tenantId}.role.{$this->id}.permissions";
    }

    public function clearPermissionCache(): void
    {
        Cache::forget($this->getPermissionCacheKey());

        // Clear cache for all users with this role
        foreach ($this->users as $user) {
            if (method_exists($user, 'clearPermissionCache')) {
                $user->clearPermissionCache();
            }
        }

        // Clear cache for child roles
        foreach ($this->children as $child) {
            $child->clearPermissionCache();
        }
    }

    /**
     * Get all permissions with caching
     */
    public function getCachedPermissions(): Collection
    {
        $cacheKey = $this->getPermissionCacheKey();

        return Cache::remember($cacheKey, 3600, function () {
            return $this->getAllPermissions();
        });
    }

    // =========================================================================
    // Circular Inheritance Check
    // =========================================================================

    /**
     * Check if setting a parent would create circular inheritance
     */
    public function wouldCreateCircularInheritance(int $parentId): bool
    {
        if ($parentId === $this->id) {
            return true;
        }

        $parent = static::find($parentId);
        if (!$parent) {
            return false;
        }

        $visited = [$this->id];
        $current = $parent;

        while ($current) {
            if (in_array($current->id, $visited)) {
                return true;
            }
            $visited[] = $current->id;
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Get inherited permissions from parent role
     */
    public function getInheritedPermissions(): Collection
    {
        if (!$this->parent) {
            return collect();
        }

        return $this->parent->getAllPermissions()->map(function ($permission) {
            $permission->inherited = true;
            $permission->inherited_from = $this->parent->name;
            return $permission;
        });
    }

    // =========================================================================
    // Export/Import
    // =========================================================================

    /**
     * Export role to array for JSON export
     */
    public function toExportArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'level' => $this->level,
            'parent_slug' => $this->parent?->slug,
            'permissions' => $this->grantedPermissions->pluck('slug')->toArray(),
        ];
    }

    /**
     * Import role from array
     */
    public static function fromImportArray(array $data, ?int $tenantId = null): static
    {
        $parent = null;
        if (!empty($data['parent_slug'])) {
            $parent = static::where('slug', $data['parent_slug'])
                ->forTenant($tenantId)
                ->first();
        }

        $role = static::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#6B7280',
            'icon' => $data['icon'] ?? 'shield',
            'level' => $data['level'] ?? 0,
            'parent_id' => $parent?->id,
            'tenant_id' => $tenantId,
        ]);

        if (!empty($data['permissions'])) {
            $permissionIds = Permission::whereIn('slug', $data['permissions'])
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            $role->syncPermissions($permissionIds);
        }

        return $role;
    }

    /**
     * Duplicate a role with a new name
     */
    public function duplicate(string $newName, ?string $newSlug = null): static
    {
        $newSlug = $newSlug ?? \Str::slug($newName);

        $newRole = $this->replicate(['id', 'created_at', 'updated_at']);
        $newRole->name = $newName;
        $newRole->slug = $newSlug;
        $newRole->is_system = false;
        $newRole->is_default = false;
        $newRole->save();

        // Copy permissions
        $permissionData = [];
        foreach ($this->grantedPermissions as $permission) {
            $permissionData[$permission->id] = [
                'granted' => true,
                'granted_at' => now(),
                'granted_by' => auth()->id(),
            ];
        }
        $newRole->permissions()->sync($permissionData);

        return $newRole;
    }

    /**
     * Get user count for this role
     */
    public function getUserCount(): int
    {
        return $this->activeUsers()->count();
    }
}
