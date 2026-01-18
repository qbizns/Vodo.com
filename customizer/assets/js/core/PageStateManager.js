/**
 * TailwindPlus Customizer - Page State Manager
 * =============================================
 * Manages page state, blocks, history (undo/redo)
 * 
 * @module core/PageStateManager
 * @version 1.0.0
 */

import { eventBus, Events } from './EventBus.js';

/**
 * Generate unique block ID
 * @returns {string} Unique ID
 */
function generateBlockId() {
    return 'block-' + Date.now().toString(36) + '-' + Math.random().toString(36).substr(2, 9);
}

export class PageStateManager {
    constructor(options = {}) {
        this._blocks = [];
        this._selectedBlockId = null;
        this._history = {
            past: [],
            future: [],
            maxSize: options.maxHistorySize || 50,
        };
        this._settings = {
            direction: 'rtl',
            language: 'ar',
            ...options.settings,
        };
    }

    // ============================================
    // HISTORY METHODS
    // ============================================

    /**
     * Save current state to history
     * @private
     */
    _saveHistory() {
        const snapshot = JSON.stringify(this._blocks);
        this._history.past.push(snapshot);
        this._history.future = [];

        // Limit history size
        if (this._history.past.length > this._history.maxSize) {
            this._history.past.shift();
        }
    }

