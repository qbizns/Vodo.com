@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function() {
    'use strict';

    /**
     * Settings Page JavaScript
     * Handles AJAX form submission and navigation
     */

    // Handle form submission via AJAX
    function initSettingsForm() {
        const forms = document.querySelectorAll('.settings-form');
        console.log('Settings forms found:', forms.length);
        
        forms.forEach(function(form) {
            console.log('Attaching submit handler to form:', form.id, 'action:', form.action);
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Form submitted:', form.id);
                
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Debug: log form data
                console.log('Form action:', form.action);
                for (let pair of formData.entries()) {
                    console.log('Form data:', pair[0], '=', pair[1]);
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Saving...</span>';
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(function(response) {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Error response:', text);
                            throw new Error('Server error: ' + response.status);
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    console.log('Response data:', data);
                    if (data.success) {
                        showNotification('success', data.message || 'Settings saved successfully');
                    } else {
                        showNotification('error', data.message || 'Failed to save settings');
                    }
                })
                .catch(function(error) {
                    console.error('Error saving settings:', error);
                    showNotification('error', 'An error occurred while saving settings');
                })
                .finally(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        });
    }

    /**
     * Show a notification message
     */
    function showNotification(type, message) {
        // Remove existing notifications
        const existingAlerts = document.querySelectorAll('.settings-content .alert');
        existingAlerts.forEach(function(alert) {
            alert.remove();
        });

        // Create new notification
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type;
        
        const iconName = type === 'success' ? 'checkCircle' : 'alertCircle';
        alertDiv.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                ${type === 'success' 
                    ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                    : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
                }
            </svg>
            <span>${message}</span>
        `;

        // Insert at the top of settings content
        const settingsContent = document.getElementById('settingsContent');
        if (settingsContent) {
            settingsContent.insertBefore(alertDiv, settingsContent.firstChild);
        }

        // Auto-remove after 5 seconds
        setTimeout(function() {
            alertDiv.remove();
        }, 5000);
    }

    /**
     * Handle navigation clicks for AJAX loading (optional enhancement)
     */
    function initNavigation() {
        const navItems = document.querySelectorAll('.settings-nav-item');
        
        navItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                // Allow normal navigation for now
                // Could be enhanced to load content via AJAX
            });
        });
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        initSettingsForm();
        initNavigation();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
