{{-- Dashboard Scripts --}}
<script>
(function() {
    'use strict';

    // Dashboard Manager Class
    class DashboardManager {
        constructor() {
            this.container = document.querySelector('.dashboard-page');
            this.grid = document.getElementById('widgetGrid');
            this.currentDashboard = this.container?.dataset.dashboard || 'main';
            this.widgets = [];
            this.isDragging = false;
            this.draggedWidget = null;
            this.saveTimeout = null;
            
            this.init();
        }

        init() {
            if (!this.container) return;
            
            this.bindEvents();
            this.loadWidgetData();
            this.initializeGreeting();
        }

        bindEvents() {
            // Add Widget Modal
            const addWidgetBtn = document.getElementById('addWidgetBtn');
            const addFirstWidgetBtn = document.getElementById('addFirstWidgetBtn');
            const closeModalBtn = document.getElementById('closeWidgetModal');
            const modal = document.getElementById('addWidgetModal');

            if (addWidgetBtn) {
                addWidgetBtn.addEventListener('click', () => this.openAddWidgetModal());
            }
            
            if (addFirstWidgetBtn) {
                addFirstWidgetBtn.addEventListener('click', () => this.openAddWidgetModal());
            }
            
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', () => this.closeAddWidgetModal());
            }
            
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) this.closeAddWidgetModal();
                });
            }

            // Widget option click handlers
            document.querySelectorAll('.btn-add-this-widget').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const option = e.target.closest('.widget-option');
                    const widgetId = option.dataset.widgetId;
                    const pluginSlug = option.dataset.pluginSlug;
                    this.addWidget(widgetId, pluginSlug);
                });
            });

            // Widget action handlers
            this.grid?.addEventListener('click', (e) => {
                const btn = e.target.closest('.widget-action-btn');
                if (!btn) return;

                const widget = btn.closest('.widget');
                const widgetId = widget.dataset.widgetId;

                if (btn.classList.contains('widget-refresh')) {
                    this.refreshWidget(widgetId, widget);
                } else if (btn.classList.contains('widget-remove')) {
                    this.removeWidget(widgetId, widget);
                } else if (btn.classList.contains('widget-settings')) {
                    this.openWidgetSettings(widgetId, widget);
                }
            });

            // Drag and Drop
            this.initDragAndDrop();

            // Customize button
            const customizeBtn = document.getElementById('customizeBtn');
            if (customizeBtn) {
                customizeBtn.addEventListener('click', () => this.toggleCustomizeMode());
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeAddWidgetModal();
                }
            });
        }

        initDragAndDrop() {
            if (!this.grid) return;

            this.grid.querySelectorAll('.widget').forEach(widget => {
                this.makeWidgetDraggable(widget);
            });
        }

        makeWidgetDraggable(widget) {
            const handle = widget.querySelector('.widget-drag-handle');
            if (!handle) return;

            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this.startDrag(widget, e);
            });

            handle.addEventListener('touchstart', (e) => {
                this.startDrag(widget, e.touches[0]);
            }, { passive: true });
        }

        startDrag(widget, e) {
            this.isDragging = true;
            this.draggedWidget = widget;
            widget.classList.add('dragging');
            
            const rect = widget.getBoundingClientRect();
            this.dragOffset = {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };

            const moveHandler = (e) => {
                if (!this.isDragging) return;
                const point = e.touches ? e.touches[0] : e;
                this.handleDrag(point);
            };

            const upHandler = () => {
                this.endDrag();
                document.removeEventListener('mousemove', moveHandler);
                document.removeEventListener('mouseup', upHandler);
                document.removeEventListener('touchmove', moveHandler);
                document.removeEventListener('touchend', upHandler);
            };

            document.addEventListener('mousemove', moveHandler);
            document.addEventListener('mouseup', upHandler);
            document.addEventListener('touchmove', moveHandler, { passive: true });
            document.addEventListener('touchend', upHandler);
        }

        handleDrag(point) {
            if (!this.draggedWidget) return;
            
            // Visual feedback during drag
            const widgets = this.grid.querySelectorAll('.widget:not(.dragging)');
            const draggedRect = this.draggedWidget.getBoundingClientRect();
            
            widgets.forEach(widget => {
                const rect = widget.getBoundingClientRect();
                const overlap = this.checkOverlap(draggedRect, rect);
                
                if (overlap > 0.3) {
                    widget.classList.add('drag-over');
                } else {
                    widget.classList.remove('drag-over');
                }
            });
        }

        checkOverlap(rect1, rect2) {
            const x_overlap = Math.max(0, Math.min(rect1.right, rect2.right) - Math.max(rect1.left, rect2.left));
            const y_overlap = Math.max(0, Math.min(rect1.bottom, rect2.bottom) - Math.max(rect1.top, rect2.top));
            const overlap_area = x_overlap * y_overlap;
            const min_area = Math.min(
                (rect1.right - rect1.left) * (rect1.bottom - rect1.top),
                (rect2.right - rect2.left) * (rect2.bottom - rect2.top)
            );
            return overlap_area / min_area;
        }

        endDrag() {
            if (!this.isDragging || !this.draggedWidget) return;
            
            this.isDragging = false;
            this.draggedWidget.classList.remove('dragging');
            
            // Find the widget we're overlapping with
            const widgets = Array.from(this.grid.querySelectorAll('.widget'));
            const draggedIndex = widgets.indexOf(this.draggedWidget);
            let targetIndex = draggedIndex;
            
            widgets.forEach((widget, index) => {
                if (widget !== this.draggedWidget && widget.classList.contains('drag-over')) {
                    targetIndex = index;
                    widget.classList.remove('drag-over');
                }
            });
            
            // Reorder widgets
            if (targetIndex !== draggedIndex) {
                if (targetIndex > draggedIndex) {
                    widgets[targetIndex].after(this.draggedWidget);
                } else {
                    widgets[targetIndex].before(this.draggedWidget);
                }
                
                this.debounceSaveLayout();
            }
            
            this.draggedWidget = null;
        }

        loadWidgetData() {
            // Load data for each widget that needs it
            this.grid?.querySelectorAll('.widget').forEach(widget => {
                const component = widget.querySelector('.widget-content')?.dataset.component;
                const widgetId = widget.dataset.widgetId;
                const pluginSlug = widget.dataset.pluginSlug;

                // Load data for dynamic widgets
                if (['stats', 'list', 'table', 'chart'].includes(component)) {
                    this.loadWidgetContent(widgetId, pluginSlug, widget);
                }
            });
        }

        async loadWidgetContent(widgetId, pluginSlug, widget) {
            widget.classList.add('loading');

            try {
                const params = new URLSearchParams();
                if (pluginSlug) params.append('plugin_slug', pluginSlug);

                const response = await fetch(`/dashboard/widgets/${widgetId}/data?${params}`);
                const result = await response.json();

                if (result.success && result.data) {
                    this.renderWidgetData(widget, result.data);
                }
            } catch (error) {
                console.error('Failed to load widget data:', error);
            } finally {
                widget.classList.remove('loading');
            }
        }

        renderWidgetData(widget, data) {
            const component = widget.querySelector('.widget-content')?.dataset.component;
            const body = widget.querySelector('.widget-body');

            switch (component) {
                case 'stats':
                    this.renderStatsWidget(body, data);
                    break;
                case 'list':
                    this.renderListWidget(body, data);
                    break;
                case 'table':
                    this.renderTableWidget(body, data);
                    break;
                case 'chart':
                    this.renderChartWidget(body, data);
                    break;
            }
        }

        renderStatsWidget(body, data) {
            const container = body.querySelector('.stats-items');
            if (!container || !data.data?.items) return;

            container.innerHTML = data.data.items.map(item => `
                <div class="stats-item">
                    <div class="stat-label">${item.label}</div>
                    <div class="stat-value">${item.value}</div>
                    ${item.status ? `<span class="stat-status ${item.status}">${item.status}</span>` : ''}
                </div>
            `).join('');
        }

        renderListWidget(body, data) {
            const container = body.querySelector('.widget-list-items');
            if (!container || !data.data?.items) return;

            container.innerHTML = data.data.items.map(item => `
                <div class="list-item">
                    <span class="list-item-title">${item.title}</span>
                    <span class="list-item-time">${item.time}</span>
                </div>
            `).join('');
        }

        renderTableWidget(body, data) {
            const table = body.querySelector('.mini-table tbody');
            if (!table || !data.data?.rows) return;

            table.innerHTML = data.data.rows.map(row => `
                <tr>
                    ${row.map(cell => `<td>${cell}</td>`).join('')}
                </tr>
            `).join('');
        }

        renderChartWidget(body, data) {
            // Chart rendering would require Chart.js
            const placeholder = body.querySelector('.chart-placeholder');
            if (placeholder) {
                placeholder.innerHTML = '<span>Chart data loaded</span>';
            }
        }

        initializeGreeting() {
            const greetingEl = document.querySelector('[data-greeting]');
            if (!greetingEl) return;

            const hour = new Date().getHours();
            let greeting = 'Good evening';
            
            if (hour < 12) {
                greeting = 'Good morning';
            } else if (hour < 17) {
                greeting = 'Good afternoon';
            }

            greetingEl.textContent = greeting;
        }

        openAddWidgetModal() {
            const modal = document.getElementById('addWidgetModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        closeAddWidgetModal() {
            const modal = document.getElementById('addWidgetModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        async addWidget(widgetId, pluginSlug) {
            try {
                const response = await fetch('/dashboard/widgets/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        widget_id: widgetId,
                        dashboard: this.currentDashboard,
                        plugin_slug: pluginSlug || null
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showNotification('Widget added successfully', 'success');
                    // Reload page to show new widget
                    window.location.reload();
                } else {
                    this.showNotification(result.message || 'Failed to add widget', 'error');
                }
            } catch (error) {
                console.error('Failed to add widget:', error);
                this.showNotification('Failed to add widget', 'error');
            }

            this.closeAddWidgetModal();
        }

        async removeWidget(widgetId, widgetElement) {
            if (!confirm('Are you sure you want to remove this widget?')) return;

            try {
                const response = await fetch(`/dashboard/widgets/${widgetId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        dashboard: this.currentDashboard
                    })
                });

                const result = await response.json();

                if (result.success) {
                    widgetElement.remove();
                    this.showNotification('Widget removed', 'success');
                    
                    // Check if grid is empty
                    if (this.grid.querySelectorAll('.widget').length === 0) {
                        window.location.reload();
                    }
                } else {
                    this.showNotification(result.message || 'Failed to remove widget', 'error');
                }
            } catch (error) {
                console.error('Failed to remove widget:', error);
                this.showNotification('Failed to remove widget', 'error');
            }
        }

        async refreshWidget(widgetId, widgetElement) {
            const pluginSlug = widgetElement.dataset.pluginSlug;
            await this.loadWidgetContent(widgetId, pluginSlug, widgetElement);
            this.showNotification('Widget refreshed', 'success');
        }

        openWidgetSettings(widgetId, widgetElement) {
            // TODO: Implement widget settings modal
            this.showNotification('Widget settings coming soon', 'info');
        }

        toggleCustomizeMode() {
            this.container.classList.toggle('customize-mode');
            const isCustomizing = this.container.classList.contains('customize-mode');
            
            if (isCustomizing) {
                this.showNotification('Customize mode enabled. Drag widgets to reorder.', 'info');
            } else {
                this.debounceSaveLayout();
            }
        }

        debounceSaveLayout() {
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => this.saveLayout(), 500);
        }

        async saveLayout() {
            const widgets = Array.from(this.grid.querySelectorAll('.widget')).map((widget, index) => ({
                widget_id: widget.dataset.widgetId,
                position: index,
                col: parseInt(widget.dataset.col) || 0,
                row: parseInt(widget.dataset.row) || 0,
                width: parseInt(widget.dataset.width) || 1,
                height: parseInt(widget.dataset.height) || 1,
                visible: true
            }));

            try {
                const response = await fetch('/dashboard/widgets/layout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        dashboard: this.currentDashboard,
                        widgets: widgets
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    console.error('Failed to save layout:', result.message);
                }
            } catch (error) {
                console.error('Failed to save layout:', error);
            }
        }

        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `dashboard-notification ${type}`;
            notification.innerHTML = `
                <span>${message}</span>
                <button type="button" class="notification-close">&times;</button>
            `;

            // Add styles if not exists
            if (!document.querySelector('#dashboard-notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'dashboard-notification-styles';
                styles.textContent = `
                    .dashboard-notification {
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        padding: 12px 20px;
                        background: #333;
                        color: white;
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        z-index: 9999;
                        animation: slideIn 0.3s ease;
                    }
                    .dashboard-notification.success { background: #28a745; }
                    .dashboard-notification.error { background: #dc3545; }
                    .dashboard-notification.info { background: #17a2b8; }
                    .notification-close {
                        background: none;
                        border: none;
                        color: white;
                        font-size: 18px;
                        cursor: pointer;
                        padding: 0;
                        line-height: 1;
                        opacity: 0.7;
                    }
                    .notification-close:hover { opacity: 1; }
                    @keyframes slideIn {
                        from { transform: translateX(100px); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(styles);
            }

            document.body.appendChild(notification);

            // Close handlers
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.remove();
            });

            // Auto remove after 3 seconds
            setTimeout(() => notification.remove(), 3000);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new DashboardManager());
    } else {
        new DashboardManager();
    }
})();
</script>
