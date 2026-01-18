/**
 * TailwindPlus Customizer - Preview UI Component
 * ===============================================
 * Live preview iframe with device toggle and block toolbar
 * 
 * @module ui/Preview
 * @version 1.2.0
 */

import { eventBus, Events } from '../core/EventBus.js';
import { icon } from '../utils/icons.js';
import { getText } from '../utils/helpers.js';

export class Preview {
    /**
     * @param {Object} options - Preview options
     * @param {HTMLElement} options.container - Container element
     * @param {Object} options.pageState - Page state manager instance
     * @param {string} options.language - Current language
     */
    constructor(options) {
        this.container = options.container;
        this.pageState = options.pageState;
        this.language = options.language || 'ar';
        this.device = 'desktop';
        this.iframe = null;
        this.iframeDoc = null;
        this.iframeReady = false;
        this._updateTimeout = null;

        this._bindEvents();
        this.render();
    }

    /**
     * Render preview area
     */
    render() {
        this.container.innerHTML = `
            <div class="preview">
                <!-- Topbar -->
                <div class="preview__topbar">
                    <div class="preview__topbar-left">
                        <button class="preview__more-btn">
                            ${icon('more', { size: 20 })}
                        </button>
                        <div class="preview__status">
                            <span class="preview__status-dot"></span>
                            <span>${getText({ ar: 'مُفعّل', en: 'Active' }, this.language)}</span>
                        </div>
                    </div>
                    <div class="preview__topbar-right">
                        <button 
                            class="preview__device-btn ${this.device === 'mobile' ? 'is-active' : ''}" 
                            data-device="mobile"
                            title="${getText({ ar: 'عرض الجوال', en: 'Mobile view' }, this.language)}"
                        >
                            ${icon('mobile', { size: 20 })}
                        </button>
                        <button 
                            class="preview__device-btn ${this.device === 'desktop' ? 'is-active' : ''}" 
                            data-device="desktop"
                            title="${getText({ ar: 'عرض سطح المكتب', en: 'Desktop view' }, this.language)}"
                        >
                            ${icon('desktop', { size: 20 })}
                        </button>
                    </div>
                </div>
                
                <!-- Body -->
                <div class="preview__body">
                    <div class="preview__container preview__container--${this.device}" id="preview-container">
                        <iframe 
                            class="preview__iframe" 
                            id="preview-frame" 
                            title="${getText({ ar: 'معاينة الصفحة', en: 'Page preview' }, this.language)}"
                        ></iframe>
                    </div>
                </div>
            </div>
        `;

        this.iframe = this.container.querySelector('#preview-frame');
        this._initIframe();
    }

    /**
     * Initialize iframe with base document
     * @private
     */
    _initIframe() {
        if (!this.iframe) return;

        const doc = this.iframe.contentDocument || this.iframe.contentWindow.document;
        doc.open();
        doc.write(this._generateBaseDocument());
        doc.close();

        this.iframeDoc = doc;
        
        // Wait for iframe to be ready
        this.iframe.onload = () => {
            this.iframeReady = true;
            this.iframeDoc = this.iframe.contentDocument || this.iframe.contentWindow.document;
            this._updateContent();
        };

        // Fallback if onload doesn't fire
        setTimeout(() => {
            if (!this.iframeReady) {
                this.iframeReady = true;
                this.iframeDoc = this.iframe.contentDocument || this.iframe.contentWindow.document;
                this._updateContent();
            }
        }, 500);
    }

    /**
     * Update preview content (debounced, incremental)
     */
    update() {
        // Debounce updates
        if (this._updateTimeout) {
            clearTimeout(this._updateTimeout);
        }
        
        this._updateTimeout = setTimeout(() => {
            this._updateContent();
        }, 50);
    }

    /**
     * Update content inside iframe (no full reload)
     * @private
     */
    _updateContent() {
        if (!this.iframeDoc || !this.iframeReady) return;

        const blocks = this.pageState.getVisibleBlocks();
        const selectedId = this.pageState.getSelectedBlockId();
        const container = this.iframeDoc.getElementById('preview-content');
        
        if (!container) return;

        if (blocks.length === 0) {
            container.innerHTML = this._renderEmptyState();
            return;
        }

        // Build new content
        const newContent = blocks.map(block => this._wrapBlock(block, selectedId)).join('');
        
        // Update with fade transition
        container.style.opacity = '0.7';
        
        requestAnimationFrame(() => {
            container.innerHTML = newContent;
            container.style.opacity = '1';
            
            // Update toolbar position if block is selected
            if (selectedId) {
                this._updateToolbarPosition(selectedId);
            }
        });
    }

