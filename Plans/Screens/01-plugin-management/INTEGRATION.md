# Plugin Management - Integration Guide

## Overview

This document describes how plugins integrate with the Plugin Management system, including lifecycle hooks, service providers, manifest structure, and extension points.

## Plugin Structure

### Directory Layout

```
plugins/
└── invoice-manager/
    ├── plugin.json                 # Plugin manifest
    ├── composer.json               # PHP dependencies
    ├── InvoiceManagerPlugin.php    # Main plugin class
    ├── src/
    │   ├── Providers/
    │   │   └── InvoiceManagerServiceProvider.php
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   ├── Middleware/
    │   │   └── Requests/
    │   ├── Models/
    │   ├── Services/
    │   ├── Events/
    │   ├── Listeners/
    │   ├── Jobs/
    │   └── Console/
    ├── config/
    │   └── invoice-manager.php
    ├── database/
    │   ├── migrations/
    │   └── seeders/
    ├── resources/
    │   ├── views/
    │   ├── lang/
    │   └── assets/
    ├── routes/
    │   ├── web.php
    │   └── api.php
    └── tests/
```

### Plugin Manifest (plugin.json)

```json
{
    "name": "invoice-manager",
    "title": "Invoice Manager",
    "description": "Comprehensive invoicing solution for managing invoices, payments, and billing",
    "version": "2.1.0",
    "author": {
        "name": "Vendor Name",
        "email": "support@vendor.com",
        "url": "https://vendor.com"
    },
    "homepage": "https://vendor.com/invoice-manager",
    "documentation": "https://docs.vendor.com/invoice-manager",
    "license": "proprietary",
    "category": "accounting",
    "tags": ["invoice", "billing", "payment", "accounting"],
    "icon": "resources/assets/icon.png",
    "screenshots": [
        "resources/assets/screenshots/list.png",
        "resources/assets/screenshots/create.png"
    ],
    "requirements": {
        "php": "^8.1",
        "system": "^2.0",
        "extensions": ["gd", "zip"]
    },
    "dependencies": {
        "core-finance": "^1.5.0",
        "pdf-generator": "^2.0.0",
        "email-service": "^1.0.0"
    },
    "optional_dependencies": {
        "payment-gateway": "^1.0.0"
    },
    "autoload": {
        "psr-4": {
            "InvoiceManager\\": "src/"
        }
    },
    "providers": [
        "InvoiceManager\\Providers\\InvoiceManagerServiceProvider"
    ],
    "entry_class": "InvoiceManagerPlugin",
    "is_premium": true,
    "requires_license": true,
    "settings": {
        "has_settings_page": true,
        "settings_route": "admin.plugins.invoice-manager.settings"
    }
}
```

---

## Main Plugin Class

