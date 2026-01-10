<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmailList extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_email_lists';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'type',
        'criteria',
        'last_synced_at',
        'is_active',
        'allow_public_signup',
        'welcome_message',
        'send_welcome_email',
        'welcome_email_template_id',
        'require_double_optin',
        'confirmation_email_template_id',
        'total_subscribers',
        'active_subscribers',
        'unsubscribed_count',
        'bounced_count',
        'complained_count',
        'avg_open_rate',
        'avg_click_rate',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
            'allow_public_signup' => 'boolean',
            'send_welcome_email' => 'boolean',
            'require_double_optin' => 'boolean',
            'avg_open_rate' => 'decimal:2',
            'avg_click_rate' => 'decimal:2',
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

    public function welcomeEmailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'welcome_email_template_id');
    }

    public function confirmationEmailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'confirmation_email_template_id');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(EmailListSubscriber::class, 'list_id');
    }

    public function activeSubscribers(): HasMany
    {
        return $this->subscribers()->where('status', 'subscribed');
    }

    public function pendingSubscribers(): HasMany
    {
        return $this->subscribers()->where('status', 'pending');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeStatic(Builder $query): void
    {
        $query->where('type', 'static');
    }

    public function scopeDynamic(Builder $query): void
    {
        $query->where('type', 'dynamic');
    }

    public function scopeSegment(Builder $query): void
    {
        $query->where('type', 'segment');
    }

    public function scopePublicSignup(Builder $query): void
    {
        $query->where('allow_public_signup', true);
    }

    public function scopeForStore(Builder $query, int $storeId): void
    {
        $query->where('store_id', $storeId);
    }

    public function scopeNeedsSync(Builder $query): void
    {
        $query->where('type', 'dynamic')
            ->where(function ($q) {
                $q->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', now()->subHours(1));
            });
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isStatic(): bool
    {
        return $this->type === 'static';
    }

    public function isDynamic(): bool
    {
        return $this->type === 'dynamic';
    }

    public function isSegment(): bool
    {
        return $this->type === 'segment';
    }

    public function allowsPublicSignup(): bool
    {
        return $this->allow_public_signup === true;
    }

    public function requiresDoubleOptin(): bool
    {
        return $this->require_double_optin === true;
    }

    public function addSubscriber(
        string $email,
        ?string $firstName = null,
        ?string $lastName = null,
        ?int $customerId = null,
        string $source = 'manual'
    ): EmailListSubscriber {
        return $this->subscribers()->create([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'customer_id' => $customerId,
            'status' => $this->requiresDoubleOptin() ? 'pending' : 'subscribed',
            'source' => $source,
            'subscribed_at' => now(),
        ]);
    }

    public function removeSubscriber(string $email): bool
    {
        return $this->subscribers()
            ->where('email', $email)
            ->delete();
    }

    public function hasSubscriber(string $email): bool
    {
        return $this->subscribers()
            ->where('email', $email)
            ->where('status', 'subscribed')
            ->exists();
    }

    public function syncSubscriberCounts(): void
    {
        $this->update([
            'total_subscribers' => $this->subscribers()->count(),
            'active_subscribers' => $this->activeSubscribers()->count(),
            'unsubscribed_count' => $this->subscribers()->where('status', 'unsubscribed')->count(),
            'bounced_count' => $this->subscribers()->where('status', 'bounced')->count(),
            'complained_count' => $this->subscribers()->where('status', 'complained')->count(),
        ]);
    }

    public function calculateEngagementMetrics(): void
    {
        $avgOpenRate = $this->subscribers()
            ->where('emails_sent', '>', 0)
            ->avg('open_rate');

        $avgClickRate = $this->subscribers()
            ->where('emails_sent', '>', 0)
            ->avg('click_rate');

        $this->update([
            'avg_open_rate' => $avgOpenRate ? round($avgOpenRate, 2) : 0,
            'avg_click_rate' => $avgClickRate ? round($avgClickRate, 2) : 0,
        ]);
    }

    public function syncDynamicList(): int
    {
        if (!$this->isDynamic() || empty($this->criteria)) {
            return 0;
        }

        // This would query customers based on criteria
        // For now, just mark as synced
        $this->update(['last_synced_at' => now()]);

        return 0; // Return count of new subscribers added
    }

    public function getHealthScore(): float
    {
        if ($this->total_subscribers === 0) {
            return 0;
        }

        $activeRate = ($this->active_subscribers / $this->total_subscribers) * 100;
        $bounceRate = ($this->bounced_count / $this->total_subscribers) * 100;
        $complaintRate = ($this->complained_count / $this->total_subscribers) * 100;

        // Health score: higher is better
        $score = $activeRate - ($bounceRate * 2) - ($complaintRate * 3);

        return round(max(0, min(100, $score)), 2);
    }

    public function cleanInactiveSubscribers(int $inactiveDays = 365): int
    {
        $cutoffDate = now()->subDays($inactiveDays);

        $count = $this->subscribers()
            ->where('status', 'subscribed')
            ->where(function ($q) use ($cutoffDate) {
                $q->whereNull('last_opened_at')
                    ->orWhere('last_opened_at', '<', $cutoffDate);
            })
            ->update(['status' => 'cleaned']);

        $this->syncSubscriberCounts();

        return $count;
    }

    // =========================================================================
    // EVENTS
    // =========================================================================

    protected static function booted(): void
    {
        static::creating(function (EmailList $list) {
            if (empty($list->slug)) {
                $list->slug = Str::slug($list->name);

                // Ensure uniqueness
                $originalSlug = $list->slug;
                $counter = 1;

                while (static::where('slug', $list->slug)->exists()) {
                    $list->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }
}