    /**
     * Update only selection state (very fast, no content change)
     * @param {string|null} blockId - Selected block ID
     * @private
     */
    _updateSelection(blockId) {
        if (!this.iframeDoc || !this.iframeReady) return;

        const blocks = this.iframeDoc.querySelectorAll('.preview-block');
        const toolbar = this.iframeDoc.getElementById('block-toolbar');
        const nameLabel = this.iframeDoc.getElementById('block-name-label');
        
        blocks.forEach(block => {
            const isSelected = block.dataset.blockId === blockId;
            block.classList.toggle('is-selected', isSelected);
        });

        if (blockId) {
            const block = this.pageState.getBlock(blockId);
            if (block && nameLabel) {
                nameLabel.textContent = getText(block.name, this.language);
                nameLabel.classList.add('is-visible');
            }
            if (toolbar) {
                toolbar.classList.add('is-visible');
                this._updateToolbarPosition(blockId);
            }
        } else {
            if (toolbar) toolbar.classList.remove('is-visible');
            if (nameLabel) nameLabel.classList.remove('is-visible');
        }
    }

    /**
     * Update toolbar position based on selected block
     * @param {string} blockId - Block ID
     * @private
     */
    _updateToolbarPosition(blockId) {
        if (!this.iframeDoc) return;

        const blockEl = this.iframeDoc.querySelector(`[data-block-id="${blockId}"]`);
        const toolbar = this.iframeDoc.getElementById('block-toolbar');
        const nameLabel = this.iframeDoc.getElementById('block-name-label');
        
        if (!blockEl || !toolbar) return;

        const rect = blockEl.getBoundingClientRect();
        const scrollTop = this.iframeDoc.documentElement.scrollTop || this.iframeDoc.body.scrollTop;
        
        // Position toolbar at bottom center of block
        toolbar.style.top = `${rect.bottom + scrollTop - 20}px`;
        toolbar.style.left = '50%';
        toolbar.style.transform = 'translateX(-50%)';

        // Position name label at top right of block
        if (nameLabel) {
            nameLabel.style.top = `${rect.top + scrollTop + 8}px`;
            nameLabel.style.right = '8px';
        }
    }

    /**
     * Wrap block with interactive container
     * @param {Object} block - Block data
     * @param {string} selectedId - Selected block ID
     * @returns {string} HTML string
     * @private
     */
    _wrapBlock(block, selectedId) {
        const isSelected = block.id === selectedId;
        return `
            <div 
                class="preview-block ${isSelected ? 'is-selected' : ''}" 
                data-block-id="${block.id}"
            >
                ${block.html}
            </div>
        `;
    }

    /**
     * Render empty state
     * @returns {string} HTML string
     * @private
     */
    _renderEmptyState() {
        return `
            <div class="preview__empty">
                <svg class="preview__empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/>
                    <path d="M3 9h18M9 21V9" stroke-width="1.5"/>
                </svg>
                <p class="preview__empty-title">
                    ${getText({ ar: 'ابدأ بإضافة المكونات', en: 'Start adding components' }, this.language)}
                </p>
                <p class="preview__empty-text">
                    ${getText({ ar: 'اضغط على "إضافة عنصر جديد" لبناء صفحتك', en: 'Click "Add New Element" to build your page' }, this.language)}
                </p>
            </div>
        `;
    }

