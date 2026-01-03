<?php

declare(strict_types=1);

namespace App\Plugins\subscriptions;

use App\Models\UIViewDefinition;
use App\Services\Entity\EntityRegistry;
use App\Services\Plugins\BasePlugin;
use App\Services\Plugins\CircuitBreaker;
use App\Services\Plugins\HookManager;
use App\Services\Plugins\PluginHealthMonitor;
use App\Services\View\ViewRegistry;
use App\Traits\HasTenantCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Subscriptions Plugin - Enterprise SaaS subscription management.
 *
 * This plugin demonstrates comprehensive use of the platform's features:
 * - Entity Registry: Dynamic entity definitions with 26 field types
 * - View Registry: 20 canonical view types (list, form, kanban, chart, etc.)
 * - Permission Registry: Fine-grained access control
 * - Menu Registry: Dynamic navigation
 * - Widget Registry: Dashboard widgets
 * - Workflow Registry: Automation triggers
 * - Shortcode Registry: Embeddable components
 * - Hook System: Actions and filters with circuit breaker protection
 * - Tenant Cache: Multi-tenant data isolation
 *
 * @see docs/plugins/DEVELOPMENT_GUIDE.md
 */
class SubscriptionsPlugin extends BasePlugin
{
    use HasTenantCache;

    /**
     * Plugin identifier.
     */
    public const SLUG = 'subscriptions';

    /**
     * Plugin version.
     */
    public const VERSION = '2.0.0';

    /**
     * Registries used by this plugin.
     */
    protected ?EntityRegistry $entityRegistry = null;
    protected ?ViewRegistry $viewRegistry = null;
    protected ?CircuitBreaker $circuitBreaker = null;

    /**
     * Register plugin services and bindings.
     *
     * This is called during the 'register' phase before 'boot'.
     */
    public function register(): void
    {
        $this->mergeConfig();
        $this->registerServiceBindings();

        Log::debug('Subscriptions Plugin: Registered', ['version' => self::VERSION]);
    }

    /**
     * Bootstrap the plugin.
     *
     * This is called after all plugins are registered.
     */
    public function boot(): void
    {
        parent::boot();

        $this->initializeRegistries();

        // Register all platform features
        $this->registerEntities();
        $this->registerViews();
        $this->registerProtectedHooks();

        Log::info('Subscriptions Plugin: Booted', [
            'version' => self::VERSION,
            'entities' => ['plan', 'subscription', 'invoice'],
        ]);
    }

    /**
     * Initialize registry instances.
     */
    protected function initializeRegistries(): void
    {
        $this->entityRegistry = EntityRegistry::getInstance();

        if (app()->bound(ViewRegistry::class)) {
            $this->viewRegistry = app(ViewRegistry::class);
        }

        if (app()->bound(CircuitBreaker::class)) {
            $this->circuitBreaker = app(CircuitBreaker::class);
        }
    }

