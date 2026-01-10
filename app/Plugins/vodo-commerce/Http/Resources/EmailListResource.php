<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            // List Type
            'type' => $this->type,
            'criteria' => $this->criteria,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),

            // Settings
            'is_active' => $this->is_active,
            'allow_public_signup' => $this->allow_public_signup,
            'welcome_message' => $this->welcome_message,
            'send_welcome_email' => $this->send_welcome_email,
            'welcome_email_template_id' => $this->welcome_email_template_id,

            // Double Opt-in
            'require_double_optin' => $this->require_double_optin,
            'confirmation_email_template_id' => $this->confirmation_email_template_id,

            // Statistics
            'total_subscribers' => $this->total_subscribers,
            'active_subscribers' => $this->active_subscribers,
            'unsubscribed_count' => $this->unsubscribed_count,
            'bounced_count' => $this->bounced_count,
            'complained_count' => $this->complained_count,

            // Engagement Metrics
            'avg_open_rate' => (float) $this->avg_open_rate,
            'avg_click_rate' => (float) $this->avg_click_rate,

            // Health Score
            'health_score' => $this->getHealthScore(),

            // Meta
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'welcome_template' => EmailTemplateResource::make($this->whenLoaded('welcomeTemplate')),
            'confirmation_template' => EmailTemplateResource::make($this->whenLoaded('confirmationTemplate')),
            'subscribers' => EmailListSubscriberResource::collection($this->whenLoaded('subscribers')),
            'campaigns' => EmailCampaignResource::collection($this->whenLoaded('campaigns')),
        ];
    }
}
