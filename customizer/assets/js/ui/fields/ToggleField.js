/**
 * TailwindPlus Customizer - Toggle Field
 * =======================================
 * Toggle switch field for boolean values
 * 
 * @module ui/fields/ToggleField
 * @version 1.0.0
 */

import { BaseField } from '../../core/FieldRegistry.js';

export class ToggleField extends BaseField {
    static get type() {
        return 'toggle';
    }

    static getDefaultValue() {
        return false;
    }

    _template() {
        const { id, label, hint, description, onLabel, offLabel } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);
        const descriptionText = this._getText(description);
        const onText = this._getText(onLabel) || this._getText({ ar: 'تفعيل', en: 'On' });
        const offText = this._getText(offLabel) || this._getText({ ar: 'إيقاف', en: 'Off' });

        return `
            <div class="field field--toggle">
                <div class="field__toggle-wrapper">
                    <label class="field__toggle" for="field-${id}">
                        <input 
                            type="checkbox" 
                            id="field-${id}" 
                            class="field__toggle-input"
                            ${this.value ? 'checked' : ''}
                        >
                        <span class="field__toggle-track">
                            <span class="field__toggle-thumb"></span>
                        </span>
                    </label>
                    
                    <div class="field__toggle-content">
                        ${label ? `<span class="field__toggle-label">${labelText}</span>` : ''}
                        ${descriptionText ? `<span class="field__toggle-description">${descriptionText}</span>` : ''}
                    </div>
                </div>
                
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                <span class="field__error"></span>
            </div>
        `;
    }

    _bindEvents() {
        const input = this.container.querySelector('.field__toggle-input');
        if (!input) return;

        input.addEventListener('change', (e) => {
            this.value = e.target.checked;
            this.onChange(this.value, this.config.id);
        });
    }

    _updateUI() {
        const input = this.container?.querySelector('.field__toggle-input');
        if (input) {
            input.checked = !!this.value;
        }
    }

    _isEmpty() {
        return false; // Toggle is never "empty"
    }
}

export default ToggleField;
