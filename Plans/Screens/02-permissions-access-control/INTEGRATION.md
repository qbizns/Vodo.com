# Permissions & Access Control - Integration Guide

## Overview

This document describes how plugins integrate with the permission system, register their own permissions, and implement access control in their features.

---

## Registering Plugin Permissions

### Via Plugin Manifest

```json
{
    "name": "Invoice Manager",
    "slug": "invoice-manager",
    "provides": {
        "permissions": true
    }
}
```

### Via Plugin Class

```php
<?php

namespace InvoiceManager;

use App\Contracts\PluginInterface;
use App\Services\PluginManager\BasePlugin;

class InvoiceManagerPlugin extends BasePlugin implements PluginInterface
{
    public function getPermissions(): array
    {
        return [
            // Basic CRUD permissions
            [
                'name' => 'invoices.view',
                'label' => 'View Invoices',
                'description' => 'Can view invoice list and details',
                'group' => 'invoices',
                'default_roles' => ['admin', 'manager', 'user'],
            ],
            [
                'name' => 'invoices.create',
                'label' => 'Create Invoices',
                'description' => 'Can create new invoices',
                'group' => 'invoices',
                'default_roles' => ['admin', 'manager'],
            ],
            [
                'name' => 'invoices.edit',
                'label' => 'Edit Invoices',
                'description' => 'Can modify existing invoices',
                'group' => 'invoices',
                'default_roles' => ['admin', 'manager'],
            ],
            [
                'name' => 'invoices.delete',
                'label' => 'Delete Invoices',
                'description' => 'Can delete invoices',
                'group' => 'invoices',
                'default_roles' => ['admin'],
                'is_dangerous' => true,
            ],
            
            // Feature-specific permissions
            [
                'name' => 'invoices.send',
                'label' => 'Send Invoices',
                'description' => 'Can send invoices via email',
                'group' => 'invoices',
                'default_roles' => ['admin', 'manager'],
            ],
            [
                'name' => 'invoices.void',
                'label' => 'Void Invoices',
                'description' => 'Can void/cancel invoices',
                'group' => 'invoices',
                'default_roles' => ['admin'],
                'is_dangerous' => true,
            ],
            [
                'name' => 'invoices.export',
                'label' => 'Export Invoices',
                'description' => 'Can export invoice data',
                'group' => 'invoices',
                'default_roles' => ['admin', 'manager'],
            ],
            [
                'name' => 'invoices.reports',
                'label' => 'View Invoice Reports',
                'description' => 'Can access invoice analytics and reports',
                'group' => 'invoices',
                'default_roles' => ['admin', 'manager'],
            ],
            [
                'name' => 'invoices.settings',
                'label' => 'Manage Invoice Settings',
                'description' => 'Can configure invoice plugin settings',
                'group' => 'invoices',
                'default_roles' => ['admin'],
            ],
        ];
    }
    
    public function getPermissionGroups(): array
    {
        return [
            [
                'slug' => 'invoices',
                'name' => 'Invoices',
                'description' => 'Invoice management permissions',
                'icon' => 'file-text',
                'position' => 30,
            ],
        ];
    }
}
```

### Programmatic Registration

```php
use App\Services\PermissionRegistry;

class InvoiceServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistry $registry): void
    {
        // Register permission group
        $registry->registerGroup([
            'slug' => 'invoices',
            'name' => 'Invoices',
            'icon' => 'file-text',
            'plugin' => 'invoice-manager',
        ]);
        
        // Register individual permission
        $registry->register([
            'name' => 'invoices.view',
            'label' => 'View Invoices',
            'group' => 'invoices',
            'plugin' => 'invoice-manager',
        ]);
        
        // Bulk register permissions
        $registry->registerMany([
            ['name' => 'invoices.create', 'label' => 'Create Invoices', 'group' => 'invoices'],
            ['name' => 'invoices.edit', 'label' => 'Edit Invoices', 'group' => 'invoices'],
            ['name' => 'invoices.delete', 'label' => 'Delete Invoices', 'group' => 'invoices'],
        ], 'invoice-manager');
    }
}
```

