/* ============================================
   Nodes Module - Node Rendering & Management
   ============================================ */

const Nodes = {
    // State
    nodes: {},
    selectedNodes: [],
    $nodesLayer: null,
    nodeTypes: {},
    
    // Initialize
    init: async function() {
        this.$nodesLayer = $('#nodes-layer');
        
        // Load node types
        const categories = await MockAPI.getNodeTypes();
        Object.values(categories).forEach(cat => {
            cat.nodes.forEach(nodeType => {
                this.nodeTypes[nodeType.type] = nodeType;
            });
        });
        
        this.bindEvents();
        
        console.log('Nodes initialized');
    },
    
    // Bind event handlers
    bindEvents: function() {
        // Node selection
        $(document).on('click', '.workflow-node', (e) => {
            const $node = $(e.currentTarget);
            const nodeId = $node.attr('id');
            
            if (e.ctrlKey || e.metaKey) {
                this.toggleNodeSelection(nodeId);
            } else if (!this.selectedNodes.includes(nodeId)) {
                this.selectNode(nodeId);
            }
        });
        
        // Double-click to open settings
        $(document).on('dblclick', '.workflow-node', (e) => {
            e.stopPropagation();
            const nodeId = $(e.currentTarget).attr('id');
            Utils.events.emit('node:open', { nodeId });
        });
        
        // Handle connections - mousedown on handle
        $(document).on('mousedown', '.node-handle', (e) => {
            e.stopPropagation();
            const $handle = $(e.currentTarget);
            const $node = $handle.closest('.workflow-node');
            const nodeId = $node.attr('id');
            const handleType = $handle.hasClass('node-handle-output') ? 'output' : 'input';
            const handleIndex = $handle.index('.node-handle-' + handleType);
            
            Connections.startConnection(nodeId, handleType, handleIndex);
        });
        
        // Handle connections - mouseup on handle
        $(document).on('mouseup', '.node-handle', (e) => {
            if (!Connections.isConnecting) return;
            
            const $handle = $(e.currentTarget);
            const $node = $handle.closest('.workflow-node');
            const nodeId = $node.attr('id');
            const handleType = $handle.hasClass('node-handle-output') ? 'output' : 'input';
            const handleIndex = $handle.index('.node-handle-' + handleType);
            
            Connections.completeConnection(nodeId, handleType, handleIndex);
        });
        
        // Track mouse for temp connection
        $(document).on('mousemove', (e) => {
            if (Connections.isConnecting) {
                Connections.updateTempConnection(e.clientX, e.clientY);
            }
        });
        
        // Cancel connection on mouseup outside handle
        $(document).on('mouseup', (e) => {
            if (Connections.isConnecting && !$(e.target).hasClass('node-handle')) {
                Connections.cancelConnection();
            }
        });
        
        // Context menu on node
        $(document).on('contextmenu', '.workflow-node', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const nodeId = $(e.currentTarget).attr('id');
            if (!this.selectedNodes.includes(nodeId)) {
                this.selectNode(nodeId);
            }
            
            Utils.events.emit('node:contextmenu', {
                nodeId: nodeId,
                x: e.clientX,
                y: e.clientY
            });
        });
        
        // Deselect on canvas click
        Utils.events.on('canvas:click', () => this.deselectAll());
        
        // Box selection
        Utils.events.on('canvas:selection', (rect) => this.selectNodesInRect(rect));
        
        // Node menu button
        $(document).on('click', '.node-menu-btn', (e) => {
            e.stopPropagation();
            const nodeId = $(e.currentTarget).closest('.workflow-node').attr('id');
            Utils.events.emit('node:menu', {
                nodeId: nodeId,
                x: e.clientX,
                y: e.clientY
            });
        });
    },
    
    // Create a new node
    createNode: function(type, position, data = {}) {
        const nodeType = this.nodeTypes[type];
        if (!nodeType) {
            console.error('Unknown node type:', type);
            return null;
        }
        
        const id = data.id || Utils.generateId();
        const name = data.name || nodeType.name;
        
        // Snap to grid
        const snappedPos = Canvas.snapToGrid(position.x, position.y);
        
        const node = {
            id: id,
            type: type,
            name: name,
            position: snappedPos,
            parameters: data.parameters || {},
            disabled: data.disabled || false
        };
        
        this.nodes[id] = node;
        this.renderNode(node);
        
        // Hide empty state
        $('#canvas-empty-state').addClass('hidden');
        
        Utils.events.emit('node:created', node);
        
        return node;
    },
    
    // Render a node
    renderNode: function(node) {
        const nodeType = this.nodeTypes[node.type] || {};
        
        // Build handle HTML
        let inputHandles = '';
        let outputHandles = '';
        
        // Input handles
        const inputs = nodeType.inputs || ['main'];
        inputs.forEach((input, i) => {
            inputHandles += `<div class="node-handle node-handle-input" data-handle="${input}" data-index="${i}"></div>`;
        });
        
        // Output handles
        const outputs = nodeType.outputs || ['main'];
        outputs.forEach((output, i) => {
            outputHandles += `<div class="node-handle node-handle-output" data-handle="${output}" data-index="${i}"></div>`;
        });
        
        // Determine if multi-handle
        const multiClass = (inputs.length > 1 ? 'multi-input ' : '') + (outputs.length > 1 ? 'multi-output' : '');
        
        // Build node HTML
        const html = `
            <div class="workflow-node ${node.disabled ? 'disabled' : ''} ${multiClass}" 
                 id="${node.id}" 
                 data-type="${node.type}"
                 data-category="${nodeType.category || 'action'}"
                 style="left: ${node.position.x}px; top: ${node.position.y}px;">
                
                ${inputHandles}
                
                <div class="node-header">
                    <div class="node-icon">
                        <i class="${nodeType.icon || 'lucide-box'}"></i>
                    </div>
                    <span class="node-title">${Utils.escapeHtml(node.name)}</span>
                    <button class="node-menu-btn" title="Options">
                        <i class="lucide-more-vertical"></i>
                    </button>
                </div>
                
                ${this.getNodeContentHtml(node, nodeType)}
                
                ${outputHandles}
            </div>
        `;
        
        // Remove existing and add new
        $(`#${node.id}`).remove();
        this.$nodesLayer.append(html);
        
        // Initialize drag
        DragDrop.initNodeDrag($(`#${node.id}`));
    },
    
    // Get node content HTML based on parameters
    getNodeContentHtml: function(node, nodeType) {
        // Get subtitle/preview from parameters
        let subtitle = '';
        
        if (node.type === 'http.request' && node.parameters.url) {
            subtitle = `${node.parameters.method || 'GET'} ${node.parameters.url}`;
        } else if (node.type === 'trigger.webhook' && node.parameters.path) {
            subtitle = `${node.parameters.httpMethod || 'POST'} ${node.parameters.path}`;
        } else if (node.type === 'email.send' && node.parameters.to) {
            subtitle = `To: ${node.parameters.to}`;
        } else if (node.type === 'slack.message' && node.parameters.channel) {
            subtitle = `Channel: ${node.parameters.channel}`;
        }
        
        if (subtitle) {
            return `
                <div class="node-content">
                    <div class="node-subtitle">${Utils.escapeHtml(subtitle)}</div>
                </div>
            `;
        }
        
        return '';
    },
    
    // Update node
    updateNode: function(nodeId, updates) {
        const node = this.nodes[nodeId];
        if (!node) return;
        
        Object.assign(node, updates);
        this.renderNode(node);
        
        // Update connections
        Connections.updateNodeConnections(nodeId);
        
        Utils.events.emit('node:updated', node);
    },
    
    // Update node position
    updateNodePosition: function(nodeId, x, y) {
        const node = this.nodes[nodeId];
        if (!node) return;
        
        node.position = { x, y };
        $(`#${nodeId}`).css({ left: x, top: y });
        
        Utils.events.emit('node:moved', { nodeId, position: node.position });
    },
    
    // Delete node
    deleteNode: function(nodeId) {
        const node = this.nodes[nodeId];
        if (!node) return;
        
        // Remove from DOM
        $(`#${nodeId}`).remove();
        
        // Remove from state
        delete this.nodes[nodeId];
        this.selectedNodes = this.selectedNodes.filter(id => id !== nodeId);
        
        // Remove connections
        Connections.removeNodeConnections(nodeId);
        
        // Show empty state if no nodes
        if (Object.keys(this.nodes).length === 0) {
            $('#canvas-empty-state').removeClass('hidden');
        }
        
        Utils.events.emit('node:deleted', { nodeId });
    },
    
    // Delete selected nodes
    deleteSelectedNodes: function() {
        const toDelete = [...this.selectedNodes];
        toDelete.forEach(nodeId => this.deleteNode(nodeId));
    },
    
    // Select node
    selectNode: function(nodeId, addToSelection = false) {
        if (!addToSelection) {
            this.deselectAll();
        }
        
        if (!this.selectedNodes.includes(nodeId)) {
            this.selectedNodes.push(nodeId);
        }
        
        $(`#${nodeId}`).addClass('selected');
        
        Utils.events.emit('node:selected', { nodeId, selectedNodes: this.selectedNodes });
    },
    
    // Toggle node selection
    toggleNodeSelection: function(nodeId) {
        if (this.selectedNodes.includes(nodeId)) {
            this.selectedNodes = this.selectedNodes.filter(id => id !== nodeId);
            $(`#${nodeId}`).removeClass('selected');
        } else {
            this.selectedNodes.push(nodeId);
            $(`#${nodeId}`).addClass('selected');
        }
        
        Utils.events.emit('node:selected', { nodeId, selectedNodes: this.selectedNodes });
    },
    
    // Deselect all nodes
    deselectAll: function() {
        this.selectedNodes.forEach(nodeId => {
            $(`#${nodeId}`).removeClass('selected');
        });
        this.selectedNodes = [];
        
        Connections.deselectAll();
        
        Utils.events.emit('node:deselected');
    },
    
    // Select nodes in rectangle (screen coordinates)
    selectNodesInRect: function(rect) {
        this.deselectAll();
        
        Object.values(this.nodes).forEach(node => {
            const $node = $(`#${node.id}`);
            const nodeRect = $node[0].getBoundingClientRect();
            
            if (Utils.rectsOverlap(rect, {
                left: nodeRect.left,
                top: nodeRect.top,
                right: nodeRect.right,
                bottom: nodeRect.bottom
            })) {
                this.selectNode(node.id, true);
            }
        });
    },
    
    // Get selected nodes
    getSelectedNodes: function() {
        return this.selectedNodes.map(id => this.nodes[id]).filter(Boolean);
    },
    
    // Get node by ID
    getNode: function(nodeId) {
        return this.nodes[nodeId];
    },
    
    // Get all nodes
    getAllNodes: function() {
        return Object.values(this.nodes);
    },
    
    // Load nodes from workflow data
    loadNodes: function(nodesData) {
        // Clear existing
        this.nodes = {};
        this.selectedNodes = [];
        this.$nodesLayer.empty();
        
        // Create nodes
        nodesData.forEach(nodeData => {
            this.createNode(nodeData.type, nodeData.position, {
                id: nodeData.id,
                name: nodeData.name,
                parameters: nodeData.parameters,
                disabled: nodeData.disabled
            });
        });
        
        // Show/hide empty state
        if (nodesData.length === 0) {
            $('#canvas-empty-state').removeClass('hidden');
        } else {
            $('#canvas-empty-state').addClass('hidden');
        }
    },
    
    // Export nodes for saving
    exportNodes: function() {
        return Object.values(this.nodes).map(node => ({
            id: node.id,
            type: node.type,
            name: node.name,
            position: node.position,
            parameters: node.parameters,
            disabled: node.disabled
        }));
    },
    
    // Copy selected nodes
    copyNodes: function() {
        return this.getSelectedNodes().map(node => Utils.deepClone(node));
    },
    
    // Paste nodes
    pasteNodes: function(nodesData, offset = { x: 40, y: 40 }) {
        this.deselectAll();
        
        const idMap = {}; // Map old IDs to new IDs
        
        nodesData.forEach(nodeData => {
            const newNode = this.createNode(nodeData.type, {
                x: nodeData.position.x + offset.x,
                y: nodeData.position.y + offset.y
            }, {
                name: nodeData.name,
                parameters: nodeData.parameters
            });
            
            if (newNode) {
                idMap[nodeData.id] = newNode.id;
                this.selectNode(newNode.id, true);
            }
        });
        
        return idMap;
    },
    
    // Duplicate selected nodes
    duplicateSelectedNodes: function() {
        const copied = this.copyNodes();
        return this.pasteNodes(copied);
    },
    
    // Set node disabled state
    setNodeDisabled: function(nodeId, disabled) {
        this.updateNode(nodeId, { disabled });
    },
    
    // Set node execution state (for visualization)
    setNodeState: function(nodeId, state) {
        const $node = $(`#${nodeId}`);
        $node.removeClass('running success error');
        
        if (state) {
            $node.addClass(state);
        }
    }
};

// Make available globally
window.Nodes = Nodes;
