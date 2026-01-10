<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'subscription_id' => $this->subscription_id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'transaction_id' => $this->transaction_id,

            // Status
            'status' => $this->status,
            'is_paid' => $this->isPaid(),
            'is_pending' => $this->isPending(),
            'is_failed' => $this->isFailed(),

            // Billing Period
            'period_start' => $this->period_start?->toIso8601String(),
            'period_end' => $this->period_end?->toIso8601String(),

            // Amounts
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'usage_charges' => (float) $this->usage_charges,
            'tax_total' => (float) $this->tax_total,
            'total' => (float) $this->total,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) $this->amount_due,
            'refund_total' => (float) $this->refund_total,

            // Line Items
            'line_items' => $this->line_items,
            'usage_details' => $this->usage_details,

            // Payment Information
            'due_date' => $this->due_date?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),

            // Failure & Retry Information
            'payment_error' => $this->when($this->isFailed(), $this->payment_error),
            'attempt_count' => $this->attempt_count,
            'last_attempt_at' => $this->last_attempt_at?->toIso8601String(),
            'next_retry_at' => $this->next_retry_at?->toIso8601String(),
            'can_retry' => $this->canRetry(),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'transaction' => $this->when(
                $this->relationLoaded('transaction'),
                fn() => new TransactionResource($this->transaction)
            ),
        ];
    }
}
