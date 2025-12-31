<?php

declare(strict_types=1);

namespace App\Services\Integration\Flow;

use App\Contracts\Integration\FlowContract;
use App\Models\Integration\Flow;
use App\Models\Integration\FlowNode;
use App\Models\Integration\FlowEdge;
use App\Models\Integration\FlowExecution;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Flow Engine
 *
 * Manages automation flows (workflows) with nodes, conditions, and loops.
 * Similar to n8n/Make.com flow builder functionality.
 *
 * @example Create a flow
 * ```php
 * $flow = $engine->create('welcome_email', [
 *     'name' => 'Send Welcome Email',
 *     'description' => 'Send email when user signs up',
 *     'trigger' => [
 *         'connector' => 'webhook',
 *         'trigger' => 'user_created',
 *     ],
 *     'nodes' => [...],
 * ]);
 * ```
 */
class FlowEngine implements FlowContract
{
    /**
     * Node type handlers.
     *
     * @var array<string, callable>
     */
    protected array $nodeHandlers = [];

    /**
     * Registered flow templates.
     *
     * @var array<string, array>
     */
    protected array $templates = [];

    public function __construct()
    {
        $this->registerDefaultNodeHandlers();
    }

    /**
     * Register default node type handlers.
     */
    protected function registerDefaultNodeHandlers(): void
    {
        // Trigger node (entry point)
        $this->nodeHandlers['trigger'] = fn($node, $context) => $context['trigger_data'] ?? [];

        // Action node (execute external action)
        $this->nodeHandlers['action'] = fn($node, $context) => $this->executeActionNode($node, $context);

        // Condition node (if/else branching)
        $this->nodeHandlers['condition'] = fn($node, $context) => $this->evaluateConditionNode($node, $context);

        // Switch node (multiple branches)
        $this->nodeHandlers['switch'] = fn($node, $context) => $this->evaluateSwitchNode($node, $context);

        // Loop node (iterate over array)
        $this->nodeHandlers['loop'] = fn($node, $context) => $this->executeLoopNode($node, $context);

        // Merge node (combine multiple inputs)
        $this->nodeHandlers['merge'] = fn($node, $context) => $this->executeMergeNode($node, $context);

        // Transform node (data transformation)
        $this->nodeHandlers['transform'] = fn($node, $context) => $this->executeTransformNode($node, $context);

        // Filter node (filter array items)
        $this->nodeHandlers['filter'] = fn($node, $context) => $this->executeFilterNode($node, $context);

        // Split node (split into multiple items)
        $this->nodeHandlers['split'] = fn($node, $context) => $this->executeSplitNode($node, $context);

        // Wait node (delay execution)
        $this->nodeHandlers['wait'] = fn($node, $context) => $this->executeWaitNode($node, $context);

        // HTTP node (make HTTP request)
        $this->nodeHandlers['http'] = fn($node, $context) => $this->executeHttpNode($node, $context);

        // Code node (execute custom code)
        $this->nodeHandlers['code'] = fn($node, $context) => $this->executeCodeNode($node, $context);

        // Set node (set variables)
        $this->nodeHandlers['set'] = fn($node, $context) => $this->executeSetNode($node, $context);

        // Error node (error handling)
        $this->nodeHandlers['error'] = fn($node, $context) => $this->executeErrorNode($node, $context);

        // End node (terminates flow)
        $this->nodeHandlers['end'] = fn($node, $context) => ['_end' => true];
    }

    // =========================================================================
    // FLOW MANAGEMENT
    // =========================================================================

    public function create(string $slug, array $config): Flow
    {
        $flow = Flow::create([
            'id' => Str::uuid()->toString(),
            'slug' => $slug,
            'name' => $config['name'] ?? $this->generateLabel($slug),
            'description' => $config['description'] ?? null,
            'tenant_id' => $config['tenant_id'] ?? null,
            'trigger_config' => $config['trigger'] ?? null,
            'settings' => $config['settings'] ?? [],
            'status' => 'draft',
            'version' => 1,
        ]);

        // Create nodes
        if (!empty($config['nodes'])) {
            foreach ($config['nodes'] as $nodeData) {
                $this->createNode($flow->id, $nodeData);
            }
        }

        // Create edges (connections)
        if (!empty($config['edges'])) {
            foreach ($config['edges'] as $edgeData) {
                $this->createEdge($flow->id, $edgeData);
            }
        }

        return $flow->fresh(['nodes', 'edges']);
    }

