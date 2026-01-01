<?php

declare(strict_types=1);

namespace VodoCommerce\Theme;

/**
 * CommerceThemeSlots - Defines all available theme slots for commerce storefronts.
 *
 * Slots are injection points where theme extensions can add content.
 * Each slot has a unique identifier and is rendered at a specific location
 * in the storefront templates.
 *
 * Usage in Blade templates:
 * {!! render_slot('commerce.product.after_title') !!}
 *
 * Usage in extensions:
 * public function renderSlot(string $slot, array $context): ?string {
 *     if ($slot === CommerceThemeSlots::PRODUCT_AFTER_TITLE) {
 *         return view('my-extension::badge', $context)->render();
 *     }
 *     return null;
 * }
 */
class CommerceThemeSlots
{
    // =========================================================================
    // Global / Layout Slots
    // =========================================================================

    /** Before the opening <body> content */
    public const LAYOUT_BODY_START = 'commerce.layout.body_start';

    /** After the closing </body> content */
    public const LAYOUT_BODY_END = 'commerce.layout.body_end';

    /** Inside <head> before closing */
    public const LAYOUT_HEAD = 'commerce.layout.head';

    /** Header bar, after logo */
    public const HEADER_AFTER_LOGO = 'commerce.header.after_logo';

    /** Header bar, before cart */
    public const HEADER_BEFORE_CART = 'commerce.header.before_cart';

    /** Footer, before content */
    public const FOOTER_START = 'commerce.footer.start';

    /** Footer, after content */
    public const FOOTER_END = 'commerce.footer.end';

    // =========================================================================
    // Product Listing Slots
    // =========================================================================

    /** Before the product grid */
    public const PRODUCT_LIST_BEFORE = 'commerce.product_list.before';

    /** After the product grid */
    public const PRODUCT_LIST_AFTER = 'commerce.product_list.after';

    /** Before filters sidebar */
    public const PRODUCT_FILTERS_BEFORE = 'commerce.product_filters.before';

    /** After filters sidebar */
    public const PRODUCT_FILTERS_AFTER = 'commerce.product_filters.after';

    // =========================================================================
    // Product Card Slots (in listings)
    // =========================================================================

    /** Top of product card, before image */
    public const PRODUCT_CARD_TOP = 'commerce.product_card.top';

    /** Over product card image (overlay) */
    public const PRODUCT_CARD_IMAGE_OVERLAY = 'commerce.product_card.image_overlay';

    /** After product card title */
    public const PRODUCT_CARD_AFTER_TITLE = 'commerce.product_card.after_title';

    /** After product card price */
    public const PRODUCT_CARD_AFTER_PRICE = 'commerce.product_card.after_price';

    /** Bottom of product card, after add to cart */
    public const PRODUCT_CARD_BOTTOM = 'commerce.product_card.bottom';

    // =========================================================================
    // Product Detail Page Slots
    // =========================================================================

    /** Before product content */
    public const PRODUCT_BEFORE = 'commerce.product.before';

    /** After product content */
    public const PRODUCT_AFTER = 'commerce.product.after';

    /** After product title */
    public const PRODUCT_AFTER_TITLE = 'commerce.product.after_title';

    /** After product price */
    public const PRODUCT_AFTER_PRICE = 'commerce.product.after_price';

    /** Before add to cart button */
    public const PRODUCT_BEFORE_ADD_TO_CART = 'commerce.product.before_add_to_cart';

    /** After add to cart button */
    public const PRODUCT_AFTER_ADD_TO_CART = 'commerce.product.after_add_to_cart';

    /** After product description */
    public const PRODUCT_AFTER_DESCRIPTION = 'commerce.product.after_description';

    /** Product gallery, after main image */
    public const PRODUCT_GALLERY_AFTER = 'commerce.product.gallery_after';

    /** Product tabs, additional tab content */
    public const PRODUCT_TABS = 'commerce.product.tabs';

    /** Related products section, before */
    public const PRODUCT_RELATED_BEFORE = 'commerce.product.related_before';

    // =========================================================================
    // Cart Slots
    // =========================================================================

    /** Before cart content */
    public const CART_BEFORE = 'commerce.cart.before';

