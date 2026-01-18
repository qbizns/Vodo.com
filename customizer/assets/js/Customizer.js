/**
 * TailwindPlus Customizer - Main Application
 * ===========================================
 * Main application class that orchestrates all components
 * 
 * @module Customizer
 * @version 1.1.0
 */

import { EventBus, eventBus, Events } from './core/EventBus.js';
import { ComponentRegistry, componentRegistry } from './core/ComponentRegistry.js';
import { PageStateManager, pageStateManager } from './core/PageStateManager.js';
import { fieldRegistry } from './core/FieldRegistry.js';

import { Toolbar } from './ui/Toolbar.js';
import { Panel } from './ui/Panel.js';
import { Layers } from './ui/Layers.js';
import { Preview } from './ui/Preview.js';
import { registerBuiltInFields } from './ui/fields/index.js';

import { config, panelsConfig, defaultCategories } from './config/config.js';
import { inlineSprite } from './utils/icons.js';
import { getStorage, setStorage } from './utils/helpers.js';

export class Customizer {
    /**
     * @param {Object} options - Customizer options
     * @param {string} options.container - Container selector
     * @param {string} options.language - Language code
     * @param {Array} options.components - Initial components
     * @param {Array} options.categories - Custom categories
     * @param {Object} options.initialState - Initial page state
     */
    constructor(options = {}) {
        this.options = {
            container: '#app',
            language: config.defaultLanguage,
            direction: config.defaultDirection,
            ...options,
        };

        this.container = document.querySelector(this.options.container);
        if (!this.container) {
            throw new Error(`Container "${this.options.container}" not found`);
        }

        // Core instances
        this.eventBus = eventBus;
        this.registry = componentRegistry;
        this.pageState = pageStateManager;

        // UI instances
        this.toolbar = null;
        this.panel = null;
        this.layers = null;
        this.preview = null;

        // State
        this.language = this.options.language;
        this.isCollapsed = false;

        // Initialize
        this._init();
    }

    /**
     * Initialize application
     * @private
     */
    async _init() {
        try {
            // Load icon sprite
            await inlineSprite();

            // Register built-in field types
            registerBuiltInFields();

            // Register default categories
            this.registry.registerCategories(defaultCategories);

            // Register initial components
            if (this.options.components) {
                this.registry.registerComponents(this.options.components);
            }

            // Register custom categories
            if (this.options.categories) {
                this.registry.registerCategories(this.options.categories);
            }

            // Restore saved state
            this._restoreState();

            // Render UI
            this._render();

            // Initialize UI components
            this._initComponents();

            // Bind global events
            this._bindGlobalEvents();

            // Set initial panel
            this.toolbar.setActivePanel('homepage');

            // Emit ready event
            this.eventBus.emit('customizer:ready', { customizer: this });

            console.log(`🚀 ${config.name} v${config.version} initialized`);
            console.log(`📦 Components: ${this.registry.getComponentCount()}`);
            console.log(`📁 Categories: ${this.registry.listCategories().length}`);

        } catch (error) {
            console.error('Failed to initialize customizer:', error);
            throw error;
        }
    }

    /**
     * Render main layout
     * @private
     */
    _render() {
        this.container.innerHTML = `
            <div class="app ${this.isCollapsed ? 'is-sidebar-collapsed' : ''}" dir="${this.options.direction}">
                <!-- Preview Area -->
                <div id="preview-area" class="preview"></div>
                
                <!-- Panel -->
                <div id="panel-area" class="panel"></div>
                
                <!-- Toolbar -->
                <div id="toolbar-area" class="toolbar"></div>
            </div>
        `;
    }

    /**
     * Initialize UI components
     * @private
     */
    _initComponents() {
        // Toolbar
        this.toolbar = new Toolbar({
            container: this.container.querySelector('#toolbar-area'),
            panels: panelsConfig,
            language: this.language,
        });

        // Panel (handles component library as sliding panel)
        this.panel = new Panel({
            container: this.container.querySelector('#panel-area'),
            panels: panelsConfig,
            language: this.language,
            onAddElement: () => this.panel.openComponentLibrary(),
            pageState: this.pageState,
            componentRegistry: this.registry,
        });

        // Preview
        this.preview = new Preview({
            container: this.container.querySelector('#preview-area'),
            pageState: this.pageState,
            language: this.language,
        });

        // Layers (after panel is rendered)
        const layersContainer = this.panel.getLayersContainer();
        if (layersContainer) {
            this.layers = new Layers({
                container: layersContainer,
                pageState: this.pageState,
                language: this.language,
            });
        }
    }

