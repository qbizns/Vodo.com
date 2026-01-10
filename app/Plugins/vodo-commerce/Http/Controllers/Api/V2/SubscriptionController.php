<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\SubscriptionResource;
use VodoCommerce\Http\Resources\SubscriptionInvoiceResource;
use VodoCommerce\Http\Resources\SubscriptionUsageResource;
use VodoCommerce\Models\Subscription;
use VodoCommerce\Models\SubscriptionPlan;
use VodoCommerce\Models\SubscriptionItem;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\SubscriptionService;
use VodoCommerce\Services\BillingService;

class SubscriptionController
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected BillingService $billingService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = Subscription::where('store_id', $store->id);

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Filter by plan
        if ($request->filled('plan_id')) {
            $query->where('subscription_plan_id', $request->input('plan_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter active subscriptions
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filter trial subscriptions
        if ($request->boolean('trial_only')) {
            $query->trial();
        }

        // Filter subscriptions due for billing
        if ($request->boolean('due_for_billing')) {
            $query->dueForBilling();
        }

        // Search by subscription number or customer email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('subscription_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('email', 'like', '%' . $search . '%');
                    });
            });
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $subscriptions = $query->paginate($perPage);

        return $this->successResponse(
            SubscriptionResource::collection($subscriptions),
            [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ]
        );
    }

    /**
     * Get a single subscription.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = Subscription::where('store_id', $store->id);

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $subscription = $query->findOrFail($id);

        return $this->successResponse(
            new SubscriptionResource($subscription)
        );
    }

    /**
     * Create a new subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $data = $request->validate([
            'customer_id' => 'required|exists:commerce_customers,id',
            'plan_id' => 'required|exists:commerce_subscription_plans,id',
            'payment_method_id' => 'nullable|exists:commerce_payment_methods,id',
            'start_trial' => 'nullable|boolean',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|string',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.product_id' => 'nullable|exists:commerce_products,id',
            'items.*.product_variant_id' => 'nullable|exists:commerce_product_variants,id',
            'items.*.metered_config' => 'nullable|array',
            'meta' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::where('store_id', $store->id)
            ->findOrFail($data['customer_id']);

        $plan = SubscriptionPlan::where('store_id', $store->id)
            ->findOrFail($data['plan_id']);

        $paymentMethod = null;
        if (isset($data['payment_method_id'])) {
            $paymentMethod = PaymentMethod::where('customer_id', $customer->id)
                ->findOrFail($data['payment_method_id']);
        }

        $startTrial = $data['start_trial'] ?? ($plan->trial_days > 0);
        $items = $data['items'] ?? [];

        $subscription = $this->subscriptionService->createSubscription(
            $store,
            $customer,
            $plan,
            $paymentMethod,
            $items,
            $startTrial
        );

        // Apply additional metadata if provided
        if (isset($data['meta'])) {
            $subscription->update(['meta' => $data['meta']]);
        }

        if (isset($data['notes'])) {
            $subscription->update(['notes' => $data['notes']]);
        }

        return $this->successResponse(
            new SubscriptionResource($subscription->load(['plan', 'customer', 'items'])),
            null,
            'Subscription created successfully',
            201
        );
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'new_plan_id' => 'required|exists:commerce_subscription_plans,id',
            'prorate' => 'nullable|boolean',
        ]);

        $newPlan = SubscriptionPlan::where('store_id', $store->id)
            ->findOrFail($data['new_plan_id']);

        $prorate = $data['prorate'] ?? true;

        $subscription = $this->subscriptionService->changePlan(
            $subscription,
            $newPlan,
            $prorate
        );

        return $this->successResponse(
            new SubscriptionResource($subscription->load(['plan', 'items'])),
            null,
            'Subscription plan changed successfully'
        );
    }

    /**
     * Pause a subscription.
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'resume_at' => 'nullable|date|after:now',
        ]);

        $resumeAt = isset($data['resume_at']) ? Carbon::parse($data['resume_at']) : null;

        $subscription = $this->subscriptionService->pauseSubscription(
            $subscription,
            $resumeAt
        );

        return $this->successResponse(
            new SubscriptionResource($subscription),
            null,
            'Subscription paused successfully'
        );
    }

    /**
     * Resume a paused subscription.
     */
    public function resume(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($id);

        $subscription = $this->subscriptionService->resumeSubscription($subscription);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            null,
            'Subscription resumed successfully'
        );
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'immediately' => 'nullable|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $immediately = $data['immediately'] ?? false;
        $reason = $data['reason'] ?? null;

        $subscription = $this->subscriptionService->cancelSubscription(
            $subscription,
            $immediately,
            $reason,
            'api',
            auth()->id()
        );

        return $this->successResponse(
            new SubscriptionResource($subscription),
            null,
            'Subscription cancelled successfully'
        );
    }

    /**
     * Add an item to a subscription.
     */
    public function addItem(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'type' => 'required|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'product_id' => 'nullable|exists:commerce_products,id',
            'product_variant_id' => 'nullable|exists:commerce_product_variants,id',
            'metered_config' => 'nullable|array',
        ]);

        $item = $this->subscriptionService->addItem(
            $subscription,
            $data['type'],
            $data['price'],
            $data['quantity'] ?? 1,
            $data['product_id'] ?? null,
            $data['product_variant_id'] ?? null,
            $data['metered_config'] ?? []
        );

        return $this->successResponse(
            new SubscriptionResource($subscription->load('items')),
            null,
            'Item added to subscription successfully',
            201
        );
    }

    /**
     * Remove an item from a subscription.
     */
    public function removeItem(int $subscriptionId, int $itemId): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($subscriptionId);

        $item = SubscriptionItem::where('subscription_id', $subscription->id)
            ->findOrFail($itemId);

        $this->subscriptionService->removeItem($item);

        return $this->successResponse(
            new SubscriptionResource($subscription->load('items')),
            null,
            'Item removed from subscription successfully'
        );
    }

    /**
     * Record usage for a metered subscription item.
     */
    public function recordUsage(Request $request, int $subscriptionId, int $itemId): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($subscriptionId);

        $item = SubscriptionItem::where('subscription_id', $subscription->id)
            ->findOrFail($itemId);

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'metric' => 'nullable|string|max:100',
            'action' => 'nullable|string|max:100',
        ]);

        $usage = $this->subscriptionService->recordUsage(
            $item,
            $data['quantity'],
            $data['metric'] ?? null,
            $data['action'] ?? null
        );

        return $this->successResponse(
            new SubscriptionUsageResource($usage),
            null,
            'Usage recorded successfully',
            201
        );
    }

    /**
     * Get subscription invoices.
     */
    public function invoices(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($id);

        $query = $subscription->invoices();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $invoices = $query->paginate($perPage);

        return $this->successResponse(
            SubscriptionInvoiceResource::collection($invoices),
            [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ]
        );
    }

    /**
     * Get subscription usage records.
     */
    public function usage(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $subscription = Subscription::where('store_id', $store->id)->findOrFail($id);

        $query = $subscription->usageRecords();

        // Filter by item
        if ($request->filled('item_id')) {
            $query->where('subscription_item_id', $request->input('item_id'));
        }

        // Filter by billed status
        if ($request->has('is_billed')) {
            if ($request->boolean('is_billed')) {
                $query->billed();
            } else {
                $query->unbilled();
            }
        }

        // Sort
        $sortBy = $request->input('sort_by', 'recorded_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 50);
        $usage = $query->paginate($perPage);

        return $this->successResponse(
            SubscriptionUsageResource::collection($usage),
            [
                'current_page' => $usage->currentPage(),
                'last_page' => $usage->lastPage(),
                'per_page' => $usage->perPage(),
                'total' => $usage->total(),
            ]
        );
    }

    protected function successResponse(mixed $data = null, ?array $pagination = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'status' => $status,
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
