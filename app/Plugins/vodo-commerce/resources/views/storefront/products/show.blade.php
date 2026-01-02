@extends('vodo-commerce::themes.default.layouts.main')

@section('title', $product->name . ' - ' . $store->name)
@section('description', $product->short_description ?? Str::limit(strip_tags($product->description), 160))

@section('content')
<div class="container">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 1.5rem; font-size: 0.875rem; color: #6b7280;">
        <a href="{{ route('storefront.vodo-commerce.home', $store->slug) }}" style="color: #6b7280; text-decoration: none;">Home</a>
        <span style="margin: 0 0.5rem;">/</span>
        <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}" style="color: #6b7280; text-decoration: none;">Products</a>
        @if($product->category)
            <span style="margin: 0 0.5rem;">/</span>
            <a href="{{ route('storefront.vodo-commerce.category', [$store->slug, $product->category->slug]) }}" style="color: #6b7280; text-decoration: none;">{{ $product->category->name }}</a>
        @endif
        <span style="margin: 0 0.5rem;">/</span>
        <span style="color: #1f2937;">{{ $product->name }}</span>
    </nav>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
        <!-- Product Images -->
        <div>
            <div class="card" style="margin-bottom: 1rem;">
                @if($product->getPrimaryImage())
                    <img id="main-image" src="{{ $product->getPrimaryImage() }}" alt="{{ $product->name }}" style="width: 100%; height: 500px; object-fit: contain;">
                @else
                    <div style="width: 100%; height: 500px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                        No Image
                    </div>
                @endif
            </div>
            @if(count($product->images ?? []) > 1)
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    @foreach($product->images as $image)
                        <button onclick="document.getElementById('main-image').src = '{{ $image }}'" style="border: 2px solid transparent; padding: 0.25rem; border-radius: 0.5rem; cursor: pointer; background: white;">
                            <img src="{{ $image }}" alt="{{ $product->name }}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 0.25rem;">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Product Details -->
        <div>
            @if($product->category)
                <span style="font-size: 0.875rem; color: var(--primary-color);">{{ $product->category->name }}</span>
            @endif

            <h1 style="font-size: 2rem; font-weight: 700; margin: 0.5rem 0;">{{ $product->name }}</h1>

            @if($product->sku)
                <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">SKU: {{ $product->sku }}</p>
            @endif

            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <span style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">
                    {{ $store->currency }} {{ number_format($product->price, 2) }}
                </span>
                @if($product->isOnSale())
                    <span style="font-size: 1.25rem; color: #9ca3af; text-decoration: line-through;">
                        {{ $store->currency }} {{ number_format($product->compare_at_price, 2) }}
                    </span>
                    <span style="background: #fee2e2; color: #dc2626; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-weight: 600;">
                        Save {{ $product->getSalePercentage() }}%
                    </span>
                @endif
            </div>

            <div style="margin-bottom: 1.5rem;">
                @if($product->isInStock())
                    <span style="color: #059669; font-weight: 500;">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline; vertical-align: middle;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        In Stock
                        @if($product->stock_quantity <= 5)
                            <span style="color: #d97706;"> - Only {{ $product->stock_quantity }} left!</span>
                        @endif
                    </span>
                @else
                    <span style="color: #dc2626; font-weight: 500;">Out of Stock</span>
                @endif
            </div>

            @if($product->short_description)
                <p style="color: #4b5563; margin-bottom: 1.5rem;">{{ $product->short_description }}</p>
            @endif

            <!-- Variants -->
            @if($product->variants->isNotEmpty())
                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Options</label>
                    <select id="variant-select" class="form-input" onchange="updateVariant()">
                        <option value="">Select an option</option>
                        @foreach($product->variants as $variant)
                            <option value="{{ $variant->id }}" data-price="{{ $variant->price ?? $product->price }}" data-stock="{{ $variant->stock_quantity }}">
                                {{ $variant->getOptionString() ?: $variant->name }}
                                @if($variant->price && $variant->price != $product->price)
                                    ({{ $store->currency }} {{ number_format($variant->price, 2) }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Quantity -->
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <label class="form-label" style="margin: 0;">Quantity:</label>
                <div style="display: flex; border: 1px solid #d1d5db; border-radius: 0.5rem; overflow: hidden;">
                    <button onclick="updateQuantity(-1)" style="padding: 0.5rem 1rem; border: none; background: #f3f4f6; cursor: pointer;">-</button>
                    <input type="number" id="quantity" value="1" min="1" max="{{ $product->stock_quantity }}" style="width: 60px; text-align: center; border: none; border-left: 1px solid #d1d5db; border-right: 1px solid #d1d5db;">
                    <button onclick="updateQuantity(1)" style="padding: 0.5rem 1rem; border: none; background: #f3f4f6; cursor: pointer;">+</button>
                </div>
            </div>

            <!-- Add to Cart -->
            <button id="add-to-cart-btn" onclick="addToCart()" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;" {{ !$product->isInStock() ? 'disabled' : '' }}>
                {{ $product->isInStock() ? 'Add to Cart' : 'Out of Stock' }}
            </button>

            <!-- Description -->
            @if($product->description)
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                    <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Description</h2>
                    <div style="color: #4b5563; line-height: 1.8;">
                        {!! Str::sanitizeHtml($product->description) !!}
                    </div>
                </div>
            @endif

            <!-- Tags -->
            @if(!empty($product->tags))
                <div style="margin-top: 1.5rem;">
                    @foreach($product->tags as $tag)
                        <span style="display: inline-block; background: #f3f4f6; color: #4b5563; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem; margin-right: 0.5rem; margin-bottom: 0.5rem;">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Related Products -->
    @if($relatedProducts->isNotEmpty())
        <section style="margin-top: 4rem;">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Related Products</h2>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
                @foreach($relatedProducts as $related)
                    <div class="card">
                        <a href="{{ route('storefront.vodo-commerce.products.show', [$store->slug, $related->slug]) }}">
                            @if($related->getPrimaryImage())
                                <img src="{{ $related->getPrimaryImage() }}" alt="{{ $related->name }}" style="width: 100%; height: 180px; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 180px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                                    No Image
                                </div>
                            @endif
                        </a>
                        <div style="padding: 1rem;">
                            <h3 style="font-size: 0.9rem; font-weight: 600;">
                                <a href="{{ route('storefront.vodo-commerce.products.show', [$store->slug, $related->slug]) }}" style="color: inherit; text-decoration: none;">
                                    {{ $related->name }}
                                </a>
                            </h3>
                            <span style="font-weight: 700; color: var(--primary-color);">
                                {{ $store->currency }} {{ number_format($related->price, 2) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

@push('scripts')
<script>
const productId = {{ $product->id }};
let selectedVariantId = null;

function updateQuantity(delta) {
    const input = document.getElementById('quantity');
    const newValue = parseInt(input.value) + delta;
    if (newValue >= 1 && newValue <= parseInt(input.max)) {
        input.value = newValue;
    }
}

function updateVariant() {
    const select = document.getElementById('variant-select');
    const option = select.options[select.selectedIndex];
    selectedVariantId = select.value ? parseInt(select.value) : null;
}

function addToCart() {
    const quantity = parseInt(document.getElementById('quantity').value);

    fetch('{{ route('storefront.vodo-commerce.cart.add', $store->slug) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            product_id: productId,
            variant_id: selectedVariantId,
            quantity: quantity
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
