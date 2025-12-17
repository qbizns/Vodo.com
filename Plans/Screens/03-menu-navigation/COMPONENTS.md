# Menu & Navigation - UI Components

## Component Structure

```
resources/views/components/navigation/
├── sidebar.blade.php
├── topbar.blade.php
├── mobile-nav.blade.php
├── menu-item.blade.php
├── menu-badge.blade.php
├── breadcrumbs.blade.php
├── quick-links.blade.php
├── user-menu.blade.php
└── icon-picker.blade.php
```

---

## Core Components

### Sidebar Component

```blade
{{-- resources/views/components/navigation/sidebar.blade.php --}}
@props(['menu'])

<aside 
    x-data="sidebarComponent()" 
    x-init="init()"
    :class="{ 
        'sidebar--collapsed': isCollapsed,
        'sidebar--mobile-open': isMobileOpen 
    }"
    class="sidebar"
    @keydown.escape="closeMobile()">
    
    {{-- Logo --}}
    <div class="sidebar__header">
        <a href="{{ route('admin.dashboard') }}" class="sidebar__logo">
            <img :src="isCollapsed ? '{{ asset('images/logo-icon.svg') }}' : '{{ asset('images/logo.svg') }}'" 
                 alt="{{ config('app.name') }}" 
                 class="sidebar__logo-img">
        </a>
        
        <button @click="toggle()" 
                class="sidebar__toggle"
                :aria-label="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <x-icon x-show="!isCollapsed" name="chevrons-left" class="w-5 h-5" />
            <x-icon x-show="isCollapsed" name="chevrons-right" class="w-5 h-5" />
        </button>
    </div>
    
    {{-- Search (optional) --}}
    @if(config('navigation.sidebar.show_search'))
    <div class="sidebar__search" x-show="!isCollapsed">
        <input type="text" 
               x-model="searchQuery"
               placeholder="Search menu..."
               class="sidebar__search-input">
        <x-icon name="search" class="sidebar__search-icon" />
    </div>
    @endif
    
    {{-- Navigation --}}
    <nav class="sidebar__nav" role="navigation" aria-label="Main navigation">
        <ul class="sidebar__menu">
            @foreach($menu as $item)
                <x-navigation.menu-item :item="$item" :collapsed="false" />
            @endforeach
        </ul>
    </nav>
    
    {{-- Footer --}}
    <div class="sidebar__footer">
        <x-navigation.user-menu :collapsed="false" />
    </div>
</aside>

{{-- Mobile Overlay --}}
<div x-show="isMobileOpen" 
     x-transition.opacity
     @click="closeMobile()"
     class="sidebar__overlay lg:hidden"></div>

<script>
function sidebarComponent() {
    return {
        isCollapsed: Alpine.$persist(false).as('sidebar_collapsed'),
        isMobileOpen: false,
        searchQuery: '',
        expandedItems: Alpine.$persist([]).as('sidebar_expanded'),
        
        init() {
            // Listen for mobile toggle event
            window.addEventListener('toggle-mobile-nav', () => {
                this.isMobileOpen = !this.isMobileOpen;
            });
            
            // Auto-expand active section
            if ({{ config('navigation.auto_expand_active') ? 'true' : 'false' }}) {
                this.expandActiveSection();
            }
        },
        
        toggle() {
            this.isCollapsed = !this.isCollapsed;
            this.$dispatch('sidebar-toggled', { collapsed: this.isCollapsed });
        },
        
        closeMobile() {
            this.isMobileOpen = false;
        },
        
        isExpanded(key) {
            return this.expandedItems.includes(key);
        },
        
        toggleExpand(key) {
            const index = this.expandedItems.indexOf(key);
            
            if ({{ config('navigation.accordion_mode') ? 'true' : 'false' }}) {
                // Accordion mode: only one open at a time
                this.expandedItems = index > -1 ? [] : [key];
            } else {
                if (index > -1) {
                    this.expandedItems.splice(index, 1);
                } else {
                    this.expandedItems.push(key);
                }
            }
        },
        
        expandActiveSection() {
            const activeItem = document.querySelector('.menu-item--active');
            if (activeItem) {
                const parent = activeItem.closest('[data-menu-key]');
                if (parent) {
                    this.expandedItems.push(parent.dataset.menuKey);
                }
            }
        },
    };
}
</script>
```

### Menu Item Component

