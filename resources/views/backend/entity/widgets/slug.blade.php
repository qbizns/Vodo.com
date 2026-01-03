{{-- Slug Widget - URL-friendly identifier field --}}

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

<div class="mt-1 flex rounded-md shadow-sm">
    <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 px-3 text-gray-500 dark:text-gray-300 sm:text-sm">
        {{ $config['prefix'] ?? '/' }}
    </span>
    <input type="text"
           id="{{ $name }}"
           name="{{ $name }}"
           class="block w-full flex-1 rounded-none rounded-r-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm h-10 px-3"
           value="{{ old($name, $value) }}"
           placeholder="{{ $field['placeholder'] ?? 'url-friendly-slug' }}"
           pattern="[a-z0-9-]+"
           {{ $required ? 'required' : '' }}
           {{ $readonly ? 'readonly' : '' }}>
</div>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
