/**
 * VODO Platform - Component Registry
 *
 * Provides a system for registering and initializing
 * UI components with lifecycle management.
 *
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    const Vodo = global.Vodo;
    if (!Vodo) {
        console.error('Vodo core must be loaded before vodo.components.js');
        return;
    }

    // ============================================
    // Components Configuration
    // ============================================

    const components = {
        config: {
            autoInit: true,
            dataAttribute: 'data-component'
        },

        // Registered component definitions
        _definitions: new Map(),

        // Active component instances
        _instances: new WeakMap()
    };

    // ============================================
    // Component Registration
    // ============================================

    /**
     * Register a component
     * @param {string} name - Component name
     * @param {Object} definition - Component definition
     */
    components.register = function(name, definition) {
        if (typeof definition !== 'object') {
            Vodo.error('Component definition must be an object');
            return;
        }

        if (typeof definition.init !== 'function') {
            Vodo.error('Component must have an init function');
            return;
        }

        // Set defaults
        const componentDef = {
            name,
            selector: definition.selector || `[${this.config.dataAttribute}="${name}"]`,
            defaults: definition.defaults || {},
            init: definition.init,
            destroy: definition.destroy || (() => {}),
            update: definition.update || null
        };

        this._definitions.set(name, componentDef);
        Vodo.log(`Component registered: ${name}`);

        // Auto-initialize if already in DOM
        if (this.config.autoInit) {
            this.initComponent(name);
        }

        // Emit event
        if (Vodo.events) {
            Vodo.events.emit('component:registered', name, componentDef);
        }
    };

    /**
     * Unregister a component
     * @param {string} name - Component name
     */
    components.unregister = function(name) {
        // Destroy all instances first
        this.destroyComponent(name);

        this._definitions.delete(name);
        Vodo.log(`Component unregistered: ${name}`);
    };

    // ============================================
    // Component Initialization
    // ============================================

    /**
     * Initialize components in a container
     * @param {string|Element|jQuery} container - Container element
     */
    components.init = function(container = document) {
        const $container = $(container);

        this._definitions.forEach((definition, name) => {
            const $elements = $container.find(definition.selector);

            $elements.each((i, element) => {
                this.initElement(element, definition);
            });
        });

        // Emit event
        if (Vodo.events) {
            Vodo.events.emit('components:init', container);
        }
    };

    /**
     * Initialize a specific component type
     * @param {string} name - Component name
     * @param {string|Element|jQuery} container - Container element
     */
    components.initComponent = function(name, container = document) {
        const definition = this._definitions.get(name);
        if (!definition) {
            Vodo.warn(`Component not found: ${name}`);
            return;
        }

        const $container = $(container);
        const $elements = $container.find(definition.selector);

        $elements.each((i, element) => {
            this.initElement(element, definition);
        });
    };

    /**
     * Initialize a single element
     * @param {Element} element - DOM element
     * @param {Object} definition - Component definition
     */
    components.initElement = function(element, definition) {
        // Skip if already initialized
        const instances = this._instances.get(element);
        if (instances && instances.has(definition.name)) {
            return;
        }

        // Get options from data attributes
        const options = this.getOptions(element, definition);

        try {
            // Call init function
            const instance = definition.init.call(null, element, options);

            // Store instance
            if (!this._instances.has(element)) {
                this._instances.set(element, new Map());
            }
            this._instances.get(element).set(definition.name, instance);

            // Mark as initialized
            element.dataset.componentInitialized = 'true';

            Vodo.log(`Component initialized: ${definition.name}`, element);

            // Emit event
            if (Vodo.events) {
                Vodo.events.emit('component:init', definition.name, element, instance);
            }

        } catch (error) {
            Vodo.error(`Failed to initialize component ${definition.name}:`, error);
        }
    };

    /**
     * Get options from element data attributes
     */
    components.getOptions = function(element, definition) {
        const $element = $(element);
        const options = { ...definition.defaults };

        // Parse data-* attributes
        Object.keys(element.dataset).forEach(key => {
            // Skip system attributes
            if (key === 'component' || key === 'componentInitialized') {
                return;
            }

            let value = element.dataset[key];

            // Try to parse JSON values
            try {
                value = JSON.parse(value);
            } catch (e) {
                // Keep as string
            }

            // Convert kebab-case to camelCase
            const camelKey = key.replace(/-([a-z])/g, g => g[1].toUpperCase());
            options[camelKey] = value;
        });

        return options;
    };

    // ============================================
    // Component Destruction
    // ============================================

    /**
     * Destroy components in a container
     * @param {string|Element|jQuery} container - Container element
     */
    components.destroy = function(container) {
        const $container = $(container);

        this._definitions.forEach((definition, name) => {
            const $elements = $container.find(definition.selector);

            $elements.each((i, element) => {
                this.destroyElement(element, definition.name);
            });
        });

        // Emit event
        if (Vodo.events) {
            Vodo.events.emit('components:destroy', container);
        }
    };

    /**
     * Destroy a specific component type
     * @param {string} name - Component name
     * @param {string|Element|jQuery} container - Container element
     */
    components.destroyComponent = function(name, container = document) {
        const definition = this._definitions.get(name);
        if (!definition) return;

        const $container = $(container);
        const $elements = $container.find(definition.selector);

        $elements.each((i, element) => {
            this.destroyElement(element, name);
        });
    };

    /**
     * Destroy a single element's component
     * @param {Element} element - DOM element
     * @param {string} name - Component name
     */
    components.destroyElement = function(element, name) {
        const instances = this._instances.get(element);
        if (!instances || !instances.has(name)) {
            return;
        }

        const instance = instances.get(name);
        const definition = this._definitions.get(name);

        try {
            // Call destroy function
            if (definition && definition.destroy) {
                definition.destroy.call(null, element, instance);
            }

            // If instance is a function (cleanup), call it
            if (typeof instance === 'function') {
                instance();
            }

            // Remove instance
            instances.delete(name);
            if (instances.size === 0) {
                this._instances.delete(element);
            }

            // Remove initialized marker
            delete element.dataset.componentInitialized;

            Vodo.log(`Component destroyed: ${name}`, element);

            // Emit event
            if (Vodo.events) {
                Vodo.events.emit('component:destroy', name, element);
            }

        } catch (error) {
            Vodo.error(`Failed to destroy component ${name}:`, error);
        }
    };

    // ============================================
    // Component Access
    // ============================================

    /**
     * Get component instance
     * @param {string} name - Component name
     * @param {Element} element - DOM element
     * @returns {*} Component instance
     */
    components.get = function(name, element) {
        const instances = this._instances.get(element);
        if (!instances) return null;
        return instances.get(name) || null;
    };

    /**
     * Check if component exists
     * @param {string} name - Component name
     * @returns {boolean}
     */
    components.has = function(name) {
        return this._definitions.has(name);
    };

    /**
     * List registered components
     * @returns {Array<string>}
     */
    components.list = function() {
        return [...this._definitions.keys()];
    };

    /**
     * Update component
     * @param {Element} element - DOM element
     * @param {string} name - Component name
     * @param {Object} options - New options
     */
    components.update = function(element, name, options = {}) {
        const instances = this._instances.get(element);
        if (!instances || !instances.has(name)) {
            Vodo.warn(`No instance of ${name} on element`);
            return;
        }

        const definition = this._definitions.get(name);
        const instance = instances.get(name);

        if (definition && definition.update) {
            definition.update.call(null, element, instance, options);
        }
    };

    // ============================================
    // Built-in Components
    // ============================================

    /**
     * Register built-in components
     */
    function registerBuiltinComponents() {
        // Dropdown component
        components.register('dropdown', {
            selector: '[data-component="dropdown"], .dropdown',
            init(element, options) {
                const $element = $(element);
                const $trigger = $element.find('.dropdown-trigger, [data-dropdown-trigger]');
                const $menu = $element.find('.dropdown-menu, [data-dropdown-menu]');

                const toggle = (e) => {
                    e.stopPropagation();
                    const isOpen = $menu.is(':visible');

                    // Close other dropdowns
                    $('.dropdown-menu:visible').not($menu).hide();

                    $menu.toggle();
                    $element.toggleClass('is-open', !isOpen);
                };

                const close = () => {
                    $menu.hide();
                    $element.removeClass('is-open');
                };

                $trigger.on('click.dropdown', toggle);
                $(document).on('click.dropdown', close);

                // Return cleanup function
                return () => {
                    $trigger.off('click.dropdown');
                    $(document).off('click.dropdown', close);
                };
            }
        });

        // Tabs component
        components.register('tabs', {
            selector: '[data-component="tabs"], .tabs-component',
            init(element, options) {
                const $element = $(element);
                const $triggers = $element.find('[data-tab]');
                const $panes = $element.find('[data-tab-pane]');

                const activate = (tabId) => {
                    $triggers.removeClass('active');
                    $panes.removeClass('active').hide();

                    $triggers.filter(`[data-tab="${tabId}"]`).addClass('active');
                    $panes.filter(`[data-tab-pane="${tabId}"]`).addClass('active').show();
                };

                $triggers.on('click.tabs', function(e) {
                    e.preventDefault();
                    activate($(this).data('tab'));
                });

                // Activate first tab
                const firstTab = $triggers.first().data('tab');
                if (firstTab) activate(firstTab);

                return () => {
                    $triggers.off('click.tabs');
                };
            }
        });

        // Collapse component
        components.register('collapse', {
            selector: '[data-component="collapse"]',
            init(element, options) {
                const $element = $(element);
                const $trigger = $element.find('[data-collapse-trigger]');
                const $content = $element.find('[data-collapse-content]');
                const isOpen = options.open || false;

                if (!isOpen) {
                    $content.hide();
                }

                $trigger.on('click.collapse', function(e) {
                    e.preventDefault();
                    $content.slideToggle(200);
                    $element.toggleClass('is-open');
                });

                return () => {
                    $trigger.off('click.collapse');
                };
            }
        });

        // Tooltip component
        components.register('tooltip', {
            selector: '[data-tooltip]',
            init(element, options) {
                const $element = $(element);
                const text = $element.data('tooltip');
                const position = options.position || 'top';

                let $tooltip = null;

                const show = () => {
                    $tooltip = $(`<div class="tooltip tooltip-${position}">${text}</div>`);
                    $('body').append($tooltip);

                    const rect = element.getBoundingClientRect();
                    const tooltipRect = $tooltip[0].getBoundingClientRect();

                    let top, left;

                    switch (position) {
                        case 'top':
                            top = rect.top - tooltipRect.height - 8;
                            left = rect.left + (rect.width - tooltipRect.width) / 2;
                            break;
                        case 'bottom':
                            top = rect.bottom + 8;
                            left = rect.left + (rect.width - tooltipRect.width) / 2;
                            break;
                        case 'left':
                            top = rect.top + (rect.height - tooltipRect.height) / 2;
                            left = rect.left - tooltipRect.width - 8;
                            break;
                        case 'right':
                            top = rect.top + (rect.height - tooltipRect.height) / 2;
                            left = rect.right + 8;
                            break;
                    }

                    $tooltip.css({ top, left });
                };

                const hide = () => {
                    if ($tooltip) {
                        $tooltip.remove();
                        $tooltip = null;
                    }
                };

                $element.on('mouseenter.tooltip', show);
                $element.on('mouseleave.tooltip', hide);

                return () => {
                    hide();
                    $element.off('.tooltip');
                };
            }
        });

        // Copy to clipboard component
        components.register('copy', {
            selector: '[data-copy]',
            init(element, options) {
                const $element = $(element);
                const target = $element.data('copy');

                $element.on('click.copy', async () => {
                    const $target = $(target);
                    const text = $target.is('input, textarea') ? $target.val() : $target.text();

                    const success = await Vodo.utils.copyToClipboard(text);

                    if (success && Vodo.notify) {
                        Vodo.notify.success('Copied to clipboard');
                    }
                });

                return () => {
                    $element.off('click.copy');
                };
            }
        });
    }

    // ============================================
    // Initialize
    // ============================================

    components.init = function(container = document) {
        const $container = $(container);

        this._definitions.forEach((definition, name) => {
            const $elements = $container.find(definition.selector);

            $elements.each((i, element) => {
                this.initElement(element, definition);
            });
        });

        if (Vodo.events) {
            Vodo.events.emit('components:init', container);
        }

        Vodo.log('Components initialized in container');
    };

    components.setup = function() {
        // Register built-in components
        registerBuiltinComponents();

        // Initialize components on document ready
        Vodo.ready(() => {
            this.init(document);
        });

        // Re-initialize after router navigation
        if (Vodo.events) {
            Vodo.events.on('router:after', (url, fragment) => {
                const container = document.querySelector(Vodo.router?.config?.container || '#pageContent');
                if (container) {
                    this.init(container);
                }
            });
        }

        Vodo.log('Components module setup complete');
    };

    // ============================================
    // Register Module
    // ============================================

    Vodo.registerModule('components', components);

})(typeof window !== 'undefined' ? window : this);
