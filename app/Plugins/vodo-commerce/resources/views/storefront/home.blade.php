@extends('vodo-commerce::default.layouts.main')

@section('title', $store->name . ' - Home')

@section('content')
<div class="container">
    <!-- Hero Section -->
    <section style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border-radius: 1rem; padding: 4rem 2rem; text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">Welcome to {{ $store->name }}</h1>
        <p style="font-size: 1.125rem; opacity: 0.9; margin-bottom: 2rem;">{{ $store->description ?? 'Discover our amazing products' }}</p>
        <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}" class="btn" style="background: white; color: var(--primary-color);">
            Shop Now
        </a>
    </section>

    <!-- Featured Products -->
    @if($featuredProducts->isNotEmpty())
    <section style="margin-bottom: 3rem;">
        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Featured Products</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
            @foreach($featuredProducts as $product)
            <div class="card">
                <a href="{{ route('storefront.vodo-commerce.products.show', [$store->slug, $product->slug]) }}">
                    @if($product->getPrimaryImage())
                        <img src="{{ $product->getPrimaryImage() }}" alt="{{ $product->name }}" style="width: 100%; height: 200px; object-fit: cover;">
                    @else
                        <div style="width: 100%; height: 200px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                            No Image
                        </div>
                    @endif
                </a>
                <div style="padding: 1rem;">
                    @if($product->category)
                        <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">{{ $product->category->name }}</span>
                    @endif
                    <h3 style="font-size: 1rem; font-weight: 600; margin: 0.25rem 0;">
                        <a href="{{ route('storefront.vodo-commerce.products.show', [$store->slug, $product->slug]) }}" style="color: inherit; text-decoration: none;">
                            {{ $product->name }}
                        </a>
                    </h3>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                        <span style="font-size: 1.125rem; font-weight: 700; color: var(--primary-color);">
                            {{ $store->currency }} {{ number_format($product->price, 2) }}
                        </span>
                        @if($product->isOnSale())
                            <span style="font-size: 0.875rem; color: #9ca3af; text-decoration: line-through;">
                                {{ $store->currency }} {{ number_format($product->compare_at_price, 2) }}
                            </span>
                            <span style="font-size: 0.75rem; background: #fee2e2; color: #dc2626; padding: 0.125rem 0.5rem; border-radius: 0.25rem;">
                                -{{ $product->getSalePercentage() }}%
                            </span>
                        @endif
                    </div>
                    <button
                        class="btn btn-primary"
                        style="width: 100%; margin-top: 1rem;"
                        onclick="addToCart({{ $product->id }})"
                        {{ !$product->isInStock() ? 'disabled' : '' }}
                    >
                        {{ $product->isInStock() ? 'Add to Cart' : 'Out of Stock' }}
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}" class="btn btn-secondary">
                View All Products
            </a>
        </div>
    </section>
    @endif
</div>

@push('scripts')
<script>
function addToCart(productId, variantId = null) {
    fetch('{{ route('storefront.vodo-commerce.cart.add', $store->slug) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            product_id: productId,
            variant_id: variantId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cart-count').textContent = data.cart.item_count;
            alert('Added to cart!');
        } else {
            alert(data.message || 'Failed to add to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add to cart');
    });
}
</script>
@endpush
@endsection
