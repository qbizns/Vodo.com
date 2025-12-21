/**
 * VODO Platform - AJAX Manager
 *
 * Provides centralized AJAX handling with caching, deduplication,
 * retry logic, and compression support.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.ajax.js');
        return;
    }

    // ============================================
    // AJAX Configuration
    // ============================================

    const ajax = {
        config: {
            timeout: 30000,
            retries: 3,
            retryDelay: 1000,
            cache: true,
            cacheTTL: 5 * 60 * 1000, // 5 minutes
            compress: true,
            headers: {}
        },

        // Pending requests for deduplication
        _pending: new Map(),

        // Response cache
        _cache: new Map(),

        // Request interceptors
        _interceptors: {
            request: [],
            response: []
        }
    };

    // ============================================
    // Helper Functions
    // ============================================

    /**
     * Generate cache key from URL and options
     */
    function getCacheKey(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const data = options.data ? JSON.stringify(options.data) : '';
        return `${method}:${url}:${data}`;
    }

    /**
     * Check if response is cacheable
     */
    function isCacheable(method, options) {
        if (options.cache === false) return false;
        if (!ajax.config.cache) return false;
        return method === 'GET';
    }

    /**
     * Get from cache if valid
     */
    function getFromCache(key) {
        const cached = ajax._cache.get(key);
        if (!cached) return null;

        if (Date.now() > cached.expires) {
            ajax._cache.delete(key);
            return null;
        }

        return cached.data;
    }

    /**
     * Store in cache
     */
    function storeInCache(key, data, ttl = ajax.config.cacheTTL) {
        ajax._cache.set(key, {
            data,
            expires: Date.now() + ttl,
            timestamp: Date.now()
        });
    }

    /**
     * Get default headers
     */
    function getDefaultHeaders() {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json, text/html, */*'
        };

        // Add CSRF token
        if (Vodo.config.csrfToken) {
            headers['X-CSRF-TOKEN'] = Vodo.config.csrfToken;
        }

        // Note: Accept-Encoding is a forbidden header that browsers manage automatically.
        // We don't need to set it - the browser handles compression negotiation.

        // Merge custom headers
        Object.assign(headers, ajax.config.headers);

        return headers;
    }

    /**
     * Sleep helper for retry delay
     */
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // ============================================
    // Core Request Function
    // ============================================

    /**
     * Make an AJAX request
     * @param {string} url - Request URL
     * @param {Object} options - Request options
     * @returns {Promise}
     */
    ajax.request = async function(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const cacheKey = getCacheKey(url, options);

        // Check cache for GET requests
        if (method === 'GET' && isCacheable(method, options)) {
            const cached = getFromCache(cacheKey);
            if (cached) {
                Vodo.log('AJAX cache hit:', url);
                return Promise.resolve(cached);
            }
        }

        // Check for duplicate pending request
        if (ajax._pending.has(cacheKey) && method === 'GET') {
            Vodo.log('AJAX dedup:', url);
            return ajax._pending.get(cacheKey);
        }

        // Build headers
        const headers = {
            ...getDefaultHeaders(),
            ...options.headers
        };

        // Build jQuery AJAX options
        const ajaxOptions = {
            url,
            type: method,
            headers,
            timeout: options.timeout || ajax.config.timeout,
            dataType: options.dataType || 'json',
            processData: options.processData !== false,
            contentType: options.contentType !== false ? options.contentType : false
        };

        // Add data
        if (options.data) {
            if (options.data instanceof FormData) {
                ajaxOptions.data = options.data;
                ajaxOptions.processData = false;
                ajaxOptions.contentType = false;
            } else if (method !== 'GET') {
                ajaxOptions.data = options.data;
                if (!options.contentType) {
                    ajaxOptions.contentType = 'application/json';
                    if (typeof options.data === 'object') {
                        ajaxOptions.data = JSON.stringify(options.data);
                    }
                }
            } else {
                ajaxOptions.data = options.data;
            }
        }

        // Run request interceptors
        for (const interceptor of ajax._interceptors.request) {
            try {
                const result = await interceptor(ajaxOptions);
                if (result === false) {
                    return Promise.reject(new Error('Request cancelled by interceptor'));
                }
            } catch (e) {
                Vodo.error('Request interceptor error:', e);
            }
        }

        // Emit before event
        if (Vodo.events) {
            Vodo.events.emit('ajax:before', { url, method, options: ajaxOptions });
        }

        // Create promise with retry logic
        const makeRequest = async (attempt = 1) => {
            try {
                const response = await $.ajax(ajaxOptions);

                // Run response interceptors
                let processedResponse = response;
                for (const interceptor of ajax._interceptors.response) {
                    try {
                        const result = await interceptor(processedResponse, ajaxOptions);
                        if (result !== undefined) {
                            processedResponse = result;
                        }
                    } catch (e) {
                        Vodo.error('Response interceptor error:', e);
                    }
                }

                // Cache successful GET responses
                if (method === 'GET' && isCacheable(method, options)) {
                    storeInCache(cacheKey, processedResponse, options.cacheTTL);
                }

                // Emit success event
                if (Vodo.events) {
                    Vodo.events.emit('ajax:success', { url, method, response: processedResponse });
                }

                return processedResponse;

            } catch (error) {
                // Don't retry on certain errors
                const status = error.status || 0;
                const noRetry = [400, 401, 403, 404, 422].includes(status);

                if (noRetry || attempt >= (options.retries || ajax.config.retries)) {
                    // Emit error event
                    if (Vodo.events) {
                        Vodo.events.emit('ajax:error', { url, method, error });
                    }

                    throw error;
                }

                // Exponential backoff
                const delay = (options.retryDelay || ajax.config.retryDelay) * Math.pow(2, attempt - 1);
                Vodo.log(`AJAX retry ${attempt} for ${url} in ${delay}ms`);
                await sleep(delay);

                return makeRequest(attempt + 1);
            }
        };

        // Store promise for deduplication
        const promise = makeRequest()
            .finally(() => {
                ajax._pending.delete(cacheKey);

                // Emit complete event
                if (Vodo.events) {
                    Vodo.events.emit('ajax:complete', { url, method });
                }
            });

        if (method === 'GET') {
            ajax._pending.set(cacheKey, promise);
        }

        return promise;
    };

    // ============================================
    // HTTP Method Shortcuts
    // ============================================

    /**
     * GET request
     */
    ajax.get = function(url, options = {}) {
        return this.request(url, { ...options, method: 'GET' });
    };

    /**
     * POST request
     */
    ajax.post = function(url, data = {}, options = {}) {
        return this.request(url, { ...options, method: 'POST', data });
    };

    /**
     * PUT request
     */
    ajax.put = function(url, data = {}, options = {}) {
        return this.request(url, { ...options, method: 'PUT', data });
    };

    /**
     * PATCH request
     */
    ajax.patch = function(url, data = {}, options = {}) {
        return this.request(url, { ...options, method: 'PATCH', data });
    };

    /**
     * DELETE request
     */
    ajax.delete = function(url, options = {}) {
        return this.request(url, { ...options, method: 'DELETE' });
    };

    // ============================================
    // Fragment Loading
    // ============================================

    /**
     * Load HTML fragment (optimized for PJAX)
     * Uses X-PJAX header only to get HTML wrapped in #pjax-content
     */
    ajax.fragment = function(url, options = {}) {
        return this.request(url, {
            ...options,
            dataType: 'html',
            headers: {
                ...options.headers,
                'X-PJAX': 'true',
                // Note: We intentionally don't send X-Fragment-Only to get HTML response
                // JSON responses have issues with escaped content and script execution
                'X-PJAX-Container': options.container || '#pageContent'
            }
        }).then(response => {
            // Parse HTML fragment response
            if (typeof response === 'string') {
                const $temp = $('<div>').html(response);
                const $pjax = $temp.find('#pjax-content');

                if ($pjax.length) {
                    const $headerActions = $pjax.find('#pjax-header-actions');
                    const headerActionsHtml = $headerActions.html();
                    $headerActions.remove();

                    return {
                        content: $pjax.html(),
                        title: $pjax.data('page-title'),
                        header: $pjax.data('page-header'),
                        headerActions: headerActionsHtml,
                        css: $pjax.data('require-css'),
                        hideTitleBar: $pjax.data('hide-title-bar') === true || $pjax.data('hide-title-bar') === 'true',
                        raw: response
                    };
                }

                return { content: response, raw: response };
            }

            return { content: response, raw: response };
        });
    };

    // ============================================
    // Form Submission
    // ============================================

    /**
     * Submit form via AJAX
     */
    ajax.submitForm = function(form, options = {}) {
        const $form = $(form);
        const url = options.url || $form.attr('action') || window.location.href;
        const method = (options.method || $form.attr('method') || 'POST').toUpperCase();

        // Serialize form data
        let data;
        if ($form.find('input[type="file"]').length) {
            data = new FormData(form);
        } else {
            data = $form.serialize();
        }

        return this.request(url, {
            ...options,
            method,
            data,
            processData: !(data instanceof FormData),
            contentType: data instanceof FormData ? false : 'application/x-www-form-urlencoded'
        });
    };

    // ============================================
    // File Upload
    // ============================================

    /**
     * Upload files with progress
     */
    ajax.upload = function(url, files, options = {}) {
        const formData = new FormData();

        // Add files
        if (files instanceof FileList || Array.isArray(files)) {
            Array.from(files).forEach((file, i) => {
                formData.append(options.fieldName || 'files[]', file);
            });
        } else if (files instanceof File) {
            formData.append(options.fieldName || 'file', files);
        } else {
            formData.append(options.fieldName || 'file', files);
        }

        // Add additional data
        if (options.data) {
            Object.entries(options.data).forEach(([key, value]) => {
                formData.append(key, value);
            });
        }

        return new Promise((resolve, reject) => {
            $.ajax({
                url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    ...getDefaultHeaders(),
                    ...options.headers
                },
                xhr: function() {
                    const xhr = new XMLHttpRequest();

                    // Upload progress
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable && options.onProgress) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            options.onProgress(percent, e.loaded, e.total);
                        }
                    });

                    return xhr;
                },
                success: resolve,
                error: reject
            });
        });
    };

    // ============================================
    // Batch Requests
    // ============================================

    /**
     * Execute multiple requests in parallel
     */
    ajax.batch = function(requests) {
        const promises = requests.map(req => {
            if (typeof req === 'string') {
                return this.get(req);
            }
            return this.request(req.url, req);
        });

        return Promise.allSettled(promises);
    };

    /**
     * Execute requests sequentially
     */
    ajax.sequence = async function(requests) {
        const results = [];

        for (const req of requests) {
            try {
                const result = typeof req === 'string'
                    ? await this.get(req)
                    : await this.request(req.url, req);
                results.push({ status: 'fulfilled', value: result });
            } catch (error) {
                results.push({ status: 'rejected', reason: error });
            }
        }

        return results;
    };

    // ============================================
    // Cache Management
    // ============================================

    /**
     * Clear cache
     */
    ajax.clearCache = function(pattern = null) {
        if (pattern === null) {
            this._cache.clear();
            Vodo.log('AJAX cache cleared');
        } else {
            const regex = new RegExp(pattern);
            for (const key of this._cache.keys()) {
                if (regex.test(key)) {
                    this._cache.delete(key);
                }
            }
            Vodo.log('AJAX cache cleared for pattern:', pattern);
        }
    };

    /**
     * Invalidate specific URL
     */
    ajax.invalidate = function(url, method = 'GET') {
        const key = `${method.toUpperCase()}:${url}:`;
        for (const cacheKey of this._cache.keys()) {
            if (cacheKey.startsWith(key)) {
                this._cache.delete(cacheKey);
            }
        }
    };

    /**
     * Preload URL into cache
     */
    ajax.preload = function(url, options = {}) {
        const cacheKey = getCacheKey(url, { ...options, method: 'GET' });

        // Skip if already cached or pending
        if (getFromCache(cacheKey) || this._pending.has(cacheKey)) {
            return Promise.resolve();
        }

        return this.get(url, { ...options, cache: true }).catch(() => {
            // Silently fail preloads
        });
    };

    // ============================================
    // Interceptors
    // ============================================

    /**
     * Add request interceptor
     */
    ajax.addRequestInterceptor = function(callback) {
        this._interceptors.request.push(callback);
        return () => {
            const index = this._interceptors.request.indexOf(callback);
            if (index > -1) {
                this._interceptors.request.splice(index, 1);
            }
        };
    };

    /**
     * Add response interceptor
     */
    ajax.addResponseInterceptor = function(callback) {
        this._interceptors.response.push(callback);
        return () => {
            const index = this._interceptors.response.indexOf(callback);
            if (index > -1) {
                this._interceptors.response.splice(index, 1);
            }
        };
    };

    // ============================================
    // Abort Control
    // ============================================

    /**
     * Abort pending request
     */
    ajax.abort = function(url) {
        // Note: jQuery doesn't easily support aborting individual requests
        // This clears from pending map to prevent dedup
        for (const key of this._pending.keys()) {
            if (key.includes(url)) {
                this._pending.delete(key);
            }
        }
    };

    /**
     * Abort all pending requests
     */
    ajax.abortAll = function() {
        this._pending.clear();
    };

    // ============================================
    // Initialize
    // ============================================

    ajax.init = function() {
        // Add global error handler for 401
        this.addResponseInterceptor((response, options) => {
            // Handle redirects in response
            if (response && response.redirect) {
                if (response.redirect === 'login' || response.status === 401) {
                    window.location.href = response.redirect;
                }
            }
            return response;
        });

        // Update CSRF token from response headers
        $(document).ajaxComplete((event, xhr) => {
            const newToken = xhr.getResponseHeader('X-CSRF-TOKEN');
            if (newToken) {
                Vodo.config.csrfToken = newToken;
                $('meta[name="csrf-token"]').attr('content', newToken);
            }
        });

        Vodo.log('AJAX module initialized');
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('ajax', ajax);

})(typeof window !== 'undefined' ? window : this);
