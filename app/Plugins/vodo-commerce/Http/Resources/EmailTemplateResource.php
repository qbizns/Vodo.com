<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,

            // Content
            'default_subject' => $this->default_subject,
            'default_preview_text' => $this->default_preview_text,
            'html_content' => $this->when(
                $request->get('include_content', false),
                $this->html_content
            ),
            'text_content' => $this->when(
                $request->get('include_content', false),
                $this->text_content
            ),

            // Variables
            'available_variables' => $this->available_variables,
            'required_variables' => $this->required_variables,

            // Design
            'thumbnail' => $this->thumbnail,
            'design_config' => $this->design_config,

            // Type & Trigger
            'type' => $this->type,
            'trigger_event' => $this->trigger_event,
            'trigger_conditions' => $this->trigger_conditions,
            'trigger_delay_minutes' => $this->trigger_delay_minutes,

            // Status
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,

            // Usage Statistics
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at?->toIso8601String(),

            // Meta
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'campaigns' => EmailCampaignResource::collection($this->whenLoaded('campaigns')),
        ];
    }
}
