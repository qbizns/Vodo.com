<?php

namespace Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use Subscriptions\Services\SubscriptionService;
use Subscriptions\Models\Subscription;
use Subscriptions\Models\Plan;

/**
 * Subscriptions Dashboard Controller
 */
class DashboardController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display the subscriptions dashboard.
     */
    public function index()
    {
        $statistics = $this->subscriptionService->getStatistics();
        $plans = Plan::active()->withCount('activeSubscriptions')->ordered()->get();
        $recentSubscriptions = Subscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('subscriptions::dashboard', compact('statistics', 'plans', 'recentSubscriptions'));
    }
}

