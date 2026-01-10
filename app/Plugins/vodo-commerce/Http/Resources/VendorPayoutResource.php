<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorPayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'payout_number' => $this->payout_number,

            // Payout Period
            'period' => [
                'start' => $this->period_start->toDateString(),
                'end' => $this->period_end->toDateString(),
            ],

            // Financial Details
            'financial' => [
                'gross_amount' => (float) $this->gross_amount,
                'platform_fees' => (float) $this->platform_fees,
                'adjustments' => (float) $this->adjustments,
                'net_amount' => (float) $this->net_amount,
                'currency' => $this->currency,
            ],

            // Summary
            'summary' => [
                'commission_count' => $this->commission_count,
                'order_count' => $this->order_count,
            ],

            // Payout Method
            'payout_method' => $this->payout_method,

            // Status
            'status' => $this->status,
            'is_pending' => $this->isPending(),
            'is_processing' => $this->isProcessing(),
            'is_completed' => $this->isCompleted(),
            'is_failed' => $this->isFailed(),

            // Timestamps
            'processed_at' => $this->processed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),

            // Transaction Details
            'transaction_id' => $this->transaction_id,
            'failure_reason' => $this->when($this->failure_reason, $this->failure_reason),
            'cancellation_reason' => $this->when($this->cancellation_reason, $this->cancellation_reason),

            // Notes & Attachments
            'notes' => $this->notes,
            'attachments' => $this->attachments,

            // Relationships
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'commissions' => VendorCommissionResource::collection($this->whenLoaded('commissions')),
            'processed_by' => $this->whenLoaded('processedBy', fn() => [
                'id' => $this->processedBy->id,
                'name' => $this->processedBy->name,
                'email' => $this->processedBy->email,
            ]),

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
