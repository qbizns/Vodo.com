<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailSendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'template_id' => $this->template_id,
            'customer_id' => $this->customer_id,

            // Recipient Information
            'recipient_email' => $this->recipient_email,
            'recipient_name' => $this->recipient_name,

            // Email Content
            'subject' => $this->subject,
            'preview_text' => $this->preview_text,
            'from_name' => $this->from_name,
            'from_email' => $this->from_email,
            'reply_to' => $this->reply_to,

            // Send Type & Status
            'type' => $this->type,
            'status' => $this->status,

            // Timestamps
            'queued_at' => $this->queued_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'bounced_at' => $this->bounced_at?->toIso8601String(),

            // Delivery Details
            'message_id' => $this->message_id,
            'provider' => $this->provider,
            'provider_message_id' => $this->provider_message_id,
            'error_message' => $this->error_message,
            'bounce_type' => $this->bounce_type,
            'bounce_reason' => $this->bounce_reason,

            // Tracking
            'is_opened' => $this->is_opened,
            'is_clicked' => $this->is_clicked,
            'open_count' => $this->open_count,
            'click_count' => $this->click_count,
            'first_opened_at' => $this->first_opened_at?->toIso8601String(),
            'last_opened_at' => $this->last_opened_at?->toIso8601String(),
            'first_clicked_at' => $this->first_clicked_at?->toIso8601String(),
            'last_clicked_at' => $this->last_clicked_at?->toIso8601String(),

            // A/B Testing
            'ab_test_variant' => $this->ab_test_variant,

            // Sendable Relationship
            'sendable_type' => $this->sendable_type,
            'sendable_id' => $this->sendable_id,

            // Revenue Tracking
            'has_conversion' => $this->has_conversion,
            'conversion_revenue' => (float) $this->conversion_revenue,
            'converted_at' => $this->converted_at?->toIso8601String(),

            // Time Metrics
            'time_to_first_open' => $this->getTimeToFirstOpen(),
            'time_to_conversion' => $this->getTimeToConversion(),

            // UTM Parameters
            'utm_parameters' => $this->utm_parameters,

            // Status Flags
            'is_pending' => $this->isPending(),
            'is_queued' => $this->isQueued(),
            'is_sending' => $this->isSending(),
            'is_sent' => $this->isSent(),
            'is_delivered' => $this->isDelivered(),
            'is_failed' => $this->isFailed(),
            'is_bounced' => $this->isBounced(),

            // Custom Data & Meta
            'custom_data' => $this->custom_data,
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'campaign' => EmailCampaignResource::make($this->whenLoaded('campaign')),
            'template' => EmailTemplateResource::make($this->whenLoaded('template')),
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'events' => EmailEventResource::collection($this->whenLoaded('events')),
        ];
    }
}
