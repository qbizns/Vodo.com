/**
 * VODO Platform - Core Framework
 *
 * Provides the global namespace, configuration, and utility functions
 * for the VODO frontend framework.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    // ============================================
    // Global Namespace
    // ============================================

    const Vodo = {
        version: '1.0.0',

        // Configuration
        config: {
            debug: false,
            baseUrl: '',
            csrfToken: '',
            locale: 'en',
            rtl: false,
            modulePrefix: '',
            brandName: 'VODO'
        },

        // Module placeholders (populated by other files)
        ajax: null,
        router: null,
        components: null,
        events: null,
        skeleton: null,
        forms: null,
        modals: null,
        notify: null,
        storage: null,

        // Plugin extensions namespace
        plugins: {},

        // Utilities namespace
        utils: {}
    };

    // ============================================
    // Configuration Management
    // ============================================

    /**
     * Initialize Vodo with configuration
     * @param {Object} options - Configuration options
     */
    Vodo.init = function(options = {}) {
        // Merge with BackendConfig if available
        if (global.BackendConfig) {
            Object.assign(this.config, {
                baseUrl: BackendConfig.baseUrl || '',
                csrfToken: BackendConfig.csrfToken || '',
                modulePrefix: BackendConfig.modulePrefix || '',
                brandName: BackendConfig.brandName || 'VODO',
                currentPage: BackendConfig.currentPage || 'dashboard',
                currentPageLabel: BackendConfig.currentPageLabel || 'Dashboard',
                currentPageIcon: BackendConfig.currentPageIcon || 'layoutDashboard'
            });
        }

        // Merge custom options
        Object.assign(this.config, options);

        // Get CSRF token from meta tag if not set
        if (!this.config.csrfToken) {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                this.config.csrfToken = meta.getAttribute('content');
            }
        }

        // Detect RTL
        this.config.rtl = document.documentElement.dir === 'rtl';

        // Debug mode from URL param
        if (global.location.search.includes('vodo_debug=1')) {
            this.config.debug = true;
        }

        this.log('Vodo initialized', this.config);

        // Emit init event
        if (this.events) {
            this.events.emit('vodo:init', this.config);
        }

        return this;
    };

    /**
     * Get configuration value
     * @param {string} key - Config key (dot notation supported)
     * @param {*} defaultValue - Default value if not found
     */
    Vodo.getConfig = function(key, defaultValue = null) {
        const keys = key.split('.');
        let value = this.config;

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return defaultValue;
            }
        }

        return value;
    };

    /**
     * Set configuration value
     * @param {string} key - Config key (dot notation supported)
     * @param {*} value - Value to set
     */
    Vodo.setConfig = function(key, value) {
        const keys = key.split('.');
        let obj = this.config;

        for (let i = 0; i < keys.length - 1; i++) {
            const k = keys[i];
            if (!(k in obj)) {
                obj[k] = {};
            }
            obj = obj[k];
        }

        obj[keys[keys.length - 1]] = value;
        return this;
    };

    // ============================================
    // Utility Functions
    // ============================================

    /**
     * Debug logging
     */
    Vodo.log = function(...args) {
        if (this.config.debug) {
            console.log('[Vodo]', ...args);
        }
    };

    /**
     * Warning logging
     */
    Vodo.warn = function(...args) {
        console.warn('[Vodo]', ...args);
    };

    /**
     * Error logging
     */
    Vodo.error = function(...args) {
        console.error('[Vodo]', ...args);
    };

    /**
     * Generate unique ID
     */
    Vodo.utils.uniqueId = (function() {
        let counter = 0;
        return function(prefix = 'vodo') {
            return `${prefix}_${++counter}_${Date.now().toString(36)}`;
        };
    })();

    /**
     * Debounce function
     */
    Vodo.utils.debounce = function(func, wait, immediate = false) {
        let timeout;
        return function(...args) {
            const context = this;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    /**
     * Throttle function
     */
    Vodo.utils.throttle = function(func, limit) {
        let inThrottle;
        return function(...args) {
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    };

    /**
     * Deep merge objects
     */
    Vodo.utils.deepMerge = function(target, ...sources) {
        if (!sources.length) return target;
        const source = sources.shift();

        if (this.isObject(target) && this.isObject(source)) {
            for (const key in source) {
                if (this.isObject(source[key])) {
                    if (!target[key]) Object.assign(target, { [key]: {} });
                    this.deepMerge(target[key], source[key]);
                } else {
                    Object.assign(target, { [key]: source[key] });
                }
            }
        }

        return this.deepMerge(target, ...sources);
    };

    /**
     * Check if value is plain object
     */
    Vodo.utils.isObject = function(item) {
        return item && typeof item === 'object' && !Array.isArray(item);
    };

    /**
     * Escape HTML
     */
    Vodo.utils.escapeHtml = function(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    /**
     * Parse URL query string
     */
    Vodo.utils.parseQuery = function(queryString) {
        const params = {};
        const searchParams = new URLSearchParams(queryString);
        for (const [key, value] of searchParams) {
            params[key] = value;
        }
        return params;
    };

    /**
     * Build URL with query params
     */
    Vodo.utils.buildUrl = function(base, params = {}) {
        const url = new URL(base, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                url.searchParams.set(key, value);
            }
        });
        return url.toString();
    };

    /**
     * Format bytes to human readable
     */
    Vodo.utils.formatBytes = function(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
    };

    /**
     * Format number with locale
     */
    Vodo.utils.formatNumber = function(number, locale = null) {
        return new Intl.NumberFormat(locale || Vodo.config.locale).format(number);
    };

    /**
     * Check if element is in viewport
     */
    Vodo.utils.isInViewport = function(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    };

    /**
     * Wait for element to exist
     */
    Vodo.utils.waitForElement = function(selector, timeout = 5000) {
        return new Promise((resolve, reject) => {
            const element = document.querySelector(selector);
            if (element) {
                resolve(element);
                return;
            }

            const observer = new MutationObserver((mutations, obs) => {
                const element = document.querySelector(selector);
                if (element) {
                    obs.disconnect();
                    resolve(element);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            setTimeout(() => {
                observer.disconnect();
                reject(new Error(`Element ${selector} not found within ${timeout}ms`));
            }, timeout);
        });
    };

    /**
     * Copy text to clipboard
     */
    Vodo.utils.copyToClipboard = async function(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                return true;
            } catch (e) {
                return false;
            } finally {
                document.body.removeChild(textarea);
            }
        }
    };

    /**
     * Repeat string/template helper
     */
    Vodo.utils.repeat = function(count, callback) {
        return Array.from({ length: count }, (_, i) => callback(i)).join('');
    };

    /**
     * Template literal helper for creating HTML
     */
    Vodo.utils.html = function(strings, ...values) {
        return strings.reduce((result, string, i) => {
            const value = values[i - 1];
            if (Array.isArray(value)) {
                return result + value.join('') + string;
            }
            return result + (value !== undefined ? value : '') + string;
        });
    };

    // ============================================
    // DOM Ready Handler
    // ============================================

    Vodo.ready = function(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    };

    // ============================================
    // Module Registration
    // ============================================

    /**
     * Register a module
     * @param {string} name - Module name
     * @param {Object} module - Module object
     */
    Vodo.registerModule = function(name, module) {
        if (this[name]) {
            this.warn(`Module "${name}" already exists, overwriting`);
        }
        this[name] = module;
        this.log(`Module "${name}" registered`);

        // Initialize module if it has init method and Vodo is already initialized
        if (module.init && this.config.csrfToken) {
            module.init();
        }

        return this;
    };

    /**
     * Register a plugin extension
     * @param {string} pluginSlug - Plugin identifier
     * @param {Object} extension - Extension object
     */
    Vodo.registerPlugin = function(pluginSlug, extension) {
        this.plugins[pluginSlug] = extension;
        this.log(`Plugin "${pluginSlug}" registered`);

        // Emit plugin registered event
        if (this.events) {
            this.events.emit('plugin:registered', pluginSlug, extension);
        }

        return this;
    };

    // ============================================
    // Expose Global
    // ============================================

    global.Vodo = Vodo;

})(typeof window !== 'undefined' ? window : this);
