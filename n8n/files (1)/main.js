// ============================================
// Node Type Definitions
// ============================================
const TRIGGERS = [
    { id: 'manual-trigger', name: 'Trigger manually', desc: 'Runs the flow on clicking a button in n8n. Good for getting started quickly', icon: 'cursor', category: 'trigger' },
    { id: 'app-event', name: 'On app event', desc: 'Runs the flow when something happens in an app like Telegram, Notion or Airtable', icon: 'bell', category: 'trigger', arrow: true },
    { id: 'schedule', name: 'On a schedule', desc: 'Runs the flow every day, hour, or custom interval', icon: 'clock', category: 'trigger' },
    { id: 'webhook', name: 'On webhook call', desc: 'Runs the flow on receiving an HTTP request', icon: 'webhook', category: 'trigger' },
    { id: 'form', name: 'On form submission', desc: 'Generate webforms in n8n and pass their responses to the workflow', icon: 'form', category: 'trigger' },
    { id: 'execute-workflow', name: 'When Executed by Another Workflow', desc: 'Runs the flow when called by the Execute Workflow node from a different workflow', icon: 'play', category: 'trigger' },
    { id: 'chat', name: 'On chat message', desc: 'Runs the flow when a user sends a chat message. For use with AI nodes', icon: 'chat', category: 'trigger' },
    { id: 'other', name: 'Other ways...', desc: 'Runs the flow on workflow errors, file changes, etc.', icon: 'folder', category: 'trigger', arrow: true }
];

const ACTIONS = [
    { id: 'http-request', name: 'HTTP Request', desc: 'Make HTTP requests to any URL', icon: 'http', category: 'action' },
    { id: 'code', name: 'Code', desc: 'Run JavaScript or Python code', icon: 'code', category: 'action' },
    { id: 'set', name: 'Set', desc: 'Set workflow data', icon: 'set', category: 'action' },
    { id: 'if', name: 'IF', desc: 'Route items based on conditions', icon: 'if', category: 'flow' },
    { id: 'switch', name: 'Switch', desc: 'Route items to different branches', icon: 'switch', category: 'flow' },
    { id: 'merge', name: 'Merge', desc: 'Merge data from multiple sources', icon: 'merge', category: 'flow' },
    { id: 'split', name: 'Split Out', desc: 'Split data into separate items', icon: 'split', category: 'flow' },
    { id: 'loop', name: 'Loop Over Items', desc: 'Loop through items one by one', icon: 'loop', category: 'flow' },
    { id: 'wait', name: 'Wait', desc: 'Wait for a specified time', icon: 'wait', category: 'flow' }
];

const ICONS = {
    cursor: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3l14 9-6 2-4 6-4-17z"/></svg>`,
    bell: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`,
    clock: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
    webhook: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 3v6m0 6v6M3 12h6m6 0h6"/></svg>`,
    form: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h10M7 12h10M7 17h6"/></svg>`,
    play: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>`,
    chat: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
    folder: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>`,
    arrow: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>`,
    http: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>`,
    code: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>`,
    set: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/></svg>`,
    if: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v18M18 3v18M6 12h12"/></svg>`,
    switch: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v18M16 3l-8 9 8 9"/></svg>`,
    merge: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v6l6 3 6-3V3M12 12v9"/></svg>`,
    split: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v6M12 9l-6 6v6M12 9l6 6v6"/></svg>`,
    loop: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 2l4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/></svg>`,
    wait: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`
};

// ============================================
// Application State
// ============================================
const state = {
    nodes: [],
    connections: [],
    selectedNodes: new Set(),
    nextId: 1,
    zoom: 1,
    panX: 0,
    panY: 0,
    
    // Drag states
    isDraggingNode: false,
    isDraggingCanvas: false,
    isSelecting: false,
    isConnecting: false,
    
    // Drag data
    dragStartX: 0,
    dragStartY: 0,
    dragOffsets: new Map(),
    
    // Selection box
    selectionStart: { x: 0, y: 0 },
    
    // Connection
    connectingFrom: null,
    
    // Panel
    connectingNodeId: null,
    
    // Context menu
    contextNodeId: null,
    
    // Keyboard
    spacePressed: false,
    ctrlPressed: false
};

