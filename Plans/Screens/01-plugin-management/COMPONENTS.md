# Plugin Management - UI Components

## Overview

This document defines the reusable UI components for the Plugin Management module. Components are built with Blade templates and can integrate with Alpine.js, Livewire, or Vue.js.

## Component Architecture

```
resources/views/components/plugins/
├── card.blade.php              # Plugin display card
├── row.blade.php               # Table row for list view
├── status-badge.blade.php      # Status indicator
├── version-badge.blade.php     # Version display
├── rating.blade.php            # Star rating display
├── compatibility.blade.php     # Compatibility indicator
├── actions-menu.blade.php      # Dropdown actions
├── settings-field.blade.php    # Dynamic form field
├── settings-group.blade.php    # Field grouping
├── dependency-tree.blade.php   # Visual dependency tree
├── install-progress.blade.php  # Installation progress
├── changelog.blade.php         # Changelog display
├── permission-list.blade.php   # Permission listing
├── license-status.blade.php    # License indicator
└── modal/
    ├── install.blade.php
    ├── update.blade.php
    ├── uninstall.blade.php
    └── license.blade.php
```

---

## Core Components

### 1. Plugin Card

Display a plugin in card format for marketplace or grid views.

```blade
{{-- resources/views/components/plugins/card.blade.php --}}
@props([
    'plugin',
    'installed' => false,
    'showActions' => true,
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'plugin-card ' . ($compact ? 'plugin-card--compact' : '')]) }}
     data-plugin="{{ $plugin['slug'] }}">
    
    {{-- Icon --}}
    <div class="plugin-card__icon">
        @if($plugin['icon'])
            <img src="{{ $plugin['icon'] }}" 
                 alt="{{ $plugin['name'] }}" 
                 loading="lazy"
                 onerror="this.src='/images/plugin-placeholder.png'">
        @else
            <div class="plugin-card__icon-placeholder">
                <x-icon name="puzzle" class="w-8 h-8" />
            </div>
        @endif
        
        @if($plugin['is_premium'] ?? false)
            <span class="plugin-card__premium-badge">Premium</span>
        @endif
    </div>
    
    {{-- Content --}}
    <div class="plugin-card__content">
        <h3 class="plugin-card__title">
            <a href="{{ route('admin.plugins.show', $plugin['slug']) }}">
                {{ $plugin['name'] }}
            </a>
        </h3>
        
        <div class="plugin-card__meta">
            <x-plugins.rating :value="$plugin['rating'] ?? 0" :count="$plugin['reviews_count'] ?? 0" />
            <span class="plugin-card__author">
                by {{ $plugin['author']['name'] ?? $plugin['author'] ?? 'Unknown' }}
            </span>
        </div>
        
        @unless($compact)
            <p class="plugin-card__description">
                {{ Str::limit($plugin['short_description'] ?? $plugin['description'], 100) }}
            </p>
        @endunless
        
        <div class="plugin-card__footer">
            <span class="plugin-card__version">v{{ $plugin['version'] }}</span>
            
            @if(isset($plugin['downloads']))
                <span class="plugin-card__downloads">
                    <x-icon name="download" class="w-4 h-4" />
                    {{ number_format($plugin['downloads']) }}
                </span>
            @endif
            
            <x-plugins.compatibility :status="$plugin['compatibility'] ?? 'unknown'" />
        </div>
    </div>
    
    {{-- Actions --}}
    @if($showActions)
        <div class="plugin-card__actions">
            <a href="{{ route('admin.plugins.show', $plugin['slug']) }}" 
               class="btn btn-secondary btn-sm">
                View
            </a>
            
            @if($installed)
                <span class="badge badge-success">Installed</span>
            @else
                <button type="button" 
                        class="btn btn-primary btn-sm"
                        onclick="installPlugin('{{ $plugin['slug'] }}')">
                    Install
                </button>
            @endif
        </div>
    @endif
</div>

<style>
.plugin-card {
    @apply bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow;
    display: grid;
    grid-template-columns: 64px 1fr auto;
    gap: 1rem;
    align-items: start;
}

.plugin-card--compact {
    grid-template-columns: 48px 1fr auto;
    padding: 0.75rem;
}

.plugin-card__icon {
    @apply relative;
}

.plugin-card__icon img {
    @apply w-16 h-16 rounded-lg object-cover;
}

.plugin-card--compact .plugin-card__icon img {
    @apply w-12 h-12;
}

.plugin-card__icon-placeholder {
    @apply w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400;
}

.plugin-card__premium-badge {
    @apply absolute -top-1 -right-1 bg-amber-500 text-white text-xs px-1.5 py-0.5 rounded-full;
}

.plugin-card__title {
    @apply font-semibold text-gray-900 mb-1;
}

.plugin-card__title a {
    @apply hover:text-primary-600;
}

.plugin-card__meta {
    @apply flex items-center gap-2 text-sm text-gray-500 mb-2;
}

.plugin-card__description {
    @apply text-sm text-gray-600 mb-3 line-clamp-2;
}

.plugin-card__footer {
    @apply flex items-center gap-3 text-xs text-gray-500;
}

.plugin-card__actions {
    @apply flex flex-col gap-2;
}
</style>
```

