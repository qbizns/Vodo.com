/**
 * TailwindPlus Customizer - Edit Panel
 * =====================================
 * Block editing panel with dynamic fields
 * 
 * @module ui/EditPanel
 * @version 1.1.0
 */

import { eventBus, Events } from '../core/EventBus.js';
import { fieldRegistry } from '../core/FieldRegistry.js';
import { getText } from '../utils/helpers.js';

export class EditPanel {
    constructor(options) {
        this.block = options.block;
        this.component = options.component;
        this.pageState = options.pageState;
        this.language = options.language || 'ar';
        this.onSave = options.onSave;
        this.onBack = options.onBack;
        
        this.container = null;
        this.fields = new Map();
        this.values = {};
        this.isDirty = false;
        
        // Copy existing values
        if (this.block.values) {
            const keys = Object.keys(this.block.values);
            for (let i = 0; i < keys.length; i++) {
                this.values[keys[i]] = this.block.values[keys[i]];
            }
        }
    }

    render(container) {
        this.container = container;
        
        const componentFields = this.component && this.component.fields ? this.component.fields : [];
        const saveText = getText({ ar: 'حفظ التغييرات', en: 'Save Changes' }, this.language);
        const emptyText = getText({ ar: 'لا توجد إعدادات قابلة للتعديل لهذا المكون', en: 'No editable settings for this component' }, this.language);
        
        let html = '<div class="edit-panel">';
        
        // Body - scrollable fields area
        html += '<div class="edit-panel__body custom-scrollbar">';
        
        if (componentFields.length > 0) {
            html += '<div class="edit-panel__fields" id="edit-fields"></div>';
        } else {
            html += '<div class="edit-panel__empty">';
            html += '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">';
            html += '<rect x="3" y="3" width="18" height="18" rx="2"/>';
            html += '<path d="M3 9h18M9 21V9"/>';
            html += '</svg>';
            html += '<p>' + emptyText + '</p>';
            html += '</div>';
        }
        
        html += '</div>';
        
        // Footer - sticky at bottom
        html += '<div class="edit-panel__footer">';
        html += '<button class="edit-panel__save" id="save-btn" disabled>';
        html += saveText;
        html += '</button>';
        html += '</div>';
        
        html += '</div>';
        
        container.innerHTML = html;
        
        this._initFields();
        this._bindEvents();
    }

    _initFields() {
        const componentFields = this.component && this.component.fields ? this.component.fields : [];
        const fieldsContainer = this.container ? this.container.querySelector('#edit-fields') : null;
        
        if (!fieldsContainer || componentFields.length === 0) return;

        const self = this;
        
        for (let i = 0; i < componentFields.length; i++) {
            const fieldConfig = componentFields[i];
            
            const fieldWrapper = document.createElement('div');
            fieldWrapper.className = 'edit-panel__field';
            fieldWrapper.dataset.fieldId = fieldConfig.id;
            fieldsContainer.appendChild(fieldWrapper);
            
            const currentValue = this.values[fieldConfig.id] !== undefined 
                ? this.values[fieldConfig.id] 
                : fieldConfig.default;
            
            const fieldInstance = fieldRegistry.create(
                fieldConfig.type,
                fieldConfig,
                currentValue,
                function(value) {
                    self._onFieldChange(fieldConfig.id, value);
                },
                { language: this.language }
            );
            
            if (fieldInstance) {
                fieldInstance.render(fieldWrapper);
                this.fields.set(fieldConfig.id, fieldInstance);
            } else {
                const label = getText(fieldConfig.label, this.language);
                let errorHtml = '<div class="field field--error">';
                errorHtml += '<span class="field__label">' + label + '</span>';
                errorHtml += '<p class="field__error is-visible">Unknown field type: ' + fieldConfig.type + '</p>';
                errorHtml += '</div>';
                fieldWrapper.innerHTML = errorHtml;
            }
        }
    }

    _onFieldChange(fieldId, value) {
        this.values[fieldId] = value;
        this.isDirty = true;
        
        const saveBtn = this.container ? this.container.querySelector('#save-btn') : null;
        if (saveBtn) {
            saveBtn.disabled = false;
        }
        
        this._updatePreview();
        
        eventBus.emit('edit-panel:change', {
            blockId: this.block.id,
            fieldId: fieldId,
            value: value,
            values: this.values
        });
    }

    _updatePreview() {
        eventBus.emit('edit-panel:preview-update', {
            blockId: this.block.id,
            values: this.values
        });
    }

