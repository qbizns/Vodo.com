/**
 * TailwindPlus Customizer - Field Registry
 * =========================================
 * Extensible field type registration system
 * 
 * @module core/FieldRegistry
 * @version 1.0.0
 * 
 * @example
 * // Register a custom field type
 * fieldRegistry.register('rating', RatingField);
 * 
 * // Create field instance
 * const field = fieldRegistry.create('rating', config, value, onChange);
 */

import { eventBus } from './EventBus.js';

class FieldRegistry {
    constructor() {
        this._fields = new Map();
        this._validators = new Map();
    }

    /**
     * Register a field type
     * @param {string} type - Field type identifier
     * @param {Class} FieldClass - Field component class
     * @param {Object} options - Registration options
     * @param {Function} options.validator - Optional validator function
     */
    register(type, FieldClass, options = {}) {
        if (this._fields.has(type)) {
            console.warn(`Field type "${type}" is already registered. Overwriting.`);
        }

        this._fields.set(type, FieldClass);
        
        if (options.validator) {
            this._validators.set(type, options.validator);
        }

        eventBus.emit('field:registered', { type, FieldClass });
    }

    /**
     * Unregister a field type
     * @param {string} type - Field type identifier
     */
    unregister(type) {
        this._fields.delete(type);
        this._validators.delete(type);
    }

    /**
     * Check if field type exists
     * @param {string} type - Field type identifier
     * @returns {boolean} Exists
     */
    has(type) {
        return this._fields.has(type);
    }

    /**
     * Get field class
     * @param {string} type - Field type identifier
     * @returns {Class|null} Field class
     */
    get(type) {
        return this._fields.get(type) || null;
    }

    /**
     * Create field instance
     * @param {string} type - Field type identifier
     * @param {Object} config - Field configuration
     * @param {*} value - Initial value
     * @param {Function} onChange - Change callback
     * @param {Object} context - Additional context (language, etc)
     * @returns {Object|null} Field instance
     */
    create(type, config, value, onChange, context = {}) {
        const FieldClass = this._fields.get(type);
        
        if (!FieldClass) {
            console.error(`Unknown field type: "${type}"`);
            return null;
        }

        return new FieldClass({
            config,
            value,
            onChange,
            ...context
        });
    }

    /**
     * Validate field value
     * @param {string} type - Field type identifier
     * @param {*} value - Value to validate
     * @param {Object} config - Field configuration
     * @returns {Object} Validation result { valid, error }
     */
    validate(type, value, config) {
        const validator = this._validators.get(type);
        
        if (!validator) {
            return { valid: true, error: null };
        }

        return validator(value, config);
    }

    /**
     * List all registered field types
     * @returns {string[]} Array of type identifiers
     */
    list() {
        return Array.from(this._fields.keys());
    }

    /**
     * Get all registered fields
     * @returns {Map} Fields map
     */
    getAll() {
        return new Map(this._fields);
    }
}

// Singleton instance
export const fieldRegistry = new FieldRegistry();

// ============================================
// BASE FIELD CLASS
// ============================================

/**
 * Base Field class - extend this for custom fields
 * @class BaseField
 */
export class BaseField {
    /**
     * @param {Object} options - Field options
     * @param {Object} options.config - Field configuration
     * @param {*} options.value - Initial value
     * @param {Function} options.onChange - Change callback
     * @param {string} options.language - Current language
     */
    constructor(options) {
        this.config = options.config || {};
        this.value = options.value;
        this.onChange = options.onChange || (() => {});
        this.language = options.language || 'ar';
        this.container = null;
        this.isDisabled = false;
    }

    /**
     * Get field type identifier
     * @returns {string} Type identifier
     */
    static get type() {
        return 'base';
    }

    /**
     * Get default value for this field type
     * @returns {*} Default value
     */
    static getDefaultValue() {
        return null;
    }

    /**
     * Render field to container
     * @param {HTMLElement} container - Container element
     */
    render(container) {
        this.container = container;
        container.innerHTML = this._template();
        this._bindEvents();
        this._afterRender();
    }

    /**
     * Get field HTML template
     * @returns {string} HTML string
     * @protected
     */
    _template() {
        return '<div class="field">Override _template() in subclass</div>';
    }

    /**
     * Bind event listeners
     * @protected
     */
    _bindEvents() {
        // Override in subclass
    }

    /**
     * Called after render
     * @protected
     */
    _afterRender() {
        // Override in subclass
    }

    /**
     * Get current value
     * @returns {*} Current value
     */
    getValue() {
        return this.value;
    }

    /**
     * Set value
     * @param {*} value - New value
     * @param {boolean} silent - Don't trigger onChange
     */
    setValue(value, silent = false) {
        this.value = value;
        this._updateUI();
        
        if (!silent) {
            this.onChange(value, this.config.id);
        }
    }

    /**
     * Update UI to reflect current value
     * @protected
     */
    _updateUI() {
        // Override in subclass
    }

    /**
     * Validate current value
     * @returns {Object} { valid, error }
     */
    validate() {
        const { required } = this.config;
        
        if (required && this._isEmpty()) {
            return {
                valid: false,
                error: this._getText({ ar: 'هذا الحقل مطلوب', en: 'This field is required' })
            };
        }

        return { valid: true, error: null };
    }

    /**
     * Check if value is empty
     * @returns {boolean} Is empty
     * @protected
     */
    _isEmpty() {
        return this.value === null || this.value === undefined || this.value === '';
    }

    /**
     * Get translated text
     * @param {Object|string} text - Text object or string
     * @returns {string} Translated text
     * @protected
     */
    _getText(text) {
        if (typeof text === 'string') return text;
        return text?.[this.language] || text?.en || text?.ar || '';
    }

    /**
     * Disable field
     */
    disable() {
        this.isDisabled = true;
        if (this.container) {
            this.container.classList.add('field--disabled');
        }
    }

    /**
     * Enable field
     */
    enable() {
        this.isDisabled = false;
        if (this.container) {
            this.container.classList.remove('field--disabled');
        }
    }

    /**
     * Show error message
     * @param {string} message - Error message
     */
    showError(message) {
        if (!this.container) return;
        
        const errorEl = this.container.querySelector('.field__error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.add('is-visible');
        }
        this.container.classList.add('field--error');
    }

    /**
     * Clear error message
     */
    clearError() {
        if (!this.container) return;
        
        const errorEl = this.container.querySelector('.field__error');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.remove('is-visible');
        }
        this.container.classList.remove('field--error');
    }

    /**
     * Destroy field
     */
    destroy() {
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

export default fieldRegistry;
