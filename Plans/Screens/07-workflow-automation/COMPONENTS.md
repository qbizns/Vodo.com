# Workflow & Automation - UI Components

## Component Structure

```
resources/views/components/workflow/
├── canvas.blade.php
├── node.blade.php
├── node-palette.blade.php
├── node-editor.blade.php
├── connection.blade.php
├── minimap.blade.php
├── execution-timeline.blade.php
├── step-inspector.blade.php
└── variable-picker.blade.php
```

---

## Core Components

### Workflow Canvas (React/Vue Component)

```jsx
// resources/js/components/workflow/WorkflowCanvas.jsx
import React, { useCallback, useState, useRef } from 'react';
import { useWorkflowStore } from './stores/workflowStore';

export function WorkflowCanvas() {
    const canvasRef = useRef(null);
    const { nodes, connections, selectedNode, setSelectedNode } = useWorkflowStore();
    const [transform, setTransform] = useState({ x: 0, y: 0, scale: 1 });
    const [isDragging, setIsDragging] = useState(false);

    const handleDrop = useCallback((e) => {
        e.preventDefault();
        const nodeType = JSON.parse(e.dataTransfer.getData('application/json'));
        const rect = canvasRef.current.getBoundingClientRect();
        const x = (e.clientX - rect.left - transform.x) / transform.scale;
        const y = (e.clientY - rect.top - transform.y) / transform.scale;
        
        addNode({
            ...nodeType,
            key: `${nodeType.id}_${Date.now()}`,
            position: { x, y },
            config: nodeType.defaultConfig || {},
        });
    }, [transform]);

    const handleWheel = useCallback((e) => {
        if (e.ctrlKey) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            setTransform(t => ({
                ...t,
                scale: Math.min(Math.max(t.scale * delta, 0.25), 2),
            }));
        }
    }, []);

    return (
        <div 
            ref={canvasRef}
            className="workflow-canvas"
            onDrop={handleDrop}
            onDragOver={(e) => e.preventDefault()}
            onWheel={handleWheel}
        >
            <svg className="workflow-canvas__connections">
                <g transform={`translate(${transform.x}, ${transform.y}) scale(${transform.scale})`}>
                    {connections.map((conn) => (
                        <WorkflowConnection key={conn.id} connection={conn} />
                    ))}
                </g>
            </svg>
            
            <div 
                className="workflow-canvas__nodes"
                style={{
                    transform: `translate(${transform.x}px, ${transform.y}px) scale(${transform.scale})`,
                }}
            >
                {nodes.map((node) => (
                    <WorkflowNode
                        key={node.key}
                        node={node}
                        selected={selectedNode?.key === node.key}
                        onSelect={() => setSelectedNode(node)}
                    />
                ))}
            </div>
            
            <WorkflowMinimap 
                nodes={nodes}
                transform={transform}
                onNavigate={setTransform}
            />
            
            <div className="workflow-canvas__controls">
                <button onClick={() => setTransform(t => ({ ...t, scale: t.scale * 1.2 }))}>+</button>
                <span>{Math.round(transform.scale * 100)}%</span>
                <button onClick={() => setTransform(t => ({ ...t, scale: t.scale * 0.8 }))}>−</button>
                <button onClick={() => setTransform({ x: 0, y: 0, scale: 1 })}>Reset</button>
            </div>
        </div>
    );
}
```

### Workflow Node Component

```jsx
// resources/js/components/workflow/WorkflowNode.jsx
import React, { useRef, useState } from 'react';
import { useDrag } from './hooks/useDrag';

export function WorkflowNode({ node, selected, onSelect, onConnect }) {
    const nodeRef = useRef(null);
    const [isConnecting, setIsConnecting] = useState(false);
    
    const { position, isDragging } = useDrag(nodeRef, node.position, (newPos) => {
        updateNodePosition(node.key, newPos);
    });

    const nodeDefinition = getNodeDefinition(node.type);

    return (
        <div
            ref={nodeRef}
            className={`workflow-node workflow-node--${node.type} ${selected ? 'workflow-node--selected' : ''}`}
            style={{
                left: position.x,
                top: position.y,
                opacity: isDragging ? 0.7 : 1,
            }}
            onClick={(e) => {
                e.stopPropagation();
                onSelect();
            }}
        >
            {/* Input Connector */}
            {nodeDefinition.hasInput && (
                <div 
                    className="workflow-node__connector workflow-node__connector--input"
                    onMouseUp={() => handleConnectionEnd(node.key, 'input')}
                />
            )}
            
            {/* Node Header */}
            <div className="workflow-node__header" style={{ backgroundColor: nodeDefinition.color }}>
                <span className="workflow-node__icon">{nodeDefinition.icon}</span>
                <span className="workflow-node__type">{nodeDefinition.name}</span>
            </div>
            
            {/* Node Body */}
            <div className="workflow-node__body">
                <span className="workflow-node__label">{node.label || nodeDefinition.name}</span>
                {node.config?.summary && (
                    <span className="workflow-node__summary">{node.config.summary}</span>
                )}
            </div>
            
            {/* Execution Status */}
            {node.executionStatus && (
                <div className={`workflow-node__status workflow-node__status--${node.executionStatus}`}>
                    {node.executionStatus === 'running' && <Spinner />}
                    {node.executionStatus === 'success' && '✓'}
                    {node.executionStatus === 'error' && '✗'}
                </div>
            )}
            
            {/* Output Connectors */}
            {nodeDefinition.outputs.map((output, index) => (
                <div 
                    key={output.name}
                    className="workflow-node__connector workflow-node__connector--output"
                    style={{ top: `${30 + index * 20}px` }}
                    onMouseDown={() => handleConnectionStart(node.key, output.name)}
                >
                    {nodeDefinition.outputs.length > 1 && (
                        <span className="workflow-node__connector-label">{output.label}</span>
                    )}
                </div>
            ))}
        </div>
    );
}
```

