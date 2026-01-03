{{-- Phone Widget - Phone number input field --}}

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

<input type="tel"
       id="{{ $name }}"
       name="{{ $name }}"
       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm h-10 px-3"
       value="{{ old($name, $value) }}"
       placeholder="{{ $field['placeholder'] ?? '+1 (555) 000-0000' }}"
       {{ $required ? 'required' : '' }}
       {{ $readonly ? 'readonly' : '' }}>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
