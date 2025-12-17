/* ============================================
   Utility Functions
   ============================================ */

const Utils = {
    // Generate unique ID
    generateId: function() {
        return 'node_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 9);
    },

    // Deep clone object
    deepClone: function(obj) {
        return JSON.parse(JSON.stringify(obj));
    },

    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Calculate bezier curve path for connections
    getBezierPath: function(sourceX, sourceY, targetX, targetY) {
        const dx = Math.abs(targetX - sourceX);
        const dy = Math.abs(targetY - sourceY);
        const curvature = 0.5;
        
        // Control point offset based on distance
        const controlOffset = Math.min(Math.max(dx * curvature, 50), 150);
        
        // Source control point (exit to the right)
        const sourceControlX = sourceX + controlOffset;
        const sourceControlY = sourceY;
        
        // Target control point (enter from the left)
        const targetControlX = targetX - controlOffset;
        const targetControlY = targetY;
        
        return `M ${sourceX} ${sourceY} C ${sourceControlX} ${sourceControlY}, ${targetControlX} ${targetControlY}, ${targetX} ${targetY}`;
    },

    // Get center point of bezier curve (for delete button positioning)
    getBezierCenter: function(sourceX, sourceY, targetX, targetY) {
        // Simple approximation: midpoint
        return {
            x: (sourceX + targetX) / 2,
            y: (sourceY + targetY) / 2
        };
    },

    // Snap value to grid
    snapToGrid: function(value, gridSize) {
        return Math.round(value / gridSize) * gridSize;
    },

    // Check if point is inside rectangle
    pointInRect: function(px, py, rect) {
        return px >= rect.left && px <= rect.right && py >= rect.top && py <= rect.bottom;
    },

    // Check if two rectangles overlap
    rectsOverlap: function(rect1, rect2) {
        return !(rect1.right < rect2.left || 
                 rect1.left > rect2.right || 
                 rect1.bottom < rect2.top || 
                 rect1.top > rect2.bottom);
    },

    // Get element center position
    getElementCenter: function($el) {
        const rect = $el[0].getBoundingClientRect();
        return {
            x: rect.left + rect.width / 2,
            y: rect.top + rect.height / 2
        };
    },

    // Convert screen coordinates to canvas coordinates
    screenToCanvas: function(screenX, screenY, transform) {
        return {
            x: (screenX - transform.x) / transform.scale,
            y: (screenY - transform.y) / transform.scale
        };
    },

    // Convert canvas coordinates to screen coordinates
    canvasToScreen: function(canvasX, canvasY, transform) {
        return {
            x: canvasX * transform.scale + transform.x,
            y: canvasY * transform.scale + transform.y
        };
    },

    // Show toast notification
    showToast: function(message, type = 'info', duration = 3000) {
        const icons = {
            success: 'lucide-check-circle',
            error: 'lucide-x-circle',
            warning: 'lucide-alert-circle',
            info: 'lucide-info'
        };

        const $toast = $(`
            <div class="toast toast-${type}">
                <i class="toast-icon ${icons[type]}"></i>
                <span class="toast-message">${message}</span>
            </div>
        `);

        $('#toast-container').append($toast);

        setTimeout(() => {
            $toast.addClass('toast-out');
            setTimeout(() => $toast.remove(), 300);
        }, duration);
    },

    // Format execution time
    formatDuration: function(ms) {
        if (ms < 1000) return `${ms}ms`;
        if (ms < 60000) return `${(ms / 1000).toFixed(1)}s`;
        return `${Math.floor(ms / 60000)}m ${Math.floor((ms % 60000) / 1000)}s`;
    },

    // Escape HTML
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    // Get node handle position in canvas coordinates
    getHandlePosition: function($node, handleType) {
        const node = $node[0];
        const nodeRect = node.getBoundingClientRect();
        
        if (handleType === 'output') {
            return {
                x: nodeRect.right,
                y: nodeRect.top + nodeRect.height / 2
            };
        } else {
            return {
                x: nodeRect.left,
                y: nodeRect.top + nodeRect.height / 2
            };
        }
    },

    // Storage helpers
    storage: {
        set: function(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                console.warn('Failed to save to localStorage:', e);
            }
        },
        get: function(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                console.warn('Failed to read from localStorage:', e);
                return defaultValue;
            }
        },
        remove: function(key) {
            try {
                localStorage.removeItem(key);
            } catch (e) {
                console.warn('Failed to remove from localStorage:', e);
            }
        }
    },

    // Event bus for component communication
    events: {
        _listeners: {},
        
        on: function(event, callback) {
            if (!this._listeners[event]) {
                this._listeners[event] = [];
            }
            this._listeners[event].push(callback);
        },
        
        off: function(event, callback) {
            if (!this._listeners[event]) return;
            this._listeners[event] = this._listeners[event].filter(cb => cb !== callback);
        },
        
        emit: function(event, data) {
            if (!this._listeners[event]) return;
            this._listeners[event].forEach(callback => callback(data));
        }
    }
};

// Make available globally
window.Utils = Utils;
