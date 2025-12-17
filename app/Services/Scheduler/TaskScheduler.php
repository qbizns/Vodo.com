<?php

namespace App\Services\Scheduler;

use App\Models\ScheduledTask;
use App\Models\TaskLog;
use App\Models\RecurringJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class TaskScheduler
{
    protected bool $isRunning = false;

    // =========================================================================
    // Task Registration
    // =========================================================================

    /**
     * Register a scheduled task
     */
    public function register(array $config, ?string $pluginSlug = null): ScheduledTask
    {
        $slug = $config['slug'] ?? null;
        if (!$slug) {
            throw new \InvalidArgumentException('Task slug is required');
        }

        $existing = ScheduledTask::findBySlug($slug);
        if ($existing) {
            return $this->update($slug, $config, $pluginSlug);
        }

        $task = ScheduledTask::create([
            'slug' => $slug,
            'name' => $config['name'] ?? $slug,
            'description' => $config['description'] ?? null,
            'type' => $config['type'] ?? ScheduledTask::TYPE_CALLBACK,
            'handler' => $config['handler'],
            'parameters' => $config['parameters'] ?? null,
            'expression' => $config['expression'] ?? ScheduledTask::HOURLY,
            'timezone' => $config['timezone'] ?? config('app.timezone', 'UTC'),
            'without_overlapping' => $config['without_overlapping'] ?? false,
            'expires_after' => $config['expires_after'] ?? 60,
            'run_in_background' => $config['run_in_background'] ?? false,
            'run_on_one_server' => $config['run_on_one_server'] ?? false,
            'even_in_maintenance' => $config['even_in_maintenance'] ?? false,
            'max_attempts' => $config['max_attempts'] ?? 1,
            'retry_delay' => $config['retry_delay'] ?? 60,
            'output_file' => $config['output_file'] ?? null,
            'email_output' => $config['email_output'] ?? null,
            'email_on_failure' => $config['email_on_failure'] ?? false,
            'before_callback' => $config['before_callback'] ?? null,
            'after_callback' => $config['after_callback'] ?? null,
            'success_callback' => $config['success_callback'] ?? null,
            'failure_callback' => $config['failure_callback'] ?? null,
            'is_active' => $config['active'] ?? true,
            'priority' => $config['priority'] ?? 100,
            'plugin_slug' => $pluginSlug,
            'is_system' => $config['system'] ?? false,
            'meta' => $config['meta'] ?? null,
        ]);

        $task->calculateNextRun();
        $this->clearCache();

        if (function_exists('do_action')) {
            do_action('scheduled_task_registered', $task);
        }

        return $task;
    }

    /**
     * Update a scheduled task
     */
    public function update(string $slug, array $config, ?string $pluginSlug = null): ScheduledTask
    {
        $task = ScheduledTask::findBySlug($slug);
        if (!$task) {
            throw new \RuntimeException("Task '{$slug}' not found");
        }

        $fields = [
            'name', 'description', 'type', 'handler', 'parameters', 'expression',
            'timezone', 'without_overlapping', 'expires_after', 'run_in_background',
            'run_on_one_server', 'even_in_maintenance', 'max_attempts', 'retry_delay',
            'output_file', 'email_output', 'email_on_failure', 'before_callback',
            'after_callback', 'success_callback', 'failure_callback', 'priority', 'meta',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $config)) {
                $task->{$field} = $config[$field];
            }
        }

        if (array_key_exists('active', $config)) {
            $task->is_active = $config['active'];
        }

        $task->save();
        $task->calculateNextRun();
        $this->clearCache();

        return $task;
    }

    /**
     * Unregister a task
     */
    public function unregister(string $slug, ?string $pluginSlug = null): bool
    {
        $task = ScheduledTask::findBySlug($slug);
        if (!$task) {
            return false;
        }

        if ($pluginSlug && $task->plugin_slug !== $pluginSlug) {
            throw new \RuntimeException("Cannot unregister task - owned by another plugin");
        }

        if ($task->is_system) {
            throw new \RuntimeException("Cannot unregister system task");
        }

        $task->delete();
        $this->clearCache();

        return true;
    }

    /**
     * Unregister all tasks for a plugin
     */
    public function unregisterPluginTasks(string $pluginSlug): int
    {
        $count = ScheduledTask::where('plugin_slug', $pluginSlug)
            ->where('is_system', false)
            ->delete();

        $this->clearCache();
        return $count;
    }

    // =========================================================================
    // Task Execution
    // =========================================================================

    /**
     * Run all due tasks
     */
    public function runDueTasks(): array
    {
        $results = [];
        $tasks = ScheduledTask::due()->byPriority()->get();

        foreach ($tasks as $task) {
            $results[$task->slug] = $this->runTask($task);
        }

        // Also run due recurring jobs
        $jobs = RecurringJob::due()->get();
        foreach ($jobs as $job) {
            $results['recurring:' . $job->slug] = $this->runRecurringJob($job);
        }

        return $results;
    }

    /**
     * Run a specific task
     */
    public function runTask(ScheduledTask $task): array
    {
        // Check if task can run
        if ($task->isOverlapping()) {
            return ['status' => 'skipped', 'reason' => 'Task is still running'];
        }

        if (app()->isDownForMaintenance() && !$task->shouldRunInMaintenance()) {
            return ['status' => 'skipped', 'reason' => 'App in maintenance mode'];
        }

        // Create log entry
        $log = TaskLog::createForTask($task);
        $log->markAsRunning();

        try {
            // Run before callback
            $this->runCallback($task->before_callback);

            // Execute the task
            $output = $this->executeTask($task);

            // Mark as complete
            $log->markAsCompleted($output);
            $task->markAsRun();

            // Run success callback
            $this->runCallback($task->success_callback, ['output' => $output]);

            // Run after callback
            $this->runCallback($task->after_callback);

            if (function_exists('do_action')) {
                do_action('scheduled_task_completed', $task, $log);
            }

            return [
                'status' => 'completed',
                'output' => $output,
                'duration_ms' => $log->duration_ms,
            ];

        } catch (Throwable $e) {
            $log->markAsFailed($e->getMessage());

            // Run failure callback
            $this->runCallback($task->failure_callback, ['error' => $e->getMessage()]);

            // Retry if attempts remaining
            if ($log->attempt < $task->max_attempts) {
                $this->scheduleRetry($task, $log->attempt + 1);
            }

            // Email on failure
            if ($task->email_on_failure && $task->email_output) {
                $this->sendFailureEmail($task, $e);
            }

            Log::error("Scheduled task {$task->slug} failed: " . $e->getMessage());

            if (function_exists('do_action')) {
                do_action('scheduled_task_failed', $task, $log, $e);
            }

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Execute the actual task
     */
    protected function executeTask(ScheduledTask $task): ?string
    {
        $output = null;

        switch ($task->type) {
            case ScheduledTask::TYPE_CALLBACK:
                $output = $this->executeCallback($task);
                break;

            case ScheduledTask::TYPE_JOB:
                $output = $this->executeJob($task);
                break;

            case ScheduledTask::TYPE_COMMAND:
                $output = $this->executeCommand($task);
                break;

            default:
                throw new \RuntimeException("Unknown task type: {$task->type}");
        }

        // Write to output file if configured
        if ($task->output_file && $output) {
            $this->writeOutput($task, $output);
        }

        return $output;
    }

    protected function executeCallback(ScheduledTask $task): ?string
    {
        $handler = $task->getHandler();
        $class = app($handler['class']);
        $method = $handler['method'];
        $params = $task->parameters ?? [];

        ob_start();
        $result = $class->{$method}(...array_values($params));
        $output = ob_get_clean();

        return $output ?: (is_string($result) ? $result : json_encode($result));
    }

    protected function executeJob(ScheduledTask $task): ?string
    {
        $jobClass = $task->handler;
        $params = $task->parameters ?? [];

        dispatch(new $jobClass(...array_values($params)));

        return "Job {$jobClass} dispatched";
    }

    protected function executeCommand(ScheduledTask $task): ?string
    {
        $command = $task->handler;
        $params = $task->parameters ?? [];

        $exitCode = \Artisan::call($command, $params);
        $output = \Artisan::output();

        if ($exitCode !== 0) {
            throw new \RuntimeException("Command failed with exit code {$exitCode}: {$output}");
        }

        return $output;
    }

    protected function runCallback(?string $callback, array $params = []): void
    {
        if (!$callback) return;

        if (str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback);
            app($class)->{$method}($params);
        }
    }

    protected function scheduleRetry(ScheduledTask $task, int $attempt): void
    {
        // Create a delayed retry
        $delaySeconds = $task->retry_delay;
        
        Cache::put("task_retry:{$task->id}", [
            'attempt' => $attempt,
            'run_at' => now()->addSeconds($delaySeconds),
        ], $delaySeconds + 60);
    }

    protected function writeOutput(ScheduledTask $task, string $output): void
    {
        $mode = $task->append_output ? FILE_APPEND : 0;
        file_put_contents($task->output_file, $output . PHP_EOL, $mode);
    }

    protected function sendFailureEmail(ScheduledTask $task, Throwable $e): void
    {
        // Simple email notification - can be extended
        if (function_exists('do_action')) {
            do_action('scheduled_task_failure_email', $task, $e);
        }
    }

    // =========================================================================
    // Recurring Jobs
    // =========================================================================

    public function registerRecurringJob(array $config, ?string $pluginSlug = null): RecurringJob
    {
        $slug = $config['slug'] ?? null;
        if (!$slug) {
            throw new \InvalidArgumentException('Job slug is required');
        }

        $job = RecurringJob::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $config['name'] ?? $slug,
                'handler' => $config['handler'],
                'parameters' => $config['parameters'] ?? null,
                'interval_type' => $config['interval_type'] ?? RecurringJob::INTERVAL_MINUTES,
                'interval_value' => $config['interval_value'] ?? 5,
                'run_after' => $config['run_after'] ?? null,
                'run_before' => $config['run_before'] ?? null,
                'run_on_days' => $config['run_on_days'] ?? null,
                'is_active' => $config['active'] ?? true,
                'plugin_slug' => $pluginSlug,
                'meta' => $config['meta'] ?? null,
            ]
        );

        if (!$job->next_run_at) {
            $job->next_run_at = $job->calculateNextRun();
            $job->save();
        }

        return $job;
    }

    protected function runRecurringJob(RecurringJob $job): array
    {
        if (!$job->isDue()) {
            return ['status' => 'skipped', 'reason' => 'Not due'];
        }

        try {
            $handler = $job->getHandler();
            $class = app($handler['class']);
            $method = $handler['method'];
            $params = $job->parameters ?? [];

            ob_start();
            $result = $class->{$method}(...array_values($params));
            $output = ob_get_clean();

            $job->markAsRun();

            return [
                'status' => 'completed',
                'output' => $output ?: $result,
            ];
        } catch (Throwable $e) {
            Log::error("Recurring job {$job->slug} failed: " . $e->getMessage());
            $job->markAsRun(); // Still mark as run to prevent infinite loops

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    public function getTask(string $slug): ?ScheduledTask
    {
        return ScheduledTask::findBySlug($slug);
    }

    public function getAllTasks(): Collection
    {
        return ScheduledTask::byPriority()->get();
    }

    public function getActiveTasks(): Collection
    {
        return ScheduledTask::active()->byPriority()->get();
    }

    public function getDueTasks(): Collection
    {
        return ScheduledTask::due()->byPriority()->get();
    }

    public function getTasksForPlugin(string $pluginSlug): Collection
    {
        return ScheduledTask::forPlugin($pluginSlug)->get();
    }

    public function getRecurringJob(string $slug): ?RecurringJob
    {
        return RecurringJob::findBySlug($slug);
    }

    public function getAllRecurringJobs(): Collection
    {
        return RecurringJob::all();
    }

    // =========================================================================
    // Statistics
    // =========================================================================

    public function getStats(int $hours = 24): array
    {
        return [
            'tasks' => [
                'total' => ScheduledTask::count(),
                'active' => ScheduledTask::active()->count(),
                'due' => ScheduledTask::due()->count(),
            ],
            'recurring_jobs' => [
                'total' => RecurringJob::count(),
                'active' => RecurringJob::active()->count(),
                'due' => RecurringJob::due()->count(),
            ],
            'logs' => TaskLog::getStats($hours),
        ];
    }

    // =========================================================================
    // Cache
    // =========================================================================

    public function clearCache(): void
    {
        Cache::forget('scheduler:tasks');
        Cache::forget('scheduler:jobs');
    }
}
