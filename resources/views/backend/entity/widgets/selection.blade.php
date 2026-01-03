{{-- Selection Widget - Dropdown --}}
@php
    // Resolve options from multiple possible sources
    $selectOptions = $field['options'] ?? $config['options'] ?? [];
    
    // If still empty, try config inside field
    if (empty($selectOptions) && isset($field['config']['options'])) {
        $selectOptions = $field['config']['options'];
    }
@endphp

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

<select id="{{ $name }}"
        name="{{ $name }}"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'disabled' : '' }}>
    <option value="">{{ $field['placeholder'] ?? '-- Select --' }}</option>
    @foreach($selectOptions as $optValue => $optLabel)
        <option value="{{ $optValue }}" {{ old($name, $value) == $optValue ? 'selected' : '' }}>
            {{ is_array($optLabel) ? ($optLabel['label'] ?? $optValue) : $optLabel }}
        </option>
    @endforeach
</select>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
