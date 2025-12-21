/**
 * VODO Platform - Event Bus
 *
 * Provides a publish/subscribe event system for communication
 * between components and plugins.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.events.js');
        return;
    }

    // ============================================
    // Event Bus
    // ============================================

    const events = {
        _listeners: new Map(),
        _onceListeners: new Map(),

        /**
         * Configuration
         */
        config: {
            debug: false,
            maxListeners: 100,
            warnOnMaxListeners: true
        },

        /**
         * Subscribe to an event
         * @param {string} event - Event name (supports wildcards with *)
         * @param {Function} callback - Callback function
         * @param {Object} context - Context to bind callback to
         * @returns {Function} Unsubscribe function
         */
        on: function(event, callback, context = null) {
            if (typeof callback !== 'function') {
                Vodo.error('Event callback must be a function');
                return () => {};
            }

            if (!this._listeners.has(event)) {
                this._listeners.set(event, []);
            }

            const listeners = this._listeners.get(event);

            // Warn if too many listeners
            if (this.config.warnOnMaxListeners && listeners.length >= this.config.maxListeners) {
                Vodo.warn(`Event "${event}" has ${listeners.length} listeners. Possible memory leak.`);
            }

            const handler = { callback, context };
            listeners.push(handler);

            if (this.config.debug) {
                Vodo.log(`Event subscribed: ${event}`, { listeners: listeners.length });
            }

            // Return unsubscribe function
            return () => this.off(event, callback);
        },

        /**
         * Subscribe to an event once
         * @param {string} event - Event name
         * @param {Function} callback - Callback function
         * @param {Object} context - Context to bind callback to
         * @returns {Function} Unsubscribe function
         */
        once: function(event, callback, context = null) {
            if (!this._onceListeners.has(event)) {
                this._onceListeners.set(event, []);
            }

            this._onceListeners.get(event).push({ callback, context });

            return () => {
                const listeners = this._onceListeners.get(event);
                if (listeners) {
                    const index = listeners.findIndex(l => l.callback === callback);
                    if (index > -1) {
                        listeners.splice(index, 1);
                    }
                }
            };
        },

        /**
         * Unsubscribe from an event
         * @param {string} event - Event name
         * @param {Function} callback - Callback function (optional, removes all if not provided)
         */
        off: function(event, callback = null) {
            if (callback === null) {
                // Remove all listeners for this event
                this._listeners.delete(event);
                this._onceListeners.delete(event);
                return;
            }

            // Remove specific listener
            const listeners = this._listeners.get(event);
            if (listeners) {
                const index = listeners.findIndex(l => l.callback === callback);
                if (index > -1) {
                    listeners.splice(index, 1);
                }
            }

            const onceListeners = this._onceListeners.get(event);
            if (onceListeners) {
                const index = onceListeners.findIndex(l => l.callback === callback);
                if (index > -1) {
                    onceListeners.splice(index, 1);
                }
            }
        },

        /**
         * Emit an event
         * @param {string} event - Event name
         * @param {...*} args - Arguments to pass to listeners
         */
        emit: function(event, ...args) {
            if (this.config.debug) {
                Vodo.log(`Event emitted: ${event}`, args);
            }

            // Regular listeners
            const listeners = this._listeners.get(event);
            if (listeners) {
                listeners.forEach(({ callback, context }) => {
                    try {
                        callback.apply(context, args);
                    } catch (error) {
                        Vodo.error(`Error in event listener for "${event}":`, error);
                    }
                });
            }

            // Once listeners
            const onceListeners = this._onceListeners.get(event);
            if (onceListeners && onceListeners.length > 0) {
                // Copy array since we'll be removing items
                const toCall = [...onceListeners];
                this._onceListeners.set(event, []);

                toCall.forEach(({ callback, context }) => {
                    try {
                        callback.apply(context, args);
                    } catch (error) {
                        Vodo.error(`Error in once event listener for "${event}":`, error);
                    }
                });
            }

            // Wildcard listeners (e.g., "router:*" matches "router:before")
            this._listeners.forEach((wildcardListeners, pattern) => {
                if (pattern.endsWith('*') && event.startsWith(pattern.slice(0, -1))) {
                    wildcardListeners.forEach(({ callback, context }) => {
                        try {
                            callback.apply(context, [event, ...args]);
                        } catch (error) {
                            Vodo.error(`Error in wildcard listener for "${pattern}":`, error);
                        }
                    });
                }
            });
        },

        /**
         * Emit an event and wait for all async handlers
         * @param {string} event - Event name
         * @param {...*} args - Arguments to pass to listeners
         * @returns {Promise<Array>} Results from all handlers
         */
        emitAsync: async function(event, ...args) {
            if (this.config.debug) {
                Vodo.log(`Async event emitted: ${event}`, args);
            }

            const promises = [];

            // Regular listeners
            const listeners = this._listeners.get(event);
            if (listeners) {
                listeners.forEach(({ callback, context }) => {
                    try {
                        const result = callback.apply(context, args);
                        if (result instanceof Promise) {
                            promises.push(result);
                        } else {
                            promises.push(Promise.resolve(result));
                        }
                    } catch (error) {
                        promises.push(Promise.reject(error));
                    }
                });
            }

            // Once listeners
            const onceListeners = this._onceListeners.get(event);
            if (onceListeners && onceListeners.length > 0) {
                const toCall = [...onceListeners];
                this._onceListeners.set(event, []);

                toCall.forEach(({ callback, context }) => {
                    try {
                        const result = callback.apply(context, args);
                        if (result instanceof Promise) {
                            promises.push(result);
                        } else {
                            promises.push(Promise.resolve(result));
                        }
                    } catch (error) {
                        promises.push(Promise.reject(error));
                    }
                });
            }

            return Promise.allSettled(promises);
        },

        /**
         * Apply filter (WordPress-like)
         * Passes value through all handlers, each can modify it
         * @param {string} event - Filter event name
         * @param {*} value - Initial value
         * @param {...*} args - Additional arguments
         * @returns {*} Filtered value
         */
        filter: function(event, value, ...args) {
            if (this.config.debug) {
                Vodo.log(`Filter applied: ${event}`, { initial: value });
            }

            let result = value;

            const listeners = this._listeners.get(event);
            if (listeners) {
                listeners.forEach(({ callback, context }) => {
                    try {
                        const filtered = callback.apply(context, [result, ...args]);
                        if (filtered !== undefined) {
                            result = filtered;
                        }
                    } catch (error) {
                        Vodo.error(`Error in filter "${event}":`, error);
                    }
                });
            }

            if (this.config.debug) {
                Vodo.log(`Filter result: ${event}`, { result });
            }

            return result;
        },

        /**
         * Check if event has listeners
         * @param {string} event - Event name
         * @returns {boolean}
         */
        hasListeners: function(event) {
            const regular = this._listeners.get(event);
            const once = this._onceListeners.get(event);
            return (regular && regular.length > 0) || (once && once.length > 0);
        },

        /**
         * Get listener count for an event
         * @param {string} event - Event name
         * @returns {number}
         */
        listenerCount: function(event) {
            const regular = this._listeners.get(event) || [];
            const once = this._onceListeners.get(event) || [];
            return regular.length + once.length;
        },

        /**
         * Get all registered events
         * @returns {Array<string>}
         */
        eventNames: function() {
            const names = new Set([
                ...this._listeners.keys(),
                ...this._onceListeners.keys()
            ]);
            return [...names];
        },

        /**
         * Remove all listeners
         */
        clear: function() {
            this._listeners.clear();
            this._onceListeners.clear();
            Vodo.log('All event listeners cleared');
        },

        /**
         * Debug helper - list all listeners
         * @returns {Object}
         */
        debug: function() {
            const info = {};
            this._listeners.forEach((listeners, event) => {
                info[event] = {
                    count: listeners.length,
                    type: 'regular'
                };
            });
            this._onceListeners.forEach((listeners, event) => {
                if (info[event]) {
                    info[event].onceCount = listeners.length;
                } else {
                    info[event] = {
                        count: 0,
                        onceCount: listeners.length,
                        type: 'once'
                    };
                }
            });
            console.table(info);
            return info;
        }
    };

    // ============================================
    // Convenience Methods
    // ============================================

    /**
     * Create a namespaced event emitter for a plugin
     * @param {string} namespace - Plugin namespace
     * @returns {Object} Namespaced event emitter
     */
    events.namespace = function(namespace) {
        return {
            on: (event, callback, context) => events.on(`${namespace}:${event}`, callback, context),
            once: (event, callback, context) => events.once(`${namespace}:${event}`, callback, context),
            off: (event, callback) => events.off(`${namespace}:${event}`, callback),
            emit: (event, ...args) => events.emit(`${namespace}:${event}`, ...args),
            emitAsync: (event, ...args) => events.emitAsync(`${namespace}:${event}`, ...args),
            filter: (event, value, ...args) => events.filter(`${namespace}:${event}`, value, ...args)
        };
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('events', events);

})(typeof window !== 'undefined' ? window : this);
