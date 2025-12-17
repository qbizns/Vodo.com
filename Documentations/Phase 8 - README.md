# Phase 8: Event/Scheduler System

A comprehensive scheduling and event system for Laravel with cron-like task scheduling, recurring jobs, event pub/sub, and plugin integration.

## Overview

- **Cron-Based Scheduling** - Full cron expression support with common presets
- **Recurring Jobs** - Simple interval-based scheduling (every N minutes/hours/days)
- **Event Pub/Sub** - Subscribe to and dispatch custom events
- **Async Processing** - Queue-based event handling for heavy listeners
- **Execution Logging** - Complete history with duration, output, and errors
- **Retry Logic** - Configurable retries with delays
- **Plugin Integration** - Easy scheduling from plugins

## Installation

### 1. Extract Files

```bash
unzip phase-8.zip
```

### 2. Register Service Provider

```php
App\Providers\SchedulerServiceProvider::class,
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Add to Laravel Scheduler

The system automatically integrates with Laravel's scheduler. Ensure your crontab runs:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Or run manually:

```bash
php artisan scheduler:run
```

## Quick Start

### Schedule a Task

```php
// Using helper function
schedule_callback('cleanup-temp', 'App\Services\CleanupService@run', '0 * * * *');

// With more options
schedule_task([
    'slug' => 'daily-report',
    'name' => 'Daily Report Generator',
    'handler' => 'App\Services\ReportService@generate',
    'expression' => '0 8 * * *', // 8 AM daily
    'timezone' => 'America/New_York',
    'without_overlapping' => true,
    'max_attempts' => 3,
], 'my-plugin');
```

### Schedule Recurring Jobs

```php
// Every 5 minutes
every_minutes('sync-orders', 'App\Services\OrderSync@run', 5);

// Every 2 hours
every_hours('cache-warmup', 'App\Services\CacheWarmer@warm', 2);

// Every day with time window
schedule_recurring([
    'slug' => 'night-backup',
    'handler' => 'App\Services\Backup@run',
    'interval_type' => 'days',
    'interval_value' => 1,
    'run_after' => '02:00',
    'run_before' => '05:00',
], 'my-plugin');
```

### Event Subscriptions

```php
// Subscribe to an event
subscribe_event('user.created', 'App\Listeners\SendWelcomeEmail@handle');

// Async subscription (queued)
subscribe_event_async('order.placed', 'App\Listeners\ProcessOrder@handle', 'orders');

// Subscribe with conditions
subscribe_event('order.placed', 'App\Listeners\VipHandler@handle', [
    'conditions' => [
        ['field' => 'total', 'operator' => '>=', 'value' => 1000],
    ],
]);

// Dispatch events
dispatch_event('user.created', ['user_id' => 123, 'email' => 'user@example.com']);
```

## Plugin Integration

```php
use App\Traits\HasScheduledTasks;

class MyPlugin extends BasePlugin
{
    use HasScheduledTasks;

    public function activate(): void
    {
        // Schedule a cron task
        $this->scheduleCallback(
            'my-plugin.cleanup',
            'App\Services\CleanupService@run',
            '0 */6 * * *' // Every 6 hours
        );

        // Recurring job
        $this->everyMinutes('my-plugin.sync', 'App\Services\SyncService@run', 5);

        // Event subscriptions
        $this->subscribeToEvent('order.created', 'App\Listeners\OrderCreated@handle');
        $this->subscribeToEventAsync('heavy.task', 'App\Jobs\HeavyTask@handle');
    }

    public function deactivate(): void
    {
        $this->cleanupScheduledTasks();
    }
}
```

## Cron Expressions

### Common Presets

| Expression | Constant | Description |
|------------|----------|-------------|
| `* * * * *` | `EVERY_MINUTE` | Every minute |
| `*/5 * * * *` | `EVERY_FIVE_MINUTES` | Every 5 minutes |
| `*/10 * * * *` | `EVERY_TEN_MINUTES` | Every 10 minutes |
| `*/15 * * * *` | `EVERY_FIFTEEN_MINUTES` | Every 15 minutes |
| `*/30 * * * *` | `EVERY_THIRTY_MINUTES` | Every 30 minutes |
| `0 * * * *` | `HOURLY` | Every hour |
| `0 0 * * *` | `DAILY` | Daily at midnight |
| `0 12 * * *` | `DAILY_AT_NOON` | Daily at noon |
| `0 0 * * 0` | `WEEKLY` | Weekly (Sunday) |
| `0 0 1 * *` | `MONTHLY` | Monthly (1st) |

### Helper Functions

```php
cron_every_minute()        // * * * * *
cron_hourly()              // 0 * * * *
cron_daily()               // 0 0 * * *
cron_at(14, 30)            // 30 14 * * * (2:30 PM)
cron_on_day(1, 9, 0)       // 0 9 * * 1 (Monday 9 AM)
```

## Task Options

| Option | Type | Description |
|--------|------|-------------|
| `without_overlapping` | bool | Prevent concurrent executions |
| `expires_after` | int | Minutes before lock expires |
| `run_in_background` | bool | Run asynchronously |
| `run_on_one_server` | bool | Single server in cluster |
| `even_in_maintenance` | bool | Run during maintenance mode |
| `max_attempts` | int | Retry attempts on failure |
| `retry_delay` | int | Seconds between retries |
| `output_file` | string | Write output to file |
| `email_output` | string | Email address for output |
| `email_on_failure` | bool | Only email on failures |

## Callbacks

```php
schedule_task([
    'slug' => 'important-task',
    'handler' => 'App\Services\ImportantService@run',
    'expression' => '0 0 * * *',
    'before_callback' => 'App\Hooks\TaskHooks@before',
    'after_callback' => 'App\Hooks\TaskHooks@after',
    'success_callback' => 'App\Hooks\TaskHooks@onSuccess',
    'failure_callback' => 'App\Hooks\TaskHooks@onFailure',
]);
```

## Event Conditions

Events can be conditionally processed:

```php
subscribe_event('order.placed', 'App\Listeners\BigOrderHandler@handle', [
    'conditions' => [
        ['field' => 'total', 'operator' => '>=', 'value' => 500],
        ['field' => 'status', 'operator' => '=', 'value' => 'confirmed'],
        ['field' => 'country', 'operator' => 'in', 'value' => ['US', 'CA']],
    ],
]);
```

Supported operators:
- `=`, `!=`, `>`, `<`, `>=`, `<=`
- `in`, `not_in`
- `contains`, `starts_with`, `ends_with`
- `null`, `not_null`

## Artisan Commands

```bash
# Run all due tasks
php artisan scheduler:run

