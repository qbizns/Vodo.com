<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    use HasFactory;

    protected $table = 'commerce_email_events';

    protected $fillable = [
        'send_id',
        'campaign_id',
        'event_type',
        'recipient_email',
        'event_at',
        'link_url',
        'link_id',
        'link_position',
        'bounce_type',
        'bounce_classification',
        'bounce_reason',
        'smtp_code',
        'user_agent',
        'ip_address',
        'device_type',
        'os',
        'email_client',
        'browser',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'provider',
        'provider_event_id',
        'provider_data',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'provider_data' => 'array',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function send(): BelongsTo
    {
        return $this->belongsTo(EmailSend::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class);
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeForSend(Builder $query, int $sendId): void
    {
        $query->where('send_id', $sendId);
    }

    public function scopeForCampaign(Builder $query, int $campaignId): void
    {
        $query->where('campaign_id', $campaignId);
    }

    public function scopeByType(Builder $query, string $eventType): void
    {
        $query->where('event_type', $eventType);
    }

    public function scopeSent(Builder $query): void
    {
        $query->where('event_type', 'sent');
    }

    public function scopeDelivered(Builder $query): void
    {
        $query->where('event_type', 'delivered');
    }

    public function scopeOpened(Builder $query): void
    {
        $query->where('event_type', 'opened');
    }

    public function scopeClicked(Builder $query): void
    {
        $query->where('event_type', 'clicked');
    }

    public function scopeBounced(Builder $query): void
    {
        $query->where('event_type', 'bounced');
    }

    public function scopeComplained(Builder $query): void
    {
        $query->where('event_type', 'complained');
    }

    public function scopeUnsubscribed(Builder $query): void
    {
        $query->where('event_type', 'unsubscribed');
    }

    public function scopeByRecipient(Builder $query, string $email): void
    {
        $query->where('recipient_email', $email);
    }

    public function scopeByDevice(Builder $query, string $deviceType): void
    {
        $query->where('device_type', $deviceType);
    }

    public function scopeByClient(Builder $query, string $emailClient): void
    {
        $query->where('email_client', $emailClient);
    }

    public function scopeByCountry(Builder $query, string $country): void
    {
        $query->where('country', $country);
    }

    public function scopeWithLocation(Builder $query): void
    {
        $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    public function scopeByLink(Builder $query, string $linkUrl): void
    {
        $query->where('link_url', $linkUrl);
    }

    public function scopeHardBounce(Builder $query): void
    {
        $query->where('event_type', 'bounced')
            ->where('bounce_type', 'hard');
    }

    public function scopeSoftBounce(Builder $query): void
    {
        $query->where('event_type', 'bounced')
            ->where('bounce_type', 'soft');
    }

    public function scopeRecent(Builder $query, int $days = 30): void
    {
        $query->where('event_at', '>=', now()->subDays($days));
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function isSent(): bool
    {
        return $this->event_type === 'sent';
    }

    public function isDelivered(): bool
    {
        return $this->event_type === 'delivered';
    }

    public function isOpened(): bool
    {
        return $this->event_type === 'opened';
    }

    public function isClicked(): bool
    {
        return $this->event_type === 'clicked';
    }

    public function isBounced(): bool
    {
        return $this->event_type === 'bounced';
    }

    public function isComplained(): bool
    {
        return $this->event_type === 'complained';
    }

    public function isUnsubscribed(): bool
    {
        return $this->event_type === 'unsubscribed';
    }

    public function isHardBounce(): bool
    {
        return $this->isBounced() && $this->bounce_type === 'hard';
    }

    public function isSoftBounce(): bool
    {
        return $this->isBounced() && $this->bounce_type === 'soft';
    }

    public function isMobile(): bool
    {
        return in_array($this->device_type, ['mobile', 'tablet']);
    }

    public function isDesktop(): bool
    {
        return $this->device_type === 'desktop';
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getLocation(): ?array
    {
        if (!$this->hasLocation()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
        ];
    }

    public function parseUserAgent(): array
    {
        if (!$this->user_agent) {
            return [];
        }

        // Basic user agent parsing (in production, use a library like ua-parser)
        $ua = $this->user_agent;

        return [
            'device_type' => $this->device_type,
            'os' => $this->os,
            'browser' => $this->browser,
            'email_client' => $this->email_client,
        ];
    }

    public static function recordEvent(
        int $sendId,
        string $eventType,
        string $recipientEmail,
        array $additionalData = []
    ): self {
        $send = EmailSend::find($sendId);

        $data = array_merge([
            'send_id' => $sendId,
            'campaign_id' => $send?->campaign_id,
            'event_type' => $eventType,
            'recipient_email' => $recipientEmail,
            'event_at' => now(),
        ], $additionalData);

        $event = static::create($data);

        // Update send and campaign metrics
        if ($send) {
            match ($eventType) {
                'delivered' => $send->markAsDelivered(),
                'opened' => $send->recordOpen(),
                'clicked' => $send->recordClick($additionalData['link_url'] ?? null),
                'bounced' => $send->markAsBounced(
                    $additionalData['bounce_type'] ?? 'undetermined',
                    $additionalData['bounce_reason'] ?? null
                ),
                default => null,
            };

            // Update campaign metrics
            if ($send->campaign) {
                match ($eventType) {
                    'delivered' => $send->campaign->incrementDeliveredCount(),
                    'opened' => $send->campaign->incrementOpenedCount(),
                    'clicked' => $send->campaign->incrementClickedCount(),
                    'bounced' => $send->campaign->incrementBouncedCount(),
                    'unsubscribed' => $send->campaign->incrementUnsubscribedCount(),
                    'complained' => $send->campaign->incrementComplainedCount(),
                    default => null,
                };
            }
        }

        return $event;
    }
}
