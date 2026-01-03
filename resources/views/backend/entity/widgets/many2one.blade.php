{{-- Many2One Widget - Related record selector --}}

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

@php
    $relation = $config['relation'] ?? null;
    $options = $config['options'] ?? [];
    $entityName = $config['entity'] ?? null;
    
    // For admin context, bypass all scoping to show all options
    $isAdminContext = request()->is('admin/*') || request()->is('plugins/*');
    
    // If options not provided, try to load them dynamically
    if (empty($options)) {
        try {
            $modelClass = $config['model'] ?? null;
            
            // If no model class but entity name is provided, resolve from EntityDefinition
            if (!$modelClass && $entityName) {
                $relatedEntity = \App\Models\EntityDefinition::where('name', $entityName)->first();
                if ($relatedEntity) {
                    $entityConfig = is_string($relatedEntity->config) ? json_decode($relatedEntity->config, true) : ($relatedEntity->config ?? []);
                    $modelClass = $entityConfig['model_class'] ?? null;
                }
            }
            
            if ($modelClass && class_exists($modelClass)) {
                $displayField = $config['display_field'] ?? 'name';
                
                // In admin context, bypass all global scopes (tenant, store, etc.)
                if ($isAdminContext) {
                    $options = $modelClass::withoutGlobalScopes()->pluck($displayField, 'id')->toArray();
                } else {
                    $options = $modelClass::pluck($displayField, 'id')->toArray();
                }
            }
        } catch (\Exception $e) {
            // Options will remain empty on error
        }
    }
@endphp

<select id="{{ $name }}"
        name="{{ $name }}"
        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm h-10 px-3"
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'disabled' : '' }}>
    <option value="">{{ $field['placeholder'] ?? '-- Select --' }}</option>
    @foreach($options as $optValue => $optLabel)
        <option value="{{ $optValue }}" {{ old($name, $value) == $optValue ? 'selected' : '' }}>
            {{ $optLabel }}
        </option>
    @endforeach
</select>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
