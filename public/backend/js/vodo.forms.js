/**
 * VODO Platform - AJAX Form Handler
 *
 * Provides automatic AJAX form submission with validation,
 * loading states, and error handling.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.forms.js');
        return;
    }

    // ============================================
    // Forms Configuration
    // ============================================

    const forms = {
        config: {
            selector: 'form:not([data-ajax="false"])',
            loadingClass: 'form-loading',
            errorClass: 'has-error',
            successClass: 'has-success',
            fieldErrorClass: 'field-error',
            errorMessageClass: 'error-message',
            scrollToError: true,
            scrollOffset: 100,
            resetOnSuccess: false,
            showSuccessMessage: true
        },

        // Active form submissions
        _submitting: new WeakMap()
    };

    // ============================================
    // Helper Functions
    // ============================================

    /**
     * Get submit button for form
     */
    function getSubmitButton($form) {
        return $form.find('[type="submit"], button:not([type]), .btn-submit').first();
    }

    /**
     * Serialize form to object
     */
    function serializeObject($form) {
        const data = {};
        $form.serializeArray().forEach(item => {
            if (item.name.endsWith('[]')) {
                const key = item.name.slice(0, -2);
                if (!data[key]) data[key] = [];
                data[key].push(item.value);
            } else {
                data[item.name] = item.value;
            }
        });
        return data;
    }

    // ============================================
    // Loading State
    // ============================================

    /**
     * Set form loading state
     */
    forms.setLoading = function(form, loading) {
        const $form = $(form);
        const $button = getSubmitButton($form);

        if (loading) {
            $form.addClass(this.config.loadingClass);
            $button
                .prop('disabled', true)
                .data('original-text', $button.html())
                .html(`
                    <span class="btn-spinner"></span>
                    <span class="btn-loading-text">Processing...</span>
                `);
        } else {
            $form.removeClass(this.config.loadingClass);
            const originalText = $button.data('original-text');
            if (originalText) {
                $button.html(originalText);
            }
            $button.prop('disabled', false);
        }
    };

    // ============================================
    // Error Handling
    // ============================================

    /**
     * Show validation errors
     */
    forms.showErrors = function(form, errors) {
        const $form = $(form);
        let firstError = null;

        Object.entries(errors).forEach(([field, messages]) => {
            // Handle nested field names (e.g., "user.email")
            const fieldName = field.replace(/\./g, '[').replace(/([^\[]*)$/, '$1]').replace(/\]$/, '');
            const $field = $form.find(`[name="${field}"], [name="${fieldName}"], [name="${field}[]"]`);

            if (!$field.length) {
                Vodo.warn(`Field not found for error: ${field}`);
                return;
            }

            const $group = $field.closest('.form-group, .form-field, .field-wrapper');
            const errorMessage = Array.isArray(messages) ? messages[0] : messages;

            // Add error class
            $group.addClass(this.config.errorClass);
            $field.addClass(this.config.fieldErrorClass);

            // Remove existing error message
            $group.find(`.${this.config.errorMessageClass}`).remove();

            // Add error message
            const $error = $(`<div class="${this.config.errorMessageClass}">${Vodo.utils.escapeHtml(errorMessage)}</div>`);
            $field.after($error);

            // Track first error for scrolling
            if (!firstError) {
                firstError = $group[0];
            }
        });

        // Scroll to first error
        if (firstError && this.config.scrollToError) {
            const rect = firstError.getBoundingClientRect();
            const scrollTop = window.pageYOffset + rect.top - this.config.scrollOffset;
            window.scrollTo({ top: scrollTop, behavior: 'smooth' });

            // Focus first error field
            $(firstError).find('input, select, textarea').first().focus();
        }
    };

    /**
     * Clear validation errors
     */
    forms.clearErrors = function(form) {
        const $form = $(form);

        $form.find(`.${this.config.errorClass}`).removeClass(this.config.errorClass);
        $form.find(`.${this.config.fieldErrorClass}`).removeClass(this.config.fieldErrorClass);
        $form.find(`.${this.config.successClass}`).removeClass(this.config.successClass);
        $form.find(`.${this.config.errorMessageClass}`).remove();
    };

    // ============================================
    // Form Submission
    // ============================================

    /**
     * Submit form via AJAX
     */
    forms.submit = async function(form, options = {}) {
        const $form = $(form);
        const formElement = $form[0];

        // Prevent double submission
        if (this._submitting.get(formElement)) {
            Vodo.log('Form already submitting');
            return;
        }

        // Get form attributes
        const url = options.url || $form.attr('action') || window.location.href;
        const method = (options.method || $form.attr('method') || 'POST').toUpperCase();

        // Mark as submitting
        this._submitting.set(formElement, true);

        // Show loading state
        this.setLoading(form, true);
        this.clearErrors(form);

        // Emit before event
        if (Vodo.events) {
            Vodo.events.emit('form:submit', form, { url, method });
        }

        try {
            // Prepare data
            let data;
            if ($form.find('input[type="file"]').length) {
                data = new FormData(formElement);
            } else {
                data = $form.serialize();
            }

            // Make request
            const response = await Vodo.ajax.request(url, {
                method,
                data,
                processData: !(data instanceof FormData),
                contentType: data instanceof FormData ? false : 'application/x-www-form-urlencoded',
                ...options.ajax
            });

            // Handle success
            this.handleSuccess(form, response, options);

            return response;

        } catch (error) {
            // Handle error
            this.handleError(form, error, options);
            throw error;

        } finally {
            this._submitting.delete(formElement);
            this.setLoading(form, false);

            // Emit complete event
            if (Vodo.events) {
                Vodo.events.emit('form:complete', form);
            }
        }
    };

    /**
     * Handle successful submission
     */
    forms.handleSuccess = function(form, response, options = {}) {
        const $form = $(form);

        // Emit success event
        if (Vodo.events) {
            Vodo.events.emit('form:success', form, response);
        }

        // Handle redirect
        if (response.redirect) {
            if (Vodo.router) {
                Vodo.router.navigate(response.redirect);
            } else {
                window.location.href = response.redirect;
            }
            return;
        }

        // Handle HTML replacement
        if (response.html) {
            const $target = response.target ? $(response.target) : $form;
            $target.html(response.html);
            if (Vodo.components) {
                Vodo.components.init($target);
            }
        }

        // Show success message
        if (response.message && this.config.showSuccessMessage && Vodo.notify) {
            Vodo.notify.success(response.message);
        }

        // Reset form if configured
        if (this.config.resetOnSuccess || options.reset) {
            form.reset();
        }

        // Clear unsaved flag
        $form.attr('data-unsaved', 'false');

        // Custom success callback
        if (options.onSuccess) {
            options.onSuccess(response);
        }
    };

    /**
     * Handle submission error
     */
    forms.handleError = function(form, error, options = {}) {
        // Emit error event
        if (Vodo.events) {
            Vodo.events.emit('form:error', form, error);
        }

        // Handle validation errors (422)
        if (error.status === 422) {
            const errors = error.responseJSON?.errors || error.responseJSON;
            if (errors) {
                this.showErrors(form, errors);
            }

            // Show validation message
            if (error.responseJSON?.message && Vodo.notify) {
                Vodo.notify.error(error.responseJSON.message);
            }

            return;
        }

        // Handle other errors
        const message = error.responseJSON?.message || 'An error occurred. Please try again.';
        if (Vodo.notify) {
            Vodo.notify.error(message);
        }

        // Custom error callback
        if (options.onError) {
            options.onError(error);
        }
    };

    // ============================================
    // Form Utilities
    // ============================================

    /**
     * Serialize form to FormData
     */
    forms.serialize = function(form) {
        return new FormData($(form)[0]);
    };

    /**
     * Serialize form to object
     */
    forms.toObject = function(form) {
        return serializeObject($(form));
    };

    /**
     * Fill form with data
     */
    forms.fill = function(form, data) {
        const $form = $(form);

        Object.entries(data).forEach(([name, value]) => {
            const $field = $form.find(`[name="${name}"]`);

            if (!$field.length) return;

            if ($field.is(':checkbox')) {
                $field.prop('checked', !!value);
            } else if ($field.is(':radio')) {
                $form.find(`[name="${name}"][value="${value}"]`).prop('checked', true);
            } else if ($field.is('select[multiple]')) {
                $field.val(Array.isArray(value) ? value : [value]);
            } else {
                $field.val(value);
            }
        });
    };

    /**
     * Reset form
     */
    forms.reset = function(form) {
        const $form = $(form);
        form.reset();
        this.clearErrors(form);
        $form.attr('data-unsaved', 'false');
    };

    /**
     * Track form changes
     */
    forms.trackChanges = function(form) {
        const $form = $(form);
        const initialData = this.toObject(form);

        $form.on('input change', 'input, select, textarea', () => {
            const currentData = this.toObject(form);
            const hasChanges = JSON.stringify(initialData) !== JSON.stringify(currentData);
            $form.attr('data-unsaved', hasChanges ? 'true' : 'false');
        });
    };

    // ============================================
    // Event Handlers
    // ============================================

    /**
     * Handle form submission
     */
    function handleSubmit(e) {
        const $form = $(e.currentTarget);

        // Skip if explicitly disabled
        if ($form.data('ajax') === false || $form.attr('data-ajax') === 'false') {
            return;
        }

        // Skip if form has file inputs without FormData support
        if ($form.find('input[type="file"]').length && !window.FormData) {
            return;
        }

        e.preventDefault();
        forms.submit(e.currentTarget);
    }

    /**
     * Handle field blur for inline validation
     */
    function handleFieldBlur(e) {
        const $field = $(e.target);
        const $form = $field.closest('form');

        // Skip if no validation needed
        if (!$field.attr('required') && !$field.data('validate')) {
            return;
        }

        // Basic validation
        const value = $field.val();
        const $group = $field.closest('.form-group, .form-field');

        // Required check
        if ($field.attr('required') && !value) {
            $group.addClass(forms.config.errorClass);
            $field.addClass(forms.config.fieldErrorClass);
        } else {
            $group.removeClass(forms.config.errorClass);
            $field.removeClass(forms.config.fieldErrorClass);
            $group.find(`.${forms.config.errorMessageClass}`).remove();
        }
    }

    // ============================================
    // Initialize
    // ============================================

    forms.init = function(container = document) {
        const $container = $(container);

        // Bind form submissions
        $container.on('submit', this.config.selector, handleSubmit);

        // Bind field blur for inline validation
        $container.on('blur', 'input[required], select[required], textarea[required]', handleFieldBlur);

        // Add loading spinner styles if not present
        if (!document.getElementById('vodo-form-styles')) {
            const styles = `
                <style id="vodo-form-styles">
                    .form-loading {
                        pointer-events: none;
                        opacity: 0.7;
                    }
                    .btn-spinner {
                        display: inline-block;
                        width: 16px;
                        height: 16px;
                        border: 2px solid currentColor;
                        border-top-color: transparent;
                        border-radius: 50%;
                        animation: btn-spin 0.6s linear infinite;
                        margin-right: 8px;
                    }
                    @keyframes btn-spin {
                        to { transform: rotate(360deg); }
                    }
                    .error-message {
                        color: var(--text-error, #ef4444);
                        font-size: 0.875rem;
                        margin-top: 4px;
                    }
                    .has-error input,
                    .has-error select,
                    .has-error textarea {
                        border-color: var(--border-error, #ef4444) !important;
                    }
                    .field-error {
                        border-color: var(--border-error, #ef4444) !important;
                    }
                </style>
            `;
            document.head.insertAdjacentHTML('beforeend', styles);
        }

        Vodo.log('Forms module initialized');
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('forms', forms);

})(typeof window !== 'undefined' ? window : this);
