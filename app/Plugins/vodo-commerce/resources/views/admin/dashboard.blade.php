@extends('backend.layouts.pjax')

@section('title', 'Commerce Dashboard')
@section('page-id', 'commerce/dashboard')
@section('header', 'Commerce Dashboard')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('commerce.admin.orders.index') }}" class="btn-secondary">
        @include('backend.partials.icon', ['icon' => 'clipboardList'])
        <span>View Orders</span>
    </a>
    <a href="{{ route('commerce.admin.products.create') }}" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>Add Product</span>
    </a>
</div>
@endsection

@section('content')
<div class="dashboard-grid">
    {{-- Stats Cards --}}
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon bg-blue-100 text-blue-600">
                @include('backend.partials.icon', ['icon' => 'shoppingCart'])
            </div>
            <div class="stat-content">
                <span class="stat-label">Total Orders</span>
                <span class="stat-value">{{ number_format($stats['total_orders'] ?? 0) }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-green-100 text-green-600">
                @include('backend.partials.icon', ['icon' => 'dollarSign'])
            </div>
            <div class="stat-content">
                <span class="stat-label">Revenue (30 days)</span>
                <span class="stat-value">{{ $store->currency ?? 'USD' }} {{ number_format($stats['total_revenue'] ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600">
                @include('backend.partials.icon', ['icon' => 'package'])
            </div>
            <div class="stat-content">
                <span class="stat-label">Products</span>
                <span class="stat-value">{{ number_format($totalProducts ?? 0) }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-orange-100 text-orange-600">
                @include('backend.partials.icon', ['icon' => 'users'])
            </div>
            <div class="stat-content">
                <span class="stat-label">Customers</span>
                <span class="stat-value">{{ number_format($totalCustomers ?? 0) }}</span>
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="card">
        <div class="card-header">
            <h3>Recent Orders</h3>
            <a href="{{ route('commerce.admin.orders.index') }}" class="text-sm text-primary">View All</a>
        </div>
        <div class="card-body p-0">
            @if($recentOrders->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-right">Total</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('commerce.admin.orders.show', $order->id) }}" class="font-medium">
                                #{{ $order->order_number }}
                            </a>
                        </td>
                        <td>{{ $order->customer?->name ?? 'Guest' }}</td>
                        <td>
                            <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td class="text-right">{{ $store->currency }} {{ number_format($order->total, 2) }}</td>
                        <td>{{ $order->placed_at?->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state py-8">
                <p class="text-secondary">No orders yet</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Low Stock Products --}}
    @if($lowStockProducts->count() > 0)
    <div class="card">
        <div class="card-header">
            <h3>Low Stock Alert</h3>
            <a href="{{ route('commerce.admin.products.index') }}" class="text-sm text-primary">View All Products</a>
        </div>
        <div class="card-body p-0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockProducts as $product)
                    <tr>
                        <td>
                            <a href="{{ route('commerce.admin.products.edit', $product->id) }}">
                                {{ $product->name }}
                            </a>
                        </td>
                        <td class="text-right">
                            <span class="text-warning font-medium">{{ $product->stock_quantity }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<style>
.dashboard-grid {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--bg-surface-1);
    border: 1px solid var(--border-color);
    border-radius: 12px;
}
.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
}
.stat-icon svg {
    width: 24px;
    height: 24px;
}
.stat-content {
    display: flex;
    flex-direction: column;
}
.stat-label {
    font-size: 13px;
    color: var(--text-secondary);
}
.stat-value {
    font-size: 24px;
    font-weight: 600;
    color: var(--text-primary);
}
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-processing { background: #dbeafe; color: #1e40af; }
.badge-completed { background: #d1fae5; color: #065f46; }
.badge-cancelled { background: #fee2e2; color: #991b1b; }
</style>
@endsection