```php
<?php

namespace InvoiceManager;

use PluginSystem\Contracts\PluginInterface;
use PluginSystem\Support\BasePlugin;

class InvoiceManagerPlugin extends BasePlugin implements PluginInterface
{
    /**
     * Plugin identifier
     */
    public const SLUG = 'invoice-manager';
    
    /**
     * Plugin version
     */
    public const VERSION = '2.1.0';

    /**
     * Boot the plugin
     * Called when plugin is activated and system boots
     */
    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadViews();
        $this->loadTranslations();
        $this->loadMigrations();
        $this->registerCommands();
        $this->registerScheduledTasks();
        $this->registerEventListeners();
    }

    /**
     * Register plugin services
     * Called during service container registration
     */
    public function register(): void
    {
        $this->mergeConfig();
        $this->registerServices();
        $this->registerBindings();
    }

    /**
     * Called when plugin is being activated
     */
    public function onActivate(): void
    {
        // Run activation tasks
        $this->publishAssets();
        $this->seedDefaultData();
        $this->clearCaches();
        
        // Fire activation event
        event(new \InvoiceManager\Events\PluginActivated($this));
    }

    /**
     * Called when plugin is being deactivated
     */
    public function onDeactivate(): void
    {
        // Cleanup tasks
        $this->clearCaches();
        
        // Fire deactivation event
        event(new \InvoiceManager\Events\PluginDeactivated($this));
    }

    /**
     * Called before plugin is uninstalled
     */
    public function onUninstall(bool $keepData = false): void
    {
        if (!$keepData) {
            $this->dropTables();
            $this->removeFiles();
        }
        
        $this->unpublishAssets();
        $this->removePermissions();
        $this->removeMenuItems();
    }

    /**
     * Called when plugin is being updated
     */
    public function onUpdate(string $fromVersion, string $toVersion): void
    {
        // Run version-specific migrations
        $this->runUpdateMigrations($fromVersion, $toVersion);
        
        // Update assets
        $this->publishAssets(force: true);
        
        // Clear caches
        $this->clearCaches();
    }

    /**
     * Get plugin settings fields definition
     */
    public function getSettingsFields(): array
    {
        return [
            'tabs' => [
                'general' => ['label' => 'General', 'icon' => 'settings'],
                'email' => ['label' => 'Email', 'icon' => 'mail'],
                'templates' => ['label' => 'Templates', 'icon' => 'file-text'],
                'payment' => ['label' => 'Payment Gateways', 'icon' => 'credit-card'],
            ],
            'fields' => [
                [
                    'key' => 'invoice_prefix',
                    'type' => 'text',
                    'label' => 'Invoice Prefix',
                    'tab' => 'general',
                    'default' => 'INV-',
                    'rules' => 'required|string|max:10',
                ],
                // ... more fields
            ],
        ];
    }

    /**
     * Get permissions registered by this plugin
     */
    public function getPermissions(): array
    {
        return [
            'invoices.view' => [
                'label' => 'View Invoices',
                'description' => 'Can view invoice list and details',
                'group' => 'Invoices',
            ],
            'invoices.create' => [
                'label' => 'Create Invoices',
                'description' => 'Can create new invoices',
                'group' => 'Invoices',
            ],
            'invoices.edit' => [
                'label' => 'Edit Invoices',
                'description' => 'Can modify existing invoices',
                'group' => 'Invoices',
            ],
            'invoices.delete' => [
                'label' => 'Delete Invoices',
                'description' => 'Can delete invoices',
                'group' => 'Invoices',
            ],
            'invoices.send' => [
                'label' => 'Send Invoices',
                'description' => 'Can send invoices via email',
                'group' => 'Invoices',
            ],
            'invoices.reports' => [
                'label' => 'Invoice Reports',
                'description' => 'Can access invoice reports',
                'group' => 'Invoices',
            ],
            'invoices.settings' => [
                'label' => 'Invoice Settings',
                'description' => 'Can configure invoice plugin settings',
                'group' => 'Invoices',
            ],
        ];
    }

    /**
     * Get menu items registered by this plugin
     */
    public function getMenuItems(): array
    {
        return [
            [
                'id' => 'invoices',
                'label' => 'Invoices',
                'icon' => 'file-text',
                'route' => 'invoices.index',
                'permission' => 'invoices.view',
                'position' => 30,
                'children' => [
                    [
                        'id' => 'invoices.list',
                        'label' => 'All Invoices',
                        'route' => 'invoices.index',
                        'permission' => 'invoices.view',
                    ],
                    [
                        'id' => 'invoices.create',
                        'label' => 'Create Invoice',
                        'route' => 'invoices.create',
                        'permission' => 'invoices.create',
                    ],
                    [
                        'id' => 'invoices.reports',
                        'label' => 'Reports',
                        'route' => 'invoices.reports',
                        'permission' => 'invoices.reports',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get dashboard widgets registered by this plugin
     */
    public function getWidgets(): array
    {
        return [
            [
                'id' => 'invoice-summary',
                'name' => 'Invoice Summary',
                'description' => 'Overview of invoice statistics',
                'component' => 'invoice-manager::widgets.summary',
                'permissions' => ['invoices.view'],
                'default_width' => 4,
                'default_height' => 2,
            ],
            [
                'id' => 'recent-invoices',
                'name' => 'Recent Invoices',
                'description' => 'List of recent invoices',
                'component' => 'invoice-manager::widgets.recent',
                'permissions' => ['invoices.view'],
                'default_width' => 6,
                'default_height' => 3,
            ],
            [
                'id' => 'payment-chart',
                'name' => 'Payment Chart',
                'description' => 'Payment trends visualization',
                'component' => 'invoice-manager::widgets.payment-chart',
                'permissions' => ['invoices.reports'],
                'default_width' => 6,
                'default_height' => 3,
            ],
        ];
    }

    /**
     * Get entity definitions registered by this plugin
     */
    public function getEntities(): array
    {
        return [
            'invoice' => [
                'label' => 'Invoice',
                'label_plural' => 'Invoices',
                'model' => \InvoiceManager\Models\Invoice::class,
                'table' => 'invoices',
                'icon' => 'file-text',
                'searchable' => true,
                'fields' => $this->getInvoiceFields(),
            ],
            'invoice_item' => [
                'label' => 'Invoice Item',
                'label_plural' => 'Invoice Items',
                'model' => \InvoiceManager\Models\InvoiceItem::class,
                'table' => 'invoice_items',
                'parent' => 'invoice',
            ],
        ];
    }

    /**
     * Get API endpoints registered by this plugin
     */
    public function getApiEndpoints(): array
    {
        return [
            [
                'method' => 'GET',
                'path' => '/invoices',
                'name' => 'List Invoices',
                'permission' => 'invoices.view',
                'controller' => 'InvoiceApiController@index',
            ],
            [
                'method' => 'POST',
                'path' => '/invoices',
                'name' => 'Create Invoice',
                'permission' => 'invoices.create',
                'controller' => 'InvoiceApiController@store',
            ],
            // ... more endpoints
        ];
    }

    /**
     * Get scheduled tasks registered by this plugin
     */
    public function getScheduledTasks(): array
    {
        return [
            [
                'name' => 'Send Invoice Reminders',
                'description' => 'Send payment reminders for overdue invoices',
                'command' => 'invoices:send-reminders',
                'schedule' => 'daily',
                'enabled' => true,
            ],
            [
                'name' => 'Generate Monthly Report',
                'description' => 'Generate and email monthly invoice report',
                'command' => 'invoices:monthly-report',
                'schedule' => 'monthly',
                'enabled' => true,
            ],
        ];
    }

    /**
     * Get workflow triggers registered by this plugin
     */
    public function getWorkflowTriggers(): array
    {
        return [
            'invoice.created' => [
                'label' => 'Invoice Created',
                'description' => 'Triggered when a new invoice is created',
                'payload' => ['invoice_id', 'customer_id', 'total_amount'],
            ],
            'invoice.sent' => [
                'label' => 'Invoice Sent',
                'description' => 'Triggered when an invoice is sent to customer',
                'payload' => ['invoice_id', 'customer_id', 'email'],
            ],
            'invoice.paid' => [
                'label' => 'Invoice Paid',
                'description' => 'Triggered when an invoice is marked as paid',
                'payload' => ['invoice_id', 'customer_id', 'amount_paid', 'payment_method'],
            ],
            'invoice.overdue' => [
                'label' => 'Invoice Overdue',
                'description' => 'Triggered when an invoice becomes overdue',
                'payload' => ['invoice_id', 'customer_id', 'days_overdue'],
            ],
        ];
    }

    /**
     * Get shortcodes registered by this plugin
     */
    public function getShortcodes(): array
    {
        return [
            'invoice_link' => [
                'label' => 'Invoice Link',
                'description' => 'Generate a link to view/pay an invoice',
                'handler' => \InvoiceManager\Shortcodes\InvoiceLinkShortcode::class,
                'attributes' => [
                    'id' => ['type' => 'integer', 'required' => true],
                    'text' => ['type' => 'string', 'default' => 'View Invoice'],
                ],
            ],
            'invoice_total' => [
                'label' => 'Invoice Total',
                'description' => 'Display the total amount for an invoice',
                'handler' => \InvoiceManager\Shortcodes\InvoiceTotalShortcode::class,
                'attributes' => [
                    'id' => ['type' => 'integer', 'required' => true],
                    'format' => ['type' => 'string', 'default' => 'currency'],
                ],
            ],
        ];
    }
}
```