---

## Using Permissions in Controllers

### Basic Permission Check

```php
<?php

namespace InvoiceManager\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct()
    {
        // Apply permission middleware
        $this->middleware('permission:invoices.view')->only(['index', 'show']);
        $this->middleware('permission:invoices.create')->only(['create', 'store']);
        $this->middleware('permission:invoices.edit')->only(['edit', 'update']);
        $this->middleware('permission:invoices.delete')->only('destroy');
    }
    
    public function index()
    {
        // Additional check if needed
        $this->authorize('viewAny', Invoice::class);
        
        return view('invoice-manager::invoices.index');
    }
    
    public function show(Invoice $invoice)
    {
        // Check specific record permission
        $this->authorize('view', $invoice);
        
        return view('invoice-manager::invoices.show', compact('invoice'));
    }
}
```

### Programmatic Checks

```php
// Using Gate
if (Gate::allows('invoices.delete', $invoice)) {
    // Can delete
}

// Using User model
if (auth()->user()->can('invoices.edit')) {
    // Can edit any invoice
}

if (auth()->user()->can('invoices.edit', $invoice)) {
    // Can edit specific invoice
}

// Using helper
if (can('invoices.void')) {
    // Can void invoices
}
```

---

## Policy Integration

### Creating a Policy

```php
<?php

namespace InvoiceManager\Policies;

use App\Models\User;
use InvoiceManager\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;
    
    /**
     * Perform pre-authorization checks
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admins can do anything
        if ($user->is_super_admin) {
            return true;
        }
        
        return null;
    }
    
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }
    
    public function view(User $user, Invoice $invoice): bool
    {
        if (!$user->can('invoices.view')) {
            return false;
        }
        
        // Check ownership or team access
        return $invoice->user_id === $user->id 
            || $invoice->team_id === $user->team_id
            || $user->can('invoices.view_all');
    }
    
    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }
    
    public function update(User $user, Invoice $invoice): bool
    {
        if (!$user->can('invoices.edit')) {
            return false;
        }
        
        // Cannot edit sent/paid invoices unless admin
        if (in_array($invoice->status, ['sent', 'paid'])) {
            return $user->can('invoices.edit_finalized');
        }
        
        return $invoice->user_id === $user->id || $user->can('invoices.edit_all');
    }
    
    public function delete(User $user, Invoice $invoice): bool
    {
        if (!$user->can('invoices.delete')) {
            return false;
        }
        
        // Cannot delete paid invoices
        if ($invoice->status === 'paid') {
            return false;
        }
        
        return true;
    }
    
    public function void(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.void') && $invoice->canBeVoided();
    }
    
    public function send(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.send') && $invoice->canBeSent();
    }
}
```

### Registering the Policy

```php
// InvoiceServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(Invoice::class, InvoicePolicy::class);
}
```

---

## Blade Integration

### Permission Checks in Views

```blade
{{-- Basic permission check --}}
@can('invoices.create')
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        Create Invoice
    </a>
@endcan

{{-- Permission with model --}}
@can('update', $invoice)
    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary">
        Edit
    </a>
@endcan

{{-- Multiple permissions --}}
@canany(['invoices.edit', 'invoices.delete'], $invoice)
    <div class="dropdown">
        <button>Actions</button>
        <div class="dropdown-menu">
            @can('update', $invoice)
                <a href="{{ route('invoices.edit', $invoice) }}">Edit</a>
            @endcan
            @can('delete', $invoice)
                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}">
                    @csrf @method('DELETE')
                    <button type="submit">Delete</button>
                </form>
            @endcan
        </div>
    </div>
@endcanany

{{-- Using custom directives --}}
@permission('invoices.reports')
    <a href="{{ route('invoices.reports') }}">Reports</a>
@endpermission

@role('admin')
    <a href="{{ route('admin.settings') }}">Settings</a>
@endrole
```

