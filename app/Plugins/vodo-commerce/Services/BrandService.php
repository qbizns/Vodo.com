<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use VodoCommerce\Models\Brand;
use VodoCommerce\Models\Store;

class BrandService
{
    public function __construct(protected Store $store)
    {
    }

    public function find(int $brandId): ?Brand
    {
        return Brand::where('store_id', $this->store->id)
            ->with(['products'])
            ->find($brandId);
    }

    public function findBySlug(string $slug): ?Brand
    {
        return Brand::where('store_id', $this->store->id)
            ->where('slug', $slug)
            ->active()
            ->first();
    }

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Brand::where('store_id', $this->store->id);

        if (!empty($filters['is_active'])) {
            $query->active();
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function listActive(int $perPage = 20): LengthAwarePaginator
    {
        return $this->list(['is_active' => true], $perPage);
    }

    public function create(array $data): Brand
    {
        $data['store_id'] = $this->store->id;

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $brand = Brand::create($data);

        do_action('commerce.brand.created', $brand);

        return $brand;
    }

    public function update(Brand $brand, array $data): Brand
    {
        if (isset($data['name']) && empty($data['slug'])) {
            if ($data['name'] !== $brand->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $brand->id);
            }
        }

        $brand->update($data);

        do_action('commerce.brand.updated', $brand);

        return $brand->fresh();
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();

        do_action('commerce.brand.deleted', $brand);
    }

    public function getAll(): Collection
    {
        return Brand::where('store_id', $this->store->id)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function getWithProductCount(): Collection
    {
        return Brand::where('store_id', $this->store->id)
            ->withCount('products')
            ->active()
            ->orderBy('name')
            ->get();
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
        $query = Brand::where('store_id', $this->store->id)
            ->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
