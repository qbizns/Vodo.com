<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsage extends Model
{
    use HasFactory;

    protected $table = 'commerce_subscription_usage';

    protected $fillable = [
        'subscription_id',
        'subscription_item_id',
        'metric',
        'quantity',
        'usage_at',
        'period_start',
        'period_end',
        'price_per_unit',
        'amount',
        'is_billed',
        'invoice_id',
        'action',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'usage_at' => 'datetime',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'price_per_unit' => 'decimal:4',
            'amount' => 'decimal:2',
            'is_billed' => 'boolean',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function subscriptionItem(): BelongsTo
    {
        return $this->belongsTo(SubscriptionItem::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Mark usage as billed.
     */
    public function markAsBilled(int $invoiceId): void
    {
        $this->update([
            'is_billed' => true,
            'invoice_id' => $invoiceId,
        ]);
    }

    /**
     * Calculate amount based on quantity and price per unit.
     */
    public function calculateAmount(): float
    {
        return round($this->quantity * (float) $this->price_per_unit, 2);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeUnbilled($query)
    {
        return $query->where('is_billed', false);
    }

    public function scopeBilled($query)
    {
        return $query->where('is_billed', true);
    }

    public function scopeByMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }

    public function scopeInPeriod($query, \Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return $query->whereBetween('usage_at', [$start, $end]);
    }

    public function scopeForBillingPeriod($query, \Carbon\Carbon $periodStart, \Carbon\Carbon $periodEnd)
    {
        return $query->where('period_start', $periodStart)
            ->where('period_end', $periodEnd);
    }
}
