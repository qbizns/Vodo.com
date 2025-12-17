/* ============================================
   Panels Module - Sidebars & Settings Panel
   ============================================ */

const Panels = {
    // State
    currentNodeId: null,
    isSettingsPanelOpen: false,
    
    // Initialize
    init: async function() {
        await this.renderNodePalette();
        this.bindEvents();
        
        console.log('Panels initialized');
    },
    
    // Render node palette (left sidebar)
    renderNodePalette: async function() {
        const categories = await MockAPI.getNodeTypes();
        const $container = $('#node-categories');
        $container.empty();
        
        Object.entries(categories).forEach(([key, category]) => {
            if (category.nodes.length === 0) return;
            
            const nodesHtml = category.nodes.map(node => `
                <div class="node-palette-item" data-type="${node.type}" draggable="true">
                    <div class="node-palette-icon">
                        <i class="${node.icon}"></i>
                    </div>
                    <div class="node-palette-info">
                        <div class="node-palette-name">${node.name}</div>
                        <div class="node-palette-desc">${node.description}</div>
                    </div>
                </div>
            `).join('');
            
            const categoryHtml = `
                <div class="node-category" data-category="${key}">
                    <button class="node-category-header">
                        <i class="${category.icon}"></i>
                        <span>${category.name}</span>
                        <span class="node-category-count">${category.nodes.length}</span>
                        <i class="lucide-chevron-down chevron"></i>
                    </button>
                    <div class="node-category-list">
                        ${nodesHtml}
                    </div>
                </div>
            `;
            
            $container.append(categoryHtml);
        });
        
        // Initialize drag for palette items
        DragDrop.initPaletteDrag();
    },
    
    // Bind event handlers
    bindEvents: function() {
        // Category collapse/expand
        $(document).on('click', '.node-category-header', function(e) {
            $(this).closest('.node-category').toggleClass('collapsed');
        });
        
        // Node search
        $('#node-search').on('input', Utils.debounce((e) => {
            this.filterNodes(e.target.value);
        }, 200));
        
        // Close settings panel
        $('#btn-close-panel').on('click', () => this.closeSettingsPanel());
        
        // Panel tabs
        $(document).on('click', '.panel-tab', (e) => {
            const tab = $(e.currentTarget).data('tab');
            this.switchTab(tab);
        });
        
        // Open settings on node open event
        Utils.events.on('node:open', (data) => {
            this.openSettingsPanel(data.nodeId);
        });
        
        // Open settings on node select (single selection)
        Utils.events.on('node:selected', (data) => {
            if (data.selectedNodes.length === 1) {
                this.openSettingsPanel(data.selectedNodes[0]);
            }
        });
        
        // Close on deselect
        Utils.events.on('node:deselected', () => {
            // Optional: close panel on deselect
            // this.closeSettingsPanel();
        });
        
        // Test node button
        $('#btn-test-node').on('click', () => this.testCurrentNode());
        
        // Delete node button
        $('#btn-delete-node').on('click', () => {
            if (this.currentNodeId) {
                Nodes.deleteNode(this.currentNodeId);
                this.closeSettingsPanel();
            }
        });
        
        // Node name change
        $('#panel-node-name').on('change', (e) => {
            if (this.currentNodeId) {
                Nodes.updateNode(this.currentNodeId, { name: e.target.value });
            }
        });
        
        // Add first node buttons
        $('#btn-add-first-node, #btn-add-trigger').on('click', () => {
            this.openNodeCreator();
        });
        
        // Node creator modal
        $('#node-creator-modal').on('click', (e) => {
            if (e.target === e.currentTarget) {
                this.closeNodeCreator();
            }
        });
        
        // Node creator search
        $('#node-creator-search-input').on('input', Utils.debounce((e) => {
            this.filterNodeCreator(e.target.value);
        }, 150));
        
        // Node creator item click
        $(document).on('click', '.node-creator-item', (e) => {
            const type = $(e.currentTarget).data('type');
            this.createNodeFromCreator(type);
        });
        
        // Double-click on canvas opens node creator
        Utils.events.on('canvas:dblclick', (pos) => {
            this.openNodeCreator(pos);
        });
        
        // ESC key closes panels/modals
        $(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                if ($('#node-creator-modal').hasClass('visible')) {
                    this.closeNodeCreator();
                } else if (this.isSettingsPanelOpen) {
                    this.closeSettingsPanel();
                }
            }
        });
        
        // Keyboard shortcut to open node creator
        $(document).on('keydown', (e) => {
            if (e.key === '/' && !$(e.target).is('input, textarea')) {
                e.preventDefault();
                $('#node-search').focus();
            }
        });
    },
    
    // Filter nodes in palette
    filterNodes: function(query) {
        const lowerQuery = query.toLowerCase().trim();
        
        if (!lowerQuery) {
            // Show all
            $('.node-palette-item').show();
            $('.node-category').show().removeClass('collapsed');
            return;
        }
        
        // Filter items
        $('.node-palette-item').each(function() {
            const $item = $(this);
            const name = $item.find('.node-palette-name').text().toLowerCase();
            const desc = $item.find('.node-palette-desc').text().toLowerCase();
            
            if (name.includes(lowerQuery) || desc.includes(lowerQuery)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        // Hide empty categories
        $('.node-category').each(function() {
            const $category = $(this);
            const visibleItems = $category.find('.node-palette-item:visible').length;
            
            if (visibleItems === 0) {
                $category.hide();
            } else {
                $category.show().removeClass('collapsed');
            }
        });
    },
    
    // Open settings panel
    openSettingsPanel: function(nodeId) {
        const node = Nodes.getNode(nodeId);
        if (!node) return;
        
        this.currentNodeId = nodeId;
        this.isSettingsPanelOpen = true;
        
        const nodeType = Nodes.nodeTypes[node.type] || {};
        
        // Update panel header
        $('#panel-node-name').val(node.name);
        $('#panel-node-icon').html(`<i class="${nodeType.icon || 'lucide-box'}"></i>`);
        
        // Render parameters
        this.renderParameters(node, nodeType);
        
        // Switch to parameters tab
        this.switchTab('parameters');
        
        // Show panel
        $('#settings-panel').addClass('open');
    },
    
    // Close settings panel
    closeSettingsPanel: function() {
        this.currentNodeId = null;
        this.isSettingsPanelOpen = false;
        $('#settings-panel').removeClass('open');
    },
    
    // Switch panel tab
    switchTab: function(tabName) {
        $('.panel-tab').removeClass('active');
        $(`.panel-tab[data-tab="${tabName}"]`).addClass('active');
        
        $('.tab-content').removeClass('active');
        $(`.tab-content[data-tab="${tabName}"]`).addClass('active');
    },
    
    // Render node parameters form
    renderParameters: function(node, nodeType) {
        const $container = $('#tab-parameters');
        $container.empty();
        
        const parameters = nodeType.parameters || [];
        
        if (parameters.length === 0) {
            $container.html(`
                <div class="output-empty">
                    <i class="lucide-settings"></i>
                    <p>This node has no parameters</p>
                </div>
            `);
            return;
        }
        
        parameters.forEach(param => {
            const value = node.parameters[param.name] !== undefined 
                ? node.parameters[param.name] 
                : param.default;
            
            const fieldHtml = this.renderParameterField(param, value);
            $container.append(fieldHtml);
        });
        
        // Bind parameter change events
        $container.find('.param-input').on('change input', (e) => {
            const $input = $(e.target);
            const paramName = $input.data('param');
            let value = $input.val();
            
            // Type conversion
            if ($input.attr('type') === 'number') {
                value = parseFloat(value) || 0;
            } else if ($input.attr('type') === 'checkbox') {
                value = $input.is(':checked');
            }
            
            // Update node
            const params = { ...node.parameters, [paramName]: value };
            Nodes.updateNode(this.currentNodeId, { parameters: params });
        });
    },
    
    // Render single parameter field
    renderParameterField: function(param, value) {
        let inputHtml = '';
        
        switch (param.type) {
            case 'string':
                inputHtml = `
                    <input type="text" 
                           class="form-control param-input" 
                           data-param="${param.name}"
                           value="${Utils.escapeHtml(value || '')}"
                           placeholder="${param.placeholder || ''}">
                `;
                break;
                
            case 'number':
                inputHtml = `
                    <input type="number" 
                           class="form-control param-input" 
                           data-param="${param.name}"
                           value="${value || 0}">
                `;
                break;
                
            case 'text':
                inputHtml = `
                    <textarea class="form-control param-input" 
                              data-param="${param.name}"
                              rows="3"
                              placeholder="${param.placeholder || ''}">${Utils.escapeHtml(value || '')}</textarea>
                `;
                break;
                
            case 'select':
                const options = (param.options || []).map(opt => 
                    `<option value="${opt}" ${value === opt ? 'selected' : ''}>${opt}</option>`
                ).join('');
                inputHtml = `
                    <select class="form-control param-input" data-param="${param.name}">
                        ${options}
                    </select>
                `;
                break;
                
            case 'boolean':
                inputHtml = `
                    <label class="toggle-label">
                        <span>${param.label}</span>
                        <input type="checkbox" 
                               class="toggle param-input" 
                               data-param="${param.name}"
                               ${value ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                `;
                // Return early for boolean - different structure
                return `<div class="parameter-field">${inputHtml}</div>`;
                
            case 'code':
                inputHtml = `
                    <div class="code-editor-field">
                        <textarea class="param-input" 
                                  data-param="${param.name}"
                                  rows="6">${Utils.escapeHtml(value || param.default || '')}</textarea>
                    </div>
                `;
                break;
                
            case 'json':
                const jsonValue = typeof value === 'object' ? JSON.stringify(value, null, 2) : value || '{}';
                inputHtml = `
                    <div class="code-editor-field">
                        <textarea class="param-input" 
                                  data-param="${param.name}"
                                  rows="4">${Utils.escapeHtml(jsonValue)}</textarea>
                    </div>
                `;
                break;
                
            default:
                inputHtml = `
                    <input type="text" 
                           class="form-control param-input" 
                           data-param="${param.name}"
                           value="${Utils.escapeHtml(value || '')}">
                `;
        }
        
        return `
            <div class="parameter-field">
                <div class="parameter-label">
                    <span class="parameter-label-text">${param.label || param.name}</span>
                    ${param.required ? '<span class="parameter-required">*</span>' : ''}
                </div>
                ${inputHtml}
                ${param.description ? `<div class="parameter-description">${param.description}</div>` : ''}
            </div>
        `;
    },
    
    // Test current node
    testCurrentNode: async function() {
        if (!this.currentNodeId) return;
        
        const $btn = $('#btn-test-node');
        $btn.prop('disabled', true).html('<i class="lucide-loader-2 spinning"></i> Testing...');
        
        Nodes.setNodeState(this.currentNodeId, 'running');
        
        try {
            const result = await MockAPI.executeNode(this.currentNodeId, {});
            
            Nodes.setNodeState(this.currentNodeId, 'success');
            
            // Show output
            $('#output-data').text(JSON.stringify(result.data, null, 2)).addClass('visible');
            this.switchTab('output');
            
            Utils.showToast(`Executed in ${result.executionTime}ms`, 'success');
        } catch (error) {
            Nodes.setNodeState(this.currentNodeId, 'error');
            Utils.showToast('Execution failed', 'error');
        } finally {
            $btn.prop('disabled', false).html('<i class="lucide-play"></i> Test step');
        }
    },
    
    // Open node creator modal
    openNodeCreator: function(position = null) {
        this.nodeCreatorPosition = position;
        
        // Render nodes in creator
        this.renderNodeCreator();
        
        // Show modal
        $('#node-creator-modal').addClass('visible');
        $('#node-creator-search-input').val('').focus();
    },
    
    // Close node creator modal
    closeNodeCreator: function() {
        $('#node-creator-modal').removeClass('visible');
        this.nodeCreatorPosition = null;
    },
    
    // Render node creator content
    renderNodeCreator: async function() {
        const categories = await MockAPI.getNodeTypes();
        
        // Recently used (mock)
        const recentTypes = ['trigger.webhook', 'http.request', 'logic.if'];
        const $recent = $('#recent-nodes');
        $recent.empty();
        
        recentTypes.forEach(type => {
            const nodeType = Nodes.nodeTypes[type];
            if (nodeType) {
                $recent.append(this.createNodeCreatorItem(nodeType));
            }
        });
        
        // All nodes
        const $all = $('#all-nodes');
        $all.empty();
        
        Object.values(categories).forEach(category => {
            category.nodes.forEach(nodeType => {
                $all.append(this.createNodeCreatorItem(nodeType));
            });
        });
    },
    
    // Create node creator item HTML
    createNodeCreatorItem: function(nodeType) {
        return `
            <div class="node-creator-item" data-type="${nodeType.type}">
                <div class="node-creator-item-icon">
                    <i class="${nodeType.icon}"></i>
                </div>
                <div class="node-creator-item-info">
                    <div class="node-creator-item-name">${nodeType.name}</div>
                    <div class="node-creator-item-desc">${nodeType.description}</div>
                </div>
            </div>
        `;
    },
    
    // Filter node creator
    filterNodeCreator: function(query) {
        const lowerQuery = query.toLowerCase().trim();
        
        $('.node-creator-item').each(function() {
            const $item = $(this);
            const name = $item.find('.node-creator-item-name').text().toLowerCase();
            const desc = $item.find('.node-creator-item-desc').text().toLowerCase();
            
            if (!lowerQuery || name.includes(lowerQuery) || desc.includes(lowerQuery)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        // Hide empty sections
        $('.node-creator-section').each(function() {
            const $section = $(this);
            const visible = $section.find('.node-creator-item:visible').length;
            $section.toggle(visible > 0);
        });
    },
    
    // Create node from creator
    createNodeFromCreator: function(type) {
        let position;
        
        if (this.nodeCreatorPosition) {
            position = this.nodeCreatorPosition;
        } else {
            // Center of viewport
            const transform = Canvas.getTransform();
            const container = $('#canvas-container')[0].getBoundingClientRect();
            position = Canvas.screenToCanvas(
                container.left + container.width / 2,
                container.top + container.height / 2
            );
        }
        
        const node = Nodes.createNode(type, position);
        
        if (node) {
            Nodes.selectNode(node.id);
            this.openSettingsPanel(node.id);
        }
        
        this.closeNodeCreator();
    }
};

// Add spinning animation for loader
$('<style>')
    .text('@keyframes spin { to { transform: rotate(360deg); } } .spinning { animation: spin 1s linear infinite; }')
    .appendTo('head');

// Make available globally
window.Panels = Panels;
