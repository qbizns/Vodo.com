# Plugin System - Laravel Coding Guidelines & Architecture Standards

## Document Version
- **Version**: 1.0.0
- **Last Updated**: December 2024
- **Scope**: 30 Plugin Modules Implementation

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Directory Structure](#2-directory-structure)
3. [Naming Conventions](#3-naming-conventions)
4. [Controller Standards](#4-controller-standards)
5. [Service Layer Patterns](#5-service-layer-patterns)
6. [Model Standards](#6-model-standards)
7. [Request Validation](#7-request-validation)
8. [View & Blade Standards](#8-view--blade-standards)
9. [Route Definitions](#9-route-definitions)
10. [API Design](#10-api-design)
11. [Database & Migrations](#11-database--migrations)
12. [Security Standards](#12-security-standards)
13. [Error Handling](#13-error-handling)
14. [Testing Requirements](#14-testing-requirements)
15. [Performance Guidelines](#15-performance-guidelines)
16. [Plugin Extension Patterns](#16-plugin-extension-patterns)

---

## 1. Architecture Overview

### 1.1 System Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           Request Layer                                  │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │ HTTP Request → Middleware Stack → Route → Controller             │   │
│  └─────────────────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────────────────┤
│                          Application Layer                               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │
│  │ Controllers │  │  Services   │  │   Actions   │  │    DTOs     │   │
│  │ (HTTP only) │  │ (Business)  │  │ (Single Op) │  │ (Transfer)  │   │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │
├─────────────────────────────────────────────────────────────────────────┤
│                           Domain Layer                                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │
│  │   Models    │  │   Traits    │  │   Scopes    │  │   Events    │   │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │
├─────────────────────────────────────────────────────────────────────────┤
│                        Infrastructure Layer                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │
│  │ Repositories│  │   Caching   │  │   Queues    │  │  External   │   │
│  │ (optional)  │  │   Service   │  │    Jobs     │  │    APIs     │   │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Module Types

| Type | Location | Purpose |
|------|----------|---------|
| **Core Modules** | `app/Modules/{Module}` | Admin, Console, Owner, ClientArea, Frontend |
| **Plugin Modules** | `app/Plugins/{plugin-slug}` | Installable feature extensions |
| **Shared Services** | `app/Services/` | Cross-cutting business logic |

### 1.3 Key Architectural Decisions

```
✅ DO:
- Use constructor dependency injection
- Keep controllers thin (HTTP concerns only)
- Put business logic in Services
- Use Traits for reusable model behaviors
- Use Contracts (interfaces) for swappable implementations
- Use Events for decoupled cross-module communication

❌ DON'T:
- Put business logic in controllers
- Use facades in domain layer
- Create god classes with too many responsibilities
- Mix HTTP and business concerns
- Use static methods for stateful operations
```

---

## 2. Directory Structure

### 2.1 Plugin Directory Structure

```
app/Plugins/{plugin-slug}/
├── {PluginName}Plugin.php      # Main plugin class (extends BasePlugin)
├── plugin.json                  # Plugin manifest
├── routes.php                   # Plugin routes
├── migrations/                  # Database migrations
│   └── YYYY_MM_DD_HHMMSS_*.php
├── src/
│   ├── Controllers/
│   │   └── {Feature}Controller.php
│   ├── Models/
│   │   └── {Entity}.php
│   ├── Services/
│   │   └── {Feature}Service.php
│   ├── Actions/                 # Single-purpose action classes
│   │   └── {Verb}{Noun}Action.php
│   ├── DTOs/                    # Data Transfer Objects
│   │   └── {Name}Data.php
│   ├── Events/
│   │   └── {Entity}{Action}Event.php
│   ├── Listeners/
│   │   └── {Action}Listener.php
│   ├── Jobs/
│   │   └── {Action}Job.php
│   ├── Requests/
│   │   └── {Action}{Entity}Request.php
│   └── Resources/               # API Resources
│       └── {Entity}Resource.php
└── Views/
    ├── layouts/
    │   └── app.blade.php
    ├── partials/
    │   └── *.blade.php
    ├── index.blade.php
    └── {feature}.blade.php
```

### 2.2 Shared Services Structure

```
app/Services/
├── {Domain}/
│   ├── {Domain}Service.php      # Main service
│   ├── Contracts/
│   │   └── {Domain}Contract.php # Interface
│   ├── Handlers/                # Strategy pattern handlers
│   │   └── {Type}Handler.php
│   └── Exceptions/
│       └── {Domain}Exception.php
```

---

## 3. Naming Conventions

### 3.1 PHP Naming

```php
// Classes: PascalCase
class UserPermissionService {}
class CreateOrderAction {}
class OrderCreatedEvent {}

// Methods: camelCase
public function getUserPermissions(): array {}
public function calculateTotalPrice(): float {}

// Properties: camelCase
protected string $connectionName;
private array $cachedResults = [];

// Constants: SCREAMING_SNAKE_CASE
public const STATUS_ACTIVE = 'active';
public const MAX_RETRY_ATTEMPTS = 3;

// Database columns: snake_case
protected $fillable = ['user_id', 'created_at', 'is_active'];

// Route names: dot.separated.lowercase
Route::get('/users', ...)->name('console.users.index');

// Config keys: snake_case
config('plugin.max_upload_size');

// Event names: PascalCase{Entity}{Action}
class OrderCreated extends Event {}
class UserPermissionUpdated extends Event {}
```

### 3.2 File Naming

```
Controllers:     {Entity}Controller.php      → UserController.php
Services:        {Domain}Service.php         → PaymentService.php
Actions:         {Verb}{Noun}Action.php      → CreateOrderAction.php
Requests:        {Action}{Entity}Request.php → StoreUserRequest.php
Resources:       {Entity}Resource.php        → OrderResource.php
Events:          {Entity}{Action}Event.php   → OrderCreatedEvent.php
Jobs:            {Action}{Entity}Job.php     → ProcessPaymentJob.php
Migrations:      YYYY_MM_DD_HHMMSS_*.php     → 2024_01_15_create_orders_table.php
```

### 3.3 Database Naming

```sql
-- Tables: plural, snake_case
users, order_items, user_permissions

-- Pivot tables: singular, alphabetical order
permission_role, order_product

-- Columns: snake_case
user_id, created_at, is_verified, total_amount

-- Foreign keys: singular_table_id
user_id, order_id, parent_category_id

-- Indexes: table_columns_index
idx_orders_user_id, idx_orders_status_created

-- Unique constraints: table_columns_unique
unq_users_email, unq_orders_reference
```

---

## 4. Controller Standards

### 4.1 Controller Template

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\{plugin_slug}\Services\{Feature}Service;
use App\Plugins\{plugin_slug}\Requests\Store{Entity}Request;
use App\Plugins\{plugin_slug}\Requests\Update{Entity}Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles HTTP requests for {Entity} management.
 * 
 * Controllers should:
 * - Only handle HTTP concerns (request/response)
 * - Delegate business logic to services
 * - Return appropriate response types
 */
class {Entity}Controller extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        protected {Feature}Service $service
    ) {}

    /**
     * Display listing.
     */
    public function index(Request $request): View
    {
        $items = $this->service->paginate(
            perPage: $request->integer('per_page', 15),
            filters: $request->only(['status', 'search', 'sort'])
        );

        return view('{plugin-slug}::index', [
            'items' => $items,
            'currentPage' => '{feature}',
            'currentPageLabel' => '{Feature}',
            'currentPageIcon' => 'list',
        ]);
    }

    /**
     * Display single resource.
     */
    public function show(int $id): View
    {
        $item = $this->service->findOrFail($id);

        return view('{plugin-slug}::show', [
            'item' => $item,
        ]);
    }

    /**
     * Store new resource.
     */
    public function store(Store{Entity}Request $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => '{Entity} created successfully.',
            'data' => $item,
        ], 201);
    }

    /**
     * Update existing resource.
     */
    public function update(Update{Entity}Request $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => '{Entity} updated successfully.',
            'data' => $item,
        ]);
    }

    /**
     * Delete resource.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => '{Entity} deleted successfully.',
        ]);
    }
}
```

### 4.2 Controller Rules

```php
// ✅ CORRECT: Thin controller, delegates to service
public function store(StoreOrderRequest $request): JsonResponse
{
    $order = $this->orderService->create($request->validated());
    
    return response()->json([
        'success' => true,
        'data' => new OrderResource($order),
    ], 201);
}

// ❌ WRONG: Fat controller with business logic
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([...]);
    
    $order = new Order();
    $order->user_id = auth()->id();
    $order->total = 0;
    
    foreach ($request->items as $item) {
        $product = Product::find($item['product_id']);
        $order->total += $product->price * $item['quantity'];
        // ... more logic
    }
    
    $order->save();
    
    // Send notifications, update inventory, etc...
}
```

### 4.3 Response Consistency

```php
// Success responses
return response()->json([
    'success' => true,
    'message' => 'Operation completed.',
    'data' => $data,
]);

// Error responses
return response()->json([
    'success' => false,
    'message' => 'Operation failed.',
    'errors' => $errors,
], 422);

// Paginated responses
return response()->json([
    'success' => true,
    'data' => $items->items(),
    'meta' => [
        'current_page' => $items->currentPage(),
        'last_page' => $items->lastPage(),
        'per_page' => $items->perPage(),
        'total' => $items->total(),
    ],
]);
```

---

## 5. Service Layer Patterns

### 5.1 Service Template

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\Services;

use App\Plugins\{plugin_slug}\Models\{Entity};
use App\Plugins\{plugin_slug}\Events\{Entity}Created;
use App\Plugins\{plugin_slug}\Events\{Entity}Updated;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Handles business logic for {Entity} operations.
 *
 * Services should:
 * - Contain all business logic
 * - Handle transactions for multi-step operations
 * - Dispatch events for side effects
 * - Be stateless (no instance state between calls)
 */
class {Feature}Service
{
    /**
     * Cache TTL in seconds.
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get paginated list with filters.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = {Entity}::query();

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortField, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Find by ID or fail.
     */
    public function findOrFail(int $id): {Entity}
    {
        return {Entity}::findOrFail($id);
    }

    /**
     * Create new entity.
     */
    public function create(array $data): {Entity}
    {
        return DB::transaction(function () use ($data) {
            $entity = {Entity}::create($data);

            // Handle relationships
            if (!empty($data['tags'])) {
                $entity->tags()->sync($data['tags']);
            }

            // Dispatch event
            event(new {Entity}Created($entity));

            // Clear relevant caches
            $this->clearCache();

            return $entity->fresh(['tags']);
        });
    }

    /**
     * Update existing entity.
     */
    public function update(int $id, array $data): {Entity}
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findOrFail($id);
            $entity->update($data);

            if (isset($data['tags'])) {
                $entity->tags()->sync($data['tags']);
            }

            event(new {Entity}Updated($entity));
            $this->clearCache();

            return $entity->fresh(['tags']);
        });
    }

    /**
     * Delete entity.
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findOrFail($id);
            
            // Check business rules before deletion
            if ($entity->hasActiveReferences()) {
                throw new \DomainException('Cannot delete: entity has active references.');
            }

            $deleted = $entity->delete();
            $this->clearCache();

            return $deleted;
        });
    }

    /**
     * Clear service cache.
     */
    protected function clearCache(): void
    {
        Cache::tags(['{entity}'])->flush();
    }
}
```

### 5.2 Action Classes (for complex single operations)

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\Actions;

use App\Plugins\{plugin_slug}\Models\Order;
use App\Plugins\{plugin_slug}\DTOs\CreateOrderData;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new order with all related entities.
 * 
 * Use Action classes when:
 * - Operation is complex (multiple steps)
 * - Logic doesn't fit naturally in a service
 * - You want single-responsibility per operation
 */
class CreateOrderAction
{
    public function __construct(
        protected InventoryService $inventory,
        protected PaymentService $payment,
        protected NotificationService $notifications
    ) {}

    /**
     * Execute the action.
     */
    public function execute(CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            // 1. Validate inventory
            $this->inventory->validateAvailability($data->items);

            // 2. Create order
            $order = Order::create([
                'user_id' => $data->userId,
                'status' => Order::STATUS_PENDING,
                'total' => $data->calculateTotal(),
            ]);

            // 3. Create order items
            foreach ($data->items as $item) {
                $order->items()->create($item->toArray());
            }

            // 4. Reserve inventory
            $this->inventory->reserve($order);

            // 5. Process payment (if immediate)
            if ($data->paymentMethod === 'card') {
                $this->payment->charge($order, $data->paymentDetails);
            }

            // 6. Send notifications
            $this->notifications->orderCreated($order);

            return $order;
        });
    }
}
```

### 5.3 Data Transfer Objects (DTOs)

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\DTOs;

/**
 * Immutable data transfer object for order creation.
 */
readonly class CreateOrderData
{
    public function __construct(
        public int $userId,
        public array $items,
        public string $paymentMethod,
        public ?array $paymentDetails = null,
        public ?string $notes = null,
    ) {}

    /**
     * Create from request data.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            items: array_map(
                fn($item) => new OrderItemData(...$item),
                $data['items']
            ),
            paymentMethod: $data['payment_method'],
            paymentDetails: $data['payment_details'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Calculate total price.
     */
    public function calculateTotal(): float
    {
        return array_reduce(
            $this->items,
            fn($total, $item) => $total + ($item->price * $item->quantity),
            0.0
        );
    }
}
```

---

## 6. Model Standards

### 6.1 Model Template

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\Models;

use App\Traits\HasTenant;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * {Entity} Model
 *
 * @property int $id
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class {Entity} extends Model
{
    use SoftDeletes;
    use HasTenant;
    use HasAudit;

    // =========================================================================
    // Constants
    // =========================================================================

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_ARCHIVED,
    ];

    // =========================================================================
    // Configuration
    // =========================================================================

    protected $table = '{table_name}';

    protected $fillable = [
        'name',
        'description',
        'status',
        'settings',
        'user_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'settings' => '{}',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany({Entity}Item::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to active records only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to search by term.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // =========================================================================
    // Accessors & Mutators
    // =========================================================================

    /**
     * Get formatted status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ARCHIVED => 'Archived',
            default => 'Unknown',
        };
    }

    // =========================================================================
    // Business Logic Methods
    // =========================================================================

    /**
     * Check if entity can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this->status !== self::STATUS_ACTIVE 
            || $this->items()->count() === 0;
    }

    /**
     * Check if entity is editable.
     */
    public function isEditable(): bool
    {
        return $this->status !== self::STATUS_ARCHIVED;
    }

    /**
     * Publish the entity.
     */
    public function publish(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->published_at = now();
        
        return $this->save();
    }
}
```

### 6.2 Model Rules

```php
// ✅ DO: Use constants for fixed values
public const STATUS_ACTIVE = 'active';

// ✅ DO: Use casts for type safety
protected $casts = ['settings' => 'array'];

// ✅ DO: Define relationships explicitly
public function user(): BelongsTo { ... }

// ✅ DO: Use scopes for reusable query logic
public function scopeActive($query) { ... }

// ❌ DON'T: Put complex business logic in models
public function processPayment() { /* complex logic */ }

// ❌ DON'T: Access request/session in models
public function getCurrentUser() { return request()->user(); }

// ❌ DON'T: Call external services from models
public function sendNotification() { Mail::send(...); }
```

---

## 7. Request Validation

### 7.1 Form Request Template

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Store{Entity}Request extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('{plugin}.{entity}.create');
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('{table}', 'name')
                    ->where('tenant_id', tenant()->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in({Entity}::STATUSES)],
            'settings' => ['nullable', 'array'],
            'settings.key' => ['sometimes', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The {entity} name is required.',
            'name.unique' => 'A {entity} with this name already exists.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name' => '{entity} name',
            'settings.key' => 'setting key',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => \Str::slug($this->input('name')),
        ]);
    }
}
```

### 7.2 Update Request with Route Model Binding

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Update{Entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('{plugin}.{entity}.update');
    }

    public function rules(): array
    {
        $entityId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('{table}', 'name')
                    ->where('tenant_id', tenant()->id)
                    ->ignore($entityId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in({Entity}::STATUSES)],
        ];
    }
}
```

---

## 8. View & Blade Standards

### 8.1 Plugin Layout Template

```blade
{{-- {plugin-slug}::layouts/app.blade.php --}}
@extends('backend.layouts.app', [
    'guard' => 'console',
    'modulePrefix' => 'console',
    'brandName' => config('app.name'),
    'version' => 'v.1.0.0',
    'baseUrl' => '',
    'currentPage' => $currentPage ?? 'dashboard',
    'currentPageLabel' => $currentPageLabel ?? 'Dashboard',
    'currentPageIcon' => $currentPageIcon ?? 'layoutDashboard',
])

@section('title')
    @yield('page-title', $currentPageLabel ?? 'Dashboard')
@endsection

@section('header')
    @yield('page-header', $currentPageLabel ?? 'Dashboard')
@endsection

@section('content')
    @yield('page-content')
@endsection

@section('command-bar')
    @yield('page-command-bar')
@endsection

@section('header-actions')
    @yield('page-header-actions')
@endsection
```

### 8.2 Page Template

```blade
{{-- {plugin-slug}::index.blade.php --}}
@extends('{plugin-slug}::layouts.app')

@section('page-title', '{Feature} List')

@section('page-header-actions')
    <button class="btn btn-primary" onclick="openCreateModal()">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>New {Entity}</span>
    </button>
@endsection

@section('page-content')
<div class="page-container">
    {{-- Filters --}}
    <div class="filters-bar">
        @include('{plugin-slug}::partials.filters')
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        @include('{plugin-slug}::partials.table', ['items' => $items])
    </div>

    {{-- Pagination --}}
    <div class="pagination-container">
        {{ $items->links() }}
    </div>
</div>

{{-- Modals --}}
@include('{plugin-slug}::partials.create-modal')
@endsection

@push('styles')
<style>
    /* Page-specific styles */
</style>
@endpush

@push('scripts')
<script>
    // Page-specific JavaScript
    function openCreateModal() {
        // Modal logic
    }
</script>
@endpush
```

### 8.3 Blade Rules

```blade
{{-- ✅ DO: Use components for reusable UI --}}
<x-button variant="primary" icon="plus">Create</x-button>

{{-- ✅ DO: Use named slots for complex components --}}
<x-modal id="create-modal">
    <x-slot:header>Create {Entity}</x-slot:header>
    <x-slot:body>...</x-slot:body>
    <x-slot:footer>...</x-slot:footer>
</x-modal>

{{-- ✅ DO: Use @include for partials --}}
@include('backend.partials.icon', ['icon' => 'user'])

{{-- ✅ DO: Escape output by default --}}
{{ $user->name }}

{{-- ⚠️ CAREFUL: Only use {!! !!} for trusted HTML --}}
{!! $trustedHtml !!}

{{-- ❌ DON'T: Put complex logic in views --}}
@php
    $total = 0;
    foreach ($items as $item) {
        $total += $item->price * $item->quantity;
    }
@endphp

{{-- ✅ DO: Calculate in controller/service, pass to view --}}
{{ $calculatedTotal }}
```

---

## 9. Route Definitions

### 9.1 Plugin Routes Template

```php
<?php
// {plugin-slug}/routes.php

use App\Plugins\{plugin_slug}\Controllers\{Feature}Controller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Plugin Routes
|--------------------------------------------------------------------------
|
| Routes are automatically prefixed with: /plugins/{plugin-slug}
| Route names are prefixed with: plugins.{plugin-slug}.
|
*/

// Resource routes
Route::middleware(['auth:console'])->group(function () {
    
    // Dashboard
    Route::get('/', [{Feature}Controller::class, 'index'])
        ->name('index');

    // CRUD operations
    Route::prefix('{entities}')->name('{entities}.')->group(function () {
        Route::get('/', [{Entity}Controller::class, 'index'])->name('index');
        Route::get('/create', [{Entity}Controller::class, 'create'])->name('create');
        Route::post('/', [{Entity}Controller::class, 'store'])->name('store');
        Route::get('/{id}', [{Entity}Controller::class, 'show'])->name('show');
        Route::get('/{id}/edit', [{Entity}Controller::class, 'edit'])->name('edit');
        Route::put('/{id}', [{Entity}Controller::class, 'update'])->name('update');
        Route::delete('/{id}', [{Entity}Controller::class, 'destroy'])->name('destroy');
    });

    // API endpoints (return JSON)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/{entities}', [{Entity}Controller::class, 'apiList'])->name('{entities}.list');
        Route::get('/{entities}/{id}', [{Entity}Controller::class, 'apiShow'])->name('{entities}.show');
        Route::post('/{entities}', [{Entity}Controller::class, 'apiStore'])->name('{entities}.store');
        Route::put('/{entities}/{id}', [{Entity}Controller::class, 'apiUpdate'])->name('{entities}.update');
        Route::delete('/{entities}/{id}', [{Entity}Controller::class, 'apiDestroy'])->name('{entities}.destroy');
    });
});
```

### 9.2 Route Naming Convention

```php
// Pattern: {module}.{feature}.{action}

// Module routes
'console.dashboard'
'console.settings.index'
'console.plugins.show'

// Plugin routes (auto-prefixed)
'plugins.{plugin-slug}.index'
'plugins.{plugin-slug}.{entities}.store'
'plugins.{plugin-slug}.api.{entities}.list'

// API routes
'api.v1.{entities}.index'
'api.v1.{entities}.show'
```

---

## 10. API Design

### 10.1 RESTful Endpoints

```
GET    /api/v1/{resources}          → index   (list)
POST   /api/v1/{resources}          → store   (create)
GET    /api/v1/{resources}/{id}     → show    (read)
PUT    /api/v1/{resources}/{id}     → update  (full update)
PATCH  /api/v1/{resources}/{id}     → patch   (partial update)
DELETE /api/v1/{resources}/{id}     → destroy (delete)

# Nested resources
GET    /api/v1/orders/{id}/items
POST   /api/v1/orders/{id}/items

# Actions (non-CRUD)
POST   /api/v1/orders/{id}/submit
POST   /api/v1/orders/{id}/cancel
```

### 10.2 API Resource Template

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class {Entity}Resource extends JsonResource
{
    /**
     * Transform the resource.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => '{entity}',
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
                'status_label' => $this->status_label,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'user' => new UserResource($this->whenLoaded('user')),
                'items' => {Entity}ItemResource::collection($this->whenLoaded('items')),
            ],
            'meta' => [
                'can_edit' => $this->isEditable(),
                'can_delete' => $this->canBeDeleted(),
            ],
        ];
    }
}
```

### 10.3 API Response Format

```json
// Success (single resource)
{
    "success": true,
    "data": {
        "id": 1,
        "type": "order",
        "attributes": { ... }
    }
}

// Success (collection)
{
    "success": true,
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 72
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    }
}

// Error
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email must be valid."]
    }
}
```

---

## 11. Database & Migrations

### 11.1 Migration Template

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{table_name}', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Tenant (if multi-tenant)
            $table->foreignId('tenant_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Foreign keys
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Core fields
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('status', 50)->default('draft');
            
            // JSON fields
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            
            // Numeric fields
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedInteger('quantity')->default(0);
            
            // Boolean fields
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            // Timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index(['tenant_id', 'status']);
            $table->index('created_at');
            
            // Unique constraints
            $table->unique(['tenant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{table_name}');
    }
};
```

### 11.2 Migration Rules

```php
// ✅ DO: Use explicit column sizes
$table->string('name', 255);
$table->string('status', 50);

// ✅ DO: Add indexes for frequently queried columns
$table->index('status');
$table->index(['user_id', 'created_at']);

// ✅ DO: Use foreign key constraints
$table->foreignId('user_id')->constrained()->cascadeOnDelete();

// ✅ DO: Default values for new columns
$table->boolean('is_active')->default(true);

// ✅ DO: Nullable for optional columns
$table->text('notes')->nullable();

// ❌ DON'T: Create indexes on every column
// ❌ DON'T: Use text() for short strings
// ❌ DON'T: Forget down() method for rollbacks
```

### 11.3 Query Optimization

```php
// ✅ DO: Eager load relationships
$orders = Order::with(['user', 'items.product'])->get();

// ✅ DO: Select only needed columns
$users = User::select(['id', 'name', 'email'])->get();

// ✅ DO: Use chunking for large datasets
Order::chunk(1000, function ($orders) {
    foreach ($orders as $order) {
        // Process
    }
});

// ✅ DO: Use database aggregations
$total = Order::where('status', 'completed')->sum('total');

// ❌ DON'T: N+1 queries
foreach ($orders as $order) {
    echo $order->user->name; // Triggers query each iteration
}

// ❌ DON'T: Load all records for counting
$count = Order::all()->count(); // Bad
$count = Order::count(); // Good
```

---

## 12. Security Standards

### 12.1 Input Sanitization

```php
// Middleware: InputSanitizationMiddleware
// Applied to all routes

// ✅ DO: Validate all input
$validated = $request->validate([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email'],
]);

// ✅ DO: Use parameterized queries (automatic with Eloquent)
User::where('email', $email)->first();

// ❌ DON'T: Use raw queries with user input
DB::select("SELECT * FROM users WHERE email = '$email'"); // SQL Injection!

// ✅ DO: Escape output in views
{{ $user->name }} // Auto-escaped
{!! $trustedHtml !!} // Only for trusted content
```

### 12.2 Authorization

```php
// ✅ DO: Check permissions in requests
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('orders.create');
    }
}

// ✅ DO: Use policies for resource authorization
class OrderPolicy
{
    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id 
            || $user->hasPermission('orders.update.any');
    }
}

// ✅ DO: Check authorization in controllers
public function show(int $id): View
{
    $order = Order::findOrFail($id);
    $this->authorize('view', $order);
    
    return view('orders.show', compact('order'));
}
```

### 12.3 Path Traversal Protection

```php
// ✅ DO: Validate file paths
public function getFullPath(): string
{
    $basePath = app_path('Plugins');
    $realBase = realpath($basePath);

    // Check for traversal attempts
    if (str_contains($this->path, '..') || str_contains($this->path, './')) {
        throw SecurityException::pathTraversal($this->path, $realBase);
    }

    $fullPath = "{$basePath}/{$this->slug}";
    $realPath = realpath($fullPath);

    if (!str_starts_with($realPath, $realBase)) {
        throw SecurityException::pathTraversal($fullPath, $realBase);
    }

    return $realPath;
}
```

### 12.4 Rate Limiting

```php
// config/ratelimit.php
return [
    'api' => [
        'limit' => 60,
        'window' => 60, // seconds
    ],
    'auth' => [
        'limit' => 5,
        'window' => 60,
    ],
];

// Apply in routes
Route::middleware(['throttle:api'])->group(function () {
    // API routes
});
```

---

## 13. Error Handling

### 13.1 Custom Exceptions

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug}\Exceptions;

use Exception;

class {Entity}Exception extends Exception
{
    /**
     * Create for not found.
     */
    public static function notFound(int $id): self
    {
        return new self("{Entity} with ID {$id} not found.", 404);
    }

    /**
     * Create for validation error.
     */
    public static function validationFailed(array $errors): self
    {
        $message = implode(', ', array_map(
            fn($field, $messages) => "{$field}: " . implode(', ', $messages),
            array_keys($errors),
            $errors
        ));
        
        return new self("Validation failed: {$message}", 422);
    }

    /**
     * Create for business rule violation.
     */
    public static function cannotDelete(string $reason): self
    {
        return new self("Cannot delete {entity}: {$reason}", 409);
    }
}
```

### 13.2 Exception Handling in Services

```php
public function delete(int $id): bool
{
    $entity = $this->findOrFail($id);
    
    if (!$entity->canBeDeleted()) {
        throw {Entity}Exception::cannotDelete('has active references');
    }

    try {
        return DB::transaction(function () use ($entity) {
            // Cleanup related data
            $entity->items()->delete();
            
            return $entity->delete();
        });
    } catch (\Throwable $e) {
        Log::error('Failed to delete {entity}', [
            'id' => $id,
            'error' => $e->getMessage(),
        ]);
        
        throw new {Entity}Exception(
            'Failed to delete {entity}. Please try again.',
            500,
            $e
        );
    }
}
```

### 13.3 API Error Responses

```php
// In ExceptionHandler or controller
public function render($request, Throwable $e)
{
    if ($request->expectsJson()) {
        $status = method_exists($e, 'getStatusCode') 
            ? $e->getStatusCode() 
            : 500;

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => method_exists($e, 'errors') ? $e->errors() : null,
            'debug' => config('app.debug') ? [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ] : null,
        ], $status);
    }

    return parent::render($request, $e);
}
```

---

## 14. Testing Requirements

### 14.1 Test Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   └── {Feature}ServiceTest.php
│   └── Models/
│       └── {Entity}Test.php
├── Feature/
│   ├── Http/
│   │   └── {Entity}ControllerTest.php
│   └── Api/
│       └── {Entity}ApiTest.php
└── Plugins/
    └── {plugin-slug}/
        ├── Unit/
        └── Feature/
```

### 14.2 Unit Test Template

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Plugins\{plugin_slug}\Services\{Feature}Service;
use App\Plugins\{plugin_slug}\Models\{Entity};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {Feature}ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected {Feature}Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app({Feature}Service::class);
    }

    /** @test */
    public function it_creates_entity_with_valid_data(): void
    {
        $data = [
            'name' => 'Test Entity',
            'description' => 'Test description',
            'status' => {Entity}::STATUS_DRAFT,
        ];

        $entity = $this->service->create($data);

        $this->assertInstanceOf({Entity}::class, $entity);
        $this->assertEquals('Test Entity', $entity->name);
        $this->assertDatabaseHas('{table}', ['name' => 'Test Entity']);
    }

    /** @test */
    public function it_throws_exception_when_deleting_active_entity(): void
    {
        $entity = {Entity}::factory()->create([
            'status' => {Entity}::STATUS_ACTIVE,
        ]);

        $this->expectException({Entity}Exception::class);
        
        $this->service->delete($entity->id);
    }
}
```

### 14.3 Feature Test Template

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\User;
use App\Plugins\{plugin_slug}\Models\{Entity};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {Entity}ControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_displays_index_page(): void
    {
        $this->actingAs($this->user, 'console')
            ->get('/plugins/{plugin-slug}')
            ->assertOk()
            ->assertViewIs('{plugin-slug}::index');
    }

    /** @test */
    public function it_stores_new_entity(): void
    {
        $data = [
            'name' => 'New Entity',
            'status' => 'draft',
        ];

        $this->actingAs($this->user, 'console')
            ->postJson('/plugins/{plugin-slug}/{entities}', $data)
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('{table}', ['name' => 'New Entity']);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'console')
            ->postJson('/plugins/{plugin-slug}/{entities}', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $this->getJson('/plugins/{plugin-slug}')
            ->assertUnauthorized();
    }
}
```

