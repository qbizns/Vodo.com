<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderRefund;
use VodoCommerce\Models\OrderTimelineEvent;
use VodoCommerce\Services\CustomerWalletService;

class OrderRefundService
{
    /**
     * Create a new refund for an order.
     */
    public function createRefund(Order $order, array $items, array $data): OrderRefund
    {
        $amount = $this->calculateRefundAmount($items);

        $refund = $order->refunds()->create([
            'store_id' => $order->store_id,
            'amount' => $data['amount'] ?? $amount,
            'reason' => $data['reason'] ?? null,
            'refund_method' => $data['refund_method'] ?? 'original_payment',
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        // Attach items to refund
        foreach ($items as $item) {
            $refund->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity' => $item['quantity'],
                'amount' => $item['amount'],
            ]);
        }

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $order,
            'refund_requested',
            'Refund Requested',
            "Refund of {$refund->amount} requested",
            [
                'refund_id' => $refund->id,
                'refund_number' => $refund->refund_number,
                'amount' => $refund->amount,
            ]
        );

        do_action('commerce.refund.created', $refund);

        return $refund->load('items.orderItem');
    }

    /**
     * Approve a refund.
     */
    public function approveRefund(OrderRefund $refund): OrderRefund
    {
        $refund->approve();

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $refund->order,
            'refund_approved',
            'Refund Approved',
            "Refund {$refund->refund_number} approved",
            [
                'refund_id' => $refund->id,
                'refund_number' => $refund->refund_number,
                'amount' => $refund->amount,
            ]
        );

        do_action('commerce.refund.approved', $refund);

        return $refund->fresh();
    }

    /**
     * Reject a refund.
     */
    public function rejectRefund(OrderRefund $refund, string $reason): OrderRefund
    {
        $refund->reject($reason);

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $refund->order,
            'refund_rejected',
            'Refund Rejected',
            "Refund {$refund->refund_number} rejected: {$reason}",
            [
                'refund_id' => $refund->id,
                'refund_number' => $refund->refund_number,
                'reason' => $reason,
            ]
        );

        do_action('commerce.refund.rejected', $refund);

        return $refund->fresh();
    }

    /**
     * Process and complete a refund.
     */
    public function processRefund(OrderRefund $refund): OrderRefund
    {
        $refund->process();

        // Issue refund based on method
        if ($refund->refund_method === 'store_credit') {
            $this->issueStoreCredit($refund);
        }

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $refund->order,
            'refund_completed',
            'Refund Completed',
            "Refund {$refund->refund_number} of {$refund->amount} completed",
            [
                'refund_id' => $refund->id,
                'refund_number' => $refund->refund_number,
                'amount' => $refund->amount,
                'method' => $refund->refund_method,
            ]
        );

        do_action('commerce.refund.processed', $refund);

        return $refund->fresh();
    }

    /**
     * Calculate refund amount from items.
     */
    public function calculateRefundAmount(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $item['amount'];
        }

        return $total;
    }

    /**
     * Issue store credit for a refund.
     */
    public function issueStoreCredit(OrderRefund $refund): void
    {
        $order = $refund->order;

        if (!$order->customer) {
            return;
        }

        $walletService = new CustomerWalletService();
        $walletService->deposit(
            $order->customer,
            $refund->amount,
            "Refund from order {$order->order_number}",
            $refund->refund_number
        );

        do_action('commerce.refund.store_credit_issued', $refund, $order->customer);
    }

    /**
     * Get remaining refundable amount for an order.
     */
    public function getRefundableAmount(Order $order): float
    {
        $totalRefunded = $order->refunds()
            ->whereIn('status', ['processing', 'completed'])
            ->sum('amount');

        return max(0, $order->total - $totalRefunded);
    }

    /**
     * Check if an order can be refunded.
     */
    public function canRefund(Order $order): bool
    {
        if (!$order->canBeRefunded()) {
            return false;
        }

        return $this->getRefundableAmount($order) > 0;
    }
}
