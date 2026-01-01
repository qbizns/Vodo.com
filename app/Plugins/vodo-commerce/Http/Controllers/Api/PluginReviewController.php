<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Services\PluginReviewWorkflow;

/**
 * Plugin Review API Controller
 *
 * Handles plugin submission and review workflow API endpoints.
 */
class PluginReviewController extends Controller
{
    public function __construct(
        protected PluginReviewWorkflow $workflow
    ) {
    }

    /**
     * Submit a plugin for review.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'version' => 'required|string|max:20',
            'developer_id' => 'required|integer',
            'developer_email' => 'required|email',
            'description' => 'required|string|max:5000',
            'category' => 'required|string|in:payment,shipping,tax,analytics,marketing,inventory,crm,general',
            'type' => 'nullable|string|in:payment,shipping,tax,analytics,general',
            'repository_url' => 'nullable|url',
            'package_url' => 'nullable|url',
            'license' => 'nullable|string|max:50',
            'documentation_url' => 'nullable|url',
            'support_email' => 'nullable|email',
        ]);

        try {
            $result = $this->workflow->submit($validated);

            return response()->json($result, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get submission status.
     *
     * @param string $submissionId
     * @return JsonResponse
     */
    public function status(string $submissionId): JsonResponse
    {
        // In production, this would fetch from database
        $submission = $this->getSubmission($submissionId);

        if (!$submission) {
            return response()->json([
                'error' => 'Submission not found',
            ], 404);
        }

        $status = $this->workflow->getStatus($submission);

        return response()->json([
            'data' => $status,
        ]);
    }

    /**
     * Get workflow configuration.
     *
     * @return JsonResponse
     */
    public function workflowConfig(): JsonResponse
    {
        return response()->json([
            'data' => $this->workflow->getWorkflowConfig(),
        ]);
    }

    /**
     * Run automated scan (admin only).
     *
     * @param string $submissionId
     * @return JsonResponse
     */
    public function runScan(string $submissionId): JsonResponse
    {
        $submission = $this->getSubmission($submissionId);

        if (!$submission) {
            return response()->json([
                'error' => 'Submission not found',
            ], 404);
        }

        $result = $this->workflow->runAutomatedScan($submissionId, $submission);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Assign reviewer (admin only).
     *
     * @param Request $request
     * @param string $submissionId
     * @return JsonResponse
     */
    public function assignReviewer(Request $request, string $submissionId): JsonResponse
    {
        $validated = $request->validate([
            'reviewer_id' => 'required|integer',
        ]);

        $submission = $this->getSubmission($submissionId);

        if (!$submission) {
            return response()->json([
                'error' => 'Submission not found',
            ], 404);
        }

        $result = $this->workflow->assignReviewer(
            $submissionId,
            $validated['reviewer_id'],
            $submission
        );

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Complete review stage (admin only).
     *
     * @param Request $request
     * @param string $submissionId
     * @param string $stage
     * @return JsonResponse
     */
    public function completeStage(Request $request, string $submissionId, string $stage): JsonResponse
    {
        $validated = $request->validate([
            'passed' => 'required|boolean',
            'reviewer_id' => 'required|integer',
            'score' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string|max:5000',
            'issues' => 'nullable|array',
            'issues.*' => 'string',
        ]);

        $submission = $this->getSubmission($submissionId);

        if (!$submission) {
            return response()->json([
                'error' => 'Submission not found',
            ], 404);
        }

        $result = $this->workflow->completeStage(
            $submissionId,
            $stage,
            $validated,
            $submission
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Approve plugin (admin only).
     *
     * @param Request $request
     * @param string $submissionId
     * @return JsonResponse
     */
    public function approve(Request $request, string $submissionId): JsonResponse
    {
        $validated = $request->validate([
            'approver_id' => 'required|integer',
            'notes' => 'nullable|string|max:5000',
        ]);

        $submission = $this->getSubmission($submissionId);

        if (!$submission) {
            return response()->json([
                'error' => 'Submission not found',
            ], 404);
        }

        $result = $this->workflow->approve(
            $submissionId,
            $validated['approver_id'],
            $submission,
            $validated['notes'] ?? null
        );

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Reject plugin (admin only).
     *
     * @param Request $request
     * @param string $submissionId
     * @return JsonResponse
     */
    public function reject(Request $request, string $submissionId): JsonResponse
    {
        $validated = $request->validate([
            'reviewer_id' => 'required|integer',
            'reason' => 'required|string',
            'notes' => 'nullable|string|max:5000',
        ]);

        $submission = $this->getSubmission($submissionId);

        if (!$submission) {
            return response()->json([
                'error' => 'Submission not found',
            ], 404);
        }

        $result = $this->workflow->reject(
            $submissionId,
            $validated['reviewer_id'],
            $validated['reason'],
            $submission,
            $validated['notes'] ?? null
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Request changes (admin only).
     *
     * @param Request $request
     * @param string $submissionId
     * @return JsonResponse
     */
    public function requestChanges(Request $request, string $submissionId): JsonResponse
    {
        $validated = $request->validate([
            'reviewer_id' => 'required|integer',
            'changes' => 'required|array',
            'changes.*' => 'string',
        ]);

        $submission = $this->getSubmission($submissionId);

        if (!$submission) {
            return response()->json([
                'error' => 'Submission not found',
            ], 404);
        }

        $result = $this->workflow->requestChanges(
            $submissionId,
            $validated['reviewer_id'],
            $validated['changes'],
            $submission
        );

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Get submission (mock implementation).
     *
     * In production, this would fetch from database.
     *
     * @param string $submissionId
     * @return array<string, mixed>|null
     */
    protected function getSubmission(string $submissionId): ?array
    {
        // Mock implementation - in production, fetch from database
        if (!str_starts_with($submissionId, 'sub_')) {
            return null;
        }

        return [
            'id' => $submissionId,
            'plugin_name' => 'Mock Plugin',
            'plugin_version' => '1.0.0',
            'developer_id' => 1,
            'developer_email' => 'developer@example.com',
            'description' => 'A mock plugin for testing',
            'category' => 'general',
            'status' => 'pending',
            'current_stage' => 'pending_assignment',
            'stages_completed' => [],
            'submitted_at' => now()->subDays(2)->toIso8601String(),
        ];
    }
}
