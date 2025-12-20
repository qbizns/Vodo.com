{{-- Plugin Status Badge --}}
@php
$statusClass = match($status) {
    'active' => 'badge-success',
    'inactive' => 'badge-secondary',
    'error' => 'badge-danger',
    'updating' => 'badge-warning',
    default => 'badge-secondary',
};

$statusLabel = match($status) {
    'active' => __t('plugins.active'),
    'inactive' => __t('plugins.inactive'),
    'error' => __t('plugins.error'),
    'updating' => __t('plugins.updating'),
    default => ucfirst($status),
};

$statusIcon = match($status) {
    'active' => 'checkCircle',
    'inactive' => 'pauseCircle',
    'error' => 'alertCircle',
    'updating' => 'refreshCw',
    default => 'info',
};
@endphp

<span class="status-badge {{ $statusClass }}">
    <span class="status-dot"></span>
    {{ $statusLabel }}
</span>
