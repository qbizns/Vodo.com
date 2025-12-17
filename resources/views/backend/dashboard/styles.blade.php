{{-- Dashboard Styles --}}
<style>
/* Dashboard Page Layout */
.dashboard-page {
    padding: 0;
    height: 100%;
    margin: 25px;
}

/* Widget Grid */
.widget-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    grid-auto-rows: minmax(180px, auto);
}

/* Widget Styles */
.widget {
    background: var(--background-primary, #ffffff);
    border-radius: 8px;
    border: 1px solid var(--border-color, #e5e5e5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}

.widget:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.widget.dragging {
    opacity: 0.6;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.widget-header {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color, #e5e5e5);
    background: var(--background-secondary, #fafafa);
    gap: 0.5rem;
}

.widget-drag-handle {
    cursor: grab;
    color: var(--text-tertiary, #999);
    display: flex;
    align-items: center;
}

.widget-drag-handle:active {
    cursor: grabbing;
}

.widget-drag-handle svg {
    width: 14px;
    height: 14px;
}

.widget-title-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 0;
}

.widget-icon {
    color: var(--accent-color, #0066cc);
    display: flex;
}

.widget-icon svg {
    width: 16px;
    height: 16px;
}

.widget-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary, #333);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.widget-actions {
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.widget:hover .widget-actions {
    opacity: 1;
}

.widget-action-btn {
    padding: 0.375rem;
    border: none;
    background: transparent;
    color: var(--text-tertiary, #999);
    cursor: pointer;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.widget-action-btn:hover {
    background: var(--background-hover, #e5e5e5);
    color: var(--text-primary, #333);
}

.widget-action-btn.widget-remove:hover {
    background: var(--danger-light, #fee);
    color: var(--danger-color, #dc3545);
}

.widget-action-btn svg {
    width: 14px;
    height: 14px;
}

.widget-content {
    flex: 1;
    position: relative;
    overflow: auto;
}

.widget-loading {
    position: absolute;
    inset: 0;
    background: var(--background-primary, #ffffff);
    display: none;
    align-items: center;
    justify-content: center;
}

.widget.loading .widget-loading {
    display: flex;
}

.widget-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid var(--border-color, #e5e5e5);
    border-top-color: var(--accent-color, #0066cc);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.widget-body {
    padding: 1rem;
    height: 100%;
}

.widget-resize-handle {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 16px;
    height: 16px;
    cursor: se-resize;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.widget:hover .widget-resize-handle {
    opacity: 0.5;
}

.widget-resize-handle::before {
    content: '';
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 8px;
    height: 8px;
    border-right: 2px solid var(--text-tertiary, #999);
    border-bottom: 2px solid var(--text-tertiary, #999);
}

/* Welcome Widget */
.widget-welcome {
    height: 100%;
}

.welcome-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
}

.welcome-greeting {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary, #333);
    margin: 0 0 0.25rem 0;
}

.welcome-date {
    font-size: 0.875rem;
    color: var(--text-secondary, #666);
    margin: 0;
}

.welcome-stats {
    display: flex;
    gap: 2rem;
}

.welcome-stat {
    text-align: center;
}

.welcome-stat .stat-value {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--accent-color, #0066cc);
}

.welcome-stat .stat-label {
    font-size: 0.75rem;
    color: var(--text-tertiary, #999);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Quick Actions Widget */
.widget-quick-actions {
    height: 100%;
}

.quick-action-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    height: 100%;
}

.quick-action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    border-radius: 8px;
    background: var(--background-secondary, #f5f5f5);
    text-decoration: none;
    color: var(--text-primary, #333);
    transition: all 0.15s ease;
}

.quick-action-item:hover {
    background: var(--accent-color-light, #e6f0ff);
    color: var(--accent-color, #0066cc);
}

.quick-action-item svg {
    width: 24px;
    height: 24px;
}

.quick-action-item span {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Stats Widget */
.widget-stats {
    height: 100%;
}

.stats-items {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.stats-item {
    flex: 1;
    min-width: 120px;
    padding: 1rem;
    background: var(--background-secondary, #f5f5f5);
    border-radius: 8px;
}

.stats-item .stat-label {
    font-size: 0.75rem;
    color: var(--text-tertiary, #999);
    margin-bottom: 0.25rem;
}

.stats-item .stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary, #333);
}

.stats-item .stat-status {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.stats-item .stat-status.success {
    background: var(--success-light, #e6f4ea);
    color: var(--success-color, #28a745);
}

.stats-item .stat-status.warning {
    background: var(--warning-light, #fff3cd);
    color: var(--warning-color, #ffc107);
}

.stats-item .stat-status.danger {
    background: var(--danger-light, #fee);
    color: var(--danger-color, #dc3545);
}

/* List Widget */
.widget-list-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: var(--background-secondary, #f5f5f5);
    border-radius: 6px;
}

.list-item-title {
    font-size: 0.875rem;
    color: var(--text-primary, #333);
}

.list-item-time {
    font-size: 0.75rem;
    color: var(--text-tertiary, #999);
}

/* Chart Widget */
.widget-chart {
    height: 100%;
    position: relative;
}

.chart-canvas {
    width: 100% !important;
    height: 100% !important;
}

/* Table Widget */
.widget-table {
    height: 100%;
    overflow: auto;
}

.mini-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.mini-table th,
.mini-table td {
    padding: 0.5rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color, #e5e5e5);
}

.mini-table th {
    font-weight: 600;
    color: var(--text-secondary, #666);
    background: var(--background-secondary, #f5f5f5);
}

/* Placeholder Styles */
.stats-placeholder,
.list-placeholder,
.chart-placeholder,
.table-placeholder td,
.custom-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-tertiary, #999);
    font-size: 0.875rem;
}

/* Empty State */
.dashboard-empty {
    text-align: center;
    padding: 4rem 2rem;
}

.dashboard-empty-icon {
    margin-bottom: 1rem;
    color: var(--text-tertiary, #999);
}

.dashboard-empty-icon svg {
    width: 64px;
    height: 64px;
}

.dashboard-empty h3 {
    font-size: 1.25rem;
    color: var(--text-primary, #333);
    margin: 0 0 0.5rem 0;
}

.dashboard-empty p {
    color: var(--text-secondary, #666);
    margin: 0 0 1.5rem 0;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--accent-color, #0066cc);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s ease;
}

.btn-primary:hover {
    background: var(--accent-color-dark, #0052a3);
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-container {
    background: var(--background-primary, #ffffff);
    border-radius: 12px;
    width: 100%;
    max-width: 480px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color, #e5e5e5);
}

.modal-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary, #333);
    margin: 0;
}

.modal-close {
    padding: 0.375rem;
    border: none;
    background: transparent;
    color: var(--text-tertiary, #999);
    cursor: pointer;
    border-radius: 4px;
    display: flex;
    transition: all 0.15s ease;
}

.modal-close:hover {
    background: var(--background-hover, #f5f5f5);
    color: var(--text-primary, #333);
}

.modal-close svg {
    width: 18px;
    height: 18px;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.modal-empty {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary, #666);
}

/* Widget List in Modal */
.widget-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.widget-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--background-secondary, #f5f5f5);
    border-radius: 8px;
    transition: background 0.15s ease;
}

.widget-option:hover {
    background: var(--background-hover, #e5e5e5);
}

.widget-option-icon {
    width: 40px;
    height: 40px;
    background: var(--accent-color-light, #e6f0ff);
    color: var(--accent-color, #0066cc);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.widget-option-icon svg {
    width: 20px;
    height: 20px;
}

.widget-option-info {
    flex: 1;
    min-width: 0;
}

.widget-option-info h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary, #333);
    margin: 0;
}

.widget-option-info p {
    font-size: 0.75rem;
    color: var(--text-secondary, #666);
    margin: 0.25rem 0 0 0;
}

.widget-option-plugin {
    display: inline-block;
    font-size: 0.625rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--accent-color, #0066cc);
    background: var(--accent-color-light, #e6f0ff);
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    margin-top: 0.25rem;
}

.btn-add-this-widget {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: var(--accent-color, #0066cc);
    color: white;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s ease;
    flex-shrink: 0;
}

.btn-add-this-widget:hover {
    background: var(--accent-color-dark, #0052a3);
}

.btn-add-this-widget svg {
    width: 16px;
    height: 16px;
}

/* Responsive */
@media (max-width: 1200px) {
    .widget-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .widget-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .widget-grid {
        grid-template-columns: 1fr;
    }
    
    .welcome-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>
