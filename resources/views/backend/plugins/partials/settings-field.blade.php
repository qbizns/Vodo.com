{{-- Dynamic Settings Field Component --}}
@php
$fieldId = str_replace('.', '_', $field['key']);
$fieldName = $field['key'];
$fieldType = $field['type'] ?? 'text';
$fieldValue = $value;
$isRequired = !empty($field['rules']) && str_contains($field['rules'], 'required');
@endphp

<div class="settings-field" 
     @if(!empty($field['condition']))
     x-data="{ show: true }"
     x-show="show"
     @endif
>
    <label for="{{ $fieldId }}" class="settings-label">
        {{ $field['label'] }}
        @if($isRequired)
            <span class="required">*</span>
        @endif
    </label>

    @switch($fieldType)
        @case('text')
        @case('email')
        @case('url')
            <input type="{{ $fieldType }}" 
                   id="{{ $fieldId }}" 
                   name="{{ $fieldName }}"
                   value="{{ old($fieldName, $fieldValue) }}"
                   placeholder="{{ $field['placeholder'] ?? '' }}"
                   class="settings-input @error($fieldName) is-invalid @enderror"
                   @if(!empty($field['min'])) min="{{ $field['min'] }}" @endif
                   @if(!empty($field['max'])) max="{{ $field['max'] }}" @endif
                   @if($isRequired) required @endif>
            @break

        @case('number')
            <input type="number" 
                   id="{{ $fieldId }}" 
                   name="{{ $fieldName }}"
                   value="{{ old($fieldName, $fieldValue) }}"
                   placeholder="{{ $field['placeholder'] ?? '' }}"
                   class="settings-input @error($fieldName) is-invalid @enderror"
                   @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                   @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                   @if(!empty($field['step'])) step="{{ $field['step'] }}" @endif
                   @if($isRequired) required @endif>
            @break

        @case('textarea')
            <textarea id="{{ $fieldId }}" 
                      name="{{ $fieldName }}"
                      rows="{{ $field['rows'] ?? 5 }}"
                      placeholder="{{ $field['placeholder'] ?? '' }}"
                      class="settings-textarea @error($fieldName) is-invalid @enderror"
                      @if($isRequired) required @endif>{{ old($fieldName, $fieldValue) }}</textarea>
            @break

        @case('select')
            <select id="{{ $fieldId }}" 
                    name="{{ $fieldName }}"
                    class="settings-select @error($fieldName) is-invalid @enderror"
                    @if($isRequired) required @endif>
                @if(!empty($field['placeholder']))
                    <option value="">{{ $field['placeholder'] }}</option>
                @endif
                @php
                    $options = is_callable($field['options']) ? call_user_func($field['options']) : ($field['options'] ?? []);
                @endphp
                @foreach($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" {{ (string)$fieldValue === (string)$optionValue ? 'selected' : '' }}>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
            @break

        @case('checkbox')
            <label class="settings-checkbox">
                <input type="hidden" name="{{ $fieldName }}" value="0">
                <input type="checkbox" 
                       id="{{ $fieldId }}" 
                       name="{{ $fieldName }}"
                       value="1"
                       {{ $fieldValue ? 'checked' : '' }}>
                <span class="checkbox-label">{{ $field['checkbox_label'] ?? '' }}</span>
            </label>
            @break

        @case('radio')
            <div class="settings-radio-group">
                @php
                    $options = is_callable($field['options']) ? call_user_func($field['options']) : ($field['options'] ?? []);
                @endphp
                @foreach($options as $optionValue => $optionLabel)
                    <label class="settings-radio">
                        <input type="radio" 
                               name="{{ $fieldName }}"
                               value="{{ $optionValue }}"
                               {{ (string)$fieldValue === (string)$optionValue ? 'checked' : '' }}>
                        <span class="radio-label">{{ $optionLabel }}</span>
                    </label>
                @endforeach
            </div>
            @break

        @case('toggle')
            <label class="settings-toggle">
                <input type="hidden" name="{{ $fieldName }}" value="0">
                <input type="checkbox" 
                       id="{{ $fieldId }}" 
                       name="{{ $fieldName }}"
                       value="1"
                       {{ $fieldValue ? 'checked' : '' }}>
                <span class="toggle-slider"></span>
                <span class="toggle-label">{{ $field['toggle_label'] ?? '' }}</span>
            </label>
            @break

        @case('color')
            <div class="settings-color-picker">
                <input type="color" 
                       id="{{ $fieldId }}" 
                       name="{{ $fieldName }}"
                       value="{{ old($fieldName, $fieldValue) }}"
                       class="settings-color-input">
                <input type="text" 
                       id="{{ $fieldId }}_text" 
                       value="{{ old($fieldName, $fieldValue) }}"
                       class="settings-color-text"
                       pattern="^#[0-9A-Fa-f]{6}$"
                       placeholder="#000000">
            </div>
            @break

        @case('group')
            <div class="settings-field-group">
                @foreach($field['fields'] ?? [] as $subfield)
                    @include('backend.plugins.partials.settings-field', [
                        'field' => array_merge($subfield, ['key' => $fieldName . '.' . $subfield['key']]),
                        'value' => is_array($fieldValue) ? ($fieldValue[$subfield['key']] ?? $subfield['default'] ?? null) : ($subfield['default'] ?? null),
                    ])
                @endforeach
            </div>
            @break

        @default
            <input type="text" 
                   id="{{ $fieldId }}" 
                   name="{{ $fieldName }}"
                   value="{{ old($fieldName, $fieldValue) }}"
                   class="settings-input @error($fieldName) is-invalid @enderror">
    @endswitch

    @if(!empty($field['hint']))
        <p class="settings-hint">{{ $field['hint'] }}</p>
    @endif

    @error($fieldName)
        <p class="settings-error">{{ $message }}</p>
    @enderror
</div>