// ============================================
// Initialization
// ============================================
$(document).ready(() => {
    renderTriggerPanel();
    renderActionPanel();
    bindEvents();
});

function renderTriggerPanel() {
    const $c = $('#node-panel-content').empty();
    TRIGGERS.forEach(t => {
        $c.append(createPanelItem(t));
    });
}

function renderActionPanel() {
    const $c = $('#action-panel-content').empty();
    
    $c.append('<div class="panel-category">Actions</div>');
    ACTIONS.filter(a => a.category === 'action').forEach(a => {
        $c.append(createPanelItem(a));
    });
    
    $c.append('<div class="panel-category">Flow</div>');
    ACTIONS.filter(a => a.category === 'flow').forEach(a => {
        $c.append(createPanelItem(a));
    });
}

function createPanelItem(item) {
    return `
        <div class="panel-item" data-id="${item.id}" data-name="${item.name}" data-icon="${item.icon}" data-category="${item.category}">
            <div class="panel-item-icon">${ICONS[item.icon]}</div>
            <div class="panel-item-content">
                <div class="panel-item-name">${item.name}</div>
                <div class="panel-item-desc">${item.desc}</div>
            </div>
            ${item.arrow ? `<div class="panel-item-arrow">${ICONS.arrow}</div>` : ''}
        </div>
    `;
}

// ============================================
// Event Bindings
// ============================================
function bindEvents() {
    // Panel triggers
    $('#add-first-step, #add-node-btn').on('click', () => openPanel('node-panel'));
    
    // Panel item clicks
    $('#node-panel-content').on('click', '.panel-item', handleTriggerSelect);
    $('#action-panel-content').on('click', '.panel-item', handleActionSelect);
    
    // Search
    $('#node-search').on('input', function() { filterPanel($(this).val(), '#node-panel-content'); });
    $('#action-search').on('input', function() { filterPanel($(this).val(), '#action-panel-content'); });
    
    // Canvas events
    $('#canvas-wrapper')
        .on('mousedown', handleCanvasMouseDown)
        .on('wheel', handleWheel)
        .on('contextmenu', e => e.preventDefault());
    
    $(document)
        .on('mousemove', handleMouseMove)
        .on('mouseup', handleMouseUp);
    
    // Node events (delegated)
    $('#nodes-container')
        .on('mousedown', '.canvas-node', handleNodeMouseDown)
        .on('dblclick', '.canvas-node', handleNodeDoubleClick)
        .on('mousedown', '.node-handle.output', handleOutputHandleClick)
        .on('mousedown', '.node-handle.input', handleInputHandleClick)
        .on('contextmenu', '.canvas-node', handleNodeContextMenu);
    
    // Connection events
    $(document).on('click', '.connection-path', handleConnectionClick);
    
    // NDV
    $('#ndv-back').on('click', closeNdv);
    $('#btn-execute').on('click', executeNode);
    
    // View toggles
    $(document).on('click', '.view-toggle-btn', function() {
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
    });
    
    $(document).on('click', '.ndv-tab', function() {
        $(this).siblings('.ndv-tab').removeClass('active');
        $(this).addClass('active');
    });
    
    // Context menu
    $(document).on('click', '.context-menu-item', handleContextMenuAction);
    $(document).on('click', hideContextMenu);
    
    // Keyboard
    $(document).on('keydown', handleKeyDown);
    $(document).on('keyup', handleKeyUp);
    
    // Zoom controls
    $('#btn-zoom-in').on('click', () => setZoom(state.zoom + 0.1));
    $('#btn-zoom-out').on('click', () => setZoom(state.zoom - 0.1));
    $('#btn-fit').on('click', fitToView);
    $('#btn-tidy').on('click', tidyNodes);
    
    // Close panels on outside click
    $('#canvas-wrapper').on('click', function(e) {
        if (!$(e.target).closest('.node-panel, .canvas-node, .add-node-btn, .add-first-step, .control-btn, .node-handle').length) {
            closeAllPanels();
        }
    });
}

// ============================================
// Panel Handlers
// ============================================
function openPanel(id) {
    closeAllPanels();
    $(`#${id}`).addClass('open');
    $(`#${id} input`).focus();
}

function closeAllPanels() {
    $('.node-panel').removeClass('open');
    state.connectingNodeId = null;
}

