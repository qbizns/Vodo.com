<?php

namespace App\Console\Commands;

use App\Services\Scheduler\TaskScheduler;
use Illuminate\Console\Command;

class RunScheduledTasks extends Command
{
    protected $signature = 'scheduler:run 
                            {--task= : Run a specific task by slug}
                            {--force : Force run even if not due}
                            {--dry-run : Show what would run without executing}';

    protected $description = 'Run scheduled tasks that are due';

    public function handle(TaskScheduler $scheduler): int
    {
        $specificTask = $this->option('task');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($specificTask) {
            return $this->runSpecificTask($scheduler, $specificTask, $force, $dryRun);
        }

        return $this->runAllDueTasks($scheduler, $dryRun);
    }

    protected function runSpecificTask(TaskScheduler $scheduler, string $slug, bool $force, bool $dryRun): int
    {
        $task = $scheduler->getTask($slug);

        if (!$task) {
            $this->error("Task '{$slug}' not found.");
            return 1;
        }

        if ($dryRun) {
            $this->info("Would run task: {$task->name} ({$task->slug})");
            return 0;
        }

        if (!$force && !$task->isDue()) {
            $this->warn("Task '{$slug}' is not due. Use --force to run anyway.");
            return 0;
        }

        $this->info("Running task: {$task->name}...");

        $result = $scheduler->runTask($task);

        if ($result['status'] === 'completed') {
            $this->info("✓ Task completed in {$result['duration_ms']}ms");
            if ($result['output']) {
                $this->line($result['output']);
            }
            return 0;
        } elseif ($result['status'] === 'skipped') {
            $this->warn("Task skipped: {$result['reason']}");
            return 0;
        } else {
            $this->error("Task failed: {$result['error']}");
            return 1;
        }
    }

    protected function runAllDueTasks(TaskScheduler $scheduler, bool $dryRun): int
    {
        $dueTasks = $scheduler->getDueTasks();
        $dueJobs = \App\Models\RecurringJob::due()->get();

        if ($dueTasks->isEmpty() && $dueJobs->isEmpty()) {
            $this->info('No tasks due to run.');
            return 0;
        }

        $this->info('Due tasks: ' . $dueTasks->count());
        $this->info('Due recurring jobs: ' . $dueJobs->count());

        if ($dryRun) {
            $this->table(
                ['Type', 'Slug', 'Name', 'Next Run'],
                $dueTasks->map(fn($t) => ['Task', $t->slug, $t->name, $t->next_run_at?->toDateTimeString() ?? 'N/A'])
                    ->merge($dueJobs->map(fn($j) => ['Job', $j->slug, $j->name, $j->next_run_at?->toDateTimeString() ?? 'N/A']))
            );
            return 0;
        }

        $this->info('Running due tasks...');
        $this->newLine();

        $results = $scheduler->runDueTasks();
        $completed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($results as $slug => $result) {
            $status = $result['status'];

            if ($status === 'completed') {
                $this->line("  <fg=green>✓</> {$slug}");
                $completed++;
            } elseif ($status === 'skipped') {
                $this->line("  <fg=yellow>○</> {$slug} (skipped)");
                $skipped++;
            } else {
                $this->line("  <fg=red>✗</> {$slug}: {$result['error']}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Completed: {$completed}, Failed: {$failed}, Skipped: {$skipped}");

        return $failed > 0 ? 1 : 0;
    }
}