---

## Service Provider

```php
<?php

namespace InvoiceManager\Providers;

use Illuminate\Support\ServiceProvider;
use PluginSystem\Facades\Hook;
use PluginSystem\Facades\Filter;

class InvoiceManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind services
        $this->app->singleton('invoice-manager', function ($app) {
            return new \InvoiceManager\Services\InvoiceService(
                $app->make(\InvoiceManager\Repositories\InvoiceRepository::class)
            );
        });

        // Register facade
        $this->app->alias('invoice-manager', \InvoiceManager\Facades\InvoiceManager::class);
    }

    public function boot(): void
    {
        $this->registerHooks();
        $this->registerFilters();
        $this->registerViewComposers();
        $this->registerObservers();
    }

    protected function registerHooks(): void
    {
        // Hook into customer deletion to handle related invoices
        Hook::listen('customer.deleting', function ($customer) {
            // Check for unpaid invoices
            $unpaidCount = $customer->invoices()->whereNotIn('status', ['paid', 'cancelled'])->count();
            
            if ($unpaidCount > 0) {
                throw new \Exception("Cannot delete customer with {$unpaidCount} unpaid invoices");
            }
        });

        // Hook into dashboard to add invoice stats
        Hook::listen('dashboard.stats', function ($stats) {
            $stats['invoices'] = [
                'total' => \InvoiceManager\Models\Invoice::count(),
                'unpaid' => \InvoiceManager\Models\Invoice::where('status', 'unpaid')->count(),
                'overdue' => \InvoiceManager\Models\Invoice::overdue()->count(),
            ];
            return $stats;
        });

        // Hook into user profile
        Hook::listen('user.profile.tabs', function ($tabs, $user) {
            if (auth()->user()->can('invoices.view')) {
                $tabs[] = [
                    'id' => 'invoices',
                    'label' => 'Invoices',
                    'view' => 'invoice-manager::partials.user-invoices',
                    'data' => ['invoices' => $user->invoices()->latest()->limit(10)->get()],
                ];
            }
            return $tabs;
        });
    }

    protected function registerFilters(): void
    {
        // Filter export formats
        Filter::add('export.formats', function ($formats) {
            $formats['invoice_pdf'] = [
                'label' => 'Invoice PDF',
                'handler' => \InvoiceManager\Exports\InvoicePdfExport::class,
            ];
            return $formats;
        });

        // Filter search results
        Filter::add('global.search.results', function ($results, $query) {
            if (auth()->user()->can('invoices.view')) {
                $invoices = \InvoiceManager\Models\Invoice::search($query)->limit(5)->get();
                
                foreach ($invoices as $invoice) {
                    $results[] = [
                        'type' => 'invoice',
                        'title' => $invoice->number,
                        'subtitle' => $invoice->customer->name,
                        'url' => route('invoices.show', $invoice),
                        'icon' => 'file-text',
                    ];
                }
            }
            return $results;
        });

        // Filter notification channels
        Filter::add('notification.channels', function ($channels) {
            $channels['invoice_email'] = [
                'label' => 'Invoice Email',
                'handler' => \InvoiceManager\Notifications\InvoiceEmailChannel::class,
            ];
            return $channels;
        });
    }

    protected function registerViewComposers(): void
    {
        // Compose invoice views with common data
        view()->composer('invoice-manager::*', function ($view) {
            $view->with('invoiceSettings', app('invoice-manager')->getSettings());
        });
    }

    protected function registerObservers(): void
    {
        \InvoiceManager\Models\Invoice::observe(\InvoiceManager\Observers\InvoiceObserver::class);
    }
}
```

