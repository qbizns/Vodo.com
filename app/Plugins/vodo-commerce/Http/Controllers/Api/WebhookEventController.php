<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use VodoCommerce\Api\WebhookEventCatalog;

/**
 * Webhook Event Catalog Controller
 *
 * Serves the webhook event catalog for developer documentation.
 */
class WebhookEventController extends Controller
{
    /**
     * Get all webhook events organized by category.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => WebhookEventCatalog::all(),
            'meta' => [
                'total_events' => count(WebhookEventCatalog::names()),
                'categories' => array_keys(WebhookEventCatalog::all()),
            ],
        ]);
    }

    /**
     * Get events for a specific category.
     */
    public function category(string $category): JsonResponse
    {
        $events = WebhookEventCatalog::forCategory($category);

        if (empty($events)) {
            return response()->json([
                'error' => 'Category not found',
                'available_categories' => array_keys(WebhookEventCatalog::all()),
            ], 404);
        }

        return response()->json([
            'data' => $events,
            'meta' => [
                'category' => $category,
                'count' => count($events),
            ],
        ]);
    }

    /**
     * Get details for a specific event.
     */
    public function show(string $event): JsonResponse
    {
        $eventData = WebhookEventCatalog::get($event);

        if ($eventData === null) {
            return response()->json([
                'error' => 'Event not found',
                'message' => "Event '{$event}' does not exist",
            ], 404);
        }

        return response()->json([
            'data' => $eventData,
        ]);
    }

    /**
     * Get list of event names only.
     */
    public function names(): JsonResponse
    {
        return response()->json([
            'data' => WebhookEventCatalog::names(),
        ]);
    }

    /**
     * Get markdown documentation.
     */
    public function markdown(): Response
    {
        return response(WebhookEventCatalog::toMarkdown(), 200)
            ->header('Content-Type', 'text/markdown')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Validate a list of event names.
     */
    public function validate(Request $request): JsonResponse
    {
        $events = $request->input('events', []);

        if (!is_array($events)) {
            return response()->json([
                'error' => 'Invalid input',
                'message' => 'Events must be an array',
            ], 400);
        }

        $results = [];
        $invalidEvents = [];

        foreach ($events as $event) {
            $isValid = WebhookEventCatalog::isValid($event);
            $results[$event] = $isValid;

            if (!$isValid) {
                $invalidEvents[] = $event;
            }
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($events),
                'valid' => count($events) - count($invalidEvents),
                'invalid' => count($invalidEvents),
            ],
            'invalid_events' => $invalidEvents,
        ]);
    }
}
