<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

class TaxExemption extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_tax_exemptions';

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'type',
        'entity_id',
        'certificate_number',
        'valid_from',
        'valid_until',
        'country_code',
        'state_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now()->toDateString();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
        });
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForEntity($query, string $type, int $entityId)
    {
        return $query->where('type', $type)->where('entity_id', $entityId);
    }

    public function scopeForLocation($query, string $countryCode, ?string $stateCode = null)
    {
        return $query->where(function ($q) use ($countryCode, $stateCode) {
            $q->whereNull('country_code')
                ->orWhere(function ($sq) use ($countryCode, $stateCode) {
                    $sq->where('country_code', $countryCode);

                    if ($stateCode) {
                        $sq->where(function ($ssq) use ($stateCode) {
                            $ssq->whereNull('state_code')->orWhere('state_code', $stateCode);
                        });
                    }
                });
        });
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    public function isValidNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->toDateString();

        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }

        if ($this->valid_until && $this->valid_until < $now) {
            return false;
        }

        return true;
    }

    public function appliesToLocation(string $countryCode, ?string $stateCode = null): bool
    {
        // If no location specified, applies to all
        if ($this->country_code === null) {
            return true;
        }

        // Check country
        if ($this->country_code !== $countryCode) {
            return false;
        }

        // If state specified in exemption, check it
        if ($this->state_code !== null && $this->state_code !== $stateCode) {
            return false;
        }

        return true;
    }

    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    public function isExpired(): bool
    {
        if ($this->valid_until === null) {
            return false;
        }

        return $this->valid_until < now()->toDateString();
    }
}
