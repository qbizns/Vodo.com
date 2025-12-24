{{-- Bulk Role Assignment (Screen 8 - Permissions & Access Control) --}}
{{-- Uses Vodo.permissions.BulkAssign (vanilla JS, no Alpine) --}}

@extends('backend.layouts.pjax')

@section('title', 'Assign Users to ' . $role->name)
@section('page-id', 'system/roles/bulk-assign')
@section('require-css', 'permissions')

@section('header', 'Bulk Assign Role: ' . $role->name)

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.show', $role) }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Role</span>
    </a>
</div>
@endsection

@section('content')
@php
$bulkConfig = [
    'existingUserIds' => $existingUserIds
];
@endphp
<div class="bulk-assign-page" 
     data-component="bulk-assign"
     data-config="{{ json_encode($bulkConfig) }}"
     data-redirect-url="{{ route('admin.roles.show', $role) }}">
    {{-- Role Summary --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center gap-4">
                <div class="role-color-indicator lg" style="background-color: {{ $role->color }};">
                    @include('backend.partials.icon', ['icon' => $role->icon ?? 'shield'])
                </div>
                <div>
                    <h3 class="text-lg font-semibold">{{ $role->name }}</h3>
                    <p class="text-muted">
                        {{ $role->grantedPermissions->count() }} permissions
                        &middot;
                        Currently assigned to {{ $role->users_count }} users
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.roles.bulk-assign.store', $role) }}"
          method="POST"
          id="bulkAssignForm"
          data-submit-url="{{ route('admin.roles.bulk-assign.store', $role) }}">
        @csrf

        {{-- Assignment Options --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3>Assignment Options</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Expiration</label>
                        <div class="flex items-center gap-4">
                            <label class="radio-label">
                                <input type="radio"
                                       name="expires_type"
                                       value="never"
                                       checked>
                                <span>Never expires</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio"
                                       name="expires_type"
                                       value="date">
                                <span>Set expiration date</span>
                            </label>
                        </div>
                        <div class="mt-2" style="display: none;">
                            <input type="date"
                                   name="expires_at"
                                   class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notify_users" value="1">
                            <span>Send notification email to users</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- User Selection --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3>Select Users</h3>
                <div class="card-header-actions">
                    <span data-count="selected">0</span> selected
                </div>
            </div>
            <div class="card-body">
                {{-- Search and Filters --}}
                <div class="toolbar mb-4">
                    <div class="search-input-wrapper">
                        @include('backend.partials.icon', ['icon' => 'search'])
                        <input type="text"
                               class="search-input"
                               placeholder="Search users by name or email..."
                               data-role="search"
                               id="userSearch">
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-secondary btn-sm" data-action="select-all-visible">
                            Select All Visible
                        </button>
                        <button type="button" class="btn-secondary btn-sm" data-action="deselect-all">
                            Deselect All
                        </button>
                    </div>
                </div>

                {{-- Users List --}}
                <div class="user-selection-list" id="userList">
                    @foreach($users as $user)
                        @php
                            $hasRole = in_array($user->id, $existingUserIds);
                        @endphp
                        <label class="user-selection-item {{ $hasRole ? 'already-assigned' : '' }}"
                               data-user-id="{{ $user->id }}">
                            <input type="checkbox"
                                   name="users[]"
                                   value="{{ $user->id }}"
                                   {{ $hasRole ? 'disabled checked' : '' }}>
                            <div class="user-avatar">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="user-info">
                                <span class="user-name">{{ $user->name }}</span>
                                <span class="user-email">{{ $user->email }}</span>
                            </div>
                            @if($hasRole)
                                <span class="already-badge">Already assigned</span>
                            @endif
                        </label>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($users->hasPages())
                    <div class="pagination-wrapper mt-4">
                        {{ $users->links() }}
                    </div>
                @endif

                <p class="text-muted mt-4">
                    Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }} users
                </p>
            </div>
        </div>

        {{-- Summary --}}
        <div class="card mb-6" data-role="summary" style="display: none;">
            <div class="card-header">
                <h3>Summary</h3>
            </div>
            <div class="card-body">
                <ul class="summary-list">
                    <li>
                        <span data-count="selected">0</span> user(s) will be assigned the
                        <strong>{{ $role->name }}</strong> role
                    </li>
                    <li data-show="expires-never">
                        Role will not expire
                    </li>
                    <li data-show="expires-date" style="display: none;">
                        Role expires on <span data-value="expires-at"></span>
                    </li>
                    <li data-show="notify" style="display: none;">
                        Notification emails will be sent
                    </li>
                </ul>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="form-actions">
            <a href="{{ route('admin.roles.show', $role) }}" class="btn-secondary">Cancel</a>
            <button type="submit"
                    class="btn-primary"
                    disabled>
                <span class="btn-text">
                    @include('backend.partials.icon', ['icon' => 'userPlus'])
                    Assign Role to <span data-count="selected">0</span> Users
                </span>
                <span class="btn-loading" style="display: none;">Assigning...</span>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    function initBulkAssign() {
        var container = document.querySelector('.bulk-assign-page[data-component="bulk-assign"]');
        if (!container || container.dataset.initialized) return;
        
        // Simple vanilla JS implementation
        var selectedUsers = new Set();
        var existingUserIds = new Set(JSON.parse(container.dataset.config).existingUserIds || []);
        
        // User selection
        container.addEventListener('change', function(e) {
            if (e.target.matches('.user-selection-item input[type="checkbox"]')) {
                var userId = parseInt(e.target.value);
                if (existingUserIds.has(userId)) return;
                
                if (e.target.checked) {
                    selectedUsers.add(userId);
                } else {
                    selectedUsers.delete(userId);
                }
                updateUI();
            }
            
            // Expiration type
            if (e.target.matches('[name="expires_type"]')) {
                var expiresDateWrapper = container.querySelector('[name="expires_at"]').closest('.mt-2');
                if (expiresDateWrapper) {
                    expiresDateWrapper.style.display = e.target.value === 'date' ? '' : 'none';
                }
                updateSummary();
            }
            
            if (e.target.matches('[name="notify_users"]')) {
                updateSummary();
            }
            
            if (e.target.matches('[name="expires_at"]')) {
                updateSummary();
            }
        });
        
        // Select all visible
        container.querySelector('[data-action="select-all-visible"]')?.addEventListener('click', function() {
            container.querySelectorAll('.user-selection-item input[type="checkbox"]:not(:disabled)').forEach(function(cb) {
                cb.checked = true;
                selectedUsers.add(parseInt(cb.value));
            });
            updateUI();
        });
        
        // Deselect all
        container.querySelector('[data-action="deselect-all"]')?.addEventListener('click', function() {
            container.querySelectorAll('.user-selection-item input[type="checkbox"]:not(:disabled)').forEach(function(cb) {
                cb.checked = false;
            });
            selectedUsers.clear();
            updateUI();
        });
        
        // Search (server-side)
        var searchInput = container.querySelector('#userSearch');
        var searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    var params = new URLSearchParams(window.location.search);
                    params.set('search', searchInput.value);
                    window.location.href = window.location.pathname + '?' + params.toString();
                }, 500);
            });
        }
        
        // Form submission
        var form = container.querySelector('#bulkAssignForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (selectedUsers.size === 0) return;
                
                var submitBtn = form.querySelector('[type="submit"]');
                var btnText = submitBtn.querySelector('.btn-text');
                var btnLoading = submitBtn.querySelector('.btn-loading');
                
                submitBtn.disabled = true;
                if (btnText) btnText.style.display = 'none';
                if (btnLoading) btnLoading.style.display = '';
                
                var expiresType = form.querySelector('[name="expires_type"]:checked')?.value;
                var expiresAt = expiresType === 'date' ? form.querySelector('[name="expires_at"]')?.value : null;
                var notifyUsers = form.querySelector('[name="notify_users"]')?.checked || false;
                
                Vodo.api.post(form.dataset.submitUrl, {
                    users: Array.from(selectedUsers),
                    expires_at: expiresAt,
                    notify_users: notifyUsers
                }).then(function(response) {
                    if (response.success) {
                        Vodo.notifications.success(response.message || 'Users assigned successfully');
                        window.location.href = container.dataset.redirectUrl;
                    }
                }).catch(function(error) {
                    Vodo.notifications.error(error.message || 'Failed to assign users');
                    submitBtn.disabled = false;
                    if (btnText) btnText.style.display = '';
                    if (btnLoading) btnLoading.style.display = 'none';
                });
            });
        }
        
        function updateUI() {
            // Update counts
            container.querySelectorAll('[data-count="selected"]').forEach(function(el) {
                el.textContent = selectedUsers.size;
            });
            
            // Update submit button
            var submitBtn = container.querySelector('[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = selectedUsers.size === 0;
            }
            
            // Update summary visibility
            var summary = container.querySelector('[data-role="summary"]');
            if (summary) {
                summary.style.display = selectedUsers.size > 0 ? '' : 'none';
            }
            
            updateSummary();
        }
        
        function updateSummary() {
            var expiresType = container.querySelector('[name="expires_type"]:checked')?.value || 'never';
            var notifyUsers = container.querySelector('[name="notify_users"]')?.checked || false;
            var expiresAt = container.querySelector('[name="expires_at"]')?.value || '';
            
            var expiresNever = container.querySelector('[data-show="expires-never"]');
            var expiresDate = container.querySelector('[data-show="expires-date"]');
            var notify = container.querySelector('[data-show="notify"]');
            var expiresAtValue = container.querySelector('[data-value="expires-at"]');
            
            if (expiresNever) expiresNever.style.display = expiresType === 'never' ? '' : 'none';
            if (expiresDate) expiresDate.style.display = expiresType === 'date' ? '' : 'none';
            if (notify) notify.style.display = notifyUsers ? '' : 'none';
            if (expiresAtValue) expiresAtValue.textContent = expiresAt;
        }
        
        container.dataset.initialized = 'true';
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initBulkAssign, 0);
    } else {
        document.addEventListener('DOMContentLoaded', initBulkAssign);
    }
    document.addEventListener('pjax:complete', initBulkAssign);
})();
</script>
@endsection