function filterPanel(query, selector) {
    const q = query.toLowerCase();
    $(`${selector} .panel-item`).each(function() {
        const name = $(this).find('.panel-item-name').text().toLowerCase();
        const desc = $(this).find('.panel-item-desc').text().toLowerCase();
        $(this).toggle(name.includes(q) || desc.includes(q));
    });
}

function handleTriggerSelect() {
    const data = getPanelItemData($(this));
    const pos = getCanvasCenter();
    createNode(data, pos.x, pos.y);
    closeAllPanels();
}

function handleActionSelect() {
    const data = getPanelItemData($(this));
    
    if (state.connectingNodeId) {
        // Add node and connect
        const sourceNode = state.nodes.find(n => n.id === state.connectingNodeId);
        const pos = { x: sourceNode.x + 250, y: sourceNode.y };
        const newNode = createNode(data, pos.x, pos.y);
        createConnection(state.connectingNodeId, newNode.id);
    } else {
        const pos = getCanvasCenter();
        createNode(data, pos.x, pos.y);
    }
    closeAllPanels();
}

function getPanelItemData($item) {
    return {
        type: $item.data('id'),
        name: $item.data('name'),
        icon: $item.data('icon'),
        category: $item.data('category')
    };
}

// ============================================
// Node Operations
// ============================================
function createNode(data, x, y) {
    const node = {
        id: state.nextId++,
        type: data.type,
        name: data.name,
        icon: data.icon,
        category: data.category,
        x, y,
        disabled: false
    };
    
    state.nodes.push(node);
    renderNode(node);
    updateFirstStepVisibility();
    selectNode(node.id, false);
    toast(`Added "${node.name}"`);
    return node;
}

function renderNode(node) {
    const hasInput = node.category !== 'trigger';
    const iconClass = node.category === 'trigger' ? 'trigger' : 'action';
    
    const html = `
        <div class="canvas-node${node.disabled ? ' disabled' : ''}" data-node-id="${node.id}" style="left:${node.x}px;top:${node.y}px">
            ${hasInput ? '<div class="node-handle input"></div>' : ''}
            <div class="canvas-node-content">
                <div class="canvas-node-icon ${iconClass}">${ICONS[node.icon]}</div>
                <div class="canvas-node-title">${node.name}</div>
            </div>
            <div class="node-handle output"></div>
        </div>
    `;
    $('#nodes-container').append(html);
}

function deleteNode(id) {
    // Remove connections
    state.connections = state.connections.filter(c => c.from !== id && c.to !== id);
    renderConnections();
    
    // Remove node
    state.nodes = state.nodes.filter(n => n.id !== id);
    $(`.canvas-node[data-node-id="${id}"]`).remove();
    state.selectedNodes.delete(id);
    
    updateFirstStepVisibility();
    toast('Node deleted');
}

function duplicateNode(id) {
    const node = state.nodes.find(n => n.id === id);
    if (!node) return;
    
    createNode({
        type: node.type,
        name: node.name,
        icon: node.icon,
        category: node.category
    }, node.x + 50, node.y + 50);
}

function toggleNodeDisabled(id) {
    const node = state.nodes.find(n => n.id === id);
    if (!node) return;
    
    node.disabled = !node.disabled;
    $(`.canvas-node[data-node-id="${id}"]`).toggleClass('disabled', node.disabled);
    toast(node.disabled ? 'Node disabled' : 'Node enabled');
}

function updateFirstStepVisibility() {
    $('#add-first-step').toggleClass('hidden', state.nodes.length > 0);
}

// ============================================
// Selection
// ============================================
function selectNode(id, addToSelection) {
    if (!addToSelection) {
        state.selectedNodes.clear();
        $('.canvas-node').removeClass('selected');
    }
    
    if (id !== null) {
        state.selectedNodes.add(id);
        $(`.canvas-node[data-node-id="${id}"]`).addClass('selected');
    }
}

function deselectAll() {
    state.selectedNodes.clear();
    $('.canvas-node').removeClass('selected');
}

