/**
 * TailwindPlus Customizer - Modal UI Component
 * =============================================
 * Component library modal with search and categories
 * 
 * @module ui/Modal
 * @version 1.0.0
 */

import { eventBus, Events } from '../core/EventBus.js';
import { icon } from '../utils/icons.js';
import { getText, debounce } from '../utils/helpers.js';

export class Modal {
    /**
     * @param {Object} options - Modal options
     * @param {HTMLElement} options.container - Container element
     * @param {Object} options.registry - Component registry instance
     * @param {string} options.language - Current language
     * @param {Function} options.onSelect - Selection callback
     */
    constructor(options) {
        this.container = options.container;
        this.registry = options.registry;
        this.language = options.language || 'ar';
        this.onSelect = options.onSelect;
        
        this.isOpen = false;
        this.selectedCategory = 'all';
        this.selectedComponent = null;
        this.searchQuery = '';

        this._bindEvents();
        this.render();
    }

    /**
     * Render modal
     */
    render() {
        this.container.innerHTML = `
            <div class="modal-overlay" id="component-modal">
                <div class="modal">
                    <!-- Header -->
                    <div class="modal__header">
                        <button class="modal__close" data-action="close">
                            ${icon('close', { size: 20 })}
                        </button>
                        <h2 class="modal__title">
                            ${getText({ ar: 'إضافة مكوّن جديد', en: 'Add New Component' }, this.language)}
                        </h2>
                    </div>
                    
                    <!-- Search -->
                    <div class="modal__search">
                        <div class="modal__search-wrapper">
                            <input 
                                type="text" 
                                class="modal__search-input" 
                                id="component-search"
                                placeholder="${getText({ ar: 'بحث في المكونات...', en: 'Search components...' }, this.language)}"
                            >
                            <span class="modal__search-icon">
                                ${icon('search', { size: 20 })}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Body -->
                    <div class="modal__body">
                        <!-- Sidebar -->
                        <div class="modal__sidebar custom-scrollbar">
                            <div class="modal__sidebar-title">
                                ${getText({ ar: 'التصنيفات', en: 'Categories' }, this.language)}
                            </div>
                            <div id="categories-list"></div>
                        </div>
                        
                        <!-- Content -->
                        <div class="modal__content custom-scrollbar">
                            <div class="components-grid" id="components-grid"></div>
                            <div class="modal__no-results hidden" id="no-results">
                                <div class="modal__no-results-icon">
                                    ${icon('search', { size: 64 })}
                                </div>
                                <p class="modal__no-results-text">
                                    ${getText({ ar: 'لا توجد نتائج', en: 'No results found' }, this.language)}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="modal__footer">
                        <button class="modal__cancel" data-action="cancel">
                            ${getText({ ar: 'إلغاء', en: 'Cancel' }, this.language)}
                        </button>
                        <button class="modal__submit" id="add-component-btn" disabled>
                            ${getText({ ar: 'إضافة المكوّن', en: 'Add Component' }, this.language)}
                        </button>
                    </div>
                </div>
            </div>
        `;

        this._renderCategories();
        this._renderComponents();
    }

