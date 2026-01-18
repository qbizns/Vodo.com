/**
 * TailwindPlus Customizer - Color Field
 * ======================================
 * Color picker field with presets
 * 
 * @module ui/fields/ColorField
 * @version 1.0.0
 */

import { BaseField } from '../../core/FieldRegistry.js';

export class ColorField extends BaseField {
    static get type() {
        return 'color';
    }

    static getDefaultValue() {
        return '#000000';
    }

    _template() {
        const { id, label, hint, required, presets, allowCustom = true, allowTransparent = false } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);

        // Default color presets
        const defaultPresets = [
            '#000000', '#FFFFFF', '#F87171', '#FB923C', '#FBBF24', '#A3E635',
            '#34D399', '#22D3EE', '#60A5FA', '#A78BFA', '#F472B6', '#9CA3AF'
        ];

        const colorPresets = presets || defaultPresets;
        const isTransparent = this.value === 'transparent';

        return `
            <div class="field field--color">
                ${label ? `
                    <label class="field__label">
                        ${labelText}
                        ${required ? '<span class="field__required">*</span>' : ''}
                    </label>
                ` : ''}
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                
                <div class="field__color-wrapper">
                    <!-- Current Color Display -->
                    <div class="field__color-current">
                        <span 
                            class="field__color-swatch ${isTransparent ? 'is-transparent' : ''}" 
                            style="background-color: ${isTransparent ? 'transparent' : this.value}"
                        ></span>
                        <span class="field__color-value">${this.value || ''}</span>
                    </div>
                    
                    <!-- Presets -->
                    <div class="field__color-presets">
                        ${allowTransparent ? `
                            <button 
                                type="button" 
                                class="field__color-preset is-transparent ${isTransparent ? 'is-active' : ''}"
                                data-color="transparent"
                                title="${this._getText({ ar: 'شفاف', en: 'Transparent' })}"
                            ></button>
                        ` : ''}
                        ${colorPresets.map(color => `
                            <button 
                                type="button" 
                                class="field__color-preset ${this.value === color ? 'is-active' : ''}"
                                style="background-color: ${color}"
                                data-color="${color}"
                                title="${color}"
                            ></button>
                        `).join('')}
                    </div>
                    
                    <!-- Custom Color Input -->
                    ${allowCustom ? `
                        <div class="field__color-custom">
                            <input 
                                type="color" 
                                id="field-${id}-picker" 
                                class="field__color-picker"
                                value="${isTransparent ? '#000000' : this.value}"
                            >
                            <label for="field-${id}-picker" class="field__color-custom-label">
                                ${this._getText({ ar: 'لون مخصص', en: 'Custom color' })}
                            </label>
                            <input 
                                type="text" 
                                id="field-${id}-hex" 
                                class="field__input field__color-hex"
                                value="${this.value || ''}"
                                placeholder="#RRGGBB"
                                pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^transparent$"
                            >
                        </div>
                    ` : ''}
                </div>
                
                <span class="field__error"></span>
            </div>
        `;
    }

    _bindEvents() {
        const presets = this.container.querySelectorAll('.field__color-preset');
        const picker = this.container.querySelector('.field__color-picker');
        const hexInput = this.container.querySelector('.field__color-hex');

        // Preset clicks
        presets.forEach(preset => {
            preset.addEventListener('click', () => {
                this.setValue(preset.dataset.color);
                this._updateActivePreset();
            });
        });

        // Color picker change
        picker?.addEventListener('input', (e) => {
            this.setValue(e.target.value);
            this._updateActivePreset();
        });

        // Hex input change
        hexInput?.addEventListener('change', (e) => {
            let value = e.target.value.trim();
            
            // Add # if missing
            if (value && !value.startsWith('#') && value !== 'transparent') {
                value = '#' + value;
            }
            
            // Validate hex color
            if (this._isValidColor(value)) {
                this.setValue(value);
                this._updateActivePreset();
            } else {
                this.showError(this._getText({ ar: 'لون غير صالح', en: 'Invalid color' }));
            }
        });
    }

    /**
     * Validate color value
     * @param {string} color - Color value
     * @returns {boolean} Is valid
     * @private
     */
    _isValidColor(color) {
        if (color === 'transparent') return true;
        return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color);
    }

    /**
     * Update active preset indicator
     * @private
     */
    _updateActivePreset() {
        const presets = this.container?.querySelectorAll('.field__color-preset');
        presets?.forEach(preset => {
            preset.classList.toggle('is-active', preset.dataset.color === this.value);
        });
    }

    _updateUI() {
        const swatch = this.container?.querySelector('.field__color-swatch');
        const valueDisplay = this.container?.querySelector('.field__color-value');
        const picker = this.container?.querySelector('.field__color-picker');
        const hexInput = this.container?.querySelector('.field__color-hex');

        if (swatch) {
            const isTransparent = this.value === 'transparent';
            swatch.style.backgroundColor = isTransparent ? 'transparent' : this.value;
            swatch.classList.toggle('is-transparent', isTransparent);
        }

        if (valueDisplay) valueDisplay.textContent = this.value;
        if (picker && this.value !== 'transparent') picker.value = this.value;
        if (hexInput) hexInput.value = this.value;

        this._updateActivePreset();
    }

    _isEmpty() {
        return !this.value;
    }
}

export default ColorField;
