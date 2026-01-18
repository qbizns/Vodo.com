/**
 * TailwindPlus Customizer - Plugin Base
 * ======================================
 * Base class for creating plugins
 * 
 * @module plugins/Plugin
 * @version 1.0.0
 * 
 * @example
 * ```js
 * import { Plugin } from './plugins/Plugin.js';
 * 
 * class MyPlugin extends Plugin {
 *     static id = 'my-plugin';
 *     static name = { ar: 'إضافتي', en: 'My Plugin' };
 *     static version = '1.0.0';
 *     
 *     getComponents() {
 *         return [
 *             { id: 'my-component', ... }
 *         ];
 *     }
 * }
 * 
 * // Register
 * customizer.registerPlugin(new MyPlugin());
 * ```
 */

export class Plugin {
    // Static properties to override
    static id = 'base-plugin';
    static name = { ar: 'إضافة', en: 'Plugin' };
    static version = '1.0.0';
    static author = 'Unknown';
    static description = { ar: '', en: '' };

    constructor() {
        this._customizer = null;
    }

    /**
     * Get plugin configuration for registration
     * @returns {Object} Plugin config
     */
    toConfig() {
        return {
            id: this.constructor.id,
            name: this.constructor.name,
            version: this.constructor.version,
            author: this.constructor.author,
            description: this.constructor.description,
            categories: this.getCategories(),
            components: this.getComponents(),
            onRegister: (registry) => this.onRegister(registry),
            onUnregister: (registry) => this.onUnregister(registry),
        };
    }

    /**
     * Get plugin categories
     * Override in subclass to provide custom categories
     * @returns {Array} Category configurations
     */
    getCategories() {
        return [];
    }

    /**
     * Get plugin components
     * Override in subclass to provide components
     * @returns {Array} Component configurations
     */
    getComponents() {
        return [];
    }

    /**
     * Called when plugin is registered
     * Override for custom initialization
     * @param {Object} registry - Component registry
     */
    onRegister(registry) {
        // Override in subclass
    }

    /**
     * Called when plugin is unregistered
     * Override for cleanup
     * @param {Object} registry - Component registry
     */
    onUnregister(registry) {
        // Override in subclass
    }

    /**
     * Subscribe to customizer events
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     */
    on(event, callback) {
        if (this._customizer) {
            return this._customizer.on(event, callback);
        }
    }

    /**
     * Emit customizer event
     * @param {string} event - Event name
     * @param {*} data - Event data
     */
    emit(event, data) {
        if (this._customizer) {
            this._customizer.emit(event, data);
        }
    }
}

/**
 * Create a simple plugin from config object
 * @param {Object} config - Plugin configuration
 * @returns {Object} Plugin config for registration
 */
export function createPlugin(config) {
    return {
        id: config.id,
        name: config.name || config.id,
        version: config.version || '1.0.0',
        author: config.author || 'Unknown',
        categories: config.categories || [],
        components: config.components || [],
        onRegister: config.onRegister,
        onUnregister: config.onUnregister,
    };
}

export default Plugin;
