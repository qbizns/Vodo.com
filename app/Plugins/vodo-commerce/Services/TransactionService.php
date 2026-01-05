<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Collection;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Transaction;

class TransactionService
{
    /**
     * Create a new payment transaction
     */
    public function createPayment(array $data): Transaction
    {
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        // Calculate fees
        $feeCalculation = $paymentMethod->calculateFees($data['amount']);

        return Transaction::create([
            'store_id' => $data['store_id'],
            'order_id' => $data['order_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'payment_method_id' => $data['payment_method_id'],
            'external_id' => $data['external_id'] ?? null,
            'type' => Transaction::TYPE_PAYMENT,
            'status' => $data['status'] ?? Transaction::STATUS_PENDING,
            'payment_status' => $data['payment_status'] ?? null,
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'fee_amount' => $feeCalculation['fee_amount'],
            'net_amount' => $feeCalculation['net_amount'],
            'fees' => $feeCalculation['fee_breakdown'],
            'payment_method_type' => $data['payment_method_type'] ?? null,
            'card_brand' => $data['card_brand'] ?? null,
            'card_last4' => $data['card_last4'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'wallet_provider' => $data['wallet_provider'] ?? null,
            'gateway_response' => $data['gateway_response'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'is_test' => $data['is_test'] ?? false,
            'metadata' => $data['metadata'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Process a transaction (mark as completed)
     */
    public function processTransaction(Transaction $transaction, ?string $externalId = null): Transaction
    {
        $transaction->markAsCompleted($externalId);

        do_action('commerce.transaction.completed', $transaction);

        return $transaction->fresh();
    }

    /**
     * Fail a transaction
     */
    public function failTransaction(Transaction $transaction, string $reason, ?string $code = null): Transaction
    {
        $transaction->markAsFailed($reason, $code);

        do_action('commerce.transaction.failed', $transaction);

        return $transaction->fresh();
    }

    /**
     * Authorize a payment (for card payments)
     */
    public function authorizePayment(Transaction $transaction, ?string $externalId = null): Transaction
    {
        $transaction->authorize($externalId);

        do_action('commerce.transaction.authorized', $transaction);

        return $transaction->fresh();
    }

    /**
     * Capture an authorized payment
     */
    public function capturePayment(Transaction $transaction): Transaction
    {
        if ($transaction->payment_status !== Transaction::PAYMENT_STATUS_AUTHORIZED) {
            throw new \RuntimeException('Can only capture authorized transactions');
        }

        $transaction->capture();

        do_action('commerce.transaction.captured', $transaction);

        return $transaction->fresh();
    }

    /**
     * Create a refund transaction
     */
    public function createRefund(Transaction $transaction, float $amount, ?string $reason = null): Transaction
    {
        if (!$transaction->canBeRefunded()) {
            throw new \RuntimeException('Transaction cannot be refunded');
        }

        $refund = $transaction->createRefund($amount, $reason);

        do_action('commerce.transaction.refund_created', $refund, $transaction);

        return $refund;
    }

    /**
     * Process a refund (mark as completed)
     */
    public function processRefund(Transaction $refund): Transaction
    {
        if (!$refund->isRefund()) {
            throw new \RuntimeException('Transaction is not a refund');
        }

        $refund->markAsCompleted();

        do_action('commerce.transaction.refund_completed', $refund);

        return $refund->fresh();
    }

    /**
     * Cancel a transaction
     */
    public function cancelTransaction(Transaction $transaction): Transaction
    {
        if ($transaction->isCompleted()) {
            throw new \RuntimeException('Cannot cancel a completed transaction');
        }

        $transaction->cancel();

        do_action('commerce.transaction.cancelled', $transaction);

        return $transaction->fresh();
    }

    /**
     * Get all transactions for a store
     */
    public function getAll(int $storeId, array $filters = []): Collection
    {
        $query = Transaction::where('store_id', $storeId)->recent();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        if (!empty($filters['currency'])) {
            $query->inCurrency($filters['currency']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        return $query->get();
    }

    /**
     * Get transactions for an order
     */
    public function getForOrder(int $orderId): Collection
    {
        return Transaction::forOrder($orderId)->recent()->get();
    }

    /**
     * Get transactions for a customer
     */
    public function getForCustomer(int $customerId): Collection
    {
        return Transaction::forCustomer($customerId)->recent()->get();
    }

    /**
     * Update transaction metadata
     */
    public function updateMetadata(Transaction $transaction, array $metadata): Transaction
    {
        $existing = $transaction->metadata ?? [];
        $transaction->update([
            'metadata' => array_merge($existing, $metadata),
        ]);

        return $transaction->fresh();
    }

    /**
     * Add notes to transaction
     */
    public function addNotes(Transaction $transaction, string $notes): Transaction
    {
        $transaction->update(['notes' => $notes]);

        return $transaction->fresh();
    }

    /**
     * Get transaction statistics for a store
     */
    public function getStatistics(int $storeId, ?string $currency = null, ?array $dateRange = null): array
    {
        $query = Transaction::where('store_id', $storeId)->payments();

        if ($currency) {
            $query->inCurrency($currency);
        }

        if ($dateRange) {
            $query->betweenDates($dateRange['start'], $dateRange['end']);
        }

        $completed = clone $query;
        $completed->completed();

        $pending = clone $query;
        $pending->pending();

        $failed = clone $query;
        $failed->failed();

        return [
            'total_revenue' => $completed->sum('net_amount'),
            'total_fees' => $completed->sum('fee_amount'),
            'gross_revenue' => $completed->sum('amount'),
            'total_transactions' => $query->count(),
            'completed_transactions' => $completed->count(),
            'pending_transactions' => $pending->count(),
            'failed_transactions' => $failed->count(),
            'average_transaction_value' => $completed->avg('amount'),
            'currency' => $currency,
        ];
    }

    /**
     * Get refund statistics
     */
    public function getRefundStatistics(int $storeId, ?string $currency = null): array
    {
        $query = Transaction::where('store_id', $storeId)->refunds();

        if ($currency) {
            $query->inCurrency($currency);
        }

        $completed = clone $query;
        $completed->completed();

        return [
            'total_refunded' => $completed->sum('amount'),
            'total_refunds' => $query->count(),
            'completed_refunds' => $completed->count(),
            'pending_refunds' => $query->pending()->count(),
            'currency' => $currency,
        ];
    }

    /**
     * Get revenue by payment method
     */
    public function getRevenueByPaymentMethod(int $storeId, ?array $dateRange = null): Collection
    {
        $query = Transaction::where('store_id', $storeId)
            ->payments()
            ->completed()
            ->with('paymentMethod');

        if ($dateRange) {
            $query->betweenDates($dateRange['start'], $dateRange['end']);
        }

        return $query->get()->groupBy('payment_method_id')->map(function ($transactions) {
            $first = $transactions->first();

            return [
                'payment_method' => $first->paymentMethod->name,
                'total_revenue' => $transactions->sum('net_amount'),
                'total_fees' => $transactions->sum('fee_amount'),
                'transaction_count' => $transactions->count(),
            ];
        })->values();
    }

    /**
     * Reconcile transaction with gateway
     */
    public function reconcile(Transaction $transaction, array $gatewayData): Transaction
    {
        $updates = [
            'gateway_response' => $gatewayData,
            'external_id' => $gatewayData['external_id'] ?? $transaction->external_id,
        ];

        // Update status based on gateway status
        if (isset($gatewayData['status'])) {
            $updates['status'] = $this->mapGatewayStatus($gatewayData['status']);
        }

        $transaction->update($updates);

        return $transaction->fresh();
    }

    /**
     * Map gateway status to internal status
     */
    protected function mapGatewayStatus(string $gatewayStatus): string
    {
        return match (strtolower($gatewayStatus)) {
            'succeeded', 'success', 'completed' => Transaction::STATUS_COMPLETED,
            'pending', 'processing' => Transaction::STATUS_PROCESSING,
            'failed', 'error' => Transaction::STATUS_FAILED,
            'cancelled', 'canceled' => Transaction::STATUS_CANCELLED,
            'refunded' => Transaction::STATUS_REFUNDED,
            default => Transaction::STATUS_PENDING,
        };
    }
}
