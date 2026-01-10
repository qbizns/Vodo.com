<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_vendor_reviews';

    protected $fillable = [
        'vendor_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'product_quality_rating',
        'shipping_speed_rating',
        'communication_rating',
        'customer_service_rating',
        'is_verified_purchase',
        'status',
        'is_approved',
        'is_featured',
        'is_flagged',
        'flag_reason',
        'approved_at',
        'flagged_at',
        'helpful_count',
        'unhelpful_count',
        'vendor_response',
        'vendor_response_at',
        'admin_response',
        'admin_response_at',
        'admin_responder_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_verified_purchase' => 'boolean',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'is_flagged' => 'boolean',
            'approved_at' => 'datetime',
            'flagged_at' => 'datetime',
            'vendor_response_at' => 'datetime',
            'admin_response_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function adminResponder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'admin_responder_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(VendorReviewVote::class);
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeApproved(Builder $query): void
    {
        $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeRejected(Builder $query): void
    {
        $query->where('status', 'rejected');
    }

    public function scopeFlagged(Builder $query): void
    {
        $query->where('is_flagged', true);
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function scopeVerifiedPurchase(Builder $query): void
    {
        $query->where('is_verified_purchase', true);
    }

    public function scopeForVendor(Builder $query, int $vendorId): void
    {
        $query->where('vendor_id', $vendorId);
    }

    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    public function scopeForOrder(Builder $query, int $orderId): void
    {
        $query->where('order_id', $orderId);
    }

    public function scopeByRating(Builder $query, int $rating): void
    {
        $query->where('rating', $rating);
    }

    public function scopeMinRating(Builder $query, int $minRating): void
    {
        $query->where('rating', '>=', $minRating);
    }

    public function scopeMaxRating(Builder $query, int $maxRating): void
    {
        $query->where('rating', '<=', $maxRating);
    }

    public function scopeWithResponse(Builder $query): void
    {
        $query->whereNotNull('vendor_response');
    }

    public function scopeWithoutResponse(Builder $query): void
    {
        $query->whereNull('vendor_response');
    }

    public function scopeMostHelpful(Builder $query, int $limit = 10): void
    {
        $query->where('helpful_count', '>', 0)
            ->orderByDesc('helpful_count')
            ->limit($limit);
    }

    public function scopeRecent(Builder $query, int $days = 30): void
    {
        $query->where('created_at', '>=', now()->subDays($days));
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function approve(): bool
    {
        $result = $this->update([
            'status' => 'approved',
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        if ($result) {
            // Update vendor's average rating
            $this->vendor->recalculateRating();
        }

        return $result;
    }

    public function reject(string $reason = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'is_approved' => false,
            'approved_at' => null,
            'flag_reason' => $reason,
        ]);
    }

    public function flag(string $reason): bool
    {
        return $this->update([
            'is_flagged' => true,
            'flagged_at' => now(),
            'flag_reason' => $reason,
        ]);
    }

    public function unflag(): bool
    {
        return $this->update([
            'is_flagged' => false,
            'flagged_at' => null,
            'flag_reason' => null,
        ]);
    }

    public function feature(): bool
    {
        return $this->update(['is_featured' => true]);
    }

    public function unfeature(): bool
    {
        return $this->update(['is_featured' => false]);
    }

    public function addVendorResponse(string $response): bool
    {
        return $this->update([
            'vendor_response' => $response,
            'vendor_response_at' => now(),
        ]);
    }

    public function updateVendorResponse(string $response): bool
    {
        return $this->update([
            'vendor_response' => $response,
        ]);
    }

    public function removeVendorResponse(): bool
    {
        return $this->update([
            'vendor_response' => null,
            'vendor_response_at' => null,
        ]);
    }

    public function addAdminResponse(string $response, int $adminId): bool
    {
        return $this->update([
            'admin_response' => $response,
            'admin_response_at' => now(),
            'admin_responder_id' => $adminId,
        ]);
    }

    public function updateAdminResponse(string $response): bool
    {
        return $this->update([
            'admin_response' => $response,
        ]);
    }

    public function removeAdminResponse(): bool
    {
        return $this->update([
            'admin_response' => null,
            'admin_response_at' => null,
            'admin_responder_id' => null,
        ]);
    }

    public function incrementHelpfulCount(): void
    {
        $this->increment('helpful_count');
    }

    public function incrementUnhelpfulCount(): void
    {
        $this->increment('unhelpful_count');
    }

    public function decrementHelpfulCount(): void
    {
        $this->decrement('helpful_count');
    }

    public function decrementUnhelpfulCount(): void
    {
        $this->decrement('unhelpful_count');
    }

    public function getHelpfulnessScore(): float
    {
        $total = $this->helpful_count + $this->unhelpful_count;

        if ($total === 0) {
            return 0;
        }

        return round(($this->helpful_count / $total) * 100, 2);
    }

    public function getAverageDetailedRating(): float
    {
        $ratings = array_filter([
            $this->product_quality_rating,
            $this->shipping_speed_rating,
            $this->communication_rating,
            $this->customer_service_rating,
        ]);

        if (empty($ratings)) {
            return 0;
        }

        return round(array_sum($ratings) / count($ratings), 2);
    }

    public function isApproved(): bool
    {
        return $this->is_approved === true;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isFlagged(): bool
    {
        return $this->is_flagged === true;
    }

    public function isFeatured(): bool
    {
        return $this->is_featured === true;
    }

    public function isVerifiedPurchase(): bool
    {
        return $this->is_verified_purchase === true;
    }

    public function hasVendorResponse(): bool
    {
        return !empty($this->vendor_response);
    }

    public function hasAdminResponse(): bool
    {
        return !empty($this->admin_response);
    }
}
