<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'external_id' => $this->external_id,
            'reference_number' => $this->reference_number,

            // Type & Status
            'type' => $this->type,
            'status' => $this->status,
            'payment_status' => $this->payment_status,

            // Amounts
            'currency' => $this->currency,
            'amount' => $this->amount,
            'fee_amount' => $this->fee_amount,
            'net_amount' => $this->net_amount,
            'fees' => $this->fees,

            // Payment Method Details
            'payment_method_type' => $this->payment_method_type,
            'card_brand' => $this->card_brand,
            'card_last4' => $this->card_last4,
            'bank_name' => $this->bank_name,
            'wallet_provider' => $this->wallet_provider,

            // Failure Information
            'failure_reason' => $this->when($this->isFailed(), $this->failure_reason),
            'failure_code' => $this->when($this->isFailed(), $this->failure_code),

            // Timing
            'authorized_at' => $this->authorized_at?->toIso8601String(),
            'captured_at' => $this->captured_at?->toIso8601String(),
            'settled_at' => $this->settled_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),

            // Refund Information
            'refunded_amount' => $this->refunded_amount,
            'refundable_amount' => $this->getRefundableAmount(),
            'can_be_refunded' => $this->canBeRefunded(),
            'refund_reason' => $this->when($this->isRefund(), $this->refund_reason),

            // Relationships
            'order_id' => $this->order_id,
            'customer_id' => $this->customer_id,
            'payment_method' => $this->when(
                $this->relationLoaded('paymentMethod'),
                fn() => new PaymentMethodResource($this->paymentMethod)
            ),
            'parent_transaction_id' => $this->parent_transaction_id,
            'refunds' => $this->when(
                $this->relationLoaded('refunds'),
                fn() => TransactionResource::collection($this->refunds)
            ),

            // Security & Testing
            'is_test' => $this->is_test,

            // Metadata
            'metadata' => $this->metadata,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
