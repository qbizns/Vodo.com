<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use VodoCommerce\Database\Factories\WebhookSubscriptionFactory;
use VodoCommerce\Traits\BelongsToStore;

class WebhookSubscription extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_webhook_subscriptions';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WebhookSubscriptionFactory
    {
        return WebhookSubscriptionFactory::new();
    }

    protected $fillable = [
        'store_id',
        'name',
        'url',
        'description',
        'events',
        'secret',
        'is_active',
        'timeout_seconds',
        'max_retry_attempts',
        'retry_delay_seconds',
        'custom_headers',
        'total_deliveries',
        'successful_deliveries',
        'failed_deliveries',
        'last_delivery_at',
        'last_success_at',
        'last_failure_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'custom_headers' => 'array',
            'meta' => 'array',
            'is_active' => 'boolean',
            'timeout_seconds' => 'integer',
            'max_retry_attempts' => 'integer',
            'retry_delay_seconds' => 'integer',
            'total_deliveries' => 'integer',
            'successful_deliveries' => 'integer',
            'failed_deliveries' => 'integer',
            'last_delivery_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Boot Method
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (empty($subscription->secret)) {
                $subscription->secret = 'whsec_' . Str::random(40);
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function events(): HasMany
    {
        return $this->hasMany(WebhookEvent::class, 'subscription_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class, 'subscription_id');
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isSubscribedTo(string $eventType): bool
    {
        return in_array($eventType, $this->events ?? [], true);
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Activate the webhook subscription.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the webhook subscription.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Regenerate the webhook secret.
     */
    public function regenerateSecret(): string
    {
        $newSecret = 'whsec_' . Str::random(40);
        $this->update(['secret' => $newSecret]);

        return $newSecret;
    }

    /**
     * Update delivery statistics.
     */
    public function updateDeliveryStats(bool $success): void
    {
        $this->increment('total_deliveries');

        if ($success) {
            $this->increment('successful_deliveries');
            $this->update([
                'last_success_at' => now(),
                'last_delivery_at' => now(),
            ]);
        } else {
            $this->increment('failed_deliveries');
            $this->update([
                'last_failure_at' => now(),
                'last_delivery_at' => now(),
            ]);
        }
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRate(): float
    {
        if ($this->total_deliveries === 0) {
            return 0;
        }

        return round(($this->successful_deliveries / $this->total_deliveries) * 100, 2);
    }

    /**
     * Get failure rate percentage.
     */
    public function getFailureRate(): float
    {
        if ($this->total_deliveries === 0) {
            return 0;
        }

        return round(($this->failed_deliveries / $this->total_deliveries) * 100, 2);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeSubscribedToEvent($query, string $eventType)
    {
        return $query->whereJsonContains('events', $eventType);
    }

    public function scopeWithHighFailureRate($query, float $threshold = 50)
    {
        return $query->whereRaw('(failed_deliveries / total_deliveries * 100) >= ?', [$threshold])
            ->where('total_deliveries', '>', 0);
    }
}
