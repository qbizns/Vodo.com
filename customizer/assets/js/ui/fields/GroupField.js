/**
 * TailwindPlus Customizer - Group Field
 * ======================================
 * Collapsible group for organizing related fields
 * 
 * @module ui/fields/GroupField
 * @version 1.0.0
 */

import { BaseField, fieldRegistry } from '../../core/FieldRegistry.js';

export class GroupField extends BaseField {
    static get type() {
        return 'group';
    }

    static getDefaultValue() {
        return {};
    }

    constructor(options) {
        super(options);
        this.childFields = new Map();
        this.isCollapsed = options.config.collapsed || false;
        
        // Ensure value is object
        if (!this.value || typeof this.value !== 'object') {
            this.value = {};
        }
    }

    _template() {
        const { id, label, hint, fields = [], collapsible = true, border = true } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);

        return `
            <div class="field field--group ${border ? 'field--group-bordered' : ''} ${this.isCollapsed ? 'is-collapsed' : ''}">
                ${label ? `
                    <div class="field__group-header ${collapsible ? 'is-collapsible' : ''}" ${collapsible ? 'data-action="toggle"' : ''}>
                        ${collapsible ? `
                            <svg class="field__group-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="${this.isCollapsed ? 'M9 5l7 7-7 7' : 'M19 9l-7 7-7-7'}"/>
                            </svg>
                        ` : ''}
                        <span class="field__group-title">${labelText}</span>
                    </div>
                ` : ''}
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                
                <div class="field__group-body">
                    ${fields.map(field => `
                        <div class="field__group-field" data-field="${field.id}"></div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    _bindEvents() {
        const header = this.container.querySelector('[data-action="toggle"]');
        
        header?.addEventListener('click', () => {
            this._toggle();
        });

        // Initialize child fields
        this._initChildFields();
    }

    /**
     * Initialize child fields
     * @private
     */
    _initChildFields() {
        const { fields = [] } = this.config;
        
        fields.forEach(fieldConfig => {
            const container = this.container.querySelector(`.field__group-field[data-field="${fieldConfig.id}"]`);
            if (!container) return;
            
            const fieldInstance = fieldRegistry.create(
                fieldConfig.type,
                fieldConfig,
                this.value?.[fieldConfig.id],
                (value) => this._onChildChange(fieldConfig.id, value),
                { language: this.language }
            );
            
            if (fieldInstance) {
                fieldInstance.render(container);
                this.childFields.set(fieldConfig.id, fieldInstance);
            }
        });
    }

    /**
     * Handle child field change
     * @param {string} fieldId - Field ID
     * @param {*} value - New value
     * @private
     */
    _onChildChange(fieldId, value) {
        this.value[fieldId] = value;
        this.onChange(this.value, this.config.id);
    }

    /**
     * Toggle collapsed state
     * @private
     */
    _toggle() {
        this.isCollapsed = !this.isCollapsed;
        this.container.querySelector('.field--group')?.classList.toggle('is-collapsed', this.isCollapsed);
        
        const arrow = this.container.querySelector('.field__group-arrow path');
        if (arrow) {
            arrow.setAttribute('d', this.isCollapsed ? 'M9 5l7 7-7 7' : 'M19 9l-7 7-7-7');
        }
    }

    /**
     * Expand group
     */
    expand() {
        if (this.isCollapsed) {
            this._toggle();
        }
    }

    /**
     * Collapse group
     */
    collapse() {
        if (!this.isCollapsed) {
            this._toggle();
        }
    }

    validate() {
        const { fields = [] } = this.config;
        
        // Validate all child fields
        for (const [fieldId, field] of this.childFields) {
            const validation = field.validate();
            if (!validation.valid) {
                // Expand group to show error
                this.expand();
                return validation;
            }
        }

        return { valid: true, error: null };
    }

    destroy() {
        this.childFields.forEach(field => field.destroy?.());
        this.childFields.clear();
        super.destroy();
    }

    _isEmpty() {
        return Object.keys(this.value).length === 0;
    }
}

export default GroupField;