    public function get(string $flowId): ?Flow
    {
        return Flow::with(['nodes', 'edges'])->find($flowId);
    }

    public function getBySlug(string $slug): ?Flow
    {
        return Flow::with(['nodes', 'edges'])->where('slug', $slug)->first();
    }

    public function update(string $flowId, array $config): Flow
    {
        $flow = Flow::findOrFail($flowId);

        $flow->update([
            'name' => $config['name'] ?? $flow->name,
            'description' => $config['description'] ?? $flow->description,
            'trigger_config' => $config['trigger'] ?? $flow->trigger_config,
            'settings' => array_merge($flow->settings ?? [], $config['settings'] ?? []),
            'version' => $flow->version + 1,
        ]);

        // Update nodes if provided
        if (isset($config['nodes'])) {
            FlowNode::where('flow_id', $flowId)->delete();
            foreach ($config['nodes'] as $nodeData) {
                $this->createNode($flowId, $nodeData);
            }
        }

        // Update edges if provided
        if (isset($config['edges'])) {
            FlowEdge::where('flow_id', $flowId)->delete();
            foreach ($config['edges'] as $edgeData) {
                $this->createEdge($flowId, $edgeData);
            }
        }

        return $flow->fresh(['nodes', 'edges']);
    }

    public function delete(string $flowId): bool
    {
        $flow = Flow::find($flowId);

        if (!$flow) {
            return false;
        }

        // Delete related records
        FlowNode::where('flow_id', $flowId)->delete();
        FlowEdge::where('flow_id', $flowId)->delete();

        $flow->delete();

        return true;
    }

    public function duplicate(string $flowId, ?string $newSlug = null): Flow
    {
        $original = Flow::with(['nodes', 'edges'])->findOrFail($flowId);

        $newSlug = $newSlug ?? $original->slug . '_copy_' . Str::random(4);

        return $this->create($newSlug, [
            'name' => $original->name . ' (Copy)',
            'description' => $original->description,
            'tenant_id' => $original->tenant_id,
            'trigger' => $original->trigger_config,
            'settings' => $original->settings,
            'nodes' => $original->nodes->map(fn($n) => [
                'node_id' => $n->node_id,
                'type' => $n->type,
                'name' => $n->name,
                'config' => $n->config,
                'position' => $n->position,
            ])->toArray(),
            'edges' => $original->edges->map(fn($e) => [
                'source_node' => $e->source_node,
                'source_handle' => $e->source_handle,
                'target_node' => $e->target_node,
                'target_handle' => $e->target_handle,
            ])->toArray(),
        ]);
    }

    // =========================================================================
    // NODE MANAGEMENT
    // =========================================================================

    public function createNode(string $flowId, array $data): FlowNode
    {
        return FlowNode::create([
            'id' => Str::uuid()->toString(),
            'flow_id' => $flowId,
            'node_id' => $data['node_id'] ?? Str::random(8),
            'type' => $data['type'],
            'name' => $data['name'] ?? $this->generateLabel($data['type']),
            'config' => $data['config'] ?? [],
            'position' => $data['position'] ?? ['x' => 0, 'y' => 0],
        ]);
    }

    public function updateNode(string $nodeId, array $data): FlowNode
    {
        $node = FlowNode::findOrFail($nodeId);

        $node->update([
            'name' => $data['name'] ?? $node->name,
            'config' => $data['config'] ?? $node->config,
            'position' => $data['position'] ?? $node->position,
        ]);

        return $node;
    }

    public function deleteNode(string $nodeId): bool
    {
        $node = FlowNode::find($nodeId);

        if (!$node) {
            return false;
        }

        // Delete connected edges
        FlowEdge::where('flow_id', $node->flow_id)
            ->where(function ($q) use ($node) {
                $q->where('source_node', $node->node_id)
                    ->orWhere('target_node', $node->node_id);
            })
            ->delete();

        $node->delete();

        return true;
    }

