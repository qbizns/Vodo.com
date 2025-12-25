<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Flow Node Model
 *
 * Represents a node in an automation flow.
 */
class FlowNode extends Model
{
    use HasUuids;

    protected $table = 'integration_flow_nodes';

    protected $fillable = [
        'id',
        'flow_id',
        'node_id',
        'type',
        'name',
        'config',
        'position',
    ];

    protected $casts = [
        'config' => 'array',
        'position' => 'array',
    ];

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }

    public function outgoingEdges()
    {
        return $this->hasMany(FlowEdge::class, 'source_node', 'node_id')
            ->where('flow_id', $this->flow_id);
    }

    public function incomingEdges()
    {
        return $this->hasMany(FlowEdge::class, 'target_node', 'node_id')
            ->where('flow_id', $this->flow_id);
    }
}
