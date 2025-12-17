<?php

namespace App\Traits;

use App\Models\ScheduledTask;
use App\Models\RecurringJob;
use App\Models\EventSubscription;
use App\Services\Scheduler\TaskScheduler;
use App\Services\Scheduler\EventDispatcher;
use Illuminate\Support\Collection;

/**
 * Trait for plugins to register scheduled tasks and event listeners
 * 
 * class MyPlugin extends BasePlugin
 * {
 *     use HasScheduledTasks;
 * 
 *     public function activate(): void
 *     {
 *         // Schedule a task
 *         $this->scheduleTask([
 *             'slug' => 'my-plugin.cleanup',
 *             'handler' => 'App\Services\CleanupService@run',
 *             'expression' => '0 * * * *', // hourly
 *         ]);
 * 
 *         // Subscribe to an event
 *         $this->subscribeToEvent('user.created', 'App\Listeners\WelcomeEmail@handle');
 *     }
 * 
 *     public function deactivate(): void
 *     {
 *         $this->cleanupScheduledTasks();
 *     }
 * }
 */
trait HasScheduledTasks
{
    protected function taskScheduler(): TaskScheduler
    {
        return app(TaskScheduler::class);
    }

    protected function eventDispatcher(): EventDispatcher
    {
        return app(EventDispatcher::class);
    }

    protected function getSchedulerPluginSlug(): string
    {
        return $this->slug ?? $this->pluginSlug ?? strtolower(class_basename($this));
    }

    // =========================================================================
    // Scheduled Tasks
    // =========================================================================

    /**
     * Register a scheduled task
     */
    public function scheduleTask(array $config): ScheduledTask
    {
        return $this->taskScheduler()->register($config, $this->getSchedulerPluginSlug());
    }

    /**
     * Schedule a callback
     */
    public function scheduleCallback(string $slug, string $handler, string $expression, array $options = []): ScheduledTask
    {
        return $this->scheduleTask(array_merge([
            'slug' => $slug,
            'handler' => $handler,
            'type' => ScheduledTask::TYPE_CALLBACK,
            'expression' => $expression,
        ], $options));
    }

    /**
     * Schedule a job
     */
    public function scheduleJob(string $slug, string $jobClass, string $expression, array $options = []): ScheduledTask
    {
        return $this->scheduleTask(array_merge([
            'slug' => $slug,
            'handler' => $jobClass,
            'type' => ScheduledTask::TYPE_JOB,
            'expression' => $expression,
        ], $options));
    }

    /**
     * Schedule an artisan command
     */
    public function scheduleCommand(string $slug, string $command, string $expression, array $options = []): ScheduledTask
    {
        return $this->scheduleTask(array_merge([
            'slug' => $slug,
            'handler' => $command,
            'type' => ScheduledTask::TYPE_COMMAND,
            'expression' => $expression,
        ], $options));
    }

    /**
     * Unregister a scheduled task
     */
    public function unscheduleTask(string $slug): bool
    {
        return $this->taskScheduler()->unregister($slug, $this->getSchedulerPluginSlug());
    }

    /**
     * Get plugin's scheduled tasks
     */
    public function getScheduledTasks(): Collection
    {
        return $this->taskScheduler()->getTasksForPlugin($this->getSchedulerPluginSlug());
    }

    // =========================================================================
    // Recurring Jobs
    // =========================================================================

    /**
     * Register a recurring job
     */
    public function scheduleRecurringJob(array $config): RecurringJob
    {
        return $this->taskScheduler()->registerRecurringJob($config, $this->getSchedulerPluginSlug());
    }

    /**
     * Schedule a job every N minutes
     */
    public function everyMinutes(string $slug, string $handler, int $minutes, array $options = []): RecurringJob
    {
        return $this->scheduleRecurringJob(array_merge([
            'slug' => $slug,
            'handler' => $handler,
            'interval_type' => RecurringJob::INTERVAL_MINUTES,
            'interval_value' => $minutes,
        ], $options));
    }

    /**
     * Schedule a job every N hours
     */
    public function everyHours(string $slug, string $handler, int $hours, array $options = []): RecurringJob
    {
        return $this->scheduleRecurringJob(array_merge([
            'slug' => $slug,
            'handler' => $handler,
            'interval_type' => RecurringJob::INTERVAL_HOURS,
            'interval_value' => $hours,
        ], $options));
    }

    /**
     * Schedule a job every N days
     */
    public function everyDays(string $slug, string $handler, int $days, array $options = []): RecurringJob
    {
        return $this->scheduleRecurringJob(array_merge([
            'slug' => $slug,
            'handler' => $handler,
            'interval_type' => RecurringJob::INTERVAL_DAYS,
            'interval_value' => $days,
        ], $options));
    }

    /**
     * Get plugin's recurring jobs
     */
    public function getRecurringJobs(): Collection
    {
        return RecurringJob::forPlugin($this->getSchedulerPluginSlug())->get();
    }

    // =========================================================================
    // Event Subscriptions
    // =========================================================================

    /**
     * Subscribe to an event
     */
    public function subscribeToEvent(string $event, string $listener, array $options = []): EventSubscription
    {
        return $this->eventDispatcher()->subscribe($event, $listener, $options, $this->getSchedulerPluginSlug());
    }

    /**
     * Subscribe to an event asynchronously
     */
    public function subscribeToEventAsync(string $event, string $listener, ?string $queue = null, array $options = []): EventSubscription
    {
        return $this->subscribeToEvent($event, $listener, array_merge([
            'async' => true,
            'queue' => $queue,
        ], $options));
    }

    /**
     * Subscribe with conditions
     */
    public function subscribeToEventWhen(string $event, string $listener, array $conditions, array $options = []): EventSubscription
    {
        return $this->subscribeToEvent($event, $listener, array_merge([
            'conditions' => $conditions,
        ], $options));
    }

    /**
     * Unsubscribe from an event
     */
    public function unsubscribeFromEvent(string $event, string $listener): bool
    {
        return $this->eventDispatcher()->unsubscribe($event, $listener, $this->getSchedulerPluginSlug());
    }

    /**
     * Dispatch an event
     */
    public function dispatchEvent(string $event, array $payload = []): array
    {
        return $this->eventDispatcher()->dispatch($event, $payload);
    }

    /**
     * Get plugin's event subscriptions
     */
    public function getEventSubscriptions(): Collection
    {
        return EventSubscription::forPlugin($this->getSchedulerPluginSlug())->get();
    }

    // =========================================================================
    // Cleanup
    // =========================================================================

    /**
     * Remove all scheduled tasks and subscriptions for this plugin
     */
    public function cleanupScheduledTasks(): int
    {
        $slug = $this->getSchedulerPluginSlug();
        
        $taskCount = $this->taskScheduler()->unregisterPluginTasks($slug);
        $jobCount = RecurringJob::where('plugin_slug', $slug)->delete();
        $subCount = $this->eventDispatcher()->unsubscribePlugin($slug);

        return $taskCount + $jobCount + $subCount;
    }
}
