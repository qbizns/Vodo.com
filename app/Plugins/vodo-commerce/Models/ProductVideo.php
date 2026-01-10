<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVideo extends Model
{
    use HasFactory;

    protected $table = 'commerce_product_videos';

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'source',
        'video_url',
        'video_id',
        'embed_code',
        'file_path',
        'thumbnail_url',
        'type',
        'duration',
        'file_size',
        'mime_type',
        'resolution',
        'is_featured',
        'is_active',
        'sort_order',
        'autoplay',
        'show_controls',
        'loop',
        'view_count',
        'play_count',
        'avg_watch_time',
        'caption_file',
        'transcript',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'file_size' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'autoplay' => 'boolean',
            'show_controls' => 'boolean',
            'loop' => 'boolean',
            'view_count' => 'integer',
            'play_count' => 'integer',
            'avg_watch_time' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    /**
     * Get the product this video belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the embed URL for the video.
     */
    public function getEmbedUrl(): ?string
    {
        return match ($this->source) {
            'youtube' => "https://www.youtube.com/embed/{$this->video_id}",
            'vimeo' => "https://player.vimeo.com/video/{$this->video_id}",
            'url' => $this->video_url,
            default => null,
        };
    }

    /**
     * Get the full embed HTML.
     */
    public function getEmbedHtml(int $width = 560, int $height = 315): string
    {
        if ($this->embed_code) {
            return $this->embed_code;
        }

        $embedUrl = $this->getEmbedUrl();

        if (!$embedUrl) {
            return '';
        }

        $autoplay = $this->autoplay ? '?autoplay=1' : '';
        $controls = !$this->show_controls ? '&controls=0' : '';
        $loop = $this->loop ? '&loop=1' : '';

        $params = $autoplay . $controls . $loop;

        return "<iframe width=\"{$width}\" height=\"{$height}\" src=\"{$embedUrl}{$params}\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>";
    }

    /**
     * Get formatted duration (HH:MM:SS).
     */
    public function getFormattedDuration(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSize(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Record a view.
     */
    public function recordView(): void
    {
        $this->increment('view_count');
    }

    /**
     * Record a play.
     */
    public function recordPlay(): void
    {
        $this->increment('play_count');
    }

    /**
     * Update average watch time.
     */
    public function updateWatchTime(float $watchTime): void
    {
        if ($this->play_count == 0) {
            $this->update(['avg_watch_time' => $watchTime]);

            return;
        }

        // Calculate new average
        $currentTotal = $this->avg_watch_time * $this->play_count;
        $newTotal = $currentTotal + $watchTime;
        $newAverage = $newTotal / ($this->play_count + 1);

        $this->update(['avg_watch_time' => round($newAverage, 2)]);
    }

    /**
     * Get engagement rate (plays / views).
     */
    public function getEngagementRate(): float
    {
        if ($this->view_count == 0) {
            return 0;
        }

        return round(($this->play_count / $this->view_count) * 100, 2);
    }

    /**
     * Get completion rate (avg watch time / duration).
     */
    public function getCompletionRate(): float
    {
        if (!$this->duration || $this->duration == 0) {
            return 0;
        }

        return round(($this->avg_watch_time / $this->duration) * 100, 2);
    }

    /**
     * Check if video is from YouTube.
     */
    public function isYouTube(): bool
    {
        return $this->source === 'youtube';
    }

    /**
     * Check if video is from Vimeo.
     */
    public function isVimeo(): bool
    {
        return $this->source === 'vimeo';
    }

    /**
     * Check if video is self-hosted.
     */
    public function isUploaded(): bool
    {
        return $this->source === 'upload';
    }

    /**
     * Scope: Active videos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Featured videos.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: By video type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: By source.
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: By product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: High engagement (engagement rate >= threshold).
     */
    public function scopeHighEngagement($query, float $threshold = 50.0)
    {
        return $query->whereRaw('(play_count / NULLIF(view_count, 0)) * 100 >= ?', [$threshold]);
    }

    /**
     * Scope: Ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_featured', 'desc')
            ->orderBy('sort_order', 'asc');
    }
}
