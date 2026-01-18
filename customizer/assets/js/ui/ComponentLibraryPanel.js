/**
 * TailwindPlus Customizer - Component Library Panel
 * ==================================================
 * Sliding panel for browsing and selecting components
 * 
 * @module ui/ComponentLibraryPanel
 * @version 1.0.0
 */

import { eventBus, Events } from '../core/EventBus.js';
import { getText } from '../utils/helpers.js';

export class ComponentLibraryPanel {
    constructor(options) {
        this.registry = options.registry;
        this.language = options.language || 'ar';
        this.onSelect = options.onSelect;
        this.insertAfter = options.insertAfter || null;
        
        this.container = null;
        this.searchQuery = '';
        this.activeCategory = 'all';
    }

    render(container) {
        this.container = container;
        
        const categories = this.registry.listCategories();
        const components = this._getFilteredComponents();
        
        let html = '<div class="component-library">';
        
        // Search
        html += '<div class="component-library__search">';
        html += '<input type="text" class="component-library__search-input" placeholder="';
        html += getText({ ar: 'ابحث عن عنصر...', en: 'Search for element...' }, this.language);
        html += '" value="' + this.searchQuery + '">';
        html += '<svg class="component-library__search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        html += '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>';
        html += '</div>';
        
        // Categories
        html += '<div class="component-library__categories">';
        html += '<button class="component-library__category' + (this.activeCategory === 'all' ? ' is-active' : '') + '" data-category="all">';
        html += getText({ ar: 'الكل', en: 'All' }, this.language);
        html += '</button>';
        
        for (let i = 0; i < categories.length; i++) {
            const cat = categories[i];
            const isActive = this.activeCategory === cat.id ? ' is-active' : '';
            html += '<button class="component-library__category' + isActive + '" data-category="' + cat.id + '">';
            html += getText(cat.name, this.language);
            html += '</button>';
        }
        html += '</div>';
        
        // Components Grid
        html += '<div class="component-library__grid custom-scrollbar">';
        if (components.length > 0) {
            for (let i = 0; i < components.length; i++) {
                html += this._renderComponentCard(components[i]);
            }
        } else {
            html += '<div class="component-library__empty">';
            html += '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">';
            html += '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>';
            html += '<p>' + getText({ ar: 'لا توجد نتائج', en: 'No results found' }, this.language) + '</p>';
            html += '</div>';
        }
        html += '</div>';
        
        html += '</div>';
        
        container.innerHTML = html;
        this._bindEvents();
    }

    _renderComponentCard(component) {
        const name = getText(component.name, this.language);
        const description = getText(component.description, this.language);
        
        let html = '<div class="component-card" data-component-id="' + component.id + '">';
        html += '<div class="component-card__preview">';
        
        if (component.thumbnail) {
            html += '<img src="' + component.thumbnail + '" alt="' + name + '" class="component-card__img">';
        } else {
            html += '<div class="component-card__placeholder">';
            html += '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">';
            html += '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>';
            html += '</div>';
        }
        
        html += '</div>';
        html += '<div class="component-card__info">';
        html += '<h4 class="component-card__name">' + name + '</h4>';
        if (description) {
            html += '<p class="component-card__desc">' + description + '</p>';
        }
        html += '</div>';
        html += '</div>';
        
        return html;
    }

    _getFilteredComponents() {
        // Use listComponents with filters
        const filters = {};
        
        if (this.activeCategory !== 'all') {
            filters.category = this.activeCategory;
        }
        
        if (this.searchQuery) {
            filters.search = this.searchQuery;
        }
        
        return this.registry.listComponents(filters);
    }

    _bindEvents() {
        if (!this.container) return;
        const self = this;
        
        // Search input
        const searchInput = this.container.querySelector('.component-library__search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                self.searchQuery = e.target.value;
                self._updateGrid();
            });
        }
        
        // Category buttons
        const categoryBtns = this.container.querySelectorAll('[data-category]');
        for (let i = 0; i < categoryBtns.length; i++) {
            categoryBtns[i].addEventListener('click', function() {
                self.activeCategory = this.dataset.category;
                self._updateCategories();
                self._updateGrid();
            });
        }
        
        // Component cards
        this.container.addEventListener('click', function(e) {
            const card = e.target.closest('.component-card');
            if (card) {
                const componentId = card.dataset.componentId;
                const component = self.registry.getComponent(componentId);
                if (component && self.onSelect) {
                    self.onSelect(component, self.insertAfter);
                }
            }
        });
    }

    _updateCategories() {
        if (!this.container) return;
        const btns = this.container.querySelectorAll('[data-category]');
        for (let i = 0; i < btns.length; i++) {
            const btn = btns[i];
            if (btn.dataset.category === this.activeCategory) {
                btn.classList.add('is-active');
            } else {
                btn.classList.remove('is-active');
            }
        }
    }

    _updateGrid() {
        const grid = this.container ? this.container.querySelector('.component-library__grid') : null;
        if (!grid) return;
        
        const components = this._getFilteredComponents();
        
        if (components.length > 0) {
            let html = '';
            for (let i = 0; i < components.length; i++) {
                html += this._renderComponentCard(components[i]);
            }
            grid.innerHTML = html;
        } else {
            let html = '<div class="component-library__empty">';
            html += '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">';
            html += '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>';
            html += '<p>' + getText({ ar: 'لا توجد نتائج', en: 'No results found' }, this.language) + '</p>';
            html += '</div>';
            grid.innerHTML = html;
        }
    }

    destroy() {
        this.container = null;
    }
}

export default ComponentLibraryPanel;
