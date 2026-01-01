<?php

declare(strict_types=1);

namespace VodoCommerce\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use VodoCommerce\Enums\PluginReviewStatus;
use VodoCommerce\Services\PluginReviewWorkflow;

/**
 * Tests for the Plugin Review Workflow.
 */
class PluginReviewWorkflowTest extends TestCase
{
    protected PluginReviewWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = new PluginReviewWorkflow();
    }

    #[Test]
    public function it_submits_a_plugin_for_review(): void
    {
        $submission = [
            'name' => 'Test Payment Plugin',
            'version' => '1.0.0',
            'developer_id' => 1,
            'developer_email' => 'developer@example.com',
            'description' => 'A test payment plugin',
            'category' => 'payment',
            'type' => 'payment',
        ];

        $result = $this->workflow->submit($submission);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('submission_id', $result);
        $this->assertStringStartsWith('sub_', $result['submission_id']);
        $this->assertEquals(PluginReviewStatus::Pending->value, $result['status']);
        $this->assertArrayHasKey('estimated_review_time', $result);
    }

    #[Test]
    public function it_validates_required_fields_on_submission(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: name');

        $this->workflow->submit([
            'version' => '1.0.0',
            'developer_id' => 1,
        ]);
    }

    #[Test]
    public function it_validates_email_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid developer email');

        $this->workflow->submit([
            'name' => 'Test Plugin',
            'version' => '1.0.0',
            'developer_id' => 1,
            'developer_email' => 'invalid-email',
            'description' => 'A test plugin',
            'category' => 'general',
        ]);
    }

    #[Test]
    public function it_runs_automated_scan(): void
    {
        $submissionId = 'sub_test123';
        $submission = $this->getTestSubmission();

        $result = $this->workflow->runAutomatedScan($submissionId, $submission);

        $this->assertEquals($submissionId, $result['submission_id']);
        $this->assertEquals('automated_scan', $result['stage']);
        $this->assertIsBool($result['passed']);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('malware_scan', $result['checks']);
        $this->assertArrayHasKey('dependency_audit', $result['checks']);
        $this->assertArrayHasKey('security_patterns', $result['checks']);
        $this->assertArrayHasKey('summary', $result);
    }

    #[Test]
    public function it_assigns_a_reviewer(): void
    {
        $submissionId = 'sub_test123';
        $reviewerId = 42;
        $submission = $this->getTestSubmission();

        $result = $this->workflow->assignReviewer($submissionId, $reviewerId, $submission);

        $this->assertTrue($result['success']);
        $this->assertEquals($submissionId, $result['submission_id']);
        $this->assertEquals($reviewerId, $result['reviewer_id']);
        $this->assertEquals(PluginReviewStatus::InReview->value, $result['status']);
        $this->assertEquals('security_review', $result['current_stage']);
    }

    #[Test]
    public function it_completes_review_stages(): void
    {
        $submissionId = 'sub_test123';
        $submission = $this->getTestSubmission();

        $review = [
            'reviewer_id' => 42,
            'passed' => true,
            'score' => 85,
            'notes' => 'Looks good',
            'issues' => [],
        ];

        $result = $this->workflow->completeStage($submissionId, 'security_review', $review, $submission);

        $this->assertTrue($result['success']);
        $this->assertEquals('security_review', $result['stage_completed']);
        $this->assertArrayHasKey('stage_result', $result);
        $this->assertTrue($result['stage_result']['passed']);
        $this->assertEquals('code_quality', $result['next_stage']);
    }

    #[Test]
    public function it_rejects_invalid_stage(): void
    {
        $submissionId = 'sub_test123';
        $submission = $this->getTestSubmission();

        $result = $this->workflow->completeStage($submissionId, 'invalid_stage', [], $submission);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid stage', $result['error']);
    }

    #[Test]
    public function it_approves_a_plugin(): void
    {
        $submissionId = 'sub_test123';
        $approverId = 42;
        $submission = $this->getTestSubmission();

        $result = $this->workflow->approve($submissionId, $approverId, $submission, 'Great plugin!');

        $this->assertTrue($result['success']);
        $this->assertEquals(PluginReviewStatus::Approved->value, $result['status']);
        $this->assertArrayHasKey('next_steps', $result);
        $this->assertNotEmpty($result['next_steps']);
    }

    #[Test]
    public function it_rejects_a_plugin_with_valid_reason(): void
    {
        $submissionId = 'sub_test123';
        $reviewerId = 42;
        $submission = $this->getTestSubmission();

        $result = $this->workflow->reject(
            $submissionId,
            $reviewerId,
            'security_vulnerability',
            $submission,
            'Found XSS vulnerability'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(PluginReviewStatus::Rejected->value, $result['status']);
        $this->assertEquals('security_vulnerability', $result['reason']);
        $this->assertTrue($result['can_resubmit']);
        $this->assertArrayHasKey('resubmission_guidance', $result);
    }

    #[Test]
    public function it_rejects_invalid_rejection_reason(): void
    {
        $submissionId = 'sub_test123';
        $submission = $this->getTestSubmission();

        $result = $this->workflow->reject($submissionId, 42, 'invalid_reason', $submission);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('valid_reasons', $result);
    }

    #[Test]
    public function it_prevents_resubmission_for_malicious_code(): void
    {
        $submissionId = 'sub_test123';
        $submission = $this->getTestSubmission();

        $result = $this->workflow->reject($submissionId, 42, 'malicious_code', $submission);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['can_resubmit']);
    }

    #[Test]
    public function it_requests_changes(): void
    {
        $submissionId = 'sub_test123';
        $reviewerId = 42;
        $submission = $this->getTestSubmission();

        $changes = [
            'Add input validation to payment form',
            'Include unit tests for core functionality',
            'Update documentation for webhook integration',
        ];

        $result = $this->workflow->requestChanges($submissionId, $reviewerId, $changes, $submission);

        $this->assertTrue($result['success']);
        $this->assertEquals(PluginReviewStatus::ChangesRequested->value, $result['status']);
        $this->assertCount(3, $result['required_changes']);
        $this->assertArrayHasKey('deadline', $result);
    }

    #[Test]
    public function it_gets_submission_status(): void
    {
        $submission = $this->getTestSubmission([
            'stages_completed' => [
                'automated_scan' => ['passed' => true, 'completed_at' => now()->toIso8601String()],
                'security_review' => ['passed' => true, 'completed_at' => now()->toIso8601String()],
            ],
            'current_stage' => 'code_quality',
        ]);

        $status = $this->workflow->getStatus($submission);

        $this->assertArrayHasKey('progress', $status);
        $this->assertEquals(2, $status['progress']['completed']);
        $this->assertEquals(6, $status['progress']['total']);
        $this->assertArrayHasKey('stages', $status);
        $this->assertCount(6, $status['stages']);
    }

    #[Test]
    public function it_gets_workflow_configuration(): void
    {
        $config = $this->workflow->getWorkflowConfig();

        $this->assertArrayHasKey('stages', $config);
        $this->assertArrayHasKey('rejection_reasons', $config);
        $this->assertArrayHasKey('config', $config);

        $this->assertCount(6, $config['stages']);
        $this->assertNotEmpty($config['rejection_reasons']);
    }

    #[Test]
    public function it_calculates_statistics(): void
    {
        $submissions = [
            ['status' => PluginReviewStatus::Pending->value],
            ['status' => PluginReviewStatus::InReview->value],
            ['status' => PluginReviewStatus::Approved->value],
            ['status' => PluginReviewStatus::Approved->value],
            ['status' => PluginReviewStatus::Rejected->value],
        ];

        $stats = $this->workflow->getStatistics($submissions);

        $this->assertEquals(5, $stats['total_submissions']);
        $this->assertEquals(1, $stats['pending_count']);
        $this->assertEquals(1, $stats['in_review_count']);
        $this->assertEqualsWithDelta(66.7, $stats['approval_rate'], 0.1); // 2/3 approved
    }

    #[Test]
    public function plugin_review_status_enum_has_correct_labels(): void
    {
        $this->assertEquals('Pending Review', PluginReviewStatus::Pending->label());
        $this->assertEquals('In Review', PluginReviewStatus::InReview->label());
        $this->assertEquals('Changes Requested', PluginReviewStatus::ChangesRequested->label());
        $this->assertEquals('Approved', PluginReviewStatus::Approved->label());
        $this->assertEquals('Rejected', PluginReviewStatus::Rejected->label());
    }

    #[Test]
    public function plugin_review_status_enum_has_correct_transitions(): void
    {
        $this->assertTrue(PluginReviewStatus::Pending->canTransitionTo(PluginReviewStatus::InReview));
        $this->assertTrue(PluginReviewStatus::InReview->canTransitionTo(PluginReviewStatus::Approved));
        $this->assertTrue(PluginReviewStatus::InReview->canTransitionTo(PluginReviewStatus::Rejected));
        $this->assertFalse(PluginReviewStatus::Pending->canTransitionTo(PluginReviewStatus::Approved));
        $this->assertFalse(PluginReviewStatus::Rejected->canTransitionTo(PluginReviewStatus::Approved));
    }

    #[Test]
    public function plugin_review_status_enum_identifies_terminal_states(): void
    {
        $this->assertTrue(PluginReviewStatus::Approved->isTerminal());
        $this->assertTrue(PluginReviewStatus::Rejected->isTerminal());
        $this->assertFalse(PluginReviewStatus::Pending->isTerminal());
        $this->assertFalse(PluginReviewStatus::InReview->isTerminal());
    }

    /**
     * Get test submission data.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function getTestSubmission(array $overrides = []): array
    {
        return array_merge([
            'id' => 'sub_test123',
            'plugin_name' => 'Test Plugin',
            'plugin_version' => '1.0.0',
            'developer_id' => 1,
            'developer_email' => 'developer@example.com',
            'description' => 'A test plugin',
            'category' => 'general',
            'status' => PluginReviewStatus::Pending->value,
            'current_stage' => 'pending_assignment',
            'stages_completed' => [],
            'submitted_at' => now()->toIso8601String(),
        ], $overrides);
    }
}