function selectNodesInRect(x1, y1, x2, y2) {
    const minX = Math.min(x1, x2);
    const maxX = Math.max(x1, x2);
    const minY = Math.min(y1, y2);
    const maxY = Math.max(y1, y2);
    
    state.nodes.forEach(node => {
        const nodeRight = node.x + 150;
        const nodeBottom = node.y + 60;
        
        if (node.x < maxX && nodeRight > minX && node.y < maxY && nodeBottom > minY) {
            state.selectedNodes.add(node.id);
            $(`.canvas-node[data-node-id="${node.id}"]`).addClass('selected');
        }
    });
}

function selectAll() {
    state.nodes.forEach(node => {
        state.selectedNodes.add(node.id);
        $(`.canvas-node[data-node-id="${node.id}"]`).addClass('selected');
    });
}

// ============================================
// Connections
// ============================================
function createConnection(fromId, toId) {
    // Check if connection already exists
    if (state.connections.some(c => c.from === fromId && c.to === toId)) return;
    
    state.connections.push({ from: fromId, to: toId });
    renderConnections();
}

function renderConnections() {
    const $group = $('#connections-group').empty();
    // Remove old labels
    $('.connection-label').remove();
    
    state.connections.forEach((conn, index) => {
        const fromNode = state.nodes.find(n => n.id === conn.from);
        const toNode = state.nodes.find(n => n.id === conn.to);
        if (!fromNode || !toNode) return;
        
        const x1 = fromNode.x + 158;
        const y1 = fromNode.y + 30;
        const x2 = toNode.x - 8;
        const y2 = toNode.y + 30;
        
        const path = createBezierPath(x1, y1, x2, y2);
        
        $group.append(`<path class="connection-path" d="${path}" data-from="${conn.from}" data-to="${conn.to}" data-index="${index}"/>`);
        
        // Add label at midpoint
        const midX = (x1 + x2) / 2;
        const midY = (y1 + y2) / 2;
        
        // Only show label if connection has been executed (for now show placeholder)
        if (conn.itemCount) {
            $('#canvas-viewport').append(`
                <div class="connection-label" style="left:${midX}px;top:${midY}px">
                    ${conn.itemCount} item${conn.itemCount !== 1 ? 's' : ''}
                </div>
            `);
        }
    });
}

function createBezierPath(x1, y1, x2, y2) {
    const dx = x2 - x1;
    const dy = y2 - y1;
    
    // Calculate control point offset based on distance
    const cpOffset = Math.min(Math.max(Math.abs(dx) * 0.5, 50), 150);
    
    // For connections going backwards (right to left), adjust curve
    if (dx < 0) {
        const cp1x = x1 + cpOffset;
        const cp1y = y1;
        const cp2x = x2 - cpOffset;
        const cp2y = y2;
        return `M${x1},${y1} C${cp1x},${cp1y} ${cp2x},${cp2y} ${x2},${y2}`;
    }
    
    return `M${x1},${y1} C${x1 + cpOffset},${y1} ${x2 - cpOffset},${y2} ${x2},${y2}`;
}

function updateTempConnection(x1, y1, x2, y2) {
    const path = createBezierPath(x1, y1, x2, y2);
    $('#temp-connection').attr('d', path);
}

function clearTempConnection() {
    $('#temp-connection').attr('d', '');
}

// ============================================
// Mouse Handlers
// ============================================
function handleCanvasMouseDown(e) {
    if ($(e.target).closest('.canvas-node, .node-panel, .add-node-btn, .add-first-step, .control-btn').length) return;
    
    hideContextMenu();
    
    const canPan = state.spacePressed || state.ctrlPressed || e.button === 1;
    
    if (canPan) {
        // Start panning
        state.isDraggingCanvas = true;
        state.dragStartX = e.clientX - state.panX;
        state.dragStartY = e.clientY - state.panY;
        $('body').css('cursor', 'grabbing');
    } else if (e.button === 0) {
        // Start selection box
        deselectAll();
        state.isSelecting = true;
        const pos = screenToCanvas(e.clientX, e.clientY);
        state.selectionStart = pos;
        
        $('#selection-box').css({
            left: pos.x,
            top: pos.y,
            width: 0,
            height: 0
        }).addClass('active');
    }
}