    /** After cart content */
    public const CART_AFTER = 'commerce.cart.after';

    /** After each cart item row */
    public const CART_ITEM_AFTER = 'commerce.cart.item_after';

    /** Before cart totals */
    public const CART_TOTALS_BEFORE = 'commerce.cart.totals_before';

    /** After cart totals, before checkout button */
    public const CART_TOTALS_AFTER = 'commerce.cart.totals_after';

    /** Mini cart dropdown content */
    public const MINI_CART_CONTENT = 'commerce.mini_cart.content';

    // =========================================================================
    // Checkout Slots
    // =========================================================================

    /** Before checkout form */
    public const CHECKOUT_BEFORE = 'commerce.checkout.before';

    /** After checkout form */
    public const CHECKOUT_AFTER = 'commerce.checkout.after';

    /** Before shipping address form */
    public const CHECKOUT_SHIPPING_BEFORE = 'commerce.checkout.shipping_before';

    /** After shipping address form */
    public const CHECKOUT_SHIPPING_AFTER = 'commerce.checkout.shipping_after';

    /** Before shipping method selection */
    public const CHECKOUT_SHIPPING_METHODS_BEFORE = 'commerce.checkout.shipping_methods_before';

    /** After shipping method selection */
    public const CHECKOUT_SHIPPING_METHODS_AFTER = 'commerce.checkout.shipping_methods_after';

    /** Before payment method selection */
    public const CHECKOUT_PAYMENT_BEFORE = 'commerce.checkout.payment_before';

    /** After payment method selection */
    public const CHECKOUT_PAYMENT_AFTER = 'commerce.checkout.payment_after';

    /** Before order summary in checkout */
    public const CHECKOUT_SUMMARY_BEFORE = 'commerce.checkout.summary_before';

    /** After order summary in checkout */
    public const CHECKOUT_SUMMARY_AFTER = 'commerce.checkout.summary_after';

    /** Before place order button */
    public const CHECKOUT_PLACE_ORDER_BEFORE = 'commerce.checkout.place_order_before';

    // =========================================================================
    // Order Confirmation Slots
    // =========================================================================

    /** Order confirmation page, before content */
    public const ORDER_CONFIRMATION_BEFORE = 'commerce.order_confirmation.before';

    /** Order confirmation page, after content */
    public const ORDER_CONFIRMATION_AFTER = 'commerce.order_confirmation.after';

    /** After order details */
    public const ORDER_CONFIRMATION_DETAILS_AFTER = 'commerce.order_confirmation.details_after';

    // =========================================================================
    // Customer Account Slots
    // =========================================================================

    /** Account dashboard, before content */
    public const ACCOUNT_DASHBOARD_BEFORE = 'commerce.account.dashboard_before';

    /** Account dashboard, after content */
    public const ACCOUNT_DASHBOARD_AFTER = 'commerce.account.dashboard_after';

    /** Account sidebar, additional navigation */
    public const ACCOUNT_SIDEBAR = 'commerce.account.sidebar';

    /** Order history, after each order row */
    public const ACCOUNT_ORDER_AFTER = 'commerce.account.order_after';

