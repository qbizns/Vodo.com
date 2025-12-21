/**
 * VODO Platform - Skeleton Loader System
 *
 * Provides context-aware skeleton loaders for improved
 * perceived performance during content loading.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.skeleton.js');
        return;
    }

    // ============================================
    // Skeleton Configuration
    // ============================================

    const skeleton = {
        config: {
            shimmer: true,
            shimmerDuration: 1.5,
            minDisplayTime: 300,
            baseClass: 'skeleton-shimmer',
            containerClass: 'skeleton-container',
            loadingClass: 'is-loading'
        },

        // Registered skeleton types
        _types: new Map(),

        // Active skeletons
        _active: new Map()
    };

    // ============================================
    // Helper Functions
    // ============================================

    /**
     * Repeat a template n times
     */
    function repeat(count, callback) {
        return Array.from({ length: count }, (_, i) => callback(i)).join('');
    }

    /**
     * Calculate visible rows based on container height
     */
    function calculateRows(container, rowHeight = 48) {
        const $container = $(container);
        const height = $container.height() || 400;
        return Math.max(3, Math.min(15, Math.floor(height / rowHeight)));
    }

    /**
     * Calculate visible cards based on container width
     */
    function calculateCards(container, cardWidth = 280) {
        const $container = $(container);
        const width = $container.width() || 1200;
        const cols = Math.max(1, Math.floor(width / cardWidth));
        return cols * 2; // 2 rows of cards
    }

    /**
     * Get column width distribution
     */
    function getColWidths(cols) {
        const widths = [
            [100],
            [40, 60],
            [30, 40, 30],
            [10, 35, 25, 30],
            [8, 25, 20, 25, 22],
            [5, 20, 18, 22, 20, 15]
        ];
        return widths[Math.min(cols - 1, widths.length - 1)] || widths[4];
    }

    // ============================================
    // Built-in Skeleton Types
    // ============================================

    /**
     * Table skeleton
     */
    skeleton._types.set('table', function(container, options = {}) {
        const rows = options.rows || calculateRows(container);
        const cols = options.cols || 5;
        const widths = getColWidths(cols);
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-table">
                <div class="skeleton-thead">
                    ${repeat(cols, i => `
                        <div class="skeleton-th ${baseClass}" style="width: ${widths[i] || 20}%"></div>
                    `)}
                </div>
                <div class="skeleton-tbody">
                    ${repeat(rows, () => `
                        <div class="skeleton-tr">
                            ${repeat(cols, i => `
                                <div class="skeleton-td ${baseClass}" style="width: ${widths[i] || 20}%"></div>
                            `)}
                        </div>
                    `)}
                </div>
            </div>
        `;
    });

    /**
     * Card grid skeleton
     */
    skeleton._types.set('cards', function(container, options = {}) {
        const count = options.count || calculateCards(container);
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-cards">
                ${repeat(count, () => `
                    <div class="skeleton-card">
                        <div class="skeleton-card-image ${baseClass}"></div>
                        <div class="skeleton-card-body">
                            <div class="skeleton-line ${baseClass}" style="width: 70%"></div>
                            <div class="skeleton-line ${baseClass}" style="width: 50%"></div>
                            <div class="skeleton-line ${baseClass}" style="width: 90%"></div>
                        </div>
                        <div class="skeleton-card-footer">
                            <div class="skeleton-btn ${baseClass}"></div>
                        </div>
                    </div>
                `)}
            </div>
        `;
    });

    /**
     * Form skeleton
     */
    skeleton._types.set('form', function(container, options = {}) {
        const fields = options.fields || 5;
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-form">
                ${repeat(fields, (i) => `
                    <div class="skeleton-field">
                        <div class="skeleton-label ${baseClass}" style="width: ${80 + Math.random() * 40}px"></div>
                        <div class="skeleton-input ${baseClass}"></div>
                    </div>
                `)}
                <div class="skeleton-form-actions">
                    <div class="skeleton-btn skeleton-btn-primary ${baseClass}"></div>
                    <div class="skeleton-btn ${baseClass}"></div>
                </div>
            </div>
        `;
    });

    /**
     * List skeleton
     */
    skeleton._types.set('list', function(container, options = {}) {
        const items = options.items || 8;
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-list">
                ${repeat(items, () => `
                    <div class="skeleton-list-item">
                        <div class="skeleton-list-icon ${baseClass}"></div>
                        <div class="skeleton-list-content">
                            <div class="skeleton-line ${baseClass}" style="width: ${60 + Math.random() * 30}%"></div>
                            <div class="skeleton-line skeleton-line-sm ${baseClass}" style="width: ${40 + Math.random() * 20}%"></div>
                        </div>
                    </div>
                `)}
            </div>
        `;
    });

    /**
     * Detail view skeleton
     */
    skeleton._types.set('detail', function(container, options = {}) {
        const rows = options.rows || 6;
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-detail">
                <div class="skeleton-detail-header">
                    <div class="skeleton-avatar ${baseClass}"></div>
                    <div class="skeleton-detail-title">
                        <div class="skeleton-line skeleton-line-lg ${baseClass}" style="width: 50%"></div>
                        <div class="skeleton-line ${baseClass}" style="width: 30%"></div>
                    </div>
                </div>
                <div class="skeleton-detail-body">
                    ${repeat(rows, () => `
                        <div class="skeleton-detail-row">
                            <div class="skeleton-detail-label ${baseClass}"></div>
                            <div class="skeleton-detail-value ${baseClass}"></div>
                        </div>
                    `)}
                </div>
            </div>
        `;
    });

    /**
     * Dashboard skeleton
     */
    skeleton._types.set('dashboard', function(container, options = {}) {
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-dashboard">
                <div class="skeleton-stats-row">
                    ${repeat(4, () => `
                        <div class="skeleton-stat-card">
                            <div class="skeleton-stat-icon ${baseClass}"></div>
                            <div class="skeleton-stat-content">
                                <div class="skeleton-line ${baseClass}" style="width: 60%"></div>
                                <div class="skeleton-line skeleton-line-lg ${baseClass}" style="width: 40%"></div>
                            </div>
                        </div>
                    `)}
                </div>
                <div class="skeleton-widgets-row">
                    <div class="skeleton-widget skeleton-widget-lg">
                        <div class="skeleton-widget-header">
                            <div class="skeleton-line ${baseClass}" style="width: 30%"></div>
                        </div>
                        <div class="skeleton-chart ${baseClass}"></div>
                    </div>
                    <div class="skeleton-widget">
                        <div class="skeleton-widget-header">
                            <div class="skeleton-line ${baseClass}" style="width: 40%"></div>
                        </div>
                        <div class="skeleton-widget-list">
                            ${repeat(5, () => `
                                <div class="skeleton-widget-list-item ${baseClass}"></div>
                            `)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    /**
     * Text/paragraph skeleton
     */
    skeleton._types.set('text', function(container, options = {}) {
        const paragraphs = options.paragraphs || 3;
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-text">
                ${repeat(paragraphs, () => `
                    <div class="skeleton-paragraph">
                        <div class="skeleton-line ${baseClass}" style="width: 100%"></div>
                        <div class="skeleton-line ${baseClass}" style="width: 95%"></div>
                        <div class="skeleton-line ${baseClass}" style="width: 85%"></div>
                        <div class="skeleton-line ${baseClass}" style="width: 70%"></div>
                    </div>
                `)}
            </div>
        `;
    });

    /**
     * Mixed/generic skeleton
     */
    skeleton._types.set('mixed', function(container, options = {}) {
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-mixed">
                <div class="skeleton-header-block">
                    <div class="skeleton-line skeleton-line-lg ${baseClass}" style="width: 40%"></div>
                    <div class="skeleton-line ${baseClass}" style="width: 60%"></div>
                </div>
                <div class="skeleton-content-block">
                    ${repeat(3, () => `
                        <div class="skeleton-content-row">
                            <div class="skeleton-content-icon ${baseClass}"></div>
                            <div class="skeleton-content-text">
                                <div class="skeleton-line ${baseClass}" style="width: ${70 + Math.random() * 25}%"></div>
                                <div class="skeleton-line skeleton-line-sm ${baseClass}" style="width: ${50 + Math.random() * 30}%"></div>
                            </div>
                        </div>
                    `)}
                </div>
            </div>
        `;
    });

    /**
     * Modal content skeleton
     */
    skeleton._types.set('modal', function(container, options = {}) {
        const baseClass = skeleton.config.baseClass;

        return `
            <div class="skeleton-modal-content">
                <div class="skeleton-line skeleton-line-lg ${baseClass}" style="width: 60%; margin-bottom: 24px;"></div>
                ${repeat(4, () => `
                    <div class="skeleton-field">
                        <div class="skeleton-label ${baseClass}"></div>
                        <div class="skeleton-input ${baseClass}"></div>
                    </div>
                `)}
            </div>
        `;
    });

    // ============================================
    // Auto-Detection
    // ============================================

    /**
     * Detect skeleton type based on container content/attributes
     */
    function detectType(container) {
        const $container = $(container);

        // Check data attribute first
        const explicit = $container.data('skeleton-type');
        if (explicit && skeleton._types.has(explicit)) {
            return explicit;
        }

        // Check for common patterns
        if ($container.find('table, .data-table, [data-table]').length) return 'table';
        if ($container.find('.card-grid, .marketplace-grid, .grid').length) return 'cards';
        if ($container.find('form, .form-group, .form-field').length) return 'form';
        if ($container.find('ul, ol, .list-group, .list').length) return 'list';
        if ($container.hasClass('dashboard-page') || $container.find('.widget-grid').length) return 'dashboard';
        if ($container.find('.detail-view, .profile').length) return 'detail';

        // Default to mixed
        return 'mixed';
    }

    // ============================================
    // Public API
    // ============================================

    /**
     * Show skeleton in container
     * @param {string|Element|jQuery} container - Target container
     * @param {string} type - Skeleton type (or 'auto' for detection)
     * @param {Object} options - Options for skeleton generation
     * @returns {string} Skeleton ID for later reference
     */
    skeleton.show = function(container, type = 'auto', options = {}) {
        const $container = $(container);
        if (!$container.length) {
            Vodo.warn('Skeleton container not found');
            return null;
        }

        // Generate unique ID
        const id = Vodo.utils.uniqueId('skeleton');

        // Detect type if auto
        const skeletonType = type === 'auto' ? detectType($container) : type;

        // Get template function
        const templateFn = this._types.get(skeletonType);
        if (!templateFn) {
            Vodo.warn(`Unknown skeleton type: ${skeletonType}`);
            return null;
        }

        // Store original content
        const originalContent = $container.html();
        const originalMinHeight = $container.css('min-height');

        // Generate skeleton HTML
        const skeletonHtml = templateFn($container[0], options);

        // Apply skeleton
        $container
            .addClass(this.config.containerClass)
            .addClass(this.config.loadingClass)
            .css('min-height', $container.height() || 200)
            .html(`<div class="skeleton-wrapper" data-skeleton-id="${id}">${skeletonHtml}</div>`);

        // Store reference
        this._active.set(id, {
            container: $container,
            originalContent,
            originalMinHeight,
            type: skeletonType,
            startTime: Date.now()
        });

        Vodo.log(`Skeleton shown: ${id} (${skeletonType})`);

        // Emit event
        if (Vodo.events) {
            Vodo.events.emit('skeleton:show', id, skeletonType, $container);
        }

        return id;
    };

    /**
     * Hide skeleton and restore content
     * @param {string|Element|jQuery} containerOrId - Container or skeleton ID
     * @param {boolean} fade - Whether to fade out
     * @returns {Promise}
     */
    skeleton.hide = function(containerOrId, fade = true) {
        return new Promise((resolve) => {
            let $container, skeletonData;

            // Find by ID or container
            if (typeof containerOrId === 'string' && this._active.has(containerOrId)) {
                skeletonData = this._active.get(containerOrId);
                $container = skeletonData.container;
            } else {
                $container = $(containerOrId);
                // Find by container
                for (const [id, data] of this._active) {
                    if (data.container.is($container)) {
                        skeletonData = data;
                        containerOrId = id;
                        break;
                    }
                }
            }

            if (!$container || !$container.length) {
                resolve();
                return;
            }

            // Ensure minimum display time
            const elapsed = skeletonData ? Date.now() - skeletonData.startTime : this.config.minDisplayTime;
            const remaining = Math.max(0, this.config.minDisplayTime - elapsed);

            setTimeout(() => {
                const doHide = () => {
                    $container
                        .removeClass(this.config.containerClass)
                        .removeClass(this.config.loadingClass);

                    if (skeletonData) {
                        $container.css('min-height', skeletonData.originalMinHeight || '');
                    }

                    // Clean up
                    if (typeof containerOrId === 'string') {
                        this._active.delete(containerOrId);
                    }

                    Vodo.log(`Skeleton hidden: ${containerOrId}`);

                    // Emit event
                    if (Vodo.events) {
                        Vodo.events.emit('skeleton:hide', containerOrId, $container);
                    }

                    resolve();
                };

                if (fade) {
                    const $wrapper = $container.find('.skeleton-wrapper');
                    if ($wrapper.length) {
                        $wrapper.fadeOut(200, () => {
                            $wrapper.remove();
                            doHide();
                        });
                    } else {
                        doHide();
                    }
                } else {
                    $container.find('.skeleton-wrapper').remove();
                    doHide();
                }
            }, remaining);
        });
    };

    /**
     * Register custom skeleton type
     * @param {string} name - Type name
     * @param {Function} templateFn - Template function (container, options) => html
     */
    skeleton.register = function(name, templateFn) {
        if (typeof templateFn !== 'function') {
            Vodo.error('Skeleton template must be a function');
            return;
        }
        this._types.set(name, templateFn);
        Vodo.log(`Skeleton type registered: ${name}`);
    };

    /**
     * Create skeleton HTML without showing
     * @param {string} type - Skeleton type
     * @param {Object} options - Options
     * @returns {string} HTML string
     */
    skeleton.create = function(type, options = {}) {
        const templateFn = this._types.get(type);
        if (!templateFn) {
            Vodo.warn(`Unknown skeleton type: ${type}`);
            return '';
        }
        return templateFn(null, options);
    };

    /**
     * Check if skeleton is active in container
     * @param {string|Element|jQuery} containerOrId
     * @returns {boolean}
     */
    skeleton.isActive = function(containerOrId) {
        if (typeof containerOrId === 'string' && this._active.has(containerOrId)) {
            return true;
        }
        const $container = $(containerOrId);
        for (const data of this._active.values()) {
            if (data.container.is($container)) {
                return true;
            }
        }
        return false;
    };

    /**
     * Hide all active skeletons
     */
    skeleton.hideAll = function() {
        const promises = [];
        for (const id of this._active.keys()) {
            promises.push(this.hide(id));
        }
        return Promise.all(promises);
    };

    /**
     * Get list of registered types
     * @returns {Array<string>}
     */
    skeleton.types = function() {
        return [...this._types.keys()];
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('skeleton', skeleton);

})(typeof window !== 'undefined' ? window : this);
