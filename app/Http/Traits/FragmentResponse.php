<?php

declare(strict_types=1);

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Fragment Response Trait
 *
 * Provides helper methods for returning fragment responses
 * for PJAX/AJAX navigation.
 */
trait FragmentResponse
{
    /**
     * Return a view as a fragment if requested.
     *
     * When X-Fragment-Only header is present, returns JSON with content.
     * Otherwise, returns the full view.
     *
     * @param string $view View name
     * @param array $data View data
     * @param int $status HTTP status code
     * @return Response|JsonResponse|View
     */
    protected function fragment(string $view, array $data = [], int $status = 200)
    {
        $content = view($view, $data)->render();

        if (request()->header('X-Fragment-Only')) {
            return $this->fragmentJson([
                'content' => $this->minifyHtml($content),
                'title' => $data['title'] ?? null,
                'header' => $data['header'] ?? $data['title'] ?? null,
                'css' => $data['css'] ?? null,
            ], $status);
        }

        return response(view($view, $data), $status);
    }

    /**
     * Return raw HTML as a fragment.
     *
     * @param string $html HTML content
     * @param array $meta Metadata (title, header, css, etc.)
     * @param int $status HTTP status code
     * @return Response|JsonResponse
     */
    protected function fragmentHtml(string $html, array $meta = [], int $status = 200)
    {
        if (request()->header('X-Fragment-Only')) {
            return $this->fragmentJson([
                'content' => $this->minifyHtml($html),
                ...$meta,
            ], $status);
        }

        return response($html, $status)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Return a JSON fragment response.
     *
     * @param array $data Response data
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    protected function fragmentJson(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)
            ->header('X-Fragment', 'true');
    }

    /**
     * Return a success response with optional redirect.
     *
     * @param string|null $message Success message
     * @param string|null $redirect Redirect URL
     * @param array $data Additional data
     * @return JsonResponse
     */
    protected function fragmentSuccess(?string $message = null, ?string $redirect = null, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => $redirect,
            ...$data,
        ]);
    }

    /**
     * Return an error response.
     *
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param array $errors Validation errors
     * @return JsonResponse
     */
    protected function fragmentError(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $data = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $data['errors'] = $errors;
        }

        return response()->json($data, $status);
    }

    /**
     * Check if request wants a fragment response.
     *
     * @return bool
     */
    protected function wantsFragment(): bool
    {
        return request()->header('X-Fragment-Only') ||
               request()->header('X-PJAX') ||
               request()->ajax();
    }

    /**
     * Minify HTML content.
     *
     * @param string $html
     * @return string
     */
    protected function minifyHtml(string $html): string
    {
        // Skip if in debug mode
        if (config('app.debug')) {
            return $html;
        }

        // Remove HTML comments (except IE conditionals)
        $html = preg_replace('/<!--(?!\[)[^\[>].*?-->/s', '', $html) ?? $html;

        // Collapse whitespace
        $html = preg_replace('/\s+/', ' ', $html) ?? $html;

        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html) ?? $html;

        return trim($html);
    }
}
