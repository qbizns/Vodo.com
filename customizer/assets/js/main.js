/**
 * TailwindPlus Customizer - Main Entry Point
 * ===========================================
 * Initializes the customizer application
 * 
 * @version 1.0.0
 */

import { Customizer } from './Customizer.js';
import { sampleComponents } from './data/components.js';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize customizer
    window.customizer = new Customizer({
        container: '#app',
        language: 'ar',
        direction: 'rtl',
        components: sampleComponents,
    });

    // Expose to window for debugging/plugins
    window.TailwindPlusCustomizer = {
        instance: window.customizer,
        version: '1.0.0',
    };
});
