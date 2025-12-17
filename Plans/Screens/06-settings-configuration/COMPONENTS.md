# Settings & Configuration - UI Components

## Component Structure

```
resources/views/components/settings/
├── setting-field.blade.php
├── settings-group.blade.php
├── settings-tabs.blade.php
├── settings-card.blade.php
├── history-entry.blade.php
└── import-export.blade.php
```

---

## Core Components

### Settings Page Layout

```blade
{{-- resources/views/admin/settings/layout.blade.php --}}
@props(['title', 'groups' => [], 'activeGroup' => null])

<x-admin-layout>
    <div class="settings-layout">
        {{-- Sidebar Navigation --}}
        <aside class="settings-layout__sidebar">
            <nav class="settings-nav">
                @foreach($groups as $group)
                    <a href="{{ route('admin.settings.group', $group['key']) }}"
                       class="settings-nav__item {{ $activeGroup === $group['key'] ? 'settings-nav__item--active' : '' }}">
                        <x-icon :name="$group['icon']" class="w-5 h-5" />
                        <span>{{ $group['label'] }}</span>
                    </a>
                @endforeach
                
                @if(count($pluginGroups ?? []))
                    <div class="settings-nav__divider">Plugin Settings</div>
                    @foreach($pluginGroups as $plugin)
                        <a href="{{ route('admin.plugins.settings', $plugin['slug']) }}"
                           class="settings-nav__item">
                            <x-icon :name="$plugin['icon'] ?? 'puzzle'" class="w-5 h-5" />
                            <span>{{ $plugin['name'] }}</span>
                        </a>
                    @endforeach
                @endif
            </nav>
        </aside>
        
        {{-- Main Content --}}
        <main class="settings-layout__content">
            <form action="{{ $action ?? '' }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="settings-header">
                    <h1>{{ $title }}</h1>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
                
                {{ $slot }}
            </form>
        </main>
    </div>
</x-admin-layout>
```

### Settings Tabs Component

```blade
{{-- resources/views/components/settings/settings-tabs.blade.php --}}
@props(['tabs', 'activeTab' => null])

<div x-data="{ activeTab: '{{ $activeTab ?? $tabs[0]['key'] ?? '' }}' }" class="settings-tabs">
    {{-- Tab Headers --}}
    <div class="settings-tabs__header">
        @foreach($tabs as $tab)
            <button type="button"
                    @click="activeTab = '{{ $tab['key'] }}'"
                    :class="{ 'settings-tabs__tab--active': activeTab === '{{ $tab['key'] }}' }"
                    class="settings-tabs__tab">
                @if($tab['icon'] ?? null)
                    <x-icon :name="$tab['icon']" class="w-4 h-4" />
                @endif
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>
    
    {{-- Tab Content --}}
    <div class="settings-tabs__content">
        @foreach($tabs as $tab)
            <div x-show="activeTab === '{{ $tab['key'] }}'"
                 x-transition
                 class="settings-tabs__panel">
                @foreach($tab['settings'] ?? [] as $setting)
                    <x-settings.setting-field :setting="$setting" />
                @endforeach
            </div>
        @endforeach
    </div>
</div>
```

### Settings Card Component

```blade
{{-- resources/views/components/settings/settings-card.blade.php --}}
@props(['title', 'description' => null, 'icon' => null, 'collapsible' => false])

<div x-data="{ open: true }" class="settings-card">
    <div class="settings-card__header" @if($collapsible) @click="open = !open" role="button" @endif>
        <div class="settings-card__title">
            @if($icon)
                <x-icon :name="$icon" class="w-5 h-5 text-gray-500" />
            @endif
            <h3>{{ $title }}</h3>
        </div>
        
        @if($description)
            <p class="settings-card__description">{{ $description }}</p>
        @endif
        
        @if($collapsible)
            <x-icon name="chevron-down" class="w-5 h-5 transition-transform" :class="{ 'rotate-180': !open }" />
        @endif
    </div>
    
    <div x-show="open" @if($collapsible) x-collapse @endif class="settings-card__body">
        {{ $slot }}
    </div>
</div>
```

### Enhanced Setting Field Component

