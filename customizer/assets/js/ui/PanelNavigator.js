/**
 * TailwindPlus Customizer - Panel Navigator
 * ==========================================
 * Stack-based panel navigation with slide animations
 * 
 * @module ui/PanelNavigator
 * @version 1.1.0
 */

import { eventBus, Events } from '../core/EventBus.js';
import { getText } from '../utils/helpers.js';

export class PanelNavigator {
    constructor(options) {
        this.container = options.container;
        this.language = options.language || 'ar';
        this.stack = [];
        this.currentPanel = null;
        this.isAnimating = false;
        
        this._init();
    }

    _init() {
        this.container.classList.add('panel-navigator');
        this.container.innerHTML = [
            '<div class="panel-navigator__viewport">',
            '<div class="panel-navigator__track"></div>',
            '</div>'
        ].join('');
        
        this.viewport = this.container.querySelector('.panel-navigator__viewport');
        this.track = this.container.querySelector('.panel-navigator__track');
    }

    push(panel, animate) {
        if (this.isAnimating) return;
        if (animate === undefined) animate = true;
        
        const panelEl = this._createPanelElement(panel);
        this.track.appendChild(panelEl);
        
        this.stack.push({
            id: panel.id,
            title: panel.title,
            component: panel.component,
            data: panel.data,
            isMain: panel.isMain,
            element: panelEl
        });

        if (animate && this.stack.length > 1) {
            this._animateTransition('push');
        } else {
            this._updatePosition(false);
        }

        this.currentPanel = panel;
        eventBus.emit('panel:pushed', { panel: panel });
    }

    pop(animate) {
        if (this.isAnimating || this.stack.length <= 1) return null;
        if (animate === undefined) animate = true;
        
        const poppedPanel = this.stack[this.stack.length - 1];
        const self = this;
        
        if (animate) {
            this._animateTransition('pop', function() {
                poppedPanel.element.remove();
                self.stack.pop();
                
                if (poppedPanel.component && poppedPanel.component.destroy) {
                    poppedPanel.component.destroy();
                }
                
                // Make sure previous panel is visible
                self._updatePosition(false);
            });
        } else {
            poppedPanel.element.remove();
            this.stack.pop();
            if (poppedPanel.component && poppedPanel.component.destroy) {
                poppedPanel.component.destroy();
            }
            this._updatePosition(false);
        }

        this.currentPanel = this.stack.length > 1 ? this.stack[this.stack.length - 2] : this.stack[0];
        eventBus.emit('panel:popped', { panel: poppedPanel });
        
        return poppedPanel;
    }

    popToRoot(animate) {
        if (this.isAnimating || this.stack.length <= 1) return;
        if (animate === undefined) animate = true;
        
        while (this.stack.length > 1) {
            const panel = this.stack.pop();
            panel.element.remove();
            if (panel.component && panel.component.destroy) {
                panel.component.destroy();
            }
        }
        
        this._updatePosition(animate);
        this.currentPanel = this.stack[0] || null;
        eventBus.emit('panel:poppedToRoot');
    }

    replace(panel, animate) {
        if (this.isAnimating || this.stack.length === 0) return;
        if (animate === undefined) animate = true;
        
        const oldPanel = this.stack.pop();
        oldPanel.element.remove();
        if (oldPanel.component && oldPanel.component.destroy) {
            oldPanel.component.destroy();
        }
        
        this.push(panel, animate);
    }

    getCurrent() {
        return this.stack.length > 0 ? this.stack[this.stack.length - 1] : null;
    }

    getDepth() {
        return this.stack.length;
    }

    canGoBack() {
        return this.stack.length > 1;
    }

