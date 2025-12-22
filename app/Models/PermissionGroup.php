<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Permission Group Model
 *
 * Groups permissions together for UI organization and management.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $plugin_slug
 * @property string $icon
 * @property int $position
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 */
class PermissionGroup extends Model
{
    public $timestamps = false;

    protected $table = 'permission_groups';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'plugin_slug',
        'icon',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
    ];

    protected $attributes = [
        'icon' => 'folder',
        'position' => 0,
        'is_active' => true,
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'group_id')->orderBy('priority')->orderBy('name');
    }

    public function activePermissions(): HasMany
    {
        return $this->permissions()->where('is_active', true);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeCore(Builder $query): Builder
    {
        return $query->whereNull('plugin_slug');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%")
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
                'name' => ucwords(str_replace(['-', '_'], ' ', $slug)),
            ], $attributes)
        );
    }

    /**
     * Get all groups with their permissions for UI
     */
    public static function getGroupedPermissions(): array
    {
        return static::active()
            ->with(['permissions' => fn($q) => $q->active()])
            ->ordered()
            ->get()
            ->map(fn($group) => [
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
            ])
            ->toArray();
    }

    /**
     * Get all groups organized by plugin
     */
    public static function getByPlugin(): Collection
    {
        return static::active()
            ->ordered()
            ->get()
            ->groupBy(fn($group) => $group->plugin_slug ?? 'core');
    }

    // =========================================================================
    // Instance Methods
    // =========================================================================

    /**
     * Get permission count
     */
    public function getPermissionCount(): int
    {
        return $this->activePermissions()->count();
    }

    /**
     * Check if this group belongs to a plugin
     */
    public function isPluginGroup(): bool
    {
        return $this->plugin_slug !== null;
    }

    /**
     * Deactivate the group and all its permissions
     */
    public function deactivate(): bool
    {
        $this->permissions()->update(['is_active' => false]);
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Reactivate the group and all its permissions
     */
    public function reactivate(): bool
    {
        $this->permissions()->update(['is_active' => true]);
        $this->is_active = true;
        return $this->save();
    }
}
