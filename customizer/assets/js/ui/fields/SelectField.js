/**
 * TailwindPlus Customizer - Select Field
 * =======================================
 * Dropdown select field
 * 
 * @module ui/fields/SelectField
 * @version 1.0.0
 */

import { BaseField } from '../../core/FieldRegistry.js';

export class SelectField extends BaseField {
    static get type() {
        return 'select';
    }

    static getDefaultValue() {
        return '';
    }

    _template() {
        const { id, label, hint, required, options = [], placeholder, multiple, searchable } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);
        const placeholderText = this._getText(placeholder) || this._getText({ ar: 'اختر...', en: 'Select...' });

        const optionsHtml = options.map(opt => {
            const optValue = typeof opt === 'object' ? opt.value : opt;
            const optLabel = typeof opt === 'object' ? this._getText(opt.label) : opt;
            const isSelected = multiple 
                ? (Array.isArray(this.value) && this.value.includes(optValue))
                : this.value === optValue;
            
            return `<option value="${optValue}" ${isSelected ? 'selected' : ''}>${optLabel}</option>`;
        }).join('');

        return `
            <div class="field field--select ${searchable ? 'field--searchable' : ''}">
                ${label ? `
                    <label class="field__label" for="field-${id}">
                        ${labelText}
                        ${required ? '<span class="field__required">*</span>' : ''}
                    </label>
                ` : ''}
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                
                <div class="field__select-wrapper">
                    <select 
                        id="field-${id}" 
                        class="field__select"
                        ${multiple ? 'multiple' : ''}
                        ${required ? 'required' : ''}
                    >
                        ${!multiple && !this.value ? `<option value="" disabled selected>${placeholderText}</option>` : ''}
                        ${optionsHtml}
                    </select>
                    <svg class="field__select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </div>
                
                <span class="field__error"></span>
            </div>
        `;
    }

    _bindEvents() {
        const select = this.container.querySelector('.field__select');
        if (!select) return;

        select.addEventListener('change', (e) => {
            if (this.config.multiple) {
                this.value = Array.from(e.target.selectedOptions).map(opt => opt.value);
            } else {
                this.value = e.target.value;
            }
            this.onChange(this.value, this.config.id);
        });
    }

    _updateUI() {
        const select = this.container?.querySelector('.field__select');
        if (!select) return;

        if (this.config.multiple) {
            Array.from(select.options).forEach(opt => {
                opt.selected = Array.isArray(this.value) && this.value.includes(opt.value);
            });
        } else {
            select.value = this.value || '';
        }
    }

    _isEmpty() {
        if (this.config.multiple) {
            return !Array.isArray(this.value) || this.value.length === 0;
        }
        return !this.value;
    }
}

export default SelectField;