### Node Editor Panel

```jsx
// resources/js/components/workflow/NodeEditor.jsx
import React from 'react';
import { useWorkflowStore } from './stores/workflowStore';

export function NodeEditor({ node, onClose }) {
    const { updateNode } = useWorkflowStore();
    const nodeDefinition = getNodeDefinition(node.type);
    const [config, setConfig] = useState(node.config);
    const [activeTab, setActiveTab] = useState('general');

    const handleSave = () => {
        updateNode(node.key, { config });
        onClose();
    };

    return (
        <div className="node-editor">
            <div className="node-editor__header">
                <h3>Configure: {nodeDefinition.name}</h3>
                <button onClick={onClose}>×</button>
            </div>
            
            <div className="node-editor__tabs">
                <button 
                    className={activeTab === 'general' ? 'active' : ''}
                    onClick={() => setActiveTab('general')}
                >
                    General
                </button>
                <button 
                    className={activeTab === 'advanced' ? 'active' : ''}
                    onClick={() => setActiveTab('advanced')}
                >
                    Advanced
                </button>
                <button 
                    className={activeTab === 'errors' ? 'active' : ''}
                    onClick={() => setActiveTab('errors')}
                >
                    Error Handling
                </button>
            </div>
            
            <div className="node-editor__content">
                {activeTab === 'general' && (
                    <div className="node-editor__fields">
                        <div className="form-group">
                            <label>Label</label>
                            <input 
                                type="text"
                                value={config.label || ''}
                                onChange={(e) => setConfig({ ...config, label: e.target.value })}
                                placeholder={nodeDefinition.name}
                            />
                        </div>
                        
                        {nodeDefinition.configSchema.map((field) => (
                            <NodeConfigField
                                key={field.key}
                                field={field}
                                value={config[field.key]}
                                onChange={(value) => setConfig({ ...config, [field.key]: value })}
                            />
                        ))}
                    </div>
                )}
                
                {activeTab === 'errors' && (
                    <div className="node-editor__errors">
                        <div className="form-group">
                            <label>On Error</label>
                            <select 
                                value={config.onError || 'stop'}
                                onChange={(e) => setConfig({ ...config, onError: e.target.value })}
                            >
                                <option value="stop">Stop Workflow</option>
                                <option value="continue">Continue to Next</option>
                                <option value="retry">Retry</option>
                            </select>
                        </div>
                        
                        {config.onError === 'retry' && (
                            <>
                                <div className="form-group">
                                    <label>Retry Attempts</label>
                                    <input 
                                        type="number"
                                        value={config.retryAttempts || 3}
                                        onChange={(e) => setConfig({ ...config, retryAttempts: parseInt(e.target.value) })}
                                        min="1"
                                        max="10"
                                    />
                                </div>
                                <div className="form-group">
                                    <label>Retry Delay (seconds)</label>
                                    <input 
                                        type="number"
                                        value={config.retryDelay || 5}
                                        onChange={(e) => setConfig({ ...config, retryDelay: parseInt(e.target.value) })}
                                        min="1"
                                    />
                                </div>
                            </>
                        )}
                    </div>
                )}
            </div>
            
            <div className="node-editor__footer">
                <button className="btn btn-secondary" onClick={onClose}>Cancel</button>
                <button className="btn btn-primary" onClick={handleSave}>Apply</button>
            </div>
        </div>
    );
}
```

### Variable Picker Component