---

## Hook System Integration

### Available Hooks

Plugins can listen to these hooks from the Plugin Management system:

```php
// Plugin lifecycle hooks
Hook::listen('plugin.installing', function ($slug, $manifest) { });
Hook::listen('plugin.installed', function ($plugin) { });
Hook::listen('plugin.activating', function ($plugin) { });
Hook::listen('plugin.activated', function ($plugin) { });
Hook::listen('plugin.deactivating', function ($plugin) { });
Hook::listen('plugin.deactivated', function ($plugin) { });
Hook::listen('plugin.updating', function ($plugin, $fromVersion, $toVersion) { });
Hook::listen('plugin.updated', function ($plugin, $fromVersion, $toVersion) { });
Hook::listen('plugin.uninstalling', function ($plugin) { });
Hook::listen('plugin.uninstalled', function ($slug) { });

// Plugin settings hooks
Hook::listen('plugin.settings.saving', function ($plugin, $settings) { });
Hook::listen('plugin.settings.saved', function ($plugin, $settings) { });

// License hooks
Hook::listen('plugin.license.activated', function ($plugin, $license) { });
Hook::listen('plugin.license.expired', function ($plugin, $license) { });
Hook::listen('plugin.license.invalid', function ($plugin) { });
```

### Firing Custom Hooks

```php
<?php

namespace InvoiceManager\Services;

use PluginSystem\Facades\Hook;

class InvoiceService
{
    public function createInvoice(array $data): Invoice
    {
        // Before creation hook
        $data = Hook::filter('invoice.creating', $data);
        
        $invoice = Invoice::create($data);
        
        // After creation hook
        Hook::fire('invoice.created', $invoice);
        
        return $invoice;
    }

    public function sendInvoice(Invoice $invoice): bool
    {
        // Before send hook - can cancel by returning false
        if (Hook::fire('invoice.sending', $invoice) === false) {
            return false;
        }
        
        // Send logic...
        $sent = $this->emailService->send($invoice);
        
        if ($sent) {
            Hook::fire('invoice.sent', $invoice);
        }
        
        return $sent;
    }
}
```

