/* ============================================
   Canvas Module - Zoom, Pan, Grid
   ============================================ */

const Canvas = {
    // State
    $container: null,
    $viewport: null,
    panzoom: null,
    transform: { x: 0, y: 0, scale: 1 },
    isPanning: false,
    gridSize: 20,
    minZoom: 0.1,
    maxZoom: 3,
    
    // Initialize canvas
    init: function() {
        this.$container = $('#canvas-container');
        this.$viewport = $('#canvas-viewport');
        
        this.initPanZoom();
        this.bindEvents();
        this.centerCanvas();
        this.updateZoomDisplay();
        
        console.log('Canvas initialized');
    },
    
    // Initialize panzoom library
    initPanZoom: function() {
        const viewport = this.$viewport[0];
        
        this.panzoom = panzoom(viewport, {
            maxZoom: this.maxZoom,
            minZoom: this.minZoom,
            zoomDoubleClickSpeed: 1, // Disable double-click zoom
            smoothScroll: false,
            
            // Custom bounds to prevent losing the canvas
            bounds: true,
            boundsPadding: 0.5,
            
            // Don't zoom when clicking on nodes
            beforeWheel: (e) => {
                // Allow zoom only with Ctrl/Cmd key or when not over a node
                return !e.ctrlKey && !e.metaKey;
            },
            
            // Control which elements can initiate pan
            beforeMouseDown: (e) => {
                // Don't pan when clicking on interactive elements
                const target = e.target;
                const isInteractive = $(target).closest('.workflow-node, .node-handle, .btn, input, textarea, select').length > 0;
                
                if (isInteractive) {
                    return false; // Prevent panning
                }
                
                return true;
            },
            
            // Filter elements that shouldn't start pan
            filterKey: () => true,
            
            // Zoom speed
            zoomSpeed: 0.065
        });
        
        // Listen for transform changes
        this.panzoom.on('transform', (e) => {
            const transform = e.getTransform();
            this.transform = {
                x: transform.x,
                y: transform.y,
                scale: transform.scale
            };
            this.updateZoomDisplay();
            Utils.events.emit('canvas:transform', this.transform);
        });
        
        this.panzoom.on('panstart', () => {
            this.isPanning = true;
            this.$container.addClass('is-panning');
        });
        
        this.panzoom.on('panend', () => {
            this.isPanning = false;
            this.$container.removeClass('is-panning');
        });
    },
    
    // Bind event handlers
    bindEvents: function() {
        // Zoom controls
        $('#btn-zoom-in').on('click', () => this.zoomIn());
        $('#btn-zoom-out').on('click', () => this.zoomOut());
        $('#btn-fit-view, #btn-zoom-to-fit').on('click', () => this.fitView());
        
        // Mouse wheel zoom with Ctrl
        this.$container.on('wheel', (e) => {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                const delta = e.originalEvent.deltaY > 0 ? -0.1 : 0.1;
                const rect = this.$container[0].getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                this.zoomAtPoint(this.transform.scale + delta, x, y);
            }
        });
        
        // Double-click to add node (n8n behavior)
        this.$container.on('dblclick', (e) => {
            // Only if clicking on empty canvas
            if ($(e.target).closest('.workflow-node').length === 0) {
                const canvasPos = this.screenToCanvas(e.clientX, e.clientY);
                Utils.events.emit('canvas:dblclick', canvasPos);
            }
        });
        
        // Context menu
        this.$container.on('contextmenu', (e) => {
            if ($(e.target).closest('.workflow-node').length === 0) {
                e.preventDefault();
                Utils.events.emit('canvas:contextmenu', {
                    x: e.clientX,
                    y: e.clientY,
                    canvasPos: this.screenToCanvas(e.clientX, e.clientY)
                });
            }
        });
        
        // Click to deselect
        this.$container.on('mousedown', (e) => {
            if ($(e.target).closest('.workflow-node, .btn, .context-menu').length === 0) {
                Utils.events.emit('canvas:click');
            }
        });
        
        // Selection box
        this.initSelectionBox();
    },
    
    // Selection box for multi-select
    initSelectionBox: function() {
        let isSelecting = false;
        let startX, startY;
        const $selectionBox = $('#selection-box');
        
        this.$container.on('mousedown', (e) => {
            // Only start selection if clicking on canvas background
            if (e.target === this.$container[0] || $(e.target).hasClass('canvas-grid')) {
                if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
                    isSelecting = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    
                    $selectionBox.css({
                        left: startX,
                        top: startY,
                        width: 0,
                        height: 0
                    }).addClass('visible');
                }
            }
        });
        
        $(document).on('mousemove', (e) => {
            if (!isSelecting) return;
            
            const currentX = e.clientX;
            const currentY = e.clientY;
            
            const left = Math.min(startX, currentX);
            const top = Math.min(startY, currentY);
            const width = Math.abs(currentX - startX);
            const height = Math.abs(currentY - startY);
            
            $selectionBox.css({
                left: left,
                top: top,
                width: width,
                height: height
            });
        });
        
        $(document).on('mouseup', (e) => {
            if (!isSelecting) return;
            isSelecting = false;
            
            const rect = {
                left: parseInt($selectionBox.css('left')),
                top: parseInt($selectionBox.css('top')),
                right: parseInt($selectionBox.css('left')) + parseInt($selectionBox.css('width')),
                bottom: parseInt($selectionBox.css('top')) + parseInt($selectionBox.css('height'))
            };
            
            $selectionBox.removeClass('visible');
            
            // Only emit if selection box has area
            if (rect.right - rect.left > 5 && rect.bottom - rect.top > 5) {
                Utils.events.emit('canvas:selection', rect);
            }
        });
    },
    
    // Center canvas on initial load
    centerCanvas: function() {
        const containerRect = this.$container[0].getBoundingClientRect();
        
        // Center on the middle of the virtual canvas (5000, 5000)
        const centerX = -5000 + containerRect.width / 2;
        const centerY = -5000 + containerRect.height / 2;
        
        this.panzoom.moveTo(centerX, centerY);
    },
    
    // Zoom in
    zoomIn: function() {
        const newZoom = Math.min(this.transform.scale + 0.1, this.maxZoom);
        this.zoomTo(newZoom);
    },
    
    // Zoom out
    zoomOut: function() {
        const newZoom = Math.max(this.transform.scale - 0.1, this.minZoom);
        this.zoomTo(newZoom);
    },
    
    // Zoom to specific level
    zoomTo: function(scale) {
        const containerRect = this.$container[0].getBoundingClientRect();
        const centerX = containerRect.width / 2;
        const centerY = containerRect.height / 2;
        this.zoomAtPoint(scale, centerX, centerY);
    },
    
    // Zoom at specific point
    zoomAtPoint: function(scale, x, y) {
        scale = Math.max(this.minZoom, Math.min(this.maxZoom, scale));
        this.panzoom.zoomAbs(x, y, scale);
    },
    
    // Fit all nodes in view
    fitView: function() {
        const $nodes = $('.workflow-node');
        
        if ($nodes.length === 0) {
            this.centerCanvas();
            this.zoomTo(1);
            return;
        }
        
        // Get bounds of all nodes
        let minX = Infinity, minY = Infinity;
        let maxX = -Infinity, maxY = -Infinity;
        
        $nodes.each((i, node) => {
            const $node = $(node);
            const x = parseFloat($node.css('left'));
            const y = parseFloat($node.css('top'));
            const width = $node.outerWidth();
            const height = $node.outerHeight();
            
            minX = Math.min(minX, x);
            minY = Math.min(minY, y);
            maxX = Math.max(maxX, x + width);
            maxY = Math.max(maxY, y + height);
        });
        
        // Add padding
        const padding = 100;
        minX -= padding;
        minY -= padding;
        maxX += padding;
        maxY += padding;
        
        // Calculate required scale
        const containerRect = this.$container[0].getBoundingClientRect();
        const contentWidth = maxX - minX;
        const contentHeight = maxY - minY;
        
        const scaleX = containerRect.width / contentWidth;
        const scaleY = containerRect.height / contentHeight;
        let scale = Math.min(scaleX, scaleY, 1); // Don't zoom in past 100%
        scale = Math.max(this.minZoom, Math.min(this.maxZoom, scale));
        
        // Calculate position to center content
        const centerX = minX + contentWidth / 2;
        const centerY = minY + contentHeight / 2;
        
        const newX = containerRect.width / 2 - centerX * scale;
        const newY = containerRect.height / 2 - centerY * scale;
        
        // Apply transform
        this.panzoom.zoomAbs(0, 0, scale);
        this.panzoom.moveTo(newX, newY);
    },
    
    // Update zoom display
    updateZoomDisplay: function() {
        const zoomPercent = Math.round(this.transform.scale * 100);
        $('#zoom-level').text(zoomPercent + '%');
    },
    
    // Convert screen coordinates to canvas coordinates
    screenToCanvas: function(screenX, screenY) {
        const containerRect = this.$container[0].getBoundingClientRect();
        const relativeX = screenX - containerRect.left;
        const relativeY = screenY - containerRect.top;
        
        return {
            x: (relativeX - this.transform.x) / this.transform.scale,
            y: (relativeY - this.transform.y) / this.transform.scale
        };
    },
    
    // Convert canvas coordinates to screen coordinates
    canvasToScreen: function(canvasX, canvasY) {
        const containerRect = this.$container[0].getBoundingClientRect();
        
        return {
            x: canvasX * this.transform.scale + this.transform.x + containerRect.left,
            y: canvasY * this.transform.scale + this.transform.y + containerRect.top
        };
    },
    
    // Snap position to grid
    snapToGrid: function(x, y) {
        return {
            x: Utils.snapToGrid(x, this.gridSize),
            y: Utils.snapToGrid(y, this.gridSize)
        };
    },
    
    // Get current transform
    getTransform: function() {
        return { ...this.transform };
    },
    
    // Check if position is visible in viewport
    isInViewport: function(x, y) {
        const containerRect = this.$container[0].getBoundingClientRect();
        const screen = this.canvasToScreen(x, y);
        
        return screen.x >= containerRect.left && 
               screen.x <= containerRect.right &&
               screen.y >= containerRect.top && 
               screen.y <= containerRect.bottom;
    },
    
    // Pan to specific position
    panTo: function(x, y) {
        const containerRect = this.$container[0].getBoundingClientRect();
        const newX = containerRect.width / 2 - x * this.transform.scale;
        const newY = containerRect.height / 2 - y * this.transform.scale;
        
        this.panzoom.moveTo(newX, newY);
    },
    
    // Animate pan to position
    smoothPanTo: function(x, y) {
        const containerRect = this.$container[0].getBoundingClientRect();
        const newX = containerRect.width / 2 - x * this.transform.scale;
        const newY = containerRect.height / 2 - y * this.transform.scale;
        
        // Use CSS animation
        this.$viewport.css('transition', 'transform 0.3s ease');
        this.panzoom.moveTo(newX, newY);
        
        setTimeout(() => {
            this.$viewport.css('transition', '');
        }, 300);
    }
};

// Make available globally
window.Canvas = Canvas;