```jsx
// resources/js/components/workflow/VariablePicker.jsx
export function VariablePicker({ onSelect, availableVariables }) {
    const [search, setSearch] = useState('');
    
    const categories = [
        {
            name: 'Trigger Data',
            prefix: 'trigger',
            variables: availableVariables.trigger || [],
        },
        {
            name: 'Previous Steps',
            prefix: 'steps',
            variables: availableVariables.steps || [],
        },
        {
            name: 'System',
            prefix: 'system',
            variables: [
                { key: 'now', label: 'Current DateTime' },
                { key: 'today', label: 'Today\'s Date' },
                { key: 'user.id', label: 'Current User ID' },
                { key: 'user.name', label: 'Current User Name' },
            ],
        },
    ];

    return (
        <div className="variable-picker">
            <div className="variable-picker__search">
                <input 
                    type="text"
                    placeholder="Search variables..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </div>
            
            <div className="variable-picker__categories">
                {categories.map((category) => (
                    <div key={category.prefix} className="variable-picker__category">
                        <h4>{category.name}</h4>
                        <ul>
                            {category.variables
                                .filter(v => v.label.toLowerCase().includes(search.toLowerCase()))
                                .map((variable) => (
                                    <li 
                                        key={variable.key}
                                        onClick={() => onSelect(`{{${category.prefix}.${variable.key}}}`)}
                                    >
                                        <code>{`{{${category.prefix}.${variable.key}}}`}</code>
                                        <span>{variable.label}</span>
                                    </li>
                                ))
                            }
                        </ul>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

### Step Inspector Component

```blade
{{-- resources/views/components/workflow/step-inspector.blade.php --}}
@props(['step'])

<div class="step-inspector" x-data="{ activeTab: 'input' }">
    <div class="step-inspector__header">
        <h4>{{ $step->node->label ?? $step->node->type }}</h4>
        <span class="step-inspector__status step-inspector__status--{{ $step->status }}">
            {{ ucfirst($step->status) }}
        </span>
    </div>
    
    <div class="step-inspector__meta">
        <span>Duration: {{ $step->duration_ms }}ms</span>
        <span>Started: {{ $step->started_at->format('H:i:s.v') }}</span>
    </div>
    
    <div class="step-inspector__tabs">
        <button @click="activeTab = 'input'" :class="{ active: activeTab === 'input' }">
            Input
        </button>
        <button @click="activeTab = 'output'" :class="{ active: activeTab === 'output' }">
            Output
        </button>
        @if($step->error)
            <button @click="activeTab = 'error'" :class="{ active: activeTab === 'error' }">
                Error
            </button>
        @endif
    </div>
    
    <div class="step-inspector__content">
        <div x-show="activeTab === 'input'">
            <x-json-viewer :data="$step->input_data" />
        </div>
        
        <div x-show="activeTab === 'output'">
            <x-json-viewer :data="$step->output_data" />
        </div>
        
        @if($step->error)
            <div x-show="activeTab === 'error'" class="step-inspector__error">
                <pre>{{ $step->error }}</pre>
            </div>
        @endif
    </div>
</div>
```

---

## Tailwind Styles

```css
/* Workflow Canvas */
.workflow-canvas {
    @apply relative w-full h-full bg-gray-50 overflow-hidden;
    background-image: radial-gradient(circle, #ddd 1px, transparent 1px);
    background-size: 20px 20px;
}

.workflow-canvas__controls {
    @apply absolute bottom-4 right-4 flex items-center gap-2 bg-white rounded-lg shadow px-3 py-2;
}

/* Workflow Node */
.workflow-node {
    @apply absolute bg-white rounded-lg shadow-md border-2 border-gray-200 min-w-[180px] cursor-move;
}
.workflow-node--selected {
    @apply border-primary-500 shadow-lg;
}
.workflow-node__header {
    @apply flex items-center gap-2 px-3 py-2 rounded-t-md text-white text-sm font-medium;
}
.workflow-node__body {
    @apply px-3 py-2;
}
.workflow-node__connector {
    @apply absolute w-3 h-3 rounded-full bg-gray-400 border-2 border-white cursor-crosshair;
}
.workflow-node__connector--input {
    @apply -top-1.5 left-1/2 -translate-x-1/2;
}
.workflow-node__connector--output {
    @apply -bottom-1.5 left-1/2 -translate-x-1/2;
}
.workflow-node__connector:hover {
    @apply bg-primary-500;
}

/* Node Editor */
.node-editor {
    @apply fixed right-0 top-0 h-full w-96 bg-white shadow-xl border-l z-50;
}
.node-editor__header {
    @apply flex justify-between items-center px-4 py-3 border-b;
}
.node-editor__tabs {
    @apply flex border-b;
}
.node-editor__tabs button {
    @apply px-4 py-2 text-sm text-gray-600 hover:text-gray-900;
}
.node-editor__tabs button.active {
    @apply text-primary-600 border-b-2 border-primary-600;
}
.node-editor__content {
    @apply p-4 overflow-y-auto;
    height: calc(100% - 140px);
}
.node-editor__footer {
    @apply absolute bottom-0 left-0 right-0 p-4 border-t bg-gray-50 flex justify-end gap-2;
}

/* Step Inspector */
.step-inspector {
    @apply bg-white rounded-lg border p-4;
}
.step-inspector__status--success {
    @apply text-green-600;
}
.step-inspector__status--error {
    @apply text-red-600;
}
.step-inspector__error {
    @apply bg-red-50 text-red-800 p-3 rounded font-mono text-sm overflow-x-auto;
}
```