---

## Filter System Integration

### Using Filters

```php
<?php

namespace InvoiceManager\Services;

use PluginSystem\Facades\Filter;

class InvoiceService
{
    public function calculateTotal(Invoice $invoice): float
    {
        $subtotal = $invoice->items->sum('total');
        
        // Allow other plugins to modify subtotal
        $subtotal = Filter::apply('invoice.subtotal', $subtotal, $invoice);
        
        // Calculate tax
        $tax = $this->calculateTax($subtotal);
        $tax = Filter::apply('invoice.tax', $tax, $invoice, $subtotal);
        
        // Calculate discount
        $discount = $this->calculateDiscount($subtotal);
        $discount = Filter::apply('invoice.discount', $discount, $invoice, $subtotal);
        
        $total = $subtotal + $tax - $discount;
        
        // Final total filter
        return Filter::apply('invoice.total', $total, $invoice);
    }

    public function getInvoiceStatuses(): array
    {
        $statuses = [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ];
        
        // Allow plugins to add custom statuses
        return Filter::apply('invoice.statuses', $statuses);
    }
}
```

### Registering Filters

```php
// In ServiceProvider or plugin boot method

// Add custom invoice status
Filter::add('invoice.statuses', function ($statuses) {
    $statuses['partial'] = 'Partially Paid';
    return $statuses;
}, priority: 10);

// Modify invoice total (e.g., add service fee)
Filter::add('invoice.total', function ($total, $invoice) {
    if ($invoice->payment_method === 'credit_card') {
        $total += $total * 0.029; // 2.9% processing fee
    }
    return $total;
}, priority: 20);
```

---

## Event System Integration

### Defining Events

```php
<?php

namespace InvoiceManager\Events;

use Illuminate\Queue\SerializesModels;
use InvoiceManager\Models\Invoice;

class InvoiceCreated
{
    use SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}
}

class InvoicePaid
{
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public float $amountPaid,
        public string $paymentMethod
    ) {}
}
```

### Event Listeners

```php
<?php

namespace InvoiceManager\Listeners;

use InvoiceManager\Events\InvoicePaid;
use PluginSystem\Facades\Workflow;

class HandleInvoicePaid
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        
        // Update invoice status
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        
        // Trigger workflow
        Workflow::trigger('invoice.paid', [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount_paid' => $event->amountPaid,
            'payment_method' => $event->paymentMethod,
        ]);
        
        // Notify customer
        $invoice->customer->notify(new \InvoiceManager\Notifications\InvoicePaidNotification($invoice));
    }
}
```

---

## View Extension Points

### Blade Directives

```php
// Registered by plugin system
@pluginSlot('invoice.header')
@pluginSlot('invoice.footer')
@pluginSlot('invoice.actions')

// Check plugin feature
@pluginFeature('invoice-manager', 'advanced-reports')
    {{-- Show advanced reports --}}
@endPluginFeature

// Check plugin active
@pluginActive('invoice-manager')
    {{-- Show invoice-related content --}}
@endPluginActive
```

### View Slots

```blade
{{-- In invoice show view --}}
<div class="invoice-header">
    <h1>{{ $invoice->number }}</h1>
    
    {{-- Extension point for other plugins --}}
    @pluginSlot('invoice.header.actions', ['invoice' => $invoice])
</div>

<div class="invoice-body">
    {{-- Invoice content --}}
</div>

<div class="invoice-sidebar">
    {{-- Extension point for sidebar widgets --}}
    @pluginSlot('invoice.sidebar', ['invoice' => $invoice])
</div>
```

### Registering Slot Content

```php
// In another plugin that extends invoice-manager
Hook::listen('view.slot.invoice.header.actions', function ($invoice) {
    return view('my-plugin::partials.invoice-header-button', compact('invoice'))->render();
});

Hook::listen('view.slot.invoice.sidebar', function ($invoice) {
    return view('my-plugin::partials.invoice-sidebar-widget', compact('invoice'))->render();
});
```

---

## API Extension

### Extending Plugin API

