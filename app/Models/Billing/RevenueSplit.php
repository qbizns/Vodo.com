<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueSplit extends Model
{
    protected $table = 'revenue_splits';

    protected $fillable = [
        'transaction_id',
        'listing_id',
        'developer_id',
        'currency',
        'gross_amount',
        'platform_fee',
        'platform_fee_rate',
        'gateway_fee',
        'tax_amount',
        'developer_amount',
        'status',
        'payout_id',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'platform_fee_rate' => 'decimal:4',
            'gateway_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'developer_amount' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(DeveloperPayout::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function scopeByDeveloper($query, int $developerId)
    {
        return $query->where('developer_id', $developerId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'available']);
    }
}