    /**
     * Render categories sidebar
     * @private
     */
    _renderCategories() {
        const container = this.container.querySelector('#categories-list');
        const categories = this.registry.listCategories();

        const html = categories.map(cat => {
            const count = cat.id === 'all' 
                ? this.registry.getComponentCount() 
                : this.registry.getComponentCount(cat.id);
            const name = getText(cat.name, this.language);
            const isActive = this.selectedCategory === cat.id;

            return `
                <div class="category-item ${isActive ? 'is-active' : ''}" data-category="${cat.id}">
                    <span class="category-item__icon">${icon(cat.icon, { size: 20 })}</span>
                    <span class="category-item__name">${name}</span>
                    <span class="category-item__count">${count}</span>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    /**
     * Render components grid
     * @private
     */
    _renderComponents() {
        const container = this.container.querySelector('#components-grid');
        const noResults = this.container.querySelector('#no-results');

        const filters = {
            category: this.selectedCategory,
            search: this.searchQuery,
        };

        const components = this.registry.listComponents(filters);

        if (components.length === 0) {
            container.innerHTML = '';
            noResults.classList.remove('hidden');
            return;
        }

        noResults.classList.add('hidden');

        const html = components.map(comp => {
            const name = getText(comp.name, this.language);
            const description = getText(comp.description, this.language);
            const isSelected = this.selectedComponent?.id === comp.id;

            return `
                <div class="component-card ${isSelected ? 'is-selected' : ''}" data-component-id="${comp.id}">
                    <div class="component-card__preview">
                        ${comp.thumbnail 
                            ? `<img src="${comp.thumbnail}" alt="${name}" loading="lazy">` 
                            : icon('grid', { size: 48 })
                        }
                    </div>
                    <div class="component-card__info">
                        <h3 class="component-card__name">${name}</h3>
                        <p class="component-card__description">${description}</p>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    /**
     * Bind event listeners
     * @private
     */
    _bindEvents() {
        // Debounced search
        const debouncedSearch = debounce((query) => {
            this.searchQuery = query;
            this._renderComponents();
        }, 200);

        // Click handlers
        this.container.addEventListener('click', (e) => {
            const target = e.target;
            const overlay = target.closest('.modal-overlay');
            
            // Close on overlay click
            if (target === overlay) {
                this.close();
                return;
            }

            // Close button
            if (target.closest('[data-action="close"]') || target.closest('[data-action="cancel"]')) {
                this.close();
                return;
            }

            // Category click
            const categoryItem = target.closest('.category-item');
            if (categoryItem) {
                this._selectCategory(categoryItem.dataset.category);
                return;
            }

            // Component click
            const componentCard = target.closest('.component-card');
            if (componentCard) {
                this._selectComponent(componentCard.dataset.componentId);
                return;
            }

            // Add button
            if (target.closest('#add-component-btn')) {
                this._addSelectedComponent();
                return;
            }
        });

        // Double click to add
        this.container.addEventListener('dblclick', (e) => {
            const componentCard = e.target.closest('.component-card');
            if (componentCard) {
                this._selectComponent(componentCard.dataset.componentId);
                this._addSelectedComponent();
            }
        });

        // Search input
        this.container.addEventListener('input', (e) => {
            if (e.target.id === 'component-search') {
                debouncedSearch(e.target.value);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (!this.isOpen) return;

            if (e.key === 'Escape') {
                this.close();
            } else if (e.key === 'Enter' && this.selectedComponent) {
                this._addSelectedComponent();
            }
        });
    }

    /**
     * Select category
     * @param {string} categoryId - Category ID
     * @private
     */
    _selectCategory(categoryId) {
        this.selectedCategory = categoryId;
        this._renderCategories();
        this._renderComponents();
    }

    /**
     * Select component
     * @param {string} componentId - Component ID
     * @private
     */
    _selectComponent(componentId) {
        this.selectedComponent = this.registry.getComponent(componentId);
        this._renderComponents();
        this._updateAddButton();
    }

    /**
     * Add selected component
     * @private
     */
    _addSelectedComponent() {
        if (!this.selectedComponent) return;

        const component = this.selectedComponent;
        
        // Close modal first with animation
        this.close();

        // Add component after modal close animation
        setTimeout(() => {
            if (this.onSelect) {
                this.onSelect(component);
            }
        }, 150);
    }

    /**
     * Update add button state
     * @private
     */
    _updateAddButton() {
        const btn = this.container.querySelector('#add-component-btn');
        if (btn) {
            btn.disabled = !this.selectedComponent;
        }
    }

    /**
     * Open modal
     */
    open() {
        this.isOpen = true;
        this.selectedComponent = null;
        this.selectedCategory = 'all';
        this.searchQuery = '';

        const searchInput = this.container.querySelector('#component-search');
        if (searchInput) searchInput.value = '';

        this._renderCategories();
        this._renderComponents();
        this._updateAddButton();

        const overlay = this.container.querySelector('.modal-overlay');
        if (overlay) {
            overlay.classList.add('is-open');
        }

        // Focus search input
        setTimeout(() => {
            if (searchInput) searchInput.focus();
        }, 100);

        eventBus.emit(Events.MODAL_OPENED, { modal: 'component-library' });
    }

    /**
     * Close modal
     */
    close() {
        this.isOpen = false;
        this.selectedComponent = null;

        const overlay = this.container.querySelector('.modal-overlay');
        if (overlay) {
            overlay.classList.remove('is-open');
        }

        eventBus.emit(Events.MODAL_CLOSED, { modal: 'component-library' });
    }

    /**
     * Check if modal is open
     * @returns {boolean} Is open
     */
    isModalOpen() {
        return this.isOpen;
    }

    /**
     * Destroy modal
     */
    destroy() {
        this.container.innerHTML = '';
    }
}

export default Modal;
