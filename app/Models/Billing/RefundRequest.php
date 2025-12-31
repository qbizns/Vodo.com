<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    protected $table = 'refund_requests';

    protected $fillable = [
        'uuid',
        'transaction_id',
        'invoice_id',
        'tenant_id',
        'listing_id',
        'status',
        'reason',
        'description',
        'requested_amount',
        'approved_amount',
        'gateway_refund_id',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function approve(int $reviewerId, float $amount, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_amount' => $amount,
            'reviewed_by' => $reviewerId,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(int $reviewerId, string $notes): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    public function markAsProcessed(string $gatewayRefundId): void
    {
        $this->update([
            'status' => 'processed',
            'gateway_refund_id' => $gatewayRefundId,
            'processed_at' => now(),
        ]);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
