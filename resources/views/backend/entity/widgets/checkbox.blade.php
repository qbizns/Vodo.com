{{-- Checkbox Widget - Boolean toggle --}}

<label class="flex items-center gap-3 cursor-pointer">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox"
           id="{{ $name }}"
           name="{{ $name }}"
           value="1"
           class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-800"
           {{ old($name, $value) ? 'checked' : '' }}
           {{ $readonly ? 'disabled' : '' }}>
    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
        @if($required)<span class="text-red-500">*</span>@endif
    </span>
</label>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