# Run specific task
php artisan scheduler:run --task=my-task-slug

# Force run (ignore schedule)
php artisan scheduler:run --task=my-task-slug --force

# Dry run (show what would run)
php artisan scheduler:run --dry-run
```

## API Endpoints

### Tasks

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/scheduler/tasks | List tasks |
| POST | /api/v1/scheduler/tasks | Create task |
| GET | /api/v1/scheduler/tasks/{slug} | Get task |
| PUT | /api/v1/scheduler/tasks/{slug} | Update task |
| DELETE | /api/v1/scheduler/tasks/{slug} | Delete task |
| POST | /api/v1/scheduler/tasks/{slug}/run | Run task now |
| GET | /api/v1/scheduler/tasks/{slug}/logs | Get task logs |

### Recurring Jobs

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/scheduler/jobs | List jobs |
| POST | /api/v1/scheduler/jobs | Create job |
| DELETE | /api/v1/scheduler/jobs/{slug} | Delete job |

### Events

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/scheduler/subscriptions | List subscriptions |
| POST | /api/v1/scheduler/subscriptions | Subscribe |
| DELETE | /api/v1/scheduler/subscriptions | Unsubscribe |
| POST | /api/v1/scheduler/events/dispatch | Dispatch event |

### Meta

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/scheduler/stats | Get statistics |
| GET | /api/v1/scheduler/logs | All task logs |
| POST | /api/v1/scheduler/run-due | Run all due tasks |
| GET | /api/v1/scheduler/meta/expressions | Common cron expressions |

## Helper Functions

| Function | Description |
|----------|-------------|
| `schedule_task($config)` | Register scheduled task |
| `schedule_callback($slug, $handler, $expr)` | Schedule callback |
| `schedule_command($slug, $cmd, $expr)` | Schedule artisan command |
| `schedule_job($slug, $class, $expr)` | Schedule queue job |
| `unschedule_task($slug)` | Remove task |
| `every_minutes($slug, $handler, $n)` | Recurring every N minutes |
| `every_hours($slug, $handler, $n)` | Recurring every N hours |
| `subscribe_event($event, $listener)` | Subscribe to event |
| `dispatch_event($event, $payload)` | Dispatch event |
| `run_scheduled_tasks()` | Execute due tasks |
| `scheduler_stats($hours)` | Get statistics |

## File Structure

```
phase8/
├── app/
│   ├── Console/Commands/
│   │   └── RunScheduledTasks.php
│   ├── Http/Controllers/Api/
│   │   └── SchedulerApiController.php
│   ├── Jobs/
│   │   └── ProcessEventListener.php
│   ├── Models/
│   │   ├── ScheduledTask.php
│   │   ├── TaskLog.php
│   │   ├── RecurringJob.php
│   │   └── EventSubscription.php
│   ├── Providers/
│   │   └── SchedulerServiceProvider.php
│   ├── Services/Scheduler/
│   │   ├── TaskScheduler.php
│   │   └── EventDispatcher.php
│   └── Traits/
│       └── HasScheduledTasks.php
├── config/
│   └── scheduler.php
├── database/migrations/
│   └── 2025_01_01_000070_create_scheduler_tables.php
├── helpers/
│   └── scheduler-helpers.php
├── routes/
│   └── scheduler-api.php
└── README.md
```

## Database Tables

- `scheduled_tasks` - Cron-based task definitions
- `task_logs` - Execution history and output
- `recurring_jobs` - Interval-based job definitions
- `event_subscriptions` - Event listener registrations

## Dependencies

Add to composer.json:

```json
"require": {
    "dragonmantank/cron-expression": "^3.3"
}
```

## Progress Summary

| Phase | Status | Description |
|-------|--------|-------------|
| Phase 1 | ✅ Done | Dynamic Entities |
| Phase 2 | ✅ Done | Hook System |
| Phase 3 | ✅ Done | Field Type System |
| Phase 4 | ✅ Done | REST API Extension |
| Phase 5 | ✅ Done | Shortcode System |
| Phase 6 | ✅ Done | Menu System |
| Phase 7 | ✅ Done | Permissions System |
| **Phase 8** | ✅ Done | **Event/Scheduler** |
| Phase 9 | Next | Marketplace Integration |