### Dynamic Menu Items

```blade
{{-- Navigation with permission filtering --}}
@foreach($menuItems as $item)
    @if(!$item['permission'] || auth()->user()->can($item['permission']))
        <a href="{{ $item['url'] }}" class="nav-item">
            <x-icon :name="$item['icon']" />
            {{ $item['label'] }}
            @if($item['badge'])
                <span class="badge">{{ $item['badge'] }}</span>
            @endif
        </a>
    @endif
@endforeach
```

---

## API Integration

### Middleware

```php
// routes/api.php
Route::middleware(['auth:api', 'permission:invoices.view'])->group(function () {
    Route::get('/invoices', [InvoiceApiController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceApiController::class, 'show']);
});

Route::middleware(['auth:api', 'permission:invoices.create'])->group(function () {
    Route::post('/invoices', [InvoiceApiController::class, 'store']);
});
```

### Permission-Aware API Resources

```php
<?php

namespace InvoiceManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();
        
        return [
            'id' => $this->id,
            'number' => $this->number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'total' => $this->total,
            'status' => $this->status,
            
            // Conditional fields based on permissions
            'profit_margin' => $this->when(
                $user->can('invoices.view_financials'),
                $this->profit_margin
            ),
            'cost_breakdown' => $this->when(
                $user->can('invoices.view_costs'),
                $this->cost_breakdown
            ),
            
            // Available actions based on permissions
            '_actions' => [
                'can_edit' => $user->can('update', $this->resource),
                'can_delete' => $user->can('delete', $this->resource),
                'can_void' => $user->can('void', $this->resource),
                'can_send' => $user->can('send', $this->resource),
            ],
            
            // Links with permission checks
            '_links' => array_filter([
                'self' => route('api.invoices.show', $this),
                'edit' => $user->can('update', $this->resource) 
                    ? route('api.invoices.update', $this) 
                    : null,
                'pdf' => $user->can('invoices.export') 
                    ? route('api.invoices.pdf', $this) 
                    : null,
            ]),
        ];
    }
}
```

---

## Role-Based Features

### Plugin-Provided Roles

```php
public function getRoles(): array
{
    return [
        [
            'name' => 'Accountant',
            'slug' => 'accountant',
            'description' => 'Finance team member with invoice access',
            'parent' => 'user', // Inherits from user role
            'color' => '#059669',
            'icon' => 'calculator',
            'default_permissions' => [
                'invoices.view',
                'invoices.create',
                'invoices.edit',
                'invoices.send',
                'invoices.reports',
            ],
        ],
    ];
}
```

### Checking Roles

```php
// Check single role
if ($user->hasRole('accountant')) {
    // User is an accountant
}

// Check multiple roles
if ($user->hasAnyRole(['admin', 'manager', 'accountant'])) {
    // User has one of these roles
}

// In middleware
Route::middleware('role:admin,manager')->group(function () {
    // Only admin and manager can access
});
```

---

## Access Rules Integration

### Registering Access Rules

```php
// Plugins can suggest default access rules
public function getAccessRules(): array
{
    return [
        [
            'name' => 'Invoice Deletion - Business Hours',
            'description' => 'Restrict invoice deletion to business hours',
            'permissions' => ['invoices.delete', 'invoices.void'],
            'conditions' => [
                ['type' => 'time', 'operator' => 'between', 'value' => ['09:00', '17:00']],
                ['type' => 'day', 'operator' => 'is_one_of', 'value' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']],
            ],
            'action' => 'deny',
            'is_active' => false, // Disabled by default, admin can enable
        ],
    ];
}
```

### Custom Condition Types

