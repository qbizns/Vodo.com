<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $table = 'marketplace_invoice_items';

    protected $fillable = [
        'invoice_id',
        'listing_id',
        'description',
        'type',
        'quantity',
        'unit_price',
        'amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class);
    }
}
