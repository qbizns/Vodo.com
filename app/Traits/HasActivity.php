<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Activity;
use App\Models\Message;
use App\Services\Activity\ActivityManager;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasActivity - Trait for models that support activities and chatter.
 * 
 * Usage:
 * 
 * class Invoice extends Model
 * {
 *     use HasActivity;
 * 
 *     protected array $trackedFields = ['status', 'total', 'partner_id'];
 * }
 * 
 * // Then use:
 * $invoice->postMessage('Customer called about payment');
 * $invoice->scheduleActivity('call', ['due_date' => now()->addDays(3)]);
 * $invoice->getChatter(); // Get all messages and activities
 */
trait HasActivity
{
    /**
     * Boot the trait.
     */
    public static function bootHasActivity(): void
    {
        // Track field changes on update
        static::updating(function ($model) {
            if (method_exists($model, 'getTrackedFields') && !empty($model->getTrackedFields())) {
                $changes = [];
                foreach ($model->getTrackedFields() as $field) {
                    if ($model->isDirty($field)) {
                        $changes[$field] = [$model->getOriginal($field), $model->$field];
                    }
                }
                if (!empty($changes)) {
                    $model->pendingTrackingChanges = $changes;
                }
            }
        });

        static::updated(function ($model) {
            if (isset($model->pendingTrackingChanges) && !empty($model->pendingTrackingChanges)) {
                $model->getActivityManager()->trackChanges($model, $model->pendingTrackingChanges);
                unset($model->pendingTrackingChanges);
            }
        });
    }

    /**
     * Get activities relationship.
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activityable');
    }

    /**
     * Get messages relationship.
     */
    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable');
    }

    /**
     * Get the activity manager.
     */
    protected function getActivityManager(): ActivityManager
    {
        return app(ActivityManager::class);
    }

    /**
     * Get fields that should be tracked for changes.
     */
    public function getTrackedFields(): array
    {
        return $this->trackedFields ?? [];
    }

    /**
     * Post a message/comment.
     */
    public function postMessage(string $body, array $options = []): Message
    {
        return $this->getActivityManager()->postMessage($this, $body, $options);
    }

    /**
     * Post an internal note.
     */
    public function postNote(string $body): Message
    {
        return $this->getActivityManager()->postNote($this, $body);
    }

    /**
     * Schedule an activity.
     */
    public function scheduleActivity(string $typeSlug, array $options = []): Activity
    {
        return $this->getActivityManager()->schedule($this, $typeSlug, $options);
    }

    /**
     * Get pending activities.
     */
    public function getPendingActivities(): \Illuminate\Support\Collection
    {
        return $this->getActivityManager()->getActivities($this);
    }

    /**
     * Get all messages.
     */
    public function getMessages(bool $includeInternal = true): \Illuminate\Support\Collection
    {
        return $this->getActivityManager()->getMessages($this, $includeInternal);
    }

    /**
     * Get chatter summary (messages + activities).
     */
    public function getChatter(): array
    {
        return $this->getActivityManager()->getChatter($this);
    }

    /**
     * Log a system message.
     */
    public function logActivity(string $message): Message
    {
        return $this->getActivityManager()->postSystemMessage($this, $message);
    }

    /**
     * Cancel all pending activities.
     */
    public function cancelActivities(?string $typeSlug = null): int
    {
        return $this->getActivityManager()->cancelActivities($this, $typeSlug);
    }

    /**
     * Scope for records with pending activities.
     */
    public function scopeWithPendingActivities($query)
    {
        return $query->whereHas('activities', function ($q) {
            $q->pending();
        });
    }

    /**
     * Scope for records with overdue activities.
     */
    public function scopeWithOverdueActivities($query)
    {
        return $query->whereHas('activities', function ($q) {
            $q->overdue();
        });
    }
}