---

## 15. Performance Guidelines

### 15.1 Caching Strategy

```php
// ✅ DO: Cache expensive queries
public function getStats(): array
{
    return Cache::tags(['{entity}', 'stats'])
        ->remember('stats:overview', 3600, function () {
            return [
                'total' => {Entity}::count(),
                'active' => {Entity}::active()->count(),
                'recent' => {Entity}::recent(7)->count(),
            ];
        });
}

// ✅ DO: Invalidate cache on changes
public function create(array $data): {Entity}
{
    $entity = {Entity}::create($data);
    
    Cache::tags(['{entity}'])->flush();
    
    return $entity;
}

// ✅ DO: Use cache tags for granular invalidation
Cache::tags(['orders', 'user:' . $userId])->flush();
```

### 15.2 Query Optimization

```php
// ✅ DO: Use indexes for filtered queries
// Migration
$table->index(['status', 'created_at']);

// ✅ DO: Limit result sets
$items = {Entity}::active()->limit(100)->get();

// ✅ DO: Use cursor pagination for large sets
{Entity}::cursorPaginate(100);

// ✅ DO: Use raw expressions for complex aggregations
{Entity}::selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->get();
```

### 15.3 Background Jobs

```php
// ✅ DO: Queue slow operations
class ProcessLargeImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $importId,
        public readonly string $filePath
    ) {}

    public function handle(): void
    {
        // Process import
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Import failed', [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
        ]);
    }
}

// Dispatch
ProcessLargeImport::dispatch($importId, $filePath);
```