    /**
     * Bind global event listeners
     * @private
     */
    _bindGlobalEvents() {
        // Sidebar toggle
        this.eventBus.on(Events.SIDEBAR_TOGGLED, ({ collapsed }) => {
            this.isCollapsed = collapsed;
            const app = this.container.querySelector('.app');
            if (app) {
                app.classList.toggle('is-sidebar-collapsed', collapsed);
            }
        });

        // Auto-save on changes
        this.eventBus.on(Events.PAGE_CHANGED, () => {
            this._saveState();
        });

        // Open component library from preview toolbar
        this.eventBus.on('open-component-library', (data) => {
            this.openComponentLibrary(data?.insertAfter);
        });

        // Note: edit-block event is now handled by Panel.js

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Undo: Ctrl/Cmd + Z
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                this.undo();
            }
            // Redo: Ctrl/Cmd + Shift + Z or Ctrl/Cmd + Y
            if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                e.preventDefault();
                this.redo();
            }
            // Delete: Delete or Backspace when block is selected
            if ((e.key === 'Delete' || e.key === 'Backspace') && !e.target.closest('input, textarea')) {
                const selectedId = this.pageState.getSelectedBlockId();
                if (selectedId) {
                    e.preventDefault();
                    this.removeBlock(selectedId);
                }
            }
            // Escape: Deselect block
            if (e.key === 'Escape') {
                this.pageState.deselectBlock();
            }
        });
    }

    /**
     * Save state to localStorage
     * @private
     */
    _saveState() {
        const state = this.pageState.exportJSON();
        setStorage('customizer_state', state);
    }

    /**
     * Restore state from localStorage
     * @private
     */
    _restoreState() {
        if (this.options.initialState) {
            this.pageState.importJSON(this.options.initialState, this.registry);
            return;
        }

        const savedState = getStorage('customizer_state');
        if (savedState) {
            this.pageState.importJSON(savedState, this.registry);
        }
    }

    // ============================================
    // PUBLIC API
    // ============================================

    /**
     * Open component library panel
     * @param {string} insertAfter - Optional block ID to insert after
     */
    openComponentLibrary(insertAfter = null) {
        this.panel.openComponentLibrary(insertAfter);
    }

    /**
     * Close component library panel
     */
    closeComponentLibrary() {
        // Panel handles this through navigation
        if (this.panel.navigator?.canGoBack()) {
            this.panel.navigator.pop();
        }
    }

    /**
     * Add a component to the page
     * @param {Object} component - Component to add
     * @param {number} position - Position to insert at
     * @returns {Object} Created block
     */
    addComponent(component, position = null) {
        return this.pageState.addBlock(component, position);
    }

    /**
     * Remove a block from the page
     * @param {string} blockId - Block ID
     * @returns {boolean} Success
     */
    removeBlock(blockId) {
        return this.pageState.removeBlock(blockId);
    }

    /**
     * Undo last action
     * @returns {boolean} Success
     */
    undo() {
        return this.pageState.undo();
    }

    /**
     * Redo last undone action
     * @returns {boolean} Success
     */
    redo() {
        return this.pageState.redo();
    }

    /**
     * Register a component
     * @param {Object} component - Component configuration
     * @returns {boolean} Success
     */
    registerComponent(component) {
        return this.registry.registerComponent(component);
    }

    /**
     * Register multiple components
     * @param {Array} components - Component configurations
     * @returns {number} Number registered
     */
    registerComponents(components) {
        return this.registry.registerComponents(components);
    }

    /**
     * Register a plugin
     * @param {Object} plugin - Plugin configuration
     * @returns {boolean} Success
     */
    registerPlugin(plugin) {
        return this.registry.registerPlugin(plugin);
    }

    /**
     * Unregister a plugin
     * @param {string} pluginId - Plugin ID
     * @returns {boolean} Success
     */
    unregisterPlugin(pluginId) {
        return this.registry.unregisterPlugin(pluginId);
    }

    /**
     * Export page as HTML
     * @returns {string} HTML string
     */
    exportHTML() {
        return this.pageState.exportHTML();
    }

    /**
     * Export page as JSON
     * @returns {Object} JSON object
     */
    exportJSON() {
        return this.pageState.exportJSON();
    }

    /**
     * Import page from JSON
     * @param {Object} data - JSON data
     */
    importJSON(data) {
        this.pageState.importJSON(data, this.registry);
    }

    /**
     * Clear all blocks
     */
    clear() {
        this.pageState.clear();
    }

    /**
     * Set language
     * @param {string} language - Language code
     */
    setLanguage(language) {
        this.language = language;
        // Re-render would be needed for full language switch
    }

    /**
     * Get current state
     * @returns {Object} Current state
     */
    getState() {
        return this.pageState.getState();
    }

    /**
     * Subscribe to events
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @returns {Function} Unsubscribe function
     */
    on(event, callback) {
        return this.eventBus.on(event, callback);
    }

    /**
     * Emit event
     * @param {string} event - Event name
     * @param {*} data - Event data
     */
    emit(event, data) {
        this.eventBus.emit(event, data);
    }

    /**
     * Destroy customizer
     */
    destroy() {
        this.toolbar?.destroy();
        this.panel?.destroy();
        this.layers?.destroy();
        this.preview?.destroy();
        this.container.innerHTML = '';
        this.eventBus.removeAllListeners();
    }
}

// Export for global access
export default Customizer;

// Make available on window for non-module usage
if (typeof window !== 'undefined') {
    window.Customizer = Customizer;
    window.Events = Events;
}
