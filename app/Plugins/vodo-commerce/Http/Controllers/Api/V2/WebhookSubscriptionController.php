<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\WebhookSubscription;
use VodoCommerce\Services\WebhookService;

class WebhookSubscriptionController extends Controller
{
    protected Store $store;
    protected WebhookService $webhookService;

    public function __construct()
    {
        $this->store = resolve_store();
        $this->webhookService = new WebhookService($this->store);
    }

    /**
     * List all webhook subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WebhookSubscription::where('store_id', $this->store->id);

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('event_type')) {
            $query->subscribedToEvent($request->event_type);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Get webhook subscription details.
     */
    public function show(int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('store_id', $this->store->id)
            ->with(['events' => function ($query) {
                $query->recent()->limit(10);
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }

    /**
     * Create a new webhook subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string'],
            'secret' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'timeout_seconds' => ['integer', 'min:5', 'max:120'],
            'max_retry_attempts' => ['integer', 'min:0', 'max:10'],
            'retry_delay_seconds' => ['integer', 'min:30', 'max:3600'],
            'custom_headers' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subscription = $this->webhookService->createSubscription($request->all());

        return response()->json([
            'success' => true,
            'data' => $subscription,
            'message' => 'Webhook subscription created successfully',
        ], 201);
    }

    /**
     * Update webhook subscription.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('store_id', $this->store->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['string'],
            'is_active' => ['boolean'],
            'timeout_seconds' => ['integer', 'min:5', 'max:120'],
            'max_retry_attempts' => ['integer', 'min:0', 'max:10'],
            'retry_delay_seconds' => ['integer', 'min:30', 'max:3600'],
            'custom_headers' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->webhookService->updateSubscription($subscription, $request->all());

        return response()->json([
            'success' => true,
            'data' => $updated,
            'message' => 'Webhook subscription updated successfully',
        ]);
    }

    /**
     * Delete webhook subscription.
     */
    public function destroy(int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('store_id', $this->store->id)
            ->findOrFail($id);

        $this->webhookService->deleteSubscription($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Webhook subscription deleted successfully',
        ]);
    }

    /**
     * Test webhook subscription.
     */
    public function test(int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('store_id', $this->store->id)
            ->findOrFail($id);

        $event = $this->webhookService->testWebhook($subscription);

        return response()->json([
            'success' => true,
            'data' => $event,
            'message' => 'Test webhook event created and will be delivered shortly',
        ]);
    }

    /**
     * Regenerate webhook secret.
     */
    public function regenerateSecret(int $id): JsonResponse
    {
        $subscription = WebhookSubscription::where('store_id', $this->store->id)
            ->findOrFail($id);

        $newSecret = $subscription->regenerateSecret();

        return response()->json([
            'success' => true,
            'data' => [
                'subscription_id' => $subscription->id,
                'secret' => $newSecret,
            ],
            'message' => 'Webhook secret regenerated successfully',
        ]);
    }

    /**
     * Get webhook statistics.
     */
    public function statistics(): JsonResponse
    {
        $period = request()->input('period', 'last_7_days');
        $stats = $this->webhookService->getStatistics($period);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
