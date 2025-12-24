{{-- Role Comparison (Screen 7 - Permissions & Access Control) --}}
{{-- Uses Vodo.permissions.RoleCompare (vanilla JS, no Alpine) --}}

@extends('backend.layouts.pjax')

@section('title', 'Compare Roles')
@section('page-id', 'system/roles/compare')
@section('require-css', 'permissions')

@section('header', 'Compare Roles')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.index') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Roles</span>
    </a>
</div>
@endsection

@section('content')
@php
$compareConfig = [
    'comparison' => $comparison ?? null,
    'roles' => $roles ?? []
];
@endphp
<div class="role-compare-page" 
     data-component="role-compare"
     data-config="{{ json_encode($compareConfig) }}"
     data-compare-url="{{ route('admin.roles.compare') }}">
    {{-- Role Selection --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3>Select Roles to Compare (2-5 roles)</h3>
        </div>
        <div class="card-body">
            <div class="role-selector">
                @foreach($allRoles as $role)
                    @php $isSelected = in_array($role->id, ($roles ?? collect())->pluck('id')->toArray()); @endphp
                    <label class="role-selector-item {{ $isSelected ? 'selected' : '' }}"
                           data-role-id="{{ $role->id }}">
                        <input type="checkbox"
                               value="{{ $role->id }}"
                               {{ $isSelected ? 'checked' : '' }}>
                        <div class="role-selector-content">
                            <div class="role-color" style="background-color: {{ $role->color }};"></div>
                            <span class="role-name">{{ $role->name }}</span>
                            <span class="role-perms">{{ $role->permissions_count }} perms</span>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-between items-center mt-4">
                <span class="text-muted" data-count="selected">{{ count($roles ?? []) }} roles selected</span>
                <button type="button"
                        class="btn-primary"
                        data-action="compare"
                        {{ count($roles ?? []) < 2 ? 'disabled' : '' }}>
                    @include('backend.partials.icon', ['icon' => 'gitCompare'])
                    Compare Roles
                </button>
            </div>
        </div>
    </div>

    {{-- Comparison Results --}}
    @if($comparison ?? null)
        <div class="comparison-results">
            {{-- Summary Stats --}}
            <div class="comparison-summary mb-6">
                <div class="summary-item common">
                    <span class="summary-icon">
                        @include('backend.partials.icon', ['icon' => 'checkCircle'])
                    </span>
                    <span class="summary-value">{{ count($comparison['common'] ?? []) }}</span>
                    <span class="summary-label">Common Permissions</span>
                </div>

                @foreach($comparison['roles'] ?? [] as $role)
                    <div class="summary-item unique" style="border-color: {{ $role['color'] }};">
                        <span class="summary-icon" style="background-color: {{ $role['color'] }};">
                            @include('backend.partials.icon', ['icon' => 'key'])
                        </span>
                        <span class="summary-value">{{ count($comparison['unique'][$role['id']] ?? []) }}</span>
                        <span class="summary-label">Only in {{ $role['name'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Filter --}}
            <div class="comparison-filter mb-4">
                <select class="filter-select" data-role="view-filter">
                    <option value="all">Show All</option>
                    <option value="common">Common Only</option>
                    <option value="differences">Differences Only</option>
                    @foreach($comparison['roles'] ?? [] as $role)
                        <option value="unique-{{ $role['id'] }}">Only in {{ $role['name'] }}</option>
                    @endforeach
                </select>

                <div class="search-input-wrapper">
                    @include('backend.partials.icon', ['icon' => 'search'])
                    <input type="text"
                           class="search-input"
                           placeholder="Search permissions..."
                           data-role="search">
                </div>
            </div>

            {{-- Common Permissions --}}
            <div class="comparison-section" data-section="common" data-filter="common">
                <div class="section-header">
                    <span class="section-icon common">
                        @include('backend.partials.icon', ['icon' => 'checkCircle'])
                    </span>
                    <span class="section-title">Common Permissions</span>
                    <span class="section-count">{{ count($comparison['common'] ?? []) }}</span>
                    <span class="section-chevron expanded">
                        @include('backend.partials.icon', ['icon' => 'chevronDown'])
                    </span>
                </div>
                <div class="section-content">
                    <div class="permission-grid">
                        @forelse($comparison['common'] ?? [] as $permission)
                            <div class="permission-chip common" data-perm="{{ $permission }}">
                                <span>{{ $permission }}</span>
                            </div>
                        @empty
                            <p class="text-muted">No common permissions found.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Unique Permissions per Role --}}
            @foreach($comparison['roles'] ?? [] as $role)
                <div class="comparison-section" 
                     data-section="unique-{{ $role['id'] }}" 
                     data-filter="differences unique-{{ $role['id'] }}">
                    <div class="section-header">
                        <span class="section-icon" style="background-color: {{ $role['color'] }};">
                            @include('backend.partials.icon', ['icon' => 'key'])
                        </span>
                        <span class="section-title">Only in {{ $role['name'] }}</span>
                        <span class="section-count">{{ count($comparison['unique'][$role['id']] ?? []) }}</span>
                        <span class="section-chevron expanded">
                            @include('backend.partials.icon', ['icon' => 'chevronDown'])
                        </span>
                    </div>
                    <div class="section-content">
                        <div class="permission-grid">
                            @forelse($comparison['unique'][$role['id']] ?? [] as $permission)
                                <div class="permission-chip" style="border-color: {{ $role['color'] }};" data-perm="{{ $permission }}">
                                    <span>{{ $permission }}</span>
                                </div>
                            @empty
                                <p class="text-muted">No unique permissions.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Export/Print Actions --}}
            <div class="comparison-actions mt-6">
                <button type="button" class="btn-secondary" data-action="export">
                    @include('backend.partials.icon', ['icon' => 'download'])
                    Export
                </button>
                <button type="button" class="btn-secondary" onclick="window.print()">
                    @include('backend.partials.icon', ['icon' => 'printer'])
                    Print
                </button>
            </div>
        </div>
    @else
        {{-- No Selection State --}}
        <div class="empty-state" data-role="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'gitCompare'])
            </div>
            <h3>Select Roles to Compare</h3>
            <p>Select at least 2 roles from the list above to see their permission differences.</p>
        </div>
    @endif
