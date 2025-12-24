<?php

namespace Subscriptions\Services;

use Subscriptions\Models\Plan;
use Subscriptions\Models\Subscription;
use Subscriptions\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Subscription Service
 */
class SubscriptionService
{
    /**
     * Get all active plans.
     */
    public function getActivePlans()
    {
        return Cache::remember('subscriptions.plans', 300, function () {
            return Plan::active()->ordered()->get();
        });
    }

    /**
     * Create a subscription for a user.
     */
    public function createSubscription(User $user, Plan $plan, array $options = []): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $options) {
            // Cancel any existing active subscription
            $existingSubscription = $user->subscriptions()->active()->first();
            if ($existingSubscription) {
                $existingSubscription->cancel('Upgraded to new plan');
            }

            // Calculate dates
            $trialDays = $options['trial_days'] ?? $plan->trial_days ?? config('subscriptions.trial_days', 0);
            $intervals = config('subscriptions.intervals', []);
            $intervalDays = $intervals[$plan->interval]['days'] ?? 30;

            $startsAt = now();
            $trialEndsAt = $trialDays > 0 ? now()->addDays($trialDays) : null;
            $endsAt = $intervalDays ? now()->addDays($intervalDays * $plan->interval_count) : null;

            if ($trialEndsAt) {
                $endsAt = $trialEndsAt->copy()->addDays($intervalDays * $plan->interval_count);
            }

            // Create subscription
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => $trialDays > 0 ? 'trialing' : 'active',
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'meta' => $options['meta'] ?? null,
            ]);

            // Create initial invoice if not trialing
            if (!$trialDays || ($options['charge_immediately'] ?? false)) {
                $this->createInvoice($subscription);
            }

            // Clear cache
            $this->clearCache();

            return $subscription;
        });
    }

    /**
     * Create an invoice for a subscription.
     */
    public function createInvoice(Subscription $subscription, array $options = []): Invoice
    {
        $plan = $subscription->plan;

        return Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'status' => $options['status'] ?? 'pending',
            'subtotal' => $subscription->price,
            'tax' => $options['tax'] ?? 0,
            'discount' => $options['discount'] ?? 0,
            'total' => ($subscription->price + ($options['tax'] ?? 0)) - ($options['discount'] ?? 0),
            'currency' => $subscription->currency,
            'description' => $options['description'] ?? "Subscription to {$plan->name}",
            'due_date' => $options['due_date'] ?? now()->addDays(7),
        ]);
    }

    /**
     * Renew a subscription.
     */
    public function renewSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->renew();

            // Create renewal invoice
            $this->createInvoice($subscription, [
                'description' => "Renewal: {$subscription->plan->name}",
            ]);

            $this->clearCache();

            return $subscription;
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Subscription $subscription, ?string $reason = null): Subscription
    {
        return DB::transaction(function () use ($subscription, $reason) {
            $subscription->cancel($reason);
            $this->clearCache();
            return $subscription;
        });
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Subscription $subscription, Plan $newPlan, bool $prorate = true): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPlan, $prorate) {
            $oldPlan = $subscription->plan;
            $subscription->changePlan($newPlan);

            // Handle proration if upgrading
            if ($prorate && $newPlan->price > $oldPlan->price) {
                $proratedAmount = $this->calculateProration($subscription, $oldPlan, $newPlan);
                if ($proratedAmount > 0) {
                    $this->createInvoice($subscription, [
                        'subtotal' => $proratedAmount,
                        'total' => $proratedAmount,
                        'description' => "Plan upgrade: {$oldPlan->name} to {$newPlan->name}",
                    ]);
                }
            }

            $this->clearCache();

            return $subscription;
        });
    }

    /**
     * Calculate prorated amount for plan change.
     */
    protected function calculateProration(Subscription $subscription, Plan $oldPlan, Plan $newPlan): float
    {
        if (!$subscription->ends_at) {
            return $newPlan->price - $oldPlan->price;
        }

        $daysRemaining = max(0, now()->diffInDays($subscription->ends_at));
        $intervals = config('subscriptions.intervals', []);
        $totalDays = $intervals[$oldPlan->interval]['days'] ?? 30;

        $unusedOld = ($oldPlan->price / $totalDays) * $daysRemaining;
        $newCost = ($newPlan->price / $totalDays) * $daysRemaining;

        return max(0, $newCost - $unusedOld);
    }

    /**
     * Get subscription statistics.
     */
    public function getStatistics(): array
    {
        return Cache::remember('subscriptions.statistics', 300, function () {
            $totalSubscriptions = Subscription::count();
            $activeSubscriptions = Subscription::active()->count();
            $trialingSubscriptions = Subscription::trialing()->count();
            $cancelledSubscriptions = Subscription::cancelled()->count();

            $mrr = Subscription::active()
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->where('plans.interval', 'monthly')
                ->sum('subscriptions.price');

            $arr = $mrr * 12;

            return [
                'total' => $totalSubscriptions,
                'active' => $activeSubscriptions,
                'trialing' => $trialingSubscriptions,
                'cancelled' => $cancelledSubscriptions,
                'mrr' => $mrr,
                'arr' => $arr,
                'today' => Subscription::whereDate('created_at', today())->count(),
                'this_month' => Subscription::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];
        });
    }

    /**
     * Get paginated subscriptions with filters.
     */
    public function getSubscriptions(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Subscription::with(['user', 'plan']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get user's active subscription.
     */
    public function getUserSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->with('plan')
            ->whereIn('status', ['active', 'trialing'])
            ->first();
    }

    /**
     * Clear subscription-related cache.
     */
    protected function clearCache(): void
    {
        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.statistics');
    }
}

