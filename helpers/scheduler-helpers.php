<?php

/**
 * Scheduler Helper Functions
 */

use App\Models\ScheduledTask;
use App\Models\RecurringJob;
use App\Models\EventSubscription;
use App\Services\Scheduler\TaskScheduler;
use App\Services\Scheduler\EventDispatcher;

// =============================================================================
// Service Access
// =============================================================================

if (!function_exists('task_scheduler')) {
    function task_scheduler(): TaskScheduler
    {
        return app(TaskScheduler::class);
    }
}

if (!function_exists('event_dispatcher')) {
    function event_dispatcher(): EventDispatcher
    {
        return app(EventDispatcher::class);
    }
}

// =============================================================================
// Task Registration
// =============================================================================

if (!function_exists('schedule_task')) {
    function schedule_task(array $config, ?string $pluginSlug = null): ScheduledTask
    {
        return task_scheduler()->register($config, $pluginSlug);
    }
}

if (!function_exists('schedule_callback')) {
    function schedule_callback(string $slug, string $handler, string $expression, array $options = [], ?string $pluginSlug = null): ScheduledTask
    {
        return schedule_task(array_merge([
            'slug' => $slug,
            'handler' => $handler,
            'type' => ScheduledTask::TYPE_CALLBACK,
            'expression' => $expression,
        ], $options), $pluginSlug);
    }
}

if (!function_exists('schedule_command')) {
    function schedule_command(string $slug, string $command, string $expression, array $options = [], ?string $pluginSlug = null): ScheduledTask
    {
        return schedule_task(array_merge([
            'slug' => $slug,
            'handler' => $command,
            'type' => ScheduledTask::TYPE_COMMAND,
            'expression' => $expression,
        ], $options), $pluginSlug);
    }
}

if (!function_exists('schedule_job')) {
    function schedule_job(string $slug, string $jobClass, string $expression, array $options = [], ?string $pluginSlug = null): ScheduledTask
    {
        return schedule_task(array_merge([
            'slug' => $slug,
            'handler' => $jobClass,
            'type' => ScheduledTask::TYPE_JOB,
            'expression' => $expression,
        ], $options), $pluginSlug);
    }
}

if (!function_exists('unschedule_task')) {
    function unschedule_task(string $slug, ?string $pluginSlug = null): bool
    {
        return task_scheduler()->unregister($slug, $pluginSlug);
    }
}

// =============================================================================
// Recurring Jobs
// =============================================================================

if (!function_exists('schedule_recurring')) {
    function schedule_recurring(array $config, ?string $pluginSlug = null): RecurringJob
    {
        return task_scheduler()->registerRecurringJob($config, $pluginSlug);
    }
}

if (!function_exists('every_minutes')) {
    function every_minutes(string $slug, string $handler, int $minutes, array $options = [], ?string $pluginSlug = null): RecurringJob
    {
        return schedule_recurring(array_merge([
            'slug' => $slug,
            'handler' => $handler,
            'interval_type' => RecurringJob::INTERVAL_MINUTES,
            'interval_value' => $minutes,
        ], $options), $pluginSlug);
    }
}

if (!function_exists('every_hours')) {
    function every_hours(string $slug, string $handler, int $hours, array $options = [], ?string $pluginSlug = null): RecurringJob
    {
        return schedule_recurring(array_merge([
            'slug' => $slug,
            'handler' => $handler,
            'interval_type' => RecurringJob::INTERVAL_HOURS,
            'interval_value' => $hours,
        ], $options), $pluginSlug);
    }
}

if (!function_exists('every_days')) {
    function every_days(string $slug, string $handler, int $days, array $options = [], ?string $pluginSlug = null): RecurringJob
    {
        return schedule_recurring(array_merge([
            'slug' => $slug,
            'handler' => $handler,
            'interval_type' => RecurringJob::INTERVAL_DAYS,
            'interval_value' => $days,
        ], $options), $pluginSlug);
    }
}

// =============================================================================
// Event Subscriptions
// =============================================================================

