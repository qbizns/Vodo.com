{{-- JSON Widget - JSON editor --}}

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

@php
    $jsonValue = $value;
    if (is_array($jsonValue)) {
        $jsonValue = json_encode($jsonValue, JSON_PRETTY_PRINT);
    } elseif (is_string($jsonValue) && !empty($jsonValue)) {
        $decoded = json_decode($jsonValue, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $jsonValue = json_encode($decoded, JSON_PRETTY_PRINT);
        }
    }
@endphp

<textarea id="{{ $name }}"
          name="{{ $name }}"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
          rows="{{ $config['rows'] ?? 6 }}"
          placeholder='{"key": "value"}'
          {{ $required ? 'required' : '' }}
          {{ $readonly ? 'readonly' : '' }}>{{ old($name, $jsonValue) }}</textarea>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
