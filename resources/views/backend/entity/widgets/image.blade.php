{{-- Image Widget - Image upload --}}

<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

<div class="space-y-3">
    @if($value)
        <div class="relative inline-block">
            <img src="{{ is_string($value) ? asset('storage/' . $value) : $value }}" 
                 alt="Preview" 
                 class="h-24 w-auto rounded-lg border border-gray-200 dark:border-gray-700 object-cover">
        </div>
    @endif
    
    <input type="file"
           id="{{ $name }}"
           name="{{ $name }}"
           class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300"
           accept="{{ $config['accept'] ?? 'image/*' }}"
           {{ $readonly ? 'disabled' : '' }}>
</div>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
