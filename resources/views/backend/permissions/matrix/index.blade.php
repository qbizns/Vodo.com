{{-- Permission Matrix (Screen 3 - Permissions & Access Control) --}}
{{-- Uses Vodo.permissions.PermissionMatrix (vanilla JS, no Alpine) --}}

@extends('backend.layouts.pjax')

@section('title', 'Permission Matrix')
@section('page-id', 'system/permissions/matrix')
@section('require-css', 'permissions')

@section('header', 'Permission Matrix')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.index') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Roles</span>
    </a>
    <button type="button"
            class="btn-primary flex items-center gap-2"
            id="saveMatrixBtn"
            style="display: none;">
        @include('backend.partials.icon', ['icon' => 'save'])
        <span>Save Changes</span>
    </button>
</div>
@endsection

@section('content')
@php
$matrixConfig = [
    'permissions' => $matrixData['permissions'] ?? [],
    'inherited' => $matrixData['inherited'] ?? [],
    'grantable' => $matrixData['grantable'] ?? [],
    'groups' => array_keys($groupedPermissions ?? [])
];
@endphp
<div class="permission-matrix-page" 
     data-component="matrix"
     data-config="{{ json_encode($matrixConfig) }}"
     data-save-url="{{ route('admin.permissions.matrix.update') }}">
    {{-- Filters --}}
    <div class="matrix-toolbar mb-4">
        <div class="search-filter-group">
            <div class="search-input-wrapper">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text"
                       class="search-input"
                       placeholder="Search permissions..."
                       data-role="search">
            </div>

            <select class="filter-select" data-role="group-filter">
                <option value="">All Groups</option>
                @foreach($groups as $group)
                    <option value="{{ $group->slug }}">{{ $group->name }}</option>
                @endforeach
            </select>

            <select class="filter-select" data-role="plugin-filter">
                <option value="">All Plugins</option>
                <option value="core">Core</option>
                @foreach($plugins as $plugin)
                    <option value="{{ $plugin->slug }}">{{ $plugin->name }}</option>
                @endforeach
            </select>

            <label class="checkbox-label">
                <input type="checkbox" data-role="changes-only">
                <span>Show changes only</span>
            </label>
        </div>

        <div class="matrix-actions">
            <button type="button" class="btn-secondary btn-sm" data-action="collapse-all">
                @include('backend.partials.icon', ['icon' => 'minimize2'])
                Collapse All
            </button>
            <button type="button" class="btn-secondary btn-sm" data-action="expand-all">
                @include('backend.partials.icon', ['icon' => 'maximize2'])
                Expand All
            </button>
        </div>
    </div>

    {{-- Change Counter --}}
    <div class="changes-bar" style="display: none;">
        <span class="changes-count">
            <span data-count="changes">0</span> unsaved changes
        </span>
        <div class="changes-actions">
            <button type="button" class="btn-secondary btn-sm" data-action="reset">
                Reset Changes
            </button>
            <button type="button" class="btn-primary btn-sm" data-action="save">
                Save Changes
            </button>
        </div>
    </div>

    {{-- Matrix Table --}}
    <div class="matrix-container">
        <div class="matrix-scroll">
            <table class="permission-matrix">
                <thead>
                    <tr>
                        <th class="permission-col sticky-col">Permission</th>
                        @foreach($roles as $role)
                            @php $isSuperAdmin = $role->is_system && $role->slug === 'super-admin'; @endphp
                            <th class="role-col {{ $isSuperAdmin ? 'super-admin' : '' }}">
                                <div class="role-header">
                                    <span class="role-color" style="background-color: {{ $role->color }};"></span>
                                    <span class="role-name">{{ $role->name }}</span>
                                    @if($role->is_system)
                                        <span class="role-badge">System</span>
                                    @endif
                                </div>
                                @if(!$isSuperAdmin)
                                    <button type="button"
                                            class="btn-link btn-xs"
                                            data-action="toggle-column"
                                            data-role-id="{{ $role->id }}">
                                        Toggle All
                                    </button>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedPermissions as $groupSlug => $group)
                        {{-- Group Header --}}
                        <tr class="group-row" data-group="{{ $groupSlug }}">
                            <td class="group-cell sticky-col" colspan="{{ count($roles) + 1 }}">
                                <div class="group-cell-content">
                                    <div class="group-info">
                                        <span class="group-icon">
                                            @include('backend.partials.icon', ['icon' => 'folder'])
                                        </span>
                                        <span class="group-name">{{ $group['name'] }}</span>
                                        <span class="group-count">({{ count($group['permissions']) }})</span>
                                        @if($group['plugin'] ?? null)
                                            <span class="group-plugin">{{ $group['plugin'] }}</span>
                                        @endif
                                    </div>
                                    <span class="group-chevron expanded">
                                        @include('backend.partials.icon', ['icon' => 'chevronDown'])
                                    </span>
                                </div>
                            </td>
                        </tr>

                        {{-- Permission Rows --}}
                        @foreach($group['permissions'] as $permission)
                            <tr class="permission-row {{ $permission['is_dangerous'] ? 'dangerous' : '' }}"
                                data-group="{{ $groupSlug }}"
                                data-perm-slug="{{ $permission['slug'] }}">
                                <td class="permission-cell sticky-col">
                                    <span class="permission-name">
                                        {{ $permission['label'] ?? $permission['slug'] }}
                                    </span>
                                    @if($permission['is_dangerous'])
                                        <span class="badge badge-danger" title="Dangerous permission">!</span>
                                    @endif
                                    @if(!empty($permission['dependencies']))
                                        <span class="deps-indicator"
                                              title="Requires: {{ implode(', ', $permission['dependencies']) }}">
                                            @include('backend.partials.icon', ['icon' => 'link'])
                                        </span>
                                    @endif
                                </td>

                                @foreach($roles as $role)
                                    @php
                                        $isSuperAdmin = $role->slug === 'super-admin';
                                        $isGranted = isset($matrix[$role->id][$permission['id']]);
                                        $isInherited = isset($inheritedMatrix[$role->id][$permission['id']]);
                                    @endphp
                                    <td class="matrix-cell {{ $isSuperAdmin ? 'super-admin' : '' }} {{ $isInherited ? 'inherited' : '' }}">
                                        @if($isSuperAdmin)
                                            <span class="matrix-check all" title="All permissions">
                                                @include('backend.partials.icon', ['icon' => 'checkCircle'])
                                            </span>
                                        @else
                                            <button type="button"
                                                    class="matrix-toggle {{ $isGranted ? 'granted' : '' }} {{ $isInherited ? 'inherited' : '' }}"
                                                    data-role-id="{{ $role->id }}"
                                                    data-perm-id="{{ $permission['id'] }}"
                                                    {{ $isInherited ? 'disabled' : '' }}>
                                                @if($isGranted)
                                                    <span class="toggle-check">
                                                        @include('backend.partials.icon', ['icon' => 'check'])
                                                    </span>
                                                @elseif($isInherited)
                                                    <span class="toggle-inherited">
                                                        @include('backend.partials.icon', ['icon' => 'cornerDownRight'])
                                                    </span>
                                                @endif
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <br>
    
    {{-- Legend --}}
    <div class="matrix-legend mt-4">
        <div class="legend-item">
            <span class="legend-icon all">@include('backend.partials.icon', ['icon' => 'checkCircle'])</span>
            <span>All (Super Admin)</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon granted">@include('backend.partials.icon', ['icon' => 'check'])</span>
            <span>Granted</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon inherited">@include('backend.partials.icon', ['icon' => 'cornerDownRight'])</span>
            <span>Inherited</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon denied"></span>
            <span>Denied</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon dangerous">!</span>
            <span>Dangerous</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon deps">@include('backend.partials.icon', ['icon' => 'link'])</span>
            <span>Has Dependencies</span>
        </div>
    </div>
</div>

<script>
(function() {
    function initMatrix() {
        var container = document.querySelector('.permission-matrix-page[data-component="matrix"]');
        if (!container || container.dataset.initialized) return;
        
        try {
            var config = JSON.parse(container.dataset.config);
            if (window.Vodo && window.Vodo.permissions && window.Vodo.permissions.PermissionMatrix) {
                new Vodo.permissions.PermissionMatrix(container, config);
                container.dataset.initialized = 'true';
            }
        } catch (e) {
            console.error('Failed to initialize PermissionMatrix:', e);
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initMatrix, 0);
    } else {
        document.addEventListener('DOMContentLoaded', initMatrix);
    }
    document.addEventListener('pjax:complete', initMatrix);
})();
</script>
@endsection