### 2. Plugin Row (Table View)

```blade
{{-- resources/views/components/plugins/row.blade.php --}}
@props([
    'plugin',
    'selectable' => false,
])

<tr class="plugin-row" data-plugin="{{ $plugin->slug }}">
    @if($selectable)
        <td class="plugin-row__checkbox">
            <input type="checkbox" 
                   name="plugins[]" 
                   value="{{ $plugin->id }}"
                   class="rounded border-gray-300">
        </td>
    @endif
    
    <td class="plugin-row__info">
        <div class="flex items-center gap-3">
            <div class="plugin-row__icon">
                @if($plugin->icon)
                    <img src="{{ $plugin->icon }}" alt="">
                @else
                    <x-icon name="puzzle" class="w-5 h-5 text-gray-400" />
                @endif
            </div>
            <div>
                <a href="{{ route('admin.plugins.show', $plugin) }}" 
                   class="font-medium text-gray-900 hover:text-primary-600">
                    {{ $plugin->name }}
                </a>
                <p class="text-sm text-gray-500 truncate max-w-md">
                    {{ Str::limit($plugin->description, 60) }}
                </p>
            </div>
        </div>
    </td>
    
    <td class="plugin-row__status">
        <x-plugins.status-badge :status="$plugin->status" />
    </td>
    
    <td class="plugin-row__version">
        <span class="text-sm text-gray-600">v{{ $plugin->version }}</span>
        @if($plugin->has_update)
            <span class="ml-1 text-xs text-primary-600 font-medium">
                ⬆ {{ $plugin->latest_version }}
            </span>
        @endif
    </td>
    
    <td class="plugin-row__category">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            {{ $plugin->category ?? 'Uncategorized' }}
        </span>
    </td>
    
    <td class="plugin-row__actions">
        <x-plugins.actions-menu :plugin="$plugin" />
    </td>
</tr>

<style>
.plugin-row {
    @apply hover:bg-gray-50;
}

.plugin-row td {
    @apply px-4 py-3 whitespace-nowrap;
}

.plugin-row__icon {
    @apply w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden;
}

.plugin-row__icon img {
    @apply w-full h-full object-cover;
}
</style>
```

### 3. Status Badge

```blade
{{-- resources/views/components/plugins/status-badge.blade.php --}}
@props(['status'])

@php
$config = match($status) {
    'active' => [
        'class' => 'bg-green-100 text-green-800',
        'dot' => 'bg-green-500',
        'label' => 'Active',
        'icon' => 'check-circle',
    ],
    'inactive' => [
        'class' => 'bg-gray-100 text-gray-600',
        'dot' => 'bg-gray-400',
        'label' => 'Inactive',
        'icon' => 'pause-circle',
    ],
    'error' => [
        'class' => 'bg-red-100 text-red-800',
        'dot' => 'bg-red-500',
        'label' => 'Error',
        'icon' => 'alert-circle',
    ],
    'updating' => [
        'class' => 'bg-yellow-100 text-yellow-800',
        'dot' => 'bg-yellow-500 animate-pulse',
        'label' => 'Updating',
        'icon' => 'loader',
    ],
    default => [
        'class' => 'bg-gray-100 text-gray-600',
        'dot' => 'bg-gray-400',
        'label' => ucfirst($status),
        'icon' => 'help-circle',
    ],
};
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ' . $config['class']]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }}"></span>
    {{ $config['label'] }}
</span>
```

### 4. Rating Component

