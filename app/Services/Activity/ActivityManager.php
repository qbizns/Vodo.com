<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Message;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Activity Manager - Handles activities and chatter messages.
 * 
 * Features:
 * - Schedule activities (follow-ups, calls, meetings)
 * - Post messages/comments on records
 * - Track field changes
 * - Handle mentions and notifications
 * 
 * Example usage:
 * 
 * // Schedule an activity
 * $activityManager->schedule($invoice, 'call', [
 *     'subject' => 'Follow up on payment',
 *     'due_date' => now()->addDays(3),
 *     'assigned_to' => $userId,
 * ]);
 * 
 * // Post a message
 * $activityManager->postMessage($invoice, 'Customer requested delay');
 * 
 * // Track field changes
 * $activityManager->trackChanges($invoice, [
 *     'status' => ['draft', 'sent'],
 *     'total' => [100.00, 150.00],
 * ]);
 */
class ActivityManager
{
    /**
     * Tracked fields per entity.
     * @var array<string, array>
     */
    protected array $trackedFields = [];

    /**
     * Schedule an activity.
     */
    public function schedule(
        Model $record,
        string $typeSlug,
        array $options = []
    ): Activity {
        $type = ActivityType::where('slug', $typeSlug)->firstOrFail();

        return Activity::create([
            'activity_type_id' => $type->id,
            'subject' => $options['subject'] ?? $type->name,
            'note' => $options['note'] ?? $type->default_note,
            'due_date' => $options['due_date'] ?? $type->getDefaultDueDate(),
            'activityable_type' => get_class($record),
            'activityable_id' => $record->getKey(),
            'assigned_to' => $options['assigned_to'] ?? Auth::id(),
            'created_by' => Auth::id(),
            'is_automated' => $options['is_automated'] ?? false,
        ]);
    }

    /**
     * Complete an activity.
     */
    public function complete(Activity $activity, ?string $note = null): Activity
    {
        return DB::transaction(function () use ($activity, $note) {
            $activity->markCompleted($note);
            
            // Post message about completion
            $this->postSystemMessage($activity->activityable, sprintf(
                'Activity "%s" completed by %s',
                $activity->subject,
                Auth::user()?->name ?? 'System'
            ));

            return $activity->fresh();
        });
    }

    /**
     * Get pending activities for a record.
     */
    public function getActivities(Model $record): Collection
    {
        return Activity::where('activityable_type', get_class($record))
            ->where('activityable_id', $record->getKey())
            ->pending()
            ->orderBy('due_date')
            ->with(['activityType', 'assignee'])
            ->get();
    }

    /**
     * Get all activities for current user.
     */
    public function getMyActivities(?int $userId = null): Collection
    {
        return Activity::assignedTo($userId ?? Auth::id())
            ->pending()
            ->orderBy('due_date')
            ->with(['activityType', 'activityable'])
            ->get();
    }

    /**
     * Get overdue activities for current user.
     */
    public function getOverdueActivities(?int $userId = null): Collection
    {
        return Activity::assignedTo($userId ?? Auth::id())
            ->overdue()
            ->orderBy('due_date')
            ->with(['activityType', 'activityable'])
            ->get();
    }

    /**
     * Get activities due today.
     */
    public function getTodayActivities(?int $userId = null): Collection
    {
        return Activity::assignedTo($userId ?? Auth::id())
            ->dueToday()
            ->orderBy('due_date')
            ->with(['activityType', 'activityable'])
            ->get();
    }

    /**
     * Post a message/comment on a record.
     */
    public function postMessage(
        Model $record,
        string $body,
        array $options = []
    ): Message {
        $mentions = $this->extractMentions($body);

        $message = Message::create([
            'messageable_type' => get_class($record),
            'messageable_id' => $record->getKey(),
            'message_type' => $options['type'] ?? Message::TYPE_COMMENT,
            'subject' => $options['subject'] ?? null,
            'body' => $body,
            'author_id' => Auth::id(),
            'mentions' => $mentions,
            'is_internal' => $options['is_internal'] ?? false,
            'is_note' => $options['is_note'] ?? false,
            'parent_id' => $options['parent_id'] ?? null,
            'attachments' => $options['attachments'] ?? [],
        ]);

        // Send notifications to mentioned users
        if (!empty($mentions)) {
            $this->notifyMentions($message, $mentions);
        }

        return $message;
    }

    /**
     * Post a system message.
     */
    public function postSystemMessage(Model $record, string $body): Message
    {
        return Message::create([
            'messageable_type' => get_class($record),
            'messageable_id' => $record->getKey(),
            'message_type' => Message::TYPE_SYSTEM,
            'body' => $body,
            'author_id' => null,
            'is_internal' => true,
        ]);
    }

    /**
     * Post a note (internal, not visible to customers).
     */
    public function postNote(Model $record, string $body): Message
    {
        return $this->postMessage($record, $body, [
            'is_internal' => true,
            'is_note' => true,
        ]);
    }

