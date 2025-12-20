/**
 * Plugin Management Scripts
 * Handles interactive features for the Plugins page
 */

(function ($) {
    'use strict';

    // State
    window._pluginState = window._pluginState || {
        selectedPlugins: [],
        searchTimeout: null
    };

    // ============================================
    // Global Functions (Exposed for inline calls)
    // ============================================

    window.toggleActionMenu = function (btn) {
        var $btn = $(btn);
        var $dropdown = $btn.closest('.actions-dropdown');
        var $menu = $dropdown.find('.action-menu');

        // Close all other menus first
        $('.action-menu').not($menu).removeClass('show');

        // Toggle this menu
        $menu.toggleClass('show');
    };

    window.activatePlugin = function (slug) {
        performPluginAction(slug, 'activate', 'Activating');
    };

    window.deactivatePlugin = function (slug) {
        if (!confirm('Are you sure you want to deactivate this plugin?')) return;
        performPluginAction(slug, 'deactivate', 'Deactivating');
    };

    window.updatePlugin = function (slug) {
        if (!confirm('Are you sure you want to update this plugin?')) return;
        performPluginAction(slug, 'update', 'Updating');
    };

    window.uninstallPlugin = function (slug, name) {
        if (!confirm('Are you sure you want to uninstall ' + name + '?')) return;
        performPluginAction(slug, 'destroy', 'Uninstalling', 'DELETE');
    };

    window.bulkAction = function (action) {
        if (window._pluginState.selectedPlugins.length === 0) {
            alert('Please select plugins first');
            return;
        }

        if (!confirm('Are you sure you want to ' + action + ' the selected plugins?')) return;

        showLoading('Processing...');

        $.ajax({
            url: window.BackendConfig.baseUrl + '/system/plugins/bulk', // Use robust URL construction
            method: 'POST',
            data: {
                action: action,
                plugins: window._pluginState.selectedPlugins
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function (response) {
                hideLoading();
                alert('Operation completed');
                location.reload();
            },
            error: function (xhr) {
                hideLoading();
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    };

    window.toggleSelectAll = function (checked) {
        $('.plugin-checkbox:not(:disabled)').prop('checked', checked);
        updateSelectedPlugins();
    };

    window.applyFilters = function () {
        var url = new URL(window.location.href);

        var search = $('#pluginSearch').val() || '';
        var status = $('#statusFilter').val() || '';
        var category = $('#categoryFilter').val() || '';

        if (search.trim()) {
            url.searchParams.set('search', search.trim());
        } else {
            url.searchParams.delete('search');
        }

        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }

        if (category) {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }

        url.searchParams.delete('page');

        // Use global navigation if available (replaces location.href)
        if (typeof window.navigateToPage === 'function') {
            window.navigateToPage(url.toString(), 'system/plugins', 'Installed Plugins', 'plug');
        } else {
            window.location.href = url.toString();
        }
    };

    window.setViewMode = function (mode) {
        var $container = $('#pluginsList, .plugins-grid, .marketplace-grid');

        if (mode === 'grid') {
            $container.addClass('grid-view').removeClass('list-view');
        } else {
            $container.addClass('list-view').removeClass('grid-view');
        }

        localStorage.setItem('plugins.viewMode', mode);
    };

    // ============================================
    // Helper Functions
    // ============================================

    function performPluginAction(slug, action, loadingText, method) {
        method = method || 'POST';
        showLoading(loadingText + '...');

        $.ajax({
            url: window.BackendConfig.baseUrl + '/system/plugins/' + slug + '/' + (action === 'destroy' ? '' : action),
            method: method,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function (response) {
                hideLoading();
                if (response.success) {
                    alert(response.message || 'Success');
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                }
            },
            error: function (xhr) {
                hideLoading();
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    }

    function updateSelectedPlugins() {
        window._pluginState.selectedPlugins = [];
        $('.plugin-checkbox:checked').each(function () {
            window._pluginState.selectedPlugins.push($(this).val());
        });

        var count = window._pluginState.selectedPlugins.length;
        $('#selectedCount').text(count + ' selected');

        if (count > 0) {
            $('#bulkActionsBar').slideDown(200);
        } else {
            $('#bulkActionsBar').slideUp(200);
        }
    }

    function showLoading(text) {
        text = text || 'Loading...';
        if ($('#loadingOverlay').length === 0) {
            $('body').append(
                '<div id="loadingOverlay" class="loading-overlay">' +
                '<div class="loading-content">' +
                '<div class="spinner"></div>' +
                '<span class="loading-text">' + text + '</span>' +
                '</div>' +
                '</div>'
            );
        } else {
            $('#loadingOverlay .loading-text').text(text);
            $('#loadingOverlay').show();
        }
    }

    function hideLoading() {
        $('#loadingOverlay').hide();
    }

    // ============================================
    // Event Handlers
    // ============================================

    // Initialize listeners immediately - using delegation for PJAX support
    function initPluginListeners() {
        // Remove previous handlers to avoid duplicates if re-run
        $(document).off('.pluginPage');

        // Action menu button click
        $(document).on('click.pluginPage', '.action-menu-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.toggleActionMenu(this);
        });

        // Close menu when clicking outside
        $(document).on('click.pluginPage', function (e) {
            if (!$(e.target).closest('.actions-dropdown').length) {
                $('.action-menu').removeClass('show');
            }
        });

        // Prevent menu from closing when clicking inside
        $(document).on('click.pluginPage', '.action-menu', function (e) {
            e.stopPropagation();
        });

        // Select all checkboxes
        $(document).on('change.pluginPage', '#selectAllHeader, #selectAllPlugins', function () {
            window.toggleSelectAll(this.checked);
        });

        // Individual plugin checkboxes
        $(document).on('change.pluginPage', '.plugin-checkbox', function () {
            updateSelectedPlugins();
        });

        // Search input with debounce
        $(document).on('input.pluginPage', '#pluginSearch', function () {
            clearTimeout(window._pluginState.searchTimeout);
            window._pluginState.searchTimeout = setTimeout(function () {
                window.applyFilters();
            }, 400);
        });

        // Handle Enter key in search
        $(document).on('keydown.pluginPage', '#pluginSearch', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(window._pluginState.searchTimeout);
                window.applyFilters();
            }
        });

        // Filter selects
        $(document).on('change.pluginPage', '#statusFilter, #categoryFilter', function () {
            window.applyFilters();
        });

        // View mode select
        $(document).on('change.pluginPage', '#viewMode', function () {
            window.setViewMode(this.value);
        });

        console.log('[Plugins] Global listeners initialized');
    }

    // Run initialization
    initPluginListeners();

    // Re-run setup that depends on DOM presence when navigate is done?
    // Not needed for delegation, but things like "setting initial view mode" need to happen repeatedly.
    // We can hook into a global event if one existed, or just rely on the fact that inline scripts
    // in the view will assume these globals exist and trigger any immediate needs.

})(jQuery);