```blade
{{-- resources/views/components/plugins/rating.blade.php --}}
@props([
    'value' => 0,
    'count' => 0,
    'showCount' => true,
    'size' => 'sm',
])

@php
$fullStars = floor($value);
$halfStar = ($value - $fullStars) >= 0.5;
$emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
$sizeClasses = match($size) {
    'xs' => 'w-3 h-3',
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
    default => 'w-4 h-4',
};
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    <div class="flex items-center">
        @for($i = 0; $i < $fullStars; $i++)
            <x-icon name="star" class="{{ $sizeClasses }} text-amber-400 fill-current" />
        @endfor
        
        @if($halfStar)
            <div class="relative {{ $sizeClasses }}">
                <x-icon name="star" class="absolute {{ $sizeClasses }} text-gray-300" />
                <div class="overflow-hidden w-1/2">
                    <x-icon name="star" class="{{ $sizeClasses }} text-amber-400 fill-current" />
                </div>
            </div>
        @endif
        
        @for($i = 0; $i < $emptyStars; $i++)
            <x-icon name="star" class="{{ $sizeClasses }} text-gray-300" />
        @endfor
    </div>
    
    @if($showCount && $count > 0)
        <span class="text-sm text-gray-500">({{ number_format($count) }})</span>
    @endif
</div>
```

### 5. Compatibility Badge

```blade
{{-- resources/views/components/plugins/compatibility.blade.php --}}
@props(['status', 'requiredVersion' => null])

@php
$config = match($status) {
    'compatible' => [
        'class' => 'text-green-600',
        'icon' => 'check-circle',
        'label' => 'Compatible',
    ],
    'requires_update' => [
        'class' => 'text-amber-600',
        'icon' => 'alert-triangle',
        'label' => $requiredVersion ? "Requires v{$requiredVersion}+" : 'Update Required',
    ],
    'incompatible' => [
        'class' => 'text-red-600',
        'icon' => 'x-circle',
        'label' => 'Incompatible',
    ],
    default => [
        'class' => 'text-gray-500',
        'icon' => 'help-circle',
        'label' => 'Unknown',
    ],
};
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs ' . $config['class']]) }}
      title="{{ $config['label'] }}">
    <x-icon :name="$config['icon']" class="w-3.5 h-3.5" />
    <span>{{ $config['label'] }}</span>
</span>
```

### 6. Actions Menu

```blade
{{-- resources/views/components/plugins/actions-menu.blade.php --}}
@props(['plugin'])

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" 
            @click.away="open = false"
            class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <x-icon name="more-vertical" class="w-5 h-5 text-gray-500" />
    </button>
    
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
        
        @can('plugins.configure')
            @if($plugin->hasSettingsPage())
                <a href="{{ route('admin.plugins.settings', $plugin) }}" 
                   class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <x-icon name="settings" class="w-4 h-4" />
                    Settings
                </a>
            @endif
        @endcan
        
        @if($plugin->homepage)
            <a href="{{ $plugin->homepage }}" 
               target="_blank"
               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <x-icon name="external-link" class="w-4 h-4" />
                Documentation
            </a>
        @endif
        
        <hr class="my-1 border-gray-200">
        
        @can('plugins.activate')
            @if($plugin->status === 'active')
                <button onclick="deactivatePlugin('{{ $plugin->slug }}')"
                        @if(!$plugin->canDeactivate()) disabled @endif
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                    <x-icon name="pause-circle" class="w-4 h-4" />
                    Deactivate
                </button>
            @else
                <button onclick="activatePlugin('{{ $plugin->slug }}')"
                        @if(!$plugin->canActivate()) disabled @endif
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                    <x-icon name="play-circle" class="w-4 h-4" />
                    Activate
                </button>
            @endif
        @endcan
        
        @can('plugins.update')
            @if($plugin->has_update)
                <button onclick="updatePlugin('{{ $plugin->slug }}')"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-primary-600 hover:bg-primary-50">
                    <x-icon name="download" class="w-4 h-4" />
                    Update to {{ $plugin->latest_version }}
                </button>
            @endif
        @endcan
        
        <hr class="my-1 border-gray-200">
        
        @can('plugins.uninstall')
            @if($plugin->canUninstall())
                <button onclick="confirmUninstall('{{ $plugin->slug }}', '{{ $plugin->name }}')"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <x-icon name="trash-2" class="w-4 h-4" />
                    Uninstall
                </button>
            @endif
        @endcan
    </div>
</div>
```

### 7. Settings Field (Dynamic)

