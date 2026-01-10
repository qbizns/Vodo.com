<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_vendors';

    protected $fillable = [
        'store_id',
        'user_id',
        'business_name',
        'legal_name',
        'slug',
        'description',
        'logo',
        'banner',
        'email',
        'phone',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'business_registration_number',
        'verification_documents',
        'commission_type',
        'commission_value',
        'commission_tiers',
        'payout_method',
        'payout_schedule',
        'minimum_payout_amount',
        'payout_details',
        'status',
        'is_verified',
        'verified_at',
        'rejection_reason',
        'average_rating',
        'total_reviews',
        'shipping_policy',
        'return_policy',
        'terms_and_conditions',
        'total_products',
        'total_sales',
        'total_revenue',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'verification_documents' => 'array',
            'commission_tiers' => 'array',
            'payout_details' => 'array',
            'shipping_policy' => 'array',
            'return_policy' => 'array',
            'terms_and_conditions' => 'array',
            'meta' => 'array',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'commission_value' => 'decimal:2',
            'minimum_payout_amount' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'total_revenue' => 'decimal:2',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'commerce_vendor_products')
            ->withPivot([
                'is_approved',
                'approved_at',
                'approved_by',
                'rejection_reason',
                'commission_override',
                'stock_quantity',
                'manage_stock',
                'price_override',
                'compare_at_price_override',
                'meta',
            ])
            ->withTimestamps();
    }

    public function approvedProducts(): BelongsToMany
    {
        return $this->products()->wherePivot('is_approved', true);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(VendorCommission::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VendorReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(VendorMessage::class);
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeSuspended(Builder $query): void
    {
        $query->where('status', 'suspended');
    }

    public function scopeRejected(Builder $query): void
    {
        $query->where('status', 'rejected');
    }

    public function scopeVerified(Builder $query): void
    {
        $query->where('is_verified', true);
    }

    public function scopeForStore(Builder $query, int $storeId): void
    {
        $query->where('store_id', $storeId);
    }

    public function scopeWithMinRating(Builder $query, float $minRating): void
    {
        $query->where('average_rating', '>=', $minRating);
    }

    public function scopeTopRated(Builder $query, int $limit = 10): void
    {
        $query->where('total_reviews', '>', 0)
            ->orderByDesc('average_rating')
            ->orderByDesc('total_reviews')
            ->limit($limit);
    }

    public function scopeByCommissionType(Builder $query, string $type): void
    {
        $query->where('commission_type', $type);
    }

    public function scopeByPayoutSchedule(Builder $query, string $schedule): void
    {
        $query->where('payout_schedule', $schedule);
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(function ($q) use ($term) {
            $q->where('business_name', 'like', "%{$term}%")
                ->orWhere('legal_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%");
        });
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function approve(): bool
    {
        return $this->update([
            'status' => 'approved',
            'rejection_reason' => null,
        ]);
    }

    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    public function suspend(string $reason = null): bool
    {
        return $this->update([
            'status' => 'suspended',
            'rejection_reason' => $reason,
        ]);
    }

    public function reject(string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function verify(): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function unverify(): bool
    {
        return $this->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'active']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    public function calculateCommission(float $amount): float
    {
        if ($this->commission_type === 'flat') {
            return (float) $this->commission_value;
        }

        if ($this->commission_type === 'percentage') {
            return round($amount * ($this->commission_value / 100), 2);
        }

        if ($this->commission_type === 'tiered' && !empty($this->commission_tiers)) {
            // Find applicable tier based on total revenue
            $tiers = collect($this->commission_tiers)->sortByDesc('threshold');

            foreach ($tiers as $tier) {
                if ($this->total_revenue >= $tier['threshold']) {
                    return round($amount * ($tier['rate'] / 100), 2);
                }
            }

            // Fallback to base rate
            return round($amount * ($this->commission_value / 100), 2);
        }

        return 0;
    }

    public function updateRating(float $newRating): void
    {
        $totalReviews = $this->total_reviews;
        $currentAverage = (float) $this->average_rating;

        // Calculate new average
        $newTotal = ($currentAverage * $totalReviews) + $newRating;
        $newCount = $totalReviews + 1;
        $newAverage = $newTotal / $newCount;

        $this->update([
            'average_rating' => round($newAverage, 2),
            'total_reviews' => $newCount,
        ]);
    }

    public function recalculateRating(): void
    {
        $avgRating = $this->approvedReviews()->avg('rating');
        $totalReviews = $this->approvedReviews()->count();

        $this->update([
            'average_rating' => $avgRating ? round($avgRating, 2) : 0,
            'total_reviews' => $totalReviews,
        ]);
    }

    public function incrementProductCount(): void
    {
        $this->increment('total_products');
    }

    public function decrementProductCount(): void
    {
        $this->decrement('total_products');
    }

    public function incrementSalesMetrics(float $amount): void
    {
        $this->increment('total_sales');
        $this->increment('total_revenue', $amount);
    }

    public function getPendingCommissionsTotal(): float
    {
        return (float) $this->commissions()
            ->where('status', 'pending')
            ->sum('vendor_earnings');
    }

    public function getApprovedCommissionsTotal(): float
    {
        return (float) $this->commissions()
            ->where('status', 'approved')
            ->whereNull('payout_id')
            ->sum('vendor_earnings');
    }

    public function canRequestPayout(): bool
    {
        $unpaidEarnings = $this->getApprovedCommissionsTotal();

        return $this->isActive()
            && $unpaidEarnings >= $this->minimum_payout_amount;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    // =========================================================================
    // EVENTS
    // =========================================================================

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor) {
            if (empty($vendor->slug)) {
                $vendor->slug = Str::slug($vendor->business_name);

                // Ensure uniqueness
                $originalSlug = $vendor->slug;
                $counter = 1;

                while (static::where('slug', $vendor->slug)->exists()) {
                    $vendor->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }
}
