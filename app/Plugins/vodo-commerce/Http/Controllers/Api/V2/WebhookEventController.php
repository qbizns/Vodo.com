<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\WebhookEvent;
use VodoCommerce\Services\WebhookService;

class WebhookEventController extends Controller
{
    protected Store $store;
    protected WebhookService $webhookService;

    public function __construct()
    {
        $this->store = resolve_store();
        $this->webhookService = new WebhookService($this->store);
    }

    /**
     * List all webhook events.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WebhookEvent::where('store_id', $this->store->id)
            ->with('subscription');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->has('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }

        $perPage = $request->input('per_page', 20);
        $events = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    /**
     * Get webhook event details.
     */
    public function show(string $eventId): JsonResponse
    {
        $event = WebhookEvent::where('store_id', $this->store->id)
            ->where('event_id', $eventId)
            ->with(['subscription', 'deliveries', 'logs'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    /**
     * Retry a failed webhook event.
     */
    public function retry(string $eventId): JsonResponse
    {
        $event = WebhookEvent::where('store_id', $this->store->id)
            ->where('event_id', $eventId)
            ->firstOrFail();

        if (!$event->canRetry()) {
            return response()->json([
                'success' => false,
                'message' => 'Event cannot be retried (max retries reached)',
            ], 422);
        }

        $this->webhookService->retryEvent($event);

        return response()->json([
            'success' => true,
            'message' => 'Event retry initiated',
            'data' => $event->fresh(),
        ]);
    }

    /**
     * Cancel a webhook event.
     */
    public function cancel(string $eventId): JsonResponse
    {
        $event = WebhookEvent::where('store_id', $this->store->id)
            ->where('event_id', $eventId)
            ->firstOrFail();

        if ($event->isDelivered()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel an already delivered event',
            ], 422);
        }

        $this->webhookService->cancelEvent($event, request()->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Event cancelled successfully',
        ]);
    }

    /**
     * Get events ready for retry.
     */
    public function pendingRetries(): JsonResponse
    {
        $events = $this->webhookService->getPendingEvents();

        return response()->json([
            'success' => true,
            'data' => $events,
            'total' => $events->count(),
        ]);
    }
}