```blade
{{-- resources/views/components/plugins/settings-field.blade.php --}}
@props([
    'field',
    'value' => null,
    'prefix' => '',
])

@php
$name = $prefix ? "{$prefix}[{$field['key']}]" : $field['key'];
$id = str_replace(['[', ']'], ['-', ''], $name);
$currentValue = old($name, $value ?? $field['default'] ?? null);
@endphp

<div class="settings-field" 
     x-data="{ visible: true }"
     @if(isset($field['condition']))
     x-init="$watch('$store.settings.{{ $field['condition']['field'] }}', val => visible = val === {{ json_encode($field['condition']['value']) }})"
     x-show="visible"
     @endif
>
    @unless(in_array($field['type'], ['checkbox', 'toggle']))
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $field['label'] }}
            @if(isset($field['rules']) && str_contains($field['rules'], 'required'))
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endunless
    
    @switch($field['type'])
        @case('text')
        @case('email')
        @case('url')
        @case('tel')
            <input type="{{ $field['type'] }}"
                   id="{{ $id }}"
                   name="{{ $name }}"
                   value="{{ $currentValue }}"
                   placeholder="{{ $field['placeholder'] ?? '' }}"
                   @if(isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error($name) border-red-500 @enderror">
            @break
            
        @case('number')
            <input type="number"
                   id="{{ $id }}"
                   name="{{ $name }}"
                   value="{{ $currentValue }}"
                   @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                   @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                   @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            @break
            
        @case('textarea')
            <textarea id="{{ $id }}"
                      name="{{ $name }}"
                      rows="{{ $field['rows'] ?? 4 }}"
                      placeholder="{{ $field['placeholder'] ?? '' }}"
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ $currentValue }}</textarea>
            @break
            
        @case('select')
            <select id="{{ $id }}"
                    name="{{ $name }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                @if(isset($field['placeholder']))
                    <option value="">{{ $field['placeholder'] }}</option>
                @endif
                @foreach($field['options'] as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected($currentValue == $optValue)>
                        {{ $optLabel }}
                    </option>
                @endforeach
            </select>
            @break
            
        @case('multiselect')
            <select id="{{ $id }}"
                    name="{{ $name }}[]"
                    multiple
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach($field['options'] as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected(in_array($optValue, (array)$currentValue))>
                        {{ $optLabel }}
                    </option>
                @endforeach
            </select>
            @break
            
        @case('checkbox')
            <label class="flex items-center gap-2">
                <input type="hidden" name="{{ $name }}" value="0">
                <input type="checkbox"
                       id="{{ $id }}"
                       name="{{ $name }}"
                       value="1"
                       @checked($currentValue)
                       class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <span class="text-sm text-gray-700">{{ $field['label'] }}</span>
            </label>
            @break
            
        @case('toggle')
            <label class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">{{ $field['label'] }}</span>
                <button type="button"
                        role="switch"
                        x-data="{ on: {{ $currentValue ? 'true' : 'false' }} }"
                        @click="on = !on"
                        :aria-checked="on"
                        :class="on ? 'bg-primary-600' : 'bg-gray-200'"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                    <input type="hidden" name="{{ $name }}" :value="on ? 1 : 0">
                    <span :class="on ? 'translate-x-5' : 'translate-x-0'"
                          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"></span>
                </button>
            </label>
            @break
            
        @case('radio')
            <div class="space-y-2">
                @foreach($field['options'] as $optValue => $optLabel)
                    <label class="flex items-center gap-2">
                        <input type="radio"
                               name="{{ $name }}"
                               value="{{ $optValue }}"
                               @checked($currentValue == $optValue)
                               class="border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="text-sm text-gray-700">{{ $optLabel }}</span>
                    </label>
                @endforeach
            </div>
            @break
            
        @case('color')
            <div class="flex items-center gap-2">
                <input type="color"
                       id="{{ $id }}"
                       name="{{ $name }}"
                       value="{{ $currentValue }}"
                       class="h-10 w-14 rounded cursor-pointer">
                <input type="text"
                       value="{{ $currentValue }}"
                       readonly
                       class="w-24 rounded-lg border-gray-300 bg-gray-50 text-sm">
            </div>
            @break
            
        @case('file')
            <input type="file"
                   id="{{ $id }}"
                   name="{{ $name }}"
                   @if(isset($field['accept'])) accept="{{ $field['accept'] }}" @endif
                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
            @break
            
        @case('code')
            <div x-data="{ editor: null }"
                 x-init="editor = CodeMirror.fromTextArea($refs.textarea, { 
                     mode: '{{ $field['mode'] ?? 'javascript' }}',
                     theme: 'default',
                     lineNumbers: true
                 })"
                 class="border border-gray-300 rounded-lg overflow-hidden">
                <textarea x-ref="textarea"
                          id="{{ $id }}"
                          name="{{ $name }}">{{ $currentValue }}</textarea>
            </div>
            @break
            
        @case('group')
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-4">
                @foreach($field['fields'] as $subfield)
                    <x-plugins.settings-field 
                        :field="$subfield" 
                        :value="$currentValue[$subfield['key']] ?? null"
                        :prefix="$name" 
                    />
                @endforeach
            </div>
            @break
            
        @case('repeater')
            <div x-data="repeater({{ json_encode((array)$currentValue) }})" class="space-y-2">
                <template x-for="(item, index) in items" :key="index">
                    <div class="flex items-center gap-2">
                        <input type="text"
                               :name="`{{ $name }}[${index}]`"
                               x-model="item.value"
                               class="flex-1 rounded-lg border-gray-300 shadow-sm">
                        <button type="button" @click="removeItem(index)" class="p-2 text-red-500 hover:bg-red-50 rounded">
                            <x-icon name="trash-2" class="w-4 h-4" />
                        </button>
                    </div>
                </template>
                <button type="button" @click="addItem()" class="text-sm text-primary-600 hover:text-primary-700">
                    + Add Item
                </button>
            </div>
            @break
    @endswitch
    
    @if(isset($field['hint']))
        <p class="mt-1 text-sm text-gray-500">{{ $field['hint'] }}</p>
    @endif
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

### 8. Installation Progress

```blade
{{-- resources/views/components/plugins/install-progress.blade.php --}}
@props(['jobId'])

