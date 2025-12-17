# Entity & Data Management - UI Components

## Core Components

### Dynamic Data Table

```blade
{{-- resources/views/components/entity/data-table.blade.php --}}
@props(['entity', 'records', 'fields'])

<div x-data="dataTable(@js($records->toArray()))" class="data-table">
    <div class="data-table__header">
        <input type="text" x-model="search" placeholder="Search..." class="data-table__search">
        <div class="data-table__actions">
            <button @click="$dispatch('open-filters')">Filters</button>
            <button @click="$dispatch('open-columns')">Columns</button>
        </div>
    </div>

    <table class="data-table__table">
        <thead>
            <tr>
                <th><input type="checkbox" @change="toggleAll($event)"></th>
                @foreach($fields as $field)
                    @if($field->show_in_list)
                    <th @click="sort('{{ $field->key }}')" class="cursor-pointer">
                        {{ $field->label }}
                        <span x-show="sortBy === '{{ $field->key }}'">
                            <template x-if="sortDir === 'asc'">↑</template>
                            <template x-if="sortDir === 'desc'">↓</template>
                        </span>
                    </th>
                    @endif
                @endforeach
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                <td><input type="checkbox" value="{{ $record->id }}" x-model="selected"></td>
                @foreach($fields as $field)
                    @if($field->show_in_list)
                    <td>
                        <x-entity.field-display :field="$field" :value="$record->{$field->key}" />
                    </td>
                    @endif
                @endforeach
                <td>
                    <x-entity.row-actions :entity="$entity" :record="$record" />
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="data-table__footer">
        <span x-text="selected.length + ' selected'"></span>
        {{ $records->links() }}
    </div>
</div>
```

### Field Display Component

```blade
{{-- resources/views/components/entity/field-display.blade.php --}}
@props(['field', 'value'])

@switch($field->type)
    @case('currency')
        <span class="font-mono">${{ number_format($value, 2) }}</span>
        @break
    @case('date')
        <span>{{ $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : '-' }}</span>
        @break
    @case('select')
        @php $options = $field->getOptions(); @endphp
        <span class="badge">{{ $options[$value] ?? $value }}</span>
        @break
    @case('boolean')
        <span class="badge {{ $value ? 'badge-green' : 'badge-gray' }}">
            {{ $value ? 'Yes' : 'No' }}
        </span>
        @break
    @case('relation')
        @if($value)
            <a href="{{ route('admin.' . $field->config['entity'] . '.show', $value->id) }}">
                {{ $value->{$field->config['display_field'] ?? 'name'} }}
            </a>
        @endif
        @break
    @case('image')
        @if($value)
            <img src="{{ Storage::url($value) }}" class="w-10 h-10 rounded object-cover">
        @endif
        @break
    @default
        <span>{{ $value ?? '-' }}</span>
@endswitch
```

### Dynamic Form

```blade
{{-- resources/views/components/entity/dynamic-form.blade.php --}}
@props(['entity', 'record' => null, 'fields'])

<form x-data="dynamicForm(@js($record?->toArray() ?? []))"
      @submit.prevent="submit()"
      action="{{ $record ? route('admin.'.$entity->slug.'.update', $record) : route('admin.'.$entity->slug.'.store') }}"
      method="POST">
    @csrf
    @if($record) @method('PUT') @endif

    <div class="form-grid">
        @foreach($fields as $field)
            @if($field->show_in_form)
            <div class="form-group {{ $field->config['width'] ?? 'col-span-1' }}">
                <label for="{{ $field->key }}">
                    {{ $field->label }}
                    @if($field->is_required)<span class="text-red-500">*</span>@endif
                </label>
                
                <x-entity.field-input :field="$field" :value="$record?->{$field->key}" />
                
                @error($field->key)
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>
            @endif
        @endforeach
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.'.$entity->slug.'.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            {{ $record ? 'Update' : 'Create' }} {{ $entity->name }}
        </button>
    </div>
</form>
```

### Relation Picker

