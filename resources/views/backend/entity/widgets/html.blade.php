{{-- HTML Widget - Rich text editor --}}

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

<div class="mt-1">
    <textarea id="{{ $name }}"
              name="{{ $name }}"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2"
              rows="{{ $config['rows'] ?? 8 }}"
              placeholder="{{ $field['placeholder'] ?? '' }}"
              {{ $required ? 'required' : '' }}
              {{ $readonly ? 'readonly' : '' }}>{{ old($name, $value) }}</textarea>
</div>

{{-- Note: For a full rich text editor, integrate TinyMCE, CKEditor, or Trix here --}}

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
