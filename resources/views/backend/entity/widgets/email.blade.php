{{-- Email Widget --}}

<label for="{{ $name }}" class="form-label {{ $required ? 'required' : '' }}">
    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}
</label>

<input type="email"
       id="{{ $name }}"
       name="{{ $name }}"
       class="form-input"
       value="{{ old($name, $value) }}"
       placeholder="{{ $field['placeholder'] ?? 'email@example.com' }}"
       {{ $required ? 'required' : '' }}
       {{ $readonly ? 'readonly' : '' }}>

@if($field['help'] ?? false)
    <span class="form-hint">{{ $field['help'] }}</span>
@endif

@error($name)
    <span class="form-error">{{ $message }}</span>
@enderror