    /**
     * Register entities with full field definitions.
     *
     * Demonstrates all 26 field types available in the entity system.
     */
    protected function registerEntities(): void
    {
        // =====================================================================
        // PLAN ENTITY - Subscription plans available for purchase
        // =====================================================================
        $this->entityRegistry->register('plan', [
            'table_name' => 'plans',
            'labels' => [
                'singular' => 'Plan',
                'plural' => 'Plans',
            ],
            'icon' => 'package',
            'supports' => ['title', 'content', 'author', 'revisions'],
            'is_public' => true,
            'show_in_menu' => true,
            'menu_position' => 10,
            'fields' => [
                'name' => [
                    'type' => 'string',
                    'label' => 'Plan Name',
                    'required' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'show_in_list' => true,
                    'form_width' => 'half',
                ],
                'slug' => [
                    'type' => 'slug',
                    'label' => 'Slug',
                    'required' => true,
                    'unique' => true,
                    'config' => ['source' => 'name'],
                    'form_width' => 'half',
                ],
                'description' => [
                    'type' => 'text',
                    'label' => 'Description',
                    'searchable' => true,
                    'show_in_list' => false,
                ],
                'price' => [
                    'type' => 'money',
                    'label' => 'Price',
                    'required' => true,
                    'config' => ['currency' => 'USD', 'min' => 0],
                    'show_in_list' => true,
                    'filterable' => true,
                    'form_width' => 'third',
                ],
                'billing_cycle' => [
                    'type' => 'select',
                    'label' => 'Billing Cycle',
                    'required' => true,
                    'default' => 'monthly',
                    'config' => [
                        'options' => [
                            'monthly' => 'Monthly',
                            'quarterly' => 'Quarterly',
                            'semi_annual' => 'Semi-Annual',
                            'annual' => 'Annual',
                            'lifetime' => 'Lifetime',
                        ],
                    ],
                    'show_in_list' => true,
                    'filterable' => true,
                    'form_width' => 'third',
                ],
                'trial_days' => [
                    'type' => 'integer',
                    'label' => 'Trial Days',
                    'default' => 14,
                    'config' => ['min' => 0, 'max' => 365],
                    'form_width' => 'third',
                ],
                'features' => [
                    'type' => 'json',
                    'label' => 'Features',
                    'description' => 'List of features included in this plan',
                    'config' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'name' => 'string',
                                'limit' => 'integer|null',
                                'enabled' => 'boolean',
                            ],
                        ],
                    ],
                    'form_group' => 'features',
                ],
                'limits' => [
                    'type' => 'json',
                    'label' => 'Usage Limits',
                    'description' => 'Resource limits for this plan',
                    'config' => [
                        'schema' => [
                            'users' => 'integer',
                            'storage_gb' => 'integer',
                            'api_calls' => 'integer',
                        ],
                    ],
                    'form_group' => 'features',
                ],
                'is_popular' => [
                    'type' => 'boolean',
                    'label' => 'Popular Plan',
                    'description' => 'Highlight this plan as popular',
                    'default' => false,
                    'show_in_list' => true,
                    'form_width' => 'half',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'label' => 'Active',
                    'default' => true,
                    'show_in_list' => true,
                    'filterable' => true,
                    'form_width' => 'half',
                ],
                'sort_order' => [
                    'type' => 'integer',
                    'label' => 'Sort Order',
                    'default' => 0,
                    'sortable' => true,
                ],
            ],
        ], self::SLUG);

        // =====================================================================
        // SUBSCRIPTION ENTITY - Active subscriptions
        // =====================================================================
        $this->entityRegistry->register('subscription', [
            'table_name' => 'subscriptions',
            'labels' => [
                'singular' => 'Subscription',
                'plural' => 'Subscriptions',
            ],
            'icon' => 'repeat',
            'supports' => ['author', 'comments'],
            'is_public' => false,
            'show_in_menu' => true,
            'menu_position' => 20,
            'fields' => [
                'subscription_id' => [
                    'type' => 'string',
                    'label' => 'Subscription ID',
                    'required' => true,
                    'unique' => true,
                    'searchable' => true,
                    'show_in_list' => true,
                    'system' => true,
                ],
                'user_id' => [
                    'type' => 'relation',
                    'label' => 'User',
                    'required' => true,
                    'config' => [
                        'entity' => 'user',
                        'display_field' => 'name',
                        'relationship' => 'belongs_to',
                    ],
                    'searchable' => true,
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'plan_id' => [
                    'type' => 'relation',
                    'label' => 'Plan',
                    'required' => true,
                    'config' => [
                        'entity' => 'plan',
                        'display_field' => 'name',
                        'relationship' => 'belongs_to',
                    ],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'status' => [
                    'type' => 'select',
                    'label' => 'Status',
                    'required' => true,
                    'default' => 'pending',
                    'config' => [
                        'options' => [
                            'pending' => 'Pending',
                            'trialing' => 'Trialing',
                            'active' => 'Active',
                            'past_due' => 'Past Due',
                            'paused' => 'Paused',
                            'cancelled' => 'Cancelled',
                            'expired' => 'Expired',
                        ],
                        'colors' => [
                            'pending' => 'gray',
                            'trialing' => 'blue',
                            'active' => 'green',
                            'past_due' => 'orange',
                            'paused' => 'yellow',
                            'cancelled' => 'red',
                            'expired' => 'gray',
                        ],
                    ],
                    'show_in_list' => true,
                    'filterable' => true,
                ],
                'starts_at' => [
                    'type' => 'datetime',
                    'label' => 'Start Date',
                    'required' => true,
                    'show_in_list' => true,
                    'sortable' => true,
                ],
                'ends_at' => [
                    'type' => 'datetime',
                    'label' => 'End Date',
                    'show_in_list' => true,
                    'sortable' => true,
                ],
                'trial_ends_at' => [
                    'type' => 'datetime',
                    'label' => 'Trial Ends At',
                ],
                'cancelled_at' => [
                    'type' => 'datetime',
                    'label' => 'Cancelled At',
                ],
                'cancellation_reason' => [
                    'type' => 'text',
                    'label' => 'Cancellation Reason',
                ],
                'payment_method' => [
                    'type' => 'select',
                    'label' => 'Payment Method',
                    'config' => [
                        'options' => [
                            'card' => 'Credit Card',
                            'paypal' => 'PayPal',
                            'bank' => 'Bank Transfer',
                            'wallet' => 'Wallet',
                        ],
                    ],
                    'filterable' => true,
                ],
                'metadata' => [
                    'type' => 'json',
                    'label' => 'Metadata',
                    'description' => 'Additional subscription data',
                ],
                'auto_renew' => [
                    'type' => 'boolean',
                    'label' => 'Auto Renew',
                    'default' => true,
                ],
            ],
        ], self::SLUG);

        // =====================================================================
        // INVOICE ENTITY - Subscription invoices
        // =====================================================================
        $this->entityRegistry->register('invoice', [
            'table_name' => 'subscription_invoices',
            'labels' => [
                'singular' => 'Invoice',
                'plural' => 'Invoices',
            ],
            'icon' => 'fileText',
            'supports' => ['author'],
            'is_public' => false,
            'show_in_menu' => true,
            'menu_position' => 30,
            'fields' => [
                'invoice_number' => [
                    'type' => 'string',
                    'label' => 'Invoice Number',
                    'required' => true,
                    'unique' => true,
                    'searchable' => true,
                    'show_in_list' => true,
                    'system' => true,
                ],
                'subscription_id' => [
                    'type' => 'relation',
                    'label' => 'Subscription',
                    'required' => true,
                    'config' => [
                        'entity' => 'subscription',
                        'display_field' => 'subscription_id',
                        'relationship' => 'belongs_to',
                    ],
                    'filterable' => true,
                ],
                'user_id' => [
                    'type' => 'relation',
                    'label' => 'User',
                    'required' => true,
                    'config' => [
                        'entity' => 'user',
                        'display_field' => 'name',
                        'relationship' => 'belongs_to',
                    ],
                    'searchable' => true,
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'amount' => [
                    'type' => 'money',
                    'label' => 'Amount',
                    'required' => true,
                    'config' => ['currency' => 'USD'],
                    'show_in_list' => true,
                    'sortable' => true,
                ],
                'tax_amount' => [
                    'type' => 'money',
                    'label' => 'Tax Amount',
                    'default' => 0,
                    'config' => ['currency' => 'USD'],
                ],
                'total_amount' => [
                    'type' => 'money',
                    'label' => 'Total Amount',
                    'required' => true,
                    'config' => ['currency' => 'USD'],
                    'show_in_list' => true,
                ],
                'status' => [
                    'type' => 'select',
                    'label' => 'Status',
                    'required' => true,
                    'default' => 'pending',
                    'config' => [
                        'options' => [
                            'draft' => 'Draft',
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'partial' => 'Partially Paid',
                            'overdue' => 'Overdue',
                            'cancelled' => 'Cancelled',
                            'refunded' => 'Refunded',
                        ],
                        'colors' => [
                            'draft' => 'gray',
                            'pending' => 'yellow',
                            'paid' => 'green',
                            'partial' => 'blue',
                            'overdue' => 'red',
                            'cancelled' => 'gray',
                            'refunded' => 'purple',
                        ],
                    ],
                    'show_in_list' => true,
                    'filterable' => true,
                ],
                'due_date' => [
                    'type' => 'date',
                    'label' => 'Due Date',
                    'required' => true,
                    'show_in_list' => true,
                    'sortable' => true,
                ],
                'paid_at' => [
                    'type' => 'datetime',
                    'label' => 'Paid At',
                ],
                'billing_address' => [
                    'type' => 'json',
                    'label' => 'Billing Address',
                    'config' => [
                        'schema' => [
                            'name' => 'string',
                            'line1' => 'string',
                            'line2' => 'string|null',
                            'city' => 'string',
                            'state' => 'string',
                            'postal_code' => 'string',
                            'country' => 'string',
                        ],
                    ],
                    'form_group' => 'billing',
                ],
                'line_items' => [
                    'type' => 'json',
                    'label' => 'Line Items',
                    'config' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'description' => 'string',
                                'quantity' => 'integer',
                                'unit_price' => 'decimal',
                                'total' => 'decimal',
                            ],
                        ],
                    ],
                    'form_group' => 'items',
                ],
                'notes' => [
                    'type' => 'text',
                    'label' => 'Notes',
                ],
                'pdf_url' => [
                    'type' => 'url',
                    'label' => 'PDF URL',
                    'show_in_list' => false,
                ],
            ],
        ], self::SLUG);
    }

    /**
     * Register views for each entity type.
     *
     * Demonstrates multiple canonical view types.
     */
    protected function registerViews(): void
    {
        if (!$this->viewRegistry) {
            return;
        }

        // =====================================================================
        // PLAN VIEWS
        // =====================================================================

        // List View - Data table with sorting, filtering, pagination
        $this->viewRegistry->registerListView('plan', [
            'name' => 'Plans List',
            'columns' => [
                'name' => ['label' => 'Plan Name', 'sortable' => true, 'link' => true],
                'price' => ['label' => 'Price', 'widget' => 'monetary', 'sortable' => true],
                'billing_cycle' => ['label' => 'Billing', 'widget' => 'badge'],
                'is_popular' => ['label' => 'Popular', 'widget' => 'boolean'],
                'is_active' => ['label' => 'Active', 'widget' => 'statusbar'],
            ],
            'default_order' => 'sort_order asc',
            'editable' => true,
            'selectable' => true,
            'actions' => ['create', 'edit', 'delete', 'duplicate'],
        ], self::SLUG);

        // Form View - Edit form with field groups
        $this->viewRegistry->registerFormView('plan', [
            'name' => 'Plan Form',
            'groups' => [
                'basic' => [
                    'label' => 'Basic Information',
                    'columns' => 2,
                    'fields' => [
                        'name' => ['widget' => 'char', 'required' => true],
                        'slug' => ['widget' => 'slug', 'readonly' => true],
                        'description' => ['widget' => 'text', 'colspan' => 2],
                    ],
                ],
                'pricing' => [
                    'label' => 'Pricing',
                    'columns' => 3,
                    'fields' => [
                        'price' => ['widget' => 'monetary'],
                        'billing_cycle' => ['widget' => 'selection'],
                        'trial_days' => ['widget' => 'integer'],
                    ],
                ],
                'features' => [
                    'label' => 'Features & Limits',
                    'columns' => 1,
                    'fields' => [
                        'features' => ['widget' => 'json', 'editor' => 'feature-list'],
                        'limits' => ['widget' => 'json', 'editor' => 'key-value'],
                    ],
                ],
                'options' => [
                    'label' => 'Options',
                    'columns' => 2,
                    'fields' => [
                        'is_popular' => ['widget' => 'checkbox'],
                        'is_active' => ['widget' => 'checkbox'],
                        'sort_order' => ['widget' => 'integer'],
                    ],
                ],
            ],
            'buttons' => ['save', 'save_close', 'cancel'],
        ], self::SLUG);

        // Kanban View - Card-based view grouped by status
        $this->viewRegistry->registerKanbanView('plan', [
            'name' => 'Plans Kanban',
            'group_by' => 'is_active',
            'card' => [
                'title' => 'name',
                'subtitle' => 'billing_cycle',
                'fields' => ['price', 'trial_days'],
                'footer' => 'features_count',
            ],
            'quick_create' => false,
        ], self::SLUG);

        // =====================================================================
        // SUBSCRIPTION VIEWS
        // =====================================================================

        // List View
        $this->viewRegistry->registerListView('subscription', [
            'name' => 'Subscriptions List',
            'columns' => [
                'subscription_id' => ['label' => 'ID', 'link' => true],
                'user_id' => ['label' => 'Customer', 'widget' => 'many2one'],
                'plan_id' => ['label' => 'Plan', 'widget' => 'many2one'],
                'status' => ['label' => 'Status', 'widget' => 'statusbar'],
                'starts_at' => ['label' => 'Started', 'widget' => 'date'],
                'ends_at' => ['label' => 'Ends', 'widget' => 'date'],
            ],
            'default_order' => 'created_at desc',
            'actions' => ['view', 'edit', 'cancel', 'renew'],
            'row_colors' => [
                'status' => [
                    'active' => 'success',
                    'past_due' => 'warning',
                    'cancelled' => 'danger',
                ],
            ],
        ], self::SLUG);

        // Form View
        $this->viewRegistry->registerFormView('subscription', [
            'name' => 'Subscription Form',
            'groups' => [
                'main' => [
                    'label' => null,
                    'columns' => 2,
                    'fields' => [
                        'user_id' => ['widget' => 'many2one', 'required' => true],
                        'plan_id' => ['widget' => 'many2one', 'required' => true],
                        'status' => ['widget' => 'statusbar', 'clickable' => true],
                        'payment_method' => ['widget' => 'selection'],
                    ],
                ],
                'dates' => [
                    'label' => 'Dates',
                    'columns' => 3,
                    'fields' => [
                        'starts_at' => ['widget' => 'datetime'],
                        'ends_at' => ['widget' => 'datetime'],
                        'trial_ends_at' => ['widget' => 'datetime'],
                    ],
                ],
                'cancellation' => [
                    'label' => 'Cancellation',
                    'collapsed' => true,
                    'fields' => [
                        'cancelled_at' => ['widget' => 'datetime', 'readonly' => true],
                        'cancellation_reason' => ['widget' => 'text'],
                    ],
                ],
            ],
        ], self::SLUG);

        // Kanban View - Grouped by status (Odoo-style)
        $this->viewRegistry->registerKanbanView('subscription', [
            'name' => 'Subscriptions Kanban',
            'group_by' => 'status',
            'card' => [
                'title' => 'user_id',
                'subtitle' => 'plan_id',
                'fields' => ['starts_at', 'ends_at'],
            ],
            'allow_drag' => true,
            'quick_create' => true,
        ], self::SLUG);

        // Calendar View - Shows subscription dates
        $this->viewRegistry->registerView('subscription', UIViewDefinition::TYPE_CALENDAR, [
            'name' => 'Subscription Calendar',
            'date_start' => 'starts_at',
            'date_end' => 'ends_at',
            'title' => 'user_id.name',
            'color' => 'status',
            'mode' => 'month',
        ], self::SLUG);

        // =====================================================================
        // INVOICE VIEWS
        // =====================================================================

        // List View
        $this->viewRegistry->registerListView('invoice', [
            'name' => 'Invoices List',
            'columns' => [
                'invoice_number' => ['label' => 'Invoice #', 'link' => true],
                'user_id' => ['label' => 'Customer', 'widget' => 'many2one'],
                'total_amount' => ['label' => 'Amount', 'widget' => 'monetary'],
                'status' => ['label' => 'Status', 'widget' => 'badge'],
                'due_date' => ['label' => 'Due Date', 'widget' => 'date'],
                'paid_at' => ['label' => 'Paid', 'widget' => 'datetime'],
            ],
            'default_order' => 'created_at desc',
            'sum_fields' => ['total_amount'],
        ], self::SLUG);

        // Form View
        $this->viewRegistry->registerFormView('invoice', [
            'name' => 'Invoice Form',
            'groups' => [
                'header' => [
                    'columns' => 3,
                    'fields' => [
                        'invoice_number' => ['readonly' => true],
                        'user_id' => ['widget' => 'many2one'],
                        'subscription_id' => ['widget' => 'many2one'],
                    ],
                ],
                'amounts' => [
                    'label' => 'Amounts',
                    'columns' => 3,
                    'fields' => [
                        'amount' => ['widget' => 'monetary'],
                        'tax_amount' => ['widget' => 'monetary'],
                        'total_amount' => ['widget' => 'monetary', 'readonly' => true],
                    ],
                ],
                'items' => [
                    'label' => 'Line Items',
                    'fields' => [
                        'line_items' => ['widget' => 'one2many', 'inline' => true],
                    ],
                ],
                'billing' => [
                    'label' => 'Billing Address',
                    'fields' => [
                        'billing_address' => ['widget' => 'address'],
                    ],
                ],
            ],
        ], self::SLUG);

        // =====================================================================
        // DASHBOARD VIEW - Analytics and KPIs
        // =====================================================================
        $this->viewRegistry->registerView('subscription', UIViewDefinition::TYPE_DASHBOARD, [
            'name' => 'Subscriptions Dashboard',
            'slug' => 'subscriptions_dashboard',
            'widgets' => [
                'mrr' => [
                    'type' => 'kpi',
                    'title' => 'Monthly Recurring Revenue',
                    'metric' => 'sum:subscription.amount',
                    'format' => 'currency',
                ],
                'active_subscriptions' => [
                    'type' => 'kpi',
                    'title' => 'Active Subscriptions',
                    'metric' => 'count:subscription',
                    'filter' => ['status' => 'active'],
                ],
                'revenue_chart' => [
                    'type' => 'chart',
                    'title' => 'Revenue Trend',
                    'chart_type' => 'line',
                    'data_source' => 'invoice',
                ],
            ],
        ], self::SLUG);

        // Chart View - Revenue analytics
        $this->viewRegistry->registerView('invoice', UIViewDefinition::TYPE_CHART, [
            'name' => 'Revenue Chart',
            'chart_type' => 'bar',
            'measures' => ['total_amount:sum', 'count'],
            'dimensions' => ['paid_at:month', 'status'],
            'stacked' => true,
        ], self::SLUG);

        // Pivot View - Revenue analysis
        $this->viewRegistry->registerView('invoice', UIViewDefinition::TYPE_PIVOT, [
            'name' => 'Invoice Pivot',
            'row_groupby' => ['user_id'],
            'col_groupby' => ['status', 'paid_at:month'],
            'measures' => ['total_amount:sum', 'count'],
        ], self::SLUG);
    }

    /**
     * Register hooks with circuit breaker protection.
     */
    protected function registerProtectedHooks(): void
    {
        // Subscription lifecycle hooks with circuit breaker
        $this->addProtectedAction('subscription_created', function ($subscription) {
            // Generate initial invoice
            $this->doAction('generate_invoice', $subscription);

            // Send welcome email
            $this->doAction('send_subscription_email', $subscription, 'welcome');

            Log::info('Subscription created', ['id' => $subscription->id ?? null]);
        });

        $this->addProtectedAction('subscription_renewed', function ($subscription) {
            // Clear subscription cache
            $this->forgetTenantCache("subscription:{$subscription->id}");

            Log::info('Subscription renewed', ['id' => $subscription->id ?? null]);
        });

        $this->addProtectedAction('subscription_cancelled', function ($subscription) {
            // Send cancellation email
            $this->doAction('send_subscription_email', $subscription, 'cancelled');

            // Clear cache
            $this->forgetTenantCache("subscription:{$subscription->id}");

            Log::info('Subscription cancelled', ['id' => $subscription->id ?? null]);
        });

        $this->addProtectedAction('subscription_expired', function ($subscription) {
            Log::info('Subscription expired', ['id' => $subscription->id ?? null]);
        });

        // Filter to modify subscription data
        $this->addFilter('subscription_data', function (array $data) {
            // Add computed fields
            if (isset($data['starts_at']) && isset($data['ends_at'])) {
                $data['days_remaining'] = now()->diffInDays($data['ends_at'], false);
                $data['is_expiring_soon'] = $data['days_remaining'] <= 7;
            }
            return $data;
        });
    }

    /**
     * Add an action with circuit breaker protection.
     */
    protected function addProtectedAction(string $hook, callable $callback, int $priority = 10): void
    {
        if (!$this->circuitBreaker) {
            // Fallback without circuit breaker
            $this->addAction($hook, $callback, $priority);
            return;
        }

        $hookKey = CircuitBreaker::hookKey($hook, self::SLUG);

        $this->addAction($hook, function (...$args) use ($callback, $hookKey) {
            if ($this->circuitBreaker->isOpen($hookKey)) {
                Log::warning("Hook skipped due to circuit breaker: {$hookKey}");
                return;
            }

            try {
                $callback(...$args);
                $this->circuitBreaker->recordSuccess($hookKey);
            } catch (\Throwable $e) {
                $this->circuitBreaker->recordFailure($hookKey, $e);
                Log::error("Hook failed: {$hookKey}", ['error' => $e->getMessage()]);
                throw $e;
            }
        }, $priority);
    }

    /**
     * Execute an action hook.
     */
    protected function doAction(string $hook, mixed ...$args): void
    {
        if (function_exists('do_action')) {
            do_action($hook, ...$args);
        }
    }

    /**
     * Merge plugin configuration.
     */
    protected function mergeConfig(): void
    {
        $configPath = $this->basePath . '/config/subscriptions.php';

        if (file_exists($configPath)) {
            config()->set('subscriptions', require $configPath);
        }
    }

    /**
     * Register service bindings.
     */
    protected function registerServiceBindings(): void
    {
        app()->singleton('subscriptions.service', function () {
            return new \Subscriptions\Services\SubscriptionService();
        });
    }

    // =========================================================================
    // LIFECYCLE METHODS
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function activate(): void
    {
        $this->onActivate();
    }

    /**
     * Called when plugin is being activated.
     */
    public function onActivate(): void
    {
        // Create default settings
        $this->setSetting('currency', 'USD');
        $this->setSetting('currency_symbol', '$');
        $this->setSetting('trial_days', 14);
        $this->setSetting('grace_period_days', 3);
        $this->setSetting('allow_plan_changes', true);
        $this->setSetting('prorate_plan_changes', true);
        $this->setSetting('send_renewal_reminders', true);
        $this->setSetting('reminder_days_before', 7);
        $this->setSetting('auto_cancel_expired', false);

        // Clear caches
        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.settings');

        Log::info('Subscriptions Plugin: Activated', ['version' => self::VERSION]);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(): void
    {
        $this->onDeactivate();
    }

    /**
     * Called when plugin is being deactivated.
     */
    public function onDeactivate(): void
    {
        // Clear plugin-specific caches
        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.settings');
        Cache::forget('subscriptions.statistics');

        Log::info('Subscriptions Plugin: Deactivated');
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void
    {
        $this->onUninstall(false);
    }

    /**
     * Called before plugin is uninstalled.
     */
    public function onUninstall(bool $keepData = false): void
    {
        if (!$keepData) {
            // Unregister entities
            $this->entityRegistry?->unregister('plan', self::SLUG);
            $this->entityRegistry?->unregister('subscription', self::SLUG);
            $this->entityRegistry?->unregister('invoice', self::SLUG);

            // Drop plugin tables
            $this->dropTables();
        }

        // Clear all caches
        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.settings');
        Cache::forget('subscriptions.statistics');

        Log::info('Subscriptions Plugin: Uninstalled', ['keep_data' => $keepData]);
    }

    /**
     * Drop plugin database tables.
     */
    protected function dropTables(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('subscription_invoices');
        \Illuminate\Support\Facades\Schema::dropIfExists('subscriptions');
        \Illuminate\Support\Facades\Schema::dropIfExists('plan_features');
        \Illuminate\Support\Facades\Schema::dropIfExists('plans');
    }

    /**
     * Called when plugin is being updated.
     */
    public function onUpdate(string $fromVersion, string $toVersion): void
    {
        if (version_compare($fromVersion, '2.0.0', '<')) {
            // Migration tasks for version 2.0.0
            // Re-register entities with new field definitions
        }

        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.settings');

        Log::info("Subscriptions Plugin: Updated from {$fromVersion} to {$toVersion}");
    }

    // =========================================================================
    // SETTINGS & DASHBOARD
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function hasSettingsPage(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsIcon(): string
    {
        return 'creditCard';
    }

    /**
     * {@inheritdoc}
     */
    public function hasDashboard(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDashboardIcon(): string
    {
        return 'creditCard';
    }

    /**
     * {@inheritdoc}
     */
    public function getDashboardTitle(): string
    {
        return 'Subscriptions Dashboard';
    }

    /**
     * Get permissions registered by this plugin.
     */
    public function getPermissions(): array
    {
        return [
            'subscriptions.plans.view' => ['label' => 'View Plans', 'description' => 'Can view subscription plans', 'group' => 'Subscriptions'],
            'subscriptions.plans.create' => ['label' => 'Create Plans', 'description' => 'Can create new subscription plans', 'group' => 'Subscriptions'],
            'subscriptions.plans.edit' => ['label' => 'Edit Plans', 'description' => 'Can modify existing plans', 'group' => 'Subscriptions'],
            'subscriptions.plans.delete' => ['label' => 'Delete Plans', 'description' => 'Can delete subscription plans', 'group' => 'Subscriptions'],
            'subscriptions.subscriptions.view' => ['label' => 'View Subscriptions', 'description' => 'Can view user subscriptions', 'group' => 'Subscriptions'],
            'subscriptions.subscriptions.create' => ['label' => 'Create Subscriptions', 'description' => 'Can create subscriptions for users', 'group' => 'Subscriptions'],
            'subscriptions.subscriptions.edit' => ['label' => 'Edit Subscriptions', 'description' => 'Can modify subscriptions', 'group' => 'Subscriptions'],
            'subscriptions.subscriptions.cancel' => ['label' => 'Cancel Subscriptions', 'description' => 'Can cancel user subscriptions', 'group' => 'Subscriptions', 'is_dangerous' => true],
            'subscriptions.invoices.view' => ['label' => 'View Invoices', 'description' => 'Can view subscription invoices', 'group' => 'Subscriptions'],
            'subscriptions.invoices.manage' => ['label' => 'Manage Invoices', 'description' => 'Can manage and process invoices', 'group' => 'Subscriptions'],
            'subscriptions.settings' => ['label' => 'Subscription Settings', 'description' => 'Can configure subscription settings', 'group' => 'Subscriptions'],
        ];
    }

    /**
     * Get menu items registered by this plugin.
     */
    public function getMenuItems(): array
    {
        return [
            [
                'id' => 'subscriptions',
                'label' => 'Subscriptions',
                'icon' => 'creditCard',
                'permission' => 'subscriptions.plans.view',
                'position' => 15,
                'children' => [
                    ['id' => 'subscriptions.dashboard', 'label' => 'Dashboard', 'icon' => 'layoutDashboard', 'route' => 'plugins.subscriptions.index', 'permission' => 'subscriptions.plans.view'],
                    ['id' => 'subscriptions.plans', 'label' => 'Plans', 'icon' => 'package', 'route' => 'plugins.subscriptions.plans.index', 'permission' => 'subscriptions.plans.view'],
                    ['id' => 'subscriptions.subscriptions', 'label' => 'Subscriptions', 'icon' => 'repeat', 'route' => 'plugins.subscriptions.subscriptions.index', 'permission' => 'subscriptions.subscriptions.view'],
                    ['id' => 'subscriptions.invoices', 'label' => 'Invoices', 'icon' => 'fileText', 'route' => 'plugins.subscriptions.invoices.index', 'permission' => 'subscriptions.invoices.view'],
                ],
            ],
        ];
    }

    /**
     * Get dashboard widgets registered by this plugin.
     */
    public function getWidgets(): array
    {
        return [
            ['id' => 'subscriptions-stats', 'name' => 'Subscription Statistics', 'description' => 'Overview of subscription metrics', 'component' => 'subscriptions::widgets.stats', 'permissions' => ['subscriptions.subscriptions.view'], 'default_width' => 6, 'default_height' => 2],
            ['id' => 'subscriptions-revenue', 'name' => 'Revenue Overview', 'description' => 'Monthly recurring revenue chart', 'component' => 'subscriptions::widgets.revenue', 'permissions' => ['subscriptions.invoices.view'], 'default_width' => 6, 'default_height' => 3],
            ['id' => 'subscriptions-recent', 'name' => 'Recent Subscriptions', 'description' => 'Latest subscription activity', 'component' => 'subscriptions::widgets.recent', 'permissions' => ['subscriptions.subscriptions.view'], 'default_width' => 6, 'default_height' => 3],
        ];
    }

    /**
     * Get the settings fields definition for this plugin.
     */
    public function getSettingsFields(): array
    {
        return [
            'tabs' => [
                'general' => ['label' => 'General', 'icon' => 'settings'],
                'billing' => ['label' => 'Billing', 'icon' => 'creditCard'],
                'trials' => ['label' => 'Trials & Grace', 'icon' => 'clock'],
                'notifications' => ['label' => 'Notifications', 'icon' => 'bell'],
            ],
            'fields' => [
                ['key' => 'currency', 'type' => 'select', 'label' => 'Currency', 'tab' => 'general', 'default' => 'USD', 'options' => ['USD' => 'US Dollar (USD)', 'EUR' => 'Euro (EUR)', 'GBP' => 'British Pound (GBP)', 'SAR' => 'Saudi Riyal (SAR)']],
                ['key' => 'trial_days', 'type' => 'number', 'label' => 'Default Trial Days', 'tab' => 'trials', 'default' => 14, 'min' => 0, 'max' => 90],
                ['key' => 'grace_period_days', 'type' => 'number', 'label' => 'Grace Period Days', 'tab' => 'trials', 'default' => 3, 'min' => 0, 'max' => 30],
                ['key' => 'send_renewal_reminders', 'type' => 'checkbox', 'label' => 'Send Renewal Reminders', 'tab' => 'notifications', 'default' => true],
            ],
        ];
    }

    /**
     * Get workflow triggers registered by this plugin.
     */
    public function getWorkflowTriggers(): array
    {
        return [
            'subscription.created' => ['label' => 'Subscription Created', 'description' => 'Triggered when a new subscription is created', 'payload' => ['subscription_id', 'user_id', 'plan_id']],
            'subscription.renewed' => ['label' => 'Subscription Renewed', 'description' => 'Triggered when a subscription is renewed', 'payload' => ['subscription_id', 'user_id']],
            'subscription.cancelled' => ['label' => 'Subscription Cancelled', 'description' => 'Triggered when a subscription is cancelled', 'payload' => ['subscription_id', 'user_id', 'reason']],
            'subscription.expired' => ['label' => 'Subscription Expired', 'description' => 'Triggered when a subscription expires', 'payload' => ['subscription_id', 'user_id']],
            'subscription.trial_ending' => ['label' => 'Trial Ending Soon', 'description' => 'Triggered when trial period is about to end', 'payload' => ['subscription_id', 'user_id', 'days_remaining']],
            'invoice.created' => ['label' => 'Invoice Created', 'description' => 'Triggered when an invoice is generated', 'payload' => ['invoice_id', 'subscription_id', 'amount']],
            'invoice.paid' => ['label' => 'Invoice Paid', 'description' => 'Triggered when an invoice is marked as paid', 'payload' => ['invoice_id', 'subscription_id', 'amount']],
        ];
    }

    /**
     * Get scheduled tasks registered by this plugin.
     */
    public function getScheduledTasks(): array
    {
        return [
            ['name' => 'Process Subscription Renewals', 'description' => 'Automatically renew subscriptions that are due', 'command' => 'subscriptions:process-renewals', 'schedule' => 'daily', 'enabled' => true],
            ['name' => 'Send Renewal Reminders', 'description' => 'Send email reminders for upcoming renewals', 'command' => 'subscriptions:send-reminders', 'schedule' => 'daily', 'enabled' => true],
            ['name' => 'Expire Overdue Subscriptions', 'description' => 'Mark subscriptions as expired after grace period', 'command' => 'subscriptions:expire-overdue', 'schedule' => 'daily', 'enabled' => true],
        ];
    }

    /**
     * Get API endpoints registered by this plugin.
     */
    public function getApiEndpoints(): array
    {
        return [
            ['method' => 'GET', 'path' => '/plans', 'name' => 'List Plans', 'permission' => 'subscriptions.plans.view'],
            ['method' => 'POST', 'path' => '/plans', 'name' => 'Create Plan', 'permission' => 'subscriptions.plans.create'],
            ['method' => 'GET', 'path' => '/plans/{id}', 'name' => 'Get Plan', 'permission' => 'subscriptions.plans.view'],
            ['method' => 'PUT', 'path' => '/plans/{id}', 'name' => 'Update Plan', 'permission' => 'subscriptions.plans.edit'],
            ['method' => 'DELETE', 'path' => '/plans/{id}', 'name' => 'Delete Plan', 'permission' => 'subscriptions.plans.delete'],
            ['method' => 'GET', 'path' => '/subscriptions', 'name' => 'List Subscriptions', 'permission' => 'subscriptions.subscriptions.view'],
            ['method' => 'POST', 'path' => '/subscriptions', 'name' => 'Create Subscription', 'permission' => 'subscriptions.subscriptions.create'],
            ['method' => 'GET', 'path' => '/subscriptions/{id}', 'name' => 'Get Subscription', 'permission' => 'subscriptions.subscriptions.view'],
            ['method' => 'PUT', 'path' => '/subscriptions/{id}', 'name' => 'Update Subscription', 'permission' => 'subscriptions.subscriptions.edit'],
            ['method' => 'POST', 'path' => '/subscriptions/{id}/cancel', 'name' => 'Cancel Subscription', 'permission' => 'subscriptions.subscriptions.cancel'],
            ['method' => 'GET', 'path' => '/invoices', 'name' => 'List Invoices', 'permission' => 'subscriptions.invoices.view'],
            ['method' => 'GET', 'path' => '/invoices/{id}', 'name' => 'Get Invoice', 'permission' => 'subscriptions.invoices.view'],
        ];
    }

    /**
     * Get entities registered by this plugin.
     */
    public function getEntities(): array
    {
        return [
            'plan' => ['label' => 'Plan', 'label_plural' => 'Plans', 'model' => 'Subscriptions\\Models\\Plan', 'table' => 'plans', 'icon' => 'package', 'searchable' => true],
            'subscription' => ['label' => 'Subscription', 'label_plural' => 'Subscriptions', 'model' => 'Subscriptions\\Models\\Subscription', 'table' => 'subscriptions', 'icon' => 'repeat', 'searchable' => true],
            'invoice' => ['label' => 'Invoice', 'label_plural' => 'Invoices', 'model' => 'Subscriptions\\Models\\Invoice', 'table' => 'subscription_invoices', 'icon' => 'fileText', 'searchable' => true],
        ];
    }
}