    /**
     * Undo last action
     * @returns {boolean} Success
     */
    undo() {
        if (this._history.past.length === 0) return false;

        this._history.future.push(JSON.stringify(this._blocks));
        this._blocks = JSON.parse(this._history.past.pop());

        eventBus.emit(Events.HISTORY_UNDO, {});
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });

        return true;
    }

    /**
     * Redo last undone action
     * @returns {boolean} Success
     */
    redo() {
        if (this._history.future.length === 0) return false;

        this._history.past.push(JSON.stringify(this._blocks));
        this._blocks = JSON.parse(this._history.future.pop());

        eventBus.emit(Events.HISTORY_REDO, {});
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });

        return true;
    }

    /**
     * Check if undo is available
     * @returns {boolean} Can undo
     */
    canUndo() {
        return this._history.past.length > 0;
    }

    /**
     * Check if redo is available
     * @returns {boolean} Can redo
     */
    canRedo() {
        return this._history.future.length > 0;
    }

    /**
     * Clear history
     */
    clearHistory() {
        this._history.past = [];
        this._history.future = [];
    }

    // ============================================
    // BLOCK METHODS
    // ============================================

    /**
     * Add a block to the page
     * @param {Object} component - Component to add
     * @param {number|null} position - Position to insert at (null = end)
     * @param {Object} settings - Block settings
     * @returns {Object} Created block
     */
    addBlock(component, position = null, settings = {}) {
        this._saveHistory();

        const block = {
            id: generateBlockId(),
            componentId: component.id,
            name: component.name,
            category: component.category,
            html: component.html,
            thumbnail: component.thumbnail,
            visible: true,
            locked: false,
            order: position !== null ? position : this._blocks.length,
            settings: { ...settings },
            createdAt: Date.now(),
            updatedAt: Date.now(),
        };

        if (position !== null && position < this._blocks.length) {
            this._blocks.splice(position, 0, block);
            this._reorderBlocks();
        } else {
            this._blocks.push(block);
        }

        eventBus.emit(Events.BLOCK_ADDED, { block });
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });

        return block;
    }

    /**
     * Remove a block from the page
     * @param {string} blockId - Block ID to remove
     * @returns {boolean} Success
     */
    removeBlock(blockId) {
        const block = this.getBlock(blockId);
        if (!block || block.locked) return false;

        this._saveHistory();

        this._blocks = this._blocks.filter(b => b.id !== blockId);
        this._reorderBlocks();

        if (this._selectedBlockId === blockId) {
            this._selectedBlockId = null;
            eventBus.emit(Events.BLOCK_DESELECTED, { blockId });
        }

        eventBus.emit(Events.BLOCK_REMOVED, { blockId, block });
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });

        return true;
    }

    /**
     * Move a block to a new position
     * @param {string} blockId - Block ID to move
     * @param {number} newPosition - New position index
     * @returns {boolean} Success
     */
    moveBlock(blockId, newPosition) {
        const currentIndex = this._blocks.findIndex(b => b.id === blockId);
        if (currentIndex === -1) return false;

        const block = this._blocks[currentIndex];
        if (block.locked) return false;

        this._saveHistory();

        // Remove from current position
        this._blocks.splice(currentIndex, 1);

        // Insert at new position
        const insertIndex = Math.min(newPosition, this._blocks.length);
        this._blocks.splice(insertIndex, 0, block);

        this._reorderBlocks();

        eventBus.emit(Events.BLOCK_MOVED, { blockId, oldPosition: currentIndex, newPosition: insertIndex });
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });

        return true;
    }

    /**
     * Update block settings
     * @param {string} blockId - Block ID
     * @param {Object} updates - Settings to update
     * @returns {boolean} Success
     */
    updateBlock(blockId, updates) {
        const block = this.getBlock(blockId);
        if (!block) return false;

        this._saveHistory();

        Object.assign(block, updates, { updatedAt: Date.now() });

        eventBus.emit(Events.BLOCK_UPDATED, { block });
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });

        return true;
    }

    /**
     * Toggle block visibility
     * @param {string} blockId - Block ID
     * @returns {boolean} New visibility state
     */
    toggleVisibility(blockId) {
        const block = this.getBlock(blockId);
        if (!block) return false;

        this._saveHistory();

        block.visible = !block.visible;
        block.updatedAt = Date.now();

        eventBus.emit(Events.BLOCK_UPDATED, { block });
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });

        return block.visible;
    }

    /**
     * Duplicate a block
     * @param {string} blockId - Block ID to duplicate
     * @returns {Object|null} New block or null
     */
    duplicateBlock(blockId) {
        const block = this.getBlock(blockId);
        if (!block) return null;

        this._saveHistory();

        const newBlock = {
            ...JSON.parse(JSON.stringify(block)),
            id: generateBlockId(),
            locked: false,
            createdAt: Date.now(),
            updatedAt: Date.now(),
        };

        const index = this._blocks.findIndex(b => b.id === blockId);
        this._blocks.splice(index + 1, 0, newBlock);
        this._reorderBlocks();

        eventBus.emit(Events.BLOCK_ADDED, { block: newBlock });
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });

        return newBlock;
    }

    /**
     * Get a block by ID
     * @param {string} blockId - Block ID
     * @returns {Object|null} Block or null
     */
    getBlock(blockId) {
        return this._blocks.find(b => b.id === blockId) || null;
    }

    /**
     * Get all blocks
     * @returns {Object[]} All blocks
     */
    getBlocks() {
        return [...this._blocks];
    }

    /**
     * Get visible blocks sorted by order
     * @returns {Object[]} Visible blocks
     */
    getVisibleBlocks() {
        return this._blocks
            .filter(b => b.visible)
            .sort((a, b) => a.order - b.order);
    }

    /**
     * Reorder blocks
     * @private
     */
    _reorderBlocks() {
        this._blocks.forEach((block, index) => {
            block.order = index;
        });
    }

    // ============================================
    // SELECTION METHODS
    // ============================================

    /**
     * Select a block
     * @param {string} blockId - Block ID to select
     */
    selectBlock(blockId) {
        if (this._selectedBlockId === blockId) return;

        if (this._selectedBlockId) {
            eventBus.emit(Events.BLOCK_DESELECTED, { blockId: this._selectedBlockId });
        }

        this._selectedBlockId = blockId;
        eventBus.emit(Events.BLOCK_SELECTED, { blockId });
    }

    /**
     * Deselect current block
     */
    deselectBlock() {
        if (this._selectedBlockId) {
            const blockId = this._selectedBlockId;
            this._selectedBlockId = null;
            eventBus.emit(Events.BLOCK_DESELECTED, { blockId });
        }
    }

    /**
     * Get selected block ID
     * @returns {string|null} Selected block ID
     */
    getSelectedBlockId() {
        return this._selectedBlockId;
    }

    /**
     * Get selected block
     * @returns {Object|null} Selected block
     */
    getSelectedBlock() {
        return this._selectedBlockId ? this.getBlock(this._selectedBlockId) : null;
    }

    // ============================================
    // STATE METHODS
    // ============================================

    /**
     * Get full page state
     * @returns {Object} Page state
     */
    getState() {
        return {
            blocks: this._blocks,
            selectedBlockId: this._selectedBlockId,
            settings: this._settings,
        };
    }

    /**
     * Set page state
     * @param {Object} state - Page state
     */
    setState(state) {
        this._saveHistory();

        if (state.blocks) {
            this._blocks = state.blocks;
        }
        if (state.settings) {
            this._settings = { ...this._settings, ...state.settings };
        }

        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });
    }

    /**
     * Clear all blocks
     */
    clear() {
        this._saveHistory();
        this._blocks = [];
        this._selectedBlockId = null;
        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });
    }

    // ============================================
    // EXPORT METHODS
    // ============================================

    /**
     * Export page as HTML
     * @returns {string} HTML string
     */
    exportHTML() {
        return this.getVisibleBlocks()
            .map(block => block.html)
            .join('\n');
    }

    /**
     * Export page as JSON
     * @returns {Object} JSON object
     */
    exportJSON() {
        return {
            version: '1.0.0',
            exportedAt: new Date().toISOString(),
            settings: this._settings,
            blocks: this._blocks.map(b => ({
                componentId: b.componentId,
                order: b.order,
                visible: b.visible,
                settings: b.settings,
            })),
        };
    }

    /**
     * Import page from JSON
     * @param {Object} data - JSON data
     * @param {Object} registry - Component registry
     */
    importJSON(data, registry) {
        this._saveHistory();
        this._blocks = [];

        if (data.settings) {
            this._settings = { ...this._settings, ...data.settings };
        }

        if (data.blocks) {
            data.blocks.forEach(blockData => {
                const component = registry.getComponent(blockData.componentId);
                if (component) {
                    this.addBlock(component, blockData.order, blockData.settings);
                    const block = this._blocks[this._blocks.length - 1];
                    block.visible = blockData.visible !== undefined ? blockData.visible : true;
                }
            });
        }

        eventBus.emit(Events.PAGE_CHANGED, { blocks: this._blocks });
    }
}

// Create singleton instance
export const pageStateManager = new PageStateManager();

export default PageStateManager;
