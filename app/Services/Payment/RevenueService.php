<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PayoutStatus;
use App\Models\Billing\DeveloperPaymentAccount;
use App\Models\Billing\DeveloperPayout;
use App\Models\Billing\PaymentTransaction;
use App\Models\Billing\RevenueSplit;
use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Revenue Service
 *
 * Manages revenue splitting, commission tracking, and developer payouts.
 */
class RevenueService
{
    protected float $defaultPlatformFee = 0.20; // 20% platform commission

    public function __construct(
        protected PaymentManager $paymentManager,
    ) {}

    /**
     * Record revenue from a transaction.
     */
    public function recordRevenue(
        PaymentTransaction $transaction,
        MarketplaceListing $listing,
        float $amount
    ): RevenueSplit {
        $developerAccount = DeveloperPaymentAccount::where('developer_id', $listing->developer_id)->first();
        $commissionRate = $developerAccount?->commission_rate ?? $this->defaultPlatformFee;

        $platformFee = $amount * $commissionRate;
        $gatewayFee = $transaction->fee ?? 0;
        $taxAmount = 0; // Would calculate based on jurisdiction
        $developerAmount = $amount - $platformFee - $gatewayFee - $taxAmount;

        return RevenueSplit::create([
            'transaction_id' => $transaction->id,
            'listing_id' => $listing->id,
            'developer_id' => $listing->developer_id,
            'currency' => $transaction->currency,
            'gross_amount' => $amount,
            'platform_fee' => $platformFee,
            'platform_fee_rate' => $commissionRate,
            'gateway_fee' => $gatewayFee,
            'tax_amount' => $taxAmount,
            'developer_amount' => $developerAmount,
            'status' => 'pending',
        ]);
    }

    /**
     * Get developer earnings summary.
     */
    public function getDeveloperEarnings(int $developerId): array
    {
        $splits = RevenueSplit::where('developer_id', $developerId);

        $pending = (clone $splits)->where('status', 'pending')->sum('developer_amount');
        $available = (clone $splits)->where('status', 'available')->sum('developer_amount');
        $paid = (clone $splits)->where('status', 'paid')->sum('developer_amount');
        $total = $splits->sum('developer_amount');

        return [
            'pending' => round($pending, 2),
            'available' => round($available, 2),
            'paid' => round($paid, 2),
            'total' => round($total, 2),
            'currency' => 'SAR',
        ];
    }

    /**
     * Get earnings breakdown by listing.
     */
    public function getEarningsByListing(int $developerId): Collection
    {
        return RevenueSplit::where('developer_id', $developerId)
            ->selectRaw('listing_id, SUM(gross_amount) as gross, SUM(developer_amount) as net, COUNT(*) as transactions')
            ->groupBy('listing_id')
            ->with('listing:id,name,plugin_slug')
            ->get();
    }

