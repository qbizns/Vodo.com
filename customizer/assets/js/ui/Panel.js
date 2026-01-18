/**
 * TailwindPlus Customizer - Panel UI Component
 * =============================================
 * Side panel with sections, controls, and navigation
 * 
 * @module ui/Panel
 * @version 2.1.0
 */

import { eventBus, Events } from '../core/EventBus.js';
import { icon } from '../utils/icons.js';
import { getText } from '../utils/helpers.js';
import { PanelNavigator } from './PanelNavigator.js';
import { EditPanel } from './EditPanel.js';
import { ComponentLibraryPanel } from './ComponentLibraryPanel.js';
import { OverlayPanel } from './OverlayPanel.js';

export class Panel {
    constructor(options) {
        this.container = options.container;
        this.panels = options.panels || [];
        this.language = options.language || 'ar';
        this.onAddElement = options.onAddElement;
        this.pageState = options.pageState;
        this.componentRegistry = options.componentRegistry;
        this.activePanel = null;
        this.isCollapsed = false;
        this.navigator = null;
        this.currentEditPanel = null;
        this._insertAfterBlockId = null;
        this._mainPanelContent = null;
        this.overlayPanel = null;

        this._init();
        this._bindEvents();
        this._initOverlayPanel();
    }

    _init() {
        this.container.innerHTML = [
            '<div class="panel__wrapper">',
            '<div class="panel__navigator-container" id="panel-navigator"></div>',
            '</div>'
        ].join('');

        const navigatorContainer = this.container.querySelector('#panel-navigator');
        this.navigator = new PanelNavigator({
            container: navigatorContainer,
            language: this.language
        });

        const self = this;
        this.navigator.push({
            id: 'main',
            title: { ar: 'عناصر الصفحة', en: 'Page Elements' },
            component: {
                render: function(container) {
                    self._mainPanelContent = container;
                    self._renderMainPanel(container);
                }
            },
            isMain: true
        }, false);
    }

    _renderMainPanel(container) {
        let html = '';
        for (let i = 0; i < this.panels.length; i++) {
            html += this._renderPanelSection(this.panels[i]);
        }
        container.innerHTML = '<div class="panel__sections">' + html + '</div>';
        this._bindMainPanelEvents(container);
    }

