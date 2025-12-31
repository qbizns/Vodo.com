<?php

declare(strict_types=1);

namespace App\Models\Enterprise;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WebhookDelivery extends Model
{
    protected $table = 'webhook_deliveries';

    protected $fillable = [
        'uuid',
        'endpoint_id',
        'event',
        'payload',
        'status',
        'attempts',
        'http_status',
        'response_body',
        'response_time_ms',
        'error_message',
        'next_retry_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'http_status' => 'integer',
            'response_time_ms' => 'integer',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? Str::uuid();
        });
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRetrying(): bool
    {
        return $this->status === 'retrying';
    }

    public function canRetry(): bool
    {
        return $this->attempts < $this->endpoint->retry_count;
    }

    public function markAsDelivered(int $httpStatus, ?string $responseBody, int $responseTimeMs): void
    {
        $this->update([
            'status' => 'delivered',
            'http_status' => $httpStatus,
            'response_body' => $responseBody ? Str::limit($responseBody, 10000) : null,
            'response_time_ms' => $responseTimeMs,
            'delivered_at' => now(),
        ]);

        $this->endpoint->recordSuccess();
    }

    public function markAsFailed(string $errorMessage, ?int $httpStatus = null): void
    {
        $this->increment('attempts');

        if ($this->canRetry()) {
            $delay = $this->calculateRetryDelay();
            $this->update([
                'status' => 'retrying',
                'error_message' => $errorMessage,
                'http_status' => $httpStatus,
                'next_retry_at' => now()->addSeconds($delay),
            ]);
        } else {
            $this->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'http_status' => $httpStatus,
            ]);
            $this->endpoint->recordFailure();
        }
    }

    public function calculateRetryDelay(): int
    {
        // Exponential backoff: 30s, 60s, 120s, 240s...
        return (int) (30 * pow(2, $this->attempts));
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRetrying($query)
    {
        return $query->where('status', 'retrying')
            ->where('next_retry_at', '<=', now());
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForEndpoint($query, int $endpointId)
    {
        return $query->where('endpoint_id', $endpointId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
