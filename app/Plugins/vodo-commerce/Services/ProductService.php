<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use VodoCommerce\Models\Category;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\Store;

class ProductService
{
    public function __construct(protected Store $store)
    {
    }

    public function find(int $productId): ?Product
    {
        return Product::where('store_id', $this->store->id)
            ->with(['category', 'variants'])
            ->find($productId);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::where('store_id', $this->store->id)
            ->where('slug', $slug)
            ->active()
            ->with(['category', 'variants'])
            ->first();
    }

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::where('store_id', $this->store->id)
            ->with(['category']);

        $this->applyFilters($query, $filters);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function listActive(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $filters['status'] = 'active';

        return $this->list($filters, $perPage);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['featured'])) {
            $query->featured();
        }

        if (!empty($filters['in_stock'])) {
            $query->inStock();
        }

        if (!empty($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
    }

    public function create(array $data): Product
    {
        $data['store_id'] = $this->store->id;

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $variants = $data['variants'] ?? [];
        unset($data['variants']);

        $product = Product::create($data);

        foreach ($variants as $index => $variantData) {
            $variantData['product_id'] = $product->id;
            $variantData['position'] = $index;
            ProductVariant::create($variantData);
        }

        do_action('commerce.product.created', $product);

        return $product->load('variants');
    }

    public function update(Product $product, array $data): Product
    {
        if (isset($data['name']) && empty($data['slug'])) {
            if ($data['name'] !== $product->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
            }
        }

        $variants = $data['variants'] ?? null;
        unset($data['variants']);

        $product->update($data);

        if ($variants !== null) {
            $this->syncVariants($product, $variants);
        }

        do_action('commerce.product.updated', $product);

        return $product->fresh(['variants', 'category']);
    }

    public function delete(Product $product): void
    {
        $product->variants()->delete();
        $product->delete();

        do_action('commerce.product.deleted', $product);
    }

    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Product::where('store_id', $this->store->id)
            ->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    protected function syncVariants(Product $product, array $variants): void
    {
        $existingIds = $product->variants()->pluck('id')->toArray();
        $updatedIds = [];

        foreach ($variants as $index => $variantData) {
            $variantData['position'] = $index;

            if (!empty($variantData['id'])) {
                $variant = $product->variants()->find($variantData['id']);
                if ($variant) {
                    $variant->update($variantData);
                    $updatedIds[] = $variant->id;
                }
            } else {
                $variantData['product_id'] = $product->id;
                $variant = ProductVariant::create($variantData);
                $updatedIds[] = $variant->id;
            }
        }

        // Delete removed variants
        $toDelete = array_diff($existingIds, $updatedIds);
        if (!empty($toDelete)) {
            $product->variants()->whereIn('id', $toDelete)->delete();
        }
    }

    public function addVariant(Product $product, array $data): ProductVariant
    {
        $data['product_id'] = $product->id;
        $data['position'] = $product->variants()->count();

        return ProductVariant::create($data);
    }

    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->update($data);

        return $variant->fresh();
    }

    public function deleteVariant(ProductVariant $variant): void
    {
        $variant->delete();
    }

    public function getFeatured(int $limit = 8): Collection
    {
        return Product::where('store_id', $this->store->id)
            ->active()
            ->featured()
            ->inStock()
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRelated(Product $product, int $limit = 4): Collection
    {
        return Product::where('store_id', $this->store->id)
            ->where('id', '!=', $product->id)
            ->active()
            ->inStock()
            ->where(function ($query) use ($product) {
                $query->where('category_id', $product->category_id);

                $tags = is_array($product->tags) ? $product->tags : [];
                if (!empty($tags)) {
                    foreach ($tags as $tag) {
                        $query->orWhereJsonContains('tags', $tag);
                    }
                }
            })
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function search(string $query, int $limit = 20): Collection
    {
        return Product::where('store_id', $this->store->id)
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereJsonContains('tags', $query);
            })
            ->with(['category'])
            ->orderByRaw("CASE WHEN name LIKE ? THEN 1 ELSE 2 END", ["%{$query}%"])
            ->limit($limit)
            ->get();
    }

    public function getByCategory(Category $category, int $perPage = 20): LengthAwarePaginator
    {
        return Product::where('store_id', $this->store->id)
            ->active()
            ->inStock()
            ->where('category_id', $category->id)
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function updateStock(Product $product, int $quantity, string $operation = 'set'): Product
    {
        switch ($operation) {
            case 'increment':
                $product->incrementStock($quantity);
                break;
            case 'decrement':
                $product->decrementStock($quantity);
                break;
            default:
                $product->update([
                    'stock_quantity' => $quantity,
                    'stock_status' => $quantity > 0 ? 'in_stock' : 'out_of_stock',
                ]);
        }

        return $product->fresh();
    }

    public function getLowStockProducts(int $threshold = 5): Collection
    {
        return Product::where('store_id', $this->store->id)
            ->active()
            ->where('stock_quantity', '<=', $threshold)
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->get();
    }

    public function getOutOfStockProducts(): Collection
    {
        return Product::where('store_id', $this->store->id)
            ->active()
            ->where(function ($q) {
                $q->where('stock_status', 'out_of_stock')
                    ->orWhere('stock_quantity', '<=', 0);
            })
            ->get();
    }
}