```php
use App\Services\AccessRuleEngine;

class InvoiceServiceProvider extends ServiceProvider
{
    public function boot(AccessRuleEngine $engine): void
    {
        // Register custom condition type
        $engine->registerConditionType('invoice_amount', function ($condition, $context) {
            $invoice = $context['model'] ?? null;
            if (!$invoice instanceof Invoice) {
                return true;
            }
            
            $operator = $condition['operator'];
            $value = $condition['value'];
            
            return match ($operator) {
                'greater_than' => $invoice->total > $value,
                'less_than' => $invoice->total < $value,
                'between' => $invoice->total >= $value['min'] && $invoice->total <= $value['max'],
                default => true,
            };
        });
    }
}
```

---

## Field-Level Permissions

### Defining Field Permissions

```php
public function getFieldPermissions(): array
{
    return [
        'invoices' => [
            'cost_price' => [
                'view' => 'invoices.view_costs',
                'edit' => 'invoices.edit_costs',
            ],
            'profit_margin' => [
                'view' => 'invoices.view_financials',
            ],
            'internal_notes' => [
                'view' => 'invoices.view_internal',
                'edit' => 'invoices.edit_internal',
            ],
        ],
    ];
}
```

### Using Field Permissions

```php
// In Model
class Invoice extends Model
{
    public function getVisibleFields(User $user): array
    {
        $fields = ['id', 'number', 'customer_id', 'total', 'status'];
        
        if ($user->can('invoices.view_costs')) {
            $fields[] = 'cost_price';
        }
        
        if ($user->can('invoices.view_financials')) {
            $fields[] = 'profit_margin';
        }
        
        return $fields;
    }
}

// In Controller
public function show(Invoice $invoice)
{
    $visibleFields = $invoice->getVisibleFields(auth()->user());
    
    return view('invoices.show', [
        'invoice' => $invoice->only($visibleFields),
    ]);
}
```

---

## Caching Permissions

### Cache Strategy

```php
// User permissions are cached
$permissions = Cache::remember(
    "user.{$user->id}.permissions",
    now()->addHour(),
    fn() => $user->getAllPermissions()
);

// Clear cache on changes
public function assignRole(Role $role): void
{
    $this->roles()->attach($role);
    Cache::forget("user.{$this->id}.permissions");
}

// Global permission cache
Cache::tags(['permissions'])->remember('all_permissions', now()->addDay(), function () {
    return Permission::with('group')->get();
});

// Clear all permission caches
Cache::tags(['permissions'])->flush();
```

### Event Listeners for Cache Invalidation

```php
// EventServiceProvider.php
protected $listen = [
    RoleUpdated::class => [ClearPermissionCache::class],
    UserRoleAssigned::class => [ClearUserPermissionCache::class],
    PermissionOverrideChanged::class => [ClearUserPermissionCache::class],
];
```

---

## Testing Permissions

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class InvoicePermissionTest extends TestCase
{
    public function test_user_with_permission_can_view_invoices(): void
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'manager')->first();
        $user->assignRole($role);
        
        $response = $this->actingAs($user)->get('/invoices');
        
        $response->assertOk();
    }
    
    public function test_user_without_permission_cannot_delete_invoice(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create();
        
        $response = $this->actingAs($user)->delete("/invoices/{$invoice->id}");
        
        $response->assertForbidden();
    }
    
    public function test_access_rule_blocks_after_hours(): void
    {
        $this->travelTo('2024-12-15 20:00:00');
        
        $user = User::factory()->withPermission('invoices.delete')->create();
        $invoice = Invoice::factory()->create();
        
        // Create access rule
        AccessRule::create([
            'name' => 'Business Hours',
            'permissions' => ['invoices.delete'],
            'conditions' => [
                ['type' => 'time', 'operator' => 'between', 'value' => ['09:00', '17:00']],
            ],
            'action' => 'deny',
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($user)->delete("/invoices/{$invoice->id}");
        
        $response->assertForbidden();
    }
}
```
