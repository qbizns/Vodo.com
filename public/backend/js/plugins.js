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
        Vodo.modals.confirm('Are you sure you want to deactivate this plugin?', {
            title: 'Deactivate Plugin',
            confirmText: 'Deactivate',
            confirmClass: 'btn-warning'
        }).then(function(confirmed) {
            if (confirmed) {
                performPluginAction(slug, 'deactivate', 'Deactivating');
            }
        });
    };

    /**
     * Show update confirmation modal
     * Gets plugin data from the DOM
     */
    window.updatePlugin = function (slug) {
        // Get plugin info from the card/row
        var $row = $('[data-plugin-slug="' + slug + '"]').length 
            ? $('[data-plugin-slug="' + slug + '"]') 
            : $('tr').filter(function() { return $(this).find('[onclick*="' + slug + '"]').length > 0; });
        
        var pluginName = $row.find('.plugin-name, .plugin-name-link').first().text().trim() || slug;
        var currentVersion = $row.find('.plugin-version').first().text().replace('v', '').trim() || '?';
        var latestVersion = $row.find('.update-available').first().text().trim() || '?';
        
        showUpdateConfirmationModal({
            slug: slug,
            name: pluginName,
            currentVersion: currentVersion,
            latestVersion: latestVersion,
            onConfirm: function() {
                performPluginAction(slug, 'update', 'Updating');
            }
        });
    };

    window.uninstallPlugin = function (slug, name) {
        Vodo.modals.confirm('Are you sure you want to uninstall <strong>' + Vodo.utils.escapeHtml(name) + '</strong>?<br><br>This will remove all plugin data and cannot be undone.', {
            title: 'Uninstall Plugin',
            confirmText: 'Uninstall',
            confirmClass: 'btn-danger'
        }).then(function(confirmed) {
            if (confirmed) {
                performPluginAction(slug, 'destroy', 'Uninstalling', 'DELETE');
            }
        });
    };

    window.bulkAction = function (action) {
        if (window._pluginState.selectedPlugins.length === 0) {
            Vodo.notify.warning('Please select plugins first');
            return;
        }

        var count = window._pluginState.selectedPlugins.length;
        var actionText = action.charAt(0).toUpperCase() + action.slice(1);
        
        Vodo.modals.confirm('Are you sure you want to ' + action + ' ' + count + ' selected plugin(s)?', {
            title: actionText + ' Plugins',
            confirmText: actionText,
            confirmClass: action === 'delete' ? 'btn-danger' : 'btn-primary'
        }).then(function(confirmed) {
            if (!confirmed) return;
            
            showLoading('Processing...');

            $.ajax({
                url: window.BackendConfig.baseUrl + '/system/plugins/bulk',
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
                    Vodo.notify.success(response.message || 'Operation completed');
                    // Hard reload for activate/deactivate to ensure all plugin changes take effect
                    if (action === 'activate' || action === 'deactivate') {
                        setTimeout(function() {
                            location.reload(true);
                        }, 500);
                    } else if (window.Vodo && Vodo.router) {
                        Vodo.router.refresh();
                    } else {
                        location.reload();
                    }
                },
                error: function (xhr) {
                    hideLoading();
                    Vodo.notify.error(xhr.responseJSON?.message || 'An error occurred');
                }
            });
        });
    };
    
    /**
     * Show the update confirmation modal with checkboxes
     */
    function showUpdateConfirmationModal(options) {
        var warningItems = [
            'A backup of your data will be created automatically',
            'The plugin will be temporarily deactivated during update',
            'Database migrations will run automatically'
        ];
        
        var warningHtml = '<div class="update-warning-box">' +
            '<div class="warning-header">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>' +
                '<span>Before updating:</span>' +
            '</div>' +
            '<ul class="warning-list">';
        
        warningItems.forEach(function(item) {
            warningHtml += '<li>' + item + '</li>';
        });
        warningHtml += '</ul></div>';
        
        var versionHtml = '<p class="update-version-info">' +
            'You are about to update <strong>' + Vodo.utils.escapeHtml(options.name) + '</strong> ' +
            'from <strong>v' + Vodo.utils.escapeHtml(options.currentVersion) + '</strong> ' +
            'to <strong>v' + Vodo.utils.escapeHtml(options.latestVersion) + '</strong>' +
            '</p>';
        
        var checkboxesHtml = '<div class="update-checkboxes">' +
            '<label class="checkbox-item">' +
                '<input type="checkbox" id="updateConfirmBackup" class="form-checkbox">' +
                '<span>I have backed up my database</span>' +
            '</label>' +
            '<label class="checkbox-item">' +
                '<input type="checkbox" id="updateConfirmDowntime" class="form-checkbox">' +
                '<span>I understand this may cause temporary downtime</span>' +
            '</label>' +
        '</div>';
        
        var contentHtml = versionHtml + warningHtml + checkboxesHtml;
        
        var modalId = Vodo.modals.open({
            title: 'Update ' + options.name,
            content: contentHtml,
            size: 'md',
            class: 'update-confirmation-modal',
            footer: '<button type="button" class="btn-secondary" data-modal-close>Cancel</button>' +
                    '<button type="button" class="btn-primary" id="updateNowBtn" disabled>' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg> ' +
                        'Update Now' +
                    '</button>',
            onOpen: function(id, $modal) {
                var $confirmBtn = $modal.find('#updateNowBtn');
                var $checkbox1 = $modal.find('#updateConfirmBackup');
                var $checkbox2 = $modal.find('#updateConfirmDowntime');
                
                function checkCheckboxes() {
                    $confirmBtn.prop('disabled', !($checkbox1.is(':checked') && $checkbox2.is(':checked')));
                }
                
                $checkbox1.on('change', checkCheckboxes);
                $checkbox2.on('change', checkCheckboxes);
                
                $confirmBtn.on('click', function() {
                    Vodo.modals.close(id);
                    if (typeof options.onConfirm === 'function') {
                        options.onConfirm();
                    }
                });
            }
        });
    }

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

        // Use Vodo router for SPA navigation
        if (window.Vodo && Vodo.router) {
            Vodo.router.navigate(url.toString(), {
                pageInfo: {
                    id: 'system/plugins',
                    label: 'Installed Plugins',
                    icon: 'plug'
                }
            });
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
                    Vodo.notify.success(response.message || 'Success');
                    setTimeout(function () {
                        // Hard reload for activate/deactivate to ensure all plugin changes take effect
                        if (action === 'activate' || action === 'deactivate') {
                            location.reload(true);
                        } else if (window.Vodo && Vodo.router) {
                            Vodo.router.refresh();
                        } else {
                            location.reload();
                        }
                    }, 500);
                }
            },
            error: function (xhr) {
                hideLoading();
                Vodo.notify.error(xhr.responseJSON?.message || 'An error occurred');
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
