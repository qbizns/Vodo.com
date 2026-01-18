/**
 * TailwindPlus Customizer - Component Registry
 * =============================================
 * Central registry for all components and categories
 * 
 * @module core/ComponentRegistry
 * @version 1.0.0
 */

import { eventBus, Events } from './EventBus.js';

/**
 * Component schema validation
 * @param {Object} component - Component to validate
 * @returns {boolean} Is valid
 */
function validateComponent(component) {
    const required = ['id', 'category', 'name', 'html'];
    return required.every(field => {
        if (!component[field]) {
            console.warn(`Component missing required field: ${field}`);
            return false;
        }
        return true;
    });
}

/**
 * Generate unique ID
 * @returns {string} Unique ID
 */
function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

export class ComponentRegistry {
    constructor() {
        this._components = new Map();
        this._categories = new Map();
        this._plugins = new Map();
    }

    // ============================================
    // COMPONENT METHODS
    // ============================================

    /**
     * Register a component
     * @param {Object} component - Component configuration
     * @returns {boolean} Success
     */
    registerComponent(component) {
        if (!validateComponent(component)) {
            return false;
        }

        const normalized = {
            id: component.id,
            category: component.category,
            name: typeof component.name === 'string' 
                ? { ar: component.name, en: component.name } 
                : component.name,
            description: typeof component.description === 'string'
                ? { ar: component.description, en: component.description }
                : component.description || { ar: '', en: '' },
            thumbnail: component.thumbnail || '',
            html: component.html,
            source: component.source || 'custom',
            tags: component.tags || [],
            fields: component.fields || [],
            constraints: component.constraints || {},
            registeredAt: Date.now(),
        };

        this._components.set(component.id, normalized);
        eventBus.emit(Events.COMPONENT_REGISTERED, { component: normalized });

        return true;
    }

    /**
     * Register multiple components
     * @param {Object[]} components - Array of components
     * @returns {number} Number of successfully registered components
     */
    registerComponents(components) {
        return components.filter(c => this.registerComponent(c)).length;
    }

    /**
     * Unregister a component
     * @param {string} componentId - Component ID
     * @returns {boolean} Success
     */
    unregisterComponent(componentId) {
        if (this._components.has(componentId)) {
            const component = this._components.get(componentId);
            this._components.delete(componentId);
            eventBus.emit(Events.COMPONENT_UNREGISTERED, { componentId, component });
            return true;
        }
        return false;
    }

    /**
     * Get a component by ID
     * @param {string} componentId - Component ID
     * @returns {Object|null} Component or null
     */
    getComponent(componentId) {
        return this._components.get(componentId) || null;
    }

    /**
     * List components with optional filters
     * @param {Object} filters - Filter options
     * @returns {Object[]} Filtered components
     */
    listComponents(filters = {}) {
        let result = Array.from(this._components.values());

        // Filter by category
        if (filters.category && filters.category !== 'all') {
            result = result.filter(c => c.category === filters.category);
        }

        // Filter by source (plugin)
        if (filters.source) {
            result = result.filter(c => c.source === filters.source);
        }

        // Filter by search query
        if (filters.search) {
            const query = filters.search.toLowerCase();
            result = result.filter(c =>
                c.name.ar.toLowerCase().includes(query) ||
                c.name.en.toLowerCase().includes(query) ||
                c.description.ar.toLowerCase().includes(query) ||
                c.description.en.toLowerCase().includes(query) ||
                c.tags.some(tag => tag.toLowerCase().includes(query))
            );
        }

        // Filter by tags
        if (filters.tags && filters.tags.length > 0) {
            result = result.filter(c =>
                filters.tags.some(tag => c.tags.includes(tag))
            );
        }

        // Sort
        if (filters.sortBy) {
            result.sort((a, b) => {
                switch (filters.sortBy) {
                    case 'name':
                        return a.name.ar.localeCompare(b.name.ar);
                    case 'recent':
                        return b.registeredAt - a.registeredAt;
                    default:
                        return 0;
                }
            });
        }

        return result;
    }

    /**
     * Get component count
     * @param {string} category - Optional category filter
     * @returns {number} Component count
     */
    getComponentCount(category) {
        if (!category || category === 'all') {
            return this._components.size;
        }
        return this.listComponents({ category }).length;
    }

