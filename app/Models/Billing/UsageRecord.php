<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    protected $table = 'usage_records';

    protected $fillable = [
        'subscription_id',
        'listing_id',
        'tenant_id',
        'metric',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'period_start',
        'period_end',
        'invoiced',
        'invoice_item_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:6',
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'invoiced' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSubscription::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function calculateAmount(): float
    {
        if ($this->unit_price === null) {
            return 0;
        }

        return round($this->quantity * $this->unit_price, 2);
    }

    public function markAsInvoiced(int $invoiceItemId): void
    {
        $this->update([
            'invoiced' => true,
            'invoice_item_id' => $invoiceItemId,
            'amount' => $this->calculateAmount(),
        ]);
    }

    public function scopeBySubscription($query, int $subscriptionId)
    {
        return $query->where('subscription_id', $subscriptionId);
    }

    public function scopeByMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }

    public function scopeUninvoiced($query)
    {
        return $query->where('invoiced', false);
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('period_start', '>=', $start)
            ->where('period_end', '<=', $end);
    }
}
