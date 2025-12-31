<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'uuid',
        'invoice_id',
        'tenant_id',
        'payment_method_id',
        'gateway',
        'gateway_transaction_id',
        'gateway_charge_id',
        'type',
        'status',
        'currency',
        'amount',
        'fee',
        'net_amount',
        'failure_code',
        'failure_message',
        'gateway_response',
        'metadata',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'gateway_response' => 'array',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'gateway_response',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function revenueSplits(): HasMany
    {
        return $this->hasMany(RevenueSplit::class, 'transaction_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Succeeded;
    }

    public function canRefund(): bool
    {
        return $this->status->canRefund() && $this->type === 'charge';
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', PaymentStatus::Succeeded);
    }

    public function scopeCharges($query)
    {
        return $query->where('type', 'charge');
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }
}
