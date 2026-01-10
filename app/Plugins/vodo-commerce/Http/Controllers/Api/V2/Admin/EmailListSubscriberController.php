<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Resources\EmailListSubscriberResource;
use VodoCommerce\Models\EmailListSubscriber;

class EmailListSubscriberController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EmailListSubscriber::query();

        // Filter by list
        if ($request->has('list_id')) {
            $query->forList((int) $request->get('list_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->get('status'));
        }

        // Filter subscribed only
        if ($request->boolean('subscribed_only')) {
            $query->subscribed();
        }

        // Filter pending confirmation
        if ($request->boolean('pending_only')) {
            $query->pending();
        }

        // Filter by source
        if ($request->has('source')) {
            $query->fromSource($request->get('source'));
        }

        // Filter engaged subscribers
        if ($request->boolean('engaged_only')) {
            $query->engaged();
        }

        // Filter inactive subscribers
        if ($request->boolean('inactive_only')) {
            $query->inactive();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
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

        return EmailListSubscriberResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function show(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::with(['list', 'customer'])->findOrFail($id);

        return response()->json([
            'data' => EmailListSubscriberResource::make($subscriber),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'preferences' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        $subscriber->update($validated);

        return response()->json([
            'message' => 'Subscriber updated successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);
        $subscriber->delete();

        return response()->json([
            'message' => 'Subscriber deleted successfully',
        ]);
    }

    // =========================================================================
    // SUBSCRIBER ACTIONS
    // =========================================================================

    public function confirm(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        $success = $subscriber->confirm();

        if (!$success) {
            return response()->json([
                'message' => 'Subscriber is not in pending status',
            ], 422);
        }

        return response()->json([
            'message' => 'Subscriber confirmed successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function unsubscribe(Request $request, int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string',
            'ip' => 'nullable|ip',
        ]);

        $success = $subscriber->unsubscribe(
            $validated['reason'] ?? null,
            $validated['ip'] ?? $request->ip()
        );

        if (!$success) {
            return response()->json([
                'message' => 'Subscriber is not in subscribed status',
            ], 422);
        }

        return response()->json([
            'message' => 'Subscriber unsubscribed successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function resubscribe(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        $subscriber->update([
            'status' => 'subscribed',
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
            'unsubscribe_reason' => null,
        ]);

        return response()->json([
            'message' => 'Subscriber resubscribed successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function markAsBounced(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        $subscriber->update(['status' => 'bounced']);

        return response()->json([
            'message' => 'Subscriber marked as bounced successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function markAsComplained(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        $subscriber->update(['status' => 'complained']);

        return response()->json([
            'message' => 'Subscriber marked as complained successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function clean(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        $subscriber->update(['status' => 'cleaned']);

        return response()->json([
            'message' => 'Subscriber cleaned successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function updatePreference(Request $request, int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required',
        ]);

        $subscriber->updatePreference($validated['key'], $validated['value']);

        return response()->json([
            'message' => 'Preference updated successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function recordEmailOpened(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);
        $subscriber->recordEmailOpened();

        return response()->json([
            'message' => 'Email open recorded successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    public function recordEmailClicked(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);
        $subscriber->recordEmailClicked();

        return response()->json([
            'message' => 'Email click recorded successfully',
            'data' => EmailListSubscriberResource::make($subscriber->fresh()),
        ]);
    }

    // =========================================================================
    // ANALYTICS
    // =========================================================================

    public function analytics(int $id): JsonResponse
    {
        $subscriber = EmailListSubscriber::findOrFail($id);

        return response()->json([
            'data' => [
                'subscriber_id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status,
                'engagement' => [
                    'emails_sent' => $subscriber->emails_sent,
                    'emails_opened' => $subscriber->emails_opened,
                    'emails_clicked' => $subscriber->emails_clicked,
                    'emails_bounced' => $subscriber->emails_bounced,
                    'open_rate' => (float) $subscriber->open_rate,
                    'click_rate' => (float) $subscriber->click_rate,
                    'engagement_score' => $subscriber->getEngagementScore(),
                ],
                'activity' => [
                    'last_opened_at' => $subscriber->last_opened_at?->toIso8601String(),
                    'last_clicked_at' => $subscriber->last_clicked_at?->toIso8601String(),
                    'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
                ],
                'flags' => [
                    'is_subscribed' => $subscriber->isSubscribed(),
                    'is_engaged' => $subscriber->isEngaged(),
                    'is_inactive' => $subscriber->isInactive(),
                ],
            ],
        ]);
    }
}
