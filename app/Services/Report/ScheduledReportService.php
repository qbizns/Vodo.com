<?php

declare(strict_types=1);

namespace App\Services\Report;

use App\Contracts\ScheduledReportContract;
use App\Contracts\ReportRegistryContract;
use App\Contracts\MailComposerContract;
use App\Models\ReportSchedule;
use App\Models\ReportScheduleLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Scheduled Report Service
 *
 * Manages scheduled report execution and delivery.
 * Supports recurring schedules with email delivery.
 *
 * @example Create a scheduled report
 * ```php
 * $service->create('monthly_sales', [
 *     'frequency' => 'monthly',
 *     'day_of_month' => 1,
 *     'hour' => 8,
 *     'format' => 'pdf',
 *     'parameters' => ['region' => 'all'],
 * ], [
 *     'sales@company.com',
 *     'management@company.com',
 * ]);
 * ```
 */
class ScheduledReportService implements ScheduledReportContract
{
    public function __construct(
        protected ReportRegistryContract $reportRegistry,
        protected MailComposerContract $mailComposer
    ) {}

    public function create(string $reportName, array $schedule, array $options = []): array
    {
        // Validate report exists
        if (!$this->reportRegistry->has($reportName)) {
            throw new \InvalidArgumentException("Report not found: {$reportName}");
        }

        $scheduleRecord = ReportSchedule::create([
            'id' => Str::uuid()->toString(),
            'report_slug' => $reportName,
            'name' => $options['name'] ?? $this->generateName($reportName, $schedule),
            'frequency' => $schedule['frequency'] ?? 'daily',
            'schedule_config' => $schedule,
            'recipients' => $options['recipients'] ?? [],
            'parameters' => $schedule['parameters'] ?? [],
            'format' => $schedule['format'] ?? 'pdf',
            'is_active' => true,
            'next_run_at' => $this->calculateNextRun($schedule),
            'timezone' => $schedule['timezone'] ?? config('app.timezone'),
            'created_by' => auth()->id(),
        ]);

        // Fire hook
        do_action('scheduled_report_created', $scheduleRecord);

        return $scheduleRecord->toArray();
    }

    public function update(string $id, array $data): array
    {
        $schedule = ReportSchedule::findOrFail($id);

        // Recalculate next run if schedule changed
        if (isset($data['schedule_config']) || isset($data['frequency'])) {
            $scheduleConfig = $data['schedule_config'] ?? $schedule->schedule_config;
            $scheduleConfig['frequency'] = $data['frequency'] ?? $schedule->frequency;
            $data['next_run_at'] = $this->calculateNextRun($scheduleConfig);
        }

        $schedule->update($data);

        // Fire hook
        do_action('scheduled_report_updated', $schedule);

        return $schedule->fresh()->toArray();
    }

    public function delete(string $id): bool
    {
        $schedule = ReportSchedule::find($id);

        if (!$schedule) {
            return false;
        }

        // Delete associated logs
        ReportScheduleLog::where('schedule_id', $id)->delete();

        // Fire hook
        do_action('scheduled_report_deleted', $schedule);

        return $schedule->delete();
    }

    public function get(string $id): ?array
    {
        $schedule = ReportSchedule::find($id);

        return $schedule ? $schedule->toArray() : null;
    }

    public function all(?string $reportName = null): Collection
    {
        $query = ReportSchedule::query();

        if ($reportName) {
            $query->where('report_slug', $reportName);
        }

        return $query->orderBy('next_run_at')->get();
    }

    public function getDue(): Collection
    {
        return ReportSchedule::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->get();
    }

