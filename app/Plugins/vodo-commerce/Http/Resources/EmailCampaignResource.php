<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subject' => $this->subject,
            'preview_text' => $this->preview_text,
            'from_name' => $this->from_name,
            'from_email' => $this->from_email,
            'reply_to' => $this->reply_to,

            // Campaign Details
            'type' => $this->type,
            'status' => $this->status,
            'template_id' => $this->template_id,
            'list_id' => $this->list_id,

            // Scheduling
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),

            // A/B Testing
            'is_ab_test' => $this->is_ab_test,
            'ab_test_config' => $this->ab_test_config,
            'ab_test_winner' => $this->ab_test_winner,

            // Target Audience
            'segment_conditions' => $this->segment_conditions,

            // Statistics
            'total_recipients' => $this->total_recipients,
            'emails_sent' => $this->emails_sent,
            'emails_delivered' => $this->emails_delivered,
            'emails_opened' => $this->emails_opened,
            'unique_opens' => $this->unique_opens,
            'emails_clicked' => $this->emails_clicked,
            'unique_clicks' => $this->unique_clicks,
            'emails_bounced' => $this->emails_bounced,
            'emails_unsubscribed' => $this->emails_unsubscribed,
            'emails_complained' => $this->emails_complained,

            // Calculated Metrics
            'open_rate' => (float) $this->open_rate,
            'click_rate' => (float) $this->click_rate,
            'click_to_open_rate' => (float) $this->click_to_open_rate,
            'bounce_rate' => (float) $this->bounce_rate,
            'unsubscribe_rate' => (float) $this->unsubscribe_rate,

            // Revenue
            'total_revenue' => (float) $this->total_revenue,
            'conversions' => $this->conversions,
            'conversion_rate' => (float) $this->conversion_rate,

            // Engagement Score
            'engagement_score' => $this->getEngagementScore(),

            // UTM Parameters
            'utm_campaign' => $this->utm_campaign,
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,

            // Meta
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'template' => EmailTemplateResource::make($this->whenLoaded('template')),
            'list' => EmailListResource::make($this->whenLoaded('list')),
            'sends' => EmailSendResource::collection($this->whenLoaded('sends')),
        ];
    }
}
