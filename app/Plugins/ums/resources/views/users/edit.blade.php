@extends('backend.layouts.pjax')

@section('title', 'Edit User')
@section('page-id', 'ums/users/edit')
@section('require-css', 'ums')

@section('header', 'Edit User')

@section('content')
<div class="user-form-page">
    <form id="editUserForm" action="{{ route('plugins.ums.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card">
            <div class="card-header">
                <h3>User Information</h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="form-label required">Name</label>
                        <input type="text" id="name" name="name" class="form-input" value="{{ $user->name }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label required">Email</label>
                        <input type="email" id="email" name="email" class="form-input" value="{{ $user->email }}" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-input" minlength="8">
                        <span class="form-hint">Leave empty to keep current password</span>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3>Roles</h3>
            </div>
            <div class="card-body">
                <div class="roles-grid">
                    @foreach($roles as $role)
                    <label class="role-checkbox">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                               {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                        <div class="role-content">
                            <div class="role-icon" style="background: {{ $role->color }}20; color: {{ $role->color }}">
                                @include('backend.partials.icon', ['icon' => $role->icon ?? 'shield'])
                            </div>
                            <div class="role-info">
                                <span class="role-name">{{ $role->name }}</span>
                                <span class="role-desc">{{ $role->description ?? 'No description' }}</span>
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3>Status</h3>
            </div>
            <div class="card-body">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}>
                    <span>Active</span>
                </label>
                <span class="form-hint">Inactive users cannot log in to the system.</span>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('plugins.ums.users.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update User</button>
        </div>
    </form>
</div>

<script>
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    // Convert roles checkboxes to array
    data.roles = Array.from(document.querySelectorAll('input[name="roles[]"]:checked')).map(cb => cb.value);
    data.is_active = document.querySelector('input[name="is_active"]').checked ? 1 : 0;

    // Remove empty password
    if (!data.password) {
        delete data.password;
        delete data.password_confirmation;
    }

    Vodo.api.put(this.action, data)
        .then(response => {
            if (response.success) {
                Vodo.notification.success(response.message);
                if (response.redirect) {
                    Vodo.pjax.load(response.redirect);
                }
            }
        })
        .catch(error => {
            Vodo.notification.error(error.message || 'Failed to update user');
        });
});
</script>

<style>
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 12px;
}

.role-checkbox {
    cursor: pointer;
}

.role-checkbox input {
    display: none;
}

.role-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 2px solid var(--border-color, #e5e7eb);
    border-radius: 8px;
    transition: all 0.2s;
}

.role-checkbox input:checked + .role-content {
    border-color: var(--primary, #6366f1);
    background: var(--primary, #6366f1)05;
}

.role-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.role-icon svg {
    width: 20px;
    height: 20px;
}

.role-info {
    display: flex;
    flex-direction: column;
}

.role-name {
    font-weight: 500;
    color: var(--text-primary, #1f2937);
}

.role-desc {
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
}
</style>
@endsection

