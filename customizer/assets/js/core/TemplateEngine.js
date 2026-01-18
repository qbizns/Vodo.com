/**
 * TailwindPlus Customizer - Template Engine
 * ==========================================
 * Parse and update HTML templates with field values
 * 
 * @module core/TemplateEngine
 * @version 1.0.0
 * 
 * Supports:
 * - Placeholder syntax: {{fieldId}}
 * - Selector-based updates via field config
 * - Nested object values: {{group.field}}
 * - Conditional rendering: {{#if fieldId}}...{{/if}}
 * - Loops: {{#each items}}...{{/each}}
 */

export class TemplateEngine {
    constructor() {
        this.helpers = new Map();
        this._registerDefaultHelpers();
    }

    /**
     * Register default template helpers
     * @private
     */
    _registerDefaultHelpers() {
        // Currency formatter
        this.registerHelper('currency', (value, options = {}) => {
            const { locale = 'ar-SA', currency = 'SAR' } = options;
            return new Intl.NumberFormat(locale, { style: 'currency', currency }).format(value);
        });

        // Date formatter
        this.registerHelper('date', (value, options = {}) => {
            const { locale = 'ar-SA', format = 'long' } = options;
            const date = new Date(value);
            return date.toLocaleDateString(locale, { dateStyle: format });
        });

        // Truncate text
        this.registerHelper('truncate', (value, options = {}) => {
            const { length = 100, suffix = '...' } = options;
            if (typeof value !== 'string') return value;
            return value.length > length ? value.substring(0, length) + suffix : value;
        });

        // Uppercase
        this.registerHelper('upper', (value) => String(value).toUpperCase());

        // Lowercase
        this.registerHelper('lower', (value) => String(value).toLowerCase());
    }

    /**
     * Register a custom helper
     * @param {string} name - Helper name
     * @param {Function} fn - Helper function
     */
    registerHelper(name, fn) {
        this.helpers.set(name, fn);
    }

    /**
     * Parse template with values
     * @param {string} template - HTML template string
     * @param {Object} values - Field values
     * @param {Object} options - Parse options
     * @returns {string} Parsed HTML
     */
    parse(template, values = {}, options = {}) {
        let result = template;

        // Process conditionals
        result = this._processConditionals(result, values);

        // Process loops
        result = this._processLoops(result, values);

        // Process placeholders
        result = this._processPlaceholders(result, values);

        // Process helpers
        result = this._processHelpers(result, values);

        return result;
    }

    /**
     * Apply values to HTML using field configurations
     * @param {string} html - HTML string
     * @param {Object} values - Field values
     * @param {Array} fields - Field configurations
     * @returns {string} Updated HTML
     */
    applyFields(html, values = {}, fields = []) {
        const temp = document.createElement('div');
        temp.innerHTML = html;

        fields.forEach(fieldConfig => {
            const value = this._getNestedValue(values, fieldConfig.id);
            if (value === undefined) return;

            const { selector, attribute = 'textContent' } = fieldConfig;

            if (selector) {
                const elements = temp.querySelectorAll(selector);
                elements.forEach(el => {
                    this._applyValueToElement(el, attribute, value, fieldConfig);
                });
            }
        });

        return temp.innerHTML;
    }

