<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\SubscriptionPlanResource;
use VodoCommerce\Models\SubscriptionPlan;
use VodoCommerce\Models\Store;
use VodoCommerce\Events\CommerceEvents;

class SubscriptionPlanController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all subscription plans.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = SubscriptionPlan::where('store_id', $store->id);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by public visibility
        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        // Filter by metered billing
        if ($request->has('is_metered')) {
            $query->where('is_metered', $request->boolean('is_metered'));
        }

        // Filter by billing interval
        if ($request->filled('billing_interval')) {
            $query->where('billing_interval', $request->input('billing_interval'));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $plans = $query->paginate($perPage);

        return $this->successResponse(
            SubscriptionPlanResource::collection($plans),
            [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
            ]
        );
    }

    /**
     * Get a single subscription plan.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = SubscriptionPlan::where('store_id', $store->id);

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $plan = $query->findOrFail($id);

        return $this->successResponse(
            new SubscriptionPlanResource($plan)
        );
    }

    /**
     * Create a new subscription plan.
     */
    public function store(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'billing_interval' => 'required|in:daily,weekly,monthly,yearly',
            'billing_interval_count' => 'required|integer|min:1',
            'trial_days' => 'nullable|integer|min:0',
            'is_metered' => 'nullable|boolean',
            'metered_units' => 'nullable|string|max:100',
            'price_per_unit' => 'nullable|numeric|min:0',
            'allow_proration' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $data['store_id'] = $store->id;

        $plan = SubscriptionPlan::create($data);

        // Fire event for plugin extensibility
        do_action(CommerceEvents::SUBSCRIPTION_PLAN_CREATED, $plan);

        return $this->successResponse(
            new SubscriptionPlanResource($plan),
            null,
            'Subscription plan created successfully',
            201
        );
    }

    /**
     * Update a subscription plan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $plan = SubscriptionPlan::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'billing_interval' => 'sometimes|required|in:daily,weekly,monthly,yearly',
            'billing_interval_count' => 'sometimes|required|integer|min:1',
            'trial_days' => 'nullable|integer|min:0',
            'is_metered' => 'nullable|boolean',
            'metered_units' => 'nullable|string|max:100',
            'price_per_unit' => 'nullable|numeric|min:0',
            'allow_proration' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $plan->update($data);

        // Fire event for plugin extensibility
        do_action(CommerceEvents::SUBSCRIPTION_PLAN_UPDATED, $plan);

        return $this->successResponse(
            new SubscriptionPlanResource($plan),
            null,
            'Subscription plan updated successfully'
        );
    }

    /**
     * Delete a subscription plan.
     */
    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $plan = SubscriptionPlan::where('store_id', $store->id)->findOrFail($id);

        // Check if plan has active subscriptions
        $activeCount = $plan->subscriptions()->active()->count();

        if ($activeCount > 0) {
            return $this->errorResponse(
                'Cannot delete plan with active subscriptions',
                400
            );
        }

        $plan->delete();

        // Fire event for plugin extensibility
        do_action(CommerceEvents::SUBSCRIPTION_PLAN_DELETED, $plan);

        return $this->successResponse(
            null,
            null,
            'Subscription plan deleted successfully'
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
