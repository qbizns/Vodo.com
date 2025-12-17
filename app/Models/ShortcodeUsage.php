<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortcodeUsage extends Model
{
    public $timestamps = false;

    protected $table = 'shortcode_usage';

    protected $fillable = [
        'shortcode_id',
        'content_type',
        'content_id',
        'field_name',
        'attributes_used',
        'created_at',
    ];

    protected $casts = [
        'attributes_used' => 'array',
        'created_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function shortcode(): BelongsTo
    {
        return $this->belongsTo(Shortcode::class);
    }

    // =========================================================================
    // Factory
    // =========================================================================

    /**
     * Track shortcode usage
     */
    public static function track(
        Shortcode $shortcode,
        string $contentType,
        int $contentId,
        ?string $fieldName = null,
        ?array $attributes = null
    ): self {
        return static::create([
            'shortcode_id' => $shortcode->id,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'field_name' => $fieldName,
            'attributes_used' => $attributes,
            'created_at' => now(),
        ]);
    }

    /**
     * Clear usage for content
     */
    public static function clearForContent(string $contentType, int $contentId): int
    {
        return static::where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->delete();
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeForContent(Builder $query, string $contentType, int $contentId): Builder
    {
        return $query->where('content_type', $contentType)
            ->where('content_id', $contentId);
    }

    public function scopeForShortcode(Builder $query, int $shortcodeId): Builder
    {
        return $query->where('shortcode_id', $shortcodeId);
    }

    // =========================================================================
    // Statistics
    // =========================================================================

    /**
     * Get usage statistics for a shortcode
     */
    public static function getStatsForShortcode(int $shortcodeId): array
    {
        $usages = static::forShortcode($shortcodeId)->get();

        return [
            'total_uses' => $usages->count(),
            'by_content_type' => $usages->groupBy('content_type')
                ->map(fn($group) => $group->count()),
            'unique_content' => $usages->unique(fn($u) => $u->content_type . ':' . $u->content_id)->count(),
        ];
    }
}