    /**
     * Process {{#if condition}}...{{/if}} blocks
     * @param {string} template - Template string
     * @param {Object} values - Values object
     * @returns {string} Processed template
     * @private
     */
    _processConditionals(template, values) {
        // Simple if: {{#if fieldId}}...{{/if}}
        const ifRegex = /\{\{#if\s+([^}]+)\}\}([\s\S]*?)\{\{\/if\}\}/g;
        
        return template.replace(ifRegex, (match, condition, content) => {
            const value = this._getNestedValue(values, condition.trim());
            const isTruthy = this._isTruthy(value);
            
            // Check for {{else}}
            const elseIndex = content.indexOf('{{else}}');
            if (elseIndex !== -1) {
                const ifContent = content.substring(0, elseIndex);
                const elseContent = content.substring(elseIndex + 8);
                return isTruthy ? ifContent : elseContent;
            }
            
            return isTruthy ? content : '';
        });
    }

    /**
     * Process {{#each items}}...{{/each}} blocks
     * @param {string} template - Template string
     * @param {Object} values - Values object
     * @returns {string} Processed template
     * @private
     */
    _processLoops(template, values) {
        const eachRegex = /\{\{#each\s+([^}]+)\}\}([\s\S]*?)\{\{\/each\}\}/g;
        
        return template.replace(eachRegex, (match, arrayPath, content) => {
            const array = this._getNestedValue(values, arrayPath.trim());
            
            if (!Array.isArray(array)) return '';
            
            return array.map((item, index) => {
                let itemContent = content;
                
                // Replace {{this}} with current item (for simple arrays)
                itemContent = itemContent.replace(/\{\{this\}\}/g, item);
                
                // Replace {{@index}} with current index
                itemContent = itemContent.replace(/\{\{@index\}\}/g, index);
                
                // Replace {{@first}} and {{@last}}
                itemContent = itemContent.replace(/\{\{@first\}\}/g, index === 0 ? 'true' : '');
                itemContent = itemContent.replace(/\{\{@last\}\}/g, index === array.length - 1 ? 'true' : '');
                
                // Replace item properties {{fieldId}}
                if (typeof item === 'object') {
                    Object.keys(item).forEach(key => {
                        const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g');
                        itemContent = itemContent.replace(regex, this._escapeHtml(item[key]));
                    });
                }
                
                return itemContent;
            }).join('');
        });
    }

    /**
     * Process {{fieldId}} placeholders
     * @param {string} template - Template string
     * @param {Object} values - Values object
     * @returns {string} Processed template
     * @private
     */
    _processPlaceholders(template, values) {
        const placeholderRegex = /\{\{([^#\/][^}]*)\}\}/g;
        
        return template.replace(placeholderRegex, (match, path) => {
            // Skip helpers (contain |)
            if (path.includes('|')) return match;
            
            const value = this._getNestedValue(values, path.trim());
            
            if (value === undefined || value === null) return '';
            if (typeof value === 'object') return JSON.stringify(value);
            
            return this._escapeHtml(String(value));
        });
    }

    /**
     * Process {{value | helper}} syntax
     * @param {string} template - Template string
     * @param {Object} values - Values object
     * @returns {string} Processed template
     * @private
     */
    _processHelpers(template, values) {
        const helperRegex = /\{\{([^}|]+)\|([^}]+)\}\}/g;
        
        return template.replace(helperRegex, (match, path, helperStr) => {
            const value = this._getNestedValue(values, path.trim());
            const [helperName, ...args] = helperStr.trim().split(':');
            
            const helper = this.helpers.get(helperName.trim());
            if (!helper) return this._escapeHtml(String(value));
            
            // Parse helper options
            const options = {};
            args.forEach(arg => {
                const [key, val] = arg.split('=');
                if (key && val) {
                    options[key.trim()] = val.trim().replace(/['"]/g, '');
                }
            });
            
            return this._escapeHtml(String(helper(value, options)));
        });
    }

    /**
     * Get nested value from object using dot notation
     * @param {Object} obj - Source object
     * @param {string} path - Dot-notation path
     * @returns {*} Value at path
     * @private
     */
    _getNestedValue(obj, path) {
        return path.split('.').reduce((curr, key) => {
            return curr && curr[key] !== undefined ? curr[key] : undefined;
        }, obj);
    }

    /**
     * Apply value to DOM element
     * @param {HTMLElement} element - Target element
     * @param {string} attribute - Attribute to modify
     * @param {*} value - Value to apply
     * @param {Object} fieldConfig - Field configuration
     * @private
     */
    _applyValueToElement(element, attribute, value, fieldConfig) {
        switch (attribute) {
            case 'textContent':
                element.textContent = value;
                break;
                
            case 'innerHTML':
                element.innerHTML = value;
                break;
                
            case 'src':
                if (value) {
                    element.setAttribute('src', value);
                } else {
                    element.removeAttribute('src');
                }
                break;
                
            case 'href':
                if (fieldConfig.type === 'link' && typeof value === 'object') {
                    element.setAttribute('href', this._buildLinkUrl(value));
                    if (value.target) {
                        element.setAttribute('target', value.target);
                    }
                } else if (value) {
                    element.setAttribute('href', value);
                }
                break;
                
            case 'style':
                if (typeof value === 'object') {
                    Object.entries(value).forEach(([prop, val]) => {
                        element.style[prop] = val;
                    });
                } else if (typeof value === 'string') {
                    element.setAttribute('style', value);
                }
                break;
                
            case 'backgroundColor':
            case 'color':
            case 'background':
                element.style[attribute] = value;
                break;
                
            case 'class':
                if (Array.isArray(value)) {
                    value.forEach(cls => element.classList.add(cls));
                } else if (typeof value === 'string') {
                    value.split(' ').forEach(cls => {
                        if (cls) element.classList.add(cls);
                    });
                }
                break;
                
            case 'hidden':
            case 'visible':
                const shouldHide = attribute === 'hidden' ? value : !value;
                element.hidden = shouldHide;
                break;
                
            case 'disabled':
                element.disabled = !!value;
                break;
                
            default:
                // Generic attribute
                if (value === false || value === null || value === undefined) {
                    element.removeAttribute(attribute);
                } else {
                    element.setAttribute(attribute, value);
                }
        }
    }

    /**
     * Build URL from link field value
     * @param {Object} linkValue - Link field value
     * @returns {string} Full URL
     * @private
     */
    _buildLinkUrl(linkValue) {
        const { type, url } = linkValue;
        
        if (!url) return '#';
        
        switch (type) {
            case 'email':
                return `mailto:${url}`;
            case 'phone':
                return `tel:${url}`;
            case 'external':
                return url.startsWith('http') ? url : `https://${url}`;
            case 'none':
                return '#';
            default:
                return url;
        }
    }

    /**
     * Check if value is truthy
     * @param {*} value - Value to check
     * @returns {boolean} Is truthy
     * @private
     */
    _isTruthy(value) {
        if (Array.isArray(value)) return value.length > 0;
        if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0;
        return !!value;
    }

    /**
     * Escape HTML special characters
     * @param {string} str - String to escape
     * @returns {string} Escaped string
     * @private
     */
    _escapeHtml(str) {
        const escapeMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };
        return String(str).replace(/[&<>"']/g, char => escapeMap[char]);
    }
}

// Singleton instance
export const templateEngine = new TemplateEngine();

export default templateEngine;
