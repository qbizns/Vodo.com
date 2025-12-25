<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Trigger Event Model
 *
 * Represents an event received from a trigger.
 */
class TriggerEvent extends Model
{
    use HasUuids;

    protected $table = 'integration_trigger_events';

    protected $fillable = [
        'id',
        'subscription_id',
        'flow_id',
        'data',
        'deduplication_key',
        'status',
        'processed_at',
        'error',
    ];

    protected $casts = [
        'data' => 'array',
        'error' => 'array',
        'processed_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(TriggerSubscription::class);
    }

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
