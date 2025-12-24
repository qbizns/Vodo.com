{{-- Create Role (Screen 2 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Create Role')
@section('page-id', 'system/roles/create')
@section('require-css', 'permissions')

@section('header', 'Create Role')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.index') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Roles</span>
    </a>
</div>
@endsection

@section('content')
<div class="role-editor-page">
    <form action="{{ route('admin.roles.store') }}"
          method="POST"
          id="roleForm"
          class="role-editor-form">
        @csrf

        @include('backend.permissions.roles._form', [
            'role' => null,
            'permissions' => $permissions,
            'selectedPermissions' => old('permissions', []),
            'inheritedPermissions' => [],
            'lockedPermissions' => $lockedPermissions ?? [],
            'parentRoles' => $parentRoles,
            'plugins' => $plugins ?? []
        ])

        {{-- Form Actions --}}
        <div class="form-actions">
            <a href="{{ route('admin.roles.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">
                @include('backend.partials.icon', ['icon' => 'check'])
                Create Role
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
        } else {
            data[key] = value;
        }
    });

    // Handle unchecked checkboxes
    if (!data.is_default) data.is_default = false;
    if (!data.is_active) data.is_active = false;

    Vodo.api.post(form.action, data).then(response => {
        if (response.success) {
            Vodo.notifications.success(response.message || 'Role created successfully');
            if (response.redirect) {
                window.location.href = response.redirect;
            } else {
                window.location.href = '{{ route('admin.roles.index') }}';
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
        Vodo.notifications.error(error.message || 'Failed to create role');
    });
});
</script>
@endsection
