/**
 * TailwindPlus Customizer - Number Field
 * =======================================
 * Number input with optional slider
 * 
 * @module ui/fields/NumberField
 * @version 1.0.0
 */

import { BaseField } from '../../core/FieldRegistry.js';

export class NumberField extends BaseField {
    static get type() {
        return 'number';
    }

    static getDefaultValue() {
        return 0;
    }

    _template() {
        const { 
            id, label, hint, required, 
            min = 0, max = 100, step = 1, 
            slider = false, unit = '', 
            prefix = '', suffix = ''
        } = this.config;
        
        const labelText = this._getText(label);
        const hintText = this._getText(hint);
        const unitText = this._getText(unit);
        const value = this.value ?? min;

        return `
            <div class="field field--number ${slider ? 'field--slider' : ''}">
                ${label ? `
                    <label class="field__label" for="field-${id}">
                        ${labelText}
                        ${required ? '<span class="field__required">*</span>' : ''}
                    </label>
                ` : ''}
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                
                <div class="field__number-wrapper">
                    ${slider ? `
                        <input 
                            type="range" 
                            id="field-${id}-slider" 
                            class="field__slider"
                            min="${min}"
                            max="${max}"
                            step="${step}"
                            value="${value}"
                        >
                    ` : ''}
                    
                    <div class="field__number-input-wrapper">
                        ${prefix ? `<span class="field__number-prefix">${prefix}</span>` : ''}
                        <input 
                            type="number" 
                            id="field-${id}" 
                            class="field__input field__number-input"
                            min="${min}"
                            max="${max}"
                            step="${step}"
                            value="${value}"
                            ${required ? 'required' : ''}
                        >
                        ${unitText || suffix ? `<span class="field__number-suffix">${unitText || suffix}</span>` : ''}
                    </div>
                    
                    ${!slider ? `
                        <div class="field__number-buttons">
                            <button type="button" class="field__number-btn" data-action="increment">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                            <button type="button" class="field__number-btn" data-action="decrement">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                    ` : ''}
                </div>
                
                <span class="field__error"></span>
            </div>
        `;
    }

    _bindEvents() {
        const input = this.container.querySelector('.field__number-input');
        const slider = this.container.querySelector('.field__slider');
        const incrementBtn = this.container.querySelector('[data-action="increment"]');
        const decrementBtn = this.container.querySelector('[data-action="decrement"]');

        // Number input change
        input?.addEventListener('input', (e) => {
            const value = this._parseValue(e.target.value);
            this._setValue(value);
            
            if (slider) {
                slider.value = value;
            }
        });

        // Slider change
        slider?.addEventListener('input', (e) => {
            const value = this._parseValue(e.target.value);
            this._setValue(value);
            
            if (input) {
                input.value = value;
            }
        });

        // Increment button
        incrementBtn?.addEventListener('click', () => {
            const step = this.config.step || 1;
            const max = this.config.max ?? Infinity;
            const newValue = Math.min((this.value || 0) + step, max);
            this.setValue(newValue);
        });

        // Decrement button
        decrementBtn?.addEventListener('click', () => {
            const step = this.config.step || 1;
            const min = this.config.min ?? 0;
            const newValue = Math.max((this.value || 0) - step, min);
            this.setValue(newValue);
        });
    }

    /**
     * Parse and validate value
     * @param {string|number} value - Input value
     * @returns {number} Parsed value
     * @private
     */
    _parseValue(value) {
        const { min = 0, max = 100 } = this.config;
        let num = parseFloat(value);
        
        if (isNaN(num)) num = min;
        if (num < min) num = min;
        if (num > max) num = max;
        
        return num;
    }

    /**
     * Set value internally
     * @param {number} value - New value
     * @private
     */
    _setValue(value) {
        this.value = value;
        this.onChange(this.value, this.config.id);
    }

    _updateUI() {
        const input = this.container?.querySelector('.field__number-input');
        const slider = this.container?.querySelector('.field__slider');

        if (input) input.value = this.value ?? this.config.min ?? 0;
        if (slider) slider.value = this.value ?? this.config.min ?? 0;
    }

    validate() {
        const { required, min, max } = this.config;
        
        if (required && this._isEmpty()) {
            return {
                valid: false,
                error: this._getText({ ar: 'هذا الحقل مطلوب', en: 'This field is required' })
            };
        }

        if (min !== undefined && this.value < min) {
            return {
                valid: false,
                error: this._getText({ ar: `القيمة يجب أن تكون ${min} على الأقل`, en: `Value must be at least ${min}` })
            };
        }

        if (max !== undefined && this.value > max) {
            return {
                valid: false,
                error: this._getText({ ar: `القيمة يجب أن تكون ${max} كحد أقصى`, en: `Value must be at most ${max}` })
            };
        }

        return { valid: true, error: null };
    }

    _isEmpty() {
        return this.value === null || this.value === undefined || this.value === '';
    }
}

export default NumberField;
