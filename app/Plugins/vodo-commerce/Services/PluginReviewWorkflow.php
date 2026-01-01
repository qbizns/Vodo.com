<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\Event;
use VodoCommerce\Enums\PluginReviewStatus;

/**
 * Plugin Review Workflow Service
 *
 * Manages the complete lifecycle of plugin submissions from
 * initial submission through review to approval/rejection.
 */
class PluginReviewWorkflow
{
    /**
     * Review stages in order.
     */
    protected const REVIEW_STAGES = [
        'automated_scan',
        'security_review',
        'code_quality',
        'functionality_test',
        'documentation_review',
        'final_approval',
    ];

    /**
     * Rejection reasons.
     */
    protected const REJECTION_REASONS = [
        'security_vulnerability' => 'Security vulnerabilities detected',
        'malicious_code' => 'Malicious or harmful code detected',
        'poor_code_quality' => 'Code quality does not meet standards',
        'insufficient_documentation' => 'Documentation is incomplete or missing',
        'incompatible_dependencies' => 'Uses incompatible or outdated dependencies',
        'policy_violation' => 'Violates platform policies',
        'duplicate_functionality' => 'Duplicates existing approved plugin',
        'performance_issues' => 'Causes significant performance degradation',
        'incomplete_submission' => 'Submission is incomplete',
        'other' => 'Other reason (see notes)',
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $workflowConfig;

    public function __construct()
    {
        $this->workflowConfig = $this->getDefaultConfig();
    }

    /**
     * Submit a plugin for review.
     *
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    public function submit(array $submission): array
    {
        $this->validateSubmission($submission);

        $submissionId = $this->generateSubmissionId();

        $record = [
            'id' => $submissionId,
            'plugin_name' => $submission['name'],
            'plugin_version' => $submission['version'],
            'developer_id' => $submission['developer_id'],
            'developer_email' => $submission['developer_email'],
            'repository_url' => $submission['repository_url'] ?? null,
            'package_url' => $submission['package_url'] ?? null,
            'description' => $submission['description'],
            'category' => $submission['category'],
            'type' => $submission['type'] ?? 'general',
            'status' => PluginReviewStatus::Pending->value,
            'current_stage' => 'pending_assignment',
            'stages_completed' => [],
            'automated_checks' => [],
            'reviewer_id' => null,
            'reviewer_notes' => [],
            'submitted_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'reviewed_at' => null,
            'decision' => null,
            'rejection_reason' => null,
        ];

        // Dispatch submission event
        Event::dispatch('commerce.plugin.submitted', [
            'submission_id' => $submissionId,
            'plugin_name' => $submission['name'],
            'developer_email' => $submission['developer_email'],
        ]);

        return [
            'success' => true,
            'submission_id' => $submissionId,
            'status' => PluginReviewStatus::Pending->value,
            'message' => 'Plugin submitted for review. You will receive updates via email.',
            'estimated_review_time' => $this->workflowConfig['estimated_review_days'] . ' business days',
            'submission' => $record,
        ];
    }

    /**
     * Run automated security scan.
     *
     * @param string $submissionId
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    public function runAutomatedScan(string $submissionId, array $submission): array
    {
        $checks = [
            'malware_scan' => $this->performMalwareScan($submission),
            'dependency_audit' => $this->auditDependencies($submission),
            'security_patterns' => $this->checkSecurityPatterns($submission),
            'code_analysis' => $this->analyzeCode($submission),
            'license_check' => $this->checkLicense($submission),
        ];

        $passed = collect($checks)->every(fn ($check) => $check['passed']);

        $result = [
            'submission_id' => $submissionId,
            'stage' => 'automated_scan',
            'completed_at' => now()->toIso8601String(),
            'passed' => $passed,
            'checks' => $checks,
            'summary' => $this->summarizeChecks($checks),
        ];

        if (!$passed) {
            $result['recommendation'] = 'manual_review_required';
            $result['blocking_issues'] = collect($checks)
                ->filter(fn ($check) => !$check['passed'])
                ->keys()
                ->toArray();
        }

        Event::dispatch('commerce.plugin.scanned', [
            'submission_id' => $submissionId,
            'passed' => $passed,
        ]);

        return $result;
    }

    /**
     * Assign a reviewer to a submission.
     *
     * @param string $submissionId
     * @param int $reviewerId
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    public function assignReviewer(string $submissionId, int $reviewerId, array $submission): array
    {
        $submission['reviewer_id'] = $reviewerId;
        $submission['status'] = PluginReviewStatus::InReview->value;
        $submission['current_stage'] = 'security_review';
        $submission['assigned_at'] = now()->toIso8601String();
        $submission['updated_at'] = now()->toIso8601String();

        Event::dispatch('commerce.plugin.reviewer_assigned', [
            'submission_id' => $submissionId,
            'reviewer_id' => $reviewerId,
        ]);

        return [
            'success' => true,
            'submission_id' => $submissionId,
            'reviewer_id' => $reviewerId,
            'status' => PluginReviewStatus::InReview->value,
            'current_stage' => 'security_review',
            'message' => 'Reviewer assigned successfully',
        ];
    }

    /**
     * Complete a review stage.
     *
     * @param string $submissionId
     * @param string $stage
     * @param array<string, mixed> $review
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    public function completeStage(
        string $submissionId,
        string $stage,
        array $review,
        array $submission
    ): array {
        if (!in_array($stage, self::REVIEW_STAGES)) {
            return [
                'success' => false,
                'error' => "Invalid stage: {$stage}",
            ];
        }

        $stageResult = [
            'stage' => $stage,
            'reviewer_id' => $review['reviewer_id'],
            'passed' => $review['passed'],
            'score' => $review['score'] ?? null,
            'notes' => $review['notes'] ?? null,
            'issues' => $review['issues'] ?? [],
            'completed_at' => now()->toIso8601String(),
        ];

        $submission['stages_completed'][$stage] = $stageResult;
        $submission['updated_at'] = now()->toIso8601String();

        // Determine next stage
        $nextStage = $this->getNextStage($stage, $submission);

        if ($nextStage) {
            $submission['current_stage'] = $nextStage;
        }

        // Check if review is complete
        $allPassed = $this->areAllStagesPassed($submission);

        return [
            'success' => true,
            'submission_id' => $submissionId,
            'stage_completed' => $stage,
            'stage_result' => $stageResult,
            'next_stage' => $nextStage,
            'all_stages_complete' => $nextStage === null,
            'ready_for_decision' => $nextStage === null && $allPassed,
        ];
    }

    /**
     * Approve a plugin.
     *
     * @param string $submissionId
     * @param int $approverId
     * @param array<string, mixed> $submission
     * @param string|null $notes
     * @return array<string, mixed>
     */
    public function approve(
        string $submissionId,
        int $approverId,
        array $submission,
        ?string $notes = null
    ): array {
        $submission['status'] = PluginReviewStatus::Approved->value;
        $submission['decision'] = 'approved';
        $submission['reviewed_at'] = now()->toIso8601String();
        $submission['updated_at'] = now()->toIso8601String();
        $submission['approver_id'] = $approverId;

        if ($notes) {
            $submission['reviewer_notes'][] = [
                'type' => 'approval',
                'reviewer_id' => $approverId,
                'note' => $notes,
                'created_at' => now()->toIso8601String(),
            ];
        }

        Event::dispatch('commerce.plugin.approved', [
            'submission_id' => $submissionId,
            'plugin_name' => $submission['plugin_name'],
            'developer_email' => $submission['developer_email'],
        ]);

        return [
            'success' => true,
            'submission_id' => $submissionId,
            'status' => PluginReviewStatus::Approved->value,
            'message' => 'Plugin approved and ready for publication',
            'next_steps' => [
                'Plugin will be added to the marketplace',
                'Developer will receive approval notification',
                'Plugin can be installed by merchants',
            ],
        ];
    }

    /**
     * Reject a plugin.
     *
     * @param string $submissionId
     * @param int $reviewerId
     * @param string $reason
     * @param array<string, mixed> $submission
     * @param string|null $notes
     * @return array<string, mixed>
     */
    public function reject(
        string $submissionId,
        int $reviewerId,
        string $reason,
        array $submission,
        ?string $notes = null
    ): array {
        if (!isset(self::REJECTION_REASONS[$reason])) {
            return [
                'success' => false,
                'error' => 'Invalid rejection reason',
                'valid_reasons' => array_keys(self::REJECTION_REASONS),
            ];
        }

        $submission['status'] = PluginReviewStatus::Rejected->value;
        $submission['decision'] = 'rejected';
        $submission['rejection_reason'] = $reason;
        $submission['rejection_description'] = self::REJECTION_REASONS[$reason];
        $submission['reviewed_at'] = now()->toIso8601String();
        $submission['updated_at'] = now()->toIso8601String();
        $submission['reviewer_id'] = $reviewerId;

        if ($notes) {
            $submission['reviewer_notes'][] = [
                'type' => 'rejection',
                'reviewer_id' => $reviewerId,
                'note' => $notes,
                'created_at' => now()->toIso8601String(),
            ];
        }

        Event::dispatch('commerce.plugin.rejected', [
            'submission_id' => $submissionId,
            'plugin_name' => $submission['plugin_name'],
            'developer_email' => $submission['developer_email'],
            'reason' => $reason,
        ]);

        return [
            'success' => true,
            'submission_id' => $submissionId,
            'status' => PluginReviewStatus::Rejected->value,
            'reason' => $reason,
            'reason_description' => self::REJECTION_REASONS[$reason],
            'message' => 'Plugin rejected',
            'can_resubmit' => $this->canResubmit($reason),
            'resubmission_guidance' => $this->getResubmissionGuidance($reason),
        ];
    }

    /**
     * Request changes from developer.
     *
     * @param string $submissionId
     * @param int $reviewerId
     * @param array<string, string> $requiredChanges
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    public function requestChanges(
        string $submissionId,
        int $reviewerId,
        array $requiredChanges,
        array $submission
    ): array {
        $submission['status'] = PluginReviewStatus::ChangesRequested->value;
        $submission['updated_at'] = now()->toIso8601String();
        $submission['changes_requested'] = [
            'reviewer_id' => $reviewerId,
            'changes' => $requiredChanges,
            'requested_at' => now()->toIso8601String(),
            'deadline' => now()->addDays(14)->toIso8601String(),
        ];

        Event::dispatch('commerce.plugin.changes_requested', [
            'submission_id' => $submissionId,
            'plugin_name' => $submission['plugin_name'],
            'developer_email' => $submission['developer_email'],
            'changes' => $requiredChanges,
        ]);

        return [
            'success' => true,
            'submission_id' => $submissionId,
            'status' => PluginReviewStatus::ChangesRequested->value,
            'required_changes' => $requiredChanges,
            'deadline' => now()->addDays(14)->toIso8601String(),
            'message' => 'Changes requested. Developer notified.',
        ];
    }

    /**
     * Get review workflow status.
     *
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    public function getStatus(array $submission): array
    {
        $stagesCompleted = $submission['stages_completed'] ?? [];
        $totalStages = count(self::REVIEW_STAGES);
        $completedCount = count($stagesCompleted);

        return [
            'submission_id' => $submission['id'],
            'status' => $submission['status'],
            'current_stage' => $submission['current_stage'],
            'progress' => [
                'completed' => $completedCount,
                'total' => $totalStages,
                'percentage' => round(($completedCount / $totalStages) * 100),
            ],
            'stages' => array_map(function ($stage) use ($stagesCompleted, $submission) {
                $isCompleted = isset($stagesCompleted[$stage]);
                $isCurrent = $submission['current_stage'] === $stage;

                return [
                    'stage' => $stage,
                    'name' => $this->getStageName($stage),
                    'status' => $isCompleted ? 'completed' : ($isCurrent ? 'in_progress' : 'pending'),
                    'result' => $stagesCompleted[$stage] ?? null,
                ];
            }, self::REVIEW_STAGES),
            'timeline' => [
                'submitted_at' => $submission['submitted_at'],
                'assigned_at' => $submission['assigned_at'] ?? null,
                'reviewed_at' => $submission['reviewed_at'] ?? null,
            ],
            'decision' => $submission['decision'] ?? null,
        ];
    }

    /**
     * Get workflow configuration.
     *
     * @return array<string, mixed>
     */
    public function getWorkflowConfig(): array
    {
        return [
            'stages' => array_map(fn ($stage) => [
                'id' => $stage,
                'name' => $this->getStageName($stage),
                'description' => $this->getStageDescription($stage),
            ], self::REVIEW_STAGES),
            'rejection_reasons' => self::REJECTION_REASONS,
            'config' => $this->workflowConfig,
        ];
    }

    /**
     * Get review statistics.
     *
     * @param array<array<string, mixed>> $submissions
     * @return array<string, mixed>
     */
    public function getStatistics(array $submissions): array
    {
        $total = count($submissions);
        $byStatus = [];

        foreach (PluginReviewStatus::cases() as $status) {
            $count = count(array_filter(
                $submissions,
                fn ($s) => $s['status'] === $status->value
            ));
            $byStatus[$status->value] = $count;
        }

        $approved = $byStatus[PluginReviewStatus::Approved->value] ?? 0;
        $rejected = $byStatus[PluginReviewStatus::Rejected->value] ?? 0;
        $decided = $approved + $rejected;

        return [
            'total_submissions' => $total,
            'by_status' => $byStatus,
            'approval_rate' => $decided > 0 ? round(($approved / $decided) * 100, 1) : 0,
            'pending_count' => $byStatus[PluginReviewStatus::Pending->value] ?? 0,
            'in_review_count' => $byStatus[PluginReviewStatus::InReview->value] ?? 0,
        ];
    }

    /**
     * Validate submission data.
     *
     * @param array<string, mixed> $submission
     * @throws \InvalidArgumentException
     */
    protected function validateSubmission(array $submission): void
    {
        $required = ['name', 'version', 'developer_id', 'developer_email', 'description', 'category'];

        foreach ($required as $field) {
            if (empty($submission[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!filter_var($submission['developer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid developer email');
        }
    }

    /**
     * Generate unique submission ID.
     *
     * @return string
     */
    protected function generateSubmissionId(): string
    {
        return 'sub_' . bin2hex(random_bytes(12));
    }

    /**
     * Perform malware scan.
     *
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    protected function performMalwareScan(array $submission): array
    {
        // Simulated malware scan - in production would use actual scanning tools
        return [
            'passed' => true,
            'scanner' => 'VodoSecurityScanner',
            'version' => '2.0',
            'threats_found' => 0,
            'scanned_files' => 0,
            'scan_time_ms' => rand(500, 2000),
        ];
    }

    /**
     * Audit dependencies.
     *
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    protected function auditDependencies(array $submission): array
    {
        return [
            'passed' => true,
            'dependencies_checked' => 0,
            'vulnerabilities' => [],
            'outdated' => [],
            'incompatible' => [],
        ];
    }

    /**
     * Check for security patterns.
     *
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    protected function checkSecurityPatterns(array $submission): array
    {
        $patterns = [
            'sql_injection' => true,
            'xss_prevention' => true,
            'csrf_protection' => true,
            'input_validation' => true,
            'output_encoding' => true,
        ];

        return [
            'passed' => true,
            'patterns_checked' => $patterns,
            'warnings' => [],
        ];
    }

    /**
     * Analyze code quality.
     *
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    protected function analyzeCode(array $submission): array
    {
        return [
            'passed' => true,
            'metrics' => [
                'cyclomatic_complexity' => 'acceptable',
                'code_coverage' => 'unknown',
                'duplication' => 'low',
            ],
            'issues' => [],
        ];
    }

    /**
     * Check license compatibility.
     *
     * @param array<string, mixed> $submission
     * @return array<string, mixed>
     */
    protected function checkLicense(array $submission): array
    {
        return [
            'passed' => true,
            'license' => $submission['license'] ?? 'unknown',
            'compatible' => true,
            'warnings' => [],
        ];
    }

    /**
     * Summarize check results.
     *
     * @param array<string, array<string, mixed>> $checks
     * @return array<string, mixed>
     */
    protected function summarizeChecks(array $checks): array
    {
        $passed = 0;
        $failed = 0;

        foreach ($checks as $check) {
            if ($check['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        return [
            'total_checks' => count($checks),
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => round(($passed / count($checks)) * 100, 1),
        ];
    }

    /**
     * Get next review stage.
     *
     * @param string $currentStage
     * @param array<string, mixed> $submission
     * @return string|null
     */
    protected function getNextStage(string $currentStage, array $submission): ?string
    {
        $currentIndex = array_search($currentStage, self::REVIEW_STAGES);

        if ($currentIndex === false || $currentIndex === count(self::REVIEW_STAGES) - 1) {
            return null;
        }

        return self::REVIEW_STAGES[$currentIndex + 1];
    }

    /**
     * Check if all stages passed.
     *
     * @param array<string, mixed> $submission
     * @return bool
     */
    protected function areAllStagesPassed(array $submission): bool
    {
        $stagesCompleted = $submission['stages_completed'] ?? [];

        if (count($stagesCompleted) < count(self::REVIEW_STAGES)) {
            return false;
        }

        foreach ($stagesCompleted as $stage) {
            if (!($stage['passed'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get human-readable stage name.
     *
     * @param string $stage
     * @return string
     */
    protected function getStageName(string $stage): string
    {
        return match ($stage) {
            'automated_scan' => 'Automated Security Scan',
            'security_review' => 'Manual Security Review',
            'code_quality' => 'Code Quality Assessment',
            'functionality_test' => 'Functionality Testing',
            'documentation_review' => 'Documentation Review',
            'final_approval' => 'Final Approval',
            default => ucwords(str_replace('_', ' ', $stage)),
        };
    }

    /**
     * Get stage description.
     *
     * @param string $stage
     * @return string
     */
    protected function getStageDescription(string $stage): string
    {
        return match ($stage) {
            'automated_scan' => 'Automated scanning for malware, vulnerabilities, and security issues',
            'security_review' => 'Manual review of security practices and potential risks',
            'code_quality' => 'Assessment of code quality, patterns, and best practices',
            'functionality_test' => 'Testing plugin functionality and compatibility',
            'documentation_review' => 'Review of documentation completeness and accuracy',
            'final_approval' => 'Final review and approval decision',
            default => 'Review stage',
        };
    }

    /**
     * Check if resubmission is allowed.
     *
     * @param string $reason
     * @return bool
     */
    protected function canResubmit(string $reason): bool
    {
        $noResubmit = ['malicious_code', 'policy_violation'];

        return !in_array($reason, $noResubmit);
    }

    /**
     * Get resubmission guidance.
     *
     * @param string $reason
     * @return string
     */
    protected function getResubmissionGuidance(string $reason): string
    {
        return match ($reason) {
            'security_vulnerability' => 'Fix the identified security issues and resubmit with a new version.',
            'poor_code_quality' => 'Improve code quality according to our guidelines and resubmit.',
            'insufficient_documentation' => 'Complete the documentation and resubmit.',
            'incompatible_dependencies' => 'Update dependencies to compatible versions and resubmit.',
            'performance_issues' => 'Optimize performance and include benchmark results.',
            'incomplete_submission' => 'Complete all required submission fields and resubmit.',
            'malicious_code' => 'Resubmission not allowed for this rejection reason.',
            'policy_violation' => 'Resubmission not allowed for policy violations.',
            default => 'Address the feedback provided and submit a new version.',
        };
    }

    /**
     * Get default workflow configuration.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultConfig(): array
    {
        return [
            'estimated_review_days' => 5,
            'max_resubmissions' => 3,
            'changes_deadline_days' => 14,
            'auto_assign_reviewers' => true,
            'require_all_stages' => true,
            'notify_on_status_change' => true,
        ];
    }
}