    /**
     * Mark pending revenue as available for payout.
     */
    public function markAsAvailable(int $developerId): int
    {
        // Revenue becomes available after a holding period (e.g., 7 days)
        // This protects against chargebacks

        $holdingDays = config('billing.payout_holding_days', 7);

        return RevenueSplit::where('developer_id', $developerId)
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subDays($holdingDays))
            ->update(['status' => 'available']);
    }

    /**
     * Create a payout for a developer.
     */
    public function createPayout(int $developerId, ?float $amount = null): DeveloperPayout
    {
        $account = DeveloperPaymentAccount::where('developer_id', $developerId)
            ->where('account_status', 'verified')
            ->firstOrFail();

        // Get available revenue
        $availableSplits = RevenueSplit::where('developer_id', $developerId)
            ->where('status', 'available')
            ->whereNull('payout_id')
            ->get();

        if ($availableSplits->isEmpty()) {
            throw new \InvalidArgumentException('No available funds for payout');
        }

        $availableAmount = $availableSplits->sum('developer_amount');

        if ($amount === null) {
            $amount = $availableAmount;
        }

        if ($amount > $availableAmount) {
            throw new \InvalidArgumentException("Requested amount exceeds available balance");
        }

        if ($amount < $account->minimum_payout) {
            throw new \InvalidArgumentException("Amount below minimum payout threshold of {$account->minimum_payout}");
        }

        return DB::transaction(function () use ($developerId, $account, $availableSplits, $amount) {
            $payoutFee = $this->calculatePayoutFee($amount, $account->gateway);
            $netAmount = $amount - $payoutFee;

            $payout = DeveloperPayout::create([
                'uuid' => Str::uuid(),
                'payout_number' => $this->generatePayoutNumber(),
                'developer_id' => $developerId,
                'payment_account_id' => $account->id,
                'gateway' => $account->gateway,
                'status' => PayoutStatus::Pending,
                'currency' => $account->currency,
                'gross_amount' => $amount,
                'fees' => $payoutFee,
                'net_amount' => $netAmount,
                'items_count' => $availableSplits->count(),
                'period_start' => $availableSplits->min('created_at'),
                'period_end' => $availableSplits->max('created_at'),
            ]);

            // Link revenue splits to this payout
            RevenueSplit::whereIn('id', $availableSplits->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            Log::info('Payout created', [
                'payout_id' => $payout->id,
                'developer_id' => $developerId,
                'amount' => $amount,
            ]);

            return $payout;
        });
    }

    /**
     * Process a payout through the payment gateway.
     */
    public function processPayout(DeveloperPayout $payout): bool
    {
        $account = $payout->paymentAccount;

        try {
            $gateway = $this->paymentManager->payoutGateway($account->gateway);

            $payout->update([
                'status' => PayoutStatus::Processing,
                'initiated_at' => now(),
            ]);

            $result = $gateway->createPayout(
                $account->gateway_account_id,
                (int) ($payout->net_amount * 100),
                $payout->currency,
                [
                    'payout_id' => $payout->id,
                    'payout_number' => $payout->payout_number,
                ]
            );

            if ($result->success) {
                $payout->update([
                    'gateway_payout_id' => $result->payoutId,
                    'status' => PayoutStatus::Completed,
                    'completed_at' => now(),
                ]);

                // Mark all linked revenue splits as paid
                RevenueSplit::where('payout_id', $payout->id)
                    ->update(['status' => 'paid']);

                Log::info('Payout processed', [
                    'payout_id' => $payout->id,
                    'gateway_payout_id' => $result->payoutId,
                ]);

                return true;
            }

            $payout->update([
                'status' => PayoutStatus::Failed,
                'failure_reason' => $result->failureMessage,
            ]);

            Log::error('Payout failed', [
                'payout_id' => $payout->id,
                'error' => $result->failureMessage,
            ]);

            return false;
        } catch (\Throwable $e) {
            $payout->update([
                'status' => PayoutStatus::Failed,
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('Payout exception', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process all pending payouts.
     */
    public function processAllPendingPayouts(): array
    {
        $results = ['success' => 0, 'failed' => 0];

        $payouts = DeveloperPayout::where('status', PayoutStatus::Pending)->get();

        foreach ($payouts as $payout) {
            if ($this->processPayout($payout)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Get platform revenue statistics.
     */
    public function getPlatformStats(string $period = 'month'): array
    {
        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $splits = RevenueSplit::where('created_at', '>=', $startDate);

        return [
            'gross_revenue' => round($splits->sum('gross_amount'), 2),
            'platform_fees' => round($splits->sum('platform_fee'), 2),
            'developer_payouts' => round($splits->sum('developer_amount'), 2),
            'gateway_fees' => round($splits->sum('gateway_fee'), 2),
            'transaction_count' => $splits->count(),
            'period' => $period,
            'start_date' => $startDate->toDateString(),
        ];
    }

    /**
     * Get top earning developers.
     */
    public function getTopDevelopers(int $limit = 10): Collection
    {
        return RevenueSplit::selectRaw('developer_id, SUM(gross_amount) as total_revenue, SUM(developer_amount) as total_earnings, COUNT(*) as sales')
            ->groupBy('developer_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate payout fee based on gateway.
     */
    protected function calculatePayoutFee(float $amount, string $gateway): float
    {
        return match ($gateway) {
            'stripe' => 0.25 + ($amount * 0.0025), // $0.25 + 0.25%
            'moyasar' => $amount * 0.01, // 1%
            'bank_transfer' => 10.00, // Flat fee
            default => 0,
        };
    }

    /**
     * Generate a unique payout number.
     */
    protected function generatePayoutNumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$date}-{$random}";
    }
}
