<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeveloperPayout extends Model
{
    protected $table = 'developer_payouts';

    protected $fillable = [
        'uuid',
        'payout_number',
        'developer_id',
        'payment_account_id',
        'gateway',
        'gateway_payout_id',
        'status',
        'currency',
        'gross_amount',
        'fees',
        'net_amount',
        'items_count',
        'period_start',
        'period_end',
        'failure_reason',
        'initiated_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'gross_amount' => 'decimal:2',
            'fees' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'items_count' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(DeveloperPaymentAccount::class, 'payment_account_id');
    }

    public function revenueSplits(): HasMany
    {
        return $this->hasMany(RevenueSplit::class, 'payout_id');
    }

    public function isPending(): bool
    {
        return $this->status === PayoutStatus::Pending;
    }

    public function isProcessing(): bool
    {
        return $this->status === PayoutStatus::Processing;
    }

    public function isCompleted(): bool
    {
        return $this->status === PayoutStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === PayoutStatus::Failed;
    }

    public function canRetry(): bool
    {
        return $this->status === PayoutStatus::Failed;
    }

    public function scopeByDeveloper($query, int $developerId)
    {
        return $query->where('developer_id', $developerId);
    }

    public function scopePending($query)
    {
        return $query->where('status', PayoutStatus::Pending);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PayoutStatus::Completed);
    }
}
