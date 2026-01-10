<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailSend extends Model
{
    use HasFactory;

    protected $table = 'commerce_email_sends';

    protected $fillable = [
        'campaign_id',
        'template_id',
        'customer_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'preview_text',
        'from_name',
        'from_email',
        'reply_to',
        'type',
        'status',
        'queued_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'bounced_at',
        'message_id',
        'provider',
        'provider_message_id',
        'error_message',
        'bounce_type',
        'bounce_reason',
        'is_opened',
        'is_clicked',
        'open_count',
        'click_count',
        'first_opened_at',
        'last_opened_at',
        'first_clicked_at',
        'last_clicked_at',
        'ab_test_variant',
        'sendable_type',
        'sendable_id',
        'has_conversion',
        'conversion_revenue',
        'converted_at',
        'utm_parameters',
        'custom_data',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'bounced_at' => 'datetime',
            'is_opened' => 'boolean',
            'is_clicked' => 'boolean',
            'first_opened_at' => 'datetime',
            'last_opened_at' => 'datetime',
            'first_clicked_at' => 'datetime',
            'last_clicked_at' => 'datetime',
            'has_conversion' => 'boolean',
            'conversion_revenue' => 'decimal:2',
            'converted_at' => 'datetime',
            'utm_parameters' => 'array',
            'custom_data' => 'array',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sendable(): MorphTo
    {
        return $this->morphTo();
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class, 'send_id');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeQueued(Builder $query): void
    {
        $query->where('status', 'queued');
    }

    public function scopeSent(Builder $query): void
    {
        $query->where('status', 'sent');
    }

    public function scopeDelivered(Builder $query): void
    {
        $query->where('status', 'delivered');
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('status', 'failed');
    }

    public function scopeBounced(Builder $query): void
    {
        $query->where('status', 'bounced');
    }

    public function scopeForCampaign(Builder $query, int $campaignId): void
    {
        $query->where('campaign_id', $campaignId);
    }

    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeOpened(Builder $query): void
    {
        $query->where('is_opened', true);
    }

    public function scopeClicked(Builder $query): void
    {
        $query->where('is_clicked', true);
    }

    public function scopeConverted(Builder $query): void
    {
        $query->where('has_conversion', true);
    }

    public function scopeByVariant(Builder $query, string $variant): void
    {
        $query->where('ab_test_variant', $variant);
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function markAsQueued(): bool
    {
        return $this->update([
            'status' => 'queued',
            'queued_at' => now(),
        ]);
    }

    public function markAsSent(string $messageId, string $provider): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'message_id' => $messageId,
            'provider' => $provider,
        ]);
    }

    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsBounced(string $bounceType, ?string $bounceReason = null): bool
    {
        return $this->update([
            'status' => 'bounced',
            'bounced_at' => now(),
            'bounce_type' => $bounceType,
            'bounce_reason' => $bounceReason,
        ]);
    }

    public function recordOpen(): void
    {
        if (!$this->is_opened) {
            $this->update([
                'is_opened' => true,
                'first_opened_at' => now(),
            ]);
        }

        $this->increment('open_count');
        $this->update(['last_opened_at' => now()]);
    }

    public function recordClick(?string $linkUrl = null): void
    {
        if (!$this->is_clicked) {
            $this->update([
                'is_clicked' => true,
                'first_clicked_at' => now(),
            ]);
        }

        $this->increment('click_count');
        $this->update(['last_clicked_at' => now()]);
    }

    public function recordConversion(float $revenue): void
    {
        $this->update([
            'has_conversion' => true,
            'conversion_revenue' => $revenue,
            'converted_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isBounced(): bool
    {
        return $this->status === 'bounced';
    }

    public function isOpened(): bool
    {
        return $this->is_opened === true;
    }

    public function isClicked(): bool
    {
        return $this->is_clicked === true;
    }

    public function hasConverted(): bool
    {
        return $this->has_conversion === true;
    }

    public function getTimeSinceDelivery(): ?int
    {
        if (!$this->delivered_at) {
            return null;
        }

        return now()->diffInMinutes($this->delivered_at);
    }

    public function getTimeToFirstOpen(): ?int
    {
        if (!$this->delivered_at || !$this->first_opened_at) {
            return null;
        }

        return $this->delivered_at->diffInMinutes($this->first_opened_at);
    }

    public function getTimeToFirstClick(): ?int
    {
        if (!$this->delivered_at || !$this->first_clicked_at) {
            return null;
        }

        return $this->delivered_at->diffInMinutes($this->first_clicked_at);
    }

    public function getTimeToConversion(): ?int
    {
        if (!$this->delivered_at || !$this->converted_at) {
            return null;
        }

        return $this->delivered_at->diffInMinutes($this->converted_at);
    }
}
