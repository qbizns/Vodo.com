# Vodo Project Agent Instructions

## Overview
Vodo is a Laravel-based admin panel with a custom PJAX SPA architecture. All code must follow the established patterns and use the Vodo framework exclusively - NO external frontend frameworks (React, Vue, Angular, etc.).

---

## Architecture Principles

### Frontend Stack
- **Navigation**: PJAX-based SPA (Single Page Application)
- **Interactivity**: Alpine.js for reactive components
- **API Calls**: `Vodo.api` namespace only
- **Notifications**: `Vodo.notification` system
- **Modals**: `Vodo.modal` for dialogs and confirmations
- **Routing**: `Vodo.pjax.load()` for navigation

### Backend Stack
- **Framework**: Laravel with custom Modules pattern
- **Module Location**: `app/Modules/{ModuleName}/`
- **Services**: Business logic in `app/Services/`
- **Plugins**: Hook-based architecture via `HookManager`

---

## Code Style Guidelines

### PHP

#### Controllers
```php
// Located in: app/Modules/{Module}/Controllers/
// Always return JSON for API endpoints, Views for PJAX pages

public function index(Request $request)
{
    // For PJAX pages
    return view('backend.{section}.index', compact('data'));
}

public function store(Request $request)
{
    // For API endpoints - always return JSON
    return response()->json([
        'success' => true,
        'message' => 'Created successfully',
        'data' => $model,
        'redirect' => route('admin.section.index') // Optional
    ]);
}
```

#### Models
```php
// Use traits for reusable functionality
use SoftDeletes;

// Define relationships
public function parent(): BelongsTo
{
    return $this->belongsTo(self::class, 'parent_id');
}

// Use scopes for common queries
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function scopeSearch($query, string $term)
{
    return $query->where('name', 'like', "%{$term}%");
}

// Use accessors for computed properties
public function getDisplayNameAttribute(): string
{
    return $this->name . ' (' . $this->slug . ')';
}
```

#### Services
```php
// Located in: app/Services/
// Inject dependencies via constructor
// Use HookManager for extensibility

public function __construct(HookManager $hooks)
{
    $this->hooks = $hooks;
}

// Apply plugin filters
$data = $this->hooks->applyFilters('filter_name', $data);

// Execute plugin actions
$this->hooks->doAction('action_name', $context);
```

### Blade Templates

#### Page Structure
```blade
{{-- Always extend the PJAX layout --}}
@extends('backend.layouts.pjax')

@section('title', 'Page Title')
@section('page-id', 'section/page-name')
@section('require-css', 'page-name') {{-- Loads /backend/css/pages/{name}.css --}}

@section('header', 'Page Header')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.section.create') }}" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>Create New</span>
    </a>
</div>
@endsection

@section('content')
{{-- Page content here --}}
@endsection
```

#### Alpine.js Components
```blade
{{-- Define Alpine component with x-data --}}
<div x-data="componentName(@json($serverData))">
    {{-- Use x-model for two-way binding --}}
    <input type="text" x-model="searchQuery">

    {{-- Use x-show for conditional display --}}
    <div x-show="isVisible" x-cloak>Content</div>

    {{-- Use x-for for lists --}}
    <template x-for="item in items" :key="item.id">
        <div x-text="item.name"></div>
    </template>

    {{-- Use @click for events --}}
    <button @click="handleAction">Action</button>
</div>

<script>
function componentName(serverData) {
    return {
        // State
        searchQuery: '',
        items: serverData.items || [],
        isVisible: false,

        // Lifecycle
        init() {
            // Called when component initializes
        },

        // Methods
        async handleAction() {
            // Use Vodo.api for AJAX calls
            try {
                const response = await Vodo.api.post('/api/endpoint', {
                    data: this.searchQuery
                });

                if (response.success) {
                    Vodo.notification.success(response.message);
                }
            } catch (error) {
                Vodo.notification.error(error.message);
            }
        }
    };
}
</script>
```

### JavaScript

#### NEVER Use
- `fetch()` directly - use `Vodo.api.get/post/put/delete()`
- `alert()` / `confirm()` - use `Vodo.notification` / `Vodo.modal`
- `window.location` for navigation - use `Vodo.pjax.load()`
- External frameworks (React, Vue, jQuery, etc.)

#### Always Use
```javascript
// API Calls
Vodo.api.get(url).then(response => { });
Vodo.api.post(url, data).then(response => { });
Vodo.api.put(url, data).then(response => { });
Vodo.api.delete(url).then(response => { });

// Notifications
Vodo.notification.success('Success message');
Vodo.notification.error('Error message');
Vodo.notification.warning('Warning message');
Vodo.notification.info('Info message');

// Modals
Vodo.modal.confirm({
    title: 'Confirm Action',
    message: 'Are you sure?',
    confirmText: 'Yes',
    confirmClass: 'btn-danger',
    onConfirm: () => { /* action */ }
});

// Navigation
Vodo.pjax.load('/admin/section');

// Form Submission Pattern
document.getElementById('myForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    Vodo.api.post(this.action, data)
        .then(response => {
            if (response.success) {
                Vodo.notification.success(response.message);
                if (response.redirect) {
                    Vodo.pjax.load(response.redirect);
                }
            }
        })
        .catch(error => {
            Vodo.notification.error(error.message);
        });
});
```

### CSS