```blade
{{-- resources/views/components/navigation/menu-item.blade.php --}}
@props(['item', 'level' => 0, 'collapsed' => false])

@php
    $hasChildren = !empty($item['children']);
    $isActive = request()->routeIs($item['route'] ?? '') || ($item['is_active'] ?? false);
    $isDivider = ($item['type'] ?? '') === 'divider';
@endphp

<li class="menu-item menu-item--level-{{ $level }}" 
    data-menu-key="{{ $item['key'] }}">
    
    @if($isDivider)
        <hr class="menu-item__divider">
    @elseif($hasChildren)
        {{-- Parent item with children --}}
        <div x-data="{ open: $parent.isExpanded('{{ $item['key'] }}') }"
             @click="$parent.toggleExpand('{{ $item['key'] }}')"
             class="menu-item__parent {{ $isActive ? 'menu-item--active' : '' }}">
            
            <button class="menu-item__link">
                <x-icon :name="$item['icon'] ?? 'circle'" class="menu-item__icon" />
                
                <span class="menu-item__label" x-show="!$parent.isCollapsed">
                    {{ $item['label'] }}
                </span>
                
                @if($item['badge'] ?? null)
                    <x-navigation.menu-badge 
                        :count="$item['badge']" 
                        :color="$item['badge_color'] ?? 'primary'" />
                @endif
                
                <x-icon name="chevron-down" 
                        class="menu-item__chevron"
                        x-show="!$parent.isCollapsed"
                        :class="{ 'rotate-180': open }" />
            </button>
            
            {{-- Children --}}
            <ul x-show="open && !$parent.isCollapsed" 
                x-collapse
                class="menu-item__children">
                @foreach($item['children'] as $child)
                    <x-navigation.menu-item :item="$child" :level="$level + 1" />
                @endforeach
            </ul>
        </div>
    @else
        {{-- Single item --}}
        <a href="{{ $item['url'] }}" 
           class="menu-item__link {{ $isActive ? 'menu-item--active' : '' }}"
           @if($item['new_tab'] ?? false) target="_blank" rel="noopener" @endif
           @if($collapsed) title="{{ $item['label'] }}" @endif>
            
            <x-icon :name="$item['icon'] ?? 'circle'" class="menu-item__icon" />
            
            <span class="menu-item__label" x-show="!$parent.isCollapsed">
                {{ $item['label'] }}
            </span>
            
            @if($item['badge'] ?? null)
                <x-navigation.menu-badge 
                    :count="$item['badge']" 
                    :color="$item['badge_color'] ?? 'primary'" />
            @endif
            
            @if($item['new_tab'] ?? false)
                <x-icon name="external-link" class="menu-item__external" x-show="!$parent.isCollapsed" />
            @endif
        </a>
    @endif
</li>
```

### Menu Badge Component

```blade
{{-- resources/views/components/navigation/menu-badge.blade.php --}}
@props(['count', 'color' => 'primary', 'max' => 99])

@php
    $displayCount = $count > $max ? "{$max}+" : $count;
    $colorClasses = [
        'primary' => 'bg-primary-500 text-white',
        'red' => 'bg-red-500 text-white',
        'amber' => 'bg-amber-500 text-white',
        'green' => 'bg-green-500 text-white',
        'gray' => 'bg-gray-500 text-white',
    ];
@endphp

@if($count > 0)
    <span class="menu-badge {{ $colorClasses[$color] ?? $colorClasses['primary'] }}">
        {{ $displayCount }}
    </span>
@endif

<style>
.menu-badge {
    @apply inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-medium rounded-full;
}
</style>
```

### Topbar Component

```blade
{{-- resources/views/components/navigation/topbar.blade.php --}}
@props(['quickLinks' => []])

<header class="topbar">
    <div class="topbar__left">
        {{-- Mobile menu toggle --}}
        <button @click="$dispatch('toggle-mobile-nav')" 
                class="topbar__mobile-toggle lg:hidden">
            <x-icon name="menu" class="w-6 h-6" />
        </button>
        
        {{-- Breadcrumbs --}}
        @if(config('navigation.show_breadcrumbs'))
            <x-navigation.breadcrumbs :items="$breadcrumbs ?? []" />
        @endif
    </div>
    
    <div class="topbar__center">
        {{-- Quick Links --}}
        @if(config('navigation.enable_quick_links') && count($quickLinks))
            <x-navigation.quick-links :links="$quickLinks" />
        @endif
    </div>
    
    <div class="topbar__right">
        {{-- Global Search --}}
        <button @click="$dispatch('open-search')" class="topbar__search-btn">
            <x-icon name="search" class="w-5 h-5" />
            <span class="hidden md:inline">Search</span>
            <kbd class="hidden md:inline">⌘K</kbd>
        </button>
        
        {{-- Notifications --}}
        <x-navigation.notifications-dropdown />
        
        {{-- User Menu --}}
        <x-navigation.user-dropdown />
    </div>
</header>
```

