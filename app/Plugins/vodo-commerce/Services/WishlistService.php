<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Wishlist;
use VodoCommerce\Models\WishlistCollaborator;
use VodoCommerce\Models\WishlistItem;

class WishlistService
{
    public function __construct(
        protected Store $store
    ) {
    }

    public function createWishlist(int $customerId, array $data): Wishlist
    {
        return DB::transaction(function () use ($customerId, $data) {
            // Generate unique slug
            $slug = Str::slug($data['name']) . '-' . Str::random(8);
            while (Wishlist::where('slug', $slug)->exists()) {
                $slug = Str::slug($data['name']) . '-' . Str::random(8);
            }

            $wishlist = Wishlist::create([
                'store_id' => $this->store->id,
                'customer_id' => $customerId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'slug' => $slug,
                'visibility' => $data['visibility'] ?? Wishlist::VISIBILITY_PRIVATE,
                'share_token' => $data['visibility'] !== Wishlist::VISIBILITY_PRIVATE ? Str::random(32) : null,
                'is_default' => $data['is_default'] ?? false,
                'allow_comments' => $data['allow_comments'] ?? false,
                'show_purchased_items' => $data['show_purchased_items'] ?? true,
                'event_type' => $data['event_type'] ?? null,
                'event_date' => $data['event_date'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            // If marked as default, unmark other wishlists
            if ($wishlist->is_default) {
                Wishlist::where('store_id', $this->store->id)
                    ->where('customer_id', $customerId)
                    ->where('id', '!=', $wishlist->id)
                    ->update(['is_default' => false]);
            }

            return $wishlist->fresh();
        });
    }

    public function updateWishlist(Wishlist $wishlist, array $data): Wishlist
    {
        return DB::transaction(function () use ($wishlist, $data) {
            // Update slug if name changed
            if (isset($data['name']) && $data['name'] !== $wishlist->name) {
                $slug = Str::slug($data['name']) . '-' . Str::random(8);
                while (Wishlist::where('slug', $slug)->where('id', '!=', $wishlist->id)->exists()) {
                    $slug = Str::slug($data['name']) . '-' . Str::random(8);
                }
                $data['slug'] = $slug;
            }

            // Generate share token if visibility changed to shared/public
            if (isset($data['visibility']) && $data['visibility'] !== Wishlist::VISIBILITY_PRIVATE && ! $wishlist->share_token) {
                $data['share_token'] = Str::random(32);
            }

            $wishlist->update($data);

            // Handle default wishlist change
            if (isset($data['is_default']) && $data['is_default']) {
                Wishlist::where('store_id', $this->store->id)
                    ->where('customer_id', $wishlist->customer_id)
                    ->where('id', '!=', $wishlist->id)
                    ->update(['is_default' => false]);
            }

            return $wishlist->fresh();
        });
    }

    public function deleteWishlist(Wishlist $wishlist): bool
    {
        return DB::transaction(function () use ($wishlist) {
            // If this was the default wishlist, make the oldest wishlist default
            if ($wishlist->is_default) {
                $nextWishlist = Wishlist::where('store_id', $this->store->id)
                    ->where('customer_id', $wishlist->customer_id)
                    ->where('id', '!=', $wishlist->id)
                    ->oldest()
                    ->first();

                if ($nextWishlist) {
                    $nextWishlist->makeDefault();
                }
            }

            return $wishlist->delete();
        });
    }

    public function addItemToWishlist(Wishlist $wishlist, int $productId, array $data = []): WishlistItem
    {
        return DB::transaction(function () use ($wishlist, $productId, $data) {
            // Get product to track current price
            $product = Product::findOrFail($productId);

            // Check if item already exists
            $existingItem = WishlistItem::where('wishlist_id', $wishlist->id)
                ->where('product_id', $productId)
                ->where('variant_id', $data['variant_id'] ?? null)
                ->first();

            if ($existingItem) {
                // Update quantity instead of creating duplicate
                $existingItem->update([
                    'quantity' => $existingItem->quantity + ($data['quantity'] ?? 1),
                    'notes' => $data['notes'] ?? $existingItem->notes,
                    'priority' => $data['priority'] ?? $existingItem->priority,
                ]);

                return $existingItem;
            }

            // Get next display order
            $maxOrder = WishlistItem::where('wishlist_id', $wishlist->id)->max('display_order') ?? 0;

            $item = WishlistItem::create([
                'wishlist_id' => $wishlist->id,
                'product_id' => $productId,
                'variant_id' => $data['variant_id'] ?? null,
                'quantity' => $data['quantity'] ?? 1,
                'notes' => $data['notes'] ?? null,
                'priority' => $data['priority'] ?? WishlistItem::PRIORITY_MEDIUM,
                'price_when_added' => $product->price,
                'notify_on_price_drop' => $data['notify_on_price_drop'] ?? false,
                'notify_on_back_in_stock' => $data['notify_on_back_in_stock'] ?? false,
                'display_order' => $maxOrder + 1,
                'meta' => $data['meta'] ?? null,
            ]);

            // Increment wishlist items count
            $wishlist->increment('items_count');

            return $item->fresh();
        });
    }

    public function updateWishlistItem(WishlistItem $item, array $data): WishlistItem
    {
        $item->update($data);

        return $item->fresh();
    }

    public function removeItemFromWishlist(WishlistItem $item): bool
    {
        return DB::transaction(function () use ($item) {
            $wishlist = $item->wishlist;

            $item->delete();

            // Decrement wishlist items count
            $wishlist->decrement('items_count');

            return true;
        });
    }

    public function markItemAsPurchased(WishlistItem $item, ?int $purchasedBy = null, ?int $quantityPurchased = null): WishlistItem
    {
        $item->markAsPurchased($purchasedBy, $quantityPurchased);

        return $item->fresh();
    }

    public function reorderWishlistItems(Wishlist $wishlist, array $itemOrder): bool
    {
        return DB::transaction(function () use ($wishlist, $itemOrder) {
            foreach ($itemOrder as $order => $itemId) {
                WishlistItem::where('wishlist_id', $wishlist->id)
                    ->where('id', $itemId)
                    ->update(['display_order' => $order]);
            }

            return true;
        });
    }

    public function addCollaborator(Wishlist $wishlist, string $email, string $permission = WishlistCollaborator::PERMISSION_VIEW): WishlistCollaborator
    {
        return DB::transaction(function () use ($wishlist, $email, $permission) {
            // Check if email belongs to a customer
            $customer = Customer::where('store_id', $this->store->id)
                ->where('email', $email)
                ->first();

            // Check if collaborator already exists
            $existing = WishlistCollaborator::where('wishlist_id', $wishlist->id)
                ->where(function ($query) use ($customer, $email) {
                    if ($customer) {
                        $query->where('customer_id', $customer->id);
                    } else {
                        $query->where('invited_email', $email);
                    }
                })
                ->first();

            if ($existing) {
                // Update permission if different
                if ($existing->permission !== $permission) {
                    $existing->update(['permission' => $permission]);
                }

                return $existing;
            }

            return WishlistCollaborator::create([
                'wishlist_id' => $wishlist->id,
                'customer_id' => $customer?->id,
                'invited_email' => $customer ? null : $email,
                'permission' => $permission,
                'status' => WishlistCollaborator::STATUS_PENDING,
                'invitation_token' => Str::random(32),
                'invited_at' => now(),
            ]);
        });
    }

    public function removeCollaborator(WishlistCollaborator $collaborator): bool
    {
        return $collaborator->delete();
    }

    public function updateCollaboratorPermission(WishlistCollaborator $collaborator, string $permission): WishlistCollaborator
    {
        $collaborator->update(['permission' => $permission]);

        return $collaborator->fresh();
    }

    public function acceptInvitation(WishlistCollaborator $collaborator): WishlistCollaborator
    {
        $collaborator->accept();

        return $collaborator->fresh();
    }

    public function declineInvitation(WishlistCollaborator $collaborator): WishlistCollaborator
    {
        $collaborator->decline();

        return $collaborator->fresh();
    }

    public function recordWishlistView(Wishlist $wishlist): void
    {
        $wishlist->recordView();
    }

    public function getOrCreateDefaultWishlist(int $customerId): Wishlist
    {
        $defaultWishlist = Wishlist::where('store_id', $this->store->id)
            ->where('customer_id', $customerId)
            ->where('is_default', true)
            ->first();

        if ($defaultWishlist) {
            return $defaultWishlist;
        }

        // Create default wishlist
        return $this->createWishlist($customerId, [
            'name' => 'My Wishlist',
            'visibility' => Wishlist::VISIBILITY_PRIVATE,
            'is_default' => true,
        ]);
    }

    public function getCustomerWishlists(int $customerId): Collection
    {
        return Wishlist::where('store_id', $this->store->id)
            ->where('customer_id', $customerId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findWishlistBySlug(string $slug): ?Wishlist
    {
        return Wishlist::where('store_id', $this->store->id)
            ->where('slug', $slug)
            ->first();
    }

    public function findWishlistByShareToken(string $token): ?Wishlist
    {
        return Wishlist::where('store_id', $this->store->id)
            ->where('share_token', $token)
            ->first();
    }

    public function verifyPurchasesForWishlist(Wishlist $wishlist): array
    {
        return DB::transaction(function () use ($wishlist) {
            $verified = [];
            $items = $wishlist->items()->where('is_purchased', false)->get();

            foreach ($items as $item) {
                // Check if customer has purchased this product in an order
                $purchase = Order::where('store_id', $this->store->id)
                    ->where('customer_id', $wishlist->customer_id)
                    ->whereHas('items', function ($query) use ($item) {
                        $query->where('product_id', $item->product_id);
                        if ($item->variant_id) {
                            $query->where('variant_id', $item->variant_id);
                        }
                    })
                    ->where('status', Order::STATUS_COMPLETED)
                    ->first();

                if ($purchase) {
                    $item->markAsPurchased($wishlist->customer_id);
                    $verified[] = $item->id;
                }
            }

            return $verified;
        });
    }

    public function getWishlistStats(Wishlist $wishlist): array
    {
        $items = $wishlist->items;

        return [
            'total_items' => $items->count(),
            'purchased_items' => $items->where('is_purchased', true)->count(),
            'partially_purchased_items' => $items->where('quantity_purchased', '>', 0)
                ->where('is_purchased', false)->count(),
            'total_value' => $items->sum('price_when_added'),
            'purchased_value' => $items->where('is_purchased', true)->sum('price_when_added'),
            'remaining_value' => $items->where('is_purchased', false)->sum('price_when_added'),
            'high_priority_items' => $items->where('priority', WishlistItem::PRIORITY_HIGH)->count(),
            'price_tracked_items' => $items->where('notify_on_price_drop', true)->count(),
            'collaborators_count' => $wishlist->collaborators()
                ->where('status', WishlistCollaborator::STATUS_ACCEPTED)->count(),
            'pending_invitations' => $wishlist->collaborators()
                ->where('status', WishlistCollaborator::STATUS_PENDING)->count(),
            'views_count' => $wishlist->views_count,
            'last_viewed_at' => $wishlist->last_viewed_at,
        ];
    }

    public function getPopularWishlists(int $limit = 10): Collection
    {
        return Wishlist::where('store_id', $this->store->id)
            ->where('visibility', Wishlist::VISIBILITY_PUBLIC)
            ->orderBy('views_count', 'desc')
            ->orderBy('items_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUpcomingEvents(int $limit = 10): Collection
    {
        return Wishlist::where('store_id', $this->store->id)
            ->where('visibility', Wishlist::VISIBILITY_PUBLIC)
            ->whereNotNull('event_date')
            ->where('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->limit($limit)
            ->get();
    }

    public function searchWishlists(array $filters): Collection
    {
        $query = Wishlist::where('store_id', $this->store->id);

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['has_upcoming_event']) && $filters['has_upcoming_event']) {
            $query->whereNotNull('event_date')
                ->where('event_date', '>=', now());
        }

        $query->orderBy('created_at', 'desc');

        return $query->get();
    }

    public function getItemsWithPriceDrops(Wishlist $wishlist): Collection
    {
        return $wishlist->items()
            ->whereHas('product', function ($query) {
                $query->whereColumn('commerce_products.price', '<', 'commerce_wishlist_items.price_when_added');
            })
            ->where('notify_on_price_drop', true)
            ->where('is_purchased', false)
            ->get();
    }

    public function getItemsBackInStock(Wishlist $wishlist): Collection
    {
        return $wishlist->items()
            ->whereHas('product', function ($query) {
                $query->where('stock_quantity', '>', 0)
                    ->where('stock_status', 'in_stock');
            })
            ->where('notify_on_back_in_stock', true)
            ->where('is_purchased', false)
            ->get();
    }
}
