<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use App\Traits\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderNote extends Model
{
    use BelongsToStore;

    protected $table = 'commerce_order_notes';

    protected $fillable = [
        'store_id',
        'order_id',
        'author_type',
        'author_id',
        'content',
        'is_customer_visible',
    ];

    protected $casts = [
        'is_customer_visible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that owns the note.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the author of the note (polymorphic).
     */
    public function author(): MorphTo
    {
        return $this->morphTo('author');
    }

    /**
     * Scope: Get only customer-visible notes.
     */
    public function scopeCustomerVisible($query)
    {
        return $query->where('is_customer_visible', true);
    }

    /**
     * Scope: Get only admin-only notes.
     */
    public function scopeAdminOnly($query)
    {
        return $query->where('is_customer_visible', false);
    }

    /**
     * Scope: Filter by author type.
     */
    public function scopeByAuthorType($query, string $authorType)
    {
        return $query->where('author_type', $authorType);
    }

    /**
     * Check if note is visible to customer.
     */
    public function isVisibleToCustomer(): bool
    {
        return $this->is_customer_visible;
    }

    /**
     * Get author name for display.
     */
    public function getAuthorName(): string
    {
        if ($this->author_type === 'system') {
            return 'System';
        }

        if ($this->author && method_exists($this->author, 'getName')) {
            return $this->author->getName();
        }

        if ($this->author && isset($this->author->name)) {
            return $this->author->name;
        }

        return ucfirst($this->author_type);
    }
}
