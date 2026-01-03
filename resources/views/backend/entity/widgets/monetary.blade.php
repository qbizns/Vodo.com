{{-- Monetary Widget - Currency amount field --}}

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

<div class="mt-1 relative rounded-md shadow-sm">
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <span class="text-gray-500 dark:text-gray-400 sm:text-sm">{{ $config['currency'] ?? '$' }}</span>
    </div>
    <input type="number"
           id="{{ $name }}"
           name="{{ $name }}"
           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white pl-7 pr-12 focus:border-blue-500 focus:ring-blue-500 sm:text-sm h-10"
           value="{{ old($name, $value) }}"
           placeholder="0.00"
           step="0.01"
           min="0"
           {{ $required ? 'required' : '' }}
           {{ $readonly ? 'readonly' : '' }}>
    @if($config['currency_code'] ?? false)
    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
        <span class="text-gray-500 dark:text-gray-400 sm:text-sm">{{ $config['currency_code'] }}</span>
    </div>
    @endif
</div>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
