<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PluginLicense extends Model
{
    protected $fillable = [
        'plugin_id', 'license_key', 'license_type', 'status',
        'activation_id', 'activation_email', 'instance_id',
        'activations_used', 'activations_limit', 'purchased_at',
        'expires_at', 'last_verified_at', 'support_active',
        'support_expires_at', 'updates_active', 'updates_expire_at',
        'features', 'meta',
    ];

    protected $casts = [
        'activations_used' => 'integer',
        'activations_limit' => 'integer',
        'support_active' => 'boolean',
        'updates_active' => 'boolean',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'support_expires_at' => 'datetime',
        'updates_expire_at' => 'datetime',
        'features' => 'array',
        'meta' => 'array',
    ];

    protected $hidden = ['license_key'];

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_INVALID = 'invalid';

    // License types
    public const TYPE_STANDARD = 'standard';
    public const TYPE_EXTENDED = 'extended';
    public const TYPE_LIFETIME = 'lifetime';
    public const TYPE_SUBSCRIPTION = 'subscription';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(InstalledPlugin::class, 'plugin_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now());
    }

    // =========================================================================
    // Validation Methods
    // =========================================================================

    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isLifetime(): bool
    {
        return $this->license_type === self::TYPE_LIFETIME;
    }

    public function isSubscription(): bool
    {
        return $this->license_type === self::TYPE_SUBSCRIPTION;
    }

    public function hasSupport(): bool
    {
        if (!$this->support_active) {
            return false;
        }

        if ($this->support_expires_at && $this->support_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasUpdates(): bool
    {
        if (!$this->updates_active) {
            return false;
        }

        if ($this->updates_expire_at && $this->updates_expire_at->isPast()) {
            return false;
        }

        return true;
    }

    public function canActivate(): bool
    {
        if (!$this->activations_limit) {
            return true;
        }

        return $this->activations_used < $this->activations_limit;
    }

    // =========================================================================
    // Feature Methods
    // =========================================================================

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getFeatures(): array
    {
        return $this->features ?? [];
    }

    // =========================================================================
    // Lifecycle Methods
    // =========================================================================

    public function activate(string $email = null): bool
    {
        if (!$this->canActivate()) {
            return false;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->activation_email = $email;
        $this->activation_id = Str::uuid()->toString();
        $this->instance_id = $this->instance_id ?? $this->generateInstanceId();
        $this->activations_used++;
        $this->last_verified_at = now();

        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->status = self::STATUS_INACTIVE ?? self::STATUS_SUSPENDED;
        $this->activation_id = null;

        if ($this->activations_used > 0) {
            $this->activations_used--;
        }

        return $this->save();
    }

    public function suspend(string $reason = null): bool
    {
        $this->status = self::STATUS_SUSPENDED;
        
        if ($reason) {
            $meta = $this->meta ?? [];
            $meta['suspend_reason'] = $reason;
            $meta['suspended_at'] = now()->toIso8601String();
            $this->meta = $meta;
        }

        return $this->save();
    }

    public function markExpired(): bool
    {
        $this->status = self::STATUS_EXPIRED;
        return $this->save();
    }

    public function markVerified(): bool
    {
        $this->last_verified_at = now();
        return $this->save();
    }

    protected function generateInstanceId(): string
    {
        $data = [
            config('app.url'),
            config('app.key'),
            $this->plugin_id,
            $this->license_key,
        ];

        return hash('sha256', implode('|', $data));
    }

    // =========================================================================
    // Display Methods
    // =========================================================================

    public function getMaskedKey(): string
    {
        $key = $this->license_key;
        $length = strlen($key);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    public static function findByKey(string $key): ?self
    {
        return static::where('license_key', $key)->first();
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_INVALID => 'Invalid',
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_STANDARD => 'Standard',
            self::TYPE_EXTENDED => 'Extended',
            self::TYPE_LIFETIME => 'Lifetime',
            self::TYPE_SUBSCRIPTION => 'Subscription',
        ];
    }
}
