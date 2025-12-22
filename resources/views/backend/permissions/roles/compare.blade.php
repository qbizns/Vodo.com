{{-- Role Comparison (Screen 7 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

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
<div class="role-compare-page" x-data="roleCompare(@json($comparison ?? null), @json($roles))">
    {{-- Role Selection --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3>Select Roles to Compare (2-5 roles)</h3>
        </div>
        <div class="card-body">
            <div class="role-selector">
                @foreach($allRoles as $role)
                    <label class="role-selector-item"
                           :class="{ 'selected': isSelected({{ $role->id }}) }">
                        <input type="checkbox"
                               value="{{ $role->id }}"
                               :checked="isSelected({{ $role->id }})"
                               @change="toggleRole({{ $role->id }})"
                               :disabled="!isSelected({{ $role->id }}) && selectedRoles.length >= 5">
                        <div class="role-selector-content">
                            <div class="role-color" style="background-color: {{ $role->color }};"></div>
                            <span class="role-name">{{ $role->name }}</span>
                            <span class="role-perms">{{ $role->permissions_count }} perms</span>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-between items-center mt-4">
                <span class="text-muted" x-text="selectedRoles.length + ' roles selected'"></span>
                <button type="button"
                        class="btn-primary"
                        @click="compare"
                        :disabled="selectedRoles.length < 2">
                    @include('backend.partials.icon', ['icon' => 'gitCompare'])
                    Compare Roles
                </button>
            </div>
        </div>
    </div>

    {{-- Comparison Results --}}
    <template x-if="comparison">
        <div class="comparison-results">
            {{-- Summary Stats --}}
            <div class="comparison-summary mb-6">
                <div class="summary-item common">
                    <span class="summary-icon">
                        @include('backend.partials.icon', ['icon' => 'checkCircle'])
                    </span>
                    <span class="summary-value" x-text="comparison.common.length"></span>
                    <span class="summary-label">Common Permissions</span>
                </div>

                <template x-for="(role, index) in comparison.roles" :key="role.id">
                    <div class="summary-item unique" :style="{ borderColor: role.color }">
                        <span class="summary-icon" :style="{ backgroundColor: role.color }">
                            @include('backend.partials.icon', ['icon' => 'key'])
                        </span>
                        <span class="summary-value" x-text="comparison.unique[role.id]?.length || 0"></span>
                        <span class="summary-label">Only in <span x-text="role.name"></span></span>
                    </div>
                </template>
            </div>

            {{-- Filter --}}
            <div class="comparison-filter mb-4">
                <select class="filter-select" x-model="viewFilter" @change="filterResults">
                    <option value="all">Show All</option>
                    <option value="common">Common Only</option>
                    <option value="differences">Differences Only</option>
                    <template x-for="role in comparison.roles" :key="role.id">
                        <option :value="'unique-' + role.id" x-text="'Only in ' + role.name"></option>
                    </template>
                </select>

                <div class="search-input-wrapper">
                    @include('backend.partials.icon', ['icon' => 'search'])
                    <input type="text"
                           class="search-input"
                           placeholder="Search permissions..."
                           x-model="searchQuery">
                </div>
            </div>

            {{-- Common Permissions --}}
            <div class="comparison-section" x-show="viewFilter === 'all' || viewFilter === 'common'">
                <div class="section-header" @click="toggleSection('common')">
                    <span class="section-icon common">
                        @include('backend.partials.icon', ['icon' => 'checkCircle'])
                    </span>
                    <span class="section-title">Common Permissions</span>
                    <span class="section-count" x-text="filteredCommon.length"></span>
                    <span class="section-chevron" :class="{ 'expanded': sections.common }">
                        @include('backend.partials.icon', ['icon' => 'chevronDown'])
                    </span>
                </div>
                <div class="section-content" x-show="sections.common" x-collapse>
                    <div class="permission-grid">
                        <template x-for="permission in filteredCommon" :key="permission">
                            <div class="permission-chip common">
                                <span x-text="permission"></span>
                            </div>
                        </template>
                    </div>
                    <template x-if="filteredCommon.length === 0">
                        <p class="text-muted">No common permissions found.</p>
                    </template>
                </div>
            </div>

            {{-- Unique Permissions per Role --}}
            <template x-for="role in comparison.roles" :key="'unique-' + role.id">
                <div class="comparison-section"
                     x-show="viewFilter === 'all' || viewFilter === 'differences' || viewFilter === 'unique-' + role.id">
                    <div class="section-header" @click="toggleSection('unique-' + role.id)">
                        <span class="section-icon" :style="{ backgroundColor: role.color }">
                            @include('backend.partials.icon', ['icon' => 'key'])
                        </span>
                        <span class="section-title">Only in <span x-text="role.name"></span></span>
                        <span class="section-count" x-text="getFilteredUnique(role.id).length"></span>
                        <span class="section-chevron" :class="{ 'expanded': sections['unique-' + role.id] }">
                            @include('backend.partials.icon', ['icon' => 'chevronDown'])
                        </span>
                    </div>
                    <div class="section-content" x-show="sections['unique-' + role.id]" x-collapse>
                        <div class="permission-grid">
                            <template x-for="permission in getFilteredUnique(role.id)" :key="permission">
                                <div class="permission-chip" :style="{ borderColor: role.color }">
                                    <span x-text="permission"></span>
                                </div>
                            </template>
                        </div>
                        <template x-if="getFilteredUnique(role.id).length === 0">
                            <p class="text-muted">No unique permissions.</p>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Export/Print Actions --}}
            <div class="comparison-actions mt-6">
                <button type="button" class="btn-secondary" @click="exportComparison">
                    @include('backend.partials.icon', ['icon' => 'download'])
                    Export
                </button>
                <button type="button" class="btn-secondary" @click="window.print()">
                    @include('backend.partials.icon', ['icon' => 'printer'])
                    Print
                </button>
            </div>
        </div>
    </template>

    {{-- No Selection State --}}
    <template x-if="!comparison && selectedRoles.length < 2">
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'gitCompare'])
            </div>
            <h3>Select Roles to Compare</h3>
            <p>Select at least 2 roles from the list above to see their permission differences.</p>
        </div>
    </template>
</div>

<script>
function roleCompare(initialComparison, initialRoles) {
    return {
        comparison: initialComparison,
        roles: initialRoles || [],
        selectedRoles: initialRoles ? initialRoles.map(r => r.id) : [],
        viewFilter: 'all',
        searchQuery: '',
        sections: {
            common: true
        },

        init() {
            // Initialize unique sections as expanded
            if (this.comparison) {
                this.comparison.roles.forEach(role => {
                    this.sections['unique-' + role.id] = true;
                });
            }
        },

        isSelected(roleId) {
            return this.selectedRoles.includes(roleId);
        },

        toggleRole(roleId) {
            const index = this.selectedRoles.indexOf(roleId);
            if (index > -1) {
                this.selectedRoles.splice(index, 1);
            } else if (this.selectedRoles.length < 5) {
                this.selectedRoles.push(roleId);
            }
        },

        async compare() {
            if (this.selectedRoles.length < 2) return;

            const params = new URLSearchParams();
            this.selectedRoles.forEach(id => params.append('roles[]', id));

            const url = `{{ route('admin.roles.compare') }}?${params.toString()}`;
            Vodo.pjax.load(url);
        },

        toggleSection(key) {
            this.sections[key] = !this.sections[key];
        },

        get filteredCommon() {
            if (!this.comparison) return [];
            let perms = this.comparison.common;
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                perms = perms.filter(p => p.toLowerCase().includes(query));
            }
            return perms;
        },

        getFilteredUnique(roleId) {
            if (!this.comparison || !this.comparison.unique[roleId]) return [];
            let perms = this.comparison.unique[roleId];
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                perms = perms.filter(p => p.toLowerCase().includes(query));
            }
            return perms;
        },

        filterResults() {
            // Filter is handled by x-show directives
        },

        exportComparison() {
            const data = {
                roles: this.comparison.roles.map(r => r.name),
                common: this.comparison.common,
                unique: {}
            };

            this.comparison.roles.forEach(role => {
                data.unique[role.name] = this.comparison.unique[role.id];
            });

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'role-comparison.json';
            a.click();
            URL.revokeObjectURL(url);
        }
    };
}
</script>
@endsection
