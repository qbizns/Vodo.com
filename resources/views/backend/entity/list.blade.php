{{--
    Dynamic Entity List Template
    
    Renders list/table views dynamically from ViewRegistry list definitions.
    No manual blade files needed per entity - this template handles all entities.
    
    Variables:
    - $entity: EntityDefinition model
    - $entityName: string
    - $viewDefinition: array - list view definition from ViewRegistry
    - $records: LengthAwarePaginator|Collection
    - $columns: array - column configurations
    - $actions: array - available actions
    - $filters: array - current filter values
    - $pageTitle: string
    - $createUrl: string
    - $apiUrl: string
    - $indexUrl: string
    - $editUrlBase: string
--}}

@extends('backend.layouts.pjax')

@section('title', $pageTitle)
@section('page-id', "entities/{$entityName}")

@section('header', $pageTitle)

@section('header-actions')
<div class="flex items-center gap-3">
    @if(in_array('create', $actions ?? []) || in_array('add', $actions ?? []))
        <a href="{{ $createUrl }}" class="btn-primary flex items-center gap-2">
            @include('backend.partials.icon', ['icon' => 'plus'])
            <span>Create {{ $entity?->getSingularLabel() ?? 'New' }}</span>
        </a>
    @endif
</div>
@endsection

@section('content')
<div data-entity="{{ $entityName }}" data-api-url="{{ $apiUrl }}">
    
    {{-- Filters & Search Bar --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-4">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between gap-4">
                {{-- Search --}}
                <div class="flex items-center gap-3 flex-1">
                    <div class="relative flex-1 max-w-md">
                        <input type="text" 
                               id="searchInput"
                               class="w-full pl-10 pr-4 h-10 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 text-sm" 
                               placeholder="Search {{ $entity?->getPluralLabel() ?? 'records' }}..."
                               value="{{ $filters['search'] ?? '' }}">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            @include('backend.partials.icon', ['icon' => 'search'])
                        </span>
                    </div>

                    {{-- Status Filter --}}
                    <select id="statusFilter" class="h-10 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 text-sm">
                        <option value="">All Status</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                {{-- Bulk Actions --}}
                <div class="hidden items-center gap-2" id="bulkActions">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <span id="selectedCount">0</span> selected
                    </span>
                    <button type="button" class="btn-danger text-sm px-3 py-1.5" onclick="bulkDelete()">
                        @include('backend.partials.icon', ['icon' => 'trash'])
                        Delete
                    </button>
                </div>

                {{-- View Options --}}
                <div class="flex items-center gap-2">
                    <button type="button" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" onclick="refreshList()">
                        @include('backend.partials.icon', ['icon' => 'refreshCw'])
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="entityTable">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        {{-- Checkbox Column --}}
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        </th>

                        {{-- Dynamic Columns --}}
                        @forelse($columns as $colKey => $column)
                            @php
                                $colName = is_array($column) ? ($column['name'] ?? $colKey) : $column;
                                $colLabel = is_array($column) ? ($column['label'] ?? ucfirst(str_replace('_', ' ', $colName))) : ucfirst(str_replace('_', ' ', $column));
                                $sortable = is_array($column) ? ($column['sortable'] ?? true) : true;
                            @endphp
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider {{ $sortable ? 'cursor-pointer hover:text-gray-700 dark:hover:text-gray-200' : '' }}"
                                @if($sortable) onclick="sortByColumn('{{ $colName }}')" @endif>
                                <div class="flex items-center gap-1">
                                    {{ $colLabel }}
                                    @if($sortable)
                                        <span class="text-gray-400">
                                            @include('backend.partials.icon', ['icon' => 'chevronDown', 'class' => 'w-3 h-3'])
                                        </span>
                                    @endif
                                </div>
                            </th>
                        @empty
                            {{-- Default columns if none specified --}}
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer" onclick="sortByColumn('name')">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer" onclick="sortByColumn('created_at')">Created</th>
                        @endforelse

                        {{-- Actions Column --}}
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($records as $record)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            {{-- Checkbox --}}
                            <td class="px-4 py-3">
                                <input type="checkbox" class="row-checkbox rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" value="{{ $record->id }}" onchange="updateBulkActions()">
                            </td>

                            {{-- Dynamic Column Values --}}
                            @forelse($columns as $colKey => $column)
                                @php
                                    $colName = is_array($column) ? ($column['name'] ?? $colKey) : $column;
                                    $colType = is_array($column) ? ($column['type'] ?? 'text') : 'text';
                                    $value = data_get($record, $colName);
                                @endphp
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                    @if($colType === 'boolean' || $colType === 'checkbox')
                                        @if($value)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Yes
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                No
                                            </span>
                                        @endif
                                    @elseif($colType === 'date')
                                        {{ $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : '-' }}
                                    @elseif($colType === 'datetime')
                                        {{ $value ? \Carbon\Carbon::parse($value)->format('M d, Y H:i') : '-' }}
                                    @elseif($colType === 'money' || $colType === 'monetary')
                                        {{ $value ? number_format((float)$value, 2) : '-' }}
                                    @elseif($colType === 'image')
                                        @if($value)
                                            <img src="{{ asset('storage/' . $value) }}" alt="" class="h-8 w-8 rounded object-cover">
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    @else
                                        {{ Str::limit($value ?? '-', 50) }}
                                    @endif
                                </td>
                            @empty
                                {{-- Default columns --}}
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $record->name ?? $record->title ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $record->created_at?->format('M d, Y') ?? '-' }}</td>
                            @endforelse

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button type="button" class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" onclick="toggleActionsMenu(this)">
                                        @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                    </button>
                                    <div class="actions-menu hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-10">
                                        <div class="py-1">
                                            @if(in_array('view', $actions ?? []) || in_array('show', $actions ?? []))
                                                <a href="{{ isset($editUrlBase) ? "{$editUrlBase}/{$record->id}" : route('admin.entities.show', [$entityName, $record->id]) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    @include('backend.partials.icon', ['icon' => 'eye'])
                                                    <span>View</span>
                                                </a>
                                            @endif
                                            
                                            @if(in_array('edit', $actions ?? []))
                                                <a href="{{ isset($editUrlBase) ? "{$editUrlBase}/{$record->id}/edit" : route('admin.entities.edit', [$entityName, $record->id]) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    @include('backend.partials.icon', ['icon' => 'edit'])
                                                    <span>Edit</span>
                                                </a>
                                            @endif

                                            @if(in_array('duplicate', $actions ?? []))
                                                <button type="button" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600" onclick="duplicateRecord({{ $record->id }})">
                                                    @include('backend.partials.icon', ['icon' => 'copy'])
                                                    <span>Duplicate</span>
                                                </button>
                                            @endif

                                            @if(in_array('delete', $actions ?? []))
                                                <button type="button" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600" onclick="deleteRecord({{ $record->id }})">
                                                    @include('backend.partials.icon', ['icon' => 'trash'])
                                                    <span>Delete</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 2 }}" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600">
                                        @include('backend.partials.icon', ['icon' => 'inbox'])
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No {{ $entity?->getPluralLabel() ?? 'records' }} found</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by creating a new one.</p>
                                    @if(in_array('create', $actions ?? []))
                                        <a href="{{ $createUrl }}" class="btn-primary">
                                            @include('backend.partials.icon', ['icon' => 'plus'])
                                            <span>Create {{ $entity?->getSingularLabel() ?? 'New' }}</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ $records->firstItem() }} to {{ $records->lastItem() }} of {{ $records->total() }} results
                    </div>
                    <div class="flex items-center gap-2">
                        @if($records->onFirstPage())
                            <span class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500">Previous</span>
                        @else
                            <a href="{{ $records->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Previous</a>
                        @endif
                        
                        @foreach($records->getUrlRange(max(1, $records->currentPage() - 2), min($records->lastPage(), $records->currentPage() + 2)) as $page => $url)
                            @if($page == $records->currentPage())
                                <span class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($records->hasMorePages())
                            <a href="{{ $records->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Next</a>
                        @else
                            <span class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500">Next</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
