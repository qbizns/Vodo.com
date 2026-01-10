<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorCommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'product_id' => $this->product_id,
            'payout_id' => $this->payout_id,

            // Financial Details
            'financial' => [
                'item_subtotal' => (float) $this->item_subtotal,
                'item_discount' => (float) $this->item_discount,
                'item_tax' => (float) $this->item_tax,
                'item_total' => (float) $this->item_total,
            ],

            // Commission Details
            'commission' => [
                'type' => $this->commission_type,
                'rate' => (float) $this->commission_rate,
                'amount' => (float) $this->commission_amount,
            ],

            // Earnings
            'platform_fee' => (float) $this->platform_fee,
            'vendor_earnings' => (float) $this->vendor_earnings,

            // Status
            'status' => $this->status,
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),
            'is_paid' => $this->isPaid(),
            'is_disputed' => $this->isDisputed(),
            'is_ready_for_payout' => $this->isReadyForPayout(),

            // Timestamps
            'approved_at' => $this->approved_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'disputed_at' => $this->disputed_at?->toISOString(),
            'dispute_resolved_at' => $this->dispute_resolved_at?->toISOString(),

            // Dispute Information
            'dispute' => $this->when($this->isDisputed(), [
                'reason' => $this->dispute_reason,
                'resolution' => $this->dispute_resolution,
            ]),

            // Refund Information
            'refund' => $this->when($this->refunded_amount > 0, [
                'amount' => (float) $this->refunded_amount,
                'refunded_at' => $this->refunded_at?->toISOString(),
            ]),

            // Notes
            'notes' => $this->notes,

            // Relationships
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'payout' => new VendorPayoutResource($this->whenLoaded('payout')),

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