    _bindEvents() {
        const saveBtn = this.container ? this.container.querySelector('#save-btn') : null;
        const self = this;
        
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                self._save();
            });
        }

        this._keyHandler = function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (self.isDirty) {
                    self._save();
                }
            }
        };
        document.addEventListener('keydown', this._keyHandler);
    }

    _save() {
        let isValid = true;
        let firstError = null;
        const self = this;

        this.fields.forEach(function(field, fieldId) {
            const validation = field.validate();
            if (!validation.valid) {
                field.showError(validation.error);
                isValid = false;
                if (!firstError) {
                    firstError = fieldId;
                }
            } else {
                field.clearError();
            }
        });

        if (!isValid) {
            const errorField = this.container ? this.container.querySelector('[data-field-id="' + firstError + '"]') : null;
            if (errorField) {
                errorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Update block values
        this.block.values = {};
        const keys = Object.keys(this.values);
        for (let i = 0; i < keys.length; i++) {
            this.block.values[keys[i]] = this.values[keys[i]];
        }
        
        this._applyValuesToHtml();
        
        this.pageState.updateBlock(this.block.id, {
            values: this.values,
            html: this.block.html
        });

        this.isDirty = false;
        
        const saveBtn = this.container ? this.container.querySelector('#save-btn') : null;
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = getText({ ar: 'تم الحفظ ✓', en: 'Saved ✓' }, this.language);
            
            setTimeout(function() {
                saveBtn.textContent = getText({ ar: 'حفظ التغييرات', en: 'Save Changes' }, self.language);
            }, 2000);
        }

        if (this.onSave) {
            this.onSave(this.values, this.block);
        }

        eventBus.emit('edit-panel:saved', {
            blockId: this.block.id,
            values: this.values
        });
    }

    _applyValuesToHtml() {
        const componentFields = this.component && this.component.fields ? this.component.fields : [];
        let html = this.component && this.component.html ? this.component.html : this.block.html;
        
        const temp = document.createElement('div');
        temp.innerHTML = html;
        
        const self = this;

        for (let i = 0; i < componentFields.length; i++) {
            const fieldConfig = componentFields[i];
            const value = this.values[fieldConfig.id];
            if (value === undefined) continue;

            const selector = fieldConfig.selector;
            const attribute = fieldConfig.attribute || 'textContent';
            
            if (selector) {
                const elements = temp.querySelectorAll(selector);
                for (let j = 0; j < elements.length; j++) {
                    this._applyValueToElement(elements[j], attribute, value, fieldConfig);
                }
            }
        }

        this.block.html = temp.innerHTML;
    }

    _applyValueToElement(element, attribute, value, fieldConfig) {
        switch (attribute) {
            case 'textContent':
                element.textContent = value;
                break;
            case 'innerHTML':
                element.innerHTML = value;
                break;
            case 'src':
            case 'href':
                if (fieldConfig.type === 'link' && typeof value === 'object') {
                    element.setAttribute(attribute, this._buildLinkUrl(value));
                    if (value.target) {
                        element.setAttribute('target', value.target);
                    }
                } else {
                    element.setAttribute(attribute, value);
                }
                break;
            case 'style':
                if (typeof value === 'object') {
                    const styleKeys = Object.keys(value);
                    for (let i = 0; i < styleKeys.length; i++) {
                        element.style[styleKeys[i]] = value[styleKeys[i]];
                    }
                } else {
                    element.setAttribute('style', value);
                }
                break;
            case 'class':
                if (Array.isArray(value)) {
                    element.className = value.join(' ');
                } else {
                    element.className = value;
                }
                break;
            case 'hidden':
                element.hidden = !value;
                break;
            default:
                element.setAttribute(attribute, value);
        }
    }

    _buildLinkUrl(linkValue) {
        const type = linkValue.type;
        const url = linkValue.url;
        
        if (!url) return '#';
        
        switch (type) {
            case 'email':
                return 'mailto:' + url;
            case 'phone':
                return 'tel:' + url;
            case 'external':
                return url.indexOf('http') === 0 ? url : 'https://' + url;
            default:
                return url;
        }
    }

    hasUnsavedChanges() {
        return this.isDirty;
    }

    getValues() {
        const result = {};
        const keys = Object.keys(this.values);
        for (let i = 0; i < keys.length; i++) {
            result[keys[i]] = this.values[keys[i]];
        }
        return result;
    }

    destroy() {
        if (this._keyHandler) {
            document.removeEventListener('keydown', this._keyHandler);
        }

        this.fields.forEach(function(field) {
            if (field.destroy) {
                field.destroy();
            }
        });
        this.fields.clear();
        
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

export default EditPanel;
