@extends('backend.layouts.pjax')

@section('title', 'Invoices')
@section('page-id', 'subscriptions/invoices')
@section('require-css', 'subscriptions')

@section('header', 'Invoices')

@section('content')
<div class="invoices-page">
    <div class="card">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <span class="invoice-number">{{ $invoice->invoice_number }}</span>
                        </td>
                        <td>
                            <div class="user-cell">
                                <div class="user-name">{{ $invoice->user->name ?? 'Unknown' }}</div>
                                <div class="user-email">{{ $invoice->user->email ?? '' }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="amount">{{ $invoice->formatted_total }}</span>
                        </td>
                        <td>
                            <span class="status-badge status-badge--{{ $invoice->status }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td>
                            @if($invoice->due_date)
                                <span class="{{ $invoice->is_overdue ? 'text-danger' : 'text-secondary' }}">
                                    {{ $invoice->due_date->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-tertiary">-</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="actions-dropdown">
                                <button type="button" class="btn-icon" data-dropdown-trigger>
                                    @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                </button>
                                <div class="dropdown-menu">
                                    <a href="{{ route('admin.plugins.subscriptions.invoices.show', $invoice) }}" class="dropdown-item">
                                        @include('backend.partials.icon', ['icon' => 'eye'])
                                        <span>View</span>
                                    </a>
                                    @if($invoice->status === 'pending')
                                    <button type="button" class="dropdown-item" onclick="markPaid({{ $invoice->id }})">
                                        @include('backend.partials.icon', ['icon' => 'check'])
                                        <span>Mark Paid</span>
                                    </button>
                                    @endif
                                    <a href="{{ route('admin.plugins.subscriptions.invoices.download', $invoice) }}" class="dropdown-item">
                                        @include('backend.partials.icon', ['icon' => 'download'])
                                        <span>Download</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    @include('backend.partials.icon', ['icon' => 'fileText'])
                                </div>
                                <h3>No Invoices Found</h3>
                                <p>No invoices have been generated yet.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div class="card-footer">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function markPaid(invoiceId) {
    Vodo.modal.confirm({
        title: 'Mark as Paid',
        message: 'Are you sure you want to mark this invoice as paid?',
        confirmText: 'Mark Paid',
        onConfirm: () => {
            Vodo.api.post(`/admin/subscriptions/invoices/${invoiceId}/mark-paid`)
                .then(response => {
                    if (response.success) {
                        Vodo.notification.success(response.message);
                        Vodo.pjax.reload();
                    }
                })
                .catch(error => {
                    Vodo.notification.error(error.message);
                });
        }
    });
}
</script>

<style>
.status-badge--paid { background: #10b98120; color: #10b981; }
.status-badge--pending { background: #f59e0b20; color: #f59e0b; }
.status-badge--overdue { background: #ef444420; color: #ef4444; }
.status-badge--cancelled { background: #6b728020; color: #6b7280; }
</style>
@endsection