```blade
{{-- resources/views/components/settings/setting-field.blade.php --}}
@props(['setting'])

@php
    $hasError = isset($errors) && $errors->has($setting['key']);
    $id = Str::slug($setting['key']);
@endphp

<div class="setting-field {{ $hasError ? 'setting-field--error' : '' }}">
    <div class="setting-field__header">
        <label for="{{ $id }}" class="setting-field__label">
            {{ $setting['label'] }}
            @if($setting['is_required'] ?? false)
                <span class="text-red-500">*</span>
            @endif
        </label>
        
        @if($setting['is_system'] ?? false)
            <span class="badge badge-gray text-xs">System</span>
        @endif
    </div>
    
    <div class="setting-field__control">
        @switch($setting['type'])
            @case('string')
            @case('text')
                <input type="text"
                       id="{{ $id }}"
                       name="{{ $setting['key'] }}"
                       value="{{ old($setting['key'], $setting['value']) }}"
                       placeholder="{{ $setting['config']['placeholder'] ?? '' }}"
                       class="form-input"
                       @if($setting['is_required'] ?? false) required @endif
                       @if($setting['config']['maxlength'] ?? false) maxlength="{{ $setting['config']['maxlength'] }}" @endif>
                @break
                
            @case('textarea')
                <textarea id="{{ $id }}"
                          name="{{ $setting['key'] }}"
                          rows="{{ $setting['config']['rows'] ?? 4 }}"
                          class="form-textarea"
                          placeholder="{{ $setting['config']['placeholder'] ?? '' }}">{{ old($setting['key'], $setting['value']) }}</textarea>
                @break
                
            @case('number')
            @case('integer')
            @case('decimal')
                <input type="number"
                       id="{{ $id }}"
                       name="{{ $setting['key'] }}"
                       value="{{ old($setting['key'], $setting['value']) }}"
                       min="{{ $setting['config']['min'] ?? '' }}"
                       max="{{ $setting['config']['max'] ?? '' }}"
                       step="{{ $setting['config']['step'] ?? ($setting['type'] === 'decimal' ? '0.01' : '1') }}"
                       class="form-input">
                @break
                
            @case('boolean')
            @case('toggle')
                <label class="toggle">
                    <input type="hidden" name="{{ $setting['key'] }}" value="0">
                    <input type="checkbox"
                           id="{{ $id }}"
                           name="{{ $setting['key'] }}"
                           value="1"
                           {{ old($setting['key'], $setting['value']) ? 'checked' : '' }}
                           class="toggle__input">
                    <span class="toggle__track">
                        <span class="toggle__thumb"></span>
                    </span>
                </label>
                @break
                
            @case('select')
                <select id="{{ $id }}" name="{{ $setting['key'] }}" class="form-select">
                    @if(!($setting['is_required'] ?? false))
                        <option value="">— Select —</option>
                    @endif
                    @foreach($setting['config']['options'] ?? [] as $value => $label)
                        <option value="{{ $value }}" {{ old($setting['key'], $setting['value']) == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @break
                
            @case('multiselect')
                <select id="{{ $id }}" 
                        name="{{ $setting['key'] }}[]" 
                        multiple 
                        class="form-multiselect"
                        x-data="multiSelect()">
                    @foreach($setting['config']['options'] ?? [] as $value => $label)
                        <option value="{{ $value }}" 
                            {{ in_array($value, (array) old($setting['key'], $setting['value'] ?? [])) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @break
                
            @case('color')
                <div class="flex items-center gap-2">
                    <input type="color"
                           id="{{ $id }}"
                           name="{{ $setting['key'] }}"
                           value="{{ old($setting['key'], $setting['value'] ?? '#000000') }}"
                           class="form-color">
                    <input type="text"
                           value="{{ old($setting['key'], $setting['value'] ?? '#000000') }}"
                           class="form-input w-28"
                           pattern="^#[0-9A-Fa-f]{6}$"
                           oninput="document.getElementById('{{ $id }}').value = this.value">
                </div>
                @break
                
            @case('file')
            @case('image')
                <x-file-upload
                    name="{{ $setting['key'] }}"
                    :current="$setting['value']"
                    :accept="$setting['config']['accept'] ?? ($setting['type'] === 'image' ? 'image/*' : '*')"
                    :preview="$setting['type'] === 'image'" />
                @break
                
            @case('encrypted')
            @case('password')
                <input type="password"
                       id="{{ $id }}"
                       name="{{ $setting['key'] }}"
                       placeholder="{{ $setting['value'] ? '••••••••' : 'Enter value' }}"
                       autocomplete="new-password"
                       class="form-input">
                @if($setting['value'])
                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current value</p>
                @endif
                @break
                
            @case('json')
            @case('array')
                <x-json-editor
                    name="{{ $setting['key'] }}"
                    :value="$setting['value']" />
                @break
                
            @default
                <input type="text"
                       id="{{ $id }}"
                       name="{{ $setting['key'] }}"
                       value="{{ old($setting['key'], $setting['value']) }}"
                       class="form-input">
        @endswitch
    </div>
    
    @if($setting['description'] ?? null)
        <p class="setting-field__help">{{ $setting['description'] }}</p>
    @endif
    
    @error($setting['key'])
        <p class="setting-field__error">{{ $message }}</p>
    @enderror
</div>
```

