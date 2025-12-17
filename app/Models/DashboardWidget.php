<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_type',
        'user_id',
        'dashboard',
        'widget_id',
        'plugin_slug',
        'position',
        'col',
        'row',
        'width',
        'height',
        'settings',
        'visible',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'visible' => 'boolean',
        'position' => 'integer',
        'col' => 'integer',
        'row' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Scope to get widgets for a specific user and dashboard.
     */
    public function scopeForUser($query, string $userType, int $userId, string $dashboard = 'main')
    {
        return $query->where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('dashboard', $dashboard)
            ->where('visible', true)
            ->orderBy('position');
    }

    /**
     * Scope to get widgets for main dashboard.
     */
    public function scopeMainDashboard($query, string $userType, int $userId)
    {
        return $query->forUser($userType, $userId, 'main');
    }

    /**
     * Scope to get widgets for a plugin dashboard.
     */
    public function scopePluginDashboard($query, string $userType, int $userId, string $pluginSlug)
    {
        return $query->forUser($userType, $userId, $pluginSlug);
    }

    /**
     * Get the plugin this widget belongs to.
     */
    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'plugin_slug', 'slug');
    }

    /**
     * Check if this widget is from a plugin.
     */
    public function isFromPlugin(): bool
    {
        return !empty($this->plugin_slug);
    }

    /**
     * Get widget setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Set widget setting value.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    /**
     * Get default widgets for main dashboard.
     */
    public static function getDefaultMainWidgets(): array
    {
        return [
            [
                'widget_id' => 'welcome',
                'plugin_slug' => null,
                'position' => 0,
                'col' => 0,
                'row' => 0,
                'width' => 4,
                'height' => 1,
            ],
        ];
    }
}
