{{-- 
Extension Area Component

Creates a named area that plugins can modify or replace.
Supports both slot content and XPath-based modifications.

Usage:
<x-extension-area name="user-details">
    <p>Default content here</p>
</x-extension-area>
--}}

@props(['name'])

<div data-extension-area="{{ $name }}" {{ $attributes }}>
    @hasExtensionSlot("{$name}-prepend")
        @extensionSlot("{$name}-prepend")
    @endHasExtensionSlot
    
    {{ $slot }}
    
    @hasExtensionSlot("{$name}-append")
        @extensionSlot("{$name}-append")
    @endHasExtensionSlot
</div>
