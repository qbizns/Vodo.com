/**
 * TailwindPlus Customizer - Event Bus
 * ====================================
 * Pub/Sub event system for decoupled communication
 * 
 * @module core/EventBus
 * @version 1.0.0
 */

export class EventBus {
    constructor() {
        this._events = new Map();
        this._onceEvents = new Map();
    }

    /**
     * Subscribe to an event
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @returns {Function} Unsubscribe function
     */
    on(event, callback) {
        if (!this._events.has(event)) {
            this._events.set(event, new Set());
        }
        this._events.get(event).add(callback);

        // Return unsubscribe function
        return () => this.off(event, callback);
    }

    /**
     * Subscribe to an event once
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @returns {Function} Unsubscribe function
     */
    once(event, callback) {
        const wrapper = (...args) => {
            this.off(event, wrapper);
            callback.apply(this, args);
        };
        return this.on(event, wrapper);
    }

    /**
     * Unsubscribe from an event
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     */
    off(event, callback) {
        if (this._events.has(event)) {
            this._events.get(event).delete(callback);
        }
    }

    /**
     * Emit an event
     * @param {string} event - Event name
     * @param {*} data - Event data
     */
    emit(event, data) {
        if (this._events.has(event)) {
            this._events.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event handler for "${event}":`, error);
                }
            });
        }
    }

    /**
     * Remove all listeners for an event
     * @param {string} event - Event name (optional, removes all if not provided)
     */
    removeAllListeners(event) {
        if (event) {
            this._events.delete(event);
        } else {
            this._events.clear();
        }
    }

    /**
     * Get listener count for an event
     * @param {string} event - Event name
     * @returns {number} Number of listeners
     */
    listenerCount(event) {
        return this._events.has(event) ? this._events.get(event).size : 0;
    }

    /**
     * Get all event names
     * @returns {string[]} Array of event names
     */
    eventNames() {
        return Array.from(this._events.keys());
    }
}

// Event names constants
export const Events = {
    // Plugin events
    PLUGIN_REGISTERED: 'plugin:registered',
    PLUGIN_UNREGISTERED: 'plugin:unregistered',

    // Component events
    COMPONENT_REGISTERED: 'component:registered',
    COMPONENT_UNREGISTERED: 'component:unregistered',

    // Block events
    BLOCK_ADDED: 'block:added',
    BLOCK_REMOVED: 'block:removed',
    BLOCK_MOVED: 'block:moved',
    BLOCK_UPDATED: 'block:updated',
    BLOCK_SELECTED: 'block:selected',
    BLOCK_DESELECTED: 'block:deselected',

    // Page events
    PAGE_CHANGED: 'page:changed',
    PAGE_SAVED: 'page:saved',
    PAGE_EXPORTED: 'page:exported',

    // UI events
    PANEL_SWITCHED: 'panel:switched',
    SIDEBAR_TOGGLED: 'sidebar:toggled',
    MODAL_OPENED: 'modal:opened',
    MODAL_CLOSED: 'modal:closed',
    DEVICE_CHANGED: 'device:changed',

    // History events
    HISTORY_UNDO: 'history:undo',
    HISTORY_REDO: 'history:redo',
};

// Create singleton instance
export const eventBus = new EventBus();

export default EventBus;
