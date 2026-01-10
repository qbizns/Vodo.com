<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use VodoCommerce\Traits\BelongsToStore;

class SubscriptionInvoice extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_subscription_invoices';

    // Invoice Statuses
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_VOID = 'void';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'store_id',
        'subscription_id',
        'customer_id',
        'invoice_number',
        'status',
        'period_start',
        'period_end',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'amount_paid',
        'amount_due',
        'amount_refunded',
        'currency',
        'is_proration',
        'proration_amount',
        'usage_charges',
        'usage_details',
        'transaction_id',
        'paid_at',
        'due_date',
        'attempt_count',
        'next_retry_at',
        'last_error',
        'pdf_url',
        'line_items',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'amount_refunded' => 'decimal:2',
            'is_proration' => 'boolean',
            'proration_amount' => 'decimal:2',
            'usage_charges' => 'decimal:2',
            'usage_details' => 'array',
            'paid_at' => 'datetime',
            'due_date' => 'datetime',
            'attempt_count' => 'integer',
            'next_retry_at' => 'datetime',
            'line_items' => 'array',
            'meta' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SubscriptionInvoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->store_id);
            }
        });
    }

    // =========================================================================
    // Invoice Number Generation
    // =========================================================================

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(int $storeId, int $maxRetries = 5): string
    {
        $prefix = 'INV';
        $timestamp = now()->format('ymd');

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $random = strtoupper(Str::random(8));
            $invoiceNumber = "{$prefix}-{$timestamp}-{$random}";

            $exists = static::where('store_id', $storeId)
                ->where('invoice_number', $invoiceNumber)
                ->exists();

            if (!$exists) {
                return $invoiceNumber;
            }
        }

        throw new \RuntimeException(
            "Unable to generate unique invoice number after {$maxRetries} attempts"
        );
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    // =========================================================================
    // Status Checks
    // =========================================================================

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date && $this->due_date->isPast();
    }

    // =========================================================================
    // Payment Methods
    // =========================================================================

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(?int $transactionId = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'amount_paid' => $this->total,
            'amount_due' => 0,
            'paid_at' => now(),
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Mark invoice as failed.
     */
    public function markAsFailed(string $error, ?\Carbon\Carbon $nextRetryAt = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'attempt_count' => $this->attempt_count + 1,
            'last_error' => $error,
            'next_retry_at' => $nextRetryAt,
        ]);
    }

    /**
     * Mark invoice as void.
     */
    public function markAsVoid(): void
    {
        $this->update([
            'status' => self::STATUS_VOID,
        ]);
    }

    /**
     * Process refund.
     */
    public function refund(float $amount): void
    {
        if (!$this->isPaid()) {
            throw new \RuntimeException('Cannot refund an unpaid invoice');
        }

        $newRefundedAmount = (float) $this->amount_refunded + $amount;

        if ($newRefundedAmount > (float) $this->amount_paid) {
            throw new \RuntimeException('Refund amount exceeds paid amount');
        }

        $updateData = [
            'amount_refunded' => $newRefundedAmount,
        ];

        // If fully refunded, update status
        if ($newRefundedAmount >= (float) $this->amount_paid) {
            $updateData['status'] = self::STATUS_REFUNDED;
        }

        $this->update($updateData);
    }

    // =========================================================================
    // Calculation Methods
    // =========================================================================

    /**
     * Calculate total from line items.
     */
    public function calculateTotal(): void
    {
        $lineItems = $this->line_items ?? [];
        $subtotal = 0;

        foreach ($lineItems as $item) {
            $subtotal += $item['total'] ?? 0;
        }

        // Add usage charges
        $subtotal += (float) $this->usage_charges;

        // Add proration if applicable
        if ($this->is_proration && $this->proration_amount) {
            $subtotal += (float) $this->proration_amount;
        }

        $total = $subtotal - (float) $this->discount_total + (float) $this->tax_total;

        $this->update([
            'subtotal' => $subtotal,
            'total' => $total,
            'amount_due' => $total - (float) $this->amount_paid,
        ]);
    }

    /**
     * Get next retry date based on attempt count (exponential backoff).
     */
    public function calculateNextRetryDate(): ?\Carbon\Carbon
    {
        if ($this->attempt_count >= 4) {
            return null; // Stop retrying after 4 attempts
        }

        // Exponential backoff: 1 day, 3 days, 7 days
        $daysToWait = match ($this->attempt_count) {
            0 => 1,
            1 => 3,
            2 => 7,
            default => null,
        };

        if ($daysToWait === null) {
            return null;
        }

        return now()->addDays($daysToWait);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }

    public function scopeDueForRetry($query)
    {
        return $query->where('status', self::STATUS_FAILED)
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now());
    }

    public function scopeByPeriod($query, \Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }
}
