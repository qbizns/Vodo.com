{{-- Plugin Settings Partial --}}

<div class="settings-section">
    <div class="settings-section-header" style="display: none !important; visibility: hidden;">
        <h2>{{ $name ?? ucfirst(str_replace('-', ' ', $slug)) }} {{ __t('common.settings') }}</h2>
        <p>{{ __t('settings.no_configurable_settings') }}</p>
    </div>

    @if(empty($fields))
        <div class="settings-empty">
            <div class="settings-empty-icon">
                @include('backend.partials.icon', ['icon' => 'settings'])
            </div>
            <h3>{{ __t('settings.no_settings_available') }}</h3>
            <p>{{ __t('settings.no_configurable_settings') }}</p>
        </div>
    @else
        <form action="{{ $saveUrl }}" method="POST" class="settings-form" id="pluginSettingsForm">
            @csrf

            @foreach($fields as $groupKey => $group)
                <div class="settings-card">
                    @if(isset($group['title']))
                        <div class="settings-card-header">
                            <h3>{{ $group['title'] }}</h3>
                            @if(isset($group['description']))
                                <p>{{ $group['description'] }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="settings-card-body">
                        @if(isset($group['fields']))
                            @foreach($group['fields'] as $fieldKey => $field)
                                <div class="settings-field {{ $field['type'] === 'toggle' ? 'settings-field-toggle' : '' }}">
                                    <div class="settings-field-label">
                                        <label for="{{ $fieldKey }}">{{ $field['label'] }}</label>
                                        @if(isset($field['description']))
                                            <span class="settings-field-description">{{ $field['description'] }}</span>
                                        @endif
                                    </div>

                                    <div class="settings-field-input">
                                        @php
                                            $value = $values[$fieldKey] ?? $field['default'] ?? '';
                                        @endphp

                                        @switch($field['type'])
                                            @case('text')
                                            @case('email')
                                                <input 
                                                    type="{{ $field['type'] }}" 
                                                    name="{{ $fieldKey }}" 
                                                    id="{{ $fieldKey }}" 
                                                    value="{{ $value }}"
                                                    class="settings-input"
                                                    @if(isset($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                                                >
                                                @break

                                            @case('number')
                                                <input 
                                                    type="number" 
                                                    name="{{ $fieldKey }}" 
                                                    id="{{ $fieldKey }}" 
                                                    value="{{ $value }}"
                                                    class="settings-input settings-input-number"
                                                    @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                                                    @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                                                >
                                                @break

                                            @case('select')
                                                <select name="{{ $fieldKey }}" id="{{ $fieldKey }}" class="settings-select">
                                                    @foreach($field['options'] as $optionKey => $optionLabel)
                                                        <option value="{{ $optionKey }}" {{ $value == $optionKey ? 'selected' : '' }}>
                                                            {{ $optionLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @break

                                            @case('toggle')
                                                <label class="toggle-switch">
                                                    <input 
                                                        type="checkbox" 
                                                        name="{{ $fieldKey }}" 
                                                        id="{{ $fieldKey }}"
                                                        value="1"
                                                        {{ $value ? 'checked' : '' }}
                                                    >
                                                    <span class="toggle-slider"></span>
                                                </label>
                                                @break

                                            @case('textarea')
                                                <textarea 
                                                    name="{{ $fieldKey }}" 
                                                    id="{{ $fieldKey }}" 
                                                    class="settings-textarea"
                                                    rows="3"
                                                >{{ $value }}</textarea>
                                                @break

                                            @default
                                                <input 
                                                    type="text" 
                                                    name="{{ $fieldKey }}" 
                                                    id="{{ $fieldKey }}" 
                                                    value="{{ $value }}"
                                                    class="settings-input"
                                                >
                                        @endswitch
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="settings-actions">
                <button type="submit" class="btn-primary">
                    @include('backend.partials.icon', ['icon' => 'check'])
                    {{ __t('settings.save_settings') }}
                </button>
            </div>
        </form>
    @endif
</div>
