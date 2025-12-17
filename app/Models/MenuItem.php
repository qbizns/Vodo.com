<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'slug',
        'label',
        'title',
        'type',
        'route',
        'route_params',
        'url',
        'action',
        'target',
        'icon',
        'icon_type',
        'badge_text',
        'badge_type',
        'badge_callback',
        'roles',
        'permissions',
        'visibility_callback',
        'active_patterns',
        'active_callback',
        'order',
        'depth',
        'plugin_slug',
        'is_system',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'route_params' => 'array',
        'roles' => 'array',
        'permissions' => 'array',
        'active_patterns' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Runtime children (set during tree building)
     */
    public ?Collection $children = null;

    /**
     * Item types
     */
    public const TYPE_ROUTE = 'route';
    public const TYPE_URL = 'url';
    public const TYPE_ACTION = 'action';
    public const TYPE_DIVIDER = 'divider';
    public const TYPE_HEADER = 'header';
    public const TYPE_DROPDOWN = 'dropdown';

    /**
     * Badge types
     */
    public const BADGE_PRIMARY = 'primary';
    public const BADGE_SECONDARY = 'secondary';
    public const BADGE_SUCCESS = 'success';
    public const BADGE_WARNING = 'warning';
    public const BADGE_DANGER = 'danger';
    public const BADGE_INFO = 'info';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function childItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    // =========================================================================
    // URL Generation
    // =========================================================================

    /**
     * Get the URL for this menu item
     */
    public function getUrl(): ?string
    {
        return match ($this->type) {
            self::TYPE_ROUTE => $this->getRouteUrl(),
            self::TYPE_URL => $this->url,
            self::TYPE_ACTION => "javascript:{$this->action}",
            self::TYPE_DROPDOWN => '#',
            default => null,
        };
    }

    /**
     * Generate URL from route
     */
    protected function getRouteUrl(): ?string
    {
        if (!$this->route) {
            return null;
        }

        try {
            $params = $this->route_params ?? [];
            return route($this->route, $params);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if this item has a valid link
     */
    public function hasLink(): bool
    {
        return in_array($this->type, [self::TYPE_ROUTE, self::TYPE_URL, self::TYPE_ACTION]);
    }

    // =========================================================================
    // Badge Handling
    // =========================================================================

    /**
     * Get badge text (static or dynamic)
     */
    public function getBadge(): ?string
    {
        if ($this->badge_callback) {
            return $this->executeBadgeCallback();
        }
        return $this->badge_text;
    }

    /**
     * Execute badge callback
     */
    protected function executeBadgeCallback(): ?string
    {
        try {
            [$class, $method] = $this->parseCallback($this->badge_callback);
            $instance = app($class);
            return (string) $instance->$method($this);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get badge CSS class
     */
    public function getBadgeClass(): string
    {
        return match ($this->badge_type) {
            self::BADGE_PRIMARY => 'badge bg-primary',
            self::BADGE_SECONDARY => 'badge bg-secondary',
            self::BADGE_SUCCESS => 'badge bg-success',
            self::BADGE_WARNING => 'badge bg-warning text-dark',
            self::BADGE_DANGER => 'badge bg-danger',
            self::BADGE_INFO => 'badge bg-info',
            default => 'badge bg-secondary',
        };
    }

    // =========================================================================
    // Visibility
    // =========================================================================

    /**
     * Check if user can see this item
     */
    public function isVisibleTo($user = null): bool
    {
        $user = $user ?? auth()->user();

        if (in_array($this->type, [self::TYPE_DIVIDER, self::TYPE_HEADER])) {
            return true;
        }

        if ($this->visibility_callback) {
            if (!$this->executeVisibilityCallback($user)) {
                return false;
            }
        }

        if ($this->roles && !empty($this->roles)) {
            if (!$user) return false;
            $hasRole = false;
            foreach ($this->roles as $role) {
                if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }
            if (!$hasRole) return false;
        }

        if ($this->permissions && !empty($this->permissions)) {
            if (!$user) return false;
            foreach ($this->permissions as $permission) {
                if (method_exists($user, 'can') && !$user->can($permission)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Execute visibility callback
     */
    protected function executeVisibilityCallback($user): bool
    {
        try {
            [$class, $method] = $this->parseCallback($this->visibility_callback);
            $instance = app($class);
            return (bool) $instance->$method($this, $user);
        } catch (\Exception $e) {
            return true;
        }
    }

    // =========================================================================
    // Active State
    // =========================================================================

    /**
     * Check if this item is active
     */
    public function isActive(): bool
    {
        if ($this->active_callback) {
            return $this->executeActiveCallback();
        }

        if ($this->active_patterns && !empty($this->active_patterns)) {
            $currentPath = request()->path();
            foreach ($this->active_patterns as $pattern) {
                $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
                if (preg_match("/^{$regex}$/", $currentPath)) {
                    return true;
                }
            }
            return false;
        }

        $itemUrl = $this->getUrl();
        if ($itemUrl && $itemUrl !== '#') {
            $currentUrl = request()->url();
            if ($currentUrl === $itemUrl) {
                return true;
            }
            if ($this->route && request()->routeIs($this->route . '*')) {
                return true;
            }
        }

        if ($this->children && $this->children->isNotEmpty()) {
            foreach ($this->children as $child) {
                if ($child->isActive()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Execute active callback
     */
    protected function executeActiveCallback(): bool
    {
        try {
            [$class, $method] = $this->parseCallback($this->active_callback);
            $instance = app($class);
            return (bool) $instance->$method($this);
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // Icon Handling
    // =========================================================================

    /**
     * Get icon HTML
     */
    public function getIconHtml(): string
    {
        if (!$this->icon) {
            return '';
        }

        return match ($this->icon_type) {
            'svg' => $this->icon,
            'image' => "<img src=\"{$this->icon}\" class=\"menu-icon\" alt=\"\">",
            default => "<i class=\"{$this->icon}\"></i>",
        };
    }

    // =========================================================================
    // Utilities
    // =========================================================================

    /**
     * Parse callback string (Class@method)
     */
    protected function parseCallback(string $callback): array
    {
        if (str_contains($callback, '@')) {
            return explode('@', $callback, 2);
        }
        return [$callback, '__invoke'];
    }

    /**
     * Check if item has children
     */
    public function hasChildren(): bool
    {
        if ($this->children !== null) {
            return $this->children->isNotEmpty();
        }
        return $this->childItems()->where('is_active', true)->exists();
    }

    /**
     * Get all ancestor items
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent;
        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }
        return $ancestors;
    }

    /**
     * Get breadcrumb trail
     */
    public function getBreadcrumb(): array
    {
        $breadcrumb = [];
        foreach ($this->getAncestors() as $ancestor) {
            $breadcrumb[] = ['label' => $ancestor->label, 'url' => $ancestor->getUrl()];
        }
        $breadcrumb[] = ['label' => $this->label, 'url' => $this->getUrl()];
        return $breadcrumb;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForMenu(Builder $query, int $menuId): Builder
    {
        return $query->where('menu_id', $menuId);
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('label');
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    public static function findBySlug(int $menuId, string $slug): ?self
    {
        return static::where('menu_id', $menuId)->where('slug', $slug)->first();
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_ROUTE => 'Named Route',
            self::TYPE_URL => 'Direct URL',
            self::TYPE_ACTION => 'JavaScript Action',
            self::TYPE_DIVIDER => 'Divider',
            self::TYPE_HEADER => 'Section Header',
            self::TYPE_DROPDOWN => 'Dropdown Parent',
        ];
    }

    public static function getBadgeTypes(): array
    {
        return [
            self::BADGE_PRIMARY => 'Primary',
            self::BADGE_SECONDARY => 'Secondary',
            self::BADGE_SUCCESS => 'Success',
            self::BADGE_WARNING => 'Warning',
            self::BADGE_DANGER => 'Danger',
            self::BADGE_INFO => 'Info',
        ];
    }
}