if (!function_exists('subscribe_event')) {
    function subscribe_event(string $event, string $listener, array $options = [], ?string $pluginSlug = null): EventSubscription
    {
        return event_dispatcher()->subscribe($event, $listener, $options, $pluginSlug);
    }
}

if (!function_exists('subscribe_event_async')) {
    function subscribe_event_async(string $event, string $listener, ?string $queue = null, ?string $pluginSlug = null): EventSubscription
    {
        return subscribe_event($event, $listener, [
            'async' => true,
            'queue' => $queue,
        ], $pluginSlug);
    }
}

if (!function_exists('unsubscribe_event')) {
    function unsubscribe_event(string $event, string $listener, ?string $pluginSlug = null): bool
    {
        return event_dispatcher()->unsubscribe($event, $listener, $pluginSlug);
    }
}

if (!function_exists('dispatch_event')) {
    function dispatch_event(string $event, array $payload = []): array
    {
        return event_dispatcher()->dispatch($event, $payload);
    }
}

if (!function_exists('listen_event')) {
    /**
     * Runtime event listener (not persisted)
     */
    function listen_event(string $event, callable|string $listener, int $priority = 100): void
    {
        event_dispatcher()->listen($event, $listener, $priority);
    }
}

// =============================================================================
// Retrieval
// =============================================================================

if (!function_exists('get_scheduled_task')) {
    function get_scheduled_task(string $slug): ?ScheduledTask
    {
        return task_scheduler()->getTask($slug);
    }
}

if (!function_exists('get_all_scheduled_tasks')) {
    function get_all_scheduled_tasks(): \Illuminate\Support\Collection
    {
        return task_scheduler()->getAllTasks();
    }
}

if (!function_exists('get_due_tasks')) {
    function get_due_tasks(): \Illuminate\Support\Collection
    {
        return task_scheduler()->getDueTasks();
    }
}

if (!function_exists('get_recurring_job')) {
    function get_recurring_job(string $slug): ?RecurringJob
    {
        return task_scheduler()->getRecurringJob($slug);
    }
}

// =============================================================================
// Execution
// =============================================================================

if (!function_exists('run_scheduled_tasks')) {
    function run_scheduled_tasks(): array
    {
        return task_scheduler()->runDueTasks();
    }
}

if (!function_exists('run_task')) {
    function run_task(string $slug): array
    {
        $task = get_scheduled_task($slug);
        if (!$task) {
            return ['status' => 'error', 'error' => 'Task not found'];
        }
        return task_scheduler()->runTask($task);
    }
}

// =============================================================================
// Statistics
// =============================================================================

if (!function_exists('scheduler_stats')) {
    function scheduler_stats(int $hours = 24): array
    {
        return task_scheduler()->getStats($hours);
    }
}

// =============================================================================
// Cron Expression Helpers
// =============================================================================

if (!function_exists('cron_every_minute')) {
    function cron_every_minute(): string { return ScheduledTask::EVERY_MINUTE; }
}

if (!function_exists('cron_every_five_minutes')) {
    function cron_every_five_minutes(): string { return ScheduledTask::EVERY_FIVE_MINUTES; }
}

if (!function_exists('cron_hourly')) {
    function cron_hourly(): string { return ScheduledTask::HOURLY; }
}

if (!function_exists('cron_daily')) {
    function cron_daily(): string { return ScheduledTask::DAILY; }
}

if (!function_exists('cron_weekly')) {
    function cron_weekly(): string { return ScheduledTask::WEEKLY; }
}

if (!function_exists('cron_monthly')) {
    function cron_monthly(): string { return ScheduledTask::MONTHLY; }
}

if (!function_exists('cron_at')) {
    /**
     * Create a cron expression for a specific time
     */
    function cron_at(int $hour, int $minute = 0): string
    {
        return "{$minute} {$hour} * * *";
    }
}

if (!function_exists('cron_on_day')) {
    /**
     * Create a cron expression for a specific day of week
     * Days: 0 = Sunday, 1 = Monday, etc.
     */
    function cron_on_day(int $day, int $hour = 0, int $minute = 0): string
    {
        return "{$minute} {$hour} * * {$day}";
    }
}