    public function createEdge(string $flowId, array $data): FlowEdge
    {
        return FlowEdge::create([
            'id' => Str::uuid()->toString(),
            'flow_id' => $flowId,
            'source_node' => $data['source_node'],
            'source_handle' => $data['source_handle'] ?? 'output',
            'target_node' => $data['target_node'],
            'target_handle' => $data['target_handle'] ?? 'input',
            'condition' => $data['condition'] ?? null,
        ]);
    }

    public function deleteEdge(string $edgeId): bool
    {
        return FlowEdge::destroy($edgeId) > 0;
    }

    // =========================================================================
    // STATUS MANAGEMENT
    // =========================================================================

    public function activate(string $flowId): bool
    {
        $flow = Flow::findOrFail($flowId);

        // Validate flow before activation
        $validation = $this->validate($flowId);
        if (!$validation['valid']) {
            throw new \App\Exceptions\Integration\FlowValidationException(
                'Flow validation failed',
                $validation['errors']
            );
        }

        $flow->update(['status' => 'active']);

        do_action('flow_activated', $flow);

        return true;
    }

    public function deactivate(string $flowId): bool
    {
        $flow = Flow::findOrFail($flowId);
        $flow->update(['status' => 'inactive']);

        do_action('flow_deactivated', $flow);

        return true;
    }

