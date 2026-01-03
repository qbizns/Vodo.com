{{-- Color Widget - Color picker field --}}

<label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
    @if($required)<span class="text-red-500">*</span>@endif
</label>

<div class="mt-1 flex items-center gap-3">
    <input type="color"
           id="{{ $name }}"
           name="{{ $name }}"
           class="h-10 w-14 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
           value="{{ old($name, $value ?? '#000000') }}"
           {{ $readonly ? 'disabled' : '' }}>
    <input type="text"
           id="{{ $name }}_hex"
           class="block w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm h-10 px-3"
           value="{{ old($name, $value ?? '#000000') }}"
           pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
           placeholder="#000000"
           {{ $readonly ? 'readonly' : '' }}>
</div>

<script>
(function() {
    const colorPicker = document.getElementById('{{ $name }}');
    const hexInput = document.getElementById('{{ $name }}_hex');
    
    colorPicker.addEventListener('input', function() {
        hexInput.value = this.value;
    });
    
    hexInput.addEventListener('input', function() {
        if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(this.value)) {
            colorPicker.value = this.value;
        }
    });
})();
</script>

@if($field['help'] ?? false)
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $field['help'] }}</p>
@endif

@error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
