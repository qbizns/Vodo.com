/**
 * VODO Platform - Notification System
 *
 * Provides toast notifications with stacking, auto-dismiss,
 * and action support.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.notifications.js');
        return;
    }

    // ============================================
    // Notifications Configuration
    // ============================================

    const notify = {
        config: {
            position: 'top-right',
            duration: 5000,
            maxVisible: 5,
            showProgress: true,
            pauseOnHover: true,
            closeButton: true,
            gap: 10,
            offset: 20
        },

        // Active notifications
        _notifications: [],

        // Container element
        _container: null,

        // Notification counter
        _counter: 0
    };

    // ============================================
    // Icons
    // ============================================

    const ICONS = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
    };

    // ============================================
    // Container Management
    // ============================================

    function getContainer() {
        if (notify._container) {
            return notify._container;
        }

        // Create container
        const $container = $('<div class="vodo-notifications"></div>');
        $container.addClass(`vodo-notifications-${notify.config.position}`);
        $('body').append($container);

        notify._container = $container;
        return $container;
    }

    function updatePositions() {
        const $container = getContainer();
        const isTop = notify.config.position.includes('top');

        let offset = notify.config.offset;

        notify._notifications.forEach((notification, index) => {
            if (index >= notify.config.maxVisible) {
                notification.$element.hide();
            } else {
                notification.$element.show();
                if (isTop) {
                    notification.$element.css('top', offset);
                } else {
                    notification.$element.css('bottom', offset);
                }
                offset += notification.$element.outerHeight() + notify.config.gap;
            }
        });
    }

    // ============================================
    // Notification Display
    // ============================================

    /**
     * Show a notification
     * @param {Object} options - Notification options
     * @returns {string} Notification ID
     */
    notify.show = function(options = {}) {
        const id = `notification-${++this._counter}`;
        const type = options.type || 'info';
        const duration = options.duration !== undefined ? options.duration : this.config.duration;

        // Create notification element
        const $notification = $(`
            <div class="vodo-notification vodo-notification-${type}" data-notification-id="${id}">
                <div class="vodo-notification-icon">
                    ${options.icon || ICONS[type] || ICONS.info}
                </div>
                <div class="vodo-notification-content">
                    ${options.title ? `<div class="vodo-notification-title">${Vodo.utils.escapeHtml(options.title)}</div>` : ''}
                    <div class="vodo-notification-message">${Vodo.utils.escapeHtml(options.message)}</div>
                    ${options.actions ? `<div class="vodo-notification-actions"></div>` : ''}
                </div>
                ${this.config.closeButton ? '<button class="vodo-notification-close">&times;</button>' : ''}
                ${this.config.showProgress && duration > 0 ? '<div class="vodo-notification-progress"></div>' : ''}
            </div>
        `);

        // Add actions
        if (options.actions) {
            const $actions = $notification.find('.vodo-notification-actions');
            options.actions.forEach(action => {
                const $btn = $(`<button class="vodo-notification-action">${Vodo.utils.escapeHtml(action.label)}</button>`);
                $btn.on('click', () => {
                    if (action.onClick) {
                        action.onClick();
                    }
                    this.dismiss(id);
                });
                $actions.append($btn);
            });
        }

        // Add to container
        const $container = getContainer();
        $container.append($notification);

        // Store notification data
        const notificationData = {
            id,
            $element: $notification,
            options,
            timeout: null,
            paused: false,
            startTime: Date.now(),
            remainingTime: duration
        };

        this._notifications.push(notificationData);

        // Animate in
        $notification.css('opacity', 0).animate({ opacity: 1 }, 200);

        // Bind events
        this._bindNotificationEvents(notificationData);

        // Start auto-dismiss timer
        if (duration > 0) {
            this._startTimer(notificationData);
        }

        // Update positions
        updatePositions();

        // Emit event
        if (Vodo.events) {
            Vodo.events.emit('notification:show', id, options);
        }

        return id;
    };

    /**
     * Start auto-dismiss timer
     */
    notify._startTimer = function(notification) {
        const duration = notification.remainingTime;

        // Update progress bar
        if (this.config.showProgress) {
            const $progress = notification.$element.find('.vodo-notification-progress');
            $progress.css('width', '100%').animate({ width: '0%' }, duration, 'linear');
        }

        notification.timeout = setTimeout(() => {
            this.dismiss(notification.id);
        }, duration);

        notification.startTime = Date.now();
    };

    /**
     * Pause timer
     */
    notify._pauseTimer = function(notification) {
        if (notification.timeout) {
            clearTimeout(notification.timeout);
            notification.timeout = null;

            // Calculate remaining time
            const elapsed = Date.now() - notification.startTime;
            notification.remainingTime = Math.max(0, notification.remainingTime - elapsed);

            // Pause progress bar
            if (this.config.showProgress) {
                notification.$element.find('.vodo-notification-progress').stop();
            }

            notification.paused = true;
        }
    };

    /**
     * Resume timer
     */
    notify._resumeTimer = function(notification) {
        if (notification.paused && notification.remainingTime > 0) {
            notification.paused = false;
            this._startTimer(notification);
        }
    };

    /**
     * Bind notification events
     */
    notify._bindNotificationEvents = function(notification) {
        const $el = notification.$element;

        // Close button
        $el.on('click', '.vodo-notification-close', () => {
            this.dismiss(notification.id);
        });

        // Pause on hover
        if (this.config.pauseOnHover) {
            $el.on('mouseenter', () => {
                this._pauseTimer(notification);
            });

            $el.on('mouseleave', () => {
                this._resumeTimer(notification);
            });
        }
    };

    // ============================================
    // Shorthand Methods
    // ============================================

    notify.success = function(message, options = {}) {
        return this.show({ ...options, message, type: 'success' });
    };

    notify.error = function(message, options = {}) {
        return this.show({ ...options, message, type: 'error', duration: options.duration || 8000 });
    };

    notify.warning = function(message, options = {}) {
        return this.show({ ...options, message, type: 'warning' });
    };

    notify.info = function(message, options = {}) {
        return this.show({ ...options, message, type: 'info' });
    };

    // ============================================
    // Dismiss
    // ============================================

    /**
     * Dismiss a notification
     * @param {string} id - Notification ID
     */
    notify.dismiss = function(id) {
        const index = this._notifications.findIndex(n => n.id === id);
        if (index === -1) return;

        const notification = this._notifications[index];

        // Clear timeout
        if (notification.timeout) {
            clearTimeout(notification.timeout);
        }

        // Animate out
        notification.$element.animate({ opacity: 0, transform: 'translateX(100%)' }, 200, () => {
            notification.$element.remove();

            // Remove from array
            this._notifications.splice(index, 1);

            // Update positions
            updatePositions();

            // Emit event
            if (Vodo.events) {
                Vodo.events.emit('notification:dismiss', id);
            }
        });
    };

    /**
     * Dismiss all notifications
     */
    notify.dismissAll = function() {
        [...this._notifications].forEach(notification => {
            this.dismiss(notification.id);
        });
    };

    // ============================================
    // Initialize
    // ============================================

    notify.init = function() {
        // Add notification styles if not present
        if (!document.getElementById('vodo-notification-styles')) {
            const styles = `
                <style id="vodo-notification-styles">
                    .vodo-notifications {
                        position: fixed;
                        z-index: 9999;
                        pointer-events: none;
                    }

                    .vodo-notifications-top-right { top: 0; right: 20px; }
                    .vodo-notifications-top-left { top: 0; left: 20px; }
                    .vodo-notifications-top-center { top: 0; left: 50%; transform: translateX(-50%); }
                    .vodo-notifications-bottom-right { bottom: 0; right: 20px; }
                    .vodo-notifications-bottom-left { bottom: 0; left: 20px; }
                    .vodo-notifications-bottom-center { bottom: 0; left: 50%; transform: translateX(-50%); }

                    .vodo-notification {
                        position: absolute;
                        right: 0;
                        width: 360px;
                        max-width: calc(100vw - 40px);
                        background: var(--bg-surface-1, #fff);
                        border-radius: var(--radius-lg, 8px);
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                        display: flex;
                        align-items: flex-start;
                        padding: 16px;
                        pointer-events: auto;
                        overflow: hidden;
                        border-left: 4px solid;
                    }

                    .vodo-notification-success { border-left-color: var(--color-success, #10b981); }
                    .vodo-notification-error { border-left-color: var(--color-error, #ef4444); }
                    .vodo-notification-warning { border-left-color: var(--color-warning, #f59e0b); }
                    .vodo-notification-info { border-left-color: var(--color-info, #3b82f6); }

                    .vodo-notification-icon {
                        flex-shrink: 0;
                        margin-right: 12px;
                    }

                    .vodo-notification-success .vodo-notification-icon { color: var(--color-success, #10b981); }
                    .vodo-notification-error .vodo-notification-icon { color: var(--color-error, #ef4444); }
                    .vodo-notification-warning .vodo-notification-icon { color: var(--color-warning, #f59e0b); }
                    .vodo-notification-info .vodo-notification-icon { color: var(--color-info, #3b82f6); }

                    .vodo-notification-content {
                        flex: 1;
                        min-width: 0;
                    }

                    .vodo-notification-title {
                        font-weight: 600;
                        margin-bottom: 4px;
                    }

                    .vodo-notification-message {
                        color: var(--text-secondary);
                        font-size: 0.875rem;
                        word-wrap: break-word;
                    }

                    .vodo-notification-actions {
                        display: flex;
                        gap: 8px;
                        margin-top: 12px;
                    }

                    .vodo-notification-action {
                        background: none;
                        border: none;
                        color: var(--color-primary, #3b82f6);
                        cursor: pointer;
                        font-size: 0.875rem;
                        font-weight: 500;
                        padding: 0;
                    }

                    .vodo-notification-action:hover {
                        text-decoration: underline;
                    }

                    .vodo-notification-close {
                        flex-shrink: 0;
                        background: none;
                        border: none;
                        font-size: 20px;
                        color: var(--text-secondary);
                        cursor: pointer;
                        padding: 0;
                        margin-left: 12px;
                        line-height: 1;
                    }

                    .vodo-notification-close:hover {
                        color: var(--text-primary);
                    }

                    .vodo-notification-progress {
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        height: 3px;
                        background: currentColor;
                        opacity: 0.3;
                    }

                    .dark .vodo-notification {
                        background: var(--bg-surface-1, #1f2937);
                    }

                    @media (max-width: 480px) {
                        .vodo-notification {
                            width: calc(100vw - 40px);
                        }
                    }
                </style>
            `;
            document.head.insertAdjacentHTML('beforeend', styles);
        }

        Vodo.log('Notifications module initialized');
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('notify', notify);

})(typeof window !== 'undefined' ? window : this);
