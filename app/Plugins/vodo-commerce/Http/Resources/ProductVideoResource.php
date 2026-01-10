<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,

            // Video Details
            'title' => $this->title,
            'description' => $this->description,

            // Source
            'source' => $this->source,
            'video_url' => $this->video_url,
            'video_id' => $this->video_id,
            'embed_code' => $this->embed_code,
            'file_path' => $this->file_path,
            'thumbnail_url' => $this->thumbnail_url,
            'embed_url' => $this->getEmbedUrl(),
            'embed_html' => $this->when(
                $request->boolean('include_embed_html'),
                fn() => $this->getEmbedHtml()
            ),

            // Type
            'type' => $this->type,
            'is_youtube' => $this->isYouTube(),
            'is_vimeo' => $this->isVimeo(),
            'is_uploaded' => $this->isUploaded(),

            // Metadata
            'duration' => $this->duration,
            'duration_formatted' => $this->getFormattedDuration(),
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->getFormattedFileSize(),
            'mime_type' => $this->mime_type,
            'resolution' => $this->resolution,

            // Display Configuration
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'autoplay' => $this->autoplay,
            'show_controls' => $this->show_controls,
            'loop' => $this->loop,

            // Performance Metrics
            'view_count' => $this->view_count,
            'play_count' => $this->play_count,
            'avg_watch_time' => (float) $this->avg_watch_time,
            'engagement_rate' => $this->getEngagementRate(),
            'completion_rate' => $this->getCompletionRate(),

            // Accessibility
            'caption_file' => $this->caption_file,
            'transcript' => $this->transcript,

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            // Note: ProductResource not yet created
            // 'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
