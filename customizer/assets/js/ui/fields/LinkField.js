/**
 * TailwindPlus Customizer - Link Field
 * =====================================
 * Link type selector with URL input
 * 
 * @module ui/fields/LinkField
 * @version 1.0.0
 */

import { BaseField } from '../../core/FieldRegistry.js';

export class LinkField extends BaseField {
    static get type() {
        return 'link';
    }

    static getDefaultValue() {
        return { type: 'none', url: '', target: '_self' };
    }

    constructor(options) {
        super(options);
        // Normalize value
        if (!this.value || typeof this.value === 'string') {
            this.value = {
                type: this.value ? 'external' : 'none',
                url: this.value || '',
                target: '_self'
            };
        }
    }

    _template() {
        const { id, label, hint, required, showTarget = true } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);
        
        const linkTypes = [
            { value: 'none', label: { ar: 'بدون رابط', en: 'No link' } },
            { value: 'external', label: { ar: 'رابط خارجي', en: 'External link' } },
            { value: 'page', label: { ar: 'صفحة داخلية', en: 'Internal page' } },
            { value: 'product', label: { ar: 'منتج', en: 'Product' } },
            { value: 'category', label: { ar: 'تصنيف', en: 'Category' } },
            { value: 'email', label: { ar: 'بريد إلكتروني', en: 'Email' } },
            { value: 'phone', label: { ar: 'رقم هاتف', en: 'Phone' } },
        ];

        const currentType = this.value.type || 'none';
        const showUrlInput = !['none'].includes(currentType);

        return `
            <div class="field field--link">
                ${label ? `
                    <label class="field__label">
                        ${labelText}
                        ${required ? '<span class="field__required">*</span>' : ''}
                    </label>
                ` : ''}
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                
                <div class="field__link-wrapper">
                    <!-- Type + URL Row -->
                    <div class="field__link-row">
                        <!-- Type Selector -->
                        <div class="field__link-type">
                            <select id="field-${id}-type" class="field__select">
                                ${linkTypes.map(type => `
                                    <option value="${type.value}" ${currentType === type.value ? 'selected' : ''}>
                                        ${this._getText(type.label)}
                                    </option>
                                `).join('')}
                            </select>
                            <svg class="field__select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </div>
                        
                        <!-- URL Input -->
                        <div class="field__link-url ${showUrlInput ? '' : 'is-hidden'}">
                            <span class="field__link-prefix">${this._getPrefix(currentType)}</span>
                            <input 
                                type="${this._getInputType(currentType)}" 
                                id="field-${id}-url" 
                                class="field__input"
                                placeholder="${this._getPlaceholder(currentType)}"
                                value="${this.value.url || ''}"
                            >
                        </div>
                    </div>
                    
                    <!-- Target Selector -->
                    ${showTarget && showUrlInput ? `
                        <div class="field__link-target">
                            <label class="field__checkbox">
                                <input 
                                    type="checkbox" 
                                    id="field-${id}-target"
                                    ${this.value.target === '_blank' ? 'checked' : ''}
                                >
                                <span class="field__checkbox-mark"></span>
                                <span class="field__checkbox-label">
                                    ${this._getText({ ar: 'فتح في نافذة جديدة', en: 'Open in new window' })}
                                </span>
                            </label>
                        </div>
                    ` : ''}
                </div>
                
                <span class="field__error"></span>
            </div>
        `;
    }

    _bindEvents() {
        const typeSelect = this.container.querySelector(`#field-${this.config.id}-type`);
        const urlInput = this.container.querySelector(`#field-${this.config.id}-url`);
        const targetCheckbox = this.container.querySelector(`#field-${this.config.id}-target`);

        typeSelect?.addEventListener('change', (e) => {
            this.value.type = e.target.value;
            
            // Clear URL when switching to none
            if (e.target.value === 'none') {
                this.value.url = '';
            }
            
            this.onChange(this.value, this.config.id);
            this._rerender();
        });

        urlInput?.addEventListener('input', (e) => {
            this.value.url = e.target.value;
            this.onChange(this.value, this.config.id);
        });

        targetCheckbox?.addEventListener('change', (e) => {
            this.value.target = e.target.checked ? '_blank' : '_self';
            this.onChange(this.value, this.config.id);
        });
    }

    /**
     * Get URL prefix based on type
     * @param {string} type - Link type
     * @returns {string} Prefix
     * @private
     */
    _getPrefix(type) {
        const prefixes = {
            external: '#',
            page: '/',
            product: '/product/',
            category: '/category/',
            email: 'mailto:',
            phone: 'tel:',
        };
        return prefixes[type] || '#';
    }

    /**
     * Get input type based on link type
     * @param {string} type - Link type
     * @returns {string} Input type
     * @private
     */
    _getInputType(type) {
        const types = {
            email: 'email',
            phone: 'tel',
            external: 'url',
        };
        return types[type] || 'text';
    }

    /**
     * Get placeholder based on type
     * @param {string} type - Link type
     * @returns {string} Placeholder
     * @private
     */
    _getPlaceholder(type) {
        const placeholders = {
            external: 'https://example.com',
            page: this._getText({ ar: 'اسم الصفحة', en: 'Page name' }),
            product: this._getText({ ar: 'رقم المنتج', en: 'Product ID' }),
            category: this._getText({ ar: 'رقم التصنيف', en: 'Category ID' }),
            email: 'email@example.com',
            phone: '+966500000000',
        };
        return placeholders[type] || '';
    }

    /**
     * Re-render field
     * @private
     */
    _rerender() {
        if (this.container) {
            this.render(this.container);
        }
    }

    /**
     * Get full URL for output
     * @returns {string} Full URL
     */
    getFullUrl() {
        const { type, url } = this.value;
        
        if (type === 'none' || !url) return '';
        
        switch (type) {
            case 'email':
                return `mailto:${url}`;
            case 'phone':
                return `tel:${url}`;
            case 'external':
                return url.startsWith('http') ? url : `https://${url}`;
            default:
                return url;
        }
    }

    _isEmpty() {
        return this.value.type === 'none' || (this.value.type !== 'none' && !this.value.url);
    }

    validate() {
        const { required } = this.config;
        
        if (required && this._isEmpty()) {
            return {
                valid: false,
                error: this._getText({ ar: 'الرابط مطلوب', en: 'Link is required' })
            };
        }

        // Validate URL format for external links
        if (this.value.type === 'external' && this.value.url) {
            try {
                new URL(this.value.url.startsWith('http') ? this.value.url : `https://${this.value.url}`);
            } catch {
                return {
                    valid: false,
                    error: this._getText({ ar: 'رابط غير صالح', en: 'Invalid URL' })
                };
            }
        }

        // Validate email format
        if (this.value.type === 'email' && this.value.url) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.value.url)) {
                return {
                    valid: false,
                    error: this._getText({ ar: 'بريد إلكتروني غير صالح', en: 'Invalid email' })
                };
            }
        }

        return { valid: true, error: null };
    }
}

export default LinkField;
