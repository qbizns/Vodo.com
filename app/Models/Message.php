<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Message - Discussion thread messages attached to records (like Odoo chatter).
 * 
 * Supports:
 * - User comments
 * - System notifications
 * - Field change tracking
 * - Mentions and notifications
 * - Attachments
 */
class Message extends Model
{
    protected $fillable = [
        'messageable_type',
        'messageable_id',
        'message_type',
        'subject',
        'body',
        'author_id',
        'tracking_values',
        'attachments',
        'mentions',
        'is_internal',
        'is_note',
        'parent_id',
    ];

    protected $casts = [
        'tracking_values' => 'array',
        'attachments' => 'array',
        'mentions' => 'array',
        'is_internal' => 'boolean',
        'is_note' => 'boolean',
    ];

    /**
     * Message types.
     */
    public const TYPE_COMMENT = 'comment';
    public const TYPE_NOTIFICATION = 'notification';
    public const TYPE_EMAIL = 'email';
    public const TYPE_TRACKING = 'tracking';
    public const TYPE_ACTIVITY = 'activity';
    public const TYPE_SYSTEM = 'system';

    /**
     * Get the record this message belongs to.
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the author.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get parent message (for threading).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get replies.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Check if is a tracking message.
     */
    public function isTracking(): bool
    {
        return $this->message_type === self::TYPE_TRACKING;
    }

    /**
     * Scope for comments only.
     */
    public function scopeComments($query)
    {
        return $query->where('message_type', self::TYPE_COMMENT);
    }

    /**
     * Scope for tracking messages.
     */
    public function scopeTracking($query)
    {
        return $query->where('message_type', self::TYPE_TRACKING);
    }

    /**
     * Scope for internal messages.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope for public messages.
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Get formatted tracking values.
     */
    public function getFormattedTracking(): array
    {
        $formatted = [];
        foreach ($this->tracking_values ?? [] as $track) {
            $formatted[] = [
                'field' => $track['field'] ?? 'unknown',
                'field_label' => $track['field_label'] ?? ucfirst($track['field'] ?? ''),
                'old_value' => $track['old_value'] ?? null,
                'new_value' => $track['new_value'] ?? null,
                'old_display' => $track['old_display'] ?? $track['old_value'] ?? '-',
                'new_display' => $track['new_display'] ?? $track['new_value'] ?? '-',
            ];
        }
        return $formatted;
    }

    /**
     * Parse mentions from body.
     */
    public function parseMentions(): array
    {
        preg_match_all('/@(\w+)/', $this->body, $matches);
        return array_unique($matches[1] ?? []);
    }
}
