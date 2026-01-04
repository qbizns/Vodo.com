<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateLink extends Model
{
    use HasFactory;

    protected $table = 'commerce_affiliate_links';

    protected $fillable = [
        'affiliate_id',
        'url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'clicks',
        'conversions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'link_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function incrementClick(): void
    {
        $this->increment('clicks');
        $this->affiliate->incrementClicks();
    }

    public function incrementConversion(): void
    {
        $this->increment('conversions');
        $this->affiliate->incrementConversions();
    }

    public function getConversionRate(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return ($this->conversions / $this->clicks) * 100;
    }
}
