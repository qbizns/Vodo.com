<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\Customer;
use VodoCommerce\Models\LoyaltyPoint;
use VodoCommerce\Models\LoyaltyPointTransaction;

class LoyaltyPointService
{
    public function earnPoints(Customer $customer, int $points, ?string $description = null, ?int $orderId = null): LoyaltyPointTransaction
    {
        $loyaltyPoint = $customer->getLoyaltyPointsOrCreate();

        $transaction = $loyaltyPoint->earn($points, $description, $orderId);

        do_action('commerce.loyalty.points_earned', $loyaltyPoint, $transaction);

        return $transaction;
    }

    public function spendPoints(Customer $customer, int $points, ?string $description = null, ?int $orderId = null): LoyaltyPointTransaction
    {
        $loyaltyPoint = $customer->getLoyaltyPointsOrCreate();

        $transaction = $loyaltyPoint->spend($points, $description, $orderId);

        do_action('commerce.loyalty.points_spent', $loyaltyPoint, $transaction);

        return $transaction;
    }

    public function adjustPoints(Customer $customer, int $points, string $description): LoyaltyPointTransaction
    {
        $loyaltyPoint = $customer->getLoyaltyPointsOrCreate();

        $transaction = $loyaltyPoint->adjust($points, $description);

        do_action('commerce.loyalty.points_adjusted', $loyaltyPoint, $transaction);

        return $transaction;
    }

    public function calculatePointsForOrder(float $orderTotal, float $pointsPerCurrency = 1.0): int
    {
        return (int) floor($orderTotal * $pointsPerCurrency);
    }

    public function getBalance(Customer $customer): int
    {
        $loyaltyPoint = $customer->loyaltyPoints;

        return $loyaltyPoint ? $loyaltyPoint->balance : 0;
    }

    public function expirePoints(LoyaltyPoint $loyaltyPoint): void
    {
        if ($loyaltyPoint->balance > 0) {
            $pointsToExpire = $loyaltyPoint->balance;

            $loyaltyPoint->balance = 0;
            $loyaltyPoint->save();

            $loyaltyPoint->transactions()->create([
                'type' => 'expired',
                'points' => -$pointsToExpire,
                'balance_after' => 0,
                'description' => 'Points expired',
            ]);

            do_action('commerce.loyalty.points_expired', $loyaltyPoint);
        }
    }
}