### History Entry Component

```blade
{{-- resources/views/components/settings/history-entry.blade.php --}}
@props(['entry'])

<div class="history-entry">
    <div class="history-entry__icon">
        <x-icon name="settings" class="w-4 h-4" />
    </div>
    
    <div class="history-entry__content">
        <div class="history-entry__header">
            <code class="history-entry__key">{{ $entry->setting_key }}</code>
            <span class="history-entry__time">{{ $entry->created_at->diffForHumans() }}</span>
        </div>
        
        <div class="history-entry__user">
            Changed by {{ $entry->user?->name ?? 'System' }}
        </div>
        
        <div class="history-entry__changes">
            <div class="history-entry__old">
                <span class="text-red-600">-</span>
                {{ Str::limit($entry->old_value, 100) ?: '(empty)' }}
            </div>
            <div class="history-entry__new">
                <span class="text-green-600">+</span>
                {{ Str::limit($entry->new_value, 100) ?: '(empty)' }}
            </div>
        </div>
    </div>
    
    <div class="history-entry__actions">
        <form action="{{ route('admin.settings.restore', $entry) }}" method="POST">
            @csrf
            <button type="submit" 
                    class="btn btn-sm btn-secondary"
                    onclick="return confirm('Restore this setting to its previous value?')">
                Restore
            </button>
        </form>
    </div>
</div>
```

---

## Tailwind Styles

```css
/* Settings Layout */
.settings-layout {
    @apply flex min-h-screen;
}
.settings-layout__sidebar {
    @apply w-64 bg-gray-50 border-r border-gray-200 p-4;
}
.settings-layout__content {
    @apply flex-1 p-6;
}

/* Settings Navigation */
.settings-nav__item {
    @apply flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700;
    @apply hover:bg-gray-100 transition-colors;
}
.settings-nav__item--active {
    @apply bg-primary-50 text-primary-700;
}
.settings-nav__divider {
    @apply mt-6 mb-2 px-3 text-xs font-semibold text-gray-500 uppercase;
}

/* Settings Card */
.settings-card {
    @apply bg-white rounded-lg border border-gray-200 mb-6;
}
.settings-card__header {
    @apply px-6 py-4 border-b border-gray-100;
}
.settings-card__title {
    @apply flex items-center gap-2 font-medium text-gray-900;
}
.settings-card__body {
    @apply p-6 space-y-6;
}

/* Setting Field */
.setting-field {
    @apply space-y-2;
}
.setting-field__label {
    @apply block text-sm font-medium text-gray-700;
}
.setting-field__help {
    @apply text-sm text-gray-500;
}
.setting-field__error {
    @apply text-sm text-red-600;
}

/* Toggle Switch */
.toggle {
    @apply relative inline-flex items-center cursor-pointer;
}
.toggle__track {
    @apply w-11 h-6 bg-gray-200 rounded-full relative transition-colors;
}
.toggle__input:checked + .toggle__track {
    @apply bg-primary-600;
}
.toggle__thumb {
    @apply absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition-transform;
}
.toggle__input:checked + .toggle__track .toggle__thumb {
    @apply translate-x-5;
}
```
