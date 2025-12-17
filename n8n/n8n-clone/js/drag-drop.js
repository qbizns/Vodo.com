/* ============================================
   Drag & Drop Module
   ============================================ */

const DragDrop = {
    // State
    isDragging: false,
    draggedType: null,
    $dragPreview: null,
    
    // Initialize
    init: function() {
        this.createDragPreview();
        this.bindGlobalEvents();
        
        console.log('DragDrop initialized');
    },
    
    // Create drag preview element
    createDragPreview: function() {
        this.$dragPreview = $('<div class="drag-preview"></div>').appendTo('body');
    },
    
    // Bind global drag events
    bindGlobalEvents: function() {
        // Track mouse position for drag preview
        $(document).on('mousemove', (e) => {
            if (this.isDragging && this.$dragPreview.is(':visible')) {
                this.$dragPreview.css({
                    left: e.clientX,
                    top: e.clientY
                });
            }
        });
        
        // Cancel drag on escape
        $(document).on('keydown', (e) => {
            if (e.key === 'Escape' && this.isDragging) {
                this.cancelDrag();
            }
        });
    },
    
    // Initialize palette item dragging
    initPaletteDrag: function() {
        const self = this;
        
        $('.node-palette-item').each(function() {
            const $item = $(this);
            
            $item.on('mousedown', function(e) {
                if (e.button !== 0) return; // Left click only
                
                e.preventDefault();
                
                const type = $item.data('type');
                const nodeType = Nodes.nodeTypes[type];
                
                if (!nodeType) return;
                
                // Start drag after small movement
                let startX = e.clientX;
                let startY = e.clientY;
                let hasMoved = false;
                
                const onMouseMove = (moveEvent) => {
                    const dx = moveEvent.clientX - startX;
                    const dy = moveEvent.clientY - startY;
                    
                    if (!hasMoved && (Math.abs(dx) > 5 || Math.abs(dy) > 5)) {
                        hasMoved = true;
                        self.startPaletteDrag(type, nodeType, moveEvent.clientX, moveEvent.clientY);
                    }
                    
                    if (hasMoved) {
                        self.updatePaletteDrag(moveEvent.clientX, moveEvent.clientY);
                    }
                };
                
                const onMouseUp = (upEvent) => {
                    $(document).off('mousemove', onMouseMove);
                    $(document).off('mouseup', onMouseUp);
                    
                    if (hasMoved) {
                        self.endPaletteDrag(upEvent.clientX, upEvent.clientY);
                    }
                };
                
                $(document).on('mousemove', onMouseMove);
                $(document).on('mouseup', onMouseUp);
            });
        });
    },
    
    // Start dragging from palette
    startPaletteDrag: function(type, nodeType, x, y) {
        this.isDragging = true;
        this.draggedType = type;
        
        // Create preview
        this.$dragPreview.html(`
            <div class="workflow-node" style="position: static; pointer-events: none;">
                <div class="node-header">
                    <div class="node-icon">
                        <i class="${nodeType.icon}"></i>
                    </div>
                    <span class="node-title">${nodeType.name}</span>
                </div>
            </div>
        `).css({
            left: x,
            top: y,
            display: 'block'
        });
        
        // Add class to canvas for drop zone styling
        $('#canvas-container').addClass('drag-over');
    },
    
    // Update palette drag position
    updatePaletteDrag: function(x, y) {
        this.$dragPreview.css({ left: x, top: y });
        
        // Check if over canvas
        const container = $('#canvas-container')[0].getBoundingClientRect();
        const isOverCanvas = x >= container.left && x <= container.right && 
                            y >= container.top && y <= container.bottom;
        
        this.$dragPreview.toggleClass('valid-drop', isOverCanvas);
    },
    
    // End palette drag - create node
    endPaletteDrag: function(x, y) {
        this.$dragPreview.hide();
        $('#canvas-container').removeClass('drag-over');
        
        // Check if dropped on canvas
        const container = $('#canvas-container')[0].getBoundingClientRect();
        const isOverCanvas = x >= container.left && x <= container.right && 
                            y >= container.top && y <= container.bottom;
        
        if (isOverCanvas && this.draggedType) {
            // Convert to canvas coordinates
            const canvasPos = Canvas.screenToCanvas(x, y);
            
            // Create node
            const node = Nodes.createNode(this.draggedType, canvasPos);
            
            if (node) {
                Nodes.selectNode(node.id);
                
                // Record for undo
                History.record('add', { node: Utils.deepClone(node) });
            }
        }
        
        this.isDragging = false;
        this.draggedType = null;
    },
    
    // Cancel drag
    cancelDrag: function() {
        this.$dragPreview.hide();
        $('#canvas-container').removeClass('drag-over');
        this.isDragging = false;
        this.draggedType = null;
    },
    
    // Initialize node dragging (for existing nodes on canvas)
    initNodeDrag: function($node) {
        const self = this;
        const nodeId = $node.attr('id');
        
        interact($node[0]).draggable({
            inertia: false,
            
            listeners: {
                start: function(event) {
                    const node = Nodes.getNode(nodeId);
                    if (!node) return;
                    
                    // Store initial position for undo
                    self.dragStartPosition = { ...node.position };
                    
                    // Add dragging class
                    $node.addClass('dragging');
                    
                    // If not selected, select it
                    if (!Nodes.selectedNodes.includes(nodeId)) {
                        Nodes.selectNode(nodeId);
                    }
                },
                
                move: function(event) {
                    // Get current transform
                    const transform = Canvas.getTransform();
                    
                    // Calculate movement in canvas coordinates
                    const dx = event.dx / transform.scale;
                    const dy = event.dy / transform.scale;
                    
                    // Move all selected nodes
                    Nodes.selectedNodes.forEach(id => {
                        const node = Nodes.getNode(id);
                        if (!node) return;
                        
                        const newX = node.position.x + dx;
                        const newY = node.position.y + dy;
                        
                        Nodes.updateNodePosition(id, newX, newY);
                    });
                },
                
                end: function(event) {
                    $node.removeClass('dragging');
                    
                    // Snap to grid
                    Nodes.selectedNodes.forEach(id => {
                        const node = Nodes.getNode(id);
                        if (!node) return;
                        
                        const snapped = Canvas.snapToGrid(node.position.x, node.position.y);
                        Nodes.updateNodePosition(id, snapped.x, snapped.y);
                    });
                    
                    // Record for undo (only if position changed)
                    const node = Nodes.getNode(nodeId);
                    if (node && self.dragStartPosition) {
                        if (node.position.x !== self.dragStartPosition.x || 
                            node.position.y !== self.dragStartPosition.y) {
                            History.record('move', {
                                nodeId: nodeId,
                                from: self.dragStartPosition,
                                to: { ...node.position }
                            });
                        }
                    }
                    
                    self.dragStartPosition = null;
                }
            }
        });
    }
};

// Make available globally
window.DragDrop = DragDrop;
