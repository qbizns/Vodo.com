/* ============================================
   History Module - Undo/Redo
   ============================================ */

const History = {
    // State
    undoStack: [],
    redoStack: [],
    maxHistory: 50,
    
    // Initialize
    init: function() {
        this.updateButtons();
        console.log('History initialized');
    },
    
    // Record an action
    record: function(type, data) {
        const entry = {
            type: type,
            data: data,
            timestamp: Date.now()
        };
        
        this.undoStack.push(entry);
        
        // Clear redo stack when new action is recorded
        this.redoStack = [];
        
        // Limit history size
        if (this.undoStack.length > this.maxHistory) {
            this.undoStack.shift();
        }
        
        this.updateButtons();
        this.markUnsaved();
    },
    
    // Undo last action
    undo: function() {
        if (this.undoStack.length === 0) {
            Utils.showToast('Nothing to undo', 'info');
            return;
        }
        
        const entry = this.undoStack.pop();
        this.redoStack.push(entry);
        
        this.applyUndo(entry);
        this.updateButtons();
        this.markUnsaved();
    },
    
    // Redo last undone action
    redo: function() {
        if (this.redoStack.length === 0) {
            Utils.showToast('Nothing to redo', 'info');
            return;
        }
        
        const entry = this.redoStack.pop();
        this.undoStack.push(entry);
        
        this.applyRedo(entry);
        this.updateButtons();
        this.markUnsaved();
    },
    
    // Apply undo for an entry
    applyUndo: function(entry) {
        switch (entry.type) {
            case 'add':
                // Undo add = delete
                if (entry.data.node) {
                    Nodes.deleteNode(entry.data.node.id);
                }
                if (entry.data.nodes) {
                    entry.data.nodes.forEach(node => Nodes.deleteNode(node.id));
                }
                break;
                
            case 'delete':
                // Undo delete = restore
                if (entry.data.nodes) {
                    entry.data.nodes.forEach(node => {
                        Nodes.createNode(node.type, node.position, {
                            id: node.id,
                            name: node.name,
                            parameters: node.parameters,
                            disabled: node.disabled
                        });
                    });
                }
                if (entry.data.connections) {
                    entry.data.connections.forEach(conn => {
                        Connections.addConnection(conn);
                    });
                }
                break;
                
            case 'move':
                // Undo move = restore previous position
                Nodes.updateNodePosition(entry.data.nodeId, entry.data.from.x, entry.data.from.y);
                break;
                
            case 'update':
                // Undo update = restore previous state
                const node = Nodes.getNode(entry.data.nodeId);
                if (node) {
                    Object.assign(node, entry.data.before);
                    Nodes.renderNode(node);
                }
                break;
                
            case 'connection-add':
                // Undo connection add = delete
                Connections.removeConnection(entry.data.connection.id);
                break;
                
            case 'connection-delete':
                // Undo connection delete = restore
                Connections.addConnection(entry.data.connection);
                break;
        }
    },
    
    // Apply redo for an entry
    applyRedo: function(entry) {
        switch (entry.type) {
            case 'add':
                // Redo add = recreate
                if (entry.data.node) {
                    Nodes.createNode(entry.data.node.type, entry.data.node.position, {
                        id: entry.data.node.id,
                        name: entry.data.node.name,
                        parameters: entry.data.node.parameters,
                        disabled: entry.data.node.disabled
                    });
                }
                if (entry.data.nodes) {
                    entry.data.nodes.forEach(node => {
                        Nodes.createNode(node.type, node.position, {
                            id: node.id,
                            name: node.name,
                            parameters: node.parameters,
                            disabled: node.disabled
                        });
                    });
                }
                break;
                
            case 'delete':
                // Redo delete = delete again
                if (entry.data.nodes) {
                    entry.data.nodes.forEach(node => Nodes.deleteNode(node.id));
                }
                break;
                
            case 'move':
                // Redo move = apply new position
                Nodes.updateNodePosition(entry.data.nodeId, entry.data.to.x, entry.data.to.y);
                break;
                
            case 'update':
                // Redo update = apply new state
                const node = Nodes.getNode(entry.data.nodeId);
                if (node) {
                    Object.assign(node, entry.data.after);
                    Nodes.renderNode(node);
                }
                break;
                
            case 'connection-add':
                // Redo connection add = add again
                Connections.addConnection(entry.data.connection);
                break;
                
            case 'connection-delete':
                // Redo connection delete = delete again
                Connections.removeConnection(entry.data.connection.id);
                break;
        }
    },
    
    // Update undo/redo buttons state
    updateButtons: function() {
        $('#btn-undo').prop('disabled', this.undoStack.length === 0);
        $('#btn-redo').prop('disabled', this.redoStack.length === 0);
    },
    
    // Mark workflow as unsaved
    markUnsaved: function() {
        $('#workflow-status').text('Unsaved').addClass('unsaved');
    },
    
    // Mark workflow as saved
    markSaved: function() {
        $('#workflow-status').text('Saved').removeClass('unsaved');
    },
    
    // Clear history
    clear: function() {
        this.undoStack = [];
        this.redoStack = [];
        this.updateButtons();
    },
    
    // Check if there are unsaved changes
    hasUnsavedChanges: function() {
        return this.undoStack.length > 0 || $('#workflow-status').hasClass('unsaved');
    }
};

// Make available globally
window.History = History;
