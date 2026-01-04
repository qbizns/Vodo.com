<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    use HasFactory;

    protected $table = 'commerce_affiliate_commissions';

    protected $fillable = [
        'affiliate_id',
        'order_id',
        'link_id',
        'order_amount',
        'commission_amount',
        'commission_rate',
        'status',
        'approved_at',
        'paid_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'order_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(AffiliateLink::class, 'link_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function approve(): bool
    {
        $this->status = 'approved';
        $this->approved_at = now();
        return $this->save();
    }

    public function markAsPaid(): bool
    {
        $this->status = 'paid';
        $this->paid_at = now();

        $this->affiliate->pending_balance -= $this->commission_amount;
        $this->affiliate->paid_balance += $this->commission_amount;
        $this->affiliate->save();

        return $this->save();
    }

    public function reject(): bool
    {
        $this->status = 'rejected';

        $this->affiliate->pending_balance -= $this->commission_amount;
        $this->affiliate->save();

        return $this->save();
    }
}
