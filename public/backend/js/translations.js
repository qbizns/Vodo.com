/**
 * Vodo I18n - JavaScript Translation Helper
 * 
 * Usage:
 *   Trans.get('common.save')
 *   Trans.get('messages.welcome', { name: 'John' })
 *   Trans.choice('items.count', 5)
 *   Trans.choice('items.count', 5, { type: 'products' })
 * 
 * Initialize with Blade directive:
 *   @translationsScript
 * 
 * Or manually:
 *   window.I18n = { locale: 'en', direction: 'ltr', messages: {...} }
 */
(function(window) {
    'use strict';

    /**
     * Translation Manager
     */
    const Trans = {
        /**
         * Current locale
         */
        locale: 'en',

        /**
         * Text direction (ltr/rtl)
         */
        direction: 'ltr',

        /**
         * Loaded translations
         */
        messages: {},

        /**
         * Cache for parsed translations
         */
        _cache: {},

        /**
         * Initialize the translator
         * @param {Object} config - { locale, direction, messages }
         */
        init: function(config) {
            if (config) {
                this.locale = config.locale || 'en';
                this.direction = config.direction || 'ltr';
                this.messages = config.messages || {};
            } else if (window.I18n) {
                this.locale = window.I18n.locale || 'en';
                this.direction = window.I18n.direction || 'ltr';
                this.messages = window.I18n.messages || {};
            }
            this._cache = {};
            return this;
        },

        /**
         * Get a translation
         * @param {string} key - Translation key (e.g., 'common.save')
         * @param {Object} replace - Replacement values
         * @returns {string}
         */
        get: function(key, replace) {
            replace = replace || {};

            // Check cache
            const cacheKey = key + JSON.stringify(replace);
            if (this._cache[cacheKey]) {
                return this._cache[cacheKey];
            }

            let translation = this._resolve(key);

            // If not found, return humanized key
            if (translation === null || translation === undefined) {
                translation = this._humanize(key);
            }

            // Make replacements
            translation = this._makeReplacements(translation, replace);

            // Cache and return
            this._cache[cacheKey] = translation;
            return translation;
        },

        /**
         * Translate with pluralization
         * @param {string} key - Translation key
         * @param {number} count - Count for pluralization
         * @param {Object} replace - Replacement values
         * @returns {string}
         */
        choice: function(key, count, replace) {
            replace = replace || {};
            replace.count = count;

            let translation = this._resolve(key);

            if (translation === null || translation === undefined) {
                return this._humanize(key);
            }

            // Handle array pluralization
            if (Array.isArray(translation)) {
                translation = this._selectPlural(translation, count);
            }
            // Handle pipe-separated pluralization: "one item|:count items"
            else if (typeof translation === 'string' && translation.includes('|')) {
                translation = this._selectFromPipe(translation, count);
            }
            // Handle object pluralization: { one: '...', other: '...' }
            else if (typeof translation === 'object') {
                translation = this._selectFromObject(translation, count);
            }

            return this._makeReplacements(translation, replace);
        },

        /**
         * Check if a translation exists
         * @param {string} key
         * @returns {boolean}
         */
        has: function(key) {
            return this._resolve(key) !== null;
        },

        /**
         * Set the current locale
         * @param {string} locale
         */
        setLocale: function(locale) {
            this.locale = locale;
            this._cache = {};
        },

        /**
         * Get the current locale
         * @returns {string}
         */
        getLocale: function() {
            return this.locale;
        },

        /**
         * Check if current locale is RTL
         * @returns {boolean}
         */
        isRtl: function() {
            return this.direction === 'rtl';
        },

        /**
         * Load additional translations
         * @param {Object} translations
         */
        load: function(translations) {
            this.messages = this._deepMerge(this.messages, translations);
            this._cache = {};
        },

        /**
         * Load translations from server
         * @param {string} url - API endpoint
         * @param {Function} callback
         */
        loadFromServer: function(url, callback) {
            const self = this;
            fetch(url || '/api/translations/js')
                .then(response => response.json())
                .then(data => {
                    self.locale = data.locale || self.locale;
                    self.direction = data.direction || self.direction;
                    self.messages = data.messages || {};
                    self._cache = {};
                    if (callback) callback(null, data);
                })
                .catch(error => {
                    console.error('Failed to load translations:', error);
                    if (callback) callback(error);
                });
        },

        /**
         * Resolve a key to its translation
         * @private
         */
        _resolve: function(key) {
            const parts = key.split('.');
            let current = this.messages;

            for (let i = 0; i < parts.length; i++) {
                if (current === null || current === undefined) {
                    return null;
                }
                current = current[parts[i]];
            }

            return current;
        },

        /**
         * Make replacements in a string
         * @private
         */
        _makeReplacements: function(str, replace) {
            if (typeof str !== 'string') {
                return str;
            }

            for (const key in replace) {
                if (replace.hasOwnProperty(key)) {
                    const value = replace[key];
                    // :key, :KEY, :Key
                    str = str.replace(new RegExp(':' + key, 'g'), value);
                    str = str.replace(new RegExp(':' + key.toUpperCase(), 'g'), String(value).toUpperCase());
                    str = str.replace(new RegExp(':' + this._capitalize(key), 'g'), this._capitalize(String(value)));
                }
            }

            return str;
        },

        /**
         * Select from pipe-separated pluralization
         * @private
         */
        _selectFromPipe: function(str, count) {
            const parts = str.split('|');
            if (parts.length === 1) {
                return parts[0];
            }
            return count === 1 ? parts[0].trim() : (parts[1] || parts[0]).trim();
        },

        /**
         * Select from object pluralization
         * @private
         */
        _selectFromObject: function(obj, count) {
            if (count === 0 && obj.zero) return obj.zero;
            if (count === 1 && obj.one) return obj.one;
            if (count === 2 && obj.two) return obj.two;
            if (count >= 3 && count <= 10 && obj.few) return obj.few;
            if (count >= 11 && count <= 99 && obj.many) return obj.many;
            return obj.other || obj.many || obj.few || obj.one || '';
        },

        /**
         * Select from array pluralization
         * @private
         */
        _selectPlural: function(arr, count) {
            if (count === 1) {
                return arr[0] || '';
            }
            return arr[1] || arr[0] || '';
        },

        /**
         * Humanize a key (last segment, converted to title case)
         * @private
         */
        _humanize: function(key) {
            const parts = key.split('.');
            const last = parts[parts.length - 1];
            return last
                .replace(/[_-]/g, ' ')
                .replace(/([a-z])([A-Z])/g, '$1 $2')
                .replace(/\b\w/g, l => l.toUpperCase());
        },

        /**
         * Capitalize first letter
         * @private
         */
        _capitalize: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        },

        /**
         * Deep merge objects
         * @private
         */
        _deepMerge: function(target, source) {
            const output = Object.assign({}, target);
            if (this._isObject(target) && this._isObject(source)) {
                Object.keys(source).forEach(key => {
                    if (this._isObject(source[key])) {
                        if (!(key in target)) {
                            Object.assign(output, { [key]: source[key] });
                        } else {
                            output[key] = this._deepMerge(target[key], source[key]);
                        }
                    } else {
                        Object.assign(output, { [key]: source[key] });
                    }
                });
            }
            return output;
        },

        /**
         * Check if value is an object
         * @private
         */
        _isObject: function(item) {
            return item && typeof item === 'object' && !Array.isArray(item);
        }
    };

    // Initialize with window.I18n if available
    if (window.I18n) {
        Trans.init(window.I18n);
    }

    // Export
    window.Trans = Trans;

    // Also export as I18n for convenience
    window.I18n = window.I18n || {};
    window.I18n.Trans = Trans;

    // Helper shorthand functions
    window.__ = function(key, replace) {
        return Trans.get(key, replace);
    };

    window.__c = function(key, count, replace) {
        return Trans.choice(key, count, replace);
    };

})(window);
