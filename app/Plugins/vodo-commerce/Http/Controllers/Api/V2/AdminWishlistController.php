<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Models\Wishlist;
use VodoCommerce\Models\WishlistItem;
use VodoCommerce\Services\WishlistService;

class AdminWishlistController extends Controller
{
    public function __construct(
        protected WishlistService $wishlistService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Wishlist::query();

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Filter by visibility
        if ($request->has('visibility')) {
            $query->where('visibility', $request->input('visibility'));
        }

        // Filter by event type
        if ($request->has('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->has('created_from')) {
            $query->where('created_at', '>=', $request->input('created_from'));
        }

        if ($request->has('created_to')) {
            $query->where('created_at', '<=', $request->input('created_to'));
        }

        // Filter by event date range
        if ($request->has('event_date_from')) {
            $query->where('event_date', '>=', $request->input('event_date_from'));
        }

        if ($request->has('event_date_to')) {
            $query->where('event_date', '<=', $request->input('event_date_to'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = (int) $request->input('per_page', 15);
        $wishlists = $query->with(['customer', 'items', 'collaborators'])->paginate($perPage);

        return response()->json($wishlists);
    }

    public function show(int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::with(['customer', 'items.product', 'items.variant', 'collaborators.customer'])
            ->findOrFail($wishlistId);

        $stats = $this->wishlistService->getWishlistStats($wishlist);

        return response()->json([
            'data' => $wishlist,
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'sometimes|string|in:private,shared,public',
            'is_default' => 'sometimes|boolean',
            'allow_comments' => 'sometimes|boolean',
            'show_purchased_items' => 'sometimes|boolean',
            'event_type' => 'nullable|string|max:255',
            'event_date' => 'nullable|date',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wishlist = $this->wishlistService->updateWishlist($wishlist, $validator->validated());

        return response()->json([
            'data' => $wishlist->load(['items', 'collaborators']),
            'message' => 'Wishlist updated successfully',
        ]);
    }

    public function destroy(int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        $this->wishlistService->deleteWishlist($wishlist);

        return response()->json([
            'message' => 'Wishlist deleted successfully',
        ]);
    }

    public function statistics(): JsonResponse
    {
        $totalWishlists = Wishlist::count();
        $totalItems = WishlistItem::count();
        $publicWishlists = Wishlist::where('visibility', Wishlist::VISIBILITY_PUBLIC)->count();
        $sharedWishlists = Wishlist::where('visibility', Wishlist::VISIBILITY_SHARED)->count();
        $privateWishlists = Wishlist::where('visibility', Wishlist::VISIBILITY_PRIVATE)->count();

        $upcomingEvents = Wishlist::whereNotNull('event_date')
            ->where('event_date', '>=', now())
            ->count();

        $purchasedItems = WishlistItem::where('is_purchased', true)->count();
        $purchaseRate = $totalItems > 0 ? round(($purchasedItems / $totalItems) * 100, 2) : 0;

        $mostPopularProducts = WishlistItem::selectRaw('product_id, COUNT(*) as wishlist_count')
            ->groupBy('product_id')
            ->orderBy('wishlist_count', 'desc')
            ->limit(10)
            ->with('product')
            ->get();

        $eventTypes = Wishlist::whereNotNull('event_type')
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get();

        return response()->json([
            'data' => [
                'totals' => [
                    'wishlists' => $totalWishlists,
                    'items' => $totalItems,
                    'upcoming_events' => $upcomingEvents,
                ],
                'visibility_breakdown' => [
                    'public' => $publicWishlists,
                    'shared' => $sharedWishlists,
                    'private' => $privateWishlists,
                ],
                'purchase_stats' => [
                    'purchased_items' => $purchasedItems,
                    'pending_items' => $totalItems - $purchasedItems,
                    'purchase_rate' => $purchaseRate,
                ],
                'most_popular_products' => $mostPopularProducts,
                'event_types' => $eventTypes,
            ],
        ]);
    }

    public function priceDrops(): JsonResponse
    {
        $items = WishlistItem::whereHas('product', function ($query) {
            $query->whereColumn('commerce_products.price', '<', 'commerce_wishlist_items.price_when_added');
        })
            ->where('notify_on_price_drop', true)
            ->where('is_purchased', false)
            ->with(['wishlist.customer', 'product'])
            ->get();

        return response()->json([
            'data' => $items->map(function ($item) {
                return [
                    'item' => $item,
                    'price_difference' => $item->getPriceDifference(),
                    'wishlist' => $item->wishlist,
                    'customer' => $item->wishlist->customer,
                ];
            }),
            'count' => $items->count(),
        ]);
    }

    public function backInStock(): JsonResponse
    {
        $items = WishlistItem::whereHas('product', function ($query) {
            $query->where('stock_quantity', '>', 0)
                ->where('stock_status', 'in_stock');
        })
            ->where('notify_on_back_in_stock', true)
            ->where('is_purchased', false)
            ->with(['wishlist.customer', 'product'])
            ->get();

        return response()->json([
            'data' => $items,
            'count' => $items->count(),
        ]);
    }

    public function popularProducts(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);

        $products = WishlistItem::selectRaw('product_id, COUNT(*) as wishlist_count, SUM(quantity) as total_quantity')
            ->groupBy('product_id')
            ->orderBy('wishlist_count', 'desc')
            ->limit($limit)
            ->with('product')
            ->get();

        return response()->json([
            'data' => $products,
            'count' => $products->count(),
        ]);
    }

    public function upcomingEvents(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $days = (int) $request->input('days', 30);

        $wishlists = Wishlist::whereNotNull('event_date')
            ->where('event_date', '>=', now())
            ->where('event_date', '<=', now()->addDays($days))
            ->orderBy('event_date', 'asc')
            ->limit($limit)
            ->with(['customer', 'items'])
            ->get();

        return response()->json([
            'data' => $wishlists->map(function ($wishlist) {
                return [
                    'wishlist' => $wishlist,
                    'stats' => $this->wishlistService->getWishlistStats($wishlist),
                    'days_until_event' => now()->diffInDays($wishlist->event_date, false),
                ];
            }),
            'count' => $wishlists->count(),
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wishlist_ids' => 'required|array',
            'wishlist_ids.*' => 'integer|exists:commerce_wishlists,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wishlists = Wishlist::whereIn('id', $request->input('wishlist_ids'))->get();

        foreach ($wishlists as $wishlist) {
            $this->wishlistService->deleteWishlist($wishlist);
        }

        return response()->json([
            'message' => 'Wishlists deleted successfully',
            'count' => $wishlists->count(),
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $query = Wishlist::query();

        // Apply same filters as index
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->has('visibility')) {
            $query->where('visibility', $request->input('visibility'));
        }

        if ($request->has('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->has('created_from')) {
            $query->where('created_at', '>=', $request->input('created_from'));
        }

        if ($request->has('created_to')) {
            $query->where('created_at', '<=', $request->input('created_to'));
        }

        $wishlists = $query->with(['customer', 'items.product'])->get();

        $exportData = $wishlists->map(function ($wishlist) {
            return [
                'id' => $wishlist->id,
                'name' => $wishlist->name,
                'customer_name' => $wishlist->customer->name ?? 'N/A',
                'customer_email' => $wishlist->customer->email ?? 'N/A',
                'visibility' => $wishlist->visibility,
                'items_count' => $wishlist->items_count,
                'event_type' => $wishlist->event_type,
                'event_date' => $wishlist->event_date?->format('Y-m-d'),
                'views_count' => $wishlist->views_count,
                'created_at' => $wishlist->created_at->format('Y-m-d H:i:s'),
                'items' => $wishlist->items->map(function ($item) {
                    return [
                        'product_name' => $item->product->name ?? 'N/A',
                        'quantity' => $item->quantity,
                        'price' => $item->price_when_added,
                        'priority' => $item->priority,
                        'is_purchased' => $item->is_purchased,
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => $exportData,
            'count' => $exportData->count(),
        ]);
    }
}
