<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Activity;
use App\Models\Message;
use App\Services\Activity\ActivityManager;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait for models that support chatter (messages and activities).
 * 
 * Usage:
 * 
 * class Invoice extends Model
 * {
 *     use HasChatter;
 *     
 *     // Optionally define tracked fields
 *     protected array $trackedFields = ['status', 'total', 'partner_id'];
 * }
 */
trait HasChatter
{
    /**
     * Boot the trait.
     */
    public static function bootHasChatter(): void
    {
        static::updating(function ($model) {
            $model->trackFieldChanges();
        });
    }

    /**
     * Get messages for this record.
     */
    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable')->orderBy('created_at', 'desc');
    }

    /**
     * Get activities for this record.
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activityable');
    }

    /**
     * Get pending activities.
     */
    public function pendingActivities(): MorphMany
    {
        return $this->activities()->pending();
    }

    /**
     * Post a message on this record.
     */
    public function postMessage(string $body, array $options = []): Message
    {
        return app(ActivityManager::class)->postMessage($this, $body, $options);
    }

    /**
     * Post an internal note.
     */
    public function postNote(string $body): Message
    {
        return app(ActivityManager::class)->postNote($this, $body);
    }

    /**
     * Schedule an activity.
     */
    public function scheduleActivity(string $typeSlug, array $options = []): Activity
    {
        return app(ActivityManager::class)->schedule($this, $typeSlug, $options);
    }

    /**
     * Get chatter data (messages + activities).
     */
    public function getChatter(): array
    {
        return app(ActivityManager::class)->getChatter($this);
    }

    /**
     * Get the fields to track for changes.
     */
    public function getTrackedFields(): array
    {
        return $this->trackedFields ?? [];
    }

    /**
     * Track field changes and create tracking message.
     */
    protected function trackFieldChanges(): void
    {
        $dirty = $this->getDirty();
        $trackedFields = $this->getTrackedFields();

        if (empty($trackedFields)) {
            return;
        }

        $changes = [];
        foreach ($dirty as $field => $newValue) {
            if (in_array($field, $trackedFields)) {
                $oldValue = $this->getOriginal($field);
                $changes[$field] = [$oldValue, $newValue];
            }
        }

        if (!empty($changes)) {
            app(ActivityManager::class)->trackChanges($this, $changes);
        }
    }

    /**
     * Get message count.
     */
    public function getMessageCountAttribute(): int
    {
        return $this->messages()->count();
    }

    /**
     * Get pending activity count.
     */
    public function getPendingActivityCountAttribute(): int
    {
        return $this->pendingActivities()->count();
    }

    /**
     * Check if has overdue activities.
     */
    public function hasOverdueActivities(): bool
    {
        return $this->activities()->overdue()->exists();
    }

    /**
     * Cancel all pending activities.
     */
    public function cancelActivities(?string $typeSlug = null): int
    {
        return app(ActivityManager::class)->cancelActivities($this, $typeSlug);
    }

    /**
     * Log a system message.
     */
    public function logSystemMessage(string $body): Message
    {
        return app(ActivityManager::class)->postSystemMessage($this, $body);
    }
}
