<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Compress Response Middleware
 *
 * Compresses HTML responses for AJAX/PJAX requests to improve
 * performance and reduce bandwidth.
 */
class CompressResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        // Only process successful responses
        if (!$response instanceof Response || $response->getStatusCode() !== 200) {
            return $response;
        }

        // Only compress AJAX/PJAX requests
        if (!$request->ajax() && !$request->header('X-PJAX')) {
            return $response;
        }

        // Skip if already compressed
        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        // Get content type
        $contentType = $response->headers->get('Content-Type', '');

        // Only compress HTML and JSON responses
        if (!$this->isCompressible($contentType)) {
            return $response;
        }

        $content = $response->getContent();

        // Skip small responses (compression overhead not worth it)
        if (strlen($content) < 1024) {
            return $response;
        }

        // Minify HTML content
        if (str_contains($contentType, 'html')) {
            $content = $this->minifyHtml($content);
            $response->setContent($content);
        }

        // Gzip compress if client accepts it
        $acceptEncoding = $request->header('Accept-Encoding', '');
        if (str_contains($acceptEncoding, 'gzip') && function_exists('gzencode')) {
            $compressed = gzencode($content, 6);

            if ($compressed !== false && strlen($compressed) < strlen($content)) {
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Content-Length', (string) strlen($compressed));
                $response->headers->set('Vary', 'Accept-Encoding');
            }
        }

        return $response;
    }

    /**
     * Check if content type is compressible.
     */
    protected function isCompressible(string $contentType): bool
    {
        $compressibleTypes = [
            'text/html',
            'text/plain',
            'text/css',
            'text/javascript',
            'application/javascript',
            'application/json',
            'application/xml',
            'text/xml',
        ];

        foreach ($compressibleTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Minify HTML content.
     *
     * Removes unnecessary whitespace and comments while preserving
     * content within <pre>, <script>, <style>, and <textarea> tags.
     */
    protected function minifyHtml(string $html): string
    {
        // Skip if empty
        if (empty($html)) {
            return $html;
        }

        // Preserve content in special tags
        $preserved = [];
        $preserveTags = ['pre', 'script', 'style', 'textarea', 'code'];

        foreach ($preserveTags as $tag) {
            $pattern = '/(<' . $tag . '[^>]*>)(.*?)(<\/' . $tag . '>)/is';
            $html = preg_replace_callback($pattern, function ($matches) use (&$preserved) {
                $key = '<!--PRESERVE:' . count($preserved) . '-->';
                $preserved[$key] = $matches[0];
                return $key;
            }, $html) ?? $html;
        }

        // Remove HTML comments (except IE conditionals and preserved markers)
        $html = preg_replace('/<!--(?!\[|PRESERVE:)[^\[>].*?-->/s', '', $html) ?? $html;

        // Collapse whitespace
        $html = preg_replace('/\s+/', ' ', $html) ?? $html;

        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html) ?? $html;

        // Remove whitespace around certain tags
        $html = preg_replace('/\s*(<\/?(?:html|head|body|div|p|br|hr|section|article|header|footer|nav|aside|main|ul|ol|li|dl|dt|dd|table|tr|td|th|thead|tbody|tfoot|form|fieldset|legend)[^>]*>)\s*/i', '$1', $html) ?? $html;

        // Restore preserved content
        foreach ($preserved as $key => $content) {
            $html = str_replace($key, $content, $html);
        }

        return trim($html);
    }
}
