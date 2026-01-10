<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Resources\EmailCampaignResource;
use VodoCommerce\Models\EmailCampaign;

class EmailCampaignController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EmailCampaign::query();

        // Filter by store
        if ($request->has('store_id')) {
            $query->forStore((int) $request->get('store_id'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->get('type'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->get('status'));
        }

        // Filter active campaigns
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filter scheduled
        if ($request->boolean('scheduled_only')) {
            $query->scheduled();
        }

        // Filter completed
        if ($request->boolean('completed_only')) {
            $query->completed();
        }

        // Filter A/B tests
        if ($request->boolean('ab_tests_only')) {
            $query->abTests();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Relationships
        if ($request->has('include')) {
            $includes = explode(',', $request->get('include'));
            $query->with($includes);
        }

        return EmailCampaignResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:commerce_stores,id',
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'type' => 'required|in:newsletter,promotional,abandoned_cart,post_purchase,welcome_series,win_back,product_recommendation,seasonal,drip_campaign,transactional,other',
            'template_id' => 'nullable|exists:commerce_email_templates,id',
            'list_id' => 'nullable|exists:commerce_email_lists,id',
            'segment_conditions' => 'nullable|array',
            'is_ab_test' => 'boolean',
            'ab_test_config' => 'nullable|array',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'meta' => 'nullable|array',
        ]);

        $campaign = EmailCampaign::create($validated);

        return response()->json([
            'message' => 'Email campaign created successfully',
            'data' => EmailCampaignResource::make($campaign),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = EmailCampaign::with(['template', 'list', 'sends'])->findOrFail($id);

        return response()->json([
            'data' => EmailCampaignResource::make($campaign),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'preview_text' => 'nullable|string',
            'from_name' => 'sometimes|string|max:255',
            'from_email' => 'sometimes|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'type' => 'sometimes|in:newsletter,promotional,abandoned_cart,post_purchase,welcome_series,win_back,product_recommendation,seasonal,drip_campaign,transactional,other',
            'template_id' => 'nullable|exists:commerce_email_templates,id',
            'list_id' => 'nullable|exists:commerce_email_lists,id',
            'segment_conditions' => 'nullable|array',
            'is_ab_test' => 'boolean',
            'ab_test_config' => 'nullable|array',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'meta' => 'nullable|array',
        ]);

        $campaign->update($validated);

        return response()->json([
            'message' => 'Email campaign updated successfully',
            'data' => EmailCampaignResource::make($campaign->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);
        $campaign->delete();

        return response()->json([
            'message' => 'Email campaign deleted successfully',
        ]);
    }

    // =========================================================================
    // WORKFLOW ACTIONS
    // =========================================================================

    public function schedule(Request $request, int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $success = $campaign->schedule(new \DateTime($validated['scheduled_at']));

        if (!$success) {
            return response()->json([
                'message' => 'Cannot schedule campaign. It must be in draft or paused status.',
            ], 422);
        }

        return response()->json([
            'message' => 'Campaign scheduled successfully',
            'data' => EmailCampaignResource::make($campaign->fresh()),
        ]);
    }

    public function send(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $success = $campaign->send();

        if (!$success) {
            return response()->json([
                'message' => 'Cannot send campaign. It must be in draft or scheduled status.',
            ], 422);
        }

        return response()->json([
            'message' => 'Campaign send initiated successfully',
            'data' => EmailCampaignResource::make($campaign->fresh()),
        ]);
    }

    public function pause(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $success = $campaign->pause();

        if (!$success) {
            return response()->json([
                'message' => 'Cannot pause campaign. It must be in scheduled or sending status.',
            ], 422);
        }

        return response()->json([
            'message' => 'Campaign paused successfully',
            'data' => EmailCampaignResource::make($campaign->fresh()),
        ]);
    }

    public function resume(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $success = $campaign->resume();

        if (!$success) {
            return response()->json([
                'message' => 'Cannot resume campaign. It must be in paused status.',
            ], 422);
        }

        return response()->json([
            'message' => 'Campaign resumed successfully',
            'data' => EmailCampaignResource::make($campaign->fresh()),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $success = $campaign->cancel();

        if (!$success) {
            return response()->json([
                'message' => 'Cannot cancel campaign.',
            ], 422);
        }

        return response()->json([
            'message' => 'Campaign cancelled successfully',
            'data' => EmailCampaignResource::make($campaign->fresh()),
        ]);
    }

    public function selectAbTestWinner(Request $request, int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $validated = $request->validate([
            'variant' => 'required|string|in:A,B,C,D',
        ]);

        $success = $campaign->selectAbTestWinner($validated['variant']);

        if (!$success) {
            return response()->json([
                'message' => 'Cannot select A/B test winner. Campaign must be an A/B test and in sent status.',
            ], 422);
        }

        return response()->json([
            'message' => 'A/B test winner selected successfully',
            'data' => EmailCampaignResource::make($campaign->fresh()),
        ]);
    }

    public function recordConversion(Request $request, int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        $validated = $request->validate([
            'revenue' => 'required|numeric|min:0',
        ]);

        $campaign->recordConversion((float) $validated['revenue']);

        return response()->json([
            'message' => 'Conversion recorded successfully',
            'data' => EmailCampaignResource::make($campaign->fresh()),
        ]);
    }

    // =========================================================================
    // ANALYTICS & REPORTING
    // =========================================================================

    public function analytics(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);

        return response()->json([
            'data' => [
                'campaign_id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'statistics' => [
                    'total_recipients' => $campaign->total_recipients,
                    'emails_sent' => $campaign->emails_sent,
                    'emails_delivered' => $campaign->emails_delivered,
                    'emails_opened' => $campaign->emails_opened,
                    'unique_opens' => $campaign->unique_opens,
                    'emails_clicked' => $campaign->emails_clicked,
                    'unique_clicks' => $campaign->unique_clicks,
                    'emails_bounced' => $campaign->emails_bounced,
                    'emails_unsubscribed' => $campaign->emails_unsubscribed,
                    'emails_complained' => $campaign->emails_complained,
                ],
                'metrics' => [
                    'open_rate' => (float) $campaign->open_rate,
                    'click_rate' => (float) $campaign->click_rate,
                    'click_to_open_rate' => (float) $campaign->click_to_open_rate,
                    'bounce_rate' => (float) $campaign->bounce_rate,
                    'unsubscribe_rate' => (float) $campaign->unsubscribe_rate,
                    'conversion_rate' => (float) $campaign->conversion_rate,
                ],
                'revenue' => [
                    'total_revenue' => (float) $campaign->total_revenue,
                    'conversions' => $campaign->conversions,
                    'revenue_per_email' => $campaign->emails_sent > 0
                        ? (float) $campaign->total_revenue / $campaign->emails_sent
                        : 0,
                ],
                'engagement' => [
                    'engagement_score' => $campaign->getEngagementScore(),
                ],
                'ab_test' => $campaign->is_ab_test ? [
                    'is_ab_test' => true,
                    'config' => $campaign->ab_test_config,
                    'winner' => $campaign->ab_test_winner,
                ] : null,
            ],
        ]);
    }
}
