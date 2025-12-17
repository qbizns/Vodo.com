<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Services\Scheduler\TaskScheduler;
use App\Services\Scheduler\EventDispatcher;
use App\Console\Commands\RunScheduledTasks;

class SchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/scheduler.php', 'scheduler');

        $this->app->singleton(TaskScheduler::class, fn($app) => new TaskScheduler());
        $this->app->singleton(EventDispatcher::class, fn($app) => new EventDispatcher());

        $this->app->alias(TaskScheduler::class, 'scheduler');
        $this->app->alias(EventDispatcher::class, 'events.custom');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/scheduler.php' => config_path('scheduler.php'),
        ], 'scheduler-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'scheduler-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/scheduler-api.php');

        require_once __DIR__ . '/../../helpers/scheduler-helpers.php';

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                RunScheduledTasks::class,
            ]);
        }

        // Register with Laravel's scheduler if enabled
        if (config('scheduler.integrate_laravel_scheduler', true)) {
            $this->registerWithLaravelScheduler();
        }

        if (function_exists('do_action')) {
            do_action('scheduler_ready');
        }
    }

    protected function registerWithLaravelScheduler(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Run our custom scheduler every minute
            $frequency = config('scheduler.check_frequency', 'everyMinute');
            
            $schedule->command('scheduler:run')
                ->{$frequency}()
                ->withoutOverlapping()
                ->runInBackground();
        });
    }
}