    /**
     * Track field changes and create tracking message.
     */
    public function trackChanges(Model $record, array $changes): ?Message
    {
        if (empty($changes)) {
            return null;
        }

        $entityName = $this->getEntityName($record);
        $trackedFields = $this->trackedFields[$entityName] ?? [];

        $trackingValues = [];
        foreach ($changes as $field => $values) {
            // Only track if field is in tracking list or list is empty (track all)
            if (!empty($trackedFields) && !in_array($field, $trackedFields)) {
                continue;
            }

            [$oldValue, $newValue] = is_array($values) ? $values : [null, $values];

            $trackingValues[] = [
                'field' => $field,
                'field_label' => $this->getFieldLabel($entityName, $field),
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'old_display' => $this->formatValueForDisplay($oldValue),
                'new_display' => $this->formatValueForDisplay($newValue),
            ];
        }

        if (empty($trackingValues)) {
            return null;
        }

        return Message::create([
            'messageable_type' => get_class($record),
            'messageable_id' => $record->getKey(),
            'message_type' => Message::TYPE_TRACKING,
            'tracking_values' => $trackingValues,
            'author_id' => Auth::id(),
            'is_internal' => true,
        ]);
    }

    /**
     * Configure tracked fields for an entity.
     */
    public function configureTracking(string $entityName, array $fields): void
    {
        $this->trackedFields[$entityName] = $fields;
    }

    /**
     * Get messages for a record.
     */
    public function getMessages(Model $record, bool $includeInternal = true): Collection
    {
        $query = Message::where('messageable_type', get_class($record))
            ->where('messageable_id', $record->getKey())
            ->whereNull('parent_id') // Top-level messages only
            ->orderBy('created_at', 'desc')
            ->with(['author', 'replies.author']);

        if (!$includeInternal) {
            $query->where('is_internal', false);
        }

        return $query->get();
    }

    /**
     * Get chatter summary (messages + activities).
     */
    public function getChatter(Model $record): array
    {
        return [
            'messages' => $this->getMessages($record),
            'activities' => $this->getActivities($record),
            'activity_count' => $this->getActivities($record)->count(),
            'message_count' => Message::where('messageable_type', get_class($record))
                ->where('messageable_id', $record->getKey())
                ->count(),
        ];
    }

    /**
     * Register an activity type.
     */
    public function registerActivityType(array $definition, ?string $pluginSlug = null): ActivityType
    {
        return ActivityType::updateOrCreate(
            ['slug' => $definition['slug']],
            [
                'name' => $definition['name'],
                'icon' => $definition['icon'] ?? 'activity',
                'color' => $definition['color'] ?? 'blue',
                'default_days' => $definition['default_days'] ?? 1,
                'default_note' => $definition['default_note'] ?? null,
                'is_system' => $definition['is_system'] ?? false,
                'plugin_slug' => $pluginSlug,
            ]
        );
    }

    /**
     * Get all activity types.
     */
    public function getActivityTypes(): Collection
    {
        return ActivityType::all();
    }

    /**
     * Extract mentions from message body.
     */
    protected function extractMentions(string $body): array
    {
        preg_match_all('/@(\w+)/', $body, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Notify mentioned users.
     */
    protected function notifyMentions(Message $message, array $mentions): void
    {
        // Implementation depends on your notification system
        // This is a placeholder
        foreach ($mentions as $username) {
            // Find user and send notification
            // Notification::send($user, new MentionNotification($message));
        }
    }

    /**
     * Get entity name from model.
     */
    protected function getEntityName(Model $record): string
    {
        return $record->entity_name ?? $record->getTable();
    }

    /**
     * Get field label for display.
     */
    protected function getFieldLabel(string $entityName, string $field): string
    {
        // This could be enhanced to look up from EntityField definitions
        return ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Format value for display in tracking.
     */
    protected function formatValueForDisplay(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string)$value;
    }

    /**
     * Get activity statistics.
     */
    public function getStatistics(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        return [
            'pending' => Activity::assignedTo($userId)->pending()->count(),
            'overdue' => Activity::assignedTo($userId)->overdue()->count(),
            'due_today' => Activity::assignedTo($userId)->dueToday()->count(),
            'completed_this_week' => Activity::assignedTo($userId)
                ->completed()
                ->where('completed_at', '>=', now()->startOfWeek())
                ->count(),
        ];
    }

    /**
     * Schedule automated activity based on rules.
     */
    public function scheduleAutomated(
        Model $record,
        string $typeSlug,
        array $options = []
    ): Activity {
        return $this->schedule($record, $typeSlug, array_merge($options, [
            'is_automated' => true,
        ]));
    }

    /**
     * Cancel pending activities for a record.
     */
    public function cancelActivities(Model $record, ?string $typeSlug = null): int
    {
        $query = Activity::where('activityable_type', get_class($record))
            ->where('activityable_id', $record->getKey())
            ->pending();

        if ($typeSlug) {
            $query->whereHas('activityType', fn($q) => $q->where('slug', $typeSlug));
        }

        $count = $query->count();
        $query->delete();

        return $count;
    }
}
