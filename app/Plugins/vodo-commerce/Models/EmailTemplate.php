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

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_email_templates';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'category',
        'default_subject',
        'default_preview_text',
        'html_content',
        'text_content',
        'available_variables',
        'required_variables',
        'thumbnail',
        'design_config',
        'type',
        'trigger_event',
        'trigger_conditions',
        'trigger_delay_minutes',
        'is_active',
        'is_default',
        'usage_count',
        'last_used_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'required_variables' => 'array',
            'design_config' => 'array',
            'trigger_conditions' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'last_used_at' => 'datetime',
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

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'template_id');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'template_id');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): void
    {
        $query->where('is_default', true);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeByCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    public function scopeByTrigger(Builder $query, string $triggerEvent): void
    {
        $query->where('trigger_event', $triggerEvent);
    }

    public function scopeTransactional(Builder $query): void
    {
        $query->where('type', 'transactional');
    }

    public function scopeMarketing(Builder $query): void
    {
        $query->where('type', 'marketing');
    }

    public function scopeAutomated(Builder $query): void
    {
        $query->where('type', 'automated');
    }

    public function scopeForStore(Builder $query, int $storeId): void
    {
        $query->where('store_id', $storeId);
    }

    public function scopeMostUsed(Builder $query, int $limit = 10): void
    {
        $query->where('usage_count', '>', 0)
            ->orderByDesc('usage_count')
            ->limit($limit);
    }

    public function scopeRecentlyUsed(Builder $query, int $days = 30): void
    {
        $query->whereNotNull('last_used_at')
            ->where('last_used_at', '>=', now()->subDays($days))
            ->orderByDesc('last_used_at');
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

    public function setAsDefault(): bool
    {
        // Unset other defaults for this trigger event
        if ($this->trigger_event) {
            static::where('store_id', $this->store_id)
                ->where('trigger_event', $this->trigger_event)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);
        }

        return $this->update(['is_default' => true]);
    }

    public function unsetAsDefault(): bool
    {
        return $this->update(['is_default' => false]);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    public function isTransactional(): bool
    {
        return $this->type === 'transactional';
    }

    public function isMarketing(): bool
    {
        return $this->type === 'marketing';
    }

    public function isAutomated(): bool
    {
        return $this->type === 'automated';
    }

    public function render(array $variables = []): string
    {
        $content = $this->html_content;

        // Replace variables
        foreach ($variables as $key => $value) {
            $placeholder = '{{ ' . $key . ' }}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    public function renderPlainText(array $variables = []): string
    {
        $content = $this->text_content ?? strip_tags($this->html_content);

        // Replace variables
        foreach ($variables as $key => $value) {
            $placeholder = '{{ ' . $key . ' }}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    public function validateVariables(array $variables): array
    {
        $missing = [];

        if ($this->required_variables) {
            foreach ($this->required_variables as $required) {
                if (!isset($variables[$required])) {
                    $missing[] = $required;
                }
            }
        }

        return $missing;
    }

    public function hasRequiredVariables(array $variables): bool
    {
        return empty($this->validateVariables($variables));
    }

    public function incrementUsageCount(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function getTriggerDelayInMinutes(): int
    {
        return $this->trigger_delay_minutes ?? 0;
    }

    public function shouldTriggerForEvent(string $event): bool
    {
        return $this->is_active
            && $this->trigger_event === $event;
    }

    public function evaluateTriggerConditions(array $data): bool
    {
        if (empty($this->trigger_conditions)) {
            return true;
        }

        // Evaluate conditions (simple implementation)
        foreach ($this->trigger_conditions as $field => $condition) {
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;
            $dataValue = $data[$field] ?? null;

            $result = match ($operator) {
                '=' => $dataValue == $value,
                '!=' => $dataValue != $value,
                '>' => $dataValue > $value,
                '>=' => $dataValue >= $value,
                '<' => $dataValue < $value,
                '<=' => $dataValue <= $value,
                'contains' => str_contains((string) $dataValue, (string) $value),
                'in' => in_array($dataValue, (array) $value),
                default => false,
            };

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // EVENTS
    // =========================================================================

    protected static function booted(): void
    {
        static::creating(function (EmailTemplate $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);

                // Ensure uniqueness
                $originalSlug = $template->slug;
                $counter = 1;

                while (static::where('slug', $template->slug)->exists()) {
                    $template->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }
}