### Mobile Navigation

```blade
{{-- resources/views/components/navigation/mobile-nav.blade.php --}}
@props(['menu'])

<div x-data="{ open: false }" 
     @toggle-mobile-nav.window="open = !open"
     class="mobile-nav lg:hidden">
    
    {{-- Overlay --}}
    <div x-show="open" 
         x-transition.opacity
         @click="open = false"
         class="mobile-nav__overlay"></div>
    
    {{-- Drawer --}}
    <div x-show="open"
         x-transition:enter="transform transition-transform duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition-transform duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="mobile-nav__drawer">
        
        <div class="mobile-nav__header">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="h-8">
            <button @click="open = false" class="mobile-nav__close">
                <x-icon name="x" class="w-6 h-6" />
            </button>
        </div>
        
        <nav class="mobile-nav__content">
            <ul class="mobile-nav__menu">
                @foreach($menu as $item)
                    <x-navigation.mobile-menu-item :item="$item" />
                @endforeach
            </ul>
        </nav>
        
        <div class="mobile-nav__footer">
            <x-navigation.user-menu :mobile="true" />
        </div>
    </div>
</div>
```

### Icon Picker Component

```blade
{{-- resources/views/components/navigation/icon-picker.blade.php --}}
@props(['selected' => null, 'name' => 'icon'])

<div x-data="iconPicker('{{ $selected }}')" class="icon-picker">
    <div class="icon-picker__selected" @click="open = true">
        <template x-if="selectedIcon">
            <span class="icon-picker__preview">
                <x-dynamic-icon x-bind:name="selectedIcon" class="w-5 h-5" />
                <span x-text="selectedIcon"></span>
            </span>
        </template>
        <template x-if="!selectedIcon">
            <span class="icon-picker__placeholder">Select icon...</span>
        </template>
        <button type="button" class="icon-picker__browse">Browse</button>
    </div>
    
    <input type="hidden" :name="'{{ $name }}'" :value="selectedIcon">
    
    {{-- Modal --}}
    <div x-show="open" 
         x-transition
         @click.away="open = false"
         @keydown.escape="open = false"
         class="icon-picker__modal">
        
        <div class="icon-picker__header">
            <input type="text" 
                   x-model="search" 
                   placeholder="Search icons..."
                   class="icon-picker__search">
        </div>
        
        <div class="icon-picker__grid">
            @foreach(config('ui.icons') as $category => $icons)
                <div class="icon-picker__category" x-show="categoryMatches('{{ $category }}')">
                    <h4 class="icon-picker__category-title">{{ $category }}</h4>
                    <div class="icon-picker__icons">
                        @foreach($icons as $icon)
                            <button type="button"
                                    @click="select('{{ $icon }}')"
                                    x-show="iconMatches('{{ $icon }}')"
                                    :class="{ 'selected': selectedIcon === '{{ $icon }}' }"
                                    class="icon-picker__icon"
                                    title="{{ $icon }}">
                                <x-icon :name="$icon" class="w-5 h-5" />
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
function iconPicker(initial) {
    return {
        open: false,
        search: '',
        selectedIcon: initial,
        
        select(icon) {
            this.selectedIcon = icon;
            this.open = false;
            this.$dispatch('icon-selected', { icon });
        },
        
        iconMatches(icon) {
            if (!this.search) return true;
            return icon.toLowerCase().includes(this.search.toLowerCase());
        },
        
        categoryMatches(category) {
            if (!this.search) return true;
            return category.toLowerCase().includes(this.search.toLowerCase());
        },
    };
}
</script>
```

### Menu Builder Draggable Tree