<div x-data="installProgress('{{ $jobId }}')" class="space-y-4">
    {{-- Overall Progress --}}
    <div>
        <div class="flex justify-between text-sm mb-1">
            <span x-text="currentStep"></span>
            <span x-text="progress + '%'"></span>
        </div>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-primary-600 transition-all duration-300"
                 :style="{ width: progress + '%' }"></div>
        </div>
    </div>
    
    {{-- Steps --}}
    <div class="space-y-2">
        <template x-for="step in steps" :key="step.key">
            <div class="flex items-center gap-3 text-sm">
                <div :class="{
                    'text-green-500': step.status === 'complete',
                    'text-primary-500': step.status === 'running',
                    'text-gray-400': step.status === 'pending'
                }">
                    <template x-if="step.status === 'complete'">
                        <x-icon name="check-circle" class="w-5 h-5" />
                    </template>
                    <template x-if="step.status === 'running'">
                        <x-icon name="loader" class="w-5 h-5 animate-spin" />
                    </template>
                    <template x-if="step.status === 'pending'">
                        <x-icon name="circle" class="w-5 h-5" />
                    </template>
                    <template x-if="step.status === 'error'">
                        <x-icon name="x-circle" class="w-5 h-5 text-red-500" />
                    </template>
                </div>
                <span :class="{ 'text-gray-900': step.status !== 'pending', 'text-gray-500': step.status === 'pending' }"
                      x-text="step.label"></span>
            </div>
        </template>
    </div>
    
    {{-- Log Output --}}
    <div x-show="showLog" class="mt-4">
        <div class="bg-gray-900 text-gray-100 p-4 rounded-lg text-xs font-mono h-32 overflow-auto">
            <template x-for="line in logLines" :key="line.id">
                <div x-text="line.text" :class="{ 'text-red-400': line.error }"></div>
            </template>
        </div>
    </div>
    
    {{-- Error State --}}
    <div x-show="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start gap-3">
            <x-icon name="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
            <div>
                <h4 class="font-medium text-red-800">Installation Failed</h4>
                <p class="text-sm text-red-600 mt-1" x-text="errorMessage"></p>
                <button @click="retry()" class="mt-2 text-sm text-red-700 underline hover:no-underline">
                    Retry Installation
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function installProgress(jobId) {
    return {
        jobId: jobId,
        progress: 0,
        currentStep: 'Initializing...',
        showLog: false,
        error: false,
        errorMessage: '',
        logLines: [],
        steps: [
            { key: 'download', label: 'Downloading plugin...', status: 'pending' },
            { key: 'verify', label: 'Verifying package...', status: 'pending' },
            { key: 'extract', label: 'Extracting files...', status: 'pending' },
            { key: 'dependencies', label: 'Installing dependencies...', status: 'pending' },
            { key: 'migrations', label: 'Running migrations...', status: 'pending' },
            { key: 'register', label: 'Registering plugin...', status: 'pending' },
            { key: 'assets', label: 'Publishing assets...', status: 'pending' },
            { key: 'cache', label: 'Clearing caches...', status: 'pending' },
        ],
        
        init() {
            this.connect();
        },
        
        connect() {
            const eventSource = new EventSource(`/api/v1/admin/plugins/install/${this.jobId}/progress`);
            
            eventSource.addEventListener('progress', (e) => {
                const data = JSON.parse(e.data);
                this.updateProgress(data);
            });
            
            eventSource.addEventListener('complete', (e) => {
                const data = JSON.parse(e.data);
                eventSource.close();
                this.onComplete(data);
            });
            
            eventSource.addEventListener('error', (e) => {
                if (e.data) {
                    const data = JSON.parse(e.data);
                    this.onError(data);
                }
                eventSource.close();
            });
        },
        
        updateProgress(data) {
            this.progress = data.progress;
            this.currentStep = data.message;
            
            // Update step status
            const stepIndex = this.steps.findIndex(s => s.key === data.step);
            if (stepIndex !== -1) {
                this.steps.forEach((step, i) => {
                    if (i < stepIndex) step.status = 'complete';
                    else if (i === stepIndex) step.status = data.status;
                    else step.status = 'pending';
                });
            }
            
            // Add to log
            if (data.log) {
                this.logLines.push({ id: Date.now(), text: data.log, error: false });
            }
        },
        
        onComplete(data) {
            this.progress = 100;
            this.currentStep = 'Installation complete!';
            this.steps.forEach(s => s.status = 'complete');
            
            // Redirect or show success
            setTimeout(() => {
                window.location.href = `/admin/plugins/${data.plugin.slug}`;
            }, 1500);
        },
        
        onError(data) {
            this.error = true;
            this.errorMessage = data.message;
            this.logLines.push({ id: Date.now(), text: data.message, error: true });
        },
        
        retry() {
            this.error = false;
            this.errorMessage = '';
            this.progress = 0;
            this.steps.forEach(s => s.status = 'pending');
            // Re-trigger installation
        }
    };
}
</script>
```

### 9. Dependency Tree

```blade
{{-- resources/views/components/plugins/dependency-tree.blade.php --}}
@props(['plugin', 'dependencies'])

