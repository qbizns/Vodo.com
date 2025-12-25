<?php

declare(strict_types=1);

namespace App\Services\Report;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued Scheduled Report Execution Job
 *
 * Handles asynchronous scheduled report execution.
 */
class ExecuteScheduledReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 300; // 5 minutes

    public function __construct(
        protected string $scheduleId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ScheduledReportService $service): void
    {
        $service->execute($this->scheduleId);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Fire hook for monitoring
        do_action('scheduled_report_job_failed', $this->scheduleId, $exception);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'scheduled-report',
            'schedule:' . $this->scheduleId,
        ];
    }
}
