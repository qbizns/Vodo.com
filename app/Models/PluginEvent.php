<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PluginEvent Model - Audit log for plugin lifecycle events.
 */
class PluginEvent extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    protected $fillable = [
        'plugin_id',
        'plugin_slug',
        'event',
        'version',
        'previous_version',
        'user_id',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Event type constants.
     */
    public const EVENT_INSTALLED = 'installed';
    public const EVENT_ACTIVATED = 'activated';
    public const EVENT_DEACTIVATED = 'deactivated';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_UNINSTALLED = 'uninstalled';
    public const EVENT_ERROR = 'error';
    public const EVENT_SETTINGS_CHANGED = 'settings_changed';
    public const EVENT_LICENSE_ACTIVATED = 'license_activated';
    public const EVENT_LICENSE_EXPIRED = 'license_expired';

    // ==================== Relationships ====================

    /**
     * Get the plugin this event belongs to.
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    /**
     * Get the user who triggered this event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Scopes ====================

    /**
     * Scope to filter by plugin slug.
     */
    public function scopeForPlugin($query, string $slug)
    {
        return $query->where('plugin_slug', $slug);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeOfType($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to get recent events.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== Accessors ====================

    /**
     * Get the event label for display.
     */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            self::EVENT_INSTALLED => 'Plugin Installed',
            self::EVENT_ACTIVATED => 'Plugin Activated',
            self::EVENT_DEACTIVATED => 'Plugin Deactivated',
            self::EVENT_UPDATED => 'Plugin Updated',
            self::EVENT_UNINSTALLED => 'Plugin Uninstalled',
            self::EVENT_ERROR => 'Error Occurred',
            self::EVENT_SETTINGS_CHANGED => 'Settings Changed',
            self::EVENT_LICENSE_ACTIVATED => 'License Activated',
            self::EVENT_LICENSE_EXPIRED => 'License Expired',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }

    /**
     * Get the event icon for display.
     */
    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            self::EVENT_INSTALLED => 'download',
            self::EVENT_ACTIVATED => 'check-circle',
            self::EVENT_DEACTIVATED => 'pause-circle',
            self::EVENT_UPDATED => 'refresh-cw',
            self::EVENT_UNINSTALLED => 'trash-2',
            self::EVENT_ERROR => 'alert-circle',
            self::EVENT_SETTINGS_CHANGED => 'settings',
            self::EVENT_LICENSE_ACTIVATED => 'key',
            self::EVENT_LICENSE_EXPIRED => 'alert-triangle',
            default => 'info',
        };
    }

    /**
     * Get the event color class for display.
     */
    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            self::EVENT_INSTALLED, self::EVENT_ACTIVATED => 'success',
            self::EVENT_DEACTIVATED, self::EVENT_UNINSTALLED => 'secondary',
            self::EVENT_UPDATED, self::EVENT_SETTINGS_CHANGED => 'info',
            self::EVENT_ERROR, self::EVENT_LICENSE_EXPIRED => 'danger',
            self::EVENT_LICENSE_ACTIVATED => 'primary',
            default => 'secondary',
        };
    }

    // ==================== Static Methods ====================

    /**
     * Log a plugin event.
     */
    public static function log(
        Plugin $plugin,
        string $event,
        array $payload = [],
        ?string $previousVersion = null
    ): self {
        return self::create([
            'plugin_id' => $plugin->id,
            'plugin_slug' => $plugin->slug,
            'event' => $event,
            'version' => $plugin->version,
            'previous_version' => $previousVersion,
            'user_id' => auth()->id(),
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get all available event types.
     */
    public static function getEventTypes(): array
    {
        return [
            self::EVENT_INSTALLED => 'Installed',
            self::EVENT_ACTIVATED => 'Activated',
            self::EVENT_DEACTIVATED => 'Deactivated',
            self::EVENT_UPDATED => 'Updated',
            self::EVENT_UNINSTALLED => 'Uninstalled',
            self::EVENT_ERROR => 'Error',
            self::EVENT_SETTINGS_CHANGED => 'Settings Changed',
            self::EVENT_LICENSE_ACTIVATED => 'License Activated',
            self::EVENT_LICENSE_EXPIRED => 'License Expired',
        ];
    }
}
