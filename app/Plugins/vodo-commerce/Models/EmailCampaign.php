<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_email_campaigns';

    protected $fillable = [
        'store_id',
        'template_id',
        'name',
        'subject',
        'preview_text',
        'from_name',
        'from_email',
        'reply_to',
        'type',
        'html_content',
        'text_content',
        'target_lists',
        'target_segments',
        'target_filters',
        'status',
        'scheduled_at',
        'sent_at',
        'completed_at',
        'is_ab_test',
        'ab_test_config',
        'ab_test_sample_size',
        'ab_test_winner_selected_at',
        'ab_test_winner',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
        'complained_count',
        'open_rate',
        'click_rate',
        'click_to_open_rate',
        'bounce_rate',
        'unsubscribe_rate',
        'total_revenue',
        'conversion_count',
        'conversion_rate',
        'track_opens',
        'track_clicks',
        'utm_parameters',
        'settings',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'target_lists' => 'array',
            'target_segments' => 'array',
            'target_filters' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_ab_test' => 'boolean',
            'ab_test_config' => 'array',
            'ab_test_winner_selected_at' => 'datetime',
            'open_rate' => 'decimal:2',
            'click_rate' => 'decimal:2',
            'click_to_open_rate' => 'decimal:2',
            'bounce_rate' => 'decimal:2',
            'unsubscribe_rate' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'conversion_rate' => 'decimal:2',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'utm_parameters' => 'array',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'campaign_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class, 'campaign_id');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', 'scheduled');
    }

    public function scopeSending(Builder $query): void
    {
        $query->where('status', 'sending');
    }

    public function scopeSent(Builder $query): void
    {
        $query->where('status', 'sent');
    }

    public function scopePaused(Builder $query): void
    {
        $query->where('status', 'paused');
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeAbTest(Builder $query): void
    {
        $query->where('is_ab_test', true);
    }

    public function scopeReadyToSend(Builder $query): void
    {
        $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeForStore(Builder $query, int $storeId): void
    {
        $query->where('store_id', $storeId);
    }

    public function scopeRecent(Builder $query, int $days = 30): void
    {
        $query->where('created_at', '>=', now()->subDays($days));
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function schedule(\DateTime $scheduledAt): bool
    {
        return $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function send(): bool
    {
        return $this->update([
            'status' => 'sending',
            'sent_at' => now(),
        ]);
    }

    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'sent',
            'completed_at' => now(),
        ]);
    }

    public function pause(): bool
    {
        return $this->update(['status' => 'paused']);
    }

    public function resume(): bool
    {
        return $this->update(['status' => 'scheduled']);
    }

    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    public function markAsFailed(): bool
    {
        return $this->update(['status' => 'failed']);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isAbTest(): bool
    {
        return $this->is_ab_test === true;
    }

    public function calculateMetrics(): void
    {
        $totalSent = $this->sent_count;

        if ($totalSent === 0) {
            return;
        }

        $openRate = ($this->opened_count / $totalSent) * 100;
        $clickRate = ($this->clicked_count / $totalSent) * 100;
        $clickToOpenRate = $this->opened_count > 0
            ? ($this->clicked_count / $this->opened_count) * 100
            : 0;
        $bounceRate = ($this->bounced_count / $totalSent) * 100;
        $unsubscribeRate = ($this->unsubscribed_count / $totalSent) * 100;
        $conversionRate = ($this->conversion_count / $totalSent) * 100;

        $this->update([
            'open_rate' => round($openRate, 2),
            'click_rate' => round($clickRate, 2),
            'click_to_open_rate' => round($clickToOpenRate, 2),
            'bounce_rate' => round($bounceRate, 2),
            'unsubscribe_rate' => round($unsubscribeRate, 2),
            'conversion_rate' => round($conversionRate, 2),
        ]);
    }

    public function incrementSentCount(): void
    {
        $this->increment('sent_count');
    }

    public function incrementDeliveredCount(): void
    {
        $this->increment('delivered_count');
        $this->calculateMetrics();
    }

    public function incrementOpenedCount(): void
    {
        $this->increment('opened_count');
        $this->calculateMetrics();
    }

    public function incrementClickedCount(): void
    {
        $this->increment('clicked_count');
        $this->calculateMetrics();
    }

    public function incrementBouncedCount(): void
    {
        $this->increment('bounced_count');
        $this->calculateMetrics();
    }

    public function incrementUnsubscribedCount(): void
    {
        $this->increment('unsubscribed_count');
        $this->calculateMetrics();
    }

    public function incrementComplainedCount(): void
    {
        $this->increment('complained_count');
    }

    public function recordConversion(float $revenue): void
    {
        $this->increment('conversion_count');
        $this->increment('total_revenue', $revenue);
        $this->calculateMetrics();
    }

    public function selectAbTestWinner(string $variant): bool
    {
        return $this->update([
            'ab_test_winner' => $variant,
            'ab_test_winner_selected_at' => now(),
        ]);
    }

    public function getEngagementScore(): float
    {
        // Simple engagement score based on opens and clicks
        $openWeight = 0.3;
        $clickWeight = 0.7;

        return round(
            ($this->open_rate * $openWeight) + ($this->click_rate * $clickWeight),
            2
        );
    }

    public function getRevenuePerRecipient(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round($this->total_revenue / $this->total_recipients, 2);
    }
}
