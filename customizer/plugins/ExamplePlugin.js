/**
 * TailwindPlus Customizer - Example Plugin
 * =========================================
 * Demonstrates how to create a plugin
 * 
 * @module plugins/ExamplePlugin
 * @version 1.0.0
 */

import { Plugin } from './Plugin.js';

export class ExamplePlugin extends Plugin {
    static id = 'example-plugin';
    static name = { ar: 'مكونات إضافية', en: 'Extra Components' };
    static version = '1.0.0';
    static author = 'TailwindPlus Team';
    static description = { 
        ar: 'مجموعة من المكونات الإضافية', 
        en: 'Collection of additional components' 
    };

    /**
     * Get plugin components
     * @returns {Array} Component configurations
     */
    getComponents() {
        return [
            {
                id: 'example/newsletter-signup',
                category: 'promo-sections',
                name: { ar: 'نموذج الاشتراك بالنشرة', en: 'Newsletter Signup' },
                description: { ar: 'نموذج اشتراك في النشرة البريدية', en: 'Email newsletter subscription form' },
                tags: ['newsletter', 'email', 'form'],
                html: `
                    <div class="bg-teal-600 py-16">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div class="text-center">
                                <h2 class="text-2xl font-bold text-white">اشترك في نشرتنا البريدية</h2>
                                <p class="mt-2 text-teal-100">احصل على آخر العروض والمنتجات الجديدة مباشرة في بريدك</p>
                                <div class="mt-6 flex justify-center">
                                    <div class="flex w-full max-w-md">
                                        <button class="bg-white text-teal-600 px-6 py-3 rounded-r-lg font-medium hover:bg-gray-100">اشترك</button>
                                        <input type="email" placeholder="بريدك الإلكتروني" class="flex-1 px-4 py-3 rounded-l-lg text-right focus:outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
            },
            {
                id: 'example/cta-banner',
                category: 'promo-sections',
                name: { ar: 'شريط دعوة للعمل', en: 'CTA Banner' },
                description: { ar: 'شريط ملون مع زر دعوة للعمل', en: 'Colorful banner with call-to-action button' },
                tags: ['cta', 'banner', 'action'],
                html: `
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 py-12">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                                <div class="text-center md:text-right">
                                    <h2 class="text-2xl font-bold text-white">عروض نهاية الموسم</h2>
                                    <p class="mt-1 text-purple-100">خصومات تصل إلى 70% على جميع المنتجات</p>
                                </div>
                                <a href="#" class="inline-flex items-center gap-2 bg-white text-purple-600 px-8 py-3 rounded-lg font-medium hover:bg-gray-100 transition">
                                    <span>←</span>
                                    <span>تسوق الآن</span>
                                </a>
                            </div>
                        </div>
                    </div>
                `,
            },
            {
                id: 'example/countdown-timer',
                category: 'promo-sections',
                name: { ar: 'عداد تنازلي', en: 'Countdown Timer' },
                description: { ar: 'عداد تنازلي للعروض المحدودة', en: 'Countdown timer for limited offers' },
                tags: ['countdown', 'timer', 'offer'],
                html: `
                    <div class="bg-gray-900 py-8">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div class="text-center">
                                <p class="text-teal-400 text-sm font-medium mb-2">ينتهي العرض خلال</p>
                                <div class="flex justify-center gap-4">
                                    <div class="text-center">
                                        <div class="bg-white text-gray-900 w-16 h-16 rounded-lg flex items-center justify-center text-2xl font-bold">05</div>
                                        <p class="text-gray-400 text-xs mt-1">ثواني</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="bg-white text-gray-900 w-16 h-16 rounded-lg flex items-center justify-center text-2xl font-bold">30</div>
                                        <p class="text-gray-400 text-xs mt-1">دقائق</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="bg-white text-gray-900 w-16 h-16 rounded-lg flex items-center justify-center text-2xl font-bold">12</div>
                                        <p class="text-gray-400 text-xs mt-1">ساعات</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="bg-white text-gray-900 w-16 h-16 rounded-lg flex items-center justify-center text-2xl font-bold">02</div>
                                        <p class="text-gray-400 text-xs mt-1">أيام</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
            },
            {
                id: 'example/brand-logos',
                category: 'incentives',
                name: { ar: 'شعارات العلامات التجارية', en: 'Brand Logos' },
                description: { ar: 'عرض شعارات العلامات التجارية المتعاون معها', en: 'Display partner brand logos' },
                tags: ['brands', 'logos', 'partners'],
                html: `
                    <div class="bg-gray-50 py-12">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <p class="text-center text-sm text-gray-500 mb-8">نفخر بتعاوننا مع أفضل العلامات التجارية</p>
                            <div class="flex flex-wrap items-center justify-center gap-8 md:gap-16">
                                <div class="w-24 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 font-bold">BRAND</div>
                                <div class="w-24 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 font-bold">BRAND</div>
                                <div class="w-24 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 font-bold">BRAND</div>
                                <div class="w-24 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 font-bold">BRAND</div>
                                <div class="w-24 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400 font-bold">BRAND</div>
                            </div>
                        </div>
                    </div>
                `,
            },
            {
                id: 'example/simple-footer',
                category: 'store-navigation',
                name: { ar: 'ذيل صفحة بسيط', en: 'Simple Footer' },
                description: { ar: 'ذيل صفحة بسيط مع روابط ومعلومات التواصل', en: 'Simple footer with links and contact info' },
                tags: ['footer', 'navigation', 'contact'],
                html: `
                    <footer class="bg-gray-900 text-gray-300">
                        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                                <div class="text-right">
                                    <h3 class="text-white text-lg font-bold mb-4">متجري</h3>
                                    <p class="text-sm">متجرك الأول للتسوق الإلكتروني. نقدم أفضل المنتجات بأفضل الأسعار.</p>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-white font-medium mb-4">روابط سريعة</h4>
                                    <ul class="space-y-2 text-sm">
                                        <li><a href="#" class="hover:text-white">الرئيسية</a></li>
                                        <li><a href="#" class="hover:text-white">المنتجات</a></li>
                                        <li><a href="#" class="hover:text-white">العروض</a></li>
                                        <li><a href="#" class="hover:text-white">تواصل معنا</a></li>
                                    </ul>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-white font-medium mb-4">خدمة العملاء</h4>
                                    <ul class="space-y-2 text-sm">
                                        <li><a href="#" class="hover:text-white">الأسئلة الشائعة</a></li>
                                        <li><a href="#" class="hover:text-white">سياسة الإرجاع</a></li>
                                        <li><a href="#" class="hover:text-white">الشحن والتوصيل</a></li>
                                        <li><a href="#" class="hover:text-white">طرق الدفع</a></li>
                                    </ul>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-white font-medium mb-4">تواصل معنا</h4>
                                    <ul class="space-y-2 text-sm">
                                        <li>الرياض، المملكة العربية السعودية</li>
                                        <li>info@mystore.com</li>
                                        <li>+966 50 000 0000</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                                <p>© 2024 متجري. جميع الحقوق محفوظة.</p>
                            </div>
                        </div>
                    </footer>
                `,
            },
        ];
    }

    /**
     * Called when plugin is registered
     * @param {Object} registry - Component registry
     */
    onRegister(registry) {
        console.log(`✅ ${this.constructor.name.ar} تم تفعيله`);
    }

    /**
     * Called when plugin is unregistered
     * @param {Object} registry - Component registry
     */
    onUnregister(registry) {
        console.log(`❌ ${this.constructor.name.ar} تم إلغاء تفعيله`);
    }
}

export default ExamplePlugin;