    _createPanelElement(panel) {
        const el = document.createElement('div');
        el.className = 'panel-navigator__panel';
        el.dataset.panelId = panel.id;
        
        const title = getText(panel.title, this.language);
        const showBack = this.stack.length > 0;
        const isMainPanel = panel.isMain === true;
        
        let html = '';
        
        if (isMainPanel) {
            // Main panel - no header
            html = '<div class="panel-navigator__content"></div>';
        } else {
            // Sub panels - show header with back button
            html = '<div class="panel-navigator__header">';
            if (showBack) {
                html += '<button class="panel-navigator__back" data-action="back">';
                html += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
                html += '<path d="M9 18l6-6-6-6"/>';
                html += '</svg>';
                html += '</button>';
            }
            html += '<div class="panel-navigator__title-wrapper">';
            if (showBack) {
                html += '<span class="panel-navigator__subtitle">' + getText({ ar: 'أنت الآن تقوم بتخصيص', en: 'You are customizing' }, this.language) + '</span>';
            }
            html += '<h2 class="panel-navigator__title">' + title + '</h2>';
            html += '</div>';
            html += '</div>';
            html += '<div class="panel-navigator__content"></div>';
        }
        
        el.innerHTML = html;

        // Bind back button
        const self = this;
        const backBtn = el.querySelector('[data-action="back"]');
        if (backBtn) {
            backBtn.addEventListener('click', function() {
                self.pop();
            });
        }

        // Render component content
        const contentEl = el.querySelector('.panel-navigator__content');
        if (panel.component && panel.component.render) {
            panel.component.render(contentEl, panel.data);
        } else if (panel.content) {
            contentEl.innerHTML = panel.content;
        }

        return el;
    }

    _animateTransition(direction, callback) {
        this.isAnimating = true;
        
        const duration = 250;
        const panels = this.track.querySelectorAll('.panel-navigator__panel');
        const currentIndex = this.stack.length - 1;
        const self = this;
        
        if (direction === 'push') {
            const newPanel = panels[currentIndex];
            const oldPanel = currentIndex > 0 ? panels[currentIndex - 1] : null;
            
            newPanel.style.transform = 'translateX(-100%)';
            newPanel.style.opacity = '0';
            
            requestAnimationFrame(function() {
                newPanel.style.transition = 'transform ' + duration + 'ms ease, opacity ' + duration + 'ms ease';
                newPanel.style.transform = 'translateX(0)';
                newPanel.style.opacity = '1';
                
                if (oldPanel) {
                    oldPanel.style.transition = 'transform ' + duration + 'ms ease, opacity ' + duration + 'ms ease';
                    oldPanel.style.transform = 'translateX(30%)';
                    oldPanel.style.opacity = '0.5';
                }
            });
        } else {
            const currentPanel = panels[currentIndex];
            const prevPanel = currentIndex > 0 ? panels[currentIndex - 1] : null;
            
            if (prevPanel) {
                prevPanel.style.transform = 'translateX(30%)';
                prevPanel.style.opacity = '0.5';
                prevPanel.style.pointerEvents = 'auto';
            }
            
            requestAnimationFrame(function() {
                currentPanel.style.transition = 'transform ' + duration + 'ms ease, opacity ' + duration + 'ms ease';
                currentPanel.style.transform = 'translateX(-100%)';
                currentPanel.style.opacity = '0';
                
                if (prevPanel) {
                    prevPanel.style.transition = 'transform ' + duration + 'ms ease, opacity ' + duration + 'ms ease';
                    prevPanel.style.transform = 'translateX(0)';
                    prevPanel.style.opacity = '1';
                }
            });
        }

        setTimeout(function() {
            self.isAnimating = false;
            self._cleanupTransitions();
            if (callback) callback();
        }, duration);
    }

    _updatePosition(animate) {
        const panels = this.track.querySelectorAll('.panel-navigator__panel');
        const activeIndex = this.stack.length - 1;
        
        for (let i = 0; i < panels.length; i++) {
            const panel = panels[i];
            const isActive = i === activeIndex;
            
            if (animate) {
                panel.style.transition = 'transform 250ms ease, opacity 250ms ease';
            } else {
                panel.style.transition = 'none';
            }
            
            panel.style.transform = isActive ? 'translateX(0)' : 'translateX(30%)';
            panel.style.opacity = isActive ? '1' : '0';
            panel.style.pointerEvents = isActive ? 'auto' : 'none';
        }
    }

    _cleanupTransitions() {
        const panels = this.track.querySelectorAll('.panel-navigator__panel');
        const activeIndex = this.stack.length - 1;
        
        for (let i = 0; i < panels.length; i++) {
            const panel = panels[i];
            const isActive = i === activeIndex;
            
            panel.style.transition = '';
            panel.style.transform = isActive ? '' : 'translateX(30%)';
            panel.style.opacity = isActive ? '' : '0';
            panel.style.pointerEvents = isActive ? '' : 'none';
        }
    }

    destroy() {
        for (let i = 0; i < this.stack.length; i++) {
            const panel = this.stack[i];
            if (panel.component && panel.component.destroy) {
                panel.component.destroy();
            }
        }
        this.stack = [];
        this.container.innerHTML = '';
    }
}

export default PanelNavigator;
