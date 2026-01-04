<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\Customer;
use VodoCommerce\Models\CustomerWallet;
use VodoCommerce\Models\CustomerWalletTransaction;

class CustomerWalletService
{
    public function deposit(Customer $customer, float $amount, ?string $description = null, ?string $reference = null): CustomerWalletTransaction
    {
        $wallet = $customer->getWalletOrCreate();

        $transaction = $wallet->deposit($amount, $description, $reference);

        do_action('commerce.wallet.deposit', $wallet, $transaction);

        return $transaction;
    }

    public function withdraw(Customer $customer, float $amount, ?string $description = null, ?string $reference = null): CustomerWalletTransaction
    {
        $wallet = $customer->getWalletOrCreate();

        $transaction = $wallet->withdraw($amount, $description, $reference);

        do_action('commerce.wallet.withdraw', $wallet, $transaction);

        return $transaction;
    }

    public function refund(Customer $customer, float $amount, ?int $orderId = null, ?string $description = null): CustomerWalletTransaction
    {
        $wallet = $customer->getWalletOrCreate();

        $wallet->balance += $amount;
        $wallet->save();

        $transaction = $wallet->transactions()->create([
            'type' => 'refund',
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'description' => $description ?? 'Refund from order',
            'order_id' => $orderId,
        ]);

        do_action('commerce.wallet.refund', $wallet, $transaction);

        return $transaction;
    }

    public function deductForPurchase(Customer $customer, float $amount, int $orderId): CustomerWalletTransaction
    {
        $wallet = $customer->getWalletOrCreate();

        if (!$wallet->hasBalance($amount)) {
            throw new \Exception('Insufficient wallet balance');
        }

        $wallet->balance -= $amount;
        $wallet->save();

        $transaction = $wallet->transactions()->create([
            'type' => 'purchase',
            'amount' => -$amount,
            'balance_after' => $wallet->balance,
            'description' => "Payment for order #{$orderId}",
            'order_id' => $orderId,
        ]);

        do_action('commerce.wallet.purchase', $wallet, $transaction);

        return $transaction;
    }

    public function adjust(Customer $customer, float $amount, string $description): CustomerWalletTransaction
    {
        $wallet = $customer->getWalletOrCreate();

        $wallet->balance += $amount;
        $wallet->save();

        $transaction = $wallet->transactions()->create([
            'type' => 'adjustment',
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'description' => $description,
        ]);

        do_action('commerce.wallet.adjusted', $wallet, $transaction);

        return $transaction;
    }

    public function getBalance(Customer $customer): float
    {
        $wallet = $customer->wallet;

        return $wallet ? $wallet->balance : 0.0;
    }
}
