{{-- Edit Role (Screen 2 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Edit Role: ' . $role->name)
@section('page-id', 'system/roles/edit')
@section('require-css', 'permissions')

@section('header')
Edit Role: {{ $role->name }}
@if($role->is_system)
    <span class="system-badge ml-2">System</span>
@endif
@endsection

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.index') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Roles</span>
    </a>
    <a href="{{ route('admin.roles.show', $role) }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'eye'])
        <span>View Details</span>
    </a>
</div>
@endsection

@section('content')
<div class="role-editor-page">
    {{-- System Role Warning --}}
    @if($role->is_system)
        <div class="alert alert-info mb-4">
            <div class="alert-icon">
                @include('backend.partials.icon', ['icon' => 'info'])
            </div>
            <div class="alert-content">
                <h4>System Role</h4>
                <p>This is a system-defined role. Some fields may be restricted to protect core functionality.</p>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.roles.update', $role) }}"
          method="POST"
          id="roleForm"
          class="role-editor-form">
        @csrf
        @method('PUT')

        @include('backend.permissions.roles._form', [
            'role' => $role,
            'permissions' => $permissions,
            'selectedPermissions' => old('permissions', $selectedPermissions),
            'inheritedPermissions' => $inheritedPermissions,
            'lockedPermissions' => $lockedPermissions ?? [],
            'parentRoles' => $parentRoles,
            'plugins' => $plugins ?? []
        ])

        {{-- Form Actions --}}
        <div class="form-actions">
            <a href="{{ route('admin.roles.index') }}" class="btn-secondary">Cancel</a>

            @if(!$role->is_system)
                <button type="button"
                        class="btn-danger"
                        onclick="deleteRole()">
                    @include('backend.partials.icon', ['icon' => 'trash'])
                    Delete Role
                </button>
            @endif

            <button type="submit" class="btn-primary">
                @include('backend.partials.icon', ['icon' => 'check'])
                Save Changes
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('roleForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    // Convert FormData to object, handling arrays properly
    const data = {};
    formData.forEach((value, key) => {
        if (key.endsWith('[]')) {
            const arrayKey = key.slice(0, -2);
            if (!data[arrayKey]) data[arrayKey] = [];
            data[arrayKey].push(value);
        } else if (key !== '_method') {
            data[key] = value;
        }
    });

    // Handle unchecked checkboxes
    if (!data.is_default) data.is_default = false;
    if (!data.is_active) data.is_active = false;

    Vodo.api.put(form.action, data).then(response => {
        if (response.success) {
            Vodo.notification.success(response.message || 'Role updated successfully');
            if (response.redirect) {
                Vodo.pjax.load(response.redirect);
            }
        }
    }).catch(error => {
        if (error.errors) {
            // Display validation errors
            Object.keys(error.errors).forEach(field => {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) {
                    input.classList.add('is-invalid');
                    const errorEl = input.nextElementSibling;
                    if (errorEl && errorEl.classList.contains('form-error')) {
                        errorEl.textContent = error.errors[field][0];
                    }
                }
            });
        }
        Vodo.notification.error(error.message || 'Failed to update role');
    });
});

function deleteRole() {
    Vodo.modal.confirm({
        title: 'Delete Role',
        message: 'Are you sure you want to delete this role? This action cannot be undone. Users with this role will lose these permissions.',
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        onConfirm: () => {
            Vodo.api.delete('{{ route('admin.roles.destroy', $role) }}').then(response => {
                if (response.success) {
                    Vodo.notification.success(response.message || 'Role deleted successfully');
                    Vodo.pjax.load('{{ route('admin.roles.index') }}');
                }
            }).catch(error => {
                Vodo.notification.error(error.message || 'Failed to delete role');
            });
        }
    });
}
</script>
@endsection
