<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginMigration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plugin_id',
        'migration',
        'batch',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'batch' => 'integer',
    ];

    /**
     * Get the plugin that owns this migration.
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    /**
     * Get the next batch number for a plugin.
     */
    public static function getNextBatch(int $pluginId): int
    {
        $maxBatch = static::where('plugin_id', $pluginId)->max('batch');
        
        return ($maxBatch ?? 0) + 1;
    }

    /**
     * Get the last batch number for a plugin.
     */
    public static function getLastBatch(int $pluginId): int
    {
        return static::where('plugin_id', $pluginId)->max('batch') ?? 0;
    }

    /**
     * Get migrations for a specific batch.
     */
    public function scopeForBatch($query, int $pluginId, int $batch)
    {
        return $query->where('plugin_id', $pluginId)->where('batch', $batch);
    }
}
