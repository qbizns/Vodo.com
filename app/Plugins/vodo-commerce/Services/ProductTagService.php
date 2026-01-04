<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductTag;
use VodoCommerce\Models\Store;

class ProductTagService
{
    public function __construct(protected Store $store)
    {
    }

    public function find(int $tagId): ?ProductTag
    {
        return ProductTag::where('store_id', $this->store->id)
            ->find($tagId);
    }

    public function findBySlug(string $slug): ?ProductTag
    {
        return ProductTag::where('store_id', $this->store->id)
            ->where('slug', $slug)
            ->first();
    }

    public function list(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = ProductTag::where('store_id', $this->store->id);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function getAll(): Collection
    {
        return ProductTag::where('store_id', $this->store->id)
            ->orderBy('name')
            ->get();
    }

    public function getWithProductCount(): Collection
    {
        return ProductTag::where('store_id', $this->store->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): ProductTag
    {
        $data['store_id'] = $this->store->id;

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $tag = ProductTag::create($data);

        do_action('commerce.product_tag.created', $tag);

        return $tag;
    }

    public function update(ProductTag $tag, array $data): ProductTag
    {
        if (isset($data['name']) && empty($data['slug'])) {
            if ($data['name'] !== $tag->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $tag->id);
            }
        }

        $tag->update($data);

        do_action('commerce.product_tag.updated', $tag);

        return $tag->fresh();
    }

    public function delete(ProductTag $tag): void
    {
        $tag->delete();

        do_action('commerce.product_tag.deleted', $tag);
    }

    public function attachToProduct(Product $product, array $tagIds): void
    {
        $product->tags()->sync($tagIds);
    }

    public function detachFromProduct(Product $product, array $tagIds): void
    {
        $product->tags()->detach($tagIds);
    }

    public function syncProductTags(Product $product, array $tagIds): void
    {
        $product->tags()->sync($tagIds);
    }

    public function findOrCreate(string $name): ProductTag
    {
        $slug = Str::slug($name);

        $tag = ProductTag::where('store_id', $this->store->id)
            ->where('slug', $slug)
            ->first();

        if ($tag) {
            return $tag;
        }

        return $this->create(['name' => $name, 'slug' => $slug]);
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
        $query = ProductTag::where('store_id', $this->store->id)
            ->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
