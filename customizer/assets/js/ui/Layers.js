/**
 * TailwindPlus Customizer - Layers UI Component
 * ==============================================
 * Sortable layer list for page blocks with smooth updates
 * 
 * @module ui/Layers
 * @version 1.1.0
 */

import { eventBus, Events } from '../core/EventBus.js';
import { icon } from '../utils/icons.js';
import { getText } from '../utils/helpers.js';

export class Layers {
    /**
     * @param {Object} options - Layers options
     * @param {HTMLElement} options.container - Container element
     * @param {Object} options.pageState - Page state manager instance
     * @param {string} options.language - Current language
     */
    constructor(options) {
        this.container = options.container;
        this.pageState = options.pageState;
        this.language = options.language || 'ar';
        this.sortableInitialized = false;
        this._skipNextRender = false;

        this._bindEvents();
        this.render();
    }

    /**
     * Render layers list (initial render only)
     */
    render() {
        const blocks = this.pageState.getBlocks();
        const selectedId = this.pageState.getSelectedBlockId();

        if (blocks.length === 0) {
            this._destroySortable();
            this.container.innerHTML = this._renderEmptyState();
            return;
        }

        // Check if we already have a layers container
        let layersContainer = this.container.querySelector('.layers');
        
        if (!layersContainer) {
            this.container.innerHTML = '<div class="layers"></div>';
            layersContainer = this.container.querySelector('.layers');
        }

        // Build layers
        const layersHtml = blocks
            .sort((a, b) => a.order - b.order)
            .map(block => this._renderLayerItem(block, selectedId))
            .join('');

        layersContainer.innerHTML = layersHtml;
        
        // Initialize sortable after render
        this._initSortable();
    }

    /**
     * Render empty state
     * @returns {string} HTML string
     * @private
     */
    _renderEmptyState() {
        return `
            <div class="layers__empty">
                <div class="layers__empty-icon">
                    ${icon('empty', { size: 48 })}
                </div>
                <p class="layers__empty-title">
                    ${getText({ ar: 'لا توجد عناصر', en: 'No elements' }, this.language)}
                </p>
                <p class="layers__empty-text">
                    ${getText({ ar: 'اضغط على "إضافة عنصر جديد" للبدء', en: 'Click "Add New Element" to start' }, this.language)}
                </p>
            </div>
        `;
    }