(function() {
    const apiUrl = '{{ $apiUrl }}';
    const indexUrl = '{{ $indexUrl ?? route("admin.entities.index", $entityName) }}';
    const editUrlBase = '{{ $editUrlBase ?? "" }}';

    // Search on Enter
    document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });

    // Status filter change
    document.getElementById('statusFilter')?.addEventListener('change', function() {
        applyFilters();
    });

    // Apply filters
    window.applyFilters = function() {
        const search = document.getElementById('searchInput')?.value || '';
        const status = document.getElementById('statusFilter')?.value || '';
        
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (status) params.set('status', status);
        
        const url = indexUrl + (params.toString() ? '?' + params.toString() : '');
        Vodo.pjax.load(url);
    };

    // Refresh list
    window.refreshList = function() {
        Vodo.pjax.reload();
    };

    // Sort by column
    window.sortByColumn = function(column) {
        const currentSort = new URLSearchParams(window.location.search).get('sort') || '';
        let newSort = column + ' asc';
        
        if (currentSort === column + ' asc') {
            newSort = column + ' desc';
        } else if (currentSort === column + ' desc') {
            newSort = '';
        }
        
        const params = new URLSearchParams(window.location.search);
        if (newSort) {
            params.set('sort', newSort);
        } else {
            params.delete('sort');
        }
        
        Vodo.pjax.load(indexUrl + '?' + params.toString());
    };

    // Toggle select all
    window.toggleSelectAll = function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkActions();
    };

    // Update bulk actions visibility
    window.updateBulkActions = function() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        if (checked.length > 0) {
            bulkActions?.classList.remove('hidden');
            bulkActions?.classList.add('flex');
            if (selectedCount) selectedCount.textContent = checked.length;
        } else {
            bulkActions?.classList.add('hidden');
            bulkActions?.classList.remove('flex');
        }
    };

    // Delete single record
    window.deleteRecord = function(id) {
        Vodo.modal.confirm({
            title: 'Delete Record',
            message: 'Are you sure you want to delete this record? This action cannot be undone.',
            confirmText: 'Delete',
            confirmClass: 'btn-danger',
            onConfirm: function() {
                Vodo.api.delete(apiUrl + '/' + id)
                    .then(response => {
                        if (response.success) {
                            Vodo.notification.success(response.message || 'Deleted successfully');
                            Vodo.pjax.reload();
                        }
                    })
                    .catch(error => {
                        Vodo.notification.error(error.message || 'Failed to delete');
                    });
            }
        });
    };

    // Bulk delete
    window.bulkDelete = function() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        
        if (ids.length === 0) return;
        
        Vodo.modal.confirm({
            title: 'Delete Multiple Records',
            message: `Are you sure you want to delete ${ids.length} record(s)? This action cannot be undone.`,
            confirmText: 'Delete All',
            confirmClass: 'btn-danger',
            onConfirm: function() {
                Vodo.api.post(apiUrl + '/bulk', { action: 'delete', ids: ids })
                    .then(response => {
                        if (response.success) {
                            Vodo.notification.success(response.message || 'Records deleted successfully');
                            Vodo.pjax.reload();
                        }
                    })
                    .catch(error => {
                        Vodo.notification.error(error.message || 'Failed to delete records');
                    });
            }
        });
    };

    // Duplicate record
    window.duplicateRecord = function(id) {
        Vodo.api.post(apiUrl + '/' + id + '/duplicate')
            .then(response => {
                if (response.success) {
                    Vodo.notification.success(response.message || 'Duplicated successfully');
                    if (response.data?.id) {
                        if (editUrlBase) {
                            Vodo.pjax.load(editUrlBase + '/' + response.data.id + '/edit');
                        } else {
                            Vodo.pjax.reload();
                        }
                    } else {
                        Vodo.pjax.reload();
                    }
                }
            })
            .catch(error => {
                Vodo.notification.error(error.message || 'Failed to duplicate');
            });
    };

    // Toggle actions menu
    window.toggleActionsMenu = function(button) {
        // Close all other menus first
        document.querySelectorAll('.actions-menu').forEach(menu => {
            if (menu !== button.nextElementSibling) {
                menu.classList.add('hidden');
            }
        });
        
        const menu = button.nextElementSibling;
        menu?.classList.toggle('hidden');
    };

    // Close menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.relative')) {
            document.querySelectorAll('.actions-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });
})();
</script>
@endsection
