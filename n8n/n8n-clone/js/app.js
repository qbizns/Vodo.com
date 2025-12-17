/* ============================================
   Main Application - Initialization & Core Logic
   ============================================ */

const App = {
    // Current workflow
    workflow: null,
    
    // Initialize application
    init: async function() {
        console.log('Initializing Workflow Editor...');
        
        try {
            // Initialize modules in order
            DragDrop.init();
            Canvas.init();
            Connections.init();
            await Nodes.init();
            await Panels.init();
            Keyboard.init();
            History.init();
            
            // Bind toolbar events
            this.bindToolbarEvents();
            
            // Bind context menu
            this.bindContextMenu();
            
            // Bind connection events
            this.bindConnectionEvents();
            
            // Load sample workflow or empty
            await this.loadWorkflow('sample');
            
            // Fit view after loading
            setTimeout(() => Canvas.fitView(), 100);
            
            console.log('Workflow Editor initialized successfully!');
            Utils.showToast('Workflow Editor ready', 'success');
            
        } catch (error) {
            console.error('Failed to initialize:', error);
            Utils.showToast('Failed to initialize editor', 'error');
        }
    },
    
    // Bind toolbar button events
    bindToolbarEvents: function() {
        // Save
        $('#btn-save').on('click', () => this.saveWorkflow());
        
        // Test workflow
        $('#btn-test-workflow').on('click', () => this.testWorkflow());
        
        // Undo/Redo
        $('#btn-undo').on('click', () => History.undo());
        $('#btn-redo').on('click', () => History.redo());
        
        // Workflow name change
        $('#workflow-name-input').on('change', (e) => {
            if (this.workflow) {
                this.workflow.name = e.target.value;
                History.markUnsaved();
            }
        });
        
        // Menu button
        $('#btn-menu').on('click', () => {
            Utils.showToast('Menu not implemented in demo', 'info');
        });
        
        // Minimap toggle
        $('#btn-toggle-minimap').on('click', () => {
            $('#minimap').toggleClass('visible');
        });
    },
    
    // Bind context menu
    bindContextMenu: function() {
        const $menu = $('#context-menu');
        
        // Show context menu
        Utils.events.on('node:contextmenu', (data) => {
            $menu.css({
                left: data.x,
                top: data.y
            }).addClass('visible');
        });
        
        Utils.events.on('canvas:contextmenu', (data) => {
            // Could show different menu for canvas
        });
        
        // Hide on click outside
        $(document).on('click', (e) => {
            if (!$(e.target).closest('.context-menu').length) {
                $menu.removeClass('visible');
            }
        });
        
        // Menu item actions
        $menu.on('click', '.context-menu-item', (e) => {
            const action = $(e.currentTarget).data('action');
            
            switch (action) {
                case 'copy':
                    Keyboard.copy();
                    break;
                case 'paste':
                    Keyboard.paste();
                    break;
                case 'duplicate':
                    Keyboard.duplicate();
                    break;
                case 'delete':
                    Keyboard.deleteSelected();
                    break;
                case 'disable':
                    const selected = Nodes.getSelectedNodes();
                    selected.forEach(node => {
                        Nodes.setNodeDisabled(node.id, !node.disabled);
                    });
                    break;
                case 'pin':
                    Utils.showToast('Pin data not implemented in demo', 'info');
                    break;
            }
            
            $menu.removeClass('visible');
        });
    },
    
    // Bind connection events
    bindConnectionEvents: function() {
        Utils.events.on('connection:created', (connection) => {
            History.record('connection-add', { connection: Utils.deepClone(connection) });
        });
        
        Utils.events.on('connection:deleted', (data) => {
            // Connection already deleted, just for notification
        });
    },
    
    // Load workflow
    loadWorkflow: async function(id) {
        try {
            this.workflow = await MockAPI.loadWorkflow(id);
            
            // Update UI
            $('#workflow-name-input').val(this.workflow.name);
            
            // Load nodes
            Nodes.loadNodes(this.workflow.nodes || []);
            
            // Load connections (after nodes are rendered)
            setTimeout(() => {
                Connections.setConnections(this.workflow.connections || []);
            }, 50);
            
            // Clear history
            History.clear();
            History.markSaved();
            
            console.log('Workflow loaded:', this.workflow.name);
            
        } catch (error) {
            console.error('Failed to load workflow:', error);
            Utils.showToast('Failed to load workflow', 'error');
        }
    },
    
    // Save workflow
    saveWorkflow: async function() {
        if (!this.workflow) return;
        
        const $btn = $('#btn-save');
        $btn.prop('disabled', true).html('<i class="lucide-loader-2 spinning"></i> Saving...');
        
        try {
            // Update workflow data
            this.workflow.nodes = Nodes.exportNodes();
            this.workflow.connections = Connections.getConnections();
            
            // Save via API
            const result = await MockAPI.saveWorkflow(this.workflow);
            
            History.markSaved();
            Utils.showToast('Workflow saved!', 'success');
            
        } catch (error) {
            console.error('Failed to save workflow:', error);
            Utils.showToast('Failed to save workflow', 'error');
        } finally {
            $btn.prop('disabled', false).html('<i class="lucide-save"></i> <span>Save</span>');
        }
    },
    
    // Test workflow
    testWorkflow: async function() {
        const $btn = $('#btn-test-workflow');
        $btn.prop('disabled', true).html('<i class="lucide-loader-2 spinning"></i> Running...');
        
        try {
            // Animate execution through nodes
            const nodes = Nodes.getAllNodes();
            
            for (const node of nodes) {
                Nodes.setNodeState(node.id, 'running');
                
                // Animate connections leading to this node
                const connections = Connections.getNodeConnections(node.id);
                connections.forEach(conn => {
                    if (conn.target === node.id) {
                        Connections.animateConnection(conn.id, 'running');
                    }
                });
                
                // Simulate execution time
                await new Promise(resolve => setTimeout(resolve, 500 + Math.random() * 500));
                
                // Mark as success (random chance of error for demo)
                const success = Math.random() > 0.1;
                Nodes.setNodeState(node.id, success ? 'success' : 'error');
                
                connections.forEach(conn => {
                    if (conn.target === node.id) {
                        Connections.animateConnection(conn.id, success ? 'success' : 'error');
                    }
                });
                
                if (!success) {
                    Utils.showToast(`Error in "${node.name}"`, 'error');
                    break;
                }
            }
            
            Utils.showToast('Workflow executed successfully!', 'success');
            
        } catch (error) {
            console.error('Workflow execution failed:', error);
            Utils.showToast('Workflow execution failed', 'error');
        } finally {
            $btn.prop('disabled', false).html('<i class="lucide-play"></i> <span>Test Workflow</span>');
            
            // Clear states after delay
            setTimeout(() => {
                Nodes.getAllNodes().forEach(node => {
                    Nodes.setNodeState(node.id, null);
                });
            }, 3000);
        }
    },
    
    // Create new workflow
    newWorkflow: function() {
        if (History.hasUnsavedChanges()) {
            if (!confirm('You have unsaved changes. Create new workflow anyway?')) {
                return;
            }
        }
        
        this.workflow = {
            id: Utils.generateId(),
            name: 'New Workflow',
            nodes: [],
            connections: []
        };
        
        $('#workflow-name-input').val(this.workflow.name);
        Nodes.loadNodes([]);
        Connections.setConnections([]);
        History.clear();
        
        Utils.showToast('New workflow created', 'info');
    },
    
    // Export workflow as JSON
    exportWorkflow: function() {
        if (!this.workflow) return;
        
        this.workflow.nodes = Nodes.exportNodes();
        this.workflow.connections = Connections.getConnections();
        
        const json = JSON.stringify(this.workflow, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `${this.workflow.name.replace(/\s+/g, '_')}.json`;
        a.click();
        
        URL.revokeObjectURL(url);
        Utils.showToast('Workflow exported', 'success');
    },
    
    // Get current workflow data
    getWorkflowData: function() {
        if (!this.workflow) return null;
        
        return {
            ...this.workflow,
            nodes: Nodes.exportNodes(),
            connections: Connections.getConnections()
        };
    }
};

// Initialize when DOM is ready
$(document).ready(() => {
    App.init();
});

// Warn before leaving with unsaved changes
$(window).on('beforeunload', (e) => {
    if (History.hasUnsavedChanges()) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

// Make available globally
window.App = App;