    // ============================================
    // CATEGORY METHODS
    // ============================================

    /**
     * Register a category
     * @param {Object} category - Category configuration
     */
    registerCategory(category) {
        this._categories.set(category.id, {
            id: category.id,
            name: typeof category.name === 'string'
                ? { ar: category.name, en: category.name }
                : category.name,
            icon: category.icon || 'grid',
            order: category.order || 999,
        });
    }

    /**
     * Register multiple categories
     * @param {Object[]} categories - Array of categories
     */
    registerCategories(categories) {
        categories.forEach(c => this.registerCategory(c));
    }

    /**
     * Unregister a category
     * @param {string} categoryId - Category ID
     */
    unregisterCategory(categoryId) {
        this._categories.delete(categoryId);
    }

    /**
     * Get a category by ID
     * @param {string} categoryId - Category ID
     * @returns {Object|null} Category or null
     */
    getCategory(categoryId) {
        return this._categories.get(categoryId) || null;
    }

    /**
     * List all categories
     * @returns {Object[]} Categories sorted by order
     */
    listCategories() {
        return Array.from(this._categories.values())
            .sort((a, b) => a.order - b.order);
    }

    // ============================================
    // PLUGIN METHODS
    // ============================================

    /**
     * Register a plugin
     * @param {Object} plugin - Plugin configuration
     * @returns {boolean} Success
     */
    registerPlugin(plugin) {
        if (!plugin.id) {
            console.warn('Plugin must have an ID');
            return false;
        }

        // Store plugin info
        this._plugins.set(plugin.id, {
            id: plugin.id,
            name: plugin.name || plugin.id,
            version: plugin.version || '1.0.0',
            author: plugin.author || 'Unknown',
            registeredAt: Date.now(),
        });

        // Register plugin categories
        if (plugin.categories) {
            plugin.categories.forEach(cat => {
                cat.source = plugin.id;
                this.registerCategory(cat);
            });
        }

        // Register plugin components
        if (plugin.components) {
            plugin.components.forEach(comp => {
                comp.source = plugin.id;
                this.registerComponent(comp);
            });
        }

        // Call plugin lifecycle hook
        if (typeof plugin.onRegister === 'function') {
            plugin.onRegister(this);
        }

        eventBus.emit(Events.PLUGIN_REGISTERED, { plugin });
        return true;
    }

    /**
     * Unregister a plugin
     * @param {string} pluginId - Plugin ID
     * @returns {boolean} Success
     */
    unregisterPlugin(pluginId) {
        const plugin = this._plugins.get(pluginId);
        if (!plugin) return false;

        // Remove plugin components
        this._components.forEach((comp, id) => {
            if (comp.source === pluginId) {
                this._components.delete(id);
            }
        });

        // Remove plugin categories
        this._categories.forEach((cat, id) => {
            if (cat.source === pluginId) {
                this._categories.delete(id);
            }
        });

        // Call plugin lifecycle hook
        if (typeof plugin.onUnregister === 'function') {
            plugin.onUnregister(this);
        }

        this._plugins.delete(pluginId);
        eventBus.emit(Events.PLUGIN_UNREGISTERED, { pluginId });
        return true;
    }

    /**
     * List all plugins
     * @returns {Object[]} Plugins
     */
    listPlugins() {
        return Array.from(this._plugins.values());
    }

    /**
     * Check if plugin is registered
     * @param {string} pluginId - Plugin ID
     * @returns {boolean} Is registered
     */
    hasPlugin(pluginId) {
        return this._plugins.has(pluginId);
    }

    // ============================================
    // UTILITY METHODS
    // ============================================

    /**
     * Clear all data
     */
    clear() {
        this._components.clear();
        this._categories.clear();
        this._plugins.clear();
    }

    /**
     * Export registry data
     * @returns {Object} Registry data
     */
    export() {
        return {
            components: Array.from(this._components.values()),
            categories: Array.from(this._categories.values()),
            plugins: Array.from(this._plugins.values()),
        };
    }

    /**
     * Import registry data
     * @param {Object} data - Registry data
     */
    import(data) {
        if (data.categories) {
            this.registerCategories(data.categories);
        }
        if (data.components) {
            this.registerComponents(data.components);
        }
    }
}

// Create singleton instance
export const componentRegistry = new ComponentRegistry();

export default ComponentRegistry;