    /**
     * Get all slot definitions with metadata.
     *
     * @return array<string, array{slot: string, location: string, description: string}>
     */
    public static function all(): array
    {
        return [
            // Layout
            self::LAYOUT_BODY_START => [
                'slot' => self::LAYOUT_BODY_START,
                'location' => 'Layout',
                'description' => 'Start of body content, good for announcement bars',
            ],
            self::LAYOUT_BODY_END => [
                'slot' => self::LAYOUT_BODY_END,
                'location' => 'Layout',
                'description' => 'End of body, good for modals and floating elements',
            ],
            self::LAYOUT_HEAD => [
                'slot' => self::LAYOUT_HEAD,
                'location' => 'Layout',
                'description' => 'Inside head tag, for meta tags and inline styles',
            ],
            self::HEADER_AFTER_LOGO => [
                'slot' => self::HEADER_AFTER_LOGO,
                'location' => 'Header',
                'description' => 'After the store logo',
            ],
            self::HEADER_BEFORE_CART => [
                'slot' => self::HEADER_BEFORE_CART,
                'location' => 'Header',
                'description' => 'Before the cart icon in header',
            ],

            // Product Listing
            self::PRODUCT_LIST_BEFORE => [
                'slot' => self::PRODUCT_LIST_BEFORE,
                'location' => 'Product Listing',
                'description' => 'Before the product grid',
            ],
            self::PRODUCT_LIST_AFTER => [
                'slot' => self::PRODUCT_LIST_AFTER,
                'location' => 'Product Listing',
                'description' => 'After the product grid',
            ],

            // Product Card
            self::PRODUCT_CARD_TOP => [
                'slot' => self::PRODUCT_CARD_TOP,
                'location' => 'Product Card',
                'description' => 'Top of product card, for badges',
            ],
            self::PRODUCT_CARD_IMAGE_OVERLAY => [
                'slot' => self::PRODUCT_CARD_IMAGE_OVERLAY,
                'location' => 'Product Card',
                'description' => 'Overlay on product image',
            ],
            self::PRODUCT_CARD_AFTER_PRICE => [
                'slot' => self::PRODUCT_CARD_AFTER_PRICE,
                'location' => 'Product Card',
                'description' => 'After product price in card',
            ],

            // Product Detail
            self::PRODUCT_BEFORE => [
                'slot' => self::PRODUCT_BEFORE,
                'location' => 'Product Page',
                'description' => 'Before product content',
            ],
            self::PRODUCT_AFTER_TITLE => [
                'slot' => self::PRODUCT_AFTER_TITLE,
                'location' => 'Product Page',
                'description' => 'After product title, for ratings/reviews',
            ],
            self::PRODUCT_AFTER_PRICE => [
                'slot' => self::PRODUCT_AFTER_PRICE,
                'location' => 'Product Page',
                'description' => 'After product price',
            ],
            self::PRODUCT_BEFORE_ADD_TO_CART => [
                'slot' => self::PRODUCT_BEFORE_ADD_TO_CART,
                'location' => 'Product Page',
                'description' => 'Before add to cart button',
            ],
            self::PRODUCT_AFTER_ADD_TO_CART => [
                'slot' => self::PRODUCT_AFTER_ADD_TO_CART,
                'location' => 'Product Page',
                'description' => 'After add to cart button',
            ],
            self::PRODUCT_TABS => [
                'slot' => self::PRODUCT_TABS,
                'location' => 'Product Page',
                'description' => 'Additional product tabs',
            ],

            // Cart
            self::CART_BEFORE => [
                'slot' => self::CART_BEFORE,
                'location' => 'Cart',
                'description' => 'Before cart content',
            ],
            self::CART_TOTALS_AFTER => [
                'slot' => self::CART_TOTALS_AFTER,
                'location' => 'Cart',
                'description' => 'After totals, for trust badges',
            ],

            // Checkout
            self::CHECKOUT_BEFORE => [
                'slot' => self::CHECKOUT_BEFORE,
                'location' => 'Checkout',
                'description' => 'Before checkout form',
            ],
            self::CHECKOUT_PAYMENT_AFTER => [
                'slot' => self::CHECKOUT_PAYMENT_AFTER,
                'location' => 'Checkout',
                'description' => 'After payment methods',
            ],
            self::CHECKOUT_PLACE_ORDER_BEFORE => [
                'slot' => self::CHECKOUT_PLACE_ORDER_BEFORE,
                'location' => 'Checkout',
                'description' => 'Before place order button',
            ],

            // Order Confirmation
            self::ORDER_CONFIRMATION_AFTER => [
                'slot' => self::ORDER_CONFIRMATION_AFTER,
                'location' => 'Order Confirmation',
                'description' => 'After confirmation content',
            ],

            // Account
            self::ACCOUNT_SIDEBAR => [
                'slot' => self::ACCOUNT_SIDEBAR,
                'location' => 'Account',
                'description' => 'Account navigation sidebar',
            ],
        ];
    }

    /**
     * Get slots grouped by location.
     *
     * @return array<string, array>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $slot) {
            $location = $slot['location'];
            if (!isset($grouped[$location])) {
                $grouped[$location] = [];
            }
            $grouped[$location][] = $slot;
        }
        return $grouped;
    }
}
