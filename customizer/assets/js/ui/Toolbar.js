/**
 * TailwindPlus Customizer - Toolbar UI Component
 * ===============================================
 * Right-side toolbar with panel navigation
 * 
 * @module ui/Toolbar
 * @version 1.0.0
 */

import { eventBus, Events } from '../core/EventBus.js';
import { icon } from '../utils/icons.js';
import { getText } from '../utils/helpers.js';

export class Toolbar {
    /**
     * @param {Object} options - Toolbar options
     * @param {HTMLElement} options.container - Container element
     * @param {Array} options.panels - Panel configurations
     * @param {string} options.language - Current language
     */
    constructor(options) {
        this.container = options.container;
        this.panels = options.panels || [];
        this.language = options.language || 'ar';
        this.activePanel = null;
        this.isCollapsed = false;

        this._bindEvents();
        this.render();
    }

    /**
     * Render toolbar
     */
    render() {
        const html = `
            <button 
                class="toolbar__btn" 
                data-action="toggle-collapse"
                data-tooltip="${getText({ ar: 'طي اللوحة', en: 'Toggle Panel' }, this.language)}"
            >
                ${icon(this.isCollapsed ? 'expand' : 'menu')}
            </button>
            
            ${this.panels.map(panel => `
                <button 
                    class="toolbar__btn ${this.activePanel === panel.id ? 'is-active' : ''}" 
                    data-panel="${panel.id}"
                    data-tooltip="${getText(panel.title, this.language)}"
                >
                    ${icon(panel.icon)}
                </button>
            `).join('')}
        `;

        this.container.innerHTML = html;
    }

    /**
     * Bind event listeners
     * @private
     */
    _bindEvents() {
        // Click handler
        this.container.addEventListener('click', (e) => {
            const btn = e.target.closest('.toolbar__btn');
            if (!btn) return;

            const action = btn.dataset.action;
            const panelId = btn.dataset.panel;

            if (action === 'toggle-collapse') {
                this.toggleCollapse();
            } else if (panelId) {
                this.setActivePanel(panelId);
            }
        });

        // Listen for external panel changes
        eventBus.on(Events.PANEL_SWITCHED, ({ panelId }) => {
            this.activePanel = panelId;
            this._updateActiveState();
        });

        // Listen for sidebar toggle
        eventBus.on(Events.SIDEBAR_TOGGLED, ({ collapsed }) => {
            this.isCollapsed = collapsed;
            this._updateCollapseButton();
        });
    }

    /**
     * Set active panel
     * @param {string} panelId - Panel ID
     */
    setActivePanel(panelId) {
        if (this.activePanel === panelId && !this.isCollapsed) {
            return;
        }

        // If collapsed, expand first
        if (this.isCollapsed) {
            this.toggleCollapse();
        }

        this.activePanel = panelId;
        this._updateActiveState();
        eventBus.emit(Events.PANEL_SWITCHED, { panelId });
    }

    /**
     * Toggle collapse state
     */
    toggleCollapse() {
        this.isCollapsed = !this.isCollapsed;
        this._updateCollapseButton();
        eventBus.emit(Events.SIDEBAR_TOGGLED, { collapsed: this.isCollapsed });
    }

    /**
     * Update active state in UI
     * @private
     */
    _updateActiveState() {
        this.container.querySelectorAll('.toolbar__btn[data-panel]').forEach(btn => {
            btn.classList.toggle('is-active', btn.dataset.panel === this.activePanel);
        });
    }

    /**
     * Update collapse button icon
     * @private
     */
    _updateCollapseButton() {
        const btn = this.container.querySelector('[data-action="toggle-collapse"]');
        if (btn) {
            btn.innerHTML = icon(this.isCollapsed ? 'expand' : 'menu');
        }
    }

    /**
     * Get active panel ID
     * @returns {string|null} Active panel ID
     */
    getActivePanel() {
        return this.activePanel;
    }

    /**
     * Destroy toolbar
     */
    destroy() {
        this.container.innerHTML = '';
    }
}

export default Toolbar;
