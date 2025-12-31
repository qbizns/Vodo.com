<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Marketplace\MarketplaceSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    protected $table = 'marketplace_invoices';

    protected $fillable = [
        'uuid',
        'invoice_number',
        'tenant_id',
        'subscription_id',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'discount_amount',
        'total',
        'billing_period',
        'period_start',
        'period_end',
        'due_date',
        'paid_at',
        'billing_address',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'billing_address' => 'array',
            'metadata' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSubscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function successfulTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class)
            ->where('status', 'succeeded')
            ->latest();
    }

    public function discountUses(): HasMany
    {
        return $this->hasMany(DiscountCodeUse::class);
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isOverdue(): bool
    {
        return $this->status->isPayable() && $this->due_date->isPast();
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function void(): void
    {
        if (!$this->status->canVoid()) {
            throw new \InvalidArgumentException('Cannot void this invoice');
        }

        $this->update(['status' => InvoiceStatus::Void]);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', InvoiceStatus::Pending);
    }

    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::Paid);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', [InvoiceStatus::Pending, InvoiceStatus::Failed])
            ->where('due_date', '<', now());
    }
}
