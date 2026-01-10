<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSetting extends Model
{
    use HasFactory;

    protected $table = 'commerce_marketplace_settings';

    protected $fillable = [
        'store_id',
        'key',
        'value',
        'type',
        'group',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
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

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeForStore(Builder $query, int $storeId): void
    {
        $query->where('store_id', $storeId);
    }

    public function scopeByKey(Builder $query, string $key): void
    {
        $query->where('key', $key);
    }

    public function scopeByGroup(Builder $query, string $group): void
    {
        $query->where('group', $group);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    public function setTypedValue(mixed $value): void
    {
        $stringValue = match ($this->type) {
            'boolean' => $value ? 'true' : 'false',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };

        $this->update(['value' => $stringValue]);
    }

    public static function get(int $storeId, string $key, mixed $default = null): mixed
    {
        $setting = static::forStore($storeId)->byKey($key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->getTypedValue();
    }

    public static function set(
        int $storeId,
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = null,
        string $description = null
    ): MarketplaceSetting {
        $setting = static::forStore($storeId)->byKey($key)->first();

        if ($setting) {
            $setting->setTypedValue($value);
            return $setting;
        }

        // Create new setting
        $stringValue = match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };

        return static::create([
            'store_id' => $storeId,
            'key' => $key,
            'value' => $stringValue,
            'type' => $type,
            'group' => $group,
            'description' => $description,
        ]);
    }

    public static function delete(int $storeId, string $key): bool
    {
        return static::forStore($storeId)->byKey($key)->delete();
    }

    public static function getByGroup(int $storeId, string $group): array
    {
        $settings = static::forStore($storeId)->byGroup($group)->get();

        return $settings->mapWithKeys(function (MarketplaceSetting $setting) {
            return [$setting->key => $setting->getTypedValue()];
        })->toArray();
    }

    public static function all(int $storeId): array
    {
        $settings = static::forStore($storeId)->get();

        return $settings->mapWithKeys(function (MarketplaceSetting $setting) {
            return [$setting->key => $setting->getTypedValue()];
        })->toArray();
    }

    public static function flush(int $storeId, string $group = null): bool
    {
        $query = static::forStore($storeId);

        if ($group) {
            $query->byGroup($group);
        }

        return $query->delete();
    }
}
