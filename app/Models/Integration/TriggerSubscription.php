<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Trigger Subscription Model
 *
 * Represents a subscription to a trigger (webhook, polling, etc.).
 */
class TriggerSubscription extends Model
{
    use HasUuids;

    protected $table = 'integration_trigger_subscriptions';

    protected $fillable = [
        'id',
        'flow_id',
        'connector_name',
        'trigger_name',
        'connection_id',
        'config',
        'status',
        'webhook_id',
        'webhook_secret',
        'webhook_registered_at',
        'polling_state',
        'last_polled_at',
    ];

    protected $casts = [
        'config' => 'array',
        'polling_state' => 'array',
        'webhook_registered_at' => 'datetime',
        'last_polled_at' => 'datetime',
    ];

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }

    public function connection()
    {
        return $this->belongsTo(Connection::class);
    }

    public function events()
    {
        return $this->hasMany(TriggerEvent::class, 'subscription_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForConnector($query, string $connectorName)
    {
        return $query->where('connector_name', $connectorName);
    }
}
