<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'customer_email' => $this->customer_email,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'discount_total' => (float) $this->discount_total,
            'shipping_total' => (float) $this->shipping_total,
            'tax_total' => (float) $this->tax_total,
            'total' => (float) $this->total,
            'refund_total' => (float) $this->refund_total,
            'has_refunds' => $this->has_refunds,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'shipping_method' => $this->shipping_method,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'discount_codes' => $this->discount_codes,
            'notes' => $this->notes,
            'cancel_reason' => $this->cancel_reason,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'is_exported' => $this->is_exported,
            'exported_at' => $this->exported_at?->toIso8601String(),
            'meta' => $this->meta,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            // Phase 3: Order Management Extensions
            'order_notes' => OrderNoteResource::collection($this->whenLoaded('notes')),
            'fulfillments' => OrderFulfillmentResource::collection($this->whenLoaded('fulfillments')),
            'refunds' => OrderRefundResource::collection($this->whenLoaded('refunds')),
            'timeline' => OrderTimelineEventResource::collection($this->whenLoaded('timeline')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }
}
