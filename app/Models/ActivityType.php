<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Activity Type - Defines types of activities (Call, Meeting, Email, Task, etc.)
 */
class ActivityType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'default_days',
        'default_note',
        'is_system',
        'plugin_slug',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'is_system' => 'boolean',
    ];

    /**
     * Built-in activity types.
     */
    public const TYPE_CALL = 'call';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_EMAIL = 'email';
    public const TYPE_TODO = 'todo';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_UPLOAD = 'upload';

    /**
     * Get activities of this type.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get default due date based on type.
     */
    public function getDefaultDueDate(): \Carbon\Carbon
    {
        return now()->addDays($this->default_days ?? 1);
    }

    /**
     * Scope for plugin types.
     */
    public function scopeForPlugin($query, string $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }
}