    public function execute(string $id): array
    {
        $schedule = ReportSchedule::findOrFail($id);

        $log = ReportScheduleLog::create([
            'schedule_id' => $id,
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            // Generate report
            $reportPath = $this->reportRegistry->export(
                $schedule->report_slug,
                $schedule->parameters ?? [],
                $schedule->format
            );

            // Send to recipients
            $this->sendToRecipients($schedule, $reportPath);

            // Update log
            $log->update([
                'completed_at' => now(),
                'status' => 'success',
                'file_path' => $reportPath,
                'recipients_count' => count($schedule->recipients),
            ]);

            // Update schedule with next run time
            $schedule->update([
                'next_run_at' => $this->calculateNextRun($schedule->schedule_config),
                'last_run_at' => now(),
                'last_run_status' => 'success',
            ]);

            // Fire hook
            do_action('scheduled_report_executed', $schedule, $log);

            return [
                'success' => true,
                'log_id' => $log->id,
                'file_path' => $reportPath,
            ];
        } catch (\Exception $e) {
            // Update log with error
            $log->update([
                'completed_at' => now(),
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            // Update schedule
            $schedule->update([
                'next_run_at' => $this->calculateNextRun($schedule->schedule_config),
                'last_run_at' => now(),
                'last_run_status' => 'failed',
            ]);

            // Fire hook
            do_action('scheduled_report_failed', $schedule, $e);

            return [
                'success' => false,
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function pause(string $id): bool
    {
        $schedule = ReportSchedule::find($id);

        if (!$schedule) {
            return false;
        }

        $schedule->update(['is_active' => false]);

        do_action('scheduled_report_paused', $schedule);

        return true;
    }

    public function resume(string $id): bool
    {
        $schedule = ReportSchedule::find($id);

        if (!$schedule) {
            return false;
        }

        $schedule->update([
            'is_active' => true,
            'next_run_at' => $this->calculateNextRun($schedule->schedule_config),
        ]);

        do_action('scheduled_report_resumed', $schedule);

        return true;
    }

    public function getHistory(string $id, int $limit = 10): Collection
    {
        return ReportScheduleLog::where('schedule_id', $id)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Send report to recipients.
     */
    protected function sendToRecipients(ReportSchedule $schedule, string $filePath): void
    {
        $reportConfig = $this->reportRegistry->get($schedule->report_slug);

        foreach ($schedule->recipients as $recipient) {
            $this->mailComposer->send($recipient, 'scheduled_report', [
                'report_name' => $reportConfig['title'] ?? $schedule->report_slug,
                'schedule_name' => $schedule->name,
                'generated_at' => now()->format('Y-m-d H:i:s'),
            ], [
                'attachments' => [
                    [
                        'path' => $filePath,
                        'name' => basename($filePath),
                    ],
                ],
            ]);
        }
    }

    /**
     * Calculate next run time.
     */
    protected function calculateNextRun(array $schedule): string
    {
        $frequency = $schedule['frequency'] ?? 'daily';
        $hour = $schedule['hour'] ?? 8;
        $minute = $schedule['minute'] ?? 0;

        $next = now();

        switch ($frequency) {
            case 'hourly':
                $next = $next->addHour()->startOfHour();
                break;

            case 'daily':
                $next = $next->addDay()->setTime($hour, $minute);
                break;

            case 'weekly':
                $dayOfWeek = $schedule['day_of_week'] ?? 1; // Monday
                $next = $next->next($this->getDayName($dayOfWeek))->setTime($hour, $minute);
                break;

            case 'monthly':
                $dayOfMonth = $schedule['day_of_month'] ?? 1;
                $next = $next->addMonth()->startOfMonth()
                    ->addDays($dayOfMonth - 1)->setTime($hour, $minute);
                break;

            case 'quarterly':
                $next = $next->addQuarter()->startOfQuarter()->setTime($hour, $minute);
                break;

            case 'yearly':
                $month = $schedule['month'] ?? 1;
                $dayOfMonth = $schedule['day_of_month'] ?? 1;
                $next = $next->addYear()->setMonth($month)->setDay($dayOfMonth)->setTime($hour, $minute);
                break;

            default:
                $next = $next->addDay()->setTime($hour, $minute);
        }

        return $next->toDateTimeString();
    }

    /**
     * Get day name from number.
     */
    protected function getDayName(int $day): string
    {
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        return $days[$day] ?? 'monday';
    }

    /**
     * Generate schedule name.
     */
    protected function generateName(string $reportName, array $schedule): string
    {
        $frequency = ucfirst($schedule['frequency'] ?? 'daily');

        return "{$frequency} - " . Str::title(str_replace(['_', '-'], ' ', $reportName));
    }

    /**
     * Run all due scheduled reports.
     */
    public function runDue(): array
    {
        $due = $this->getDue();
        $results = [];

        foreach ($due as $schedule) {
            Queue::push(new ExecuteScheduledReportJob($schedule->id));
            $results[] = $schedule->id;
        }

        return $results;
    }

    /**
     * Get statistics for scheduled reports.
     */
    public function getStatistics(?string $scheduleId = null): array
    {
        $query = ReportScheduleLog::query();

        if ($scheduleId) {
            $query->where('schedule_id', $scheduleId);
        }

        return [
            'total_runs' => $query->count(),
            'successful' => (clone $query)->where('status', 'success')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'avg_duration_seconds' => (clone $query)
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg')
                ->value('avg'),
        ];
    }
}
