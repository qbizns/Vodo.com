/**
 * TailwindPlus Customizer - Image Field
 * ======================================
 * Image upload field with preview
 * 
 * @module ui/fields/ImageField
 * @version 1.0.0
 */

import { BaseField } from '../../core/FieldRegistry.js';

export class ImageField extends BaseField {
    static get type() {
        return 'image';
    }

    static getDefaultValue() {
        return '';
    }

    _template() {
        const { id, label, hint, required, accept = 'image/*', maxSize, dimensions } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);
        const hasImage = !!this.value;

        let dimensionHint = hintText;
        if (!dimensionHint && dimensions) {
            dimensionHint = this._getText({ 
                ar: `المقاس المناسب للصورة هو ${dimensions.width}×${dimensions.height} بكسل`, 
                en: `Recommended size is ${dimensions.width}×${dimensions.height}px` 
            });
        }

        const dimensionDisplay = dimensions ? `${dimensions.width}x${dimensions.height}` : '';

        return `
            <div class="field field--image">
                ${label ? `
                    <label class="field__label">
                        ${labelText}
                        ${required ? '<span class="field__required">*</span>' : ''}
                    </label>
                ` : ''}
                ${dimensionHint ? `<p class="field__hint">${dimensionHint}</p>` : ''}
                
                <div class="field__image-wrapper">
                    <!-- Preview Box -->
                    <div class="field__image-box">
                        <div class="field__image-preview ${hasImage ? 'has-image' : ''}">
                            ${hasImage 
                                ? `
                                    <img src="${this.value}" alt="" class="field__image-img">
                                    <button type="button" class="field__image-remove-btn" data-action="remove" title="${this._getText({ ar: 'حذف', en: 'Remove' })}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                                `
                                : `
                                    <div class="field__image-placeholder">
                                        <svg class="field__image-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <path d="M21 15l-5-5L5 21"/>
                                        </svg>
                                        ${dimensionDisplay ? `<span class="field__image-dimensions">${dimensionDisplay}</span>` : ''}
                                    </div>
                                `
                            }
                        </div>
                        <input 
                            type="file" 
                            id="field-${id}" 
                            class="field__image-input"
                            accept="${accept}"
                            ${required && !hasImage ? 'required' : ''}
                        >
                    </div>
                </div>
                
                <span class="field__error"></span>
            </div>
        `;
    }

    _bindEvents() {
        const fileInput = this.container.querySelector('.field__image-input');
        const removeBtn = this.container.querySelector('[data-action="remove"]');
        const previewBox = this.container.querySelector('.field__image-box');

        // File upload
        fileInput?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                this._handleFile(file);
            }
        });

        // Click on preview box to upload
        previewBox?.addEventListener('click', (e) => {
            if (!e.target.closest('[data-action="remove"]')) {
                fileInput?.click();
            }
        });

        // Remove button
        removeBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.setValue('');
            this._rerender();
        });

        // Drag and drop
        const preview = this.container.querySelector('.field__image-preview');
        if (preview) {
            preview.addEventListener('dragover', (e) => {
                e.preventDefault();
                preview.classList.add('is-dragover');
            });

            preview.addEventListener('dragleave', () => {
                preview.classList.remove('is-dragover');
            });

            preview.addEventListener('drop', (e) => {
                e.preventDefault();
                preview.classList.remove('is-dragover');
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    this._handleFile(file);
                }
            });
        }
    }

    /**
     * Handle file upload
     * @param {File} file - File object
     * @private
     */
    _handleFile(file) {
        const { maxSize } = this.config;

        // Check file size
        if (maxSize && file.size > maxSize) {
            const maxMB = (maxSize / 1024 / 1024).toFixed(1);
            this.showError(this._getText({ 
                ar: `حجم الملف يجب أن يكون أقل من ${maxMB} ميجابايت`, 
                en: `File size must be less than ${maxMB}MB` 
            }));
            return;
        }

        // Read file as base64
        const reader = new FileReader();
        reader.onload = (e) => {
            this.setValue(e.target.result);
            this._rerender();
        };
        reader.readAsDataURL(file);
    }

    /**
     * Validate and set URL
     * @param {string} url - Image URL
     * @private
     */
    _validateAndSetUrl(url) {
        // Basic URL validation
        try {
            new URL(url);
            this.setValue(url);
            this._rerender();
        } catch {
            this.showError(this._getText({ ar: 'رابط غير صالح', en: 'Invalid URL' }));
        }
    }

    /**
     * Re-render the field
     * @private
     */
    _rerender() {
        if (this.container) {
            this.render(this.container);
        }
    }

    _updateUI() {
        const preview = this.container?.querySelector('.field__image-preview');
        const img = preview?.querySelector('.field__image-img');
        
        if (this.value) {
            if (img) {
                img.src = this.value;
            }
            preview?.classList.add('has-image');
        } else {
            preview?.classList.remove('has-image');
        }
    }

    _isEmpty() {
        return !this.value;
    }
}

export default ImageField;