    _renderPanelSection(panel) {
        const title = getText(panel.title, this.language);
        const description = getText(panel.description, this.language);
        const helpText = panel.helpLink ? getText(panel.helpLink.text, this.language) : '';
        const isActive = this.activePanel === panel.id ? 'is-active' : '';

        let html = '<div class="panel__section ' + isActive + '" data-panel="' + panel.id + '">';
        
        html += '<div class="panel__header">';
        html += '<h2 class="panel__title">' + title + '</h2>';
        html += '<p class="panel__description">' + description + '</p>';
        if (panel.helpLink) {
            html += '<a href="' + panel.helpLink.url + '" class="panel__help-link">';
            html += '<span>تحتاج إلى مساعدة، ' + helpText + '</span>';
            html += icon('link', { size: 14 });
            html += '</a>';
        }
        html += '</div>';

        if (panel.hasSearch) {
            html += '<div class="panel__search">';
            html += '<button class="panel__search-btn">' + icon('search', { size: 16 }) + '</button>';
            html += '<span class="panel__search-label">' + getText({ ar: 'عناصر الصفحة', en: 'Page Elements' }, this.language) + '</span>';
            html += '</div>';
        }

        html += '<div class="panel__body custom-scrollbar">';
        if (panel.isLayerPanel) {
            html += this._renderLayerPanel();
        } else {
            html += this._renderControls(panel.controls);
        }
        html += '</div>';

        if (panel.hasSaveButton) {
            html += '<div class="panel__footer">';
            html += '<button class="panel__save-btn" data-action="save">';
            html += getText({ ar: 'حفظ التغييرات', en: 'Save Changes' }, this.language);
            html += '</button>';
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    _renderLayerPanel() {
        let html = '<button class="panel__add-btn" data-action="add-element">';
        html += icon('plus', { size: 16 });
        html += '<span>' + getText({ ar: 'إضافة عنصر جديد', en: 'Add New Element' }, this.language) + '</span>';
        html += '</button>';
        html += '<div class="layers" id="layers-container"></div>';
        return html;
    }

    _renderControls(controls) {
        if (!controls) return '';
        let html = '';
        for (let i = 0; i < controls.length; i++) {
            const control = controls[i];
            switch (control.type) {
                case 'add-button':
                    html += this._renderAddButton(control);
                    break;
                case 'accordion':
                    html += this._renderAccordion(control);
                    break;
                case 'section-header':
                    html += this._renderSectionHeader(control);
                    break;
                case 'section-divider':
                    html += this._renderSectionDivider(control);
                    break;
                case 'navigation':
                    html += this._renderNavigation(control);
                    break;
                case 'toggle':
                    html += this._renderToggle(control);
                    break;
                case 'warning-banner':
                    html += this._renderWarningBanner(control);
                    break;
                case 'editable-list':
                    html += this._renderEditableList(control);
                    break;
            }
        }
        return html;
    }

    _renderAddButton(control) {
        const label = getText(control.label, this.language);
        let html = '<button class="panel__add-btn" data-action="add-element">';
        html += icon('plus', { size: 16 });
        html += '<span>' + label + '</span>';
        html += '</button>';
        return html;
    }

    _renderAccordion(control) {
        let html = '<div class="accordion">';
        for (let i = 0; i < control.sections.length; i++) {
            const section = control.sections[i];
            const title = getText(section.title, this.language);
            html += '<div class="accordion__item" data-section="' + section.id + '">';
            html += '<button class="accordion__trigger">';
            html += '<span class="accordion__trigger-icon">' + icon('chevron-down', { size: 16 }) + '</span>';
            html += '<span class="accordion__trigger-text">' + title + '</span>';
            html += '</button>';
            html += '<div class="accordion__content">';
            html += '<div class="accordion__content-inner">';
            html += '<p class="text-sm text-gray-400">' + getText({ ar: 'محتوى ' + title, en: title + ' content' }, this.language) + '</p>';
            html += '</div></div></div>';
        }
        html += '</div>';
        return html;
    }

    _renderSectionHeader(control) {
        const title = getText(control.title, this.language);
        return '<div class="panel__section-header"><span class="panel__section-header-text">' + title + '</span></div>';
    }

    _renderSectionDivider(control) {
        const title = getText(control.title, this.language);
        return '<div class="panel__section-divider"><span class="panel__section-divider-text">' + title + '</span></div>';
    }

    _renderNavigation(control) {
        let html = '<div class="accordion">';
        for (let i = 0; i < control.items.length; i++) {
            const item = control.items[i];
            const label = getText(item.label, this.language);
            html += '<button class="nav-item" data-target="' + item.id + '">';
            html += '<span class="nav-item__arrow">' + icon('chevron-left', { size: 16 }) + '</span>';
            html += '<div class="nav-item__content">';
            html += '<span class="nav-item__label">' + label + '</span>';
            html += '<span class="nav-item__icon">' + icon(item.icon, { size: 20 }) + '</span>';
            html += '</div></button>';
        }
        html += '</div>';
        return html;
    }

    _renderToggle(control) {
        const label = getText(control.label, this.language);
        const checked = control.value ? 'checked' : '';
        let html = '<div class="toggle-row">';
        html += '<label class="toggle">';
        html += '<input type="checkbox" class="toggle__input" ' + checked + ' data-setting="' + control.id + '">';
        html += '<span class="toggle__slider"></span>';
        html += '</label>';
        html += '<span class="toggle-row__label">' + label + '</span>';
        html += '</div>';
        return html;
    }

    _renderWarningBanner(control) {
        const message = getText(control.message, this.language);
        let html = '<div class="panel__warning">';
        html += '<span class="panel__warning-icon">' + icon('warning', { size: 20 }) + '</span>';
        html += '<span class="panel__warning-text">' + message + '</span>';
        html += '</div>';
        return html;
    }

    _renderEditableList(control) {
        let html = '<div class="editable-list">';
        for (let i = 0; i < control.items.length; i++) {
            const item = control.items[i];
            const label = getText(item.label, this.language);
            html += '<div class="editable-item" data-id="' + item.id + '">';
            html += '<span class="editable-item__label">' + label + '</span>';
            html += '<button class="editable-item__action" data-action="edit">' + icon('edit', { size: 16 }) + '</button>';
            html += '<button class="editable-item__action" data-action="more">' + icon('more', { size: 16 }) + '</button>';
            html += '</div>';
        }
        html += '</div>';
        return html;
    }

    _bindMainPanelEvents(container) {
        const self = this;
        container.addEventListener('click', function(e) {
            const target = e.target;
            
            if (target.closest('[data-action="add-element"]')) {
                self.openComponentLibrary();
                return;
            }
            
            const accordionTrigger = target.closest('.accordion__trigger');
            if (accordionTrigger) {
                const item = accordionTrigger.closest('.accordion__item');
                if (item) {
                    item.classList.toggle('is-open');
                }
                return;
            }
            
            if (target.closest('[data-action="save"]')) {
                eventBus.emit(Events.PAGE_SAVED, {});
                return;
            }
        });
    }

    _bindEvents() {
        const self = this;

        eventBus.on(Events.PANEL_SWITCHED, function(data) {
            self.setActivePanel(data.panelId);
        });

        eventBus.on(Events.SIDEBAR_TOGGLED, function(data) {
            self.isCollapsed = data.collapsed;
            self.container.classList.toggle('is-collapsed', data.collapsed);
        });

        eventBus.on('edit-block', function(data) {
            self.openEditPanel(data.blockId);
        });

        eventBus.on('open-component-library', function(data) {
            self.openComponentLibrary(data ? data.insertAfter : null);
        });

        eventBus.on('panel:popped', function(data) {
            if (data.panel && data.panel.id && data.panel.id.indexOf('edit-') === 0) {
                self.currentEditPanel = null;
            }
            if (data.panel && data.panel.id === 'component-library') {
                self._insertAfterBlockId = null;
            }
        });
    }

    _initOverlayPanel() {
        const self = this;
        this.overlayPanel = new OverlayPanel({
            language: this.language,
            onClose: function() {
                self._insertAfterBlockId = null;
            }
        });
    }

    openComponentLibrary(insertAfter) {
        this._insertAfterBlockId = insertAfter || null;
        const self = this;
        
        const libraryPanel = new ComponentLibraryPanel({
            registry: this.componentRegistry,
            language: this.language,
            insertAfter: insertAfter,
            onSelect: function(component, insertAfterId) {
                let position = null;
                if (insertAfterId) {
                    const blocks = self.pageState.getBlocks();
                    for (let i = 0; i < blocks.length; i++) {
                        if (blocks[i].id === insertAfterId) {
                            position = i + 1;
                            break;
                        }
                    }
                }
                self.pageState.addBlock(component, position);
                self.overlayPanel.close();
            }
        });

        this.overlayPanel.open({
            title: { ar: 'اضافة عنصر جديد لعناصر الصفحة', en: 'Add New Element to Page' },
            component: libraryPanel
        });
    }

    openEditPanel(blockId) {
        const block = this.pageState.getBlock(blockId);
        if (!block) {
            console.error('Block not found:', blockId);
            return;
        }

        const component = this.componentRegistry ? this.componentRegistry.getComponent(block.componentId) : null;
        const self = this;
        
        const editPanel = new EditPanel({
            block: block,
            component: component,
            pageState: this.pageState,
            language: this.language,
            onSave: function(values, updatedBlock) {
                eventBus.emit(Events.PAGE_CHANGED, { blocks: self.pageState.getBlocks() });
            },
            onBack: function() {
                self.closeEditPanel();
            }
        });

        this.currentEditPanel = editPanel;

        this.navigator.push({
            id: 'edit-' + blockId,
            title: component ? component.name : block.name,
            component: editPanel,
            data: { blockId: blockId }
        });
    }

    closeEditPanel() {
        if (this.navigator.canGoBack()) {
            this.navigator.pop();
            this.currentEditPanel = null;
        }
    }

    isInEditMode() {
        return this.currentEditPanel !== null;
    }

    setActivePanel(panelId) {
        this.activePanel = panelId;
        
        if (this.navigator.getDepth() > 1) {
            this.navigator.popToRoot();
        }
        
        const sections = this.container.querySelectorAll('.panel__section');
        for (let i = 0; i < sections.length; i++) {
            const section = sections[i];
            if (section.dataset.panel === panelId) {
                section.classList.add('is-active');
            } else {
                section.classList.remove('is-active');
            }
        }
    }

    getLayersContainer() {
        return this.container.querySelector('#layers-container');
    }

    render() {
        if (this._mainPanelContent && this.navigator.getDepth() === 1) {
            this._renderMainPanel(this._mainPanelContent);
        }
    }

    destroy() {
        if (this.navigator) {
            this.navigator.destroy();
        }
        if (this.currentEditPanel) {
            this.currentEditPanel.destroy();
        }
        if (this.overlayPanel) {
            this.overlayPanel.destroy();
        }
        this.container.innerHTML = '';
    }
}

export default Panel;
