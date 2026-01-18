/**
 * TailwindPlus Customizer - Rich Text Field
 * ==========================================
 * Rich text editor with basic formatting
 * 
 * @module ui/fields/RichTextField
 * @version 1.0.0
 */

import { BaseField } from '../../core/FieldRegistry.js';

export class RichTextField extends BaseField {
    static get type() {
        return 'richtext';
    }

    static getDefaultValue() {
        return '';
    }

    _template() {
        const { id, label, hint, required, placeholder, minHeight = 150 } = this.config;
        const labelText = this._getText(label);
        const hintText = this._getText(hint);
        const placeholderText = this._getText(placeholder) || '';

        return `
            <div class="field field--richtext">
                ${label ? `
                    <label class="field__label">
                        ${labelText}
                        ${required ? '<span class="field__required">*</span>' : ''}
                    </label>
                ` : ''}
                ${hintText ? `<p class="field__hint">${hintText}</p>` : ''}
                
                <div class="field__richtext-wrapper">
                    <!-- Toolbar -->
                    <div class="field__richtext-toolbar">
                        <button type="button" class="field__richtext-btn" data-command="bold" title="${this._getText({ ar: 'عريض', en: 'Bold' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/>
                            </svg>
                        </button>
                        <button type="button" class="field__richtext-btn" data-command="italic" title="${this._getText({ ar: 'مائل', en: 'Italic' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/>
                            </svg>
                        </button>
                        <button type="button" class="field__richtext-btn" data-command="underline" title="${this._getText({ ar: 'تسطير', en: 'Underline' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 3v7a6 6 0 006 6 6 6 0 006-6V3"/><line x1="4" y1="21" x2="20" y2="21"/>
                            </svg>
                        </button>
                        
                        <span class="field__richtext-divider"></span>
                        
                        <button type="button" class="field__richtext-btn" data-command="insertUnorderedList" title="${this._getText({ ar: 'قائمة', en: 'List' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                                <circle cx="3" cy="6" r="1" fill="currentColor"/><circle cx="3" cy="12" r="1" fill="currentColor"/><circle cx="3" cy="18" r="1" fill="currentColor"/>
                            </svg>
                        </button>
                        <button type="button" class="field__richtext-btn" data-command="insertOrderedList" title="${this._getText({ ar: 'قائمة مرقمة', en: 'Numbered list' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/>
                                <text x="2" y="8" font-size="8" fill="currentColor">1</text>
                                <text x="2" y="14" font-size="8" fill="currentColor">2</text>
                                <text x="2" y="20" font-size="8" fill="currentColor">3</text>
                            </svg>
                        </button>
                        
                        <span class="field__richtext-divider"></span>
                        
                        <button type="button" class="field__richtext-btn" data-command="justifyRight" title="${this._getText({ ar: 'محاذاة يمين', en: 'Align right' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="12" x2="9" y2="12"/><line x1="21" y1="18" x2="3" y2="18"/>
                            </svg>
                        </button>
                        <button type="button" class="field__richtext-btn" data-command="justifyCenter" title="${this._getText({ ar: 'توسيط', en: 'Center' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="6"/><line x1="21" y1="12" x2="3" y2="12"/><line x1="18" y1="18" x2="6" y2="18"/>
                            </svg>
                        </button>
                        <button type="button" class="field__richtext-btn" data-command="justifyLeft" title="${this._getText({ ar: 'محاذاة يسار', en: 'Align left' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                            </svg>
                        </button>
                        
                        <span class="field__richtext-divider"></span>
                        
                        <button type="button" class="field__richtext-btn" data-command="createLink" title="${this._getText({ ar: 'إضافة رابط', en: 'Add link' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                                <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
                            </svg>
                        </button>
                        <button type="button" class="field__richtext-btn" data-command="removeFormat" title="${this._getText({ ar: 'إزالة التنسيق', en: 'Clear formatting' })}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Editor -->
                    <div 
                        class="field__richtext-editor" 
                        id="field-${id}"
                        contenteditable="true"
                        data-placeholder="${placeholderText}"
                        style="min-height: ${minHeight}px"
                    >${this.value || ''}</div>
                </div>
                
                <span class="field__error"></span>
            </div>
        `;
    }

    _bindEvents() {
        const editor = this.container.querySelector('.field__richtext-editor');
        const buttons = this.container.querySelectorAll('.field__richtext-btn');

        // Toolbar buttons
        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const command = btn.dataset.command;
                
                if (command === 'createLink') {
                    const url = prompt(this._getText({ ar: 'أدخل الرابط:', en: 'Enter URL:' }));
                    if (url) {
                        document.execCommand(command, false, url);
                    }
                } else {
                    document.execCommand(command, false, null);
                }
                
                editor.focus();
                this._updateValue();
                this._updateToolbarState();
            });
        });

        // Editor input
        editor?.addEventListener('input', () => {
            this._updateValue();
        });

        // Update toolbar state on selection change
        editor?.addEventListener('keyup', () => {
            this._updateToolbarState();
        });

        editor?.addEventListener('mouseup', () => {
            this._updateToolbarState();
        });

        // Handle paste - clean HTML
        editor?.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = e.clipboardData.getData('text/plain');
            document.execCommand('insertText', false, text);
            this._updateValue();
        });
    }

    /**
     * Update internal value from editor
     * @private
     */
    _updateValue() {
        const editor = this.container?.querySelector('.field__richtext-editor');
        if (editor) {
            this.value = editor.innerHTML;
            this.onChange(this.value, this.config.id);
        }
    }

    /**
     * Update toolbar button states
     * @private
     */
    _updateToolbarState() {
        const buttons = this.container?.querySelectorAll('.field__richtext-btn');
        
        buttons?.forEach(btn => {
            const command = btn.dataset.command;
            if (['bold', 'italic', 'underline'].includes(command)) {
                const isActive = document.queryCommandState(command);
                btn.classList.toggle('is-active', isActive);
            }
        });
    }

    _updateUI() {
        const editor = this.container?.querySelector('.field__richtext-editor');
        if (editor && editor.innerHTML !== this.value) {
            editor.innerHTML = this.value || '';
        }
    }

    _isEmpty() {
        // Strip HTML tags to check if empty
        const text = this.value?.replace(/<[^>]*>/g, '').trim();
        return !text;
    }

    /**
     * Get plain text value
     * @returns {string} Plain text
     */
    getPlainText() {
        return this.value?.replace(/<[^>]*>/g, '') || '';
    }
}

export default RichTextField;