---

## 16. Plugin Extension Patterns

### 16.1 Main Plugin Class

```php
<?php

declare(strict_types=1);

namespace App\Plugins\{plugin_slug};

use App\Services\Plugins\BasePlugin;

class {PluginName}Plugin extends BasePlugin
{
    /**
     * Register plugin services.
     */
    public function register(): void
    {
        // Bind services to container
        $this->app->singleton({Feature}Service::class);
    }

    /**
     * Bootstrap the plugin.
     */
    public function boot(): void
    {
        parent::boot();

        // Add hooks
        $this->addFilter('dashboard_widgets', fn($widgets) => 
            $this->registerWidgets($widgets)
        );

        // Add navigation
        // (Loaded from plugin.json automatically)
    }

    /**
     * Handle activation.
     */
    public function activate(): void
    {
        // Set default settings
        $this->setSetting('enabled', true);
        $this->setSetting('default_limit', 50);
    }

    /**
     * Handle deactivation.
     */
    public function deactivate(): void
    {
        // Cleanup if needed
    }

    /**
     * Check if plugin has settings page.
     */
    public function hasSettingsPage(): bool
    {
        return true;
    }

    /**
     * Get settings fields definition.
     */
    public function getSettingsFields(): array
    {
        return [
            'general' => [
                'title' => 'General Settings',
                'fields' => [
                    'enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable Feature',
                        'default' => true,
                    ],
                    'default_limit' => [
                        'type' => 'number',
                        'label' => 'Default Limit',
                        'default' => 50,
                        'min' => 10,
                        'max' => 500,
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if plugin has dashboard.
     */
    public function hasDashboard(): bool
    {
        return true;
    }

    /**
     * Get dashboard widgets.
     */
    public function getDashboardWidgets(): array
    {
        return [
            'overview' => [
                'title' => '{Feature} Overview',
                'icon' => 'barChart',
                'component' => 'stats',
                'default_width' => 2,
                'default_height' => 1,
            ],
        ];
    }

    /**
     * Get widget data.
     */
    public function getWidgetData(string $widgetId): array
    {
        return match($widgetId) {
            'overview' => $this->getOverviewData(),
            default => ['widget_id' => $widgetId, 'data' => []],
        };
    }
}
```

