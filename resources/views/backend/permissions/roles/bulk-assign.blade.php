{{-- Bulk Role Assignment (Screen 8 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

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
<div class="bulk-assign-page" x-data="bulkAssign(@json($existingUserIds))">
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
          @submit.prevent="submitForm">
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
                                       checked
                                       x-model="expiresType">
                                <span>Never expires</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio"
                                       name="expires_type"
                                       value="date"
                                       x-model="expiresType">
                                <span>Set expiration date</span>
                            </label>
                        </div>
                        <div class="mt-2" x-show="expiresType === 'date'">
                            <input type="date"
                                   name="expires_at"
                                   class="form-input"
                                   x-model="expiresAt"
                                   :required="expiresType === 'date'">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notify_users" value="1" x-model="notifyUsers">
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
                    <span x-text="selectedUsers.size + ' selected'"></span>
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
                               x-model="searchQuery"
                               @input.debounce.300ms="searchUsers">
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-secondary btn-sm" @click="selectAllVisible">
                            Select All Visible
                        </button>
                        <button type="button" class="btn-secondary btn-sm" @click="deselectAll">
                            Deselect All
                        </button>
                    </div>
                </div>

                {{-- Users List --}}
                <div class="user-selection-list" x-ref="userList">
                    @foreach($users as $user)
                        @php
                            $hasRole = in_array($user->id, $existingUserIds);
                        @endphp
                        <label class="user-selection-item {{ $hasRole ? 'already-assigned' : '' }}"
                               data-user-id="{{ $user->id }}">
                            <input type="checkbox"
                                   name="users[]"
                                   value="{{ $user->id }}"
                                   :checked="isSelected({{ $user->id }})"
                                   @change="toggleUser({{ $user->id }})"
                                   {{ $hasRole ? 'disabled' : '' }}>
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
        <div class="card mb-6" x-show="selectedUsers.size > 0">
            <div class="card-header">
                <h3>Summary</h3>
            </div>
            <div class="card-body">
                <ul class="summary-list">
                    <li>
                        <span x-text="selectedUsers.size"></span> user(s) will be assigned the
                        <strong>{{ $role->name }}</strong> role
                    </li>
                    <li x-show="expiresType === 'never'">
                        Role will not expire
                    </li>
                    <li x-show="expiresType === 'date'">
                        Role expires on <span x-text="expiresAt"></span>
                    </li>
                    <li x-show="notifyUsers">
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
                    :disabled="selectedUsers.size === 0 || isSubmitting">
                <template x-if="isSubmitting">
                    <span>Assigning...</span>
                </template>
                <template x-if="!isSubmitting">
                    <span>
                        @include('backend.partials.icon', ['icon' => 'userPlus'])
                        Assign Role to <span x-text="selectedUsers.size"></span> Users
                    </span>
                </template>
            </button>
        </div>
    </form>
</div>

<script>
function bulkAssign(existingUserIds) {
    return {
        selectedUsers: new Set(),
        existingUserIds: new Set(existingUserIds),
        searchQuery: '',
        expiresType: 'never',
        expiresAt: '',
        notifyUsers: false,
        isSubmitting: false,

        isSelected(userId) {
            return this.selectedUsers.has(userId);
        },

        toggleUser(userId) {
            if (this.existingUserIds.has(userId)) return;

            if (this.selectedUsers.has(userId)) {
                this.selectedUsers.delete(userId);
            } else {
                this.selectedUsers.add(userId);
            }
        },

        selectAllVisible() {
            const checkboxes = this.$refs.userList.querySelectorAll('input[type="checkbox"]:not(:disabled)');
            checkboxes.forEach(cb => {
                const userId = parseInt(cb.value);
                this.selectedUsers.add(userId);
                cb.checked = true;
            });
        },

        deselectAll() {
            this.selectedUsers.clear();
            const checkboxes = this.$refs.userList.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);
        },

        searchUsers() {
            const params = new URLSearchParams(window.location.search);
            params.set('search', this.searchQuery);
            const url = `{{ route('admin.roles.bulk-assign', $role) }}?${params.toString()}`;
            Vodo.pjax.load(url);
        },

        async submitForm() {
            if (this.selectedUsers.size === 0 || this.isSubmitting) return;

            this.isSubmitting = true;

            try {
                const response = await Vodo.api.post('{{ route('admin.roles.bulk-assign.store', $role) }}', {
                    users: Array.from(this.selectedUsers),
                    expires_at: this.expiresType === 'date' ? this.expiresAt : null,
                    notify_users: this.notifyUsers
                });

                if (response.success) {
                    Vodo.notification.success(response.message || 'Users assigned successfully');
                    Vodo.pjax.load('{{ route('admin.roles.show', $role) }}');
                }
            } catch (error) {
                Vodo.notification.error(error.message || 'Failed to assign users');
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endsection
