<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Integration Connection Model
 *
 * Represents a connection to an external service (stored credentials reference).
 */
class Connection extends Model
{
    use HasUuids;

    protected $table = 'integration_connections';

    protected $fillable = [
        'id',
        'tenant_id',
        'connector_name',
        'label',
        'credential_id',
        'metadata',
        'status',
        'last_used_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function subscriptions()
    {
        return $this->hasMany(TriggerSubscription::class, 'connection_id');
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForConnector($query, string $connectorName)
    {
        return $query->where('connector_name', $connectorName);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
