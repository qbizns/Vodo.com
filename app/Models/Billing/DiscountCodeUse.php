<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCodeUse extends Model
{
    protected $table = 'discount_code_uses';

    protected $fillable = [
        'discount_code_id',
        'invoice_id',
        'tenant_id',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
        ];
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
