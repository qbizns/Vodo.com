<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VendorPayout extends Model
{
    use HasFactory;

    protected $table = 'commerce_vendor_payouts';

    protected $fillable = [
        'vendor_id',
        'payout_number',
        'period_start',
        'period_end',
        'gross_amount',
        'platform_fees',
        'adjustments',
        'net_amount',
        'currency',
        'commission_count',
        'order_count',
        'payout_method',
        'payout_details',
        'status',
        'processed_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'transaction_id',
        'failure_reason',
        'cancellation_reason',
        'notes',
        'attachments',
        'processed_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'gross_amount' => 'decimal:2',
            'platform_fees' => 'decimal:2',
            'adjustments' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'payout_details' => 'array',
            'attachments' => 'array',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(VendorCommission::class, 'payout_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeProcessing(Builder $query): void
    {
        $query->where('status', 'processing');
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('status', 'failed');
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', 'cancelled');
    }

    public function scopeForVendor(Builder $query, int $vendorId): void
    {
        $query->where('vendor_id', $vendorId);
    }

    public function scopeByPeriod(Builder $query, string $startDate, string $endDate): void
    {
        $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    public function scopeByMethod(Builder $query, string $method): void
    {
        $query->where('payout_method', $method);
    }

    public function scopeByNumber(Builder $query, string $number): void
    {
        $query->where('payout_number', $number);
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function markAsProcessing(int $processedBy = null): bool
    {
        return $this->update([
            'status' => 'processing',
            'processed_at' => now(),
            'processed_by' => $processedBy,
        ]);
    }

    public function markAsCompleted(string $transactionId = null): bool
    {
        // Update payout status
        $result = $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'transaction_id' => $transactionId,
        ]);

        // Mark all commissions as paid
        if ($result) {
            $this->commissions()->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return $result;
    }

    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function cancel(string $reason): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function retry(): bool
    {
        return $this->update([
            'status' => 'pending',
            'processed_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function addAttachment(string $filename, string $url): void
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = [
            'filename' => $filename,
            'url' => $url,
            'uploaded_at' => now()->toDateTimeString(),
        ];

        $this->update(['attachments' => $attachments]);
    }

    public function calculateNetAmount(): float
    {
        return (float) ($this->gross_amount - $this->platform_fees + $this->adjustments);
    }

    public function recalculateTotals(): void
    {
        $commissions = $this->commissions;

        $grossAmount = $commissions->sum('vendor_earnings');
        $commissionCount = $commissions->count();
        $orderCount = $commissions->pluck('order_id')->unique()->count();

        $this->update([
            'gross_amount' => $grossAmount,
            'commission_count' => $commissionCount,
            'order_count' => $orderCount,
            'net_amount' => $this->calculateNetAmount(),
        ]);
    }

    public static function generatePayoutNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');

        // Find the last payout number for this month
        $lastPayout = static::where('payout_number', 'like', "PAYOUT-{$year}-{$month}-%")
            ->orderByDesc('payout_number')
            ->first();

        if ($lastPayout) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastPayout->payout_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('PAYOUT-%s-%s-%03d', $year, $month, $sequence);
    }

    // =========================================================================
    // EVENTS
    // =========================================================================

    protected static function booted(): void
    {
        static::creating(function (VendorPayout $payout) {
            if (empty($payout->payout_number)) {
                $payout->payout_number = static::generatePayoutNumber();
            }
        });
    }
}
