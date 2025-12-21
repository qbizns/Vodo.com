/**
 * VODO Platform - Storage Wrapper
 *
 * Provides a unified API for localStorage and sessionStorage
 * with JSON serialization, expiration, and namespacing.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.storage.js');
        return;
    }

    // ============================================
    // Storage Configuration
    // ============================================

    const config = {
        prefix: 'vodo_',
        serialize: JSON.stringify,
        deserialize: JSON.parse
    };

    // ============================================
    // Memory Fallback (when storage is unavailable)
    // ============================================

    const memoryStorage = {
        _data: {},
        getItem: function(key) { return this._data[key] || null; },
        setItem: function(key, value) { this._data[key] = value; },
        removeItem: function(key) { delete this._data[key]; },
        clear: function() { this._data = {}; },
        key: function(index) { return Object.keys(this._data)[index]; },
        get length() { return Object.keys(this._data).length; }
    };

    // ============================================
    // Storage Availability Check
    // ============================================

    function isStorageAvailable(type) {
        try {
            const storage = window[type];
            const testKey = '__vodo_storage_test__';
            storage.setItem(testKey, 'test');
            storage.removeItem(testKey);
            return true;
        } catch (e) {
            return false;
        }
    }

    const localStorageAvailable = isStorageAvailable('localStorage');
    const sessionStorageAvailable = isStorageAvailable('sessionStorage');

    // ============================================
    // Storage Factory
    // ============================================

    function createStorage(storageType) {
        const storage = storageType === 'local'
            ? (localStorageAvailable ? window.localStorage : memoryStorage)
            : (sessionStorageAvailable ? window.sessionStorage : memoryStorage);

        return {
            /**
             * Get a value from storage
             * @param {string} key - Storage key
             * @param {*} defaultValue - Default value if not found
             * @returns {*} Stored value or default
             */
            get: function(key, defaultValue = null) {
                try {
                    const prefixedKey = config.prefix + key;
                    const item = storage.getItem(prefixedKey);

                    if (item === null) {
                        return defaultValue;
                    }

                    const parsed = config.deserialize(item);

                    // Check expiration
                    if (parsed && typeof parsed === 'object' && parsed._expires) {
                        if (Date.now() > parsed._expires) {
                            this.remove(key);
                            return defaultValue;
                        }
                        return parsed._value;
                    }

                    return parsed;
                } catch (e) {
                    Vodo.warn(`Storage get error for key "${key}":`, e);
                    return defaultValue;
                }
            },

            /**
             * Set a value in storage
             * @param {string} key - Storage key
             * @param {*} value - Value to store
             * @param {number|null} expiresIn - Expiration in milliseconds (null = no expiration)
             * @returns {boolean} Success
             */
            set: function(key, value, expiresIn = null) {
                try {
                    const prefixedKey = config.prefix + key;
                    let toStore = value;

                    // Add expiration wrapper if specified
                    if (expiresIn !== null && storageType === 'local') {
                        toStore = {
                            _value: value,
                            _expires: Date.now() + expiresIn
                        };
                    }

                    storage.setItem(prefixedKey, config.serialize(toStore));
                    return true;
                } catch (e) {
                    Vodo.warn(`Storage set error for key "${key}":`, e);

                    // Handle quota exceeded
                    if (e.name === 'QuotaExceededError' || e.code === 22) {
                        Vodo.warn('Storage quota exceeded, attempting cleanup');
                        this.cleanup();

                        // Retry once after cleanup
                        try {
                            storage.setItem(config.prefix + key, config.serialize(value));
                            return true;
                        } catch (e2) {
                            Vodo.error('Storage still full after cleanup');
                        }
                    }
                    return false;
                }
            },

            /**
             * Remove a value from storage
             * @param {string} key - Storage key
             */
            remove: function(key) {
                try {
                    storage.removeItem(config.prefix + key);
                } catch (e) {
                    Vodo.warn(`Storage remove error for key "${key}":`, e);
                }
            },

            /**
             * Check if key exists in storage
             * @param {string} key - Storage key
             * @returns {boolean}
             */
            has: function(key) {
                return this.get(key) !== null;
            },

            /**
             * Clear all Vodo storage items
             */
            clear: function() {
                try {
                    const keysToRemove = [];
                    for (let i = 0; i < storage.length; i++) {
                        const key = storage.key(i);
                        if (key && key.startsWith(config.prefix)) {
                            keysToRemove.push(key);
                        }
                    }
                    keysToRemove.forEach(key => storage.removeItem(key));
                } catch (e) {
                    Vodo.warn('Storage clear error:', e);
                }
            },

            /**
             * Get all keys with Vodo prefix
             * @returns {Array<string>}
             */
            keys: function() {
                const keys = [];
                try {
                    for (let i = 0; i < storage.length; i++) {
                        const key = storage.key(i);
                        if (key && key.startsWith(config.prefix)) {
                            keys.push(key.substring(config.prefix.length));
                        }
                    }
                } catch (e) {
                    Vodo.warn('Storage keys error:', e);
                }
                return keys;
            },

            /**
             * Get storage size in bytes
             * @returns {number}
             */
            size: function() {
                let size = 0;
                try {
                    for (let i = 0; i < storage.length; i++) {
                        const key = storage.key(i);
                        if (key && key.startsWith(config.prefix)) {
                            size += (storage.getItem(key) || '').length * 2; // UTF-16
                        }
                    }
                } catch (e) {
                    Vodo.warn('Storage size error:', e);
                }
                return size;
            },

            /**
             * Cleanup expired items
             */
            cleanup: function() {
                if (storageType !== 'local') return;

                try {
                    const now = Date.now();
                    const keysToRemove = [];

                    for (let i = 0; i < storage.length; i++) {
                        const key = storage.key(i);
                        if (key && key.startsWith(config.prefix)) {
                            try {
                                const item = config.deserialize(storage.getItem(key));
                                if (item && item._expires && now > item._expires) {
                                    keysToRemove.push(key);
                                }
                            } catch (e) {
                                // Invalid JSON, remove it
                                keysToRemove.push(key);
                            }
                        }
                    }

                    keysToRemove.forEach(key => storage.removeItem(key));
                    Vodo.log(`Storage cleanup: removed ${keysToRemove.length} expired items`);
                } catch (e) {
                    Vodo.warn('Storage cleanup error:', e);
                }
            },

            /**
             * Get all items as object
             * @returns {Object}
             */
            all: function() {
                const items = {};
                this.keys().forEach(key => {
                    items[key] = this.get(key);
                });
                return items;
            }
        };
    }

    // ============================================
    // Storage Module
    // ============================================

    const storage = {
        local: createStorage('local'),
        session: createStorage('session'),
        config: config,

        /**
         * Check if localStorage is available
         * @returns {boolean}
         */
        isLocalAvailable: function() {
            return localStorageAvailable;
        },

        /**
         * Check if sessionStorage is available
         * @returns {boolean}
         */
        isSessionAvailable: function() {
            return sessionStorageAvailable;
        },

        /**
         * Set storage prefix
         * @param {string} prefix
         */
        setPrefix: function(prefix) {
            config.prefix = prefix;
        },

        /**
         * Create a namespaced storage
         * @param {string} namespace
         * @returns {Object}
         */
        namespace: function(namespace) {
            const ns = namespace + '_';
            return {
                local: {
                    get: (key, def) => storage.local.get(ns + key, def),
                    set: (key, val, exp) => storage.local.set(ns + key, val, exp),
                    remove: (key) => storage.local.remove(ns + key),
                    has: (key) => storage.local.has(ns + key),
                    clear: () => {
                        storage.local.keys()
                            .filter(k => k.startsWith(ns))
                            .forEach(k => storage.local.remove(k));
                    }
                },
                session: {
                    get: (key, def) => storage.session.get(ns + key, def),
                    set: (key, val) => storage.session.set(ns + key, val),
                    remove: (key) => storage.session.remove(ns + key),
                    has: (key) => storage.session.has(ns + key),
                    clear: () => {
                        storage.session.keys()
                            .filter(k => k.startsWith(ns))
                            .forEach(k => storage.session.remove(k));
                    }
                }
            };
        },

        /**
         * Initialize storage module
         */
        init: function() {
            // Run cleanup on init
            this.local.cleanup();

            // Warn if falling back to memory
            if (!localStorageAvailable) {
                Vodo.warn('localStorage unavailable, using memory fallback');
            }
            if (!sessionStorageAvailable) {
                Vodo.warn('sessionStorage unavailable, using memory fallback');
            }
        }
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('storage', storage);

})(typeof window !== 'undefined' ? window : this);