#### File Location
- Page-specific CSS: `public/backend/css/pages/{page-name}.css`
- Global styles: `public/backend/css/style.css`

#### Naming Conventions
```css
/* Component blocks */
.component-name { }
.component-name__element { }
.component-name--modifier { }

/* State classes */
.is-active { }
.is-loading { }
.has-error { }

/* Use CSS custom properties */
.my-component {
    background: var(--bg-surface-1, #fff);
    color: var(--text-primary, #1f2937);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 8px;
    padding: var(--spacing-4, 16px);
}
```

#### Common Variables
```css
/* Colors */
--primary: #6366f1;
--bg-surface-1: #fff;
--bg-surface-2: #f9fafb;
--text-primary: #1f2937;
--text-secondary: #6b7280;
--text-tertiary: #9ca3af;
--border-color: #e5e7eb;

/* Spacing */
--spacing-1: 4px;
--spacing-2: 8px;
--spacing-3: 12px;
--spacing-4: 16px;
--spacing-6: 24px;
```

---

## Routes

### Convention
```php
// In: app/Modules/{Module}/routes.php

// Resource routes with prefix
Route::prefix('section')->name('section.')->group(function () {
    Route::get('/', [Controller::class, 'index'])->name('index');
    Route::get('/create', [Controller::class, 'create'])->name('create');
    Route::post('/', [Controller::class, 'store'])->name('store');
    Route::get('/{id}', [Controller::class, 'show'])->name('show');
    Route::get('/{id}/edit', [Controller::class, 'edit'])->name('edit');
    Route::put('/{id}', [Controller::class, 'update'])->name('update');
    Route::delete('/{id}', [Controller::class, 'destroy'])->name('destroy');
});

// API routes
Route::prefix('api')->group(function () {
    Route::get('/data', [Controller::class, 'apiMethod']);
});
```

---

## Navigation

### Adding Menu Items
```php
// In: app/Services/NavigationService.php -> getBaseNavGroups()

[
    'id' => 'unique-id',
    'icon' => 'iconName',        // From icon set
    'label' => 'Menu Label',
    'url' => '/admin/path',
    'children' => [              // Optional sub-items
        ['id' => 'child', 'label' => 'Child Item', 'url' => '/admin/path/child'],
    ]
]
```

---

## Plugins / Hooks

### Registering Hooks
```php
// In plugin's register() method
$this->hooks->addFilter('filter_name', function ($data) {
    // Modify and return $data
    return $data;
}, priority: 10);

$this->hooks->addAction('action_name', function ($context) {
    // Perform action
}, priority: 10);
```

### Common Hooks
- `navigation_items` - Modify sidebar navigation
- `permission_registered` - After permission is registered
- `plugin_enabled` / `plugin_disabled` - Plugin lifecycle

---

## Database

### Migrations
```php
// Naming: {date}_create_{table}_table.php or {date}_add_{feature}_to_{table}.php

Schema::create('table_name', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
});
```

---

## Icons

### Usage
```blade
@include('backend.partials.icon', ['icon' => 'iconName'])
```

### Common Icons
`shield`, `users`, `user`, `settings`, `plus`, `edit`, `trash`, `check`, `x`, `search`, `filter`, `download`, `upload`, `arrowLeft`, `arrowRight`, `chevronDown`, `chevronUp`, `eye`, `lock`, `unlock`, `key`, `folder`, `file`, `save`, `copy`, `link`, `info`, `alertTriangle`, `checkCircle`

---

## Form Components

### Standard Form Structure
```blade
<div class="form-group">
    <label for="field" class="form-label required">Label</label>
    <input type="text" id="field" name="field" class="form-input" required>
    <span class="form-hint">Help text</span>
    @error('field')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label class="checkbox-label">
        <input type="checkbox" name="option" value="1">
        <span>Checkbox label</span>
    </label>
</div>

<div class="form-actions">
    <a href="{{ route('admin.section.index') }}" class="btn-secondary">Cancel</a>
    <button type="submit" class="btn-primary">Save</button>
</div>
```

---

## Common Patterns

### Data Tables
```blade
<div class="data-table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Column</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td class="text-right">
                    <div class="actions-dropdown">
                        {{-- Action menu --}}
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

### Cards
```blade
<div class="card">
    <div class="card-header">
        <h3>Card Title</h3>
        <div class="card-header-actions">
            {{-- Header actions --}}
        </div>
    </div>
    <div class="card-body">
        {{-- Card content --}}
    </div>
</div>
```

### Empty States
```blade
<div class="empty-state">
    <div class="empty-state-icon">
        @include('backend.partials.icon', ['icon' => 'inbox'])
    </div>
    <h3>No Items Found</h3>
    <p>Description text here.</p>
    <a href="{{ route('admin.section.create') }}" class="btn-primary mt-4">
        Create First Item
    </a>
</div>
```

---

## Testing Checklist

Before committing, ensure:
- [ ] No `fetch()`, `alert()`, `confirm()` used directly
- [ ] All navigation uses `Vodo.pjax.load()`
- [ ] All API calls use `Vodo.api.*`
- [ ] Views extend `backend.layouts.pjax`
- [ ] CSS in `public/backend/css/pages/`
- [ ] Forms submit via JavaScript with `e.preventDefault()`
- [ ] Error handling with `Vodo.notification.error()`
- [ ] JSON responses include `success`, `message` keys