```php
<?php

// Other plugins can extend the invoice API

// In ServiceProvider
public function boot(): void
{
    // Add custom endpoint to invoice API
    Hook::listen('api.routes.invoice-manager', function ($router) {
        $router->get('/invoices/{invoice}/custom-action', [CustomController::class, 'action']);
    });
    
    // Add custom data to invoice API response
    Filter::add('api.response.invoice', function ($data, $invoice) {
        $data['custom_field'] = $this->getCustomData($invoice);
        return $data;
    });
}
```

### API Response Transformation

```php
<?php

namespace InvoiceManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use PluginSystem\Facades\Filter;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'total' => $this->total,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toISOString(),
        ];
        
        // Allow plugins to extend the response
        return Filter::apply('api.resource.invoice', $data, $this->resource);
    }
}
```

---

## Database Integration

### Using Plugin Migrator

```php
<?php

// Migration file: database/migrations/2024_01_01_000001_create_invoices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plugin identifier for tracking
     */
    public string $plugin = 'invoice-manager';

    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'due_date']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
```

### Extending Core Tables

```php
<?php

// Add columns to existing tables (safely)

return new class extends Migration
{
    public string $plugin = 'invoice-manager';

    public function up(): void
    {
        // Add invoice_count to customers table if it exists
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'invoice_count')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedInteger('invoice_count')->default(0)->after('email');
                $table->decimal('total_invoiced', 12, 2)->default(0)->after('invoice_count');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn(['invoice_count', 'total_invoiced']);
            });
        }
    }
};
```

---

## Testing Plugins

### Test Setup

```php
<?php

namespace InvoiceManager\Tests;

use PluginSystem\Testing\PluginTestCase;

abstract class TestCase extends PluginTestCase
{
    protected string $pluginSlug = 'invoice-manager';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Activate plugin for testing
        $this->activatePlugin($this->pluginSlug);
        
        // Run plugin migrations
        $this->runPluginMigrations($this->pluginSlug);
    }

    protected function tearDown(): void
    {
        // Cleanup
        $this->deactivatePlugin($this->pluginSlug);
        
        parent::tearDown();
    }
}
```

### Feature Tests

```php
<?php

namespace InvoiceManager\Tests\Feature;

use InvoiceManager\Tests\TestCase;
use InvoiceManager\Models\Invoice;

class InvoiceManagementTest extends TestCase
{
    public function test_can_create_invoice(): void
    {
        $this->actingAsAdmin();
        
        $response = $this->postJson('/api/v1/invoices', [
            'customer_id' => 1,
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'price' => 100],
            ],
        ]);
        
        $response->assertStatus(201)
                 ->assertJsonStructure(['data' => ['id', 'number', 'total']]);
    }

    public function test_hooks_are_fired_on_invoice_creation(): void
    {
        $this->actingAsAdmin();
        
        $hookFired = false;
        Hook::listen('invoice.created', function ($invoice) use (&$hookFired) {
            $hookFired = true;
        });
        
        Invoice::factory()->create();
        
        $this->assertTrue($hookFired);
    }
}
```

---

## Best Practices

### 1. Always Use Hooks for Cross-Plugin Communication

```php
// Good - allows other plugins to extend
Hook::fire('invoice.created', $invoice);
$total = Filter::apply('invoice.total', $total, $invoice);

// Bad - direct coupling
app('other-plugin')->handleInvoice($invoice);
```

### 2. Declare Dependencies Explicitly

```json
{
    "dependencies": {
        "core-finance": "^1.5.0"
    },
    "optional_dependencies": {
        "advanced-reports": "^1.0.0"
    }
}
```

### 3. Use Feature Flags for Optional Integration

```php
if ($this->pluginManager->isActive('advanced-reports')) {
    // Use advanced reporting features
} else {
    // Fall back to basic reporting
}
```

### 4. Clean Up on Uninstall

```php
public function onUninstall(bool $keepData = false): void
{
    // Remove scheduled tasks
    $this->removeScheduledTasks();
    
    // Remove menu items
    $this->removeMenuItems();
    
    // Remove permissions
    $this->removePermissions();
    
    // Optionally keep data
    if (!$keepData) {
        $this->dropTables();
    }
}
```

### 5. Version Your Settings

```php
public function onUpdate(string $from, string $to): void
{
    // Migrate settings between versions
    if (version_compare($from, '2.0.0', '<')) {
        $this->migrateSettingsToV2();
    }
}
```
