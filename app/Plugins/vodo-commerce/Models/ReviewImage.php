<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Database\Factories\ReviewImageFactory;

class ReviewImage extends Model
{
    use HasFactory;

    protected $table = 'commerce_review_images';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ReviewImageFactory
    {
        return ReviewImageFactory::new();
    }

    protected $fillable = [
        'review_id',
        'image_url',
        'thumbnail_url',
        'display_order',
        'alt_text',
        'width',
        'height',
        'file_size',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
