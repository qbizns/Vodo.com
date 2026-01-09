<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use VodoCommerce\Database\Factories\WishlistCollaboratorFactory;

class WishlistCollaborator extends Model
{
    use HasFactory;

    protected $table = 'commerce_wishlist_collaborators';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WishlistCollaboratorFactory
    {
        return WishlistCollaboratorFactory::new();
    }

    // Permission Levels
    public const PERMISSION_VIEW = 'view';
    public const PERMISSION_EDIT = 'edit';
    public const PERMISSION_MANAGE = 'manage';

    // Status Types
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'wishlist_id',
        'customer_id',
        'permission',
        'invited_email',
        'status',
        'invitation_token',
        'invited_at',
        'accepted_at',
        'last_activity_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Boot Method
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($collaborator) {
            if (empty($collaborator->invitation_token)) {
                $collaborator->invitation_token = Str::random(32);
            }

            if (empty($collaborator->invited_at)) {
                $collaborator->invited_at = now();
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function canView(): bool
    {
        return $this->isAccepted();
    }

    public function canEdit(): bool
    {
        return $this->isAccepted() && in_array($this->permission, [self::PERMISSION_EDIT, self::PERMISSION_MANAGE]);
    }

    public function canManage(): bool
    {
        return $this->isAccepted() && $this->permission === self::PERMISSION_MANAGE;
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Accept the invitation.
     */
    public function accept(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Decline the invitation.
     */
    public function decline(): void
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
        ]);
    }

    /**
     * Update permission level.
     */
    public function updatePermission(string $permission): void
    {
        $this->update(['permission' => $permission]);
    }

    /**
     * Record activity.
     */
    public function recordActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeForWishlist($query, int $wishlistId)
    {
        return $query->where('wishlist_id', $wishlistId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', self::STATUS_DECLINED);
    }

    public function scopeByInvitationToken($query, string $token)
    {
        return $query->where('invitation_token', $token);
    }
}
