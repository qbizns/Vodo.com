<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'send_id' => $this->send_id,
            'campaign_id' => $this->campaign_id,

            // Event Details
            'event_type' => $this->event_type,
            'recipient_email' => $this->recipient_email,
            'event_at' => $this->event_at->toIso8601String(),

            // Link Tracking (for click events)
            'link_url' => $this->link_url,
            'link_id' => $this->link_id,
            'link_position' => $this->link_position,

            // Bounce Details
            'bounce_type' => $this->bounce_type,
            'bounce_classification' => $this->bounce_classification,
            'bounce_reason' => $this->bounce_reason,
            'smtp_code' => $this->smtp_code,

            // Device & Browser Information
            'user_agent' => $this->user_agent,
            'device_type' => $this->device_type,
            'os' => $this->os,
            'email_client' => $this->email_client,
            'browser' => $this->browser,

            // Geographic Information
            'ip_address' => $this->ip_address,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'location' => $this->getLocation(),

            // Provider Details
            'provider' => $this->provider,
            'provider_event_id' => $this->provider_event_id,
            'provider_data' => $this->provider_data,

            // Event Type Flags
            'is_sent' => $this->isSent(),
            'is_delivered' => $this->isDelivered(),
            'is_opened' => $this->isOpened(),
            'is_clicked' => $this->isClicked(),
            'is_bounced' => $this->isBounced(),
            'is_complained' => $this->isComplained(),
            'is_unsubscribed' => $this->isUnsubscribed(),
            'is_hard_bounce' => $this->isHardBounce(),
            'is_soft_bounce' => $this->isSoftBounce(),

            // Device Type Flags
            'is_mobile' => $this->isMobile(),
            'is_desktop' => $this->isDesktop(),
            'has_location' => $this->hasLocation(),

            // Parsed User Agent
            'parsed_user_agent' => $this->parseUserAgent(),

            // Meta
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'send' => EmailSendResource::make($this->whenLoaded('send')),
            'campaign' => EmailCampaignResource::make($this->whenLoaded('campaign')),
        ];
    }
}