function handleNodeMouseDown(e) {
    if ($(e.target).hasClass('node-handle')) return;
    
    e.stopPropagation();
    hideContextMenu();
    
    const nodeId = +$(this).data('node-id');
    
    // Handle selection
    if (state.ctrlPressed) {
        // Toggle selection
        if (state.selectedNodes.has(nodeId)) {
            state.selectedNodes.delete(nodeId);
            $(this).removeClass('selected');
        } else {
            state.selectedNodes.add(nodeId);
            $(this).addClass('selected');
        }
    } else if (!state.selectedNodes.has(nodeId)) {
        selectNode(nodeId, false);
    }
    
    // Start dragging
    state.isDraggingNode = true;
    state.dragStartX = e.clientX;
    state.dragStartY = e.clientY;
    
    // Store offsets for all selected nodes
    state.dragOffsets.clear();
    state.selectedNodes.forEach(id => {
        const node = state.nodes.find(n => n.id === id);
        if (node) {
            state.dragOffsets.set(id, { x: node.x, y: node.y });
        }
    });
    
    $('.canvas-node.selected').addClass('dragging');
}

function handleOutputHandleClick(e) {
    e.stopPropagation();
    e.preventDefault();
    
    const $node = $(this).closest('.canvas-node');
    const nodeId = +$node.data('node-id');
    const node = state.nodes.find(n => n.id === nodeId);
    
    // Start drawing connection
    state.isConnecting = true;
    state.connectingFrom = nodeId;
    state.connectingNodeId = nodeId;
    
    // Set initial temp connection
    if (node) {
        const startX = node.x + 158;
        const startY = node.y + 30;
        updateTempConnection(startX, startY, startX + 50, startY);
    }
}

function handleInputHandleClick(e) {
    e.stopPropagation();
    e.preventDefault();
    // Input handle click does nothing by itself
    // It's used as a drop target for connections
}

function handleNodeDoubleClick(e) {
    e.stopPropagation();
    const nodeId = +$(this).data('node-id');
    openNdv(nodeId);
}

function handleMouseMove(e) {
    if (state.isDraggingCanvas) {
        state.panX = e.clientX - state.dragStartX;
        state.panY = e.clientY - state.dragStartY;
        updateTransform();
    }
    else if (state.isDraggingNode) {
        const dx = (e.clientX - state.dragStartX) / state.zoom;
        const dy = (e.clientY - state.dragStartY) / state.zoom;
        
        state.selectedNodes.forEach(id => {
            const offset = state.dragOffsets.get(id);
            if (!offset) return;
            
            const node = state.nodes.find(n => n.id === id);
            if (node) {
                node.x = offset.x + dx;
                node.y = offset.y + dy;
                $(`.canvas-node[data-node-id="${id}"]`).css({
                    left: node.x,
                    top: node.y
                });
            }
        });
        
        renderConnections();
    }
    else if (state.isSelecting) {
        const pos = screenToCanvas(e.clientX, e.clientY);
        const x = Math.min(state.selectionStart.x, pos.x);
        const y = Math.min(state.selectionStart.y, pos.y);
        const w = Math.abs(pos.x - state.selectionStart.x);
        const h = Math.abs(pos.y - state.selectionStart.y);
        
        $('#selection-box').css({ left: x, top: y, width: w, height: h });
    }
    else if (state.isConnecting && state.connectingFrom) {
        const fromNode = state.nodes.find(n => n.id === state.connectingFrom);
        if (fromNode) {
            const pos = screenToCanvas(e.clientX, e.clientY);
            const startX = fromNode.x + 158;
            const startY = fromNode.y + 30;
            updateTempConnection(startX, startY, pos.x, pos.y);
            
            // Highlight potential drop targets
            $('.node-handle.input').removeClass('can-drop');
            const $target = $(document.elementFromPoint(e.clientX, e.clientY));
            if ($target.hasClass('input')) {
                const $targetNode = $target.closest('.canvas-node');
                const targetId = +$targetNode.data('node-id');
                // Don't allow self-connection
                if (targetId !== state.connectingFrom) {
                    $target.addClass('can-drop');
                }
            }
        }
    }
}

