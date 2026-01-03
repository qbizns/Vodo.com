{{-- Binary Widget - File upload field --}}

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

<div class="mt-1">
    @if($value)
        <div class="mb-3 flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ is_string($value) ? basename($value) : 'Uploaded file' }}
                </p>
            </div>
            <button type="button" 
                    class="text-red-500 hover:text-red-700"
                    onclick="clearFile('{{ $name }}')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    @endif
    
    <input type="file"
           id="{{ $name }}"
           name="{{ $name }}"
           class="block w-full text-sm text-gray-500 dark:text-gray-400
                  file:mr-4 file:py-2 file:px-4
                  file:rounded-md file:border-0
                  file:text-sm file:font-semibold
                  file:bg-gray-50 file:text-gray-700
                  dark:file:bg-gray-700 dark:file:text-gray-300
                  hover:file:bg-gray-100 dark:hover:file:bg-gray-600"
           accept="{{ $config['accept'] ?? '*/*' }}"
           {{ $readonly ? 'disabled' : '' }}>
    <input type="hidden" name="{{ $name }}_current" value="{{ $value }}">
</div>

<script>
window.clearFile = window.clearFile || function(name) {
    document.getElementById(name).value = '';
    document.querySelector(`input[name="${name}_current"]`).value = '';
    const preview = document.getElementById(name).closest('.mt-1').querySelector('.mb-3');
    if (preview) preview.remove();
};
</script>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