    /**
     * Render single layer item
     * @param {Object} block - Block data
     * @param {string} selectedId - Currently selected block ID
     * @returns {string} HTML string
     * @private
     */
    _renderLayerItem(block, selectedId) {
        const name = getText(block.name, this.language);
        const isSelected = block.id === selectedId;
        const isHidden = !block.visible;
        const isLocked = block.locked;

        const classes = [
            'layer-item',
            isSelected ? 'is-selected' : '',
            isHidden ? 'is-hidden' : '',
            isLocked ? 'is-locked' : '',
        ].filter(Boolean).join(' ');

        return `
            <div class="${classes}" data-block-id="${block.id}">
                <div class="layer-item__drag" title="${getText({ ar: 'اسحب لإعادة الترتيب', en: 'Drag to reorder' }, this.language)}">
                    ${icon('drag', { size: 16 })}
                </div>
                
                <button 
                    class="layer-item__visibility" 
                    data-action="toggle-visibility"
                    title="${getText({ ar: isHidden ? 'إظهار' : 'إخفاء', en: isHidden ? 'Show' : 'Hide' }, this.language)}"
                >
                    ${icon(isHidden ? 'eye-off' : 'eye', { size: 16 })}
                </button>
                
                <span class="layer-item__name">${name}</span>
                
                <div class="layer-item__actions">
                    <button 
                        class="layer-item__action" 
                        data-action="duplicate"
                        title="${getText({ ar: 'نسخ', en: 'Duplicate' }, this.language)}"
                    >
                        ${icon('copy', { size: 16 })}
                    </button>
                    <button 
                        class="layer-item__action layer-item__action--delete" 
                        data-action="delete"
                        title="${getText({ ar: 'حذف', en: 'Delete' }, this.language)}"
                    >
                        ${icon('trash', { size: 16 })}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Destroy sortable if exists
     * @private
     */
    _destroySortable() {
        if (this.sortableInitialized) {
            const layersContainer = this.container.querySelector('.layers');
            if (layersContainer && typeof $ !== 'undefined' && $.fn.sortable) {
                try {
                    $(layersContainer).sortable('destroy');
                } catch (e) {}
            }
            this.sortableInitialized = false;
        }
    }

    /**
     * Initialize sortable functionality
     * @private
     */
    _initSortable() {
        const layersContainer = this.container.querySelector('.layers');
        if (!layersContainer) return;

        // Destroy existing sortable first
        this._destroySortable();

        // Wait for jQuery UI to be ready
        if (typeof $ !== 'undefined' && $.fn && $.fn.sortable) {
            const self = this;
            
            $(layersContainer).sortable({
                handle: '.layer-item__drag',
                placeholder: 'layer-item layer-item--placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                axis: 'y',
                containment: 'parent',
                cursor: 'grabbing',
                opacity: 0.9,
                revert: 100,
                start: function(event, ui) {
                    ui.item.addClass('layer-item--dragging');
                    ui.placeholder.height(ui.item.outerHeight());
                },
                stop: function(event, ui) {
                    ui.item.removeClass('layer-item--dragging');
                },
                update: function(event, ui) {
                    const blockId = ui.item.attr('data-block-id');
                    const newIndex = ui.item.index();
                    
                    // Skip the next PAGE_CHANGED render since we already updated DOM
                    self._skipNextRender = true;
                    self.pageState.moveBlock(blockId, newIndex);
                }
            });
            
            this.sortableInitialized = true;
        }
    }

    /**
     * Add a single layer item smoothly (no full re-render)
     * @param {Object} block - Block data
     * @private
     */
    _addLayerItem(block) {
        let layersContainer = this.container.querySelector('.layers');
        
        // If empty state is showing, replace with layers container
        if (!layersContainer) {
            this.container.innerHTML = '<div class="layers"></div>';
            layersContainer = this.container.querySelector('.layers');
            this._initSortable();
        }

        const selectedId = this.pageState.getSelectedBlockId();
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = this._renderLayerItem(block, selectedId);
        const newItem = tempDiv.firstElementChild;
        
        // Add with animation
        newItem.style.opacity = '0';
        newItem.style.transform = 'translateY(-10px)';
        layersContainer.appendChild(newItem);
        
        // Trigger animation
        requestAnimationFrame(() => {
            newItem.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateY(0)';
        });

        // Refresh sortable
        if (this.sortableInitialized && typeof $ !== 'undefined') {
            $(layersContainer).sortable('refresh');
        }
    }

    /**
     * Remove a layer item smoothly
     * @param {string} blockId - Block ID to remove
     * @private
     */
    _removeLayerItem(blockId) {
        const item = this.container.querySelector(`[data-block-id="${blockId}"]`);
        if (!item) return;

        // Animate out
        item.style.transition = 'opacity 0.2s ease, transform 0.2s ease, height 0.2s ease, margin 0.2s ease, padding 0.2s ease';
        item.style.opacity = '0';
        item.style.transform = 'translateX(20px)';
        
        setTimeout(() => {
            item.style.height = '0';
            item.style.margin = '0';
            item.style.padding = '0';
            item.style.overflow = 'hidden';
            
            setTimeout(() => {
                item.remove();
                
                // Check if empty
                const layersContainer = this.container.querySelector('.layers');
                if (layersContainer && layersContainer.children.length === 0) {
                    this.container.innerHTML = this._renderEmptyState();
                }
            }, 200);
        }, 150);
    }

    /**
     * Update visibility icon smoothly
     * @param {string} blockId - Block ID
     * @param {boolean} visible - New visibility state
     * @private
     */
    _updateVisibility(blockId, visible) {
        const item = this.container.querySelector(`[data-block-id="${blockId}"]`);
        if (!item) return;

        item.classList.toggle('is-hidden', !visible);
        
        const visBtn = item.querySelector('[data-action="toggle-visibility"]');
        if (visBtn) {
            visBtn.innerHTML = icon(visible ? 'eye' : 'eye-off', { size: 16 });
        }
    }

    /**
     * Bind event listeners
     * @private
     */
    _bindEvents() {
        // Click handlers using event delegation
        this.container.addEventListener('click', (e) => {
            const layerItem = e.target.closest('.layer-item');
            if (!layerItem) return;

            const blockId = layerItem.dataset.blockId;
            const actionBtn = e.target.closest('[data-action]');

            if (actionBtn) {
                const action = actionBtn.dataset.action;
                e.stopPropagation();
                e.preventDefault();

                switch (action) {
                    case 'toggle-visibility':
                        const block = this.pageState.getBlock(blockId);
                        if (block) {
                            this._skipNextRender = true;
                            const newVisibility = this.pageState.toggleVisibility(blockId);
                            this._updateVisibility(blockId, newVisibility);
                        }
                        break;
                    case 'duplicate':
                        this.pageState.duplicateBlock(blockId);
                        break;
                    case 'delete':
                        if (this._confirmDelete()) {
                            this._skipNextRender = true;
                            this._removeLayerItem(blockId);
                            this.pageState.removeBlock(blockId);
                        }
                        break;
                }
            } else {
                // Select block - just update class, don't re-render
                this._updateSelection(blockId);
                this.pageState.selectBlock(blockId);
            }
        });

        // Listen for block added
        eventBus.on(Events.BLOCK_ADDED, ({ block }) => {
            if (!this._skipNextRender) {
                this._addLayerItem(block);
            }
        });

        // Listen for page changes (only for full syncs)
        eventBus.on(Events.PAGE_CHANGED, () => {
            if (this._skipNextRender) {
                this._skipNextRender = false;
                return;
            }
            // Only do full render if needed
            this.render();
        });

        // Listen for block selection - just update classes
        eventBus.on(Events.BLOCK_SELECTED, ({ blockId }) => {
            this._updateSelection(blockId);
        });

        eventBus.on(Events.BLOCK_DESELECTED, () => {
            this._updateSelection(null);
        });

        // History events need full render
        eventBus.on(Events.HISTORY_UNDO, () => {
            this._skipNextRender = false;
            this.render();
        });

        eventBus.on(Events.HISTORY_REDO, () => {
            this._skipNextRender = false;
            this.render();
        });
    }

    /**
     * Confirm delete dialog
     * @returns {boolean} User confirmed
     * @private
     */
    _confirmDelete() {
        const message = getText(
            { ar: 'هل تريد حذف هذا العنصر؟', en: 'Delete this element?' },
            this.language
        );
        return confirm(message);
    }

    /**
     * Update selection state in UI (no re-render)
     * @param {string|null} blockId - Selected block ID
     * @private
     */
    _updateSelection(blockId) {
        this.container.querySelectorAll('.layer-item').forEach(item => {
            const isSelected = item.dataset.blockId === blockId;
            item.classList.toggle('is-selected', isSelected);
        });
    }

    /**
     * Scroll to layer item
     * @param {string} blockId - Block ID
     */
    scrollToBlock(blockId) {
        const item = this.container.querySelector(`[data-block-id="${blockId}"]`);
        if (item) {
            item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    /**
     * Destroy layers component
     */
    destroy() {
        this._destroySortable();
        this.container.innerHTML = '';
    }
}

export default Layers;