```blade
{{-- resources/views/components/entity/relation-picker.blade.php --}}
@props(['field', 'value' => null, 'name'])

@php
    $relatedEntity = $field->config['entity'];
    $displayField = $field->config['display_field'] ?? 'name';
    $multiple = $field->type === 'belongs_to_many';
@endphp

<div x-data="relationPicker({
    value: @js($value),
    endpoint: '/api/v1/admin/entities/{{ $relatedEntity }}',
    displayField: '{{ $displayField }}',
    multiple: {{ $multiple ? 'true' : 'false' }}
})" class="relation-picker">
    
    <div class="relation-picker__selected">
        <template x-if="!multiple && selectedItem">
            <div class="relation-picker__item">
                <span x-text="selectedItem[displayField]"></span>
                <button @click="clear()" type="button">&times;</button>
            </div>
        </template>
        
        <template x-if="multiple">
            <template x-for="item in selectedItems" :key="item.id">
                <div class="relation-picker__tag">
                    <span x-text="item[displayField]"></span>
                    <button @click="remove(item.id)" type="button">&times;</button>
                </div>
            </template>
        </template>
    </div>

    <input type="text" 
           x-model="search"
           @focus="open = true"
           @input.debounce.300ms="fetchOptions()"
           placeholder="Search {{ $relatedEntity }}..."
           class="relation-picker__input">

    <div x-show="open" @click.away="open = false" class="relation-picker__dropdown">
        <template x-for="option in options" :key="option.id">
            <div @click="select(option)" class="relation-picker__option">
                <span x-text="option[displayField]"></span>
            </div>
        </template>
        <div x-show="options.length === 0" class="relation-picker__empty">
            No results found
        </div>
    </div>

    <input type="hidden" :name="name" :value="JSON.stringify(multiple ? selectedItems.map(i => i.id) : selectedItem?.id)">
</div>
```

### Import Wizard

```blade
{{-- resources/views/components/entity/import-wizard.blade.php --}}
@props(['entity', 'fields'])

<div x-data="importWizard()" class="import-wizard">
    <div class="import-wizard__steps">
        <div :class="{ 'active': step === 1 }">1. Upload</div>
        <div :class="{ 'active': step === 2 }">2. Map Fields</div>
        <div :class="{ 'active': step === 3 }">3. Validate</div>
        <div :class="{ 'active': step === 4 }">4. Import</div>
    </div>

    {{-- Step 1: Upload --}}
    <div x-show="step === 1">
        <input type="file" @change="handleFile($event)" accept=".csv,.xlsx,.json">
        <p>Supported formats: CSV, Excel, JSON</p>
    </div>

    {{-- Step 2: Mapping --}}
    <div x-show="step === 2">
        <table>
            <thead>
                <tr>
                    <th>File Column</th>
                    <th>Maps To</th>
                    <th>Sample</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="col in fileColumns" :key="col">
                    <tr>
                        <td x-text="col"></td>
                        <td>
                            <select x-model="mapping[col]">
                                <option value="">-- Skip --</option>
                                @foreach($fields as $field)
                                    <option value="{{ $field->key }}">{{ $field->label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td x-text="getSample(col)"></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <button @click="validate()" class="btn btn-primary">Validate</button>
    </div>

    {{-- Step 3: Validation --}}
    <div x-show="step === 3">
        <div x-show="errors.length > 0" class="alert alert-danger">
            <p>Found <span x-text="errors.length"></span> errors:</p>
            <ul>
                <template x-for="error in errors.slice(0, 10)">
                    <li x-text="error"></li>
                </template>
            </ul>
        </div>
        <div x-show="errors.length === 0" class="alert alert-success">
            All <span x-text="validRows"></span> rows are valid
        </div>
        <button @click="startImport()" :disabled="errors.length > 0" class="btn btn-primary">
            Start Import
        </button>
    </div>

    {{-- Step 4: Progress --}}
    <div x-show="step === 4">
        <div class="progress-bar">
            <div :style="{ width: progress + '%' }"></div>
        </div>
        <p><span x-text="imported"></span> / <span x-text="total"></span> imported</p>
    </div>
</div>
```