    /**
     * Generate base HTML document for iframe (loaded once)
     * @returns {string} Full HTML document
     * @private
     */
    _generateBaseDocument() {
        const direction = this.language === 'ar' ? 'rtl' : 'ltr';
        
        return `<!DOCTYPE html>
<html lang="${this.language}" dir="${direction}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"><\/script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif']
                    },
                    colors: {
                        'brand': {
                            'primary': '#004D5A',
                            'teal': '#009688',
                            'mint': '#B2DFDB',
                        }
                    }
                }
            }
        }
    <\/script>
    <style>
        * { 
            font-family: 'Cairo', sans-serif; 
            box-sizing: border-box;
        }
        body { 
            margin: 0; 
            padding: 0; 
            background: #F9FAFB; 
            min-height: 100vh;
        }
        #preview-content {
            transition: opacity 0.15s ease;
            min-height: 100vh;
        }
        .preview-block { 
            position: relative; 
            transition: outline 0.1s ease, box-shadow 0.1s ease;
        }
        .preview-block:hover { 
            outline: 2px dashed #009688; 
            outline-offset: -2px;
            cursor: pointer;
        }
        .preview-block.is-selected { 
            outline: 2px solid #009688; 
            outline-offset: -2px;
            box-shadow: inset 0 0 0 2000px rgba(0, 150, 136, 0.03);
        }
        
        /* Block Name Label */
        .block-name-label {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #009688;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
            white-space: nowrap;
        }
        .block-name-label.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Block Toolbar */
        .block-toolbar {
            position: absolute;
            display: flex;
            align-items: center;
            gap: 2px;
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1001;
            opacity: 0;
            transform: translateX(-50%) translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
        }
        .block-toolbar.is-visible {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            pointer-events: auto;
        }
        .block-toolbar__btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            color: #6B7280;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .block-toolbar__btn:hover {
            background: #F3F4F6;
            color: #111827;
        }
        .block-toolbar__btn--delete {
            color: #EF4444;
        }
        .block-toolbar__btn--delete:hover {
            background: #FEE2E2;
            color: #DC2626;
        }
        .block-toolbar__btn svg {
            width: 18px;
            height: 18px;
        }
        .block-toolbar__divider {
            width: 1px;
            height: 20px;
            background: #E5E7EB;
            margin: 0 4px;
        }
        
        /* Empty State */
        .preview__empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            text-align: center;
            color: #9CA3AF;
        }
        .preview__empty-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .preview__empty-title {
            font-size: 1.25rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .preview__empty-text {
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Block Name Label -->
    <div class="block-name-label" id="block-name-label"></div>
    
    <!-- Block Toolbar -->
    <div class="block-toolbar" id="block-toolbar">
        <button class="block-toolbar__btn block-toolbar__btn--delete" data-action="delete" title="${getText({ ar: 'حذف', en: 'Delete' }, this.language)}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
        <button class="block-toolbar__btn" data-action="add-below" title="${getText({ ar: 'إضافة أسفل', en: 'Add below' }, this.language)}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>
        <button class="block-toolbar__btn" data-action="duplicate" title="${getText({ ar: 'نسخ', en: 'Duplicate' }, this.language)}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        </button>
        <div class="block-toolbar__divider"></div>
        <button class="block-toolbar__btn" data-action="move-down" title="${getText({ ar: 'تحريك للأسفل', en: 'Move down' }, this.language)}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <button class="block-toolbar__btn" data-action="move-up" title="${getText({ ar: 'تحريك للأعلى', en: 'Move up' }, this.language)}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
        </button>
        <div class="block-toolbar__divider"></div>
        <button class="block-toolbar__btn" data-action="toggle-visibility" title="${getText({ ar: 'إخفاء/إظهار', en: 'Hide/Show' }, this.language)}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
        </button>
        <button class="block-toolbar__btn" data-action="edit" title="${getText({ ar: 'تعديل', en: 'Edit' }, this.language)}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </button>
    </div>
    
    <!-- Content Container -->
    <div id="preview-content"></div>
    
    <script>
        let selectedBlockId = null;
        
        // Click on block to select
        document.addEventListener('click', (e) => {
            const block = e.target.closest('.preview-block');
            const toolbar = e.target.closest('.block-toolbar');
            
            // If clicking toolbar, handle action
            if (toolbar) {
                const btn = e.target.closest('[data-action]');
                if (btn && selectedBlockId) {
                    window.parent.postMessage({
                        type: 'toolbar-action',
                        action: btn.dataset.action,
                        blockId: selectedBlockId
                    }, '*');
                }
                return;
            }
            
            // If clicking block, select it
            if (block) {
                selectedBlockId = block.dataset.blockId;
                window.parent.postMessage({
                    type: 'block-click',
                    blockId: selectedBlockId
                }, '*');
            } else {
                // Clicking outside - deselect
                selectedBlockId = null;
                window.parent.postMessage({
                    type: 'block-deselect'
                }, '*');
            }
        });
        
        // Update toolbar position on scroll
        document.addEventListener('scroll', () => {
            if (selectedBlockId) {
                const block = document.querySelector('[data-block-id="' + selectedBlockId + '"]');
                const toolbar = document.getElementById('block-toolbar');
                const nameLabel = document.getElementById('block-name-label');
                
                if (block && toolbar) {
                    const rect = block.getBoundingClientRect();
                    const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
                    
                    toolbar.style.top = (rect.bottom + scrollTop - 20) + 'px';
                    
                    if (nameLabel) {
                        nameLabel.style.top = (rect.top + scrollTop + 8) + 'px';
                    }
                }
            }
        });
    <\/script>
</body>
</html>`;
    }

