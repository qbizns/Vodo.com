/**
 * VODO Platform - SPA Router
 *
 * Provides client-side routing with PJAX-style navigation,
 * preloading, caching, and history management.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.router.js');
        return;
    }

    // ============================================
    // Router Configuration
    // ============================================

    const router = {
        config: {
            container: '#pageContent',
            titleContainer: '#pageTitle',
            titleBarContainer: '.page-title-bar',
            headerActionsContainer: '.header-actions-container',
            tabsContainer: '#tabsContainer',
            preloadOnHover: false, // Disabled: only load on click, not hover
            preloadDelay: 150,
            scrollToTop: true,
            updateTitle: true,
            loadingClass: 'is-navigating',
            skeletonType: 'auto'
        },

        // State
        _isNavigating: false,
        _currentUrl: window.location.href,
        _preloadTimeout: null,
        _guards: [],
        _beforeCallbacks: [],
        _afterCallbacks: []
    };

    // ============================================
    // Helper Functions
    // ============================================

    /**
     * Check if URL should skip AJAX navigation
     */
    function shouldSkipAjax(href, element) {
        if (!href) return true;

        // Skip external links
        try {
            const url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return true;
        } catch (e) {
            return true;
        }

        // Skip anchors
        if (href.startsWith('#')) return true;

        // Skip javascript:
        if (href.startsWith('javascript:')) return true;

        // Skip mailto/tel
        if (href.startsWith('mailto:') || href.startsWith('tel:')) return true;

        // Skip downloads
        if (element && element.hasAttribute('download')) return true;

        // Skip target="_blank"
        if (element && element.target === '_blank') return true;

        // Skip data-no-ajax
        if (element && (element.dataset.noAjax !== undefined || element.dataset.ajax === 'false')) return true;

        // Skip logout links
        if (href.includes('/logout')) return true;

        return false;
    }

    /**
     * Extract page info from URL
     */
    function getPageInfoFromUrl(url) {
        const baseUrl = Vodo.config.baseUrl || '';
        let path = url.replace(baseUrl, '').replace(/^\/+/, '');

        // Remove query string
        const queryIndex = path.indexOf('?');
        if (queryIndex > -1) {
            path = path.substring(0, queryIndex);
        }

        // Default to dashboard
        if (!path || path === '/') {
            return {
                id: 'dashboard',
                label: 'Dashboard',
                icon: 'layoutDashboard'
            };
        }

        // Try to find matching nav item
        const navGroups = Vodo.config.navGroups || window.BackendConfig?.navGroups || [];
        for (const group of navGroups) {
            for (const item of (group.items || [])) {
                if (item.route === path || item.id === path) {
                    return {
                        id: item.id || path,
                        label: item.label || path,
                        icon: item.icon || 'file'
                    };
                }
            }
        }

        // Generate from path
        const segments = path.split('/');
        const lastSegment = segments[segments.length - 1];
        const label = lastSegment
            .replace(/[-_]/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());

        return {
            id: path.replace(/\//g, '-'),
            label,
            icon: 'file'
        };
    }

    /**
     * Load page-specific CSS
     */
    async function loadPageCss(cssNames) {
        if (!cssNames) return;

        const names = cssNames.split(',').map(s => s.trim()).filter(Boolean);
        const loadedCss = window._loadedCss || new Set(['style', 'rtl', 'skeleton']);
        window._loadedCss = loadedCss;

        const promises = names.map(name => {
            if (loadedCss.has(name)) return Promise.resolve();

            return new Promise((resolve) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = `/backend/css/pages/${name}.css`;
                link.onload = () => {
                    loadedCss.add(name);
                    resolve();
                };
                link.onerror = () => {
                    Vodo.warn(`Failed to load CSS: ${name}`);
                    resolve();
                };
                document.head.appendChild(link);
            });
        });

        return Promise.all(promises);
    }

    /**
     * Execute inline scripts from loaded content
     */
    function executeInlineScripts($container) {
        $container.find('script').each(function() {
            const script = document.createElement('script');
            if (this.src) {
                script.src = this.src;
            } else {
                script.textContent = this.textContent;
            }
            document.head.appendChild(script);
            document.head.removeChild(script);
        });
    }

    // ============================================
    // Navigation
    // ============================================

    /**
     * Navigate to URL
     * @param {string} url - Target URL
     * @param {Object} options - Navigation options
     * @returns {Promise}
     */
    router.navigate = async function(url, options = {}) {
        // Prevent double navigation
        if (this._isNavigating) {
            Vodo.log('Navigation already in progress');
            return;
        }

        // Check guards
        for (const guard of this._guards) {
            try {
                const allowed = await guard(url, options);
                if (allowed === false) {
                    Vodo.log('Navigation blocked by guard');
                    return;
                }
            } catch (e) {
                Vodo.error('Route guard error:', e);
            }
        }

        // Run before callbacks
        for (const callback of this._beforeCallbacks) {
            try {
                await callback(url, options);
            } catch (e) {
                Vodo.error('Before navigation callback error:', e);
            }
        }

        // Emit before event
        if (Vodo.events) {
            Vodo.events.emit('router:before', url, options);
        }

        this._isNavigating = true;

        const $container = $(this.config.container);
        const pageInfo = options.pageInfo || getPageInfoFromUrl(url);

        // Show skeleton
        let skeletonId = null;
        if (Vodo.skeleton && $container.length) {
            skeletonId = Vodo.skeleton.show($container, options.skeletonType || this.config.skeletonType);
        }

        // Add loading class to body
        document.body.classList.add(this.config.loadingClass);

        try {
            // Load fragment
            const fragment = await Vodo.ajax.fragment(url, {
                container: this.config.container
            });

            // Load CSS first
            if (fragment.css) {
                await loadPageCss(fragment.css);
            }

            // Hide skeleton
            if (skeletonId) {
                await Vodo.skeleton.hide(skeletonId);
            }

            // Update content
            $container.html(fragment.content);

            // Update page title
            if (this.config.updateTitle) {
                const title = fragment.title || fragment.header || pageInfo.label;
                const brandName = Vodo.config.brandName || 'VODO';
                document.title = `${title} - ${brandName}`;

                // Update page header
                let $titleBar = $(this.config.titleBarContainer);
                
                // Handle pages that should hide the title bar (e.g., Dashboard)
                if (fragment.hideTitleBar) {
                    if ($titleBar.length) {
                        $titleBar.css('display', 'none');
                    }
                } else {
                    // Create title bar if it doesn't exist (e.g., coming from Dashboard which hides it)
                    if (!$titleBar.length && (fragment.header || title)) {
                        const $contentArea = $('.flex-1.flex.flex-col');
                        if ($contentArea.length) {
                            $titleBar = $(`
                                <div class="page-title-bar flex items-center justify-between">
                                    <h2 id="pageTitle" class="page-title"></h2>
                                    <div class="header-actions-container flex items-center" style="gap: var(--spacing-3);"></div>
                                </div>
                            `);
                            // Insert before pageContent
                            $container.before($titleBar);
                            Vodo.log('Created missing page title bar');
                        }
                    }
                    
                    if ($titleBar.length) {
                        let $pageTitle = $titleBar.find(this.config.titleContainer);
                        if ($pageTitle.length) {
                            $pageTitle.text(fragment.header || title);
                        }

                        // Update header actions
                        let $headerActions = $titleBar.find(this.config.headerActionsContainer);
                        
                        // Create header actions container if missing
                        if (!$headerActions.length) {
                            $headerActions = $('<div class="header-actions-container flex items-center" style="gap: var(--spacing-3);"></div>');
                            $titleBar.append($headerActions);
                        }
                        
                        if (fragment.headerActions) {
                            $headerActions.html(fragment.headerActions).show();
                        } else {
                            $headerActions.empty().hide();
                        }

                        $titleBar.css('display', 'flex');
                    }
                }
            }

            // Update browser history
            if (options.pushState !== false) {
                const state = {
                    url,
                    pageId: pageInfo.id,
                    pageLabel: pageInfo.label,
                    pageIcon: pageInfo.icon
                };
                history.pushState(state, document.title, url);
            }

            // Update current URL
            this._currentUrl = url;

            // Update BackendConfig
            if (window.BackendConfig) {
                window.BackendConfig.currentPage = pageInfo.id;
                window.BackendConfig.currentPageLabel = pageInfo.label;
                window.BackendConfig.currentPageIcon = pageInfo.icon;
            }

            // Update sidebar active state
            $('.nav-item').removeClass('active');
            $(`.nav-item[data-nav-id="${pageInfo.id}"]`).addClass('active');

            // Scroll to top
            if (this.config.scrollToTop) {
                $container.scrollTop(0);
                window.scrollTo(0, 0);
            }

            // Execute inline scripts
            executeInlineScripts($container);

            // Initialize components
            if (Vodo.components) {
                Vodo.components.init($container);
            }

            // Run after callbacks
            for (const callback of this._afterCallbacks) {
                try {
                    await callback(url, fragment, options);
                } catch (e) {
                    Vodo.error('After navigation callback error:', e);
                }
            }

            // Emit after event
            if (Vodo.events) {
                Vodo.events.emit('router:after', url, fragment, options);
            }

            Vodo.log('Navigation complete:', url);

        } catch (error) {
            Vodo.error('Navigation error:', error);

            // Hide skeleton on error
            if (skeletonId) {
                await Vodo.skeleton.hide(skeletonId);
            }

            // Show error message (CSP-compliant: no inline event handlers)
            $container.html(`
                <div class="error-state" style="padding: var(--spacing-6); text-align: center;">
                    <div style="color: var(--text-error); margin-bottom: var(--spacing-4);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h3 style="margin-bottom: var(--spacing-2);">Failed to load page</h3>
                    <p style="color: var(--text-secondary); margin-bottom: var(--spacing-4);">
                        ${error.status === 404 ? 'Page not found' : 'An error occurred'}
                    </p>
                    <button data-action="router-refresh" class="btn-primary">
                        Try Again
                    </button>
                    <button data-action="router-hard-reload" class="btn-secondary" style="margin-left: var(--spacing-2);">
                        Reload Page
                    </button>
                </div>
            `);

            // Emit error event
            if (Vodo.events) {
                Vodo.events.emit('router:error', url, error);
            }

        } finally {
            this._isNavigating = false;
            document.body.classList.remove(this.config.loadingClass);
        }
    };

    /**
     * Preload a page
     */
    router.preload = function(url) {
        if (Vodo.ajax) {
            Vodo.ajax.preload(url);
        }
    };

    /**
     * Refresh current page
     */
    router.refresh = function() {
        // Clear cache for current URL
        if (Vodo.ajax) {
            Vodo.ajax.invalidate(this._currentUrl);
        }
        return this.navigate(this._currentUrl, { pushState: false });
    };

    /**
     * Force hard reload
     */
    router.hardReload = function() {
        window.location.reload();
    };

    /**
     * Go back
     */
    router.back = function() {
        history.back();
    };

    /**
     * Go forward
     */
    router.forward = function() {
        history.forward();
    };

    // ============================================
    // Route Guards & Hooks
    // ============================================

    /**
     * Add route guard
     */
    router.guard = function(callback) {
        this._guards.push(callback);
        return () => {
            const index = this._guards.indexOf(callback);
            if (index > -1) this._guards.splice(index, 1);
        };
    };

    /**
     * Add before navigation callback
     */
    router.before = function(callback) {
        this._beforeCallbacks.push(callback);
        return () => {
            const index = this._beforeCallbacks.indexOf(callback);
            if (index > -1) this._beforeCallbacks.splice(index, 1);
        };
    };

    /**
     * Add after navigation callback
     */
    router.after = function(callback) {
        this._afterCallbacks.push(callback);
        return () => {
            const index = this._afterCallbacks.indexOf(callback);
            if (index > -1) this._afterCallbacks.splice(index, 1);
        };
    };

    // ============================================
    // Current Route Info
    // ============================================

    /**
     * Get current route info
     */
    router.current = function() {
        return {
            url: this._currentUrl,
            path: window.location.pathname,
            query: Vodo.utils.parseQuery(window.location.search),
            hash: window.location.hash,
            ...getPageInfoFromUrl(this._currentUrl)
        };
    };

    /**
     * Check if navigating
     */
    router.isNavigating = function() {
        return this._isNavigating;
    };

    // ============================================
    // Event Handlers
    // ============================================

    /**
     * Handle link clicks
     */
    function handleLinkClick(e) {
        const $link = $(e.currentTarget);
        const href = $link.attr('href');

        if (shouldSkipAjax(href, e.currentTarget)) {
            return;
        }

        e.preventDefault();

        // Get page info from link data or nav item
        const pageInfo = {
            id: $link.data('nav-id') || $link.data('page-id'),
            label: $link.data('label') || $link.find('span').last().text() || $link.text().trim(),
            icon: $link.data('icon') || 'file'
        };

        router.navigate(href, { pageInfo });
    }

    /**
     * Handle preload on hover
     */
    function handleLinkHover(e) {
        if (!router.config.preloadOnHover) return;

        const href = $(e.currentTarget).attr('href');
        if (shouldSkipAjax(href, e.currentTarget)) return;

        clearTimeout(router._preloadTimeout);
        router._preloadTimeout = setTimeout(() => {
            router.preload(href);
        }, router.config.preloadDelay);
    }

    /**
     * Handle popstate (browser back/forward)
     */
    function handlePopState(e) {
        if (e.state && e.state.url) {
            router.navigate(e.state.url, {
                pushState: false,
                pageInfo: {
                    id: e.state.pageId,
                    label: e.state.pageLabel,
                    icon: e.state.pageIcon
                }
            });
        }
    }

    // ============================================
    // Initialize
    // ============================================

    router.init = function() {
        // Set initial history state
        const pageInfo = getPageInfoFromUrl(window.location.href);
        history.replaceState({
            url: window.location.href,
            pageId: pageInfo.id,
            pageLabel: pageInfo.label,
            pageIcon: pageInfo.icon
        }, document.title, window.location.href);

        // Bind link clicks
        $(document).on('click', 'a[href]', handleLinkClick);

        // Bind preload on hover
        $(document).on('mouseenter', 'a[href]', handleLinkHover);
        $(document).on('mouseleave', 'a[href]', () => {
            clearTimeout(router._preloadTimeout);
        });

        // Bind popstate
        window.addEventListener('popstate', handlePopState);

        // Prevent navigation away with unsaved changes
        window.addEventListener('beforeunload', (e) => {
            const forms = document.querySelectorAll('form[data-unsaved="true"]');
            if (forms.length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // CSP-compliant event delegation for router actions
        $(document).on('click', '[data-action="router-refresh"]', function(e) {
            e.preventDefault();
            router.refresh();
        });

        $(document).on('click', '[data-action="router-hard-reload"]', function(e) {
            e.preventDefault();
            router.hardReload();
        });

        Vodo.log('Router initialized');
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('router', router);

    // Also expose navigateToPage for backward compatibility
    window.navigateToPage = function(url, pageId, pageLabel, pageIcon, pushState = true) {
        router.navigate(url, {
            pushState,
            pageInfo: { id: pageId, label: pageLabel, icon: pageIcon }
        });
    };

})(typeof window !== 'undefined' ? window : this);
