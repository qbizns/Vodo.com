<?php

namespace Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * Invoice Model
 */
class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'subscription_invoices';

    protected $fillable = [
        'subscription_id',
        'user_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'description',
        'notes',
        'due_date',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'tax' => 0,
        'discount' => 0,
        'currency' => 'USD',
    ];

    // Auto-generate invoice number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = config('subscriptions.invoice_prefix', 'INV-');
        $length = config('subscriptions.invoice_number_length', 8);
        $number = str_pad((string) (static::withTrashed()->count() + 1), $length, '0', STR_PAD_LEFT);
        return $prefix . $number;
    }

    // Relationships
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date && $this->due_date->isPast();
    }

    public function getFormattedTotalAttribute(): string
    {
        $symbol = config('subscriptions.currency_symbol', '$');
        return $symbol . number_format($this->total, 2);
    }

    // Methods
    public function markAsPaid(): self
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $this;
    }

    public function calculateTotal(): float
    {
        return ($this->subtotal + $this->tax) - $this->discount;
    }
}