    /**
     * Bind event listeners
     * @private
     */
    _bindEvents() {
        // Device toggle
        this.container.addEventListener('click', (e) => {
            const deviceBtn = e.target.closest('[data-device]');
            if (deviceBtn) {
                this.setDevice(deviceBtn.dataset.device);
            }
        });

        // Listen for messages from iframe
        window.addEventListener('message', (e) => {
            if (e.data?.type === 'block-click') {
                this.pageState.selectBlock(e.data.blockId);
            } else if (e.data?.type === 'block-deselect') {
                this.pageState.deselectBlock();
            } else if (e.data?.type === 'toolbar-action') {
                this._handleToolbarAction(e.data.action, e.data.blockId);
            }
        });

        // Listen for page changes - debounced update
        eventBus.on(Events.PAGE_CHANGED, () => {
            this.update();
        });

        // Listen for block selection - fast update (no content change)
        eventBus.on(Events.BLOCK_SELECTED, ({ blockId }) => {
            this._updateSelection(blockId);
        });

        eventBus.on(Events.BLOCK_DESELECTED, () => {
            this._updateSelection(null);
        });
    }

    /**
     * Handle toolbar action from iframe
     * @param {string} action - Action name
     * @param {string} blockId - Block ID
     * @private
     */
    _handleToolbarAction(action, blockId) {
        const blocks = this.pageState.getBlocks();
        const currentIndex = blocks.findIndex(b => b.id === blockId);

        switch (action) {
            case 'delete':
                this.pageState.removeBlock(blockId);
                break;
            case 'duplicate':
                this.pageState.duplicateBlock(blockId);
                break;
            case 'add-below':
                eventBus.emit('open-component-library', { insertAfter: blockId });
                break;
            case 'move-up':
                if (currentIndex > 0) {
                    this.pageState.moveBlock(blockId, currentIndex - 1);
                }
                break;
            case 'move-down':
                if (currentIndex < blocks.length - 1) {
                    this.pageState.moveBlock(blockId, currentIndex + 1);
                }
                break;
            case 'toggle-visibility':
                this.pageState.toggleVisibility(blockId);
                break;
            case 'edit':
                eventBus.emit('edit-block', { blockId });
                break;
        }
    }

    /**
     * Set device view
     * @param {string} device - Device type ('desktop' or 'mobile')
     */
    setDevice(device) {
        this.device = device;
        
        // Update buttons
        this.container.querySelectorAll('[data-device]').forEach(btn => {
            btn.classList.toggle('is-active', btn.dataset.device === device);
        });

        // Update container
        const previewContainer = this.container.querySelector('#preview-container');
        if (previewContainer) {
            previewContainer.className = `preview__container preview__container--${device}`;
        }

        eventBus.emit(Events.DEVICE_CHANGED, { device });
    }

    /**
     * Get current device
     * @returns {string} Current device
     */
    getDevice() {
        return this.device;
    }

    /**
     * Export preview HTML
     * @returns {string} Clean HTML without preview wrappers
     */
    exportHTML() {
        return this.pageState.exportHTML();
    }

    /**
     * Destroy preview
     */
    destroy() {
        if (this._updateTimeout) {
            clearTimeout(this._updateTimeout);
        }
        this.container.innerHTML = '';
    }
}

export default Preview;
