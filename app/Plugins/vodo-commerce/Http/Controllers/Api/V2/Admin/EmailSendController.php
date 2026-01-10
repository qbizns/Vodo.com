<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Resources\EmailSendResource;
use VodoCommerce\Models\EmailSend;

class EmailSendController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EmailSend::query();

        // Filter by campaign
        if ($request->has('campaign_id')) {
            $query->forCampaign((int) $request->get('campaign_id'));
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->forCustomer((int) $request->get('customer_id'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->get('type'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->get('status'));
        }

        // Filter by recipient email
        if ($request->has('recipient_email')) {
            $query->toRecipient($request->get('recipient_email'));
        }

        // Filter by opened
        if ($request->boolean('opened_only')) {
            $query->opened();
        }

        // Filter by clicked
        if ($request->boolean('clicked_only')) {
            $query->clicked();
        }

        // Filter by converted
        if ($request->boolean('converted_only')) {
            $query->converted();
        }

        // Filter by bounced
        if ($request->boolean('bounced_only')) {
            $query->bounced();
        }

        // Filter by date range
        if ($request->has('sent_after')) {
            $query->sentAfter(new \DateTime($request->get('sent_after')));
        }

        if ($request->has('sent_before')) {
            $query->sentBefore(new \DateTime($request->get('sent_before')));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
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

        return EmailSendResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function show(int $id): JsonResponse
    {
        $send = EmailSend::with(['campaign', 'template', 'customer', 'events'])->findOrFail($id);

        return response()->json([
            'data' => EmailSendResource::make($send),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);
        $send->delete();

        return response()->json([
            'message' => 'Email send record deleted successfully',
        ]);
    }

    // =========================================================================
    // SEND TRACKING ACTIONS
    // =========================================================================

    public function markAsSent(Request $request, int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);

        $validated = $request->validate([
            'message_id' => 'required|string',
            'provider' => 'required|string',
            'provider_message_id' => 'nullable|string',
        ]);

        $success = $send->markAsSent(
            $validated['message_id'],
            $validated['provider'],
            $validated['provider_message_id'] ?? null
        );

        if (!$success) {
            return response()->json([
                'message' => 'Email send is not in pending or queued status',
            ], 422);
        }

        return response()->json([
            'message' => 'Email marked as sent successfully',
            'data' => EmailSendResource::make($send->fresh()),
        ]);
    }

    public function markAsDelivered(int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);

        $success = $send->markAsDelivered();

        if (!$success) {
            return response()->json([
                'message' => 'Email send is not in sent status',
            ], 422);
        }

        return response()->json([
            'message' => 'Email marked as delivered successfully',
            'data' => EmailSendResource::make($send->fresh()),
        ]);
    }

    public function markAsFailed(Request $request, int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);

        $validated = $request->validate([
            'error_message' => 'required|string',
        ]);

        $success = $send->markAsFailed($validated['error_message']);

        if (!$success) {
            return response()->json([
                'message' => 'Email send cannot be marked as failed',
            ], 422);
        }

        return response()->json([
            'message' => 'Email marked as failed successfully',
            'data' => EmailSendResource::make($send->fresh()),
        ]);
    }

    public function markAsBounced(Request $request, int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);

        $validated = $request->validate([
            'bounce_type' => 'required|in:hard,soft,complaint',
            'bounce_reason' => 'nullable|string',
        ]);

        $success = $send->markAsBounced(
            $validated['bounce_type'],
            $validated['bounce_reason'] ?? null
        );

        if (!$success) {
            return response()->json([
                'message' => 'Email send cannot be marked as bounced',
            ], 422);
        }

        return response()->json([
            'message' => 'Email marked as bounced successfully',
            'data' => EmailSendResource::make($send->fresh()),
        ]);
    }

    public function recordOpen(int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);
        $send->recordOpen();

        return response()->json([
            'message' => 'Email open recorded successfully',
            'data' => EmailSendResource::make($send->fresh()),
        ]);
    }

    public function recordClick(Request $request, int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);

        $validated = $request->validate([
            'link_url' => 'nullable|url',
        ]);

        $send->recordClick($validated['link_url'] ?? null);

        return response()->json([
            'message' => 'Email click recorded successfully',
            'data' => EmailSendResource::make($send->fresh()),
        ]);
    }

    public function recordConversion(Request $request, int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);

        $validated = $request->validate([
            'revenue' => 'required|numeric|min:0',
        ]);

        $send->recordConversion((float) $validated['revenue']);

        return response()->json([
            'message' => 'Conversion recorded successfully',
            'data' => EmailSendResource::make($send->fresh()),
        ]);
    }

    // =========================================================================
    // ANALYTICS
    // =========================================================================

    public function analytics(int $id): JsonResponse
    {
        $send = EmailSend::findOrFail($id);

        return response()->json([
            'data' => [
                'send_id' => $send->id,
                'campaign_id' => $send->campaign_id,
                'recipient_email' => $send->recipient_email,
                'status' => $send->status,
                'tracking' => [
                    'is_opened' => $send->is_opened,
                    'is_clicked' => $send->is_clicked,
                    'open_count' => $send->open_count,
                    'click_count' => $send->click_count,
                    'first_opened_at' => $send->first_opened_at?->toIso8601String(),
                    'last_opened_at' => $send->last_opened_at?->toIso8601String(),
                    'first_clicked_at' => $send->first_clicked_at?->toIso8601String(),
                    'last_clicked_at' => $send->last_clicked_at?->toIso8601String(),
                ],
                'timing' => [
                    'queued_at' => $send->queued_at?->toIso8601String(),
                    'sent_at' => $send->sent_at?->toIso8601String(),
                    'delivered_at' => $send->delivered_at?->toIso8601String(),
                    'time_to_first_open' => $send->getTimeToFirstOpen(),
                    'time_to_conversion' => $send->getTimeToConversion(),
                ],
                'conversion' => [
                    'has_conversion' => $send->has_conversion,
                    'conversion_revenue' => (float) $send->conversion_revenue,
                    'converted_at' => $send->converted_at?->toIso8601String(),
                ],
                'delivery' => [
                    'provider' => $send->provider,
                    'message_id' => $send->message_id,
                    'bounce_type' => $send->bounce_type,
                    'bounce_reason' => $send->bounce_reason,
                    'error_message' => $send->error_message,
                ],
            ],
        ]);
    }

    // =========================================================================
    // BULK OPERATIONS
    // =========================================================================

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:commerce_email_sends,id',
        ]);

        $count = EmailSend::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => 'Email sends deleted successfully',
            'data' => [
                'deleted_count' => $count,
            ],
        ]);
    }
}
