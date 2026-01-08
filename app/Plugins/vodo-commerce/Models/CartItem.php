<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Database\Factories\CartItemFactory;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'commerce_cart_items';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CartItemFactory
    {
        return CartItemFactory::new();
    }

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'options',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'options' => 'array',
            'meta' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getLineTotal(): float
    {
        return $this->unit_price * $this->quantity;
    }

    public function getCurrentPrice(): float
    {
        if ($this->variant) {
            return (float) $this->variant->getEffectivePrice();
        }

        return (float) $this->product->price;
    }

    public function updatePrice(): void
    {
        $this->update(['unit_price' => $this->getCurrentPrice()]);
    }

    public function isInStock(): bool
    {
        if ($this->variant) {
            return $this->variant->isInStock() && $this->variant->stock_quantity >= $this->quantity;
        }

        return $this->product->isInStock() && $this->product->stock_quantity >= $this->quantity;
    }

    public function getAvailableQuantity(): int
    {
        if ($this->variant) {
            return $this->variant->stock_quantity;
        }

        return $this->product->stock_quantity;
    }

    public function getName(): string
    {
        $name = $this->product->name;

        if ($this->variant && !empty($this->variant->options)) {
            $name .= ' - ' . $this->variant->getOptionString();
        }

        return $name;
    }

    public function getSku(): ?string
    {
        if ($this->variant && $this->variant->sku) {
            return $this->variant->sku;
        }

        return $this->product->sku;
    }

    public function getImage(): ?string
    {
        if ($this->variant && $this->variant->image) {
            return $this->variant->image;
        }

        return $this->product->getPrimaryImage();
    }
}
