<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * PluginSetting Model - Stores key-value settings for plugins.
 */
class PluginSetting extends Model
{
    protected $fillable = [
        'plugin_id',
        'key',
        'value',
        'group',
        'type',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    // ==================== Relationships ====================

    /**
     * Get the plugin this setting belongs to.
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    // ==================== Accessors & Mutators ====================

    /**
     * Encrypt sensitive values when setting.
     */
    public function setValueAttribute($value): void
    {
        if ($this->is_encrypted && $value) {
            $value = Crypt::encryptString($value);
        }
        $this->attributes['value'] = $value;
    }

    /**
     * Decrypt sensitive values when getting.
     */
    public function getValueAttribute($value): mixed
    {
        if ($this->is_encrypted && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return $value;
    }

    /**
     * Get the typed value.
     */
    public function getTypedValueAttribute(): mixed
    {
        $value = $this->value;

        return match ($this->type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }

    // ==================== Scopes ====================

    /**
     * Scope to filter by group.
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to filter by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }
}
