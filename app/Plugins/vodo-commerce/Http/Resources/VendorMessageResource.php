<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'parent_id' => $this->parent_id,

            // Message Content
            'subject' => $this->subject,
            'body' => $this->body,
            'attachments' => $this->attachments,

            // Sender Information
            'sender' => [
                'type' => $this->sender_type,
                'id' => $this->sender_id,
                'name' => $this->sender_name,
                'email' => $this->sender_email,
            ],

            // Status
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category,

            // Flags
            'is_root_message' => $this->isRootMessage(),
            'is_reply' => $this->isReply(),
            'is_high_priority' => $this->isHighPriority(),
            'has_attachments' => $this->hasAttachments(),

            // Internal Notes (admin/vendor only)
            'internal_notes' => $this->when(
                $request->user()?->can('viewInternalNotes', $this),
                $this->internal_notes
            ),

            // Relationships
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'parent' => new VendorMessageResource($this->whenLoaded('parent')),
            'replies' => VendorMessageResource::collection($this->whenLoaded('replies')),

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->toISOString()),
        ];
    }
}
