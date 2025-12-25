<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Flow Edge Model
 *
 * Represents a connection between nodes in a flow.
 */
class FlowEdge extends Model
{
    use HasUuids;

    protected $table = 'integration_flow_edges';

    protected $fillable = [
        'id',
        'flow_id',
        'source_node',
        'source_handle',
        'target_node',
        'target_handle',
        'condition',
    ];

    protected $casts = [
        'condition' => 'array',
    ];

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }
}
