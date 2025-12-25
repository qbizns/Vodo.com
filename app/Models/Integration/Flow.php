<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Flow Model
 *
 * Represents an automation flow (workflow).
 */
class Flow extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'integration_flows';

    protected $fillable = [
        'id',
        'slug',
        'name',
        'description',
        'tenant_id',
        'trigger_config',
        'settings',
        'status',
        'version',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'settings' => 'array',
        'version' => 'integer',
    ];

    public function nodes()
    {
        return $this->hasMany(FlowNode::class);
    }

    public function edges()
    {
        return $this->hasMany(FlowEdge::class);
    }

    public function executions()
    {
        return $this->hasMany(FlowExecution::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(TriggerSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
