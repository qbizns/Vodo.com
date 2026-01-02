<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Tests for XSS sanitization in commerce views.
 *
 * Covers:
 * - Product description sanitization
 * - Splash icon sanitization
 * - HTML purification
 */
class XssSanitizationTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Product Description Sanitization Tests
    // =========================================================================

    public function test_product_description_sanitizes_script_tags(): void
    {
        $maliciousHtml = '<p>Safe content</p><script>alert("xss")</script>';

        $sanitized = Str::sanitizeHtml($maliciousHtml);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('</script>', $sanitized);
        $this->assertStringContainsString('Safe content', $sanitized);
    }

    public function test_product_description_sanitizes_event_handlers(): void
    {
        $maliciousHtml = '<img src="x" onerror="alert(1)"><p>Content</p>';

        $sanitized = Str::sanitizeHtml($maliciousHtml);

        $this->assertStringNotContainsString('onerror', strtolower($sanitized));
    }

    public function test_product_description_sanitizes_javascript_urls(): void
    {
        $maliciousHtml = '<a href="javascript:alert(1)">Click me</a>';

        $sanitized = Str::sanitizeHtml($maliciousHtml);

        $this->assertStringNotContainsString('javascript:', strtolower($sanitized));
    }

    public function test_product_description_preserves_safe_html(): void
    {
        $safeHtml = '<p>This is <strong>bold</strong> and <em>italic</em> text.</p>';

        $sanitized = Str::sanitizeHtml($safeHtml);

        $this->assertStringContainsString('<p>', $sanitized);
        $this->assertStringContainsString('<strong>', $sanitized);
        $this->assertStringContainsString('<em>', $sanitized);
    }

    public function test_product_description_preserves_links(): void
    {
        $htmlWithLinks = '<p>Check out <a href="https://example.com">our website</a></p>';

        $sanitized = Str::sanitizeHtml($htmlWithLinks);

        $this->assertStringContainsString('<a href=', $sanitized);
        $this->assertStringContainsString('https://example.com', $sanitized);
    }

    public function test_product_description_preserves_images(): void
    {
        $htmlWithImages = '<p>Product image: <img src="https://example.com/image.jpg" alt="Product"></p>';

        $sanitized = Str::sanitizeHtml($htmlWithImages);

        $this->assertStringContainsString('<img', $sanitized);
        $this->assertStringContainsString('src=', $sanitized);
    }

    public function test_product_description_removes_style_tags(): void
    {
        $htmlWithStyle = '<style>body { display: none; }</style><p>Content</p>';

        $sanitized = Str::sanitizeHtml($htmlWithStyle);

        $this->assertStringNotContainsString('<style>', $sanitized);
    }

    public function test_product_description_removes_iframe(): void
    {
        $htmlWithIframe = '<iframe src="https://malicious.com"></iframe><p>Content</p>';

        $sanitized = Str::sanitizeHtml($htmlWithIframe);

        $this->assertStringNotContainsString('<iframe', strtolower($sanitized));
    }

    // =========================================================================
    // SVG Sanitization Tests (for splash icon)
    // =========================================================================

    public function test_svg_sanitization_removes_script(): void
    {
        $maliciousSvg = '<svg><script>alert(1)</script><circle r="10"/></svg>';

        $allowedTags = ['svg', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'g'];
        $sanitized = Str::sanitizeHtml($maliciousSvg, $allowedTags);

        $this->assertStringNotContainsString('<script>', $sanitized);
    }

    public function test_svg_sanitization_removes_event_handlers(): void
    {
        $maliciousSvg = '<svg onload="alert(1)"><circle r="10"/></svg>';

        $allowedTags = ['svg', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'g'];
        $sanitized = Str::sanitizeHtml($maliciousSvg, $allowedTags);

        $this->assertStringNotContainsString('onload', strtolower($sanitized));
    }

    public function test_svg_sanitization_preserves_safe_elements(): void
    {
        $safeSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><circle cx="12" cy="12" r="10"/></svg>';

        $allowedTags = ['svg', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'g'];
        $sanitized = Str::sanitizeHtml($safeSvg, $allowedTags);

        $this->assertStringContainsString('<svg', $sanitized);
        $this->assertStringContainsString('<circle', $sanitized);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function test_handles_null_input(): void
    {
        $sanitized = Str::sanitizeHtml(null);

        $this->assertEquals('', $sanitized);
    }

    public function test_handles_empty_string(): void
    {
        $sanitized = Str::sanitizeHtml('');

        $this->assertEquals('', $sanitized);
    }

    public function test_handles_plain_text(): void
    {
        $plainText = 'This is just plain text without any HTML.';

        $sanitized = Str::sanitizeHtml($plainText);

        $this->assertEquals($plainText, $sanitized);
    }

    public function test_handles_deeply_nested_xss(): void
    {
        $nestedXss = '<div><p><span><a href="#" onclick="alert(1)">Click</a></span></p></div>';

        $sanitized = Str::sanitizeHtml($nestedXss);

        $this->assertStringNotContainsString('onclick', strtolower($sanitized));
    }

    public function test_handles_encoded_xss(): void
    {
        // HTML entity encoded script tag
        $encodedXss = '&#60;script&#62;alert(1)&#60;/script&#62;';

        $sanitized = Str::sanitizeHtml($encodedXss);

        // After sanitization and decoding, should not execute
        $this->assertStringNotContainsString('<script>', html_entity_decode($sanitized));
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    public function test_blade_uses_sanitize_html(): void
    {
        // Read the blade file to verify Str::sanitizeHtml is used
        $bladeContent = file_get_contents(
            base_path('app/Plugins/vodo-commerce/resources/views/storefront/products/show.blade.php')
        );

        $this->assertStringContainsString(
            'Str::sanitizeHtml($product->description)',
            $bladeContent,
            'Product description should use Str::sanitizeHtml()'
        );
    }

    public function test_splash_blade_uses_sanitize_html(): void
    {
        // Read the splash blade file to verify Str::sanitizeHtml is used
        $bladeContent = file_get_contents(
            base_path('resources/views/backend/partials/splash.blade.php')
        );

        $this->assertStringContainsString(
            'Str::sanitizeHtml($splashIcon',
            $bladeContent,
            'Splash icon should use Str::sanitizeHtml()'
        );
    }
}