function handleMouseUp(e) {
    if (state.isDraggingCanvas) {
        state.isDraggingCanvas = false;
        $('body').css('cursor', '');
    }
    
    if (state.isDraggingNode) {
        state.isDraggingNode = false;
        $('.canvas-node').removeClass('dragging');
    }
    
    if (state.isSelecting) {
        state.isSelecting = false;
        const $box = $('#selection-box');
        const pos = screenToCanvas(e.clientX, e.clientY);
        selectNodesInRect(state.selectionStart.x, state.selectionStart.y, pos.x, pos.y);
        $box.removeClass('active');
    }
    
    if (state.isConnecting) {
        const fromId = state.connectingFrom;
        
        // Check if dropped on an input handle
        const $target = $(document.elementFromPoint(e.clientX, e.clientY));
        if ($target.hasClass('input')) {
            const $targetNode = $target.closest('.canvas-node');
            const toId = +$targetNode.data('node-id');
            
            if (toId !== fromId) {
                createConnection(fromId, toId);
            }
        } else if (!$(e.target).closest('.node-panel').length) {
            // Dropped on empty space - open action panel
            openPanel('action-panel');
        }
        
        state.isConnecting = false;
        state.connectingFrom = null;
        clearTempConnection();
        $('.node-handle.input').removeClass('can-drop');
    }
}

function handleWheel(e) {
    e.preventDefault();
    const delta = e.originalEvent.deltaY > 0 ? -0.1 : 0.1;
    setZoom(state.zoom + delta);
}

function handleConnectionClick(e) {
    e.stopPropagation();
    const fromId = +$(this).data('from');
    const toId = +$(this).data('to');
    
    // Remove the connection
    state.connections = state.connections.filter(c => !(c.from === fromId && c.to === toId));
    renderConnections();
    toast('Connection removed');
}

// ============================================
// Keyboard Handlers
// ============================================
function handleKeyDown(e) {
    if (e.key === ' ') {
        state.spacePressed = true;
        e.preventDefault();
    }
    if (e.key === 'Control' || e.key === 'Meta') {
        state.ctrlPressed = true;
    }
    
    // Don't process shortcuts when typing
    if ($(e.target).is('input, textarea')) return;
    
    if (e.key === 'Escape') {
        closeAllPanels();
        closeNdv();
        deselectAll();
        hideContextMenu();
    }
    
    if ((e.key === 'Delete' || e.key === 'Backspace') && state.selectedNodes.size > 0) {
        state.selectedNodes.forEach(id => deleteNode(id));
    }
    
    if (e.key === 'a' && state.ctrlPressed) {
        e.preventDefault();
        selectAll();
    }
    
    if (e.key === 'd' && state.ctrlPressed && state.selectedNodes.size > 0) {
        e.preventDefault();
        const ids = [...state.selectedNodes];
        ids.forEach(id => duplicateNode(id));
    }
}

function handleKeyUp(e) {
    if (e.key === ' ') state.spacePressed = false;
    if (e.key === 'Control' || e.key === 'Meta') state.ctrlPressed = false;
}

// ============================================
// Context Menu
// ============================================
function handleNodeContextMenu(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const nodeId = +$(this).data('node-id');
    state.contextNodeId = nodeId;
    
    if (!state.selectedNodes.has(nodeId)) {
        selectNode(nodeId, false);
    }
    
    $('#context-menu').css({
        left: e.clientX,
        top: e.clientY
    }).addClass('visible');
}

function hideContextMenu() {
    $('#context-menu').removeClass('visible');
    state.contextNodeId = null;
}

function handleContextMenuAction() {
    const action = $(this).data('action');
    
    if (state.selectedNodes.size > 0) {
        const ids = [...state.selectedNodes];
        
        switch(action) {
            case 'execute':
                toast('Executing nodes...');
                break;
            case 'rename':
                if (ids.length === 1) openNdv(ids[0]);
                break;
            case 'duplicate':
                ids.forEach(id => duplicateNode(id));
                break;
            case 'disable':
                ids.forEach(id => toggleNodeDisabled(id));
                break;
            case 'delete':
                ids.forEach(id => deleteNode(id));
                break;
        }
    }
    
    hideContextMenu();
}

