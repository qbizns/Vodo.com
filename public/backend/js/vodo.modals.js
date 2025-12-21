/**
 * VODO Platform - Modal System
 *
 * Provides centralized modal management with AJAX loading,
 * stacking, animations, and focus trapping.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.modals.js');
        return;
    }

    // ============================================
    // Modals Configuration
    // ============================================

    const modals = {
        config: {
            zIndexBase: 1050,
            animationDuration: 200,
            closeOnBackdrop: true,
            closeOnEscape: true,
            focusTrap: true,
            bodyClass: 'modal-open'
        },

        // Stack of open modals
        _stack: [],

        // Modal counter for unique IDs
        _counter: 0
    };

    // ============================================
    // Template
    // ============================================

    function getModalTemplate(options) {
        const id = options.id;
        const size = options.size || 'md';
        const closable = options.closable !== false;

        return `
            <div class="vodo-modal-overlay" data-modal-id="${id}" style="z-index: ${modals.config.zIndexBase + modals._stack.length * 2}">
                <div class="vodo-modal vodo-modal-${size} ${options.class || ''}" role="dialog" aria-modal="true" aria-labelledby="modal-title-${id}">
                    <div class="vodo-modal-header">
                        <h3 class="vodo-modal-title" id="modal-title-${id}">${Vodo.utils.escapeHtml(options.title || '')}</h3>
                        ${closable ? '<button type="button" class="vodo-modal-close" data-modal-close aria-label="Close">&times;</button>' : ''}
                    </div>
                    <div class="vodo-modal-body">
                        ${options.content || ''}
                    </div>
                    ${options.footer ? `<div class="vodo-modal-footer">${options.footer}</div>` : ''}
                </div>
            </div>
        `;
    }

    // ============================================
    // Modal Operations
    // ============================================

    /**
     * Open a modal
     * @param {Object} options - Modal options
     * @returns {string} Modal ID
     */
    modals.open = function(options = {}) {
        const id = options.id || `modal-${++this._counter}`;

        // Check if modal already exists
        if ($(`[data-modal-id="${id}"]`).length) {
            Vodo.warn(`Modal ${id} already exists`);
            return id;
        }

        // Create modal HTML
        const html = getModalTemplate({ ...options, id });

        // Append to body
        $('body').append(html);

        const $overlay = $(`[data-modal-id="${id}"]`);
        const $modal = $overlay.find('.vodo-modal');

        // Store in stack
        this._stack.push({
            id,
            options,
            $overlay,
            $modal,
            previousFocus: document.activeElement
        });

        // Add body class
        $('body').addClass(this.config.bodyClass);

        // Bind events
        this._bindModalEvents($overlay, id, options);

        // Animate in
        $overlay.hide().fadeIn(this.config.animationDuration);
        $modal.css({ transform: 'scale(0.95)', opacity: 0 })
            .animate({ opacity: 1 }, this.config.animationDuration)
            .css('transform', 'scale(1)');

        // Focus first focusable element
        setTimeout(() => {
            const $focusable = $modal.find('input, select, textarea, button').not('[disabled]').first();
            if ($focusable.length) {
                $focusable.focus();
            } else {
                $modal.focus();
            }
        }, this.config.animationDuration);

        // Emit event
        if (Vodo.events) {
            Vodo.events.emit('modal:open', id, options);
        }

        // Callback
        if (options.onOpen) {
            options.onOpen(id, $modal);
        }

        Vodo.log(`Modal opened: ${id}`);

        return id;
    };

    /**
     * Open modal with AJAX content
     * @param {string} url - URL to load content from
     * @param {Object} options - Modal options
     * @returns {Promise<string>} Modal ID
     */
    modals.ajax = async function(url, options = {}) {
        // Open modal with skeleton
        const id = this.open({
            ...options,
            content: Vodo.skeleton ? Vodo.skeleton.create('modal') : '<div class="loading">Loading...</div>',
            class: 'modal-loading ' + (options.class || '')
        });

        try {
            // Load content
            const fragment = await Vodo.ajax.fragment(url);

            // Update modal
            const $modal = $(`[data-modal-id="${id}"] .vodo-modal`);
            const $body = $modal.find('.vodo-modal-body');

            // Update content
            $body.html(fragment.content);

            // Update title if provided
            if (fragment.title) {
                $modal.find('.vodo-modal-title').text(fragment.title);
            }

            // Remove loading class
            $modal.removeClass('modal-loading');

            // Initialize components
            if (Vodo.components) {
                Vodo.components.init($body);
            }

            // Initialize forms
            if (Vodo.forms) {
                Vodo.forms.init($body);
            }

            return id;

        } catch (error) {
            Vodo.error('Failed to load modal content:', error);

            // Show error in modal
            const $body = $(`[data-modal-id="${id}"] .vodo-modal-body`);
            $body.html(`
                <div class="error-state" style="text-align: center; padding: 20px;">
                    <p style="color: var(--text-error);">Failed to load content</p>
                    <button class="btn-secondary" data-modal-close>Close</button>
                </div>
            `);

            throw error;
        }
    };

    /**
     * Close modal
     * @param {string} id - Modal ID (optional, closes top modal if not provided)
     */
    modals.close = function(id = null) {
        let modalData;

        if (id) {
            // Find specific modal
            const index = this._stack.findIndex(m => m.id === id);
            if (index === -1) return;

            modalData = this._stack.splice(index, 1)[0];
        } else {
            // Close top modal
            modalData = this._stack.pop();
        }

        if (!modalData) return;

        const { $overlay, $modal, options, previousFocus } = modalData;

        // Animate out
        $modal.css('transform', 'scale(0.95)');
        $overlay.fadeOut(this.config.animationDuration, () => {
            // Destroy components
            if (Vodo.components) {
                Vodo.components.destroy($modal);
            }

            // Remove from DOM
            $overlay.remove();

            // Restore focus
            if (previousFocus) {
                previousFocus.focus();
            }

            // Emit event
            if (Vodo.events) {
                Vodo.events.emit('modal:close', modalData.id);
            }

            // Callback
            if (options.onClose) {
                options.onClose(modalData.id);
            }
        });

        // Remove body class if no more modals
        if (this._stack.length === 0) {
            $('body').removeClass(this.config.bodyClass);
        }

        Vodo.log(`Modal closed: ${modalData.id}`);
    };

    /**
     * Close all modals
     */
    modals.closeAll = function() {
        while (this._stack.length > 0) {
            this.close();
        }
    };

    /**
     * Update modal content
     * @param {string} id - Modal ID
     * @param {Object} updates - Updates to apply
     */
    modals.update = function(id, updates) {
        const $overlay = $(`[data-modal-id="${id}"]`);
        if (!$overlay.length) return;

        if (updates.title) {
            $overlay.find('.vodo-modal-title').text(updates.title);
        }

        if (updates.content) {
            $overlay.find('.vodo-modal-body').html(updates.content);
        }

        if (updates.footer) {
            let $footer = $overlay.find('.vodo-modal-footer');
            if (!$footer.length) {
                $footer = $('<div class="vodo-modal-footer"></div>');
                $overlay.find('.vodo-modal').append($footer);
            }
            $footer.html(updates.footer);
        }
    };

    // ============================================
    // Dialog Helpers
    // ============================================

    /**
     * Show confirm dialog
     * @param {string} message - Confirmation message
     * @param {Object} options - Options
     * @returns {Promise<boolean>}
     */
    modals.confirm = function(message, options = {}) {
        return new Promise((resolve) => {
            const id = this.open({
                title: options.title || 'Confirm',
                content: `<p>${Vodo.utils.escapeHtml(message)}</p>`,
                size: 'sm',
                closable: options.closable !== false,
                footer: `
                    <button class="btn-secondary" data-modal-close data-result="false">
                        ${options.cancelText || 'Cancel'}
                    </button>
                    <button class="btn-primary" data-modal-close data-result="true">
                        ${options.confirmText || 'Confirm'}
                    </button>
                `,
                onClose: () => resolve(false),
                ...options
            });

            // Handle button clicks
            $(`[data-modal-id="${id}"]`).on('click', '[data-result]', function() {
                const result = $(this).data('result') === true || $(this).data('result') === 'true';
                resolve(result);
            });
        });
    };

    /**
     * Show alert dialog
     * @param {string} message - Alert message
     * @param {Object} options - Options
     * @returns {Promise<void>}
     */
    modals.alert = function(message, options = {}) {
        return new Promise((resolve) => {
            this.open({
                title: options.title || 'Alert',
                content: `<p>${Vodo.utils.escapeHtml(message)}</p>`,
                size: 'sm',
                footer: `
                    <button class="btn-primary" data-modal-close>
                        ${options.buttonText || 'OK'}
                    </button>
                `,
                onClose: () => resolve(),
                ...options
            });
        });
    };

    /**
     * Show prompt dialog
     * @param {string} message - Prompt message
     * @param {Object} options - Options
     * @returns {Promise<string|null>}
     */
    modals.prompt = function(message, options = {}) {
        return new Promise((resolve) => {
            const inputId = Vodo.utils.uniqueId('prompt-input');
            const id = this.open({
                title: options.title || 'Prompt',
                content: `
                    <p>${Vodo.utils.escapeHtml(message)}</p>
                    <input type="${options.type || 'text'}"
                           id="${inputId}"
                           class="form-input"
                           value="${Vodo.utils.escapeHtml(options.defaultValue || '')}"
                           placeholder="${Vodo.utils.escapeHtml(options.placeholder || '')}">
                `,
                size: 'sm',
                footer: `
                    <button class="btn-secondary" data-modal-close data-action="cancel">
                        ${options.cancelText || 'Cancel'}
                    </button>
                    <button class="btn-primary" data-action="submit">
                        ${options.submitText || 'Submit'}
                    </button>
                `,
                onClose: () => resolve(null),
                ...options
            });

            const $overlay = $(`[data-modal-id="${id}"]`);
            const $input = $overlay.find(`#${inputId}`);

            // Handle submit
            $overlay.on('click', '[data-action="submit"]', () => {
                resolve($input.val());
                this.close(id);
            });

            // Handle enter key
            $input.on('keypress', (e) => {
                if (e.key === 'Enter') {
                    resolve($input.val());
                    this.close(id);
                }
            });
        });
    };

    // ============================================
    // Event Binding
    // ============================================

    modals._bindModalEvents = function($overlay, id, options) {
        const $modal = $overlay.find('.vodo-modal');

        // Close button
        $overlay.on('click', '[data-modal-close]', (e) => {
            e.preventDefault();
            this.close(id);
        });

        // Backdrop click
        if (this.config.closeOnBackdrop && options.closeOnBackdrop !== false) {
            $overlay.on('click', (e) => {
                if (e.target === $overlay[0]) {
                    this.close(id);
                }
            });
        }

        // Focus trap
        if (this.config.focusTrap) {
            $modal.on('keydown', (e) => {
                if (e.key !== 'Tab') return;

                const $focusable = $modal.find('input, select, textarea, button, [tabindex]:not([tabindex="-1"])').not('[disabled]');
                const $first = $focusable.first();
                const $last = $focusable.last();

                if (e.shiftKey && document.activeElement === $first[0]) {
                    e.preventDefault();
                    $last.focus();
                } else if (!e.shiftKey && document.activeElement === $last[0]) {
                    e.preventDefault();
                    $first.focus();
                }
            });
        }
    };

    // ============================================
    // Utilities
    // ============================================

    /**
     * Get current modal
     * @returns {Object|null}
     */
    modals.current = function() {
        return this._stack.length > 0 ? this._stack[this._stack.length - 1] : null;
    };

    /**
     * Check if any modal is open
     * @returns {boolean}
     */
    modals.isOpen = function() {
        return this._stack.length > 0;
    };

    /**
     * Get modal by ID
     * @param {string} id - Modal ID
     * @returns {Object|null}
     */
    modals.get = function(id) {
        return this._stack.find(m => m.id === id) || null;
    };

    // ============================================
    // Initialize
    // ============================================

    modals.init = function() {
        // Global escape key handler
        $(document).on('keydown.vodo-modals', (e) => {
            if (e.key === 'Escape' && this.config.closeOnEscape && this._stack.length > 0) {
                const current = this.current();
                if (current && current.options.closeOnEscape !== false) {
                    this.close();
                }
            }
        });

        // Add modal styles if not present
        if (!document.getElementById('vodo-modal-styles')) {
            const styles = `
                <style id="vodo-modal-styles">
                    .modal-open { overflow: hidden; }

                    .vodo-modal-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0, 0, 0, 0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                    }

                    .vodo-modal {
                        background: var(--bg-surface-1, #fff);
                        border-radius: var(--radius-lg, 8px);
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                        max-height: 90vh;
                        display: flex;
                        flex-direction: column;
                        transition: transform 0.2s, opacity 0.2s;
                    }

                    .vodo-modal-sm { width: 100%; max-width: 400px; }
                    .vodo-modal-md { width: 100%; max-width: 600px; }
                    .vodo-modal-lg { width: 100%; max-width: 900px; }
                    .vodo-modal-xl { width: 100%; max-width: 1200px; }
                    .vodo-modal-full { width: 95%; max-width: none; height: 90vh; }

                    .vodo-modal-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 16px 20px;
                        border-bottom: 1px solid var(--border-color, #e5e7eb);
                    }

                    .vodo-modal-title {
                        font-size: 1.125rem;
                        font-weight: 600;
                        margin: 0;
                    }

                    .vodo-modal-close {
                        background: none;
                        border: none;
                        font-size: 24px;
                        cursor: pointer;
                        color: var(--text-secondary);
                        padding: 0;
                        width: 32px;
                        height: 32px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 4px;
                    }

                    .vodo-modal-close:hover {
                        background: var(--bg-surface-2);
                    }

                    .vodo-modal-body {
                        padding: 20px;
                        overflow-y: auto;
                        flex: 1;
                    }

                    .vodo-modal-footer {
                        display: flex;
                        justify-content: flex-end;
                        gap: 12px;
                        padding: 16px 20px;
                        border-top: 1px solid var(--border-color, #e5e7eb);
                    }

                    .dark .vodo-modal-overlay {
                        background: rgba(0, 0, 0, 0.7);
                    }
                </style>
            `;
            document.head.insertAdjacentHTML('beforeend', styles);
        }

        Vodo.log('Modals module initialized');
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('modals', modals);

})(typeof window !== 'undefined' ? window : this);
