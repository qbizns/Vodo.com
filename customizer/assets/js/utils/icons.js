/**
 * TailwindPlus Customizer - Icon Utility
 * =======================================
 * Helper for rendering SVG icons from sprite
 * 
 * @module utils/icons
 * @version 1.0.0
 */

const SPRITE_PATH = './assets/icons/icons.svg';

/**
 * Create SVG icon element
 * @param {string} name - Icon name (without 'icon-' prefix)
 * @param {Object} options - Options
 * @returns {string} SVG HTML string
 */
export function icon(name, options = {}) {
    const {
        size = 20,
        className = '',
        ariaLabel = '',
    } = options;

    const classes = ['icon', className].filter(Boolean).join(' ');
    const ariaAttr = ariaLabel ? `aria-label="${ariaLabel}"` : 'aria-hidden="true"';

    return `<svg class="${classes}" width="${size}" height="${size}" ${ariaAttr}>
        <use href="${SPRITE_PATH}#icon-${name}"></use>
    </svg>`;
}

/**
 * Create icon element (DOM)
 * @param {string} name - Icon name
 * @param {Object} options - Options
 * @returns {SVGElement} SVG element
 */
export function createIconElement(name, options = {}) {
    const {
        size = 20,
        className = '',
    } = options;

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('class', `icon ${className}`.trim());
    svg.setAttribute('width', size);
    svg.setAttribute('height', size);
    svg.setAttribute('aria-hidden', 'true');

    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    use.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', `${SPRITE_PATH}#icon-${name}`);
    
    svg.appendChild(use);
    return svg;
}

/**
 * Preload icon sprite
 */
export function preloadSprite() {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.href = SPRITE_PATH;
    link.as = 'image';
    link.type = 'image/svg+xml';
    document.head.appendChild(link);
}

/**
 * Inline SVG sprite into document
 * @returns {Promise} Promise that resolves when sprite is loaded
 */
export async function inlineSprite() {
    try {
        const response = await fetch(SPRITE_PATH);
        const svgText = await response.text();
        
        const container = document.createElement('div');
        container.style.display = 'none';
        container.innerHTML = svgText;
        document.body.insertBefore(container, document.body.firstChild);
        
        return true;
    } catch (error) {
        console.error('Failed to load icon sprite:', error);
        return false;
    }
}

// Icon name constants for autocomplete
export const IconNames = {
    // Navigation
    MENU: 'menu',
    EXPAND: 'expand',
    COLLAPSE: 'collapse',
    CLOSE: 'close',
    CHEVRON_LEFT: 'chevron-left',
    CHEVRON_RIGHT: 'chevron-right',
    CHEVRON_DOWN: 'chevron-down',
    CHEVRON_UP: 'chevron-up',
    
    // Panels
    HOME: 'home',
    IDENTITY: 'identity',
    HEADER_FOOTER: 'header-footer',
    DESIGN: 'design',
    CODE: 'code',
    SERVICES: 'services',
    ADS: 'ads',
    
    // Actions
    PLUS: 'plus',
    EDIT: 'edit',
    TRASH: 'trash',
    COPY: 'copy',
    DRAG: 'drag',
    MORE: 'more',
    SEARCH: 'search',
    LINK: 'link',
    
    // Visibility
    EYE: 'eye',
    EYE_OFF: 'eye-off',
    
    // Devices
    DESKTOP: 'desktop',
    MOBILE: 'mobile',
    
    // Categories
    GRID: 'grid',
    PROMO: 'promo',
    PRODUCTS: 'products',
    INCENTIVES: 'incentives',
    REVIEWS: 'reviews',
    CATEGORIES: 'categories',
    
    // Status
    WARNING: 'warning',
    CHECK: 'check',
    INFO: 'info',
    
    // Misc
    EMPTY: 'empty',
    STORE: 'store',
    PUZZLE: 'puzzle',
    HEADPHONES: 'headphones',
    CERTIFICATE: 'certificate',
    SOCIAL: 'social',
    BREADCRUMB: 'breadcrumb',
    UNDO: 'undo',
    REDO: 'redo',
};

export default icon;
