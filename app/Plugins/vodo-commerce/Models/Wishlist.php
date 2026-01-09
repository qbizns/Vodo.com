<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use VodoCommerce\Database\Factories\WishlistFactory;
use VodoCommerce\Traits\BelongsToStore;

class Wishlist extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_wishlists';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WishlistFactory
    {
        return WishlistFactory::new();
    }

    // Visibility Types
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_SHARED = 'shared';
    public const VISIBILITY_PUBLIC = 'public';

    protected $fillable = [
        'store_id',
        'customer_id',
        'name',
        'description',
        'slug',
        'visibility',
        'share_token',
        'is_default',
        'allow_comments',
        'show_purchased_items',
        'event_type',
        'event_date',
        'items_count',
        'views_count',
        'last_viewed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'allow_comments' => 'boolean',
            'show_purchased_items' => 'boolean',
            'items_count' => 'integer',
            'views_count' => 'integer',
            'event_date' => 'date',
            'last_viewed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Boot Method
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($wishlist) {
            if (empty($wishlist->slug)) {
                $wishlist->slug = Str::slug($wishlist->name) . '-' . Str::random(8);
            }

            if (empty($wishlist->share_token)) {
                $wishlist->share_token = Str::random(32);
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class)->orderBy('display_order');
    }

    public function collaborators(): HasMany
    {
        return $this->hasMany(WishlistCollaborator::class);
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isPrivate(): bool
    {
        return $this->visibility === self::VISIBILITY_PRIVATE;
    }

    public function isShared(): bool
    {
        return $this->visibility === self::VISIBILITY_SHARED;
    }

    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Make this the default wishlist for the customer.
     */
    public function makeDefault(): void
    {
        // Remove default from other wishlists
        static::where('store_id', $this->store_id)
            ->where('customer_id', $this->customer_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Increment view count.
     */
    public function recordView(): void
    {
        $this->increment('views_count');
        $this->update(['last_viewed_at' => now()]);
    }

    /**
     * Update items count.
     */
    public function updateItemsCount(): void
    {
        $this->update(['items_count' => $this->items()->count()]);
    }

    /**
     * Change visibility.
     */
    public function changeVisibility(string $visibility): void
    {
        $this->update(['visibility' => $visibility]);
    }

    /**
     * Regenerate share token.
     */
    public function regenerateShareToken(): string
    {
        $newToken = Str::random(32);
        $this->update(['share_token' => $newToken]);

        return $newToken;
    }

    /**
     * Get share URL.
     */
    public function getShareUrl(): string
    {
        return config('app.url') . "/wishlists/{$this->slug}?token={$this->share_token}";
    }

    /**
     * Get public URL.
     */
    public function getPublicUrl(): string
    {
        return config('app.url') . "/wishlists/{$this->slug}";
    }

    /**
     * Check if customer can view this wishlist.
     */
    public function canBeViewedBy(?int $customerId = null, ?string $token = null): bool
    {
        // Owner can always view
        if ($customerId && $customerId === $this->customer_id) {
            return true;
        }

        // Public wishlists can be viewed by anyone
        if ($this->isPublic()) {
            return true;
        }

        // Shared wishlists require valid token or collaboration
        if ($this->isShared()) {
            // Check token
            if ($token && $token === $this->share_token) {
                return true;
            }

            // Check collaboration
            if ($customerId) {
                $isCollaborator = $this->collaborators()
                    ->where('customer_id', $customerId)
                    ->where('status', 'accepted')
                    ->exists();

                if ($isCollaborator) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if customer can edit this wishlist.
     */
    public function canBeEditedBy(?int $customerId = null): bool
    {
        if (!$customerId) {
            return false;
        }

        // Owner can always edit
        if ($customerId === $this->customer_id) {
            return true;
        }

        // Check if collaborator with edit permissions
        $collaborator = $this->collaborators()
            ->where('customer_id', $customerId)
            ->where('status', 'accepted')
            ->whereIn('permission', ['edit', 'manage'])
            ->first();

        return $collaborator !== null;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('visibility', self::VISIBILITY_PRIVATE);
    }

    public function scopeShared($query)
    {
        return $query->where('visibility', self::VISIBILITY_SHARED);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeByShareToken($query, string $token)
    {
        return $query->where('share_token', $token);
    }

    public function scopeUpcomingEvents($query)
    {
        return $query->whereNotNull('event_date')
            ->where('event_date', '>=', now());
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
