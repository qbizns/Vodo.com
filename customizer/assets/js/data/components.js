/**
 * TailwindPlus Customizer - Sample Components
 * ============================================
 * Default components for the customizer
 * 
 * @module data/components
 * @version 1.0.0
 */

// Base64 placeholder thumbnail generator
const createThumbnail = (bgColor = '#F3F4F6', iconColor = '#9CA3AF') => {
    const svg = `<svg width="200" height="120" viewBox="0 0 200 120" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="200" height="120" fill="${bgColor}"/>
        <rect x="20" y="20" width="60" height="40" rx="4" fill="${iconColor}" opacity="0.3"/>
        <rect x="90" y="20" width="90" height="8" rx="2" fill="${iconColor}" opacity="0.5"/>
        <rect x="90" y="35" width="70" height="6" rx="2" fill="${iconColor}" opacity="0.3"/>
        <rect x="90" y="48" width="50" height="6" rx="2" fill="${iconColor}" opacity="0.3"/>
        <rect x="20" y="80" width="160" height="20" rx="4" fill="${iconColor}" opacity="0.2"/>
    </svg>`;
    return `data:image/svg+xml;base64,${btoa(svg)}`;
};

export const sampleComponents = [
    // ============================================
    // BANNERS - Wide banners like the screenshot
    // ============================================
    {
        id: 'banners/wide-banner',
        category: 'promo-sections',
        name: { ar: 'بانر عريض', en: 'Wide Banner' },
        description: { ar: 'بانر عريض بعرض كامل الصفحة', en: 'Full width wide banner' },
        thumbnail: createThumbnail('#E8F5F3', '#009688'),
        tags: ['banner', 'wide', 'promo'],
        fields: [
            {
                id: 'bannerImage',
                type: 'image',
                label: { ar: 'صورة البنر', en: 'Banner Image' },
                hint: { ar: 'المقاس المناسب للصورة هو 728×90 بكسل', en: 'Recommended size is 728×90 pixels' },
                required: true,
                selector: '.banner-image',
                attribute: 'src',
                dimensions: { width: 1260, height: 285 },
                default: ''
            },
            {
                id: 'bannerLink',
                type: 'link',
                label: { ar: 'الرابط عند الضغط على الصورة', en: 'Link when clicking the image' },
                selector: '.banner-link',
                attribute: 'href',
                default: { type: 'external', url: '', target: '_self' }
            }
        ],
        html: `
            <div class="bg-gray-50 py-4">
                <div class="mx-auto max-w-7xl px-4">
                    <a href="#" class="banner-link block overflow-hidden rounded-lg">
                        <img src="https://via.placeholder.com/1260x285/E5E7EB/9CA3AF?text=1260x285" alt="" class="banner-image w-full h-auto object-cover">
                    </a>
                </div>
            </div>
        `,
    },
    {
        id: 'banners/slim-banner',
        category: 'promo-sections',
        name: { ar: 'بانر رفيع', en: 'Slim Banner' },
        description: { ar: 'بانر رفيع مناسب للإعلانات', en: 'Slim banner suitable for ads' },
        thumbnail: createThumbnail('#FEF3C7', '#D97706'),
        tags: ['banner', 'slim', 'ad'],
        fields: [
            {
                id: 'bannerImage',
                type: 'image',
                label: { ar: 'صورة البنر', en: 'Banner Image' },
                hint: { ar: 'المقاس المناسب للصورة هو 728×90 بكسل', en: 'Recommended size is 728×90 pixels' },
                required: true,
                selector: '.banner-image',
                attribute: 'src',
                dimensions: { width: 728, height: 90 },
                default: ''
            },
            {
                id: 'bannerLink',
                type: 'link',
                label: { ar: 'الرابط عند الضغط', en: 'Link on click' },
                selector: '.banner-link',
                attribute: 'href',
                default: { type: 'external', url: '', target: '_self' }
            }
        ],
        html: `
            <div class="bg-white py-4">
                <div class="mx-auto max-w-3xl px-4">
                    <a href="#" class="banner-link block overflow-hidden rounded-lg shadow-sm">
                        <img src="https://via.placeholder.com/728x90/FEF3C7/D97706?text=728x90" alt="" class="banner-image w-full h-auto">
                    </a>
                </div>
            </div>
        `,
    },

    // ============================================
    // PROMO SECTIONS
    // ============================================
    {
        id: 'promo-sections/hero-with-background',
        category: 'promo-sections',
        name: { ar: 'قسم بطل مع صورة خلفية', en: 'Hero with Background' },
        description: { ar: 'قسم عرض رئيسي مع صورة خلفية وزر دعوة للعمل', en: 'Main hero section with background image and CTA' },
        thumbnail: createThumbnail('#1F2937', '#F9FAFB'),
        tags: ['hero', 'banner', 'promo'],
        // Editable fields configuration
        fields: [
            {
                id: 'backgroundImage',
                type: 'image',
                label: { ar: 'صورة الخلفية', en: 'Background Image' },
                hint: { ar: 'المقاس المناسب 1920×800 بكسل', en: 'Recommended size 1920×800px' },
                required: true,
                selector: '.hero-bg-image',
                attribute: 'src',
                default: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200'
            },
            {
                id: 'title',
                type: 'text',
                label: { ar: 'العنوان الرئيسي', en: 'Main Title' },
                required: true,
                maxLength: 60,
                selector: '.hero-title',
                attribute: 'textContent',
                default: 'مجموعة الموسم الجديدة'
            },
            {
                id: 'subtitle',
                type: 'textarea',
                label: { ar: 'النص الفرعي', en: 'Subtitle' },
                maxLength: 150,
                selector: '.hero-subtitle',
                attribute: 'textContent',
                default: 'اكتشف أحدث التصاميم والموديلات لهذا الموسم بأفضل الأسعار'
            },
            {
                id: 'buttonText',
                type: 'text',
                label: { ar: 'نص الزر', en: 'Button Text' },
                maxLength: 20,
                selector: '.hero-button',
                attribute: 'textContent',
                default: 'تسوق الآن'
            },
            {
                id: 'buttonLink',
                type: 'link',
                label: { ar: 'رابط الزر', en: 'Button Link' },
                selector: '.hero-button',
                attribute: 'href',
                default: { type: 'external', url: '#', target: '_self' }
            },
            {
                id: 'overlayOpacity',
                type: 'number',
                label: { ar: 'شفافية التعتيم', en: 'Overlay Opacity' },
                min: 0,
                max: 100,
                step: 10,
                slider: true,
                unit: '%',
                default: 60
            },
            {
                id: 'showButton',
                type: 'toggle',
                label: { ar: 'إظهار الزر', en: 'Show Button' },
                default: true
            }
        ],
        html: `
            <div class="relative bg-gray-900">
                <div class="absolute inset-0 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200" alt="" class="hero-bg-image w-full h-full object-cover opacity-60">
                </div>
                <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
                    <div class="max-w-2xl text-right">
                        <h1 class="hero-title text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">مجموعة الموسم الجديدة</h1>
                        <p class="hero-subtitle mt-6 text-xl text-gray-300">اكتشف أحدث التصاميم والموديلات لهذا الموسم بأفضل الأسعار</p>
                        <div class="mt-10">
                            <a href="#" class="hero-button inline-block rounded-md bg-white px-8 py-3 text-base font-medium text-gray-900 hover:bg-gray-100">تسوق الآن</a>
                        </div>
                    </div>
                </div>
            </div>
        `,
    },
    {
        id: 'promo-sections/split-with-image',
        category: 'promo-sections',
        name: { ar: 'قسم منقسم مع صورة', en: 'Split with Image' },
        description: { ar: 'قسم ترويجي مقسم بين نص وصورة', en: 'Promotional section split between text and image' },
        thumbnail: createThumbnail('#E0F7FA', '#009688'),
        tags: ['split', 'promo', 'image'],
        fields: [
            {
                id: 'image',
                type: 'image',
                label: { ar: 'الصورة', en: 'Image' },
                required: true,
                selector: '.split-image',
                attribute: 'src',
                default: 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600'
            },
            {
                id: 'badge',
                type: 'text',
                label: { ar: 'الشارة', en: 'Badge' },
                maxLength: 30,
                selector: '.split-badge',
                attribute: 'textContent',
                default: 'عروض حصرية'
            },
            {
                id: 'title',
                type: 'text',
                label: { ar: 'العنوان', en: 'Title' },
                required: true,
                maxLength: 50,
                selector: '.split-title',
                attribute: 'textContent',
                default: 'خصومات تصل إلى 50%'
            },
            {
                id: 'description',
                type: 'textarea',
                label: { ar: 'الوصف', en: 'Description' },
                maxLength: 200,
                selector: '.split-description',
                attribute: 'textContent',
                default: 'استمتع بأفضل العروض على مجموعة واسعة من المنتجات. العرض لفترة محدودة!'
            },
            {
                id: 'linkText',
                type: 'text',
                label: { ar: 'نص الرابط', en: 'Link Text' },
                maxLength: 30,
                selector: '.split-link-text',
                attribute: 'textContent',
                default: 'اكتشف العروض'
            },
            {
                id: 'link',
                type: 'link',
                label: { ar: 'الرابط', en: 'Link' },
                selector: '.split-link',
                attribute: 'href',
                default: { type: 'page', url: '#', target: '_self' }
            },
            {
                id: 'showBadge',
                type: 'toggle',
                label: { ar: 'إظهار الشارة', en: 'Show Badge' },
                default: true
            },
            {
                id: 'imagePosition',
                type: 'select',
                label: { ar: 'موقع الصورة', en: 'Image Position' },
                options: [
                    { value: 'right', label: { ar: 'يمين', en: 'Right' } },
                    { value: 'left', label: { ar: 'يسار', en: 'Left' } }
                ],
                default: 'right'
            }
        ],
        html: `
            <div class="bg-white">
                <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-16 items-center">
                        <div class="order-2 lg:order-1">
                            <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600" alt="" class="split-image rounded-lg shadow-lg w-full">
                        </div>
                        <div class="order-1 lg:order-2 text-right">
                            <span class="split-badge text-sm font-medium text-teal-600">عروض حصرية</span>
                            <h2 class="split-title mt-2 text-3xl font-bold text-gray-900">خصومات تصل إلى 50%</h2>
                            <p class="split-description mt-4 text-lg text-gray-600">استمتع بأفضل العروض على مجموعة واسعة من المنتجات. العرض لفترة محدودة!</p>
                            <a href="#" class="split-link mt-6 inline-flex items-center gap-2 text-teal-600 font-medium hover:text-teal-700">
                                <span>←</span>
                                <span class="split-link-text">اكتشف العروض</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `,
    },
    {
        id: 'promo-sections/three-column-cards',
        category: 'promo-sections',
        name: { ar: 'بطاقات ثلاثة أعمدة', en: 'Three Column Cards' },
        description: { ar: 'ثلاث بطاقات ترويجية في صف واحد', en: 'Three promotional cards in a row' },
        thumbnail: createThumbnail('#F9FAFB', '#6B7280'),
        tags: ['cards', 'grid', 'promo'],
        html: `
            <div class="bg-gray-50 py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="group relative overflow-hidden rounded-lg">
                            <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400" alt="" class="h-64 w-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <div class="absolute bottom-4 right-4 text-white text-right">
                                <h3 class="text-lg font-bold">الإلكترونيات</h3>
                                <p class="text-sm opacity-90">أحدث الأجهزة</p>
                            </div>
                        </div>
                        <div class="group relative overflow-hidden rounded-lg">
                            <img src="https://images.unsplash.com/photo-1560343090-f0409e92791a?w=400" alt="" class="h-64 w-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <div class="absolute bottom-4 right-4 text-white text-right">
                                <h3 class="text-lg font-bold">الأزياء</h3>
                                <p class="text-sm opacity-90">موضة عصرية</p>
                            </div>
                        </div>
                        <div class="group relative overflow-hidden rounded-lg">
                            <img src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400" alt="" class="h-64 w-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <div class="absolute bottom-4 right-4 text-white text-right">
                                <h3 class="text-lg font-bold">المنزل</h3>
                                <p class="text-sm opacity-90">ديكور وأثاث</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
    },

    // ============================================
    // PRODUCT LISTS
    // ============================================
    {
        id: 'product-lists/simple-grid',
        category: 'product-lists',
        name: { ar: 'شبكة منتجات بسيطة', en: 'Simple Product Grid' },
        description: { ar: 'عرض بسيط للمنتجات في شبكة من 4 أعمدة', en: 'Simple product display in 4-column grid' },
        thumbnail: createThumbnail('#FFFFFF', '#E5E7EB'),
        tags: ['products', 'grid', 'simple'],
        fields: [
            {
                id: 'title',
                type: 'text',
                label: { ar: 'العنوان', en: 'Title' },
                required: true,
                maxLength: 50,
                selector: '.product-grid-title',
                attribute: 'textContent',
                default: 'منتجاتنا المميزة'
            },
            {
                id: 'showTitle',
                type: 'toggle',
                label: { ar: 'إظهار العنوان', en: 'Show Title' },
                default: true
            },
            {
                id: 'columns',
                type: 'select',
                label: { ar: 'عدد الأعمدة', en: 'Number of Columns' },
                options: [
                    { value: '2', label: { ar: '2 أعمدة', en: '2 columns' } },
                    { value: '3', label: { ar: '3 أعمدة', en: '3 columns' } },
                    { value: '4', label: { ar: '4 أعمدة', en: '4 columns' } }
                ],
                default: '4'
            },
            {
                id: 'productCount',
                type: 'number',
                label: { ar: 'عدد المنتجات', en: 'Product Count' },
                min: 2,
                max: 12,
                step: 1,
                slider: true,
                default: 4
            },
            {
                id: 'backgroundColor',
                type: 'color',
                label: { ar: 'لون الخلفية', en: 'Background Color' },
                default: '#FFFFFF'
            }
        ],
        html: `
            <div class="bg-white py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="product-grid-title text-2xl font-bold text-gray-900 text-right mb-8">منتجاتنا المميزة</h2>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                        <a href="#" class="group">
                            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                                <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=300" alt="" class="h-full w-full object-cover group-hover:opacity-75 transition">
                            </div>
                            <h3 class="mt-3 text-sm text-gray-700 text-right">ساعة ذكية</h3>
                            <p class="mt-1 text-lg font-medium text-gray-900 text-right">299 ر.س</p>
                        </a>
                        <a href="#" class="group">
                            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300" alt="" class="h-full w-full object-cover group-hover:opacity-75 transition">
                            </div>
                            <h3 class="mt-3 text-sm text-gray-700 text-right">سماعات لاسلكية</h3>
                            <p class="mt-1 text-lg font-medium text-gray-900 text-right">199 ر.س</p>
                        </a>
                        <a href="#" class="group">
                            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                                <img src="https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=300" alt="" class="h-full w-full object-cover group-hover:opacity-75 transition">
                            </div>
                            <h3 class="mt-3 text-sm text-gray-700 text-right">نظارات شمسية</h3>
                            <p class="mt-1 text-lg font-medium text-gray-900 text-right">149 ر.س</p>
                        </a>
                        <a href="#" class="group">
                            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                                <img src="https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=300" alt="" class="h-full w-full object-cover group-hover:opacity-75 transition">
                            </div>
                            <h3 class="mt-3 text-sm text-gray-700 text-right">كاميرا فورية</h3>
                            <p class="mt-1 text-lg font-medium text-gray-900 text-right">399 ر.س</p>
                        </a>
                    </div>
                </div>
            </div>
        `,
    },
    {
        id: 'product-lists/with-quick-view',
        category: 'product-lists',
        name: { ar: 'منتجات مع عرض سريع', en: 'Products with Quick View' },
        description: { ar: 'بطاقات منتجات مع زر عرض سريع عند التمرير', en: 'Product cards with quick view button on hover' },
        thumbnail: createThumbnail('#F9FAFB', '#009688'),
        tags: ['products', 'hover', 'quick-view'],
        html: `
            <div class="bg-gray-50 py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between mb-8">
                        <a href="#" class="text-teal-600 text-sm font-medium hover:text-teal-700">عرض الكل ←</a>
                        <h2 class="text-2xl font-bold text-gray-900">وصل حديثاً</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                        <div class="group relative">
                            <div class="aspect-square overflow-hidden rounded-lg bg-white">
                                <img src="https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=300" alt="" class="h-full w-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition bg-black/20">
                                    <button class="bg-white text-gray-900 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-100">عرض سريع</button>
                                </div>
                            </div>
                            <div class="mt-3 text-right">
                                <h3 class="text-sm text-gray-700">عطر فاخر</h3>
                                <div class="flex items-center justify-end gap-2 mt-1">
                                    <span class="text-sm text-gray-400 line-through">450 ر.س</span>
                                    <span class="text-lg font-medium text-gray-900">350 ر.س</span>
                                </div>
                            </div>
                            <span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">-22%</span>
                        </div>
                        <div class="group relative">
                            <div class="aspect-square overflow-hidden rounded-lg bg-white">
                                <img src="https://images.unsplash.com/photo-1587467512961-120760940315?w=300" alt="" class="h-full w-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition bg-black/20">
                                    <button class="bg-white text-gray-900 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-100">عرض سريع</button>
                                </div>
                            </div>
                            <div class="mt-3 text-right">
                                <h3 class="text-sm text-gray-700">حقيبة جلدية</h3>
                                <p class="mt-1 text-lg font-medium text-gray-900">599 ر.س</p>
                            </div>
                            <span class="absolute top-2 right-2 bg-teal-500 text-white text-xs px-2 py-1 rounded">جديد</span>
                        </div>
                        <div class="group relative">
                            <div class="aspect-square overflow-hidden rounded-lg bg-white">
                                <img src="https://images.unsplash.com/photo-1491553895911-0055uj0d8bb9?w=300" alt="" class="h-full w-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition bg-black/20">
                                    <button class="bg-white text-gray-900 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-100">عرض سريع</button>
                                </div>
                            </div>
                            <div class="mt-3 text-right">
                                <h3 class="text-sm text-gray-700">قميص قطني</h3>
                                <p class="mt-1 text-lg font-medium text-gray-900">180 ر.س</p>
                            </div>
                        </div>
                        <div class="group relative">
                            <div class="aspect-square overflow-hidden rounded-lg bg-white">
                                <img src="https://images.unsplash.com/photo-1549298916-b41d501d3772?w=300" alt="" class="h-full w-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition bg-black/20">
                                    <button class="bg-white text-gray-900 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-100">عرض سريع</button>
                                </div>
                            </div>
                            <div class="mt-3 text-right">
                                <h3 class="text-sm text-gray-700">حذاء رياضي</h3>
                                <p class="mt-1 text-lg font-medium text-gray-900">420 ر.س</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
    },

    // ============================================
    // INCENTIVES
    // ============================================
    {
        id: 'incentives/three-column-icons',
        category: 'incentives',
        name: { ar: 'مميزات بثلاثة أعمدة', en: '3 Column with Icons' },
        description: { ar: 'عرض مميزات المتجر بأيقونات في ثلاثة أعمدة', en: 'Store features with icons in 3 columns' },
        thumbnail: createThumbnail('#E0F7FA', '#009688'),
        tags: ['features', 'icons', 'incentives'],
        html: `
            <div class="bg-teal-50 py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-3">
                        <div class="text-center">
                            <div class="mx-auto h-14 w-14 rounded-full bg-teal-100 flex items-center justify-center">
                                <svg class="h-7 w-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">ضمان الجودة</h3>
                            <p class="mt-2 text-sm text-gray-600">جميع منتجاتنا أصلية 100% مع ضمان لمدة عام</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto h-14 w-14 rounded-full bg-teal-100 flex items-center justify-center">
                                <svg class="h-7 w-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">توصيل سريع</h3>
                            <p class="mt-2 text-sm text-gray-600">توصيل خلال 24 ساعة لجميع مدن المملكة</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto h-14 w-14 rounded-full bg-teal-100 flex items-center justify-center">
                                <svg class="h-7 w-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">دفع آمن</h3>
                            <p class="mt-2 text-sm text-gray-600">طرق دفع متعددة وآمنة 100%</p>
                        </div>
                    </div>
                </div>
            </div>
        `,
    },
    {
        id: 'incentives/banner-with-icons',
        category: 'incentives',
        name: { ar: 'شريط المميزات', en: 'Features Banner' },
        description: { ar: 'شريط أفقي يعرض مميزات المتجر', en: 'Horizontal banner showing store features' },
        thumbnail: createThumbnail('#1F2937', '#F9FAFB'),
        tags: ['banner', 'features', 'dark'],
        html: `
            <div class="bg-gray-900 py-4">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-wrap items-center justify-center gap-8 text-white">
                        <div class="flex items-center gap-3">
                            <svg class="h-6 w-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span class="text-sm">شحن مجاني للطلبات فوق 200 ر.س</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="h-6 w-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="text-sm">استرجاع مجاني خلال 14 يوم</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="h-6 w-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="text-sm">دعم على مدار الساعة</span>
                        </div>
                    </div>
                </div>
            </div>
        `,
    },

    // ============================================
    // CATEGORY PREVIEWS
    // ============================================
    {
        id: 'category-previews/three-column',
        category: 'category-previews',
        name: { ar: 'تصنيفات ثلاثة أعمدة', en: 'Three Column Categories' },
        description: { ar: 'عرض التصنيفات الرئيسية في ثلاثة أعمدة', en: 'Main categories in 3 columns' },
        thumbnail: createThumbnail('#F9FAFB', '#6B7280'),
        tags: ['categories', 'grid'],
        html: `
            <div class="bg-white py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-gray-900 text-right mb-8">تسوق حسب التصنيف</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <a href="#" class="group relative block h-80 overflow-hidden rounded-lg">
                            <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400" alt="" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                            <div class="absolute bottom-6 right-6">
                                <h3 class="text-xl font-bold text-white">ملابس رجالية</h3>
                                <p class="mt-1 text-sm text-gray-200">125 منتج</p>
                            </div>
                        </a>
                        <a href="#" class="group relative block h-80 overflow-hidden rounded-lg">
                            <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=400" alt="" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                            <div class="absolute bottom-6 right-6">
                                <h3 class="text-xl font-bold text-white">ملابس نسائية</h3>
                                <p class="mt-1 text-sm text-gray-200">230 منتج</p>
                            </div>
                        </a>
                        <a href="#" class="group relative block h-80 overflow-hidden rounded-lg">
                            <img src="https://images.unsplash.com/photo-1445205170230-053b83016050?w=400" alt="" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                            <div class="absolute bottom-6 right-6">
                                <h3 class="text-xl font-bold text-white">إكسسوارات</h3>
                                <p class="mt-1 text-sm text-gray-200">85 منتج</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        `,
    },

    // ============================================
    // REVIEWS
    // ============================================
    {
        id: 'reviews/testimonials-grid',
        category: 'reviews',
        name: { ar: 'شبكة آراء العملاء', en: 'Testimonials Grid' },
        description: { ar: 'عرض آراء وتقييمات العملاء في شبكة', en: 'Customer testimonials in grid layout' },
        thumbnail: createThumbnail('#FFFFFF', '#FCD34D'),
        tags: ['reviews', 'testimonials', 'ratings'],
        html: `
            <div class="bg-white py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12">
                        <h2 class="text-2xl font-bold text-gray-900">ماذا يقول عملاؤنا</h2>
                        <p class="mt-2 text-gray-600">آراء حقيقية من عملاء سعداء</p>
                    </div>
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                        <div class="bg-gray-50 rounded-lg p-6 text-right">
                            <div class="flex items-center gap-1 justify-end mb-4">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            </div>
                            <p class="text-gray-600 mb-4">"تجربة تسوق رائعة! المنتجات بجودة عالية والتوصيل سريع جداً. أنصح الجميع بالتعامل معهم."</p>
                            <div class="flex items-center gap-3 justify-end">
                                <div>
                                    <p class="font-medium text-gray-900">أحمد محمد</p>
                                    <p class="text-sm text-gray-500">الرياض</p>
                                </div>
                                <img src="https://i.pravatar.cc/48?img=1" alt="" class="w-12 h-12 rounded-full">
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-6 text-right">
                            <div class="flex items-center gap-1 justify-end mb-4">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            </div>
                            <p class="text-gray-600 mb-4">"أفضل متجر تعاملت معه. خدمة العملاء ممتازة ومتجاوبين دائماً. شكراً لكم!"</p>
                            <div class="flex items-center gap-3 justify-end">
                                <div>
                                    <p class="font-medium text-gray-900">سارة العمري</p>
                                    <p class="text-sm text-gray-500">جدة</p>
                                </div>
                                <img src="https://i.pravatar.cc/48?img=5" alt="" class="w-12 h-12 rounded-full">
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-6 text-right">
                            <div class="flex items-center gap-1 justify-end mb-4">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            </div>
                            <p class="text-gray-600 mb-4">"منتجات أصلية وأسعار مناسبة. التغليف ممتاز والمنتج وصل بحالة ممتازة."</p>
                            <div class="flex items-center gap-3 justify-end">
                                <div>
                                    <p class="font-medium text-gray-900">خالد السعيد</p>
                                    <p class="text-sm text-gray-500">الدمام</p>
                                </div>
                                <img src="https://i.pravatar.cc/48?img=3" alt="" class="w-12 h-12 rounded-full">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
    },

    // ============================================
    // STORE NAVIGATION
    // ============================================
    {
        id: 'store-navigation/simple-header',
        category: 'store-navigation',
        name: { ar: 'رأس صفحة بسيط', en: 'Simple Header' },
        description: { ar: 'رأس صفحة بسيط مع شعار وقائمة وأيقونات', en: 'Simple header with logo, menu and icons' },
        thumbnail: createThumbnail('#FFFFFF', '#374151'),
        tags: ['header', 'navigation', 'menu'],
        html: `
            <header class="bg-white shadow-sm">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">
                        <div class="flex items-center gap-4">
                            <button class="p-2 text-gray-600 hover:text-gray-900">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            </button>
                            <button class="p-2 text-gray-600 hover:text-gray-900">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </button>
                        </div>
                        <nav class="hidden md:flex items-center gap-8">
                            <a href="#" class="text-sm font-medium text-gray-600 hover:text-gray-900">تواصل معنا</a>
                            <a href="#" class="text-sm font-medium text-gray-600 hover:text-gray-900">من نحن</a>
                            <a href="#" class="text-sm font-medium text-gray-600 hover:text-gray-900">المنتجات</a>
                            <a href="#" class="text-sm font-medium text-gray-900">الرئيسية</a>
                        </nav>
                        <a href="#" class="text-2xl font-bold text-teal-600">متجري</a>
                    </div>
                </div>
            </header>
        `,
    },
];

export default sampleComponents;