    public function getStatus(string $flowId): string
    {
        return Flow::findOrFail($flowId)->status;
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    public function validate(string $flowId): array
    {
        $flow = Flow::with(['nodes', 'edges'])->findOrFail($flowId);

        $errors = [];
        $warnings = [];

        // Check for trigger
        $triggerNodes = $flow->nodes->where('type', 'trigger');
        if ($triggerNodes->isEmpty()) {
            $errors[] = 'Flow must have at least one trigger node';
        }

        // Check for orphan nodes
        $connectedNodes = collect();
        foreach ($flow->edges as $edge) {
            $connectedNodes->push($edge->source_node);
            $connectedNodes->push($edge->target_node);
        }
        $connectedNodes = $connectedNodes->unique();

        foreach ($flow->nodes as $node) {
            if ($node->type !== 'trigger' && !$connectedNodes->contains($node->node_id)) {
                $warnings[] = "Node '{$node->name}' is not connected";
            }
        }

        // Validate each node
        foreach ($flow->nodes as $node) {
            $nodeErrors = $this->validateNode($node);
            $errors = array_merge($errors, $nodeErrors);
        }

        // Check for cycles (optional - some flows allow cycles)
        if (!($flow->settings['allow_cycles'] ?? false)) {
            if ($this->detectCycles($flow)) {
                $errors[] = 'Flow contains cycles which are not allowed';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function validateNode(FlowNode $node): array
    {
        $errors = [];

        // Type-specific validation
        switch ($node->type) {
            case 'action':
                if (empty($node->config['connector'])) {
                    $errors[] = "Node '{$node->name}': connector is required";
                }
                if (empty($node->config['action'])) {
                    $errors[] = "Node '{$node->name}': action is required";
                }
                break;

            case 'condition':
                if (empty($node->config['conditions'])) {
                    $errors[] = "Node '{$node->name}': conditions are required";
                }
                break;

            case 'loop':
                if (empty($node->config['array_path'])) {
                    $errors[] = "Node '{$node->name}': array path is required";
                }
                break;

            case 'http':
                if (empty($node->config['url'])) {
                    $errors[] = "Node '{$node->name}': URL is required";
                }
                break;
        }

        return $errors;
    }

    protected function detectCycles(Flow $flow): bool
    {
        $adjacency = [];

        foreach ($flow->nodes as $node) {
            $adjacency[$node->node_id] = [];
        }

        foreach ($flow->edges as $edge) {
            $adjacency[$edge->source_node][] = $edge->target_node;
        }

        $visited = [];
        $recursionStack = [];

        foreach (array_keys($adjacency) as $nodeId) {
            if ($this->hasCycleDFS($nodeId, $adjacency, $visited, $recursionStack)) {
                return true;
            }
        }

        return false;
    }

    protected function hasCycleDFS(
        string $nodeId,
        array $adjacency,
        array &$visited,
        array &$recursionStack
    ): bool {
        $visited[$nodeId] = true;
        $recursionStack[$nodeId] = true;

        foreach ($adjacency[$nodeId] ?? [] as $neighbor) {
            if (!isset($visited[$neighbor])) {
                if ($this->hasCycleDFS($neighbor, $adjacency, $visited, $recursionStack)) {
                    return true;
                }
            } elseif ($recursionStack[$neighbor] ?? false) {
                return true;
            }
        }

        $recursionStack[$nodeId] = false;
        return false;
    }

    // =========================================================================
    // TEMPLATES
    // =========================================================================

    public function registerTemplate(string $name, array $template): self
    {
        $this->templates[$name] = $template;
        return $this;
    }

    public function getTemplates(): Collection
    {
        return collect($this->templates);
    }

    public function createFromTemplate(string $templateName, array $overrides = []): Flow
    {
        if (!isset($this->templates[$templateName])) {
            throw new \InvalidArgumentException("Template not found: {$templateName}");
        }

        $template = $this->templates[$templateName];
        $config = array_merge($template, $overrides);
        $slug = $overrides['slug'] ?? Str::slug($config['name'] ?? $templateName) . '_' . Str::random(4);

        return $this->create($slug, $config);
    }

    // =========================================================================
    // NODE TYPE HANDLERS
    // =========================================================================

    public function registerNodeHandler(string $type, callable $handler): self
    {
        $this->nodeHandlers[$type] = $handler;
        return $this;
    }

    protected function executeActionNode(FlowNode $node, array $context): array
    {
        $connector = $node->config['connector'] ?? null;
        $action = $node->config['action'] ?? null;
        $connectionId = $node->config['connection_id'] ?? $context['connections'][$connector] ?? null;
        $input = $this->resolveNodeInput($node->config['input'] ?? [], $context);

        if (!$connector || !$action) {
            throw new \RuntimeException("Action node missing connector or action configuration");
        }

        // This will be called by ExecutionEngine which has access to ActionEngine
        return [
            '_execute_action' => true,
            'connector' => $connector,
            'action' => $action,
            'connection_id' => $connectionId,
            'input' => $input,
        ];
    }

    protected function evaluateConditionNode(FlowNode $node, array $context): array
    {
        $conditions = $node->config['conditions'] ?? [];
        $combineWith = $node->config['combine_with'] ?? 'and'; // 'and' or 'or'

        $results = [];
        foreach ($conditions as $condition) {
            $results[] = $this->evaluateCondition($condition, $context);
        }

        $result = $combineWith === 'and'
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);

        return [
            '_branch' => $result ? 'true' : 'false',
            'result' => $result,
        ];
    }

    protected function evaluateCondition(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        $actualValue = data_get($context['data'] ?? [], $field);

        return match ($operator) {
            '==' => $actualValue == $value,
            '===' => $actualValue === $value,
            '!=' => $actualValue != $value,
            '!==' => $actualValue !== $value,
            '>' => $actualValue > $value,
            '>=' => $actualValue >= $value,
            '<' => $actualValue < $value,
            '<=' => $actualValue <= $value,
            'contains' => str_contains((string)$actualValue, (string)$value),
            'not_contains' => !str_contains((string)$actualValue, (string)$value),
            'starts_with' => str_starts_with((string)$actualValue, (string)$value),
            'ends_with' => str_ends_with((string)$actualValue, (string)$value),
            'matches' => preg_match($value, (string)$actualValue) === 1,
            'in' => in_array($actualValue, (array)$value),
            'not_in' => !in_array($actualValue, (array)$value),
            'is_empty' => empty($actualValue),
            'is_not_empty' => !empty($actualValue),
            'is_null' => $actualValue === null,
            'is_not_null' => $actualValue !== null,
            default => false,
        };
    }

    protected function evaluateSwitchNode(FlowNode $node, array $context): array
    {
        $field = $node->config['field'] ?? '';
        $cases = $node->config['cases'] ?? [];
        $defaultBranch = $node->config['default'] ?? 'default';

        $value = data_get($context['data'] ?? [], $field);

        foreach ($cases as $case) {
            if ($value == $case['value']) {
                return ['_branch' => $case['branch'] ?? $case['value']];
            }
        }

        return ['_branch' => $defaultBranch];
    }

    protected function executeLoopNode(FlowNode $node, array $context): array
    {
        $arrayPath = $node->config['array_path'] ?? '';
        $itemVariable = $node->config['item_variable'] ?? 'item';
        $indexVariable = $node->config['index_variable'] ?? 'index';

        $array = data_get($context['data'] ?? [], $arrayPath, []);

        if (!is_array($array)) {
            $array = [$array];
        }

        return [
            '_loop' => true,
            'items' => $array,
            'item_variable' => $itemVariable,
            'index_variable' => $indexVariable,
        ];
    }

    protected function executeMergeNode(FlowNode $node, array $context): array
    {
        $mode = $node->config['mode'] ?? 'merge'; // 'merge', 'concat', 'zip'
        $inputs = $context['inputs'] ?? [];

        return match ($mode) {
            'merge' => array_merge(...$inputs),
            'concat' => array_values(array_merge(...$inputs)),
            'zip' => $this->zipArrays($inputs),
            default => $inputs[0] ?? [],
        };
    }

    protected function zipArrays(array $arrays): array
    {
        if (empty($arrays)) {
            return [];
        }

        $result = [];
        $maxLength = max(array_map('count', $arrays));

        for ($i = 0; $i < $maxLength; $i++) {
            $item = [];
            foreach ($arrays as $index => $array) {
                $item[$index] = $array[$i] ?? null;
            }
            $result[] = $item;
        }

        return $result;
    }

    protected function executeTransformNode(FlowNode $node, array $context): array
    {
        $mappings = $node->config['mappings'] ?? [];
        $result = [];

        foreach ($mappings as $mapping) {
            $source = $mapping['source'] ?? '';
            $target = $mapping['target'] ?? '';
            $transform = $mapping['transform'] ?? null;

            $value = data_get($context['data'] ?? [], $source);

            if ($transform) {
                $value = $this->applyTransform($value, $transform);
            }

            data_set($result, $target, $value);
        }

        return $result;
    }

    protected function applyTransform($value, array $transform)
    {
        $type = $transform['type'] ?? null;
        $params = $transform['params'] ?? [];

        return match ($type) {
            'uppercase' => strtoupper((string)$value),
            'lowercase' => strtolower((string)$value),
            'trim' => trim((string)$value),
            'split' => explode($params['delimiter'] ?? ',', (string)$value),
            'join' => implode($params['delimiter'] ?? ',', (array)$value),
            'replace' => str_replace($params['search'] ?? '', $params['replace'] ?? '', (string)$value),
            'date_format' => date($params['format'] ?? 'Y-m-d', strtotime((string)$value)),
            'number_format' => number_format((float)$value, $params['decimals'] ?? 2),
            'json_encode' => json_encode($value),
            'json_decode' => json_decode((string)$value, true),
            'default' => $value ?? ($params['value'] ?? null),
            default => $value,
        };
    }

    protected function executeFilterNode(FlowNode $node, array $context): array
    {
        $arrayPath = $node->config['array_path'] ?? '';
        $conditions = $node->config['conditions'] ?? [];

        $array = data_get($context['data'] ?? [], $arrayPath, []);

        if (!is_array($array)) {
            return [];
        }

        return array_values(array_filter($array, function ($item) use ($conditions) {
            foreach ($conditions as $condition) {
                if (!$this->evaluateCondition($condition, ['data' => $item])) {
                    return false;
                }
            }
            return true;
        }));
    }

    protected function executeSplitNode(FlowNode $node, array $context): array
    {
        $arrayPath = $node->config['array_path'] ?? '';
        $array = data_get($context['data'] ?? [], $arrayPath, []);

        return [
            '_split' => true,
            'items' => is_array($array) ? $array : [$array],
        ];
    }

    protected function executeWaitNode(FlowNode $node, array $context): array
    {
        $duration = $node->config['duration'] ?? 1000; // milliseconds
        $unit = $node->config['unit'] ?? 'ms';

        $ms = match ($unit) {
            's', 'seconds' => $duration * 1000,
            'm', 'minutes' => $duration * 60 * 1000,
            'h', 'hours' => $duration * 60 * 60 * 1000,
            default => $duration,
        };

        return [
            '_wait' => true,
            'duration_ms' => $ms,
            'resume_at' => now()->addMilliseconds($ms)->timestamp,
        ];
    }

    protected function executeHttpNode(FlowNode $node, array $context): array
    {
        return [
            '_http_request' => true,
            'url' => $this->resolveValue($node->config['url'] ?? '', $context),
            'method' => $node->config['method'] ?? 'GET',
            'headers' => $this->resolveNodeInput($node->config['headers'] ?? [], $context),
            'body' => $this->resolveNodeInput($node->config['body'] ?? [], $context),
            'query' => $this->resolveNodeInput($node->config['query'] ?? [], $context),
        ];
    }

    protected function executeCodeNode(FlowNode $node, array $context): array
    {
        // Code execution is handled by ExecutionEngine with sandboxing
        return [
            '_execute_code' => true,
            'code' => $node->config['code'] ?? '',
            'language' => $node->config['language'] ?? 'javascript',
        ];
    }

    protected function executeSetNode(FlowNode $node, array $context): array
    {
        $assignments = $node->config['assignments'] ?? [];
        $result = $context['data'] ?? [];

        foreach ($assignments as $assignment) {
            $key = $assignment['key'] ?? '';
            $value = $this->resolveValue($assignment['value'] ?? '', $context);
            data_set($result, $key, $value);
        }

        return $result;
    }

    protected function executeErrorNode(FlowNode $node, array $context): array
    {
        $action = $node->config['action'] ?? 'throw'; // 'throw', 'log', 'ignore'
        $message = $node->config['message'] ?? 'Flow error';

        return [
            '_error_handler' => true,
            'action' => $action,
            'message' => $this->resolveValue($message, $context),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function resolveNodeInput(array $input, array $context): array
    {
        $resolved = [];

        foreach ($input as $key => $value) {
            $resolved[$key] = $this->resolveValue($value, $context);
        }

        return $resolved;
    }

    protected function resolveValue($value, array $context)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Replace {{ variable }} patterns
        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) use ($context) {
            $path = trim($matches[1]);

            // Check different context sources
            if (str_starts_with($path, 'trigger.')) {
                return data_get($context['trigger_data'] ?? [], substr($path, 8), '');
            }

            if (str_starts_with($path, 'node.')) {
                $parts = explode('.', substr($path, 5), 2);
                $nodeId = $parts[0];
                $field = $parts[1] ?? '';
                return data_get($context['node_outputs'][$nodeId] ?? [], $field, '');
            }

            if (str_starts_with($path, 'config.')) {
                return config(substr($path, 7), '');
            }

            return data_get($context['data'] ?? [], $path, '');
        }, $value);
    }

    protected function generateLabel(string $name): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', $name));
    }

    // =========================================================================
    // QUERYING
    // =========================================================================

    public function list(array $filters = []): Collection
    {
        $query = Flow::with(['nodes', 'edges']);

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    public function export(string $flowId): array
    {
        $flow = Flow::with(['nodes', 'edges'])->findOrFail($flowId);

        return [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'flow' => [
                'slug' => $flow->slug,
                'name' => $flow->name,
                'description' => $flow->description,
                'trigger' => $flow->trigger_config,
                'settings' => $flow->settings,
                'nodes' => $flow->nodes->map(fn($n) => [
                    'node_id' => $n->node_id,
                    'type' => $n->type,
                    'name' => $n->name,
                    'config' => $n->config,
                    'position' => $n->position,
                ])->toArray(),
                'edges' => $flow->edges->map(fn($e) => [
                    'source_node' => $e->source_node,
                    'source_handle' => $e->source_handle,
                    'target_node' => $e->target_node,
                    'target_handle' => $e->target_handle,
                    'condition' => $e->condition,
                ])->toArray(),
            ],
        ];
    }

    public function import(array $data, ?string $tenantId = null): Flow
    {
        $flowData = $data['flow'] ?? $data;

        if ($tenantId) {
            $flowData['tenant_id'] = $tenantId;
        }

        return $this->create($flowData['slug'] ?? Str::random(8), $flowData);
    }
}