</div>

<script>
(function() {
    function initRoleCompare() {
        var container = document.querySelector('.role-compare-page[data-component="role-compare"]');
        if (!container || container.dataset.initialized) return;
        
        var config = JSON.parse(container.dataset.config);
        var selectedRoles = config.roles ? config.roles.map(function(r) { return r.id; }) : [];
        var sections = { common: true };
        
        // Initialize sections
        if (config.comparison && config.comparison.roles) {
            config.comparison.roles.forEach(function(role) {
                sections['unique-' + role.id] = true;
            });
        }
        
        // Role selection
        container.addEventListener('change', function(e) {
            if (e.target.matches('.role-selector-item input[type="checkbox"]')) {
                var roleId = parseInt(e.target.value);
                var index = selectedRoles.indexOf(roleId);
                
                if (index > -1) {
                    selectedRoles.splice(index, 1);
                    e.target.closest('.role-selector-item').classList.remove('selected');
                } else if (selectedRoles.length < 5) {
                    selectedRoles.push(roleId);
                    e.target.closest('.role-selector-item').classList.add('selected');
                } else {
                    e.target.checked = false;
                }
                
                updateUI();
            }
        });
        
        // Compare button
        var compareBtn = container.querySelector('[data-action="compare"]');
        if (compareBtn) {
            compareBtn.addEventListener('click', function() {
                if (selectedRoles.length < 2) return;
                
                var params = new URLSearchParams();
                selectedRoles.forEach(function(id) {
                    params.append('roles[]', id);
                });
                
                window.location.href = container.dataset.compareUrl + '?' + params.toString();
            });
        }
        
        // Section toggles
        container.addEventListener('click', function(e) {
            var header = e.target.closest('.section-header');
            if (header) {
                var section = header.closest('.comparison-section');
                var sectionKey = section.dataset.section;
                sections[sectionKey] = !sections[sectionKey];
                
                var content = section.querySelector('.section-content');
                var chevron = section.querySelector('.section-chevron');
                
                if (content) content.style.display = sections[sectionKey] ? '' : 'none';
                if (chevron) chevron.classList.toggle('expanded', sections[sectionKey]);
            }
        });
        
        // View filter
        var viewFilter = container.querySelector('[data-role="view-filter"]');
        if (viewFilter) {
            viewFilter.addEventListener('change', function() {
                var value = viewFilter.value;
                
                container.querySelectorAll('.comparison-section').forEach(function(section) {
                    var filters = (section.dataset.filter || '').split(' ');
                    var visible = value === 'all' || filters.includes(value);
                    section.style.display = visible ? '' : 'none';
                });
            });
        }
        
        // Search
        var searchInput = container.querySelector('[data-role="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var query = searchInput.value.toLowerCase();
                
                container.querySelectorAll('.permission-chip').forEach(function(chip) {
                    var perm = chip.dataset.perm?.toLowerCase() || '';
                    chip.style.display = !query || perm.includes(query) ? '' : 'none';
                });
            });
        }
        
        // Export
        var exportBtn = container.querySelector('[data-action="export"]');
        if (exportBtn && config.comparison) {
            exportBtn.addEventListener('click', function() {
                var data = {
                    roles: config.comparison.roles.map(function(r) { return r.name; }),
                    common: config.comparison.common,
                    unique: {}
                };
                
                config.comparison.roles.forEach(function(role) {
                    data.unique[role.name] = config.comparison.unique[role.id];
                });
                
                var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'role-comparison.json';
                a.click();
                URL.revokeObjectURL(url);
            });
        }
        
        function updateUI() {
            // Update count text
            var countEl = container.querySelector('[data-count="selected"]');
            if (countEl) {
                countEl.textContent = selectedRoles.length + ' roles selected';
            }
            
            // Update compare button
            if (compareBtn) {
                compareBtn.disabled = selectedRoles.length < 2;
            }
            
            // Update checkbox disabled state
            container.querySelectorAll('.role-selector-item input[type="checkbox"]').forEach(function(cb) {
                var roleId = parseInt(cb.value);
                cb.disabled = !selectedRoles.includes(roleId) && selectedRoles.length >= 5;
            });
        }
        
        container.dataset.initialized = 'true';
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initRoleCompare, 0);
    } else {
        document.addEventListener('DOMContentLoaded', initRoleCompare);
    }
    document.addEventListener('pjax:complete', initRoleCompare);
})();
</script>
@endsection
