{{-- 
Extension Point Component

Creates a placeholder that plugins can target with XPath.
Also renders any slot content registered for this point.

Usage:
<x-extension-point name="before-form" />
<x-extension-point name="sidebar-widget" :data="['user' => $user]" />
--}}

@props(['name', 'data' => []])

<div data-extension-point="{{ $name }}" {{ $attributes }}>
    @extensionSlot($name)
    {{ $slot }}
</div>