<div class="dependency-tree">
    <div class="dependency-tree__root">
        <div class="dependency-tree__node dependency-tree__node--root">
            <x-icon name="puzzle" class="w-5 h-5" />
            <span class="font-medium">{{ $plugin->name }}</span>
            <span class="text-sm text-gray-500">v{{ $plugin->version }}</span>
        </div>
    </div>
    
    @if(count($dependencies) > 0)
        <div class="dependency-tree__branches">
            @foreach($dependencies as $index => $dep)
                <div class="dependency-tree__branch">
                    <div class="dependency-tree__connector">
                        <div class="dependency-tree__line-v"></div>
                        <div class="dependency-tree__line-h"></div>
                    </div>
                    
                    <div class="dependency-tree__node {{ 'dependency-tree__node--' . $dep['status'] }}">
                        @switch($dep['status'])
                            @case('satisfied')
                                <x-icon name="check-circle" class="w-4 h-4 text-green-500" />
                                @break
                            @case('version_mismatch')
                                <x-icon name="alert-triangle" class="w-4 h-4 text-amber-500" />
                                @break
                            @case('missing')
                            @case('inactive')
                                <x-icon name="x-circle" class="w-4 h-4 text-red-500" />
                                @break
                        @endswitch
                        
                        <span>{{ $dep['name'] ?? $dep['slug'] }}</span>
                        <span class="text-sm text-gray-500">
                            {{ $dep['version_constraint'] }}
                            @if($dep['installed_version'])
                                → v{{ $dep['installed_version'] }}
                            @endif
                        </span>
                        
                        @if($dep['is_optional'])
                            <span class="text-xs text-gray-400">(optional)</span>
                        @endif
                    </div>
                    
                    {{-- Nested dependencies --}}
                    @if(!empty($dep['dependencies']))
                        <x-plugins.dependency-tree :plugin="null" :dependencies="$dep['dependencies']" />
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
.dependency-tree {
    @apply font-mono text-sm;
}

