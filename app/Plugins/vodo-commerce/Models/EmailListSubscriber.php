<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailListSubscriber extends Model
{
    use HasFactory;

    protected $table = 'commerce_email_list_subscribers';

    protected $fillable = [
        'list_id',
        'customer_id',
        'email',
        'first_name',
        'last_name',
        'status',
        'subscribed_at',
        'confirmed_at',
        'unsubscribed_at',
        'unsubscribe_reason',
        'unsubscribe_ip',
        'source',
        'signup_ip',
        'signup_user_agent',
        'emails_sent',
        'emails_opened',
        'emails_clicked',
        'emails_bounced',
        'open_rate',
        'click_rate',
        'last_opened_at',
        'last_clicked_at',
        'preferences',
        'custom_fields',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'last_opened_at' => 'datetime',
            'last_clicked_at' => 'datetime',
            'open_rate' => 'decimal:2',
            'click_rate' => 'decimal:2',
            'preferences' => 'array',
            'custom_fields' => 'array',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function list(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeSubscribed(Builder $query): void
    {
        $query->where('status', 'subscribed');
    }

    public function scopeUnsubscribed(Builder $query): void
    {
        $query->where('status', 'unsubscribed');
    }

    public function scopeBounced(Builder $query): void
    {
        $query->where('status', 'bounced');
    }

    public function scopeComplained(Builder $query): void
    {
        $query->where('status', 'complained');
    }

    public function scopeCleaned(Builder $query): void
    {
        $query->where('status', 'cleaned');
    }

    public function scopeForList(Builder $query, int $listId): void
    {
        $query->where('list_id', $listId);
    }

    public function scopeByEmail(Builder $query, string $email): void
    {
        $query->where('email', $email);
    }

    public function scopeBySource(Builder $query, string $source): void
    {
        $query->where('source', $source);
    }

    public function scopeEngaged(Builder $query, int $days = 90): void
    {
        $query->where('last_opened_at', '>=', now()->subDays($days));
    }

    public function scopeInactive(Builder $query, int $days = 180): void
    {
        $query->where(function ($q) use ($days) {
            $q->whereNull('last_opened_at')
                ->orWhere('last_opened_at', '<', now()->subDays($days));
        });
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function confirm(): bool
    {
        return $this->update([
            'status' => 'subscribed',
            'confirmed_at' => now(),
        ]);
    }

    public function unsubscribe(?string $reason = null, ?string $ip = null): bool
    {
        return $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
            'unsubscribe_reason' => $reason,
            'unsubscribe_ip' => $ip,
        ]);
    }

    public function markAsBounced(): bool
    {
        return $this->update([
            'status' => 'bounced',
        ]);
    }

    public function markAsComplained(): bool
    {
        return $this->update([
            'status' => 'complained',
        ]);
    }

    public function markAsCleaned(): bool
    {
        return $this->update([
            'status' => 'cleaned',
        ]);
    }

    public function resubscribe(): bool
    {
        return $this->update([
            'status' => 'subscribed',
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
            'unsubscribe_reason' => null,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSubscribed(): bool
    {
        return $this->status === 'subscribed';
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === 'unsubscribed';
    }

    public function isBounced(): bool
    {
        return $this->status === 'bounced';
    }

    public function isComplained(): bool
    {
        return $this->status === 'complained';
    }

    public function isCleaned(): bool
    {
        return $this->status === 'cleaned';
    }

    public function recordEmailSent(): void
    {
        $this->increment('emails_sent');
        $this->calculateEngagementRates();
    }

    public function recordEmailOpened(): void
    {
        $this->increment('emails_opened');
        $this->update(['last_opened_at' => now()]);
        $this->calculateEngagementRates();
    }

    public function recordEmailClicked(): void
    {
        $this->increment('emails_clicked');
        $this->update(['last_clicked_at' => now()]);
        $this->calculateEngagementRates();
    }

    public function recordEmailBounced(): void
    {
        $this->increment('emails_bounced');
        $this->calculateEngagementRates();
    }

    protected function calculateEngagementRates(): void
    {
        if ($this->emails_sent === 0) {
            return;
        }

        $openRate = ($this->emails_opened / $this->emails_sent) * 100;
        $clickRate = ($this->emails_clicked / $this->emails_sent) * 100;

        $this->update([
            'open_rate' => round($openRate, 2),
            'click_rate' => round($clickRate, 2),
        ]);
    }

    public function isEngaged(int $days = 90): bool
    {
        if (!$this->last_opened_at) {
            return false;
        }

        return $this->last_opened_at->isAfter(now()->subDays($days));
    }

    public function isInactive(int $days = 180): bool
    {
        return !$this->isEngaged($days);
    }

    public function getEngagementScore(): float
    {
        // Engagement score based on open rate, click rate, and recency
        $openWeight = 0.4;
        $clickWeight = 0.4;
        $recencyWeight = 0.2;

        $recencyScore = 0;
        if ($this->last_opened_at) {
            $daysSinceOpen = now()->diffInDays($this->last_opened_at);
            $recencyScore = max(0, 100 - ($daysSinceOpen / 3.65)); // Decay over ~1 year
        }

        return round(
            ($this->open_rate * $openWeight) +
            ($this->click_rate * $clickWeight) +
            ($recencyScore * $recencyWeight),
            2
        );
    }

    public function getFullName(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        return implode(' ', $parts) ?: $this->email;
    }

    public function updatePreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->update(['preferences' => $preferences]);
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->preferences[$key] ?? $default;
    }

    public function setCustomField(string $key, mixed $value): void
    {
        $customFields = $this->custom_fields ?? [];
        $customFields[$key] = $value;
        $this->update(['custom_fields' => $customFields]);
    }

    public function getCustomField(string $key, mixed $default = null): mixed
    {
        return $this->custom_fields[$key] ?? $default;
    }
}
