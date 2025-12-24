<?php

namespace Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use Subscriptions\Models\Plan;
use Illuminate\Http\Request;

/**
 * Plan Controller
 */
class PlanController extends Controller
{
    /**
     * Display a listing of plans.
     */
    public function index()
    {
        $plans = Plan::withCount('activeSubscriptions')
            ->ordered()
            ->paginate(25);

        return view('subscriptions::plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create()
    {
        $intervals = config('subscriptions.intervals', []);

        return view('subscriptions::plans.create', compact('intervals'));
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'interval' => 'required|string|in:monthly,quarterly,semi_annual,yearly,lifetime',
            'interval_count' => 'required|integer|min:1',
            'trial_days' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
        ]);

        $plan = Plan::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully.',
            'data' => $plan,
            'redirect' => route('admin.plugins.subscriptions.plans.index'),
        ]);
    }

    /**
     * Display the specified plan.
     */
    public function show(Plan $plan)
    {
        $plan->loadCount('activeSubscriptions');
        $subscriptions = $plan->subscriptions()->with('user')->latest()->paginate(10);

        return view('subscriptions::plans.show', compact('plan', 'subscriptions'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan)
    {
        $intervals = config('subscriptions.intervals', []);

        return view('subscriptions::plans.edit', compact('plan', 'intervals'));
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'interval' => 'required|string|in:monthly,quarterly,semi_annual,yearly,lifetime',
            'interval_count' => 'required|integer|min:1',
            'trial_days' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
        ]);

        $plan->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully.',
            'data' => $plan,
            'redirect' => route('admin.plugins.subscriptions.plans.index'),
        ]);
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(Plan $plan)
    {
        // Check if plan has active subscriptions
        if ($plan->activeSubscriptions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete plan with active subscriptions.',
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully.',
        ]);
    }

    /**
     * Toggle plan status.
     */
    public function toggleStatus(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Plan {$status} successfully.",
            'data' => $plan,
        ]);
    }
}

