/**
 * TailwindPlus Customizer - Overlay Panel
 * ========================================
 * Sliding overlay panel that appears on top of main panel
 * 
 * @module ui/OverlayPanel
 * @version 1.0.0
 */

import { eventBus } from '../core/EventBus.js';
import { getText } from '../utils/helpers.js';

export class OverlayPanel {
    constructor(options) {
        this.language = options.language || 'ar';
        this.onClose = options.onClose || null;
        
        this.backdrop = null;
        this.panel = null;
        this.contentContainer = null;
        this.isVisible = false;
        this.currentComponent = null;
        
        this._createElements();
        this._bindEvents();
    }

    _createElements() {
        // Create backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'overlay-panel-backdrop';
        
        // Create panel
        this.panel = document.createElement('div');
        this.panel.className = 'overlay-panel';
        this.panel.innerHTML = [
            '<div class="overlay-panel__header">',
            '<h2 class="overlay-panel__title"></h2>',
            '<button class="overlay-panel__close" data-action="close">',
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">',
            '<path d="M18 6L6 18M6 6l12 12"/>',
            '</svg>',
            '</button>',
            '</div>',
            '<div class="overlay-panel__content"></div>'
        ].join('');
        
        this.contentContainer = this.panel.querySelector('.overlay-panel__content');
        this.titleElement = this.panel.querySelector('.overlay-panel__title');
        
        // Append to body
        document.body.appendChild(this.backdrop);
        document.body.appendChild(this.panel);
    }

    _bindEvents() {
        const self = this;
        
        // Close button
        const closeBtn = this.panel.querySelector('[data-action="close"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                self.close();
            });
        }
        
        // Close on backdrop click
        this.backdrop.addEventListener('click', function() {
            self.close();
        });
        
        // Close on escape key
        this._handleKeydown = function(e) {
            if (e.key === 'Escape' && self.isVisible) {
                self.close();
            }
        };
        document.addEventListener('keydown', this._handleKeydown);
    }

    open(options) {
        if (!options) options = {};
        
        const title = options.title ? getText(options.title, this.language) : '';
        this.titleElement.textContent = title;
        
        // Clean previous component
        if (this.currentComponent && this.currentComponent.destroy) {
            this.currentComponent.destroy();
        }
        this.contentContainer.innerHTML = '';
        
        // Render new component
        if (options.component) {
            this.currentComponent = options.component;
            if (options.component.render) {
                options.component.render(this.contentContainer);
            }
        }
        
        // Show panel with animation
        this.isVisible = true;
        
        // Use requestAnimationFrame for smooth animation
        const self = this;
        requestAnimationFrame(function() {
            self.backdrop.classList.add('is-visible');
            self.panel.classList.add('is-visible');
        });
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        eventBus.emit('overlay-panel:opened', { title: title });
    }

    close() {
        if (!this.isVisible) return;
        
        this.isVisible = false;
        this.backdrop.classList.remove('is-visible');
        this.panel.classList.remove('is-visible');
        
        // Restore body scroll
        document.body.style.overflow = '';
        
        // Cleanup after animation
        const self = this;
        setTimeout(function() {
            if (self.currentComponent && self.currentComponent.destroy) {
                self.currentComponent.destroy();
            }
            self.currentComponent = null;
            self.contentContainer.innerHTML = '';
        }, 300);
        
        if (this.onClose) {
            this.onClose();
        }
        
        eventBus.emit('overlay-panel:closed');
    }

    isOpen() {
        return this.isVisible;
    }

    destroy() {
        document.removeEventListener('keydown', this._handleKeydown);
        
        if (this.currentComponent && this.currentComponent.destroy) {
            this.currentComponent.destroy();
        }
        
        if (this.backdrop && this.backdrop.parentNode) {
            this.backdrop.parentNode.removeChild(this.backdrop);
        }
        if (this.panel && this.panel.parentNode) {
            this.panel.parentNode.removeChild(this.panel);
        }
        
        document.body.style.overflow = '';
    }
}

export default OverlayPanel;

