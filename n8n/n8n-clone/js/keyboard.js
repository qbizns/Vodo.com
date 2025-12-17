/* ============================================
   Keyboard Shortcuts Module
   ============================================ */

const Keyboard = {
    // State
    clipboard: [],
    
    // Initialize
    init: function() {
        this.bindShortcuts();
        console.log('Keyboard shortcuts initialized');
    },
    
    // Bind keyboard shortcuts
    bindShortcuts: function() {
        $(document).on('keydown', (e) => {
            // Don't trigger shortcuts when typing in inputs
            if ($(e.target).is('input, textarea, select')) {
                // Allow Escape in inputs
                if (e.key === 'Escape') {
                    $(e.target).blur();
                }
                return;
            }
            
            const ctrl = e.ctrlKey || e.metaKey;
            const shift = e.shiftKey;
            
            // Delete - Delete selected nodes
            if (e.key === 'Delete' || e.key === 'Backspace') {
                e.preventDefault();
                this.deleteSelected();
                return;
            }
            
            // Ctrl+A - Select all
            if (ctrl && e.key === 'a') {
                e.preventDefault();
                this.selectAll();
                return;
            }
            
            // Ctrl+C - Copy
            if (ctrl && e.key === 'c') {
                e.preventDefault();
                this.copy();
                return;
            }
            
            // Ctrl+V - Paste
            if (ctrl && e.key === 'v') {
                e.preventDefault();
                this.paste();
                return;
            }
            
            // Ctrl+X - Cut
            if (ctrl && e.key === 'x') {
                e.preventDefault();
                this.cut();
                return;
            }
            
            // Ctrl+D - Duplicate
            if (ctrl && e.key === 'd') {
                e.preventDefault();
                this.duplicate();
                return;
            }
            
            // Ctrl+Z - Undo
            if (ctrl && !shift && e.key === 'z') {
                e.preventDefault();
                History.undo();
                return;
            }
            
            // Ctrl+Shift+Z or Ctrl+Y - Redo
            if ((ctrl && shift && e.key === 'z') || (ctrl && e.key === 'y')) {
                e.preventDefault();
                History.redo();
                return;
            }
            
            // Ctrl+S - Save
            if (ctrl && e.key === 's') {
                e.preventDefault();
                App.saveWorkflow();
                return;
            }
            
            // Escape - Deselect / Close panels
            if (e.key === 'Escape') {
                Nodes.deselectAll();
                return;
            }
            
            // + / = - Zoom in
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                Canvas.zoomIn();
                return;
            }
            
            // - - Zoom out
            if (e.key === '-') {
                e.preventDefault();
                Canvas.zoomOut();
                return;
            }
            
            // 0 - Reset zoom
            if (e.key === '0' && ctrl) {
                e.preventDefault();
                Canvas.zoomTo(1);
                return;
            }
            
            // 1 - Fit view
            if (e.key === '1' && ctrl) {
                e.preventDefault();
                Canvas.fitView();
                return;
            }
            
            // F2 - Rename selected node
            if (e.key === 'F2') {
                e.preventDefault();
                this.renameSelected();
                return;
            }
            
            // Tab - Open node creator
            if (e.key === 'Tab') {
                e.preventDefault();
                Panels.openNodeCreator();
                return;
            }
            
            // Arrow keys - Move selected nodes
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                if (Nodes.selectedNodes.length > 0) {
                    e.preventDefault();
                    this.moveSelectedByKey(e.key, shift ? 10 : 1);
                }
                return;
            }
            
            // Space - Hold to pan
            if (e.key === ' ' && !e.repeat) {
                e.preventDefault();
                $('#canvas-container').addClass('is-panning');
                return;
            }
            
            // E - Enable/Disable selected node
            if (e.key === 'e' && !ctrl) {
                if (Nodes.selectedNodes.length === 1) {
                    const node = Nodes.getNode(Nodes.selectedNodes[0]);
                    if (node) {
                        Nodes.setNodeDisabled(node.id, !node.disabled);
                    }
                }
                return;
            }
        });
        
        // Space key release - stop pan mode
        $(document).on('keyup', (e) => {
            if (e.key === ' ') {
                $('#canvas-container').removeClass('is-panning');
            }
        });
    },
    
    // Delete selected items
    deleteSelected: function() {
        const selected = Nodes.selectedNodes;
        
        if (selected.length === 0) {
            // Check for selected connection
            const $selectedConn = $('.connection.selected');
            if ($selectedConn.length > 0) {
                const connId = $selectedConn.attr('data-connection-id');
                Connections.removeConnection(connId);
                Utils.showToast('Connection deleted', 'info');
            }
            return;
        }
        
        // Record for undo
        const deletedNodes = selected.map(id => Utils.deepClone(Nodes.getNode(id)));
        const deletedConnections = [];
        
        selected.forEach(id => {
            deletedConnections.push(...Connections.getNodeConnections(id));
        });
        
        History.record('delete', {
            nodes: deletedNodes,
            connections: deletedConnections
        });
        
        // Delete nodes
        Nodes.deleteSelectedNodes();
        
        Utils.showToast(`Deleted ${selected.length} node(s)`, 'info');
    },
    
    // Select all nodes
    selectAll: function() {
        const allNodes = Nodes.getAllNodes();
        Nodes.deselectAll();
        
        allNodes.forEach(node => {
            Nodes.selectNode(node.id, true);
        });
    },
    
    // Copy selected nodes
    copy: function() {
        const selected = Nodes.getSelectedNodes();
        
        if (selected.length === 0) {
            Utils.showToast('Nothing to copy', 'info');
            return;
        }
        
        this.clipboard = selected.map(node => Utils.deepClone(node));
        Utils.showToast(`Copied ${selected.length} node(s)`, 'success');
    },
    
    // Paste from clipboard
    paste: function() {
        if (this.clipboard.length === 0) {
            Utils.showToast('Nothing to paste', 'info');
            return;
        }
        
        const idMap = Nodes.pasteNodes(this.clipboard);
        
        // Record for undo
        const newNodes = Object.values(idMap).map(id => Utils.deepClone(Nodes.getNode(id)));
        History.record('add', { nodes: newNodes });
        
        Utils.showToast(`Pasted ${this.clipboard.length} node(s)`, 'success');
    },
    
    // Cut selected nodes
    cut: function() {
        this.copy();
        this.deleteSelected();
    },
    
    // Duplicate selected nodes
    duplicate: function() {
        const selected = Nodes.getSelectedNodes();
        
        if (selected.length === 0) {
            Utils.showToast('Nothing to duplicate', 'info');
            return;
        }
        
        const idMap = Nodes.duplicateSelectedNodes();
        
        // Record for undo
        const newNodes = Object.values(idMap).map(id => Utils.deepClone(Nodes.getNode(id)));
        History.record('add', { nodes: newNodes });
        
        Utils.showToast(`Duplicated ${selected.length} node(s)`, 'success');
    },
    
    // Rename selected node
    renameSelected: function() {
        if (Nodes.selectedNodes.length !== 1) return;
        
        Panels.openSettingsPanel(Nodes.selectedNodes[0]);
        
        setTimeout(() => {
            $('#panel-node-name').focus().select();
        }, 100);
    },
    
    // Move selected nodes with arrow keys
    moveSelectedByKey: function(direction, amount) {
        const gridSize = Canvas.gridSize;
        const moveAmount = amount * gridSize;
        
        let dx = 0, dy = 0;
        
        switch (direction) {
            case 'ArrowUp':    dy = -moveAmount; break;
            case 'ArrowDown':  dy = moveAmount; break;
            case 'ArrowLeft':  dx = -moveAmount; break;
            case 'ArrowRight': dx = moveAmount; break;
        }
        
        Nodes.selectedNodes.forEach(id => {
            const node = Nodes.getNode(id);
            if (!node) return;
            
            Nodes.updateNodePosition(id, node.position.x + dx, node.position.y + dy);
        });
    }
};

// Make available globally
window.Keyboard = Keyboard;
