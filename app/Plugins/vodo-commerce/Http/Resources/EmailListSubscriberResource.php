<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailListSubscriberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'list_id' => $this->list_id,
            'customer_id' => $this->customer_id,

            // Subscriber Information
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name && $this->last_name
                ? "{$this->first_name} {$this->last_name}"
                : ($this->first_name ?? $this->last_name),

            // Subscription Status
            'status' => $this->status,
            'subscribed_at' => $this->subscribed_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'unsubscribed_at' => $this->unsubscribed_at?->toIso8601String(),
            'unsubscribe_reason' => $this->unsubscribe_reason,

            // Source Tracking
            'source' => $this->source,
            'signup_ip' => $this->signup_ip,

            // Engagement Metrics
            'emails_sent' => $this->emails_sent,
            'emails_opened' => $this->emails_opened,
            'emails_clicked' => $this->emails_clicked,
            'emails_bounced' => $this->emails_bounced,
            'open_rate' => (float) $this->open_rate,
            'click_rate' => (float) $this->click_rate,
            'last_opened_at' => $this->last_opened_at?->toIso8601String(),
            'last_clicked_at' => $this->last_clicked_at?->toIso8601String(),

            // Engagement Score
            'engagement_score' => $this->getEngagementScore(),

            // Status Flags
            'is_subscribed' => $this->isSubscribed(),
            'is_pending' => $this->isPending(),
            'is_unsubscribed' => $this->isUnsubscribed(),
            'is_bounced' => $this->isBounced(),
            'is_engaged' => $this->isEngaged(),
            'is_inactive' => $this->isInactive(),

            // Preferences
            'preferences' => $this->preferences,
            'custom_fields' => $this->custom_fields,

            // Meta
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'list' => EmailListResource::make($this->whenLoaded('list')),
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
        ];
    }
}
