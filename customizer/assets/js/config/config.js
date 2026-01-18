/**
 * TailwindPlus Customizer - Configuration
 * ========================================
 * Central configuration for the customizer
 * 
 * @module config/config
 * @version 1.0.0
 */

export const config = {
    // App info
    name: 'TailwindPlus Customizer',
    version: '1.0.0',
    
    // Localization
    defaultLanguage: 'ar',
    defaultDirection: 'rtl',
    
    // UI settings
    ui: {
        panelWidth: 300,
        toolbarWidth: 44,
        topbarHeight: 44,
        modalMaxWidth: 1200,
        modalMaxHeight: 800,
    },
    
    // History
    history: {
        maxSize: 50,
    },
    
    // Preview
    preview: {
        defaultDevice: 'desktop',
        mobileWidth: 375,
    },
};

// Panel configuration
export const panelsConfig = [
    {
        id: 'homepage',
        icon: 'home',
        title: { ar: 'تخصيص الصفحة الرئيسية', en: 'Customize Homepage' },
        description: { 
            ar: 'اختر العناصر وأعد ترتيبها لتصميم صفحتك الرئيسية', 
            en: 'Choose elements and reorder them to design your homepage' 
        },
        helpLink: { text: { ar: 'اقرأ المقال', en: 'Read article' }, url: '#' },
        hasSearch: true,
        hasSaveButton: true,
        isLayerPanel: true,
    },
    {
        id: 'identity',
        icon: 'identity',
        title: { ar: 'هوية و بيانات المتجر', en: 'Store Identity' },
        description: { 
            ar: 'امنح متجرك هوية قوية عبر تخصيص كل تفاصيله', 
            en: 'Give your store a strong identity by customizing all details' 
        },
        hasSaveButton: true,
        controls: [
            { type: 'accordion', sections: [
                { id: 'logo', title: { ar: 'الشعار', en: 'Logo' } },
                { id: 'color', title: { ar: 'لون المتجر', en: 'Store Color' } },
                { id: 'font', title: { ar: 'خط المتجر', en: 'Store Font' } },
            ]}
        ],
    },
    {
        id: 'header-footer',
        icon: 'header-footer',
        title: { ar: 'تخصيص رأس وذيل الصفحة', en: 'Header & Footer' },
        description: { 
            ar: 'سهّل على عملائك التنقل في متجرك', 
            en: 'Make navigation easy for your customers' 
        },
        helpLink: { text: { ar: 'اقرأ المقال', en: 'Read article' }, url: '#' },
        controls: [
            { type: 'section-header', title: { ar: 'رأس الصفحة', en: 'Header' } },
            { type: 'navigation', items: [
                { id: 'header-settings', icon: 'design', label: { ar: 'اعدادات رأس الصفحة', en: 'Header Settings' } },
                { id: 'promo-bar', icon: 'breadcrumb', label: { ar: 'الشريط الترويجي', en: 'Promo Bar' } },
            ]},
            { type: 'section-header', title: { ar: 'ذيل الصفحة', en: 'Footer' } },
            { type: 'navigation', items: [
                { id: 'footer-settings', icon: 'design', label: { ar: 'اعدادات ذيل الصفحة', en: 'Footer Settings' } },
                { id: 'customer-service', icon: 'headphones', label: { ar: 'قنوات خدمة العملاء', en: 'Customer Service' } },
                { id: 'social', icon: 'social', label: { ar: 'حسابات التواصل', en: 'Social Media' } },
            ]},
        ],
    },
    {
        id: 'menus',
        icon: 'menu',
        title: { ar: 'إعداد قوائم المتجر', en: 'Store Menus' },
        description: { 
            ar: 'سهّل على عملائك التنقل في متجرك', 
            en: 'Make navigation easy for your customers' 
        },
        helpLink: { text: { ar: 'اقرأ المقال', en: 'Read article' }, url: '#' },
        controls: [
            { type: 'add-button', label: { ar: 'إضافة قائمة جديدة', en: 'Add New Menu' } },
            { type: 'editable-list', items: [
                { id: 'header-menu', label: { ar: 'قائمة رأس الصفحة', en: 'Header Menu' } },
                { id: 'footer-menu', label: { ar: 'قائمة ذيل الصفحة', en: 'Footer Menu' } },
            ]},
        ],
    },
    {
        id: 'design',
        icon: 'design',
        title: { ar: 'خيارات التصميم', en: 'Design Options' },
        description: { 
            ar: 'تخصيص العناصر الأساسية للقالب', 
            en: 'Customize basic template elements' 
        },
        helpLink: { text: { ar: 'اقرأ المقال', en: 'Read article' }, url: '#' },
        hasSaveButton: true,
        controls: [
            { type: 'toggle', id: 'arabic-numbers', label: { ar: 'استخدام الأرقام العربية', en: 'Use Arabic Numbers' }, value: true },
            { type: 'toggle', id: 'breadcrumb', label: { ar: 'ميزة مسار التنقل', en: 'Breadcrumb Feature' }, value: true },
            { type: 'section-divider', title: { ar: 'خيارات أعلى الصفحة', en: 'Header Options' } },
            { type: 'toggle', id: 'sticky-menu', label: { ar: 'تثبيت القائمة الرئيسية', en: 'Sticky Menu' }, value: true },
            { type: 'toggle', id: 'dark-header', label: { ar: 'شريط علوي داكن', en: 'Dark Header' }, value: false },
        ],
    },
    {
        id: 'theme',
        icon: 'code',
        title: { ar: 'تخصيص الثيم', en: 'Theme Customization' },
        description: { 
            ar: 'استخدام محرر CSS لتغيير مظهر المتجر', 
            en: 'Use CSS editor to change store appearance' 
        },
        helpLink: { text: { ar: 'اقرأ المقال', en: 'Read article' }, url: '#' },
        controls: [
            { type: 'warning-banner', message: { ar: 'هذه الميزة متاحة فقط لمتجر سبيشل، برو', en: 'This feature is only available for Special and Pro stores' } },
            { type: 'navigation', items: [
                { id: 'css', icon: 'code', label: { ar: 'تخصيص عن طريق ال CSS', en: 'CSS Customization' } },
                { id: 'js', icon: 'code', label: { ar: 'تخصيص عن طريق ال JS', en: 'JS Customization' } },
            ]},
        ],
    },
    {
        id: 'services',
        icon: 'services',
        title: { ar: 'خدمات اضافية', en: 'Additional Services' },
        description: { 
            ar: 'اكتشف مجموعة من الخدمات والتطبيقات', 
            en: 'Discover various services and applications' 
        },
        controls: [
            { type: 'navigation', items: [
                { id: 'merchant-services', icon: 'store', label: { ar: 'خدمات التاجر', en: 'Merchant Services' } },
                { id: 'suggested-apps', icon: 'puzzle', label: { ar: 'تطبيقات مقترحة', en: 'Suggested Apps' } },
            ]},
        ],
    },
    {
        id: 'ads',
        icon: 'ads',
        title: { ar: 'إدارة الاعلانات الترويجية', en: 'Promotional Ads' },
        description: { 
            ar: 'شريط إعلاني يظهر للعملاء عند تصفح متجرك', 
            en: 'Promotional banner shown to customers while browsing' 
        },
        helpLink: { text: { ar: 'اقرأ المقال', en: 'Read article' }, url: '#' },
        controls: [
            { type: 'add-button', label: { ar: 'إضافة إعلان جديد', en: 'Add New Ad' } },
        ],
    },
];

// Default categories
export const defaultCategories = [
    { id: 'all', name: { ar: 'الكل', en: 'All' }, icon: 'grid', order: 0 },
    { id: 'promo-sections', name: { ar: 'أقسام ترويجية', en: 'Promo Sections' }, icon: 'promo', order: 1 },
    { id: 'product-lists', name: { ar: 'قوائم المنتجات', en: 'Product Lists' }, icon: 'products', order: 2 },
    { id: 'incentives', name: { ar: 'الحوافز والمميزات', en: 'Incentives' }, icon: 'incentives', order: 3 },
    { id: 'category-previews', name: { ar: 'معاينة التصنيفات', en: 'Category Previews' }, icon: 'categories', order: 4 },
    { id: 'reviews', name: { ar: 'التقييمات', en: 'Reviews' }, icon: 'reviews', order: 5 },
    { id: 'store-navigation', name: { ar: 'التنقل', en: 'Navigation' }, icon: 'menu', order: 6 },
];

export default config;
