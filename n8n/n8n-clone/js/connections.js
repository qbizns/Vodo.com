/* ============================================
   Connections Module - SVG Bezier Curves
   ============================================ */

const Connections = {
    // State
    $svg: null,
    $group: null,
    $tempConnection: null,
    connections: [],
    isConnecting: false,
    connectingFrom: null,
    
    // Initialize connections layer
    init: function() {
        this.$svg = $('#connections-layer');
        this.$group = $('#connections-group');
        this.$tempConnection = $('#temp-connection');
        
        this.bindEvents();
        
        console.log('Connections initialized');
    },
    
    // Bind event handlers
    bindEvents: function() {
        // Listen for node position changes
        Utils.events.on('node:moved', (data) => this.updateNodeConnections(data.nodeId));
        Utils.events.on('node:deleted', (data) => this.removeNodeConnections(data.nodeId));
        Utils.events.on('canvas:transform', () => this.updateAllConnections());
        
        // Connection hover effects
        $(document).on('mouseenter', '.connection:not(.temp)', function() {
            $(this).addClass('hovered');
        });
        
        $(document).on('mouseleave', '.connection:not(.temp)', function() {
            $(this).removeClass('hovered');
        });
        
        // Connection click to select/delete
        $(document).on('click', '.connection:not(.temp)', (e) => {
            e.stopPropagation();
            const connectionId = $(e.target).attr('data-connection-id');
            this.selectConnection(connectionId);
        });
    },
    
    // Start creating a new connection
    startConnection: function(nodeId, handleType, handleIndex = 0) {
        this.isConnecting = true;
        this.connectingFrom = {
            nodeId: nodeId,
            handleType: handleType,
            handleIndex: handleIndex
        };
        
        $('#canvas-container').addClass('is-connecting');
        
        // Show valid targets
        this.highlightValidTargets(nodeId, handleType);
        
        Utils.events.emit('connection:start', this.connectingFrom);
    },
    
    // Update temp connection line while dragging
    updateTempConnection: function(mouseX, mouseY) {
        if (!this.isConnecting || !this.connectingFrom) return;
        
        const $sourceNode = $(`#${this.connectingFrom.nodeId}`);
        if ($sourceNode.length === 0) return;
        
        // Get source handle position
        const sourcePos = this.getHandlePosition($sourceNode, this.connectingFrom.handleType, this.connectingFrom.handleIndex);
        
        // Convert mouse position to SVG coordinates
        const transform = Canvas.getTransform();
        const containerRect = $('#canvas-container')[0].getBoundingClientRect();
        
        const targetX = (mouseX - containerRect.left - transform.x) / transform.scale;
        const targetY = (mouseY - containerRect.top - transform.y) / transform.scale;
        
        // Draw bezier curve
        let path;
        if (this.connectingFrom.handleType === 'output') {
            path = Utils.getBezierPath(sourcePos.x, sourcePos.y, targetX, targetY);
        } else {
            path = Utils.getBezierPath(targetX, targetY, sourcePos.x, sourcePos.y);
        }
        
        this.$tempConnection.attr('d', path);
    },
    
    // Complete connection
    completeConnection: function(targetNodeId, targetHandleType, targetHandleIndex = 0) {
        if (!this.isConnecting || !this.connectingFrom) return;
        
        // Validate connection
        if (!this.isValidConnection(this.connectingFrom.nodeId, targetNodeId, this.connectingFrom.handleType, targetHandleType)) {
            this.cancelConnection();
            return;
        }
        
        // Create connection
        const connection = {
            id: 'conn_' + Date.now(),
            source: this.connectingFrom.handleType === 'output' ? this.connectingFrom.nodeId : targetNodeId,
            sourceHandle: this.connectingFrom.handleType === 'output' ? `output_${this.connectingFrom.handleIndex}` : `output_${targetHandleIndex}`,
            target: this.connectingFrom.handleType === 'output' ? targetNodeId : this.connectingFrom.nodeId,
            targetHandle: this.connectingFrom.handleType === 'output' ? `input_${targetHandleIndex}` : `input_${this.connectingFrom.handleIndex}`
        };
        
        // Check for duplicate
        const exists = this.connections.some(c => 
            c.source === connection.source && 
            c.target === connection.target &&
            c.sourceHandle === connection.sourceHandle &&
            c.targetHandle === connection.targetHandle
        );
        
        if (!exists) {
            this.addConnection(connection);
            Utils.events.emit('connection:created', connection);
        }
        
        this.cancelConnection();
    },
    
    // Cancel connection creation
    cancelConnection: function() {
        this.isConnecting = false;
        this.connectingFrom = null;
        
        this.$tempConnection.attr('d', '');
        $('#canvas-container').removeClass('is-connecting');
        
        // Remove highlights
        $('.node-handle').removeClass('valid-target invalid-target');
        
        Utils.events.emit('connection:cancelled');
    },
    
    // Check if connection is valid
    isValidConnection: function(sourceId, targetId, sourceHandleType, targetHandleType) {
        // Can't connect to self
        if (sourceId === targetId) return false;
        
        // Must connect output to input
        if (sourceHandleType === targetHandleType) return false;
        
        // Check for existing connection
        const wouldBeSource = sourceHandleType === 'output' ? sourceId : targetId;
        const wouldBeTarget = sourceHandleType === 'output' ? targetId : sourceId;
        
        // Could add more validation (cycles, etc.)
        
        return true;
    },
    
    // Highlight valid connection targets
    highlightValidTargets: function(sourceNodeId, sourceHandleType) {
        const targetHandleClass = sourceHandleType === 'output' ? '.node-handle-input' : '.node-handle-output';
        
        $(targetHandleClass).each((i, handle) => {
            const $node = $(handle).closest('.workflow-node');
            const nodeId = $node.attr('id');
            
            if (nodeId !== sourceNodeId) {
                $(handle).addClass('valid-target');
            } else {
                $(handle).addClass('invalid-target');
            }
        });
    },
    
    // Add a connection
    addConnection: function(connection) {
        this.connections.push(connection);
        this.renderConnection(connection);
    },
    
    // Render single connection
    renderConnection: function(connection) {
        const $source = $(`#${connection.source}`);
        const $target = $(`#${connection.target}`);
        
        if ($source.length === 0 || $target.length === 0) return;
        
        const sourcePos = this.getHandlePosition($source, 'output', this.getHandleIndex(connection.sourceHandle));
        const targetPos = this.getHandlePosition($target, 'input', this.getHandleIndex(connection.targetHandle));
        
        const path = Utils.getBezierPath(sourcePos.x, sourcePos.y, targetPos.x, targetPos.y);
        
        // Remove existing if any
        this.$group.find(`[data-connection-id="${connection.id}"]`).remove();
        
        // Create SVG path
        const $path = $(document.createElementNS('http://www.w3.org/2000/svg', 'path'));
        $path.attr({
            'd': path,
            'class': 'connection',
            'data-connection-id': connection.id,
            'data-source': connection.source,
            'data-target': connection.target
        });
        
        this.$group.append($path);
    },
    
    // Get handle index from handle name
    getHandleIndex: function(handleName) {
        const match = handleName.match(/_(\d+)$/);
        return match ? parseInt(match[1]) : 0;
    },
    
    // Get handle position in canvas coordinates
    getHandlePosition: function($node, handleType, handleIndex = 0) {
        const nodePos = {
            x: parseFloat($node.css('left')),
            y: parseFloat($node.css('top'))
        };
        const nodeWidth = $node.outerWidth();
        const nodeHeight = $node.outerHeight();
        
        // Default single handle at center
        let handleY = nodePos.y + nodeHeight / 2;
        
        // For multiple handles, distribute them
        const $handles = $node.find(handleType === 'output' ? '.node-handle-output' : '.node-handle-input');
        if ($handles.length > 1 && handleIndex < $handles.length) {
            const spacing = nodeHeight / ($handles.length + 1);
            handleY = nodePos.y + spacing * (handleIndex + 1);
        }
        
        if (handleType === 'output') {
            return { x: nodePos.x + nodeWidth, y: handleY };
        } else {
            return { x: nodePos.x, y: handleY };
        }
    },
    
    // Update connections for a specific node
    updateNodeConnections: function(nodeId) {
        this.connections.forEach(conn => {
            if (conn.source === nodeId || conn.target === nodeId) {
                this.renderConnection(conn);
            }
        });
    },
    
    // Update all connections
    updateAllConnections: function() {
        this.connections.forEach(conn => this.renderConnection(conn));
    },
    
    // Remove connections for a node
    removeNodeConnections: function(nodeId) {
        this.connections = this.connections.filter(conn => {
            if (conn.source === nodeId || conn.target === nodeId) {
                this.$group.find(`[data-connection-id="${conn.id}"]`).remove();
                return false;
            }
            return true;
        });
    },
    
    // Remove specific connection
    removeConnection: function(connectionId) {
        this.connections = this.connections.filter(conn => {
            if (conn.id === connectionId) {
                this.$group.find(`[data-connection-id="${conn.id}"]`).remove();
                return false;
            }
            return true;
        });
        
        Utils.events.emit('connection:deleted', { connectionId });
    },
    
    // Select a connection
    selectConnection: function(connectionId) {
        // Deselect all
        this.$group.find('.connection').removeClass('selected');
        
        // Select this one
        const $conn = this.$group.find(`[data-connection-id="${connectionId}"]`);
        $conn.addClass('selected');
        
        Utils.events.emit('connection:selected', { connectionId });
    },
    
    // Deselect all connections
    deselectAll: function() {
        this.$group.find('.connection').removeClass('selected');
    },
    
    // Get connections for a node
    getNodeConnections: function(nodeId) {
        return this.connections.filter(conn => 
            conn.source === nodeId || conn.target === nodeId
        );
    },
    
    // Set connections (used when loading workflow)
    setConnections: function(connections) {
        // Clear existing
        this.connections = [];
        this.$group.empty();
        
        // Add new
        connections.forEach(conn => {
            // Ensure connection has an ID
            if (!conn.id) {
                conn.id = 'conn_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }
            this.addConnection(conn);
        });
    },
    
    // Get all connections
    getConnections: function() {
        return this.connections.map(c => ({
            source: c.source,
            sourceHandle: c.sourceHandle || 'main',
            target: c.target,
            targetHandle: c.targetHandle || 'main'
        }));
    },
    
    // Animate connection (for execution visualization)
    animateConnection: function(connectionId, status = 'running') {
        const $conn = this.$group.find(`[data-connection-id="${connectionId}"]`);
        
        if (status === 'running') {
            $conn.addClass('animated');
        } else {
            $conn.removeClass('animated');
            $conn.addClass(status); // 'success' or 'error'
            
            // Remove status class after delay
            setTimeout(() => {
                $conn.removeClass(status);
            }, 2000);
        }
    }
};

// Make available globally
window.Connections = Connections;
