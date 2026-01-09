<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Models\Wishlist;
use VodoCommerce\Models\WishlistCollaborator;
use VodoCommerce\Models\WishlistItem;
use VodoCommerce\Services\WishlistService;

class WishlistController extends Controller
{
    public function __construct(
        protected WishlistService $wishlistService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $customerId = $request->input('customer_id');

        if (! $customerId) {
            return response()->json(['error' => 'Customer ID is required'], 400);
        }

        $wishlists = $this->wishlistService->getCustomerWishlists((int) $customerId);

        return response()->json([
            'data' => $wishlists,
            'count' => $wishlists->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:commerce_customers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'nullable|string|in:private,shared,public',
            'is_default' => 'nullable|boolean',
            'allow_comments' => 'nullable|boolean',
            'show_purchased_items' => 'nullable|boolean',
            'event_type' => 'nullable|string|max:255',
            'event_date' => 'nullable|date',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wishlist = $this->wishlistService->createWishlist(
            (int) $request->input('customer_id'),
            $validator->validated()
        );

        return response()->json([
            'data' => $wishlist->load(['items', 'collaborators']),
            'message' => 'Wishlist created successfully',
        ], 201);
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        // Support both slug and share token
        $wishlist = null;

        if (strlen($identifier) === 32) {
            // Likely a share token
            $wishlist = $this->wishlistService->findWishlistByShareToken($identifier);
        } else {
            // Likely a slug
            $wishlist = $this->wishlistService->findWishlistBySlug($identifier);
        }

        if (! $wishlist) {
            return response()->json(['error' => 'Wishlist not found'], 404);
        }

        // Check viewing permissions
        $customerId = $request->input('customer_id');
        $token = $request->input('token', $identifier);

        if (! $wishlist->canBeViewedBy($customerId ? (int) $customerId : null, $token)) {
            return response()->json(['error' => 'You do not have permission to view this wishlist'], 403);
        }

        // Record view
        $this->wishlistService->recordWishlistView($wishlist);

        return response()->json([
            'data' => $wishlist->load(['items.product', 'items.variant', 'collaborators.customer']),
            'stats' => $this->wishlistService->getWishlistStats($wishlist),
        ]);
    }

    public function update(Request $request, int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check editing permissions
        $customerId = $request->input('customer_id');
        if (! $wishlist->canBeEditedBy($customerId ? (int) $customerId : null)) {
            return response()->json(['error' => 'You do not have permission to edit this wishlist'], 403);
        }

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

    public function destroy(Request $request, int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check editing permissions
        $customerId = $request->input('customer_id');
        if (! $wishlist->canBeEditedBy($customerId ? (int) $customerId : null)) {
            return response()->json(['error' => 'You do not have permission to delete this wishlist'], 403);
        }

        $this->wishlistService->deleteWishlist($wishlist);

        return response()->json([
            'message' => 'Wishlist deleted successfully',
        ]);
    }

    public function addItem(Request $request, int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check editing permissions
        $customerId = $request->input('customer_id');
        if (! $wishlist->canBeEditedBy($customerId ? (int) $customerId : null)) {
            return response()->json(['error' => 'You do not have permission to edit this wishlist'], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:commerce_products,id',
            'variant_id' => 'nullable|integer|exists:commerce_product_variants,id',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'priority' => 'nullable|string|in:low,medium,high',
            'notify_on_price_drop' => 'nullable|boolean',
            'notify_on_back_in_stock' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = $this->wishlistService->addItemToWishlist(
            $wishlist,
            (int) $request->input('product_id'),
            $validator->validated()
        );

        return response()->json([
            'data' => $item->load(['product', 'variant']),
            'message' => 'Item added to wishlist successfully',
        ], 201);
    }

    public function updateItem(Request $request, int $wishlistId, int $itemId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);
        $item = WishlistItem::where('wishlist_id', $wishlistId)->findOrFail($itemId);

        // Check editing permissions
        $customerId = $request->input('customer_id');
        if (! $wishlist->canBeEditedBy($customerId ? (int) $customerId : null)) {
            return response()->json(['error' => 'You do not have permission to edit this wishlist'], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|integer|min:1',
            'notes' => 'nullable|string',
            'priority' => 'sometimes|string|in:low,medium,high',
            'notify_on_price_drop' => 'sometimes|boolean',
            'notify_on_back_in_stock' => 'sometimes|boolean',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = $this->wishlistService->updateWishlistItem($item, $validator->validated());

        return response()->json([
            'data' => $item->load(['product', 'variant']),
            'message' => 'Wishlist item updated successfully',
        ]);
    }

    public function removeItem(Request $request, int $wishlistId, int $itemId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);
        $item = WishlistItem::where('wishlist_id', $wishlistId)->findOrFail($itemId);

        // Check editing permissions
        $customerId = $request->input('customer_id');
        if (! $wishlist->canBeEditedBy($customerId ? (int) $customerId : null)) {
            return response()->json(['error' => 'You do not have permission to edit this wishlist'], 403);
        }

        $this->wishlistService->removeItemFromWishlist($item);

        return response()->json([
            'message' => 'Item removed from wishlist successfully',
        ]);
    }

    public function markItemPurchased(Request $request, int $wishlistId, int $itemId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);
        $item = WishlistItem::where('wishlist_id', $wishlistId)->findOrFail($itemId);

        // For purchased items, allow collaborators with view permission
        $customerId = $request->input('customer_id');
        if (! $wishlist->canBeViewedBy($customerId ? (int) $customerId : null)) {
            return response()->json(['error' => 'You do not have permission to access this wishlist'], 403);
        }

        $validator = Validator::make($request->all(), [
            'purchased_by' => 'nullable|integer|exists:commerce_customers,id',
            'quantity_purchased' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = $this->wishlistService->markItemAsPurchased(
            $item,
            $request->input('purchased_by') ? (int) $request->input('purchased_by') : null,
            $request->input('quantity_purchased') ? (int) $request->input('quantity_purchased') : null
        );

        return response()->json([
            'data' => $item->load(['product', 'variant']),
            'message' => 'Item marked as purchased successfully',
        ]);
    }

    public function reorderItems(Request $request, int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check editing permissions
        $customerId = $request->input('customer_id');
        if (! $wishlist->canBeEditedBy($customerId ? (int) $customerId : null)) {
            return response()->json(['error' => 'You do not have permission to edit this wishlist'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_order' => 'required|array',
            'item_order.*' => 'integer|exists:commerce_wishlist_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->wishlistService->reorderWishlistItems($wishlist, $request->input('item_order'));

        return response()->json([
            'message' => 'Wishlist items reordered successfully',
        ]);
    }

    public function addCollaborator(Request $request, int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check editing permissions (only owner can add collaborators)
        $customerId = $request->input('customer_id');
        if ($wishlist->customer_id !== (int) $customerId) {
            return response()->json(['error' => 'Only the wishlist owner can add collaborators'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'permission' => 'nullable|string|in:view,edit,manage',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $collaborator = $this->wishlistService->addCollaborator(
            $wishlist,
            $request->input('email'),
            $request->input('permission', WishlistCollaborator::PERMISSION_VIEW)
        );

        return response()->json([
            'data' => $collaborator->load('customer'),
            'message' => 'Collaborator added successfully',
        ], 201);
    }

    public function removeCollaborator(Request $request, int $wishlistId, int $collaboratorId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);
        $collaborator = WishlistCollaborator::where('wishlist_id', $wishlistId)->findOrFail($collaboratorId);

        // Check permissions (owner or the collaborator themselves)
        $customerId = $request->input('customer_id');
        if ($wishlist->customer_id !== (int) $customerId && $collaborator->customer_id !== (int) $customerId) {
            return response()->json(['error' => 'You do not have permission to remove this collaborator'], 403);
        }

        $this->wishlistService->removeCollaborator($collaborator);

        return response()->json([
            'message' => 'Collaborator removed successfully',
        ]);
    }

    public function updateCollaboratorPermission(Request $request, int $wishlistId, int $collaboratorId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);
        $collaborator = WishlistCollaborator::where('wishlist_id', $wishlistId)->findOrFail($collaboratorId);

        // Only owner can update permissions
        $customerId = $request->input('customer_id');
        if ($wishlist->customer_id !== (int) $customerId) {
            return response()->json(['error' => 'Only the wishlist owner can update permissions'], 403);
        }

        $validator = Validator::make($request->all(), [
            'permission' => 'required|string|in:view,edit,manage',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $collaborator = $this->wishlistService->updateCollaboratorPermission(
            $collaborator,
            $request->input('permission')
        );

        return response()->json([
            'data' => $collaborator->load('customer'),
            'message' => 'Collaborator permission updated successfully',
        ]);
    }

    public function acceptInvitation(Request $request, string $token): JsonResponse
    {
        $collaborator = WishlistCollaborator::where('invitation_token', $token)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:commerce_customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Link customer to invitation if not already linked
        if (! $collaborator->customer_id) {
            $collaborator->update(['customer_id' => (int) $request->input('customer_id')]);
        }

        $collaborator = $this->wishlistService->acceptInvitation($collaborator);

        return response()->json([
            'data' => $collaborator->load(['wishlist', 'customer']),
            'message' => 'Invitation accepted successfully',
        ]);
    }

    public function declineInvitation(string $token): JsonResponse
    {
        $collaborator = WishlistCollaborator::where('invitation_token', $token)->firstOrFail();

        $collaborator = $this->wishlistService->declineInvitation($collaborator);

        return response()->json([
            'message' => 'Invitation declined successfully',
        ]);
    }

    public function verifyPurchases(Request $request, int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check editing permissions
        $customerId = $request->input('customer_id');
        if (! $wishlist->canBeEditedBy($customerId ? (int) $customerId : null)) {
            return response()->json(['error' => 'You do not have permission to verify purchases'], 403);
        }

        $verifiedItemIds = $this->wishlistService->verifyPurchasesForWishlist($wishlist);

        return response()->json([
            'verified_items' => $verifiedItemIds,
            'count' => count($verifiedItemIds),
            'message' => 'Purchase verification completed',
        ]);
    }

    public function statistics(int $wishlistId): JsonResponse
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        $stats = $this->wishlistService->getWishlistStats($wishlist);

        return response()->json([
            'data' => $stats,
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);

        $wishlists = $this->wishlistService->getPopularWishlists($limit);

        return response()->json([
            'data' => $wishlists,
            'count' => $wishlists->count(),
        ]);
    }

    public function upcomingEvents(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);

        $wishlists = $this->wishlistService->getUpcomingEvents($limit);

        return response()->json([
            'data' => $wishlists,
            'count' => $wishlists->count(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|integer|exists:commerce_customers,id',
            'visibility' => 'nullable|string|in:private,shared,public',
            'event_type' => 'nullable|string',
            'search' => 'nullable|string',
            'has_upcoming_event' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wishlists = $this->wishlistService->searchWishlists($validator->validated());

        return response()->json([
            'data' => $wishlists,
            'count' => $wishlists->count(),
        ]);
    }
}
