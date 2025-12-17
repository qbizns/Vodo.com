<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Menu extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'location', 'show_icons', 'show_badges',
        'collapsible', 'roles', 'permissions', 'is_active', 'priority', 'meta',
    ];

    protected $casts = [
        'show_icons' => 'boolean', 'show_badges' => 'boolean', 'collapsible' => 'boolean',
        'roles' => 'array', 'permissions' => 'array', 'is_active' => 'boolean', 'meta' => 'array',
    ];

    public const LOCATION_SIDEBAR = 'sidebar';
    public const LOCATION_TOPBAR = 'topbar';
    public const LOCATION_FOOTER = 'footer';
    public const LOCATION_DROPDOWN = 'dropdown';

    public function items(): HasMany { return $this->hasMany(MenuItem::class); }

    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->where('is_active', true)->orderBy('order');
    }

    public function isVisibleTo($user = null): bool
    {
        $user = $user ?? auth()->user();
        if ($this->roles && !empty($this->roles)) {
            if (!$user) return false;
            $hasRole = false;
            foreach ($this->roles as $role) {
                if (method_exists($user, 'hasRole') && $user->hasRole($role)) { $hasRole = true; break; }
            }
            if (!$hasRole) return false;
        }
        if ($this->permissions && !empty($this->permissions)) {
            if (!$user) return false;
            foreach ($this->permissions as $p) {
                if (method_exists($user, 'can') && !$user->can($p)) return false;
            }
        }
        return true;
    }

    public function getTree($user = null): Collection
    {
        $items = $this->items()->where('is_active', true)->orderBy('order')->get();
        return $this->buildTree($items, null, $user);
    }

    protected function buildTree(Collection $items, ?int $parentId, $user = null): Collection
    {
        return $items->where('parent_id', $parentId)->filter(fn($item) => $item->isVisibleTo($user))
            ->map(function ($item) use ($items, $user) {
                $item->children = $this->buildTree($items, $item->id, $user);
                return $item;
            })->values();
    }

    public function getFlattenedItems($user = null): Collection
    {
        return $this->flattenTree($this->getTree($user));
    }

    protected function flattenTree(Collection $items, int $depth = 0): Collection
    {
        $result = collect();
        foreach ($items as $item) {
            $item->depth = $depth;
            $result->push($item);
            if ($item->children && $item->children->isNotEmpty()) {
                $result = $result->merge($this->flattenTree($item->children, $depth + 1));
            }
        }
        return $result;
    }

    public function scopeActive(Builder $q): Builder { return $q->where('is_active', true); }
    public function scopeLocation(Builder $q, string $loc): Builder { return $q->where('location', $loc); }
    public function scopeOrdered(Builder $q): Builder { return $q->orderBy('priority')->orderBy('name'); }

    public static function findBySlug(string $slug): ?self { return static::where('slug', $slug)->first(); }

    public static function getLocations(): array
    {
        return [
            self::LOCATION_SIDEBAR => 'Sidebar', self::LOCATION_TOPBAR => 'Top Bar',
            self::LOCATION_FOOTER => 'Footer', self::LOCATION_DROPDOWN => 'Dropdown',
        ];
    }

    public static function findOrCreate(string $slug, array $attrs = []): self
    {
        return static::firstOrCreate(['slug' => $slug], array_merge([
            'name' => ucwords(str_replace(['_', '-'], ' ', $slug)), 'location' => self::LOCATION_SIDEBAR,
        ], $attrs));
    }
}
