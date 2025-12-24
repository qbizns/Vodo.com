<?php

namespace Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Subscriptions\Models\Plan;
use Subscriptions\Models\Subscription;
use Subscriptions\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Subscription Controller
 */
class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'plan_id']);
        $subscriptions = $this->subscriptionService->getSubscriptions($filters);
        $plans = Plan::active()->ordered()->get();
        $statuses = config('subscriptions.statuses', []);

        return view('subscriptions::subscriptions.index', compact('subscriptions', 'plans', 'statuses', 'filters'));
    }

    /**
     * Show the form for creating a new subscription.
     */
    public function create()
    {
        $plans = Plan::active()->ordered()->get();
        $users = User::orderBy('name')->get();

        return view('subscriptions::subscriptions.create', compact('plans', 'users'));
    }

    /**
     * Store a newly created subscription.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'trial_days' => 'nullable|integer|min:0',
        ]);

        $user = User::findOrFail($request->user_id);
        $plan = Plan::findOrFail($request->plan_id);

        $subscription = $this->subscriptionService->createSubscription($user, $plan, [
            'trial_days' => $request->trial_days,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully.',
            'data' => $subscription,
            'redirect' => route('admin.plugins.subscriptions.subscriptions.index'),
        ]);
    }

    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'plan', 'invoices']);

        return view('subscriptions::subscriptions.show', compact('subscription'));
    }

    /**
     * Show the form for editing the specified subscription.
     */
    public function edit(Subscription $subscription)
    {
        $subscription->load(['user', 'plan']);
        $plans = Plan::active()->ordered()->get();

        return view('subscriptions::subscriptions.edit', compact('subscription', 'plans'));
    }

    /**
     * Update the specified subscription.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', array_keys(config('subscriptions.statuses', []))),
            'ends_at' => 'nullable|date',
        ]);

        $subscription->update($request->only(['status', 'ends_at']));

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated successfully.',
            'data' => $subscription,
        ]);
    }

    /**
     * Cancel the specified subscription.
     */
    public function cancel(Request $request, Subscription $subscription)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->subscriptionService->cancelSubscription($subscription, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully.',
            'data' => $subscription->fresh(),
        ]);
    }

    /**
     * Renew the specified subscription.
     */
    public function renew(Subscription $subscription)
    {
        $this->subscriptionService->renewSubscription($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription renewed successfully.',
            'data' => $subscription->fresh(),
        ]);
    }

    /**
     * Change the subscription plan.
     */
    public function changePlan(Request $request, Subscription $subscription)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'prorate' => 'nullable|boolean',
        ]);

        $newPlan = Plan::findOrFail($request->plan_id);
        $prorate = $request->input('prorate', config('subscriptions.prorate_plan_changes', true));

        $this->subscriptionService->changePlan($subscription, $newPlan, $prorate);

        return response()->json([
            'success' => true,
            'message' => 'Plan changed successfully.',
            'data' => $subscription->fresh(['plan']),
        ]);
    }
}

