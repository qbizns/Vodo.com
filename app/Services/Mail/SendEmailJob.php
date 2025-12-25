<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued Email Job
 *
 * Handles asynchronous email sending.
 */
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    public function __construct(
        protected string $to,
        protected string $template,
        protected array $data,
        protected array $options,
        protected string $jobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MailComposer $composer): void
    {
        try {
            $composer->send($this->to, $this->template, $this->data, $this->options);

            // Update log status
            EmailLog::where('job_id', $this->jobId)->update(['status' => 'sent']);
        } catch (\Exception $e) {
            // Update log with error
            EmailLog::where('job_id', $this->jobId)->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        EmailLog::where('job_id', $this->jobId)->update([
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ]);
    }
}
