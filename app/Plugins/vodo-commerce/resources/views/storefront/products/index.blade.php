@extends('vodo-commerce::default.layouts.main')

@section('title', 'Products - ' . $store->name)

@section('content')
<div class="container">
    <div style="display: flex; gap: 2rem;">
        <!-- Sidebar -->
        <aside style="width: 250px; flex-shrink: 0;">
            <div class="card" style="padding: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Categories</h3>
                <ul style="list-style: none;">
                    <li style="margin-bottom: 0.5rem;">
                        <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}" style="color: {{ !isset($filters['category_id']) ? 'var(--primary-color)' : '#4b5563' }}; text-decoration: none;">
                            All Products
                        </a>
                    </li>
                    @foreach($categories as $category)
                    <li style="margin-bottom: 0.5rem;">
                        <a href="{{ route('storefront.vodo-commerce.category', [$store->slug, $category->slug]) }}" style="color: #4b5563; text-decoration: none;">
                            {{ $category->name }}
                        </a>
                        @if($category->children->isNotEmpty())
                        <ul style="list-style: none; margin-left: 1rem; margin-top: 0.5rem;">
                            @foreach($category->children as $child)
                            <li style="margin-bottom: 0.25rem;">
                                <a href="{{ route('storefront.vodo-commerce.category', [$store->slug, $child->slug]) }}" style="color: #6b7280; text-decoration: none; font-size: 0.875rem;">
                                    {{ $child->name }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </li>
                    @endforeach
                </ul>

                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #e5e7eb;">

                <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Price Range</h3>
                <form method="GET" action="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}">
                    <div class="form-group">
                        <input type="number" name="price_min" class="form-input" placeholder="Min" value="{{ $filters['price_min'] ?? '' }}" style="margin-bottom: 0.5rem;">
                        <input type="number" name="price_max" class="form-input" placeholder="Max" value="{{ $filters['price_max'] ?? '' }}">
                    </div>
                    <button type="submit" class="btn btn-secondary" style="width: 100%;">Apply</button>
                </form>
            </div>
        </aside>

        <!-- Products Grid -->
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1 style="font-size: 1.5rem; font-weight: 600;">Products</h1>
                <div>
                    <select onchange="window.location.href = this.value" class="form-input" style="width: auto;">
                        <option value="{{ route('storefront.vodo-commerce.products.index', array_merge([$store->slug], request()->except(['sort_by', 'sort_dir']))) }}">Sort by</option>
                        <option value="{{ route('storefront.vodo-commerce.products.index', array_merge([$store->slug], request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'created_at', 'sort_dir' => 'desc'])) }}" {{ ($filters['sort_by'] ?? '') === 'created_at' ? 'selected' : '' }}>Newest</option>
                        <option value="{{ route('storefront.vodo-commerce.products.index', array_merge([$store->slug], request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'price', 'sort_dir' => 'asc'])) }}" {{ ($filters['sort_by'] ?? '') === 'price' && ($filters['sort_dir'] ?? '') === 'asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="{{ route('storefront.vodo-commerce.products.index', array_merge([$store->slug], request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'price', 'sort_dir' => 'desc'])) }}" {{ ($filters['sort_by'] ?? '') === 'price' && ($filters['sort_dir'] ?? '') === 'desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="{{ route('storefront.vodo-commerce.products.index', array_merge([$store->slug], request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'name', 'sort_dir' => 'asc'])) }}" {{ ($filters['sort_by'] ?? '') === 'name' ? 'selected' : '' }}>Name</option>
                    </select>
                </div>
            </div>

            @if($products->isEmpty())
                <div class="card" style="padding: 3rem; text-align: center;">
                    <p style="color: #6b7280;">No products found.</p>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem;">
                    @foreach($products as $product)
                    <div class="card">
                        <a href="{{ route('storefront.vodo-commerce.products.show', [$store->slug, $product->slug]) }}">
                            @if($product->getPrimaryImage())
                                <img src="{{ $product->getPrimaryImage() }}" alt="{{ $product->name }}" style="width: 100%; height: 180px; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 180px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                                    No Image
                                </div>
                            @endif
                        </a>
                        <div style="padding: 1rem;">
                            <h3 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem;">
                                <a href="{{ route('storefront.vodo-commerce.products.show', [$store->slug, $product->slug]) }}" style="color: inherit; text-decoration: none;">
                                    {{ $product->name }}
                                </a>
                            </h3>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-weight: 700; color: var(--primary-color);">
                                    {{ $store->currency }} {{ number_format($product->price, 2) }}
                                </span>
                                @if($product->isOnSale())
                                    <span style="font-size: 0.75rem; color: #9ca3af; text-decoration: line-through;">
                                        {{ $store->currency }} {{ number_format($product->compare_at_price, 2) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div style="margin-top: 2rem;">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
