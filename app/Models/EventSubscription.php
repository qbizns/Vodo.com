<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EventSubscription extends Model
{
    protected $fillable = [
        'event', 'listener', 'priority', 'is_active', 'run_async',
        'queue', 'plugin_slug', 'conditions', 'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'run_async' => 'boolean',
        'conditions' => 'array',
        'meta' => 'array',
    ];

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('id');
    }

    public function scopeSync(Builder $query): Builder
    {
        return $query->where('run_async', false);
    }

    public function scopeAsync(Builder $query): Builder
    {
        return $query->where('run_async', true);
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function getListener(): array
    {
        if (str_contains($this->listener, '@')) {
            [$class, $method] = explode('@', $this->listener);
            return ['class' => $class, 'method' => $method];
        }
        return ['class' => $this->listener, 'method' => 'handle'];
    }

    public function shouldRun(array $payload = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->conditions) {
            return true;
        }

        return $this->evaluateConditions($payload);
    }

    protected function evaluateConditions(array $payload): bool
    {
        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field) continue;

            $actualValue = data_get($payload, $field);

            $result = match ($operator) {
                '=' => $actualValue == $value,
                '!=' => $actualValue != $value,
                '>' => $actualValue > $value,
                '<' => $actualValue < $value,
                '>=' => $actualValue >= $value,
                '<=' => $actualValue <= $value,
                'in' => in_array($actualValue, (array) $value),
                'not_in' => !in_array($actualValue, (array) $value),
                'contains' => str_contains($actualValue, $value),
                'starts_with' => str_starts_with($actualValue, $value),
                'ends_with' => str_ends_with($actualValue, $value),
                'null' => is_null($actualValue),
                'not_null' => !is_null($actualValue),
                default => true,
            };

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    public static function getForEvent(string $event): \Illuminate\Support\Collection
    {
        return static::active()
            ->forEvent($event)
            ->byPriority()
            ->get();
    }
}
