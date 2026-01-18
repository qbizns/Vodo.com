/**
 * TailwindPlus Customizer - Repeater Field
 * =========================================
 * Dynamic list field for repeatable items
 * 
 * @module ui/fields/RepeaterField
 * @version 1.0.0
 */

import { BaseField, fieldRegistry } from '../../core/FieldRegistry.js';

export class RepeaterField extends BaseField {
    static get type() {
        return 'repeater';
    }

    static getDefaultValue() {
        return [];
    }

    constructor(options) {
        super(options);
        this.itemFields = new Map(); // Map<index, Map<fieldId, fieldInstance>>
        this.collapsedItems = new Set();
        
        // Ensure value is array
        if (!Array.isArray(this.value)) {
            this.value = [];
        }
    }

    _template() {
        const { id, label, hint, min = 0, max = Infinity, fields = [], itemLabel, collapsible = true } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);
        const canAdd = this.value.length < max;
        const canRemove = this.value.length > min;

        return `
            <div class="field field--repeater">
                ${label ? `
                    <div class="field__repeater-header">
                        <label class="field__label">${labelText}</label>
                        <span class="field__repeater-count">${this.value.length} ${this._getText({ ar: 'عنصر', en: 'items' })}</span>
                    </div>
                ` : ''}
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                
                <div class="field__repeater-items" id="repeater-${id}">
                    ${this.value.map((item, index) => this._renderItem(item, index, canRemove)).join('')}
                </div>
                
                ${canAdd ? `
                    <button type="button" class="field__repeater-add" data-action="add">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 4v16m8-8H4"/>
                        </svg>
                        ${this._getText({ ar: 'إضافة عنصر', en: 'Add item' })}
                    </button>
                ` : ''}
                
                <span class="field__error"></span>
            </div>
        `;
    }

    /**
     * Render single repeater item
     * @param {Object} item - Item data
     * @param {number} index - Item index
     * @param {boolean} canRemove - Can remove items
     * @returns {string} HTML string
     * @private
     */
    _renderItem(item, index, canRemove) {
        const { fields = [], itemLabel, collapsible = true } = this.config;
        const isCollapsed = this.collapsedItems.has(index);
        
        // Generate item title
        let title = this._getText(itemLabel) || `${this._getText({ ar: 'عنصر', en: 'Item' })} ${index + 1}`;
        if (typeof itemLabel === 'function') {
            title = itemLabel(item, index) || title;
        } else if (item && fields[0]) {
            // Use first field value as title if available
            const firstFieldValue = item[fields[0].id];
            if (firstFieldValue && typeof firstFieldValue === 'string') {
                title = firstFieldValue.substring(0, 30) + (firstFieldValue.length > 30 ? '...' : '');
            }
        }

        return `
            <div class="field__repeater-item ${isCollapsed ? 'is-collapsed' : ''}" data-index="${index}">
                <div class="field__repeater-item-header">
                    <button type="button" class="field__repeater-drag" title="${this._getText({ ar: 'اسحب لإعادة الترتيب', en: 'Drag to reorder' })}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 8h16M4 16h16"/>
                        </svg>
                    </button>
                    
                    ${collapsible ? `
                        <button type="button" class="field__repeater-toggle" data-action="toggle" data-index="${index}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="${isCollapsed ? 'M9 5l7 7-7 7' : 'M19 9l-7 7-7-7'}"/>
                            </svg>
                        </button>
                    ` : ''}
                    
                    <span class="field__repeater-item-title">${title}</span>
                    
                    <div class="field__repeater-item-actions">
                        <button type="button" class="field__repeater-action" data-action="duplicate" data-index="${index}" title="${this._getText({ ar: 'نسخ', en: 'Duplicate' })}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                            </svg>
                        </button>
                        ${canRemove ? `
                            <button type="button" class="field__repeater-action field__repeater-action--delete" data-action="remove" data-index="${index}" title="${this._getText({ ar: 'حذف', en: 'Remove' })}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                            </button>
                        ` : ''}
                    </div>
                </div>
                
                <div class="field__repeater-item-body">
                    ${fields.map(field => `
                        <div class="field__repeater-field" data-field="${field.id}" data-index="${index}"></div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    _bindEvents() {
        const addBtn = this.container.querySelector('[data-action="add"]');
        
        // Add item
        addBtn?.addEventListener('click', () => {
            this._addItem();
        });

        // Delegate other actions
        this.container.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]');
            if (!action) return;
            
            const actionType = action.dataset.action;
            const index = parseInt(action.dataset.index);
            
            switch (actionType) {
                case 'toggle':
                    this._toggleItem(index);
                    break;
                case 'remove':
                    this._removeItem(index);
                    break;
                case 'duplicate':
                    this._duplicateItem(index);
                    break;
            }
        });

        // Initialize item fields
        this._initItemFields();
    }

    /**
     * Initialize fields for all items
     * @private
     */
    _initItemFields() {
        const { fields = [] } = this.config;
        
        this.value.forEach((item, index) => {
            this._initFieldsForItem(index, item);
        });
    }

    /**
     * Initialize fields for a single item
     * @param {number} index - Item index
     * @param {Object} item - Item data
     * @private
     */
    _initFieldsForItem(index, item) {
        const { fields = [] } = this.config;
        const itemFieldsMap = new Map();
        
        fields.forEach(fieldConfig => {
            const container = this.container.querySelector(
                `.field__repeater-field[data-field="${fieldConfig.id}"][data-index="${index}"]`
            );
            
            if (!container) return;
            
            const fieldInstance = fieldRegistry.create(
                fieldConfig.type,
                fieldConfig,
                item?.[fieldConfig.id],
                (value) => this._onFieldChange(index, fieldConfig.id, value),
                { language: this.language }
            );
            
            if (fieldInstance) {
                fieldInstance.render(container);
                itemFieldsMap.set(fieldConfig.id, fieldInstance);
            }
        });
        
        this.itemFields.set(index, itemFieldsMap);
    }

    /**
     * Handle field value change
     * @param {number} index - Item index
     * @param {string} fieldId - Field ID
     * @param {*} value - New value
     * @private
     */
    _onFieldChange(index, fieldId, value) {
        if (!this.value[index]) {
            this.value[index] = {};
        }
        this.value[index][fieldId] = value;
        this.onChange(this.value, this.config.id);
        
        // Update item title if needed
        this._updateItemTitle(index);
    }

    /**
     * Update item title display
     * @param {number} index - Item index
     * @private
     */
    _updateItemTitle(index) {
        const item = this.container.querySelector(`.field__repeater-item[data-index="${index}"]`);
        const titleEl = item?.querySelector('.field__repeater-item-title');
        
        if (!titleEl || !this.value[index]) return;
        
        const { fields = [], itemLabel } = this.config;
        
        if (fields[0] && this.value[index][fields[0].id]) {
            const firstValue = this.value[index][fields[0].id];
            if (typeof firstValue === 'string') {
                titleEl.textContent = firstValue.substring(0, 30) + (firstValue.length > 30 ? '...' : '');
            }
        }
    }

    /**
     * Add new item
     * @private
     */
    _addItem() {
        const { fields = [], max = Infinity } = this.config;
        
        if (this.value.length >= max) return;
        
        // Create default item with field defaults
        const newItem = {};
        fields.forEach(field => {
            const FieldClass = fieldRegistry.get(field.type);
            if (FieldClass?.getDefaultValue) {
                newItem[field.id] = FieldClass.getDefaultValue();
            } else if (field.default !== undefined) {
                newItem[field.id] = field.default;
            }
        });
        
        this.value.push(newItem);
        this.onChange(this.value, this.config.id);
        this._rerender();
    }

    /**
     * Remove item
     * @param {number} index - Item index
     * @private
     */
    _removeItem(index) {
        const { min = 0 } = this.config;
        
        if (this.value.length <= min) return;
        
        // Destroy field instances
        const itemFieldsMap = this.itemFields.get(index);
        if (itemFieldsMap) {
            itemFieldsMap.forEach(field => field.destroy?.());
            this.itemFields.delete(index);
        }
        
        this.value.splice(index, 1);
        this.collapsedItems.delete(index);
        
        // Update collapsed indices
        const newCollapsed = new Set();
        this.collapsedItems.forEach(i => {
            if (i > index) newCollapsed.add(i - 1);
            else if (i < index) newCollapsed.add(i);
        });
        this.collapsedItems = newCollapsed;
        
        this.onChange(this.value, this.config.id);
        this._rerender();
    }

    /**
     * Duplicate item
     * @param {number} index - Item index
     * @private
     */
    _duplicateItem(index) {
        const { max = Infinity } = this.config;
        
        if (this.value.length >= max) return;
        
        const duplicate = JSON.parse(JSON.stringify(this.value[index]));
        this.value.splice(index + 1, 0, duplicate);
        
        this.onChange(this.value, this.config.id);
        this._rerender();
    }

    /**
     * Toggle item collapse
     * @param {number} index - Item index
     * @private
     */
    _toggleItem(index) {
        if (this.collapsedItems.has(index)) {
            this.collapsedItems.delete(index);
        } else {
            this.collapsedItems.add(index);
        }
        
        const item = this.container.querySelector(`.field__repeater-item[data-index="${index}"]`);
        item?.classList.toggle('is-collapsed', this.collapsedItems.has(index));
        
        const toggleBtn = item?.querySelector('[data-action="toggle"] svg path');
        if (toggleBtn) {
            toggleBtn.setAttribute('d', this.collapsedItems.has(index) ? 'M9 5l7 7-7 7' : 'M19 9l-7 7-7-7');
        }
    }

    /**
     * Re-render field
     * @private
     */
    _rerender() {
        // Cleanup old field instances
        this.itemFields.forEach(itemFieldsMap => {
            itemFieldsMap.forEach(field => field.destroy?.());
        });
        this.itemFields.clear();
        
        if (this.container) {
            this.render(this.container);
        }
    }

    validate() {
        const { required, min = 0, fields = [] } = this.config;
        
        if (required && this.value.length === 0) {
            return {
                valid: false,
                error: this._getText({ ar: 'يجب إضافة عنصر واحد على الأقل', en: 'At least one item is required' })
            };
        }

        if (this.value.length < min) {
            return {
                valid: false,
                error: this._getText({ ar: `يجب إضافة ${min} عناصر على الأقل`, en: `At least ${min} items required` })
            };
        }

        // Validate each item's fields
        for (let i = 0; i < this.value.length; i++) {
            const itemFieldsMap = this.itemFields.get(i);
            if (itemFieldsMap) {
                for (const [fieldId, field] of itemFieldsMap) {
                    const validation = field.validate();
                    if (!validation.valid) {
                        return {
                            valid: false,
                            error: `${this._getText({ ar: 'عنصر', en: 'Item' })} ${i + 1}: ${validation.error}`
                        };
                    }
                }
            }
        }

        return { valid: true, error: null };
    }

    destroy() {
        this.itemFields.forEach(itemFieldsMap => {
            itemFieldsMap.forEach(field => field.destroy?.());
        });
        this.itemFields.clear();
        super.destroy();
    }

    _isEmpty() {
        return this.value.length === 0;
    }
}

export default RepeaterField;