.dependency-tree__node {
    @apply flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200;
}

.dependency-tree__node--root {
    @apply bg-primary-50 border-primary-200;
}

.dependency-tree__node--satisfied {
    @apply bg-green-50 border-green-200;
}

.dependency-tree__node--version_mismatch {
    @apply bg-amber-50 border-amber-200;
}

.dependency-tree__node--missing,
.dependency-tree__node--inactive {
    @apply bg-red-50 border-red-200;
}

.dependency-tree__branches {
    @apply ml-6 mt-2 space-y-2;
}

.dependency-tree__branch {
    @apply relative pl-6;
}

.dependency-tree__connector {
    @apply absolute left-0 top-0;
}

.dependency-tree__line-v {
    @apply absolute left-0 top-0 w-px h-full bg-gray-300;
}

.dependency-tree__line-h {
    @apply absolute left-0 top-4 w-6 h-px bg-gray-300;
}
</style>
```

### 10. Changelog Display

```blade
{{-- resources/views/components/plugins/changelog.blade.php --}}
@props(['changelog', 'limit' => null])

<div class="changelog">
    @foreach(collect($changelog)->when($limit, fn($c) => $c->take($limit)) as $version => $entry)
        <div class="changelog__entry">
            <div class="changelog__header">
                <span class="changelog__version">{{ $version }}</span>
                @if(isset($entry['date']))
                    <span class="changelog__date">{{ \Carbon\Carbon::parse($entry['date'])->format('F j, Y') }}</span>
                @endif
                @if(isset($entry['is_security']) && $entry['is_security'])
                    <span class="changelog__badge changelog__badge--security">Security</span>
                @endif
                @if(isset($entry['is_breaking']) && $entry['is_breaking'])
                    <span class="changelog__badge changelog__badge--breaking">Breaking</span>
                @endif
            </div>
            
            <div class="changelog__content">
                @if(is_array($entry['changes'] ?? $entry))
                    @foreach(['added', 'changed', 'fixed', 'removed', 'deprecated', 'security'] as $type)
                        @if(!empty($entry[$type] ?? ($entry['changes'][$type] ?? null)))
                            <div class="changelog__section">
                                <h4 class="changelog__section-title changelog__section-title--{{ $type }}">
                                    {{ ucfirst($type) }}
                                </h4>
                                <ul class="changelog__list">
                                    @foreach($entry[$type] ?? $entry['changes'][$type] as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                @else
                    <p>{{ $entry }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

<style>
.changelog__entry {
    @apply border-b border-gray-200 pb-4 mb-4 last:border-0 last:mb-0 last:pb-0;
}

.changelog__header {
    @apply flex items-center gap-2 mb-2;
}

.changelog__version {
    @apply font-semibold text-gray-900;
}

.changelog__date {
    @apply text-sm text-gray-500;
}

.changelog__badge {
    @apply text-xs px-2 py-0.5 rounded-full font-medium;
}

.changelog__badge--security {
    @apply bg-red-100 text-red-700;
}

.changelog__badge--breaking {
    @apply bg-amber-100 text-amber-700;
}

.changelog__section-title {
    @apply text-sm font-medium mb-1;
}

.changelog__section-title--added { @apply text-green-600; }
.changelog__section-title--changed { @apply text-blue-600; }
.changelog__section-title--fixed { @apply text-purple-600; }
.changelog__section-title--removed { @apply text-red-600; }
.changelog__section-title--deprecated { @apply text-amber-600; }
.changelog__section-title--security { @apply text-red-600; }

.changelog__list {
    @apply list-disc list-inside text-sm text-gray-600 space-y-1;
}
</style>
```

---

## JavaScript Utilities

### Plugin Actions

```javascript
// resources/js/admin/plugins.js

window.PluginManager = {
    // Activate plugin
    async activate(slug) {
        try {
            const response = await fetch(`/api/v1/admin/plugins/${slug}/activate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('success', 'Plugin activated successfully');
                this.refreshPluginRow(slug);
            } else {
                this.showNotification('error', data.error.message);
            }
        } catch (error) {
            this.showNotification('error', 'Failed to activate plugin');
        }
    },
    
    // Deactivate plugin
    async deactivate(slug) {
        try {
            const response = await fetch(`/api/v1/admin/plugins/${slug}/deactivate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('success', 'Plugin deactivated successfully');
                this.refreshPluginRow(slug);
            } else {
                this.showNotification('error', data.error.message);
            }
        } catch (error) {
            this.showNotification('error', 'Failed to deactivate plugin');
        }
    },
    
    // Install plugin
    install(slug, options = {}) {
        // Open installation modal
        const modal = document.getElementById('install-modal');
        modal.dataset.slug = slug;
        modal.classList.remove('hidden');
        
        // Start installation
        this.startInstallation(slug, options);
    },
    
    async startInstallation(slug, options) {
        try {
            const response = await fetch('/api/v1/admin/plugins/install', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    source: 'marketplace',
                    slug: slug,
                    activate: options.activate ?? true,
                    license_key: options.licenseKey ?? null,
                }),
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Installation will be tracked via SSE
                this.trackInstallation(data.job_id);
            } else {
                this.showNotification('error', data.error.message);
            }
        } catch (error) {
            this.showNotification('error', 'Failed to start installation');
        }
    },
    
    // Confirm uninstall
    confirmUninstall(slug, name) {
        if (confirm(`Are you sure you want to uninstall "${name}"? This action cannot be undone.`)) {
            this.uninstall(slug);
        }
    },
    
    async uninstall(slug) {
        try {
            const response = await fetch(`/api/v1/admin/plugins/${slug}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('success', 'Plugin uninstalled successfully');
                document.querySelector(`[data-plugin="${slug}"]`)?.remove();
            } else {
                this.showNotification('error', data.error.message);
            }
        } catch (error) {
            this.showNotification('error', 'Failed to uninstall plugin');
        }
    },
    
    // Helper methods
    showNotification(type, message) {
        // Use your notification system
        window.dispatchEvent(new CustomEvent('notify', { 
            detail: { type, message } 
        }));
    },
    
    refreshPluginRow(slug) {
        // Refresh the plugin row via AJAX or page reload
        window.location.reload();
    },
};

// Global shortcut functions
window.activatePlugin = (slug) => PluginManager.activate(slug);
window.deactivatePlugin = (slug) => PluginManager.deactivate(slug);
window.installPlugin = (slug) => PluginManager.install(slug);
window.updatePlugin = (slug) => PluginManager.install(slug, { update: true });
window.confirmUninstall = (slug, name) => PluginManager.confirmUninstall(slug, name);
```

---

## Livewire Components (Alternative)

### PluginList Livewire Component

```php
<?php

namespace App\Livewire\Admin\Plugins;

use App\Models\Plugin;
use Livewire\Component;
use Livewire\WithPagination;

class PluginList extends Component
{
    use WithPagination;
    
    public string $search = '';
    public string $status = '';
    public string $category = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public array $selected = [];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'category' => ['except' => ''],
    ];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function activate(int $id)
    {
        $plugin = Plugin::findOrFail($id);
        
        if ($plugin->canActivate()) {
            app('plugin.manager')->activate($plugin);
            $this->dispatch('notify', type: 'success', message: 'Plugin activated');
        }
    }
    
    public function deactivate(int $id)
    {
        $plugin = Plugin::findOrFail($id);
        
        if ($plugin->canDeactivate()) {
            app('plugin.manager')->deactivate($plugin);
            $this->dispatch('notify', type: 'success', message: 'Plugin deactivated');
        }
    }
    
    public function bulkAction(string $action)
    {
        $plugins = Plugin::whereIn('id', $this->selected)->get();
        
        foreach ($plugins as $plugin) {
            match ($action) {
                'activate' => $plugin->canActivate() && app('plugin.manager')->activate($plugin),
                'deactivate' => $plugin->canDeactivate() && app('plugin.manager')->deactivate($plugin),
                default => null,
            };
        }
        
        $this->selected = [];
        $this->dispatch('notify', type: 'success', message: 'Bulk action completed');
    }
    
    public function render()
    {
        $plugins = Plugin::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->category, fn($q) => $q->where('category', $this->category))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(20);
        
        return view('livewire.admin.plugins.list', [
            'plugins' => $plugins,
            'categories' => Plugin::distinct('category')->pluck('category'),
            'stats' => [
                'total' => Plugin::count(),
                'active' => Plugin::active()->count(),
                'updates' => Plugin::hasUpdate()->count(),
            ],
        ]);
    }
}
```