// ============================================
// NDV (Node Detail View)
// ============================================
function openNdv(nodeId) {
    const node = state.nodes.find(n => n.id === nodeId);
    if (!node) return;
    
    $('#ndv-node-icon').html(ICONS[node.icon]).attr('class', `ndv-node-icon ${node.category}`);
    $('#ndv-node-name').val(node.name);
    
    $('#ndv-form-content').html(`
        <div class="form-group">
            <p style="color: var(--text-muted);">This node will execute when triggered.</p>
        </div>
        <div class="form-section">
            <div class="form-section-title">Options</div>
            <div class="form-section-empty">No properties</div>
            <button class="btn-add-field">Add Field <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></button>
        </div>
    `);
    
    $('#ndv-overlay').addClass('open');
}

function closeNdv() {
    $('#ndv-overlay').removeClass('open');
}

function executeNode() {
    const name = $('#ndv-node-name').val();
    toast(`Executed "${name}"`);
    
    // Find the current node and update outgoing connections with item count
    const currentId = state.nodes.find(n => n.name === name)?.id;
    if (currentId) {
        state.connections.forEach(conn => {
            if (conn.from === currentId) {
                conn.itemCount = Math.floor(Math.random() * 5) + 1; // Random 1-5 items for demo
            }
        });
        renderConnections();
    }
    
    $('#ndv-output-content').html(`
        <div class="json-viewer">
            <span class="json-bracket">[</span><br>
            &nbsp;&nbsp;<span class="json-bracket">{</span><br>
            &nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"success"</span>: <span class="json-number">true</span>,<br>
            &nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"timestamp"</span>: <span class="json-string">"${new Date().toISOString()}"</span><br>
            &nbsp;&nbsp;<span class="json-bracket">}</span><br>
            <span class="json-bracket">]</span>
        </div>
    `);
}

// ============================================
// Canvas Utilities
// ============================================
function screenToCanvas(screenX, screenY) {
    const rect = $('#canvas-wrapper')[0].getBoundingClientRect();
    return {
        x: (screenX - rect.left - state.panX) / state.zoom,
        y: (screenY - rect.top - state.panY) / state.zoom
    };
}

function getCanvasCenter() {
    const $c = $('#canvas-wrapper');
    return screenToCanvas($c.width() / 2, $c.height() / 2);
}

function setZoom(z) {
    state.zoom = Math.max(0.25, Math.min(2, z));
    updateTransform();
}

function updateTransform() {
    $('#canvas-viewport').css('transform', `translate(${state.panX}px, ${state.panY}px) scale(${state.zoom})`);
}

function fitToView() {
    if (state.nodes.length === 0) {
        state.zoom = 1;
        state.panX = 0;
        state.panY = 0;
    } else {
        const bounds = getNodesBounds();
        const $c = $('#canvas-wrapper');
        const padding = 100;
        
        const scaleX = ($c.width() - padding * 2) / (bounds.maxX - bounds.minX + 150);
        const scaleY = ($c.height() - padding * 2) / (bounds.maxY - bounds.minY + 60);
        
        state.zoom = Math.min(Math.max(Math.min(scaleX, scaleY), 0.25), 1);
        state.panX = ($c.width() - (bounds.minX + bounds.maxX + 150) * state.zoom) / 2;
        state.panY = ($c.height() - (bounds.minY + bounds.maxY + 60) * state.zoom) / 2;
    }
    updateTransform();
}

function getNodesBounds() {
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    state.nodes.forEach(n => {
        minX = Math.min(minX, n.x);
        minY = Math.min(minY, n.y);
        maxX = Math.max(maxX, n.x);
        maxY = Math.max(maxY, n.y);
    });
    return { minX, minY, maxX, maxY };
}

function tidyNodes() {
    const cols = Math.ceil(Math.sqrt(state.nodes.length));
    const spacing = { x: 250, y: 100 };
    
    state.nodes.forEach((node, i) => {
        node.x = (i % cols) * spacing.x;
        node.y = Math.floor(i / cols) * spacing.y;
        $(`.canvas-node[data-node-id="${node.id}"]`).css({
            left: node.x,
            top: node.y
        });
    });
    
    renderConnections();
    fitToView();
    toast('Nodes tidied');
}

// ============================================
// Toast
// ============================================
function toast(msg) {
    const $t = $(`<div class="toast">${msg}</div>`);
    $('#toast-container').append($t);
    setTimeout(() => $t.fadeOut(300, () => $t.remove()), 3000);
}