```blade
{{-- resources/views/components/navigation/menu-builder-tree.blade.php --}}
@props(['items'])

<div x-data="menuBuilder(@js($items))" class="menu-builder">
    <div class="menu-builder__toolbar">
        <button @click="addItem()" class="btn btn-primary btn-sm">
            <x-icon name="plus" class="w-4 h-4" /> Add Item
        </button>
        <button @click="addDivider()" class="btn btn-secondary btn-sm">
            <x-icon name="minus" class="w-4 h-4" /> Add Divider
        </button>
        <button @click="collapseAll()" class="btn btn-secondary btn-sm">
            Collapse All
        </button>
    </div>
    
    <ul x-ref="sortable" class="menu-builder__list">
        <template x-for="item in items" :key="item.id">
            <li class="menu-builder__item"
                :data-id="item.id"
                :class="{ 'menu-builder__item--system': item.is_system }">
                
                <div class="menu-builder__item-row">
                    <span class="menu-builder__handle">≡</span>
                    
                    <span class="menu-builder__icon">
                        <x-dynamic-icon x-bind:name="item.icon || 'circle'" class="w-4 h-4" />
                    </span>
                    
                    <span class="menu-builder__label" x-text="item.label"></span>
                    
                    <span class="menu-builder__meta">
                        <span x-show="item.plugin" class="badge badge-gray" x-text="item.plugin"></span>
                        <span x-show="item.is_system" class="badge badge-amber">System</span>
                    </span>
                    
                    <span x-show="item.badge" 
                          class="menu-builder__badge"
                          :class="'badge-' + (item.badge_color || 'primary')"
                          x-text="item.badge"></span>
                    
                    <div class="menu-builder__actions">
                        <button @click="editItem(item)" class="btn-icon" title="Edit">
                            <x-icon name="edit" class="w-4 h-4" />
                        </button>
                        <button @click="deleteItem(item)" 
                                x-show="!item.is_system"
                                class="btn-icon text-red-500" 
                                title="Delete">
                            <x-icon name="trash-2" class="w-4 h-4" />
                        </button>
                    </div>
                </div>
                
                {{-- Children --}}
                <ul x-show="item.children && item.children.length" class="menu-builder__children">
                    <template x-for="child in item.children" :key="child.id">
                        {{-- Recursive child items --}}
                    </template>
                </ul>
            </li>
        </template>
    </ul>
    
    <div class="menu-builder__footer">
        <button @click="saveChanges()" class="btn btn-primary">
            Save Changes
        </button>
    </div>
</div>

<script>
function menuBuilder(initialItems) {
    return {
        items: initialItems,
        sortable: null,
        
        init() {
            this.sortable = new Sortable(this.$refs.sortable, {
                handle: '.menu-builder__handle',
                animation: 150,
                group: 'menu',
                onEnd: (evt) => this.handleReorder(evt),
            });
        },
        
        handleReorder(evt) {
            // Update positions based on new order
            const newOrder = [...this.$refs.sortable.children].map((el, index) => ({
                id: parseInt(el.dataset.id),
                position: (index + 1) * 10,
            }));
            this.$dispatch('menu-reordered', { order: newOrder });
        },
        
        addItem() {
            this.$dispatch('open-modal', 'menu-item-editor');
        },
        
        addDivider() {
            this.items.push({
                id: Date.now(),
                type: 'divider',
                label: '',
                position: this.items.length * 10 + 10,
            });
        },
        
        editItem(item) {
            this.$dispatch('open-modal', { name: 'menu-item-editor', data: item });
        },
        
        deleteItem(item) {
            if (confirm('Delete this menu item?')) {
                this.items = this.items.filter(i => i.id !== item.id);
            }
        },
        
        async saveChanges() {
            const response = await fetch('/api/v1/admin/menus/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: this.items }),
            });
            
            if (response.ok) {
                this.$dispatch('notify', { message: 'Menu saved successfully' });
            }
        },
    };
}
</script>
```

---

## Tailwind Styles

```css
/* Sidebar */
.sidebar {
    @apply fixed left-0 top-0 h-screen bg-gray-900 text-white flex flex-col z-40;
    @apply w-64 transition-all duration-300;
}
.sidebar--collapsed { @apply w-16; }

.sidebar__nav { @apply flex-1 overflow-y-auto py-4; }
.sidebar__menu { @apply space-y-1 px-3; }

/* Menu Item */
.menu-item__link {
    @apply flex items-center gap-3 px-3 py-2 rounded-lg text-gray-300;
    @apply hover:bg-gray-800 hover:text-white transition-colors;
}
.menu-item--active > .menu-item__link {
    @apply bg-primary-600 text-white;
}
.menu-item__icon { @apply w-5 h-5 flex-shrink-0; }
.menu-item__label { @apply flex-1 truncate; }
.menu-item__chevron { @apply w-4 h-4 transition-transform; }
.menu-item__children { @apply ml-6 mt-1 space-y-1; }
.menu-item__divider { @apply my-3 border-gray-700; }

/* Topbar */
.topbar {
    @apply sticky top-0 h-16 bg-white border-b border-gray-200;
    @apply flex items-center justify-between px-4 z-30;
}
```
