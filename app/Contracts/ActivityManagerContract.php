<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract for the Activity Manager (Chatter).
 *
 * Handles activities, messages, and record tracking.
 */
interface ActivityManagerContract
{
    /**
     * Schedule an activity.
     *
     * @param Model $record Associated record
     * @param string $typeSlug Activity type slug
     * @param array $options Activity options
     * @return mixed Activity instance
     */
    public function schedule(Model $record, string $typeSlug, array $options = []): mixed;

    /**
     * Complete an activity.
     *
     * @param mixed $activity Activity instance
     * @param string|null $note Completion note
     * @return mixed Updated activity
     */
    public function complete(mixed $activity, ?string $note = null): mixed;

    /**
     * Post a message on a record.
     *
     * @param Model $record Associated record
     * @param string $body Message body
     * @param array $options Message options
     * @return mixed Message instance
     */
    public function postMessage(Model $record, string $body, array $options = []): mixed;

    /**
     * Post a system message.
     *
     * @param Model $record Associated record
     * @param string $body Message body
     * @return mixed Message instance
     */
    public function postSystemMessage(Model $record, string $body): mixed;

    /**
     * Track field changes.
     *
     * @param Model $record Record with changes
     * @param array $changes Field changes
     * @return mixed|null Tracking message or null
     */
    public function trackChanges(Model $record, array $changes): mixed;

    /**
     * Get activities for a record.
     *
     * @param Model $record Record
     * @return Collection
     */
    public function getActivities(Model $record): Collection;

    /**
     * Get messages for a record.
     *
     * @param Model $record Record
     * @param bool $includeInternal Include internal messages
     * @return Collection
     */
    public function getMessages(Model $record, bool $includeInternal = true): Collection;

    /**
     * Get chatter summary (messages + activities).
     *
     * @param Model $record Record
     * @return array
     */
    public function getChatter(Model $record): array;

    /**
     * Register an activity type.
     *
     * @param array $definition Type definition
     * @param string|null $pluginSlug Owner plugin
     * @return mixed ActivityType instance
     */
    public function registerActivityType(array $definition, ?string $pluginSlug = null): mixed;

    /**
     * Get all activity types.
     *
     * @return Collection
     */
    public function getActivityTypes(): Collection;

    /**
     * Configure tracked fields for an entity.
     *
     * @param string $entityName Entity name
     * @param array $fields Fields to track
     * @return void
     */
    public function configureTracking(string $entityName, array $fields): void;
}
