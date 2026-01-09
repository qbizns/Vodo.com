<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\ReviewImage;

class ReviewImageFactory extends Factory
{
    protected $model = ReviewImage::class;

    public function definition(): array
    {
        return [
            'review_id' => ProductReview::factory(),
            'image_url' => $this->faker->imageUrl(800, 600, 'product'),
            'thumbnail_url' => $this->faker->imageUrl(200, 200, 'product'),
            'display_order' => 0,
            'alt_text' => $this->faker->sentence(3),
            'width' => 800,
            'height' => 600,
            'file_size' => $this->faker->numberBetween(50000, 500000),
            'meta' => null,
        ];
    }
}
