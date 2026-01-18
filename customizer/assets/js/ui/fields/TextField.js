/**
 * TailwindPlus Customizer - Text Field
 * =====================================
 * Text input field (single line and multiline)
 * 
 * @module ui/fields/TextField
 * @version 1.0.0
 */

import { BaseField } from '../../core/FieldRegistry.js';

export class TextField extends BaseField {
    static get type() {
        return 'text';
    }

    static getDefaultValue() {
        return '';
    }

    _template() {
        const { id, label, hint, placeholder, required, maxLength, multiline, rows = 3 } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);
        const placeholderText = this._getText(placeholder) || '';

        const inputEl = multiline
            ? `<textarea 
                    id="field-${id}" 
                    class="field__textarea"
                    placeholder="${placeholderText}"
                    rows="${rows}"
                    ${maxLength ? `maxlength="${maxLength}"` : ''}
                    ${required ? 'required' : ''}
                >${this.value || ''}</textarea>`
            : `<input 
                    type="text" 
                    id="field-${id}" 
                    class="field__input"
                    placeholder="${placeholderText}"
                    value="${this.value || ''}"
                    ${maxLength ? `maxlength="${maxLength}"` : ''}
                    ${required ? 'required' : ''}
                >`;

        return `
            <div class="field field--text ${multiline ? 'field--textarea' : ''}">
                ${label ? `
                    <label class="field__label" for="field-${id}">
                        ${labelText}
                        ${required ? '<span class="field__required">*</span>' : ''}
                    </label>
                ` : ''}
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                <div class="field__control">
                    ${inputEl}
                    ${maxLength ? `<span class="field__counter"><span class="field__counter-current">${(this.value || '').length}</span>/${maxLength}</span>` : ''}
                </div>
                <span class="field__error"></span>
            </div>
        `;
    }

    _bindEvents() {
        const input = this.container.querySelector('.field__input, .field__textarea');
        if (!input) return;

        input.addEventListener('input', (e) => {
            this.value = e.target.value;
            this.onChange(this.value, this.config.id);
            this._updateCounter();
        });

        input.addEventListener('blur', () => {
            this.clearError();
            const validation = this.validate();
            if (!validation.valid) {
                this.showError(validation.error);
            }
        });
    }

    _updateUI() {
        const input = this.container?.querySelector('.field__input, .field__textarea');
        if (input && input.value !== this.value) {
            input.value = this.value || '';
            this._updateCounter();
        }
    }

    _updateCounter() {
        const counter = this.container?.querySelector('.field__counter-current');
        if (counter) {
            counter.textContent = (this.value || '').length;
        }
    }

    _isEmpty() {
        return !this.value || this.value.trim() === '';
    }
}

/**
 * Textarea field - alias for multiline text
 */
export class TextareaField extends TextField {
    static get type() {
        return 'textarea';
    }

    constructor(options) {
        super(options);
        this.config.multiline = true;
    }
}

export default TextField;