### 16.2 Plugin Manifest (plugin.json)

```json
{
    "name": "{Plugin Name}",
    "slug": "{plugin-slug}",
    "version": "1.0.0",
    "description": "Description of the plugin",
    "author": "Author Name",
    "author_url": "https://example.com",
    "main": "{PluginName}Plugin.php",
    "requires": {
        "core": ">=1.0.0"
    },
    "icon": "plug",
    "navigation": {
        "categories": [
            {
                "name": "{Category}",
                "icon": "folder",
                "order": 50
            }
        ],
        "items": [
            {
                "id": "{plugin-slug}",
                "icon": "list",
                "label": "{Feature}",
                "route": "index",
                "category": "{Category}",
                "order": 1,
                "children": [
                    {
                        "id": "{plugin-slug}-list",
                        "icon": "list",
                        "label": "All {Entities}",
                        "route": "index"
                    },
                    {
                        "id": "{plugin-slug}-create",
                        "icon": "plus",
                        "label": "Create New",
                        "route": "create"
                    }
                ]
            }
        ]
    },
    "dashboard": {
        "enabled": true,
        "icon": "layoutDashboard",
        "title": "{Plugin} Dashboard"
    },
    "settings": true
}
```

---

## Appendix A: Code Review Checklist

Before submitting code for each module, verify:

### Architecture
- [ ] Controllers only handle HTTP concerns
- [ ] Business logic is in Services/Actions
- [ ] Models don't call external services
- [ ] Dependencies injected via constructor

### Security
- [ ] All input validated via Form Requests
- [ ] Authorization checks in place
- [ ] No raw SQL with user input
- [ ] Path traversal protection for file operations

### Performance
- [ ] Eager loading for relationships
- [ ] Indexes for frequently queried columns
- [ ] Caching for expensive operations
- [ ] Pagination for list endpoints

### Code Quality
- [ ] Strict types declared
- [ ] Type hints for parameters and returns
- [ ] Consistent naming conventions
- [ ] PHPDoc for public methods

### Testing
- [ ] Unit tests for services
- [ ] Feature tests for controllers
- [ ] Edge cases covered
- [ ] Authorization tested

---

## Appendix B: Common Patterns Reference

### Repository Pattern (Optional)

```php
interface {Entity}RepositoryInterface
{
    public function find(int $id): ?{Entity};
    public function findOrFail(int $id): {Entity};
    public function create(array $data): {Entity};
    public function update(int $id, array $data): {Entity};
    public function delete(int $id): bool;
}

class Eloquent{Entity}Repository implements {Entity}RepositoryInterface
{
    public function find(int $id): ?{Entity}
    {
        return {Entity}::find($id);
    }
    
    // ... other methods
}
```

### Observer Pattern

```php
class {Entity}Observer
{
    public function creating({Entity} $entity): void
    {
        $entity->slug = \Str::slug($entity->name);
    }

    public function created({Entity} $entity): void
    {
        Cache::tags(['{entity}'])->flush();
    }

    public function updated({Entity} $entity): void
    {
        Cache::tags(['{entity}'])->flush();
    }
}

// Register in ServiceProvider
{Entity}::observe({Entity}Observer::class);
```

### Strategy Pattern

```php
interface PaymentProcessorInterface
{
    public function charge(Order $order, array $details): PaymentResult;
}

class StripeProcessor implements PaymentProcessorInterface { ... }
class PayPalProcessor implements PaymentProcessorInterface { ... }

class PaymentService
{
    public function getProcessor(string $method): PaymentProcessorInterface
    {
        return match($method) {
            'stripe' => new StripeProcessor(),
            'paypal' => new PayPalProcessor(),
            default => throw new InvalidPaymentMethodException($method),
        };
    }
}
```

---

**Document End**

This document should be treated as the authoritative reference for all 30 plugin modules. Any deviations require explicit justification and approval.
