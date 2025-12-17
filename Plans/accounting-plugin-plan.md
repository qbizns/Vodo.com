# Accounting Plugin - Master Implementation Plan

## Executive Summary

The Accounting Plugin will serve as the **definitive reference implementation** for the Laravel Plugin System, demonstrating every feature from all 9 phases. This enterprise-grade double-entry accounting system will showcase best practices for plugin development.

---

## Table of Contents

1. [Plugin Overview](#1-plugin-overview)
2. [Architecture Design](#2-architecture-design)
3. [Phase Integration Map](#3-phase-integration-map)
4. [Module Breakdown](#4-module-breakdown)
5. [Database Schema](#5-database-schema)
6. [Implementation Phases](#6-implementation-phases)
7. [File Structure](#7-file-structure)
8. [Detailed Feature Specifications](#8-detailed-feature-specifications)
9. [Testing Strategy](#9-testing-strategy)
10. [Timeline & Milestones](#10-timeline--milestones)

---

## 1. Plugin Overview

### 1.1 Purpose

A complete double-entry accounting system demonstrating:
- All 9 plugin system phases in real-world usage
- Enterprise patterns and best practices
- Multi-tenant and multi-currency support
- Extensibility for other plugins

### 1.2 Core Features

| Module | Description |
|--------|-------------|
| **Chart of Accounts** | Hierarchical account structure with types |
| **General Ledger** | Double-entry journal with posting |
| **Accounts Payable** | Vendor management, bills, payments |
| **Accounts Receivable** | Customer invoicing, receipts |
| **Banking** | Bank accounts, reconciliation |
| **Financial Reports** | Balance Sheet, P&L, Cash Flow |
| **Budgeting** | Budget planning and variance analysis |
| **Multi-Currency** | Exchange rates, conversions |
| **Fiscal Periods** | Period management, year-end close |
| **Audit Trail** | Complete transaction history |

### 1.3 Target Users

- Small to Medium Businesses
- Accountants & Bookkeepers
- Financial Controllers
- Auditors (read-only access)

---

## 2. Architecture Design

### 2.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      ACCOUNTING PLUGIN                          │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │   Web UI    │  │  REST API   │  │  CLI Tools  │             │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘             │
│         │                │                │                     │
│  ┌──────┴────────────────┴────────────────┴──────┐             │
│  │              SERVICE LAYER                     │             │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐         │             │
│  │  │ Ledger  │ │ Reports │ │ Banking │  ...    │             │
│  │  │ Service │ │ Service │ │ Service │         │             │
│  │  └─────────┘ └─────────┘ └─────────┘         │             │
│  └──────────────────────────────────────────────┘             │
│                          │                                     │
│  ┌──────────────────────────────────────────────┐             │
│  │           PLUGIN SYSTEM INTEGRATION           │             │
│  │  ┌────┐ ┌────┐ ┌────┐ ┌────┐ ┌────┐ ┌────┐  │             │
│  │  │ P1 │ │ P2 │ │ P3 │ │ P4 │ │ P5 │ │ P6 │  │             │
│  │  │Ent │ │Hook│ │Fld │ │API │ │Shrt│ │Menu│  │             │
│  │  └────┘ └────┘ └────┘ └────┘ └────┘ └────┘  │             │
│  │  ┌────┐ ┌────┐ ┌────┐                       │             │
│  │  │ P7 │ │ P8 │ │ P9 │                       │             │
│  │  │Perm│ │Schd│ │Mkt │                       │             │
│  │  └────┘ └────┘ └────┘                       │             │
│  └──────────────────────────────────────────────┘             │
│                          │                                     │
│  ┌──────────────────────────────────────────────┐             │
│  │              DATA LAYER                       │             │
│  │  Models │ Repositories │ Query Builders      │             │
│  └──────────────────────────────────────────────┘             │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Design Principles

1. **Domain-Driven Design** - Clear bounded contexts
2. **SOLID Principles** - Clean, maintainable code
3. **Event Sourcing Ready** - All changes tracked
4. **Plugin-First** - Use plugin system features everywhere
5. **Testable** - 100% unit test coverage goal

---

## 3. Phase Integration Map

### Complete Integration Matrix

| Feature | P1 | P2 | P3 | P4 | P5 | P6 | P7 | P8 | P9 |
|---------|----|----|----|----|----|----|----|----|-----|
| Chart of Accounts | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | |
| Journal Entries | ✓ | ✓ | ✓ | ✓ | | | ✓ | ✓ | |
| Invoicing | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Banking | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Reports | | ✓ | | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Settings | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ | | |
| Multi-Currency | ✓ | ✓ | ✓ | ✓ | | | ✓ | ✓ | |
| Audit Trail | | ✓ | | ✓ | | | ✓ | | |
| Premium Features | | | | | | | | | ✓ |

### 3.1 Phase 1: Dynamic Entities

```php
// Entities created at runtime
$entities = [
    'acc_accounts' => [
        'code', 'name', 'type', 'parent_id', 'currency_id',
        'is_active', 'opening_balance', 'current_balance'
    ],
    'acc_journals' => [
        'number', 'date', 'reference', 'description', 
        'status', 'posted_at', 'posted_by'
    ],
    'acc_journal_lines' => [
        'journal_id', 'account_id', 'debit', 'credit',
        'description', 'cost_center_id'
    ],
    'acc_invoices' => [
        'type', 'number', 'contact_id', 'date', 'due_date',
        'subtotal', 'tax', 'total', 'paid', 'status'
    ],
    'acc_payments' => [
        'type', 'number', 'contact_id', 'account_id',
        'date', 'amount', 'reference', 'journal_id'
    ],
    'acc_bank_accounts' => [
        'account_id', 'bank_name', 'account_number',
        'routing_number', 'last_reconciled'
    ],
    'acc_reconciliations' => [
        'bank_account_id', 'statement_date', 'statement_balance',
        'reconciled_balance', 'difference', 'status'
    ],
    'acc_fiscal_years' => [
        'name', 'start_date', 'end_date', 'status', 'closed_at'
    ],
    'acc_budgets' => [
        'fiscal_year_id', 'account_id', 'period', 'amount'
    ],
    'acc_currencies' => [
        'code', 'name', 'symbol', 'decimal_places', 
        'exchange_rate', 'is_base'
    ],
    'acc_cost_centers' => [
        'code', 'name', 'parent_id', 'manager_id', 'is_active'
    ],
    'acc_tax_rates' => [
        'name', 'rate', 'type', 'account_id', 'is_default'
    ],
    'acc_contacts' => [
        'type', 'name', 'email', 'phone', 'tax_number',
        'payment_terms', 'credit_limit', 'balance'
    ],
];
```

### 3.2 Phase 2: Hook System

```php
// Actions (30+)
$actions = [
    // Journal lifecycle
    'acc_journal_creating',
    'acc_journal_created',
    'acc_journal_posting',
    'acc_journal_posted',
    'acc_journal_voiding',
    'acc_journal_voided',
    
    // Invoice lifecycle
    'acc_invoice_creating',
    'acc_invoice_created',
    'acc_invoice_sending',
    'acc_invoice_sent',
    'acc_invoice_paid',
    'acc_invoice_overdue',
    
    // Payment lifecycle
    'acc_payment_creating',
    'acc_payment_created',
    'acc_payment_applied',
    
    // Banking
    'acc_reconciliation_starting',
    'acc_reconciliation_completed',
    'acc_bank_transaction_imported',
    
    // Period management
    'acc_period_closing',
    'acc_period_closed',
    'acc_year_closing',
    'acc_year_closed',
    
    // Reports
    'acc_report_generating',
    'acc_report_generated',
    'acc_report_exporting',
];

// Filters (25+)
$filters = [
    // Formatting
    'acc_format_money',
    'acc_format_account_code',
    'acc_format_invoice_number',
    
    // Calculations
    'acc_calculate_tax',
    'acc_calculate_discount',
    'acc_calculate_exchange_rate',
    'acc_calculate_balance',
    
    // Validation
    'acc_validate_journal',
    'acc_validate_invoice',
    'acc_validate_payment',
    
    // Display
    'acc_account_display_name',
    'acc_contact_display_name',
    'acc_transaction_description',
    
    // Reports
    'acc_report_data',
    'acc_report_columns',
    'acc_report_filters',
    'acc_dashboard_widgets',
    
    // Queries
    'acc_accounts_query',
    'acc_transactions_query',
    'acc_reports_query',
];
```

### 3.3 Phase 3: Field Types

```php
// Custom field types (10+)
$fieldTypes = [
    'money' => [
        'component' => 'MoneyInput',
        'validation' => 'numeric|min:0',
        'formatting' => 'currency_format',
        'options' => ['currency', 'decimal_places', 'allow_negative']
    ],
    
    'account_picker' => [
        'component' => 'AccountPicker',
        'validation' => 'exists:acc_accounts,id',
        'options' => ['account_types', 'show_balance', 'allow_create']
    ],
    
    'contact_picker' => [
        'component' => 'ContactPicker',
        'validation' => 'exists:acc_contacts,id',
        'options' => ['contact_type', 'show_balance']
    ],
    
    'currency_selector' => [
        'component' => 'CurrencySelector',
        'validation' => 'exists:acc_currencies,id',
        'options' => ['show_rate', 'show_symbol']
    ],
    
    'tax_rate_picker' => [
        'component' => 'TaxRatePicker',
        'validation' => 'exists:acc_tax_rates,id',
        'options' => ['show_rate', 'allow_multiple']
    ],
    
    'fiscal_period' => [
        'component' => 'FiscalPeriodPicker',
        'validation' => 'date_in_fiscal_year',
        'options' => ['fiscal_year_id', 'show_status']
    ],
    
    'journal_line_grid' => [
        'component' => 'JournalLineGrid',
        'validation' => 'balanced_journal',
        'options' => ['show_cost_center', 'show_tax']
    ],
    
    'invoice_line_grid' => [
        'component' => 'InvoiceLineGrid',
        'validation' => 'array|min:1',
        'options' => ['show_tax', 'show_discount', 'show_account']
    ],
    
    'bank_statement_matcher' => [
        'component' => 'BankStatementMatcher',
        'options' => ['auto_match', 'show_suggestions']
    ],
    
    'chart_of_accounts_tree' => [
        'component' => 'AccountTreeView',
        'options' => ['show_balances', 'expandable', 'draggable']
    ],
];
```

### 3.4 Phase 4: REST API

```php
// API Endpoints (50+)
$endpoints = [
    // Chart of Accounts
    'GET    /api/v1/accounting/accounts',
    'POST   /api/v1/accounting/accounts',
    'GET    /api/v1/accounting/accounts/{id}',
    'PUT    /api/v1/accounting/accounts/{id}',
    'DELETE /api/v1/accounting/accounts/{id}',
    'GET    /api/v1/accounting/accounts/{id}/transactions',
    'GET    /api/v1/accounting/accounts/{id}/balance',
    'GET    /api/v1/accounting/accounts/tree',
    
    // Journals
    'GET    /api/v1/accounting/journals',
    'POST   /api/v1/accounting/journals',
    'GET    /api/v1/accounting/journals/{id}',
    'PUT    /api/v1/accounting/journals/{id}',
    'DELETE /api/v1/accounting/journals/{id}',
    'POST   /api/v1/accounting/journals/{id}/post',
    'POST   /api/v1/accounting/journals/{id}/void',
    'POST   /api/v1/accounting/journals/{id}/reverse',
    
    // Invoices
    'GET    /api/v1/accounting/invoices',
    'POST   /api/v1/accounting/invoices',
    'GET    /api/v1/accounting/invoices/{id}',
    'PUT    /api/v1/accounting/invoices/{id}',
    'DELETE /api/v1/accounting/invoices/{id}',
    'POST   /api/v1/accounting/invoices/{id}/send',
    'POST   /api/v1/accounting/invoices/{id}/record-payment',
    'GET    /api/v1/accounting/invoices/{id}/pdf',
    
    // Payments
    'GET    /api/v1/accounting/payments',
    'POST   /api/v1/accounting/payments',
    'GET    /api/v1/accounting/payments/{id}',
    'POST   /api/v1/accounting/payments/{id}/apply',
    
    // Banking
    'GET    /api/v1/accounting/bank-accounts',
    'POST   /api/v1/accounting/bank-accounts',
    'GET    /api/v1/accounting/bank-accounts/{id}/transactions',
    'POST   /api/v1/accounting/bank-accounts/{id}/import',
    'GET    /api/v1/accounting/bank-accounts/{id}/reconciliation',
    'POST   /api/v1/accounting/bank-accounts/{id}/reconcile',
    
    // Reports
    'GET    /api/v1/accounting/reports/balance-sheet',
    'GET    /api/v1/accounting/reports/profit-loss',
    'GET    /api/v1/accounting/reports/cash-flow',
    'GET    /api/v1/accounting/reports/trial-balance',
    'GET    /api/v1/accounting/reports/general-ledger',
    'GET    /api/v1/accounting/reports/aged-receivables',
    'GET    /api/v1/accounting/reports/aged-payables',
    'GET    /api/v1/accounting/reports/tax-summary',
    'POST   /api/v1/accounting/reports/custom',
    
    // Settings
    'GET    /api/v1/accounting/settings',
    'PUT    /api/v1/accounting/settings',
    'GET    /api/v1/accounting/currencies',
    'GET    /api/v1/accounting/tax-rates',
    'GET    /api/v1/accounting/fiscal-years',
    'POST   /api/v1/accounting/fiscal-years/{id}/close',
    
    // Dashboard
    'GET    /api/v1/accounting/dashboard/summary',
    'GET    /api/v1/accounting/dashboard/cash-flow-chart',
    'GET    /api/v1/accounting/dashboard/income-expense-chart',
    'GET    /api/v1/accounting/dashboard/receivables-payables',
];
```

### 3.5 Phase 5: Shortcodes

```php
// Shortcodes (20+)
$shortcodes = [
    // Account widgets
    '[acc_account_balance account="1000" show_name="true"]',
    '[acc_account_transactions account="1000" limit="10"]',
    '[acc_chart_of_accounts type="asset" expandable="true"]',
    
    // Invoice widgets
    '[acc_invoice id="123" format="summary"]',
    '[acc_invoice_list status="unpaid" limit="5"]',
    '[acc_invoice_total customer="456" period="this_month"]',
    '[acc_payment_button invoice="123" methods="stripe,paypal"]',
    
    // Report widgets
    '[acc_balance_sheet date="2025-12-31" format="summary"]',
    '[acc_profit_loss from="2025-01-01" to="2025-12-31"]',
    '[acc_cash_flow period="this_quarter"]',
    '[acc_kpi metric="gross_profit_margin"]',
    
    // Charts
    '[acc_chart type="income_vs_expense" period="12_months"]',
    '[acc_chart type="cash_flow_trend" period="6_months"]',
    '[acc_chart type="top_expenses" limit="5"]',
    '[acc_chart type="receivables_aging"]',
    
    // Customer/Vendor portals
    '[acc_customer_statement customer="123"]',
    '[acc_customer_invoices customer="123"]',
    '[acc_vendor_bills vendor="456"]',
    
    // Forms
    '[acc_payment_form invoice="123"]',
    '[acc_expense_form category="travel"]',
    '[acc_quick_invoice customer="123"]',
];
```

### 3.6 Phase 6: Menu System

```php
// Menu structure
$menus = [
    'accounting_main' => [
        'location' => 'sidebar',
        'items' => [
            [
                'label' => 'Dashboard',
                'route' => 'accounting.dashboard',
                'icon' => 'chart-pie',
                'permission' => 'accounting.dashboard.view',
            ],
            [
                'label' => 'Transactions',
                'icon' => 'document-text',
                'children' => [
                    ['label' => 'Journal Entries', 'route' => 'accounting.journals.index'],
                    ['label' => 'New Journal', 'route' => 'accounting.journals.create'],
                    ['label' => 'Recurring Journals', 'route' => 'accounting.journals.recurring'],
                ],
            ],
            [
                'label' => 'Sales',
                'icon' => 'currency-dollar',
                'badge_callback' => 'AccountingService@getUnpaidInvoiceCount',
                'children' => [
                    ['label' => 'Invoices', 'route' => 'accounting.invoices.index'],
                    ['label' => 'New Invoice', 'route' => 'accounting.invoices.create'],
                    ['label' => 'Customers', 'route' => 'accounting.customers.index'],
                    ['label' => 'Receipts', 'route' => 'accounting.receipts.index'],
                ],
            ],
            [
                'label' => 'Purchases',
                'icon' => 'shopping-cart',
                'badge_callback' => 'AccountingService@getUnpaidBillCount',
                'children' => [
                    ['label' => 'Bills', 'route' => 'accounting.bills.index'],
                    ['label' => 'New Bill', 'route' => 'accounting.bills.create'],
                    ['label' => 'Vendors', 'route' => 'accounting.vendors.index'],
                    ['label' => 'Payments', 'route' => 'accounting.payments.index'],
                ],
            ],
            [
                'label' => 'Banking',
                'icon' => 'building-library',
                'children' => [
                    ['label' => 'Accounts', 'route' => 'accounting.banking.index'],
                    ['label' => 'Transactions', 'route' => 'accounting.banking.transactions'],
                    ['label' => 'Reconciliation', 'route' => 'accounting.banking.reconcile'],
                    ['label' => 'Import Statement', 'route' => 'accounting.banking.import'],
                ],
            ],
            [
                'label' => 'Reports',
                'icon' => 'document-chart-bar',
                'permission' => 'accounting.reports.view',
                'children' => [
                    ['type' => 'header', 'label' => 'Financial Statements'],
                    ['label' => 'Balance Sheet', 'route' => 'accounting.reports.balance-sheet'],
                    ['label' => 'Profit & Loss', 'route' => 'accounting.reports.profit-loss'],
                    ['label' => 'Cash Flow', 'route' => 'accounting.reports.cash-flow'],
                    ['type' => 'divider'],
                    ['type' => 'header', 'label' => 'Detail Reports'],
                    ['label' => 'Trial Balance', 'route' => 'accounting.reports.trial-balance'],
                    ['label' => 'General Ledger', 'route' => 'accounting.reports.general-ledger'],
                    ['label' => 'Aged Receivables', 'route' => 'accounting.reports.aged-receivables'],
                    ['label' => 'Aged Payables', 'route' => 'accounting.reports.aged-payables'],
                    ['type' => 'divider'],
                    ['label' => 'Custom Reports', 'route' => 'accounting.reports.custom'],
                ],
            ],
            [
                'label' => 'Chart of Accounts',
                'route' => 'accounting.accounts.index',
                'icon' => 'folder-tree',
            ],
            [
                'label' => 'Settings',
                'icon' => 'cog',
                'permission' => 'accounting.settings.manage',
                'children' => [
                    ['label' => 'General', 'route' => 'accounting.settings.general'],
                    ['label' => 'Currencies', 'route' => 'accounting.settings.currencies'],
                    ['label' => 'Tax Rates', 'route' => 'accounting.settings.tax-rates'],
                    ['label' => 'Fiscal Years', 'route' => 'accounting.settings.fiscal-years'],
                    ['label' => 'Number Sequences', 'route' => 'accounting.settings.sequences'],
                    ['label' => 'Cost Centers', 'route' => 'accounting.settings.cost-centers'],
                    ['label' => 'Payment Terms', 'route' => 'accounting.settings.payment-terms'],
                ],
            ],
        ],
    ],
    
    'accounting_quick_actions' => [
        'location' => 'topbar',
        'items' => [
            ['label' => 'New Invoice', 'route' => 'accounting.invoices.create', 'icon' => 'plus'],
            ['label' => 'New Bill', 'route' => 'accounting.bills.create', 'icon' => 'plus'],
            ['label' => 'New Payment', 'route' => 'accounting.payments.create', 'icon' => 'plus'],
            ['label' => 'New Journal', 'route' => 'accounting.journals.create', 'icon' => 'plus'],
        ],
    ],
];
```

### 3.7 Phase 7: Permissions System

```php
// Roles (6)
$roles = [
    'accounting_admin' => [
        'name' => 'Accounting Administrator',
        'level' => 800,
        'permissions' => ['accounting.*'],
    ],
    'accountant' => [
        'name' => 'Accountant',
        'level' => 600,
        'permissions' => [
            'accounting.dashboard.view',
            'accounting.journals.*',
            'accounting.invoices.*',
            'accounting.bills.*',
            'accounting.payments.*',
            'accounting.banking.*',
            'accounting.reports.*',
            'accounting.accounts.view',
            'accounting.accounts.create',
            'accounting.accounts.update',
            'accounting.contacts.*',
        ],
    ],
    'bookkeeper' => [
        'name' => 'Bookkeeper',
        'level' => 400,
        'permissions' => [
            'accounting.dashboard.view',
            'accounting.journals.view',
            'accounting.journals.create',
            'accounting.invoices.*',
            'accounting.bills.*',
            'accounting.payments.*',
            'accounting.banking.view',
            'accounting.banking.import',
            'accounting.reports.view',
            'accounting.contacts.*',
        ],
    ],
    'billing_clerk' => [
        'name' => 'Billing Clerk',
        'level' => 300,
        'permissions' => [
            'accounting.invoices.view',
            'accounting.invoices.create',
            'accounting.invoices.send',
            'accounting.receipts.*',
            'accounting.customers.*',
            'accounting.reports.aged-receivables',
        ],
    ],
    'ap_clerk' => [
        'name' => 'AP Clerk',
        'level' => 300,
        'permissions' => [
            'accounting.bills.view',
            'accounting.bills.create',
            'accounting.payments.view',
            'accounting.payments.create',
            'accounting.vendors.*',
            'accounting.reports.aged-payables',
        ],
    ],
    'auditor' => [
        'name' => 'Auditor',
        'level' => 500,
        'permissions' => [
            'accounting.*.view',
            'accounting.reports.*',
            'accounting.audit-trail.view',
        ],
    ],
];

// Permissions (50+)
$permissions = [
    // Dashboard
    'accounting.dashboard.view',
    
    // Accounts
    'accounting.accounts.view',
    'accounting.accounts.create',
    'accounting.accounts.update',
    'accounting.accounts.delete',
    
    // Journals
    'accounting.journals.view',
    'accounting.journals.create',
    'accounting.journals.update',
    'accounting.journals.delete',
    'accounting.journals.post',
    'accounting.journals.void',
    'accounting.journals.reverse',
    
    // Invoices
    'accounting.invoices.view',
    'accounting.invoices.create',
    'accounting.invoices.update',
    'accounting.invoices.delete',
    'accounting.invoices.send',
    'accounting.invoices.record-payment',
    
    // Bills
    'accounting.bills.view',
    'accounting.bills.create',
    'accounting.bills.update',
    'accounting.bills.delete',
    'accounting.bills.approve',
    
    // Payments
    'accounting.payments.view',
    'accounting.payments.create',
    'accounting.payments.void',
    
    // Banking
    'accounting.banking.view',
    'accounting.banking.create',
    'accounting.banking.import',
    'accounting.banking.reconcile',
    
    // Reports
    'accounting.reports.view',
    'accounting.reports.financial-statements',
    'accounting.reports.export',
    'accounting.reports.custom',
    
    // Settings
    'accounting.settings.view',
    'accounting.settings.manage',
    'accounting.settings.currencies',
    'accounting.settings.fiscal-years',
    'accounting.settings.close-period',
    
    // Audit
    'accounting.audit-trail.view',
    
    // Premium (Phase 9)
    'accounting.premium.budgeting',
    'accounting.premium.multi-currency',
    'accounting.premium.consolidation',
    'accounting.premium.advanced-reports',
];
```

### 3.8 Phase 8: Event & Scheduler System

```php
// Scheduled Tasks (15+)
$scheduledTasks = [
    [
        'slug' => 'accounting.update-exchange-rates',
        'name' => 'Update Exchange Rates',
        'handler' => 'AccountingServices\Currency@updateRates',
        'expression' => '0 6 * * *', // Daily at 6 AM
    ],
    [
        'slug' => 'accounting.send-invoice-reminders',
        'name' => 'Send Invoice Reminders',
        'handler' => 'AccountingServices\Invoice@sendReminders',
        'expression' => '0 9 * * *', // Daily at 9 AM
    ],
    [
        'slug' => 'accounting.check-overdue-invoices',
        'name' => 'Check Overdue Invoices',
        'handler' => 'AccountingServices\Invoice@markOverdue',
        'expression' => '0 0 * * *', // Daily at midnight
    ],
    [
        'slug' => 'accounting.recurring-invoices',
        'name' => 'Generate Recurring Invoices',
        'handler' => 'AccountingServices\Invoice@processRecurring',
        'expression' => '0 1 * * *', // Daily at 1 AM
    ],
    [
        'slug' => 'accounting.recurring-journals',
        'name' => 'Post Recurring Journals',
        'handler' => 'AccountingServices\Journal@processRecurring',
        'expression' => '0 2 * * *', // Daily at 2 AM
    ],
    [
        'slug' => 'accounting.bank-sync',
        'name' => 'Sync Bank Transactions',
        'handler' => 'AccountingServices\Banking@syncAll',
        'expression' => '0 */4 * * *', // Every 4 hours
    ],
    [
        'slug' => 'accounting.backup-data',
        'name' => 'Backup Accounting Data',
        'handler' => 'AccountingServices\Backup@run',
        'expression' => '0 3 * * *', // Daily at 3 AM
    ],
    [
        'slug' => 'accounting.calculate-balances',
        'name' => 'Recalculate Account Balances',
        'handler' => 'AccountingServices\Ledger@recalculateBalances',
        'expression' => '30 3 * * *', // Daily at 3:30 AM
    ],
    [
        'slug' => 'accounting.period-close-reminder',
        'name' => 'Period Close Reminder',
        'handler' => 'AccountingServices\Period@sendCloseReminder',
        'expression' => '0 9 1 * *', // 1st of each month
    ],
    [
        'slug' => 'accounting.generate-monthly-reports',
        'name' => 'Generate Monthly Reports',
        'handler' => 'AccountingServices\Reports@generateMonthly',
        'expression' => '0 4 1 * *', // 1st of each month at 4 AM
    ],
];

// Event Subscriptions (25+)
$events = [
    // Journal events
    [
        'event' => 'acc_journal_posted',
        'handler' => 'AccountingListeners\UpdateAccountBalances',
    ],
    [
        'event' => 'acc_journal_voided',
        'handler' => 'AccountingListeners\ReverseAccountBalances',
    ],
    
    // Invoice events
    [
        'event' => 'acc_invoice_created',
        'handler' => 'AccountingListeners\UpdateReceivables',
    ],
    [
        'event' => 'acc_invoice_paid',
        'handler' => 'AccountingListeners\CreateReceiptJournal',
    ],
    [
        'event' => 'acc_invoice_overdue',
        'handler' => 'AccountingListeners\SendOverdueNotification',
        'async' => true,
    ],
    
    // Payment events
    [
        'event' => 'acc_payment_created',
        'handler' => 'AccountingListeners\ApplyPaymentToInvoices',
    ],
    [
        'event' => 'acc_payment_created',
        'handler' => 'AccountingListeners\UpdateBankBalance',
    ],
    
    // Banking events
    [
        'event' => 'acc_bank_transaction_imported',
        'handler' => 'AccountingListeners\AutoMatchTransactions',
        'async' => true,
    ],
    [
        'event' => 'acc_reconciliation_completed',
        'handler' => 'AccountingListeners\CreateReconciliationJournal',
    ],
    
    // Period events
    [
        'event' => 'acc_period_closed',
        'handler' => 'AccountingListeners\LockPeriodTransactions',
    ],
    [
        'event' => 'acc_year_closed',
        'handler' => 'AccountingListeners\CreateClosingEntries',
    ],
    
    // Notification events
    [
        'event' => 'acc_invoice_sent',
        'handler' => 'AccountingListeners\LogInvoiceSent',
    ],
    [
        'event' => 'acc_large_transaction',
        'handler' => 'AccountingListeners\NotifyManagement',
        'conditions' => [
            ['field' => 'amount', 'operator' => '>=', 'value' => 10000],
        ],
    ],
];
```

### 3.9 Phase 9: Marketplace Integration

```php
// Plugin manifest
$manifest = [
    'slug' => 'accounting',
    'name' => 'Enterprise Accounting',
    'version' => '1.0.0',
    'description' => 'Complete double-entry accounting system',
    'author' => 'Plugin System',
    'homepage' => 'https://example.com/accounting',
    'is_premium' => true,
    'pricing' => [
        'standard' => 99,
        'extended' => 299,
        'lifetime' => 499,
    ],
];

// Feature flags (license-based)
$features = [
    'core' => [
        'chart_of_accounts',
        'journals',
        'basic_invoicing',
        'basic_reports',
    ],
    'standard' => [
        'banking',
        'reconciliation',
        'full_invoicing',
        'full_reports',
        'contacts',
    ],
    'extended' => [
        'multi_currency',
        'budgeting',
        'cost_centers',
        'advanced_reports',
        'api_access',
        'custom_fields',
    ],
    'lifetime' => [
        'consolidation',
        'multi_company',
        'audit_trail_export',
        'white_label',
        'priority_support',
    ],
];

// Update hooks
$updateHooks = [
    '1.0.0' => 'migrateToV1',
    '1.1.0' => 'addBudgetingModule',
    '1.2.0' => 'addMultiCurrencySupport',
    '2.0.0' => 'migrateToV2Schema',
];
```

---

## 4. Module Breakdown

### 4.1 Core Modules

```
Module                 | Entities | APIs | Shortcodes | Priority
-----------------------|----------|------|------------|----------
Chart of Accounts      | 1        | 8    | 3          | P0
General Ledger         | 2        | 10   | 2          | P0
Contacts (AR/AP)       | 1        | 6    | 2          | P0
Invoicing (Sales)      | 2        | 12   | 5          | P0
Bills (Purchases)      | 2        | 10   | 3          | P0
Payments               | 1        | 6    | 2          | P0
Banking                | 3        | 10   | 3          | P1
Reports                | 0        | 15   | 8          | P1
Settings               | 5        | 8    | 0          | P0
```

### 4.2 Premium Modules

```
Module                 | License    | Entities | APIs | Priority
-----------------------|------------|----------|------|----------
Multi-Currency         | Standard+  | 1        | 4    | P1
Budgeting              | Extended   | 2        | 6    | P2
Cost Centers           | Extended   | 1        | 4    | P2
Advanced Reports       | Extended   | 0        | 10   | P2
Multi-Company          | Lifetime   | 2        | 8    | P3
Consolidation          | Lifetime   | 1        | 4    | P3
```

---

## 5. Database Schema

### 5.1 Core Tables

```
acc_accounts           - Chart of accounts (hierarchical)
acc_account_types      - Asset, Liability, Equity, Revenue, Expense
acc_journals           - Journal entry headers
acc_journal_lines      - Journal entry lines (debit/credit)
acc_contacts           - Customers and vendors
acc_invoices           - Sales invoices
acc_invoice_lines      - Invoice line items
acc_bills              - Purchase bills
acc_bill_lines         - Bill line items
acc_payments           - Payments (incoming/outgoing)
acc_payment_allocations - Payment to invoice mapping
```

### 5.2 Banking Tables

```
acc_bank_accounts      - Bank account definitions
acc_bank_transactions  - Imported bank transactions
acc_reconciliations    - Reconciliation sessions
acc_reconciliation_lines - Matched transactions
```

### 5.3 Settings Tables

```
acc_currencies         - Currency definitions
acc_exchange_rates     - Historical exchange rates
acc_tax_rates          - Tax rate definitions
acc_fiscal_years       - Fiscal year periods
acc_fiscal_periods     - Monthly/quarterly periods
acc_sequences          - Number sequences (INV-0001)
acc_payment_terms      - Net 30, Due on Receipt, etc.
```

### 5.4 Premium Tables

```
acc_cost_centers       - Cost center hierarchy
acc_budgets            - Budget definitions
acc_budget_lines       - Budget amounts by period
acc_companies          - Multi-company support
acc_consolidations     - Consolidation definitions
acc_intercompany       - Intercompany transactions
```

### 5.5 Entity Relationship Diagram

```
                    ┌─────────────────┐
                    │   acc_accounts  │
                    │─────────────────│
                    │ id              │
                    │ parent_id (FK)  │◄─────────┐
                    │ code            │          │
                    │ name            │          │
                    │ type_id (FK)────┼──►acc_account_types
                    │ currency_id(FK)─┼──►acc_currencies
                    │ opening_balance │          │
                    │ current_balance │          │
                    └────────┬────────┘          │
                             │                   │
         ┌───────────────────┼───────────────────┘
         │                   │
         ▼                   ▼
┌─────────────────┐  ┌─────────────────┐
│  acc_journals   │  │acc_journal_lines│
│─────────────────│  │─────────────────│
│ id              │◄─┤ journal_id (FK) │
│ number          │  │ account_id (FK)─┼──►acc_accounts
│ date            │  │ debit           │
│ reference       │  │ credit          │
│ status          │  │ description     │
│ posted_at       │  │ cost_center_id  │
└────────┬────────┘  └─────────────────┘
         │
         │ Creates journal when posted
         ▼
┌─────────────────┐     ┌─────────────────┐
│  acc_invoices   │     │    acc_bills    │
│─────────────────│     │─────────────────│
│ id              │     │ id              │
│ number          │     │ number          │
│ contact_id (FK)─┼──┐  │ contact_id(FK)──┼──┐
│ journal_id (FK)─┼──┤  │ journal_id(FK)──┼──┤
│ date            │  │  │ date            │  │
│ due_date        │  │  │ due_date        │  │
│ status          │  │  │ status          │  │
│ total           │  │  │ total           │  │
└────────┬────────┘  │  └────────┬────────┘  │
         │           │           │           │
         ▼           │           ▼           │
┌─────────────────┐  │  ┌─────────────────┐  │
│acc_invoice_lines│  │  │ acc_bill_lines  │  │
│─────────────────│  │  │─────────────────│  │
│ invoice_id (FK) │  │  │ bill_id (FK)    │  │
│ account_id (FK) │  │  │ account_id (FK) │  │
│ description     │  │  │ description     │  │
│ quantity        │  │  │ quantity        │  │
│ unit_price      │  │  │ unit_price      │  │
│ tax_rate_id(FK) │  │  │ tax_rate_id(FK) │  │
└─────────────────┘  │  └─────────────────┘  │
                     │                       │
                     ▼                       ▼
              ┌─────────────────┐     ┌─────────────────┐
              │  acc_contacts   │     │  acc_journals   │
              │─────────────────│     │(linked above)   │
              │ id              │     └─────────────────┘
              │ type (C/V)      │
              │ name            │
              │ balance         │
              └────────┬────────┘
                       │
                       ▼
              ┌─────────────────┐
              │  acc_payments   │
              │─────────────────│
              │ id              │
              │ type (IN/OUT)   │
              │ contact_id (FK) │
              │ bank_account_id │
              │ journal_id (FK) │
              │ amount          │
              └────────┬────────┘
                       │
                       ▼
              ┌─────────────────────┐
              │acc_payment_allocations│
              │─────────────────────│
              │ payment_id (FK)     │
              │ invoice_id (FK)     │
              │ amount              │
              └─────────────────────┘
```

---

## 6. Implementation Phases

### Phase A: Foundation (Week 1-2)

```
Tasks:
├── Plugin skeleton setup
├── Base models & migrations
├── Service layer architecture
├── Configuration system
├── Phase 1: Dynamic entities registration
└── Phase 9: Marketplace integration

Deliverables:
├── Working plugin installation
├── Basic settings management
├── License validation
└── Update mechanism
```

### Phase B: Core Accounting (Week 3-4)

```
Tasks:
├── Chart of Accounts
│   ├── Account model & CRUD
│   ├── Account types & hierarchy
│   └── Opening balances
├── General Ledger
│   ├── Journal model & CRUD
│   ├── Double-entry validation
│   ├── Posting mechanism
│   └── Reversals & voids
├── Phase 2: Hook integration
├── Phase 4: REST API
└── Phase 7: Permissions

Deliverables:
├── Full Chart of Accounts
├── Journal entry system
├── Posting & validation
└── API endpoints
```

### Phase C: Sales & Purchases (Week 5-6)

```
Tasks:
├── Contacts (Customers/Vendors)
├── Invoicing
│   ├── Invoice CRUD
│   ├── Line items
│   ├── Tax calculations
│   ├── PDF generation
│   └── Email sending
├── Bills (mirror of invoicing)
├── Payments
│   ├── Receipt recording
│   ├── Payment recording
│   └── Allocation to invoices
├── Phase 3: Custom field types
├── Phase 5: Shortcodes
└── Phase 6: Menu system

Deliverables:
├── Complete AR module
├── Complete AP module
├── Payment processing
└── Document generation
```

### Phase D: Banking & Reports (Week 7-8)

```
Tasks:
├── Banking
│   ├── Bank account setup
│   ├── Transaction import (CSV/OFX)
│   ├── Auto-matching
│   └── Reconciliation
├── Reports
│   ├── Balance Sheet
│   ├── Profit & Loss
│   ├── Trial Balance
│   ├── General Ledger
│   ├── Aged Reports
│   └── Export (PDF/Excel)
├── Phase 8: Scheduled tasks
└── Dashboard widgets

Deliverables:
├── Bank reconciliation
├── All financial reports
├── Automated tasks
└── Dashboard
```

### Phase E: Premium Features (Week 9-10)

```
Tasks:
├── Multi-Currency
│   ├── Currency management
│   ├── Exchange rates
│   └── Conversion handling
├── Budgeting
│   ├── Budget creation
│   ├── Period allocation
│   └── Variance reports
├── Cost Centers
├── Advanced Reports
│   ├── Custom report builder
│   ├── Comparative reports
│   └── Charts & visualizations
└── License-gated features

Deliverables:
├── Multi-currency support
├── Budget management
├── Cost center tracking
└── Advanced reporting
```

### Phase F: Polish & Testing (Week 11-12)

```
Tasks:
├── Unit tests (100% coverage goal)
├── Integration tests
├── Performance optimization
├── Documentation
├── Sample data & demo
└── Final QA

Deliverables:
├── Fully tested plugin
├── Complete documentation
├── Demo environment
└── Release package
```

---

## 7. File Structure

```
plugins/accounting/
├── plugin.json                      # Manifest
├── composer.json                    # Dependencies
├── README.md                        # Documentation
│
├── src/
│   ├── AccountingPlugin.php         # Main plugin class
│   │
│   ├── Config/
│   │   ├── accounting.php           # Main config
│   │   ├── permissions.php          # Permission definitions
│   │   ├── menus.php                # Menu definitions
│   │   └── entities.php             # Entity definitions
│   │
│   ├── Models/
│   │   ├── Account.php
│   │   ├── AccountType.php
│   │   ├── Journal.php
│   │   ├── JournalLine.php
│   │   ├── Contact.php
│   │   ├── Invoice.php
│   │   ├── InvoiceLine.php
│   │   ├── Bill.php
│   │   ├── BillLine.php
│   │   ├── Payment.php
│   │   ├── PaymentAllocation.php
│   │   ├── BankAccount.php
│   │   ├── BankTransaction.php
│   │   ├── Reconciliation.php
│   │   ├── Currency.php
│   │   ├── ExchangeRate.php
│   │   ├── TaxRate.php
│   │   ├── FiscalYear.php
│   │   ├── FiscalPeriod.php
│   │   ├── CostCenter.php
│   │   ├── Budget.php
│   │   └── BudgetLine.php
│   │
│   ├── Services/
│   │   ├── LedgerService.php        # Core ledger operations
│   │   ├── JournalService.php       # Journal management
│   │   ├── InvoiceService.php       # Invoice operations
│   │   ├── BillService.php          # Bill operations
│   │   ├── PaymentService.php       # Payment processing
│   │   ├── BankingService.php       # Banking & reconciliation
│   │   ├── ReportService.php        # Report generation
│   │   ├── CurrencyService.php      # Currency & exchange
│   │   ├── TaxService.php           # Tax calculations
│   │   ├── PeriodService.php        # Fiscal period management
│   │   ├── BudgetService.php        # Budgeting
│   │   └── ExportService.php        # PDF/Excel export
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AccountController.php
│   │   │   ├── JournalController.php
│   │   │   ├── InvoiceController.php
│   │   │   ├── BillController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── BankingController.php
│   │   │   ├── ReportController.php
│   │   │   ├── SettingsController.php
│   │   │   └── DashboardController.php
│   │   │
│   │   ├── Requests/
│   │   │   ├── StoreJournalRequest.php
│   │   │   ├── StoreInvoiceRequest.php
│   │   │   └── ...
│   │   │
│   │   └── Resources/
│   │       ├── AccountResource.php
│   │       ├── JournalResource.php
│   │       └── ...
│   │
│   ├── FieldTypes/
│   │   ├── MoneyFieldType.php
│   │   ├── AccountPickerFieldType.php
│   │   ├── ContactPickerFieldType.php
│   │   ├── CurrencyFieldType.php
│   │   ├── TaxRateFieldType.php
│   │   ├── JournalLineGridFieldType.php
│   │   └── InvoiceLineGridFieldType.php
│   │
│   ├── Shortcodes/
│   │   ├── AccountBalanceShortcode.php
│   │   ├── InvoiceShortcode.php
│   │   ├── ReportShortcode.php
│   │   ├── ChartShortcode.php
│   │   └── PaymentFormShortcode.php
│   │
│   ├── Events/
│   │   ├── JournalPosted.php
│   │   ├── InvoiceCreated.php
│   │   ├── InvoicePaid.php
│   │   ├── PaymentReceived.php
│   │   └── PeriodClosed.php
│   │
│   ├── Listeners/
│   │   ├── UpdateAccountBalances.php
│   │   ├── CreateReceiptJournal.php
│   │   ├── SendInvoiceReminder.php
│   │   └── LockPeriodTransactions.php
│   │
│   ├── Jobs/
│   │   ├── ProcessRecurringInvoices.php
│   │   ├── SyncBankTransactions.php
│   │   ├── GenerateMonthlyReports.php
│   │   └── UpdateExchangeRates.php
│   │
│   ├── Reports/
│   │   ├── BalanceSheet.php
│   │   ├── ProfitLoss.php
│   │   ├── CashFlow.php
│   │   ├── TrialBalance.php
│   │   ├── GeneralLedger.php
│   │   ├── AgedReceivables.php
│   │   ├── AgedPayables.php
│   │   └── BudgetVariance.php
│   │
│   ├── Exports/
│   │   ├── PdfExporter.php
│   │   ├── ExcelExporter.php
│   │   └── CsvExporter.php
│   │
│   ├── Imports/
│   │   ├── BankStatementImporter.php
│   │   ├── ChartOfAccountsImporter.php
│   │   └── OpeningBalanceImporter.php
│   │
│   └── Console/
│       ├── RecalculateBalances.php
│       ├── CloseperiodCommand.php
│       └── SeedDemoData.php
│
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_acc_account_types.php
│   │   ├── 2025_01_01_000002_create_acc_currencies.php
│   │   ├── 2025_01_01_000003_create_acc_accounts.php
│   │   ├── 2025_01_01_000004_create_acc_fiscal_years.php
│   │   ├── 2025_01_01_000005_create_acc_journals.php
│   │   ├── 2025_01_01_000006_create_acc_contacts.php
│   │   ├── 2025_01_01_000007_create_acc_invoices.php
│   │   ├── 2025_01_01_000008_create_acc_bills.php
│   │   ├── 2025_01_01_000009_create_acc_payments.php
│   │   ├── 2025_01_01_000010_create_acc_bank_accounts.php
│   │   └── ...
│   │
│   └── seeders/
│       ├── AccountTypeSeeder.php
│       ├── DefaultChartOfAccounts.php
│       └── DemoDataSeeder.php
│
├── routes/
│   ├── api.php                      # API routes
│   └── web.php                      # Web routes
│
├── resources/
│   ├── views/
│   │   ├── dashboard/
│   │   ├── accounts/
│   │   ├── journals/
│   │   ├── invoices/
│   │   ├── bills/
│   │   ├── payments/
│   │   ├── banking/
│   │   ├── reports/
│   │   ├── settings/
│   │   ├── pdf/
│   │   │   ├── invoice.blade.php
│   │   │   └── report.blade.php
│   │   └── components/
│   │
│   ├── js/
│   │   ├── components/
│   │   │   ├── AccountPicker.vue
│   │   │   ├── JournalLineGrid.vue
│   │   │   ├── InvoiceLineGrid.vue
│   │   │   └── BankMatcher.vue
│   │   └── pages/
│   │
│   └── lang/
│       ├── en/
│       └── ar/
│
├── tests/
│   ├── Unit/
│   │   ├── LedgerServiceTest.php
│   │   ├── JournalServiceTest.php
│   │   ├── InvoiceServiceTest.php
│   │   └── ...
│   │
│   ├── Feature/
│   │   ├── JournalApiTest.php
│   │   ├── InvoiceApiTest.php
│   │   └── ...
│   │
│   └── Integration/
│       ├── FullInvoiceFlowTest.php
│       └── ReconciliationFlowTest.php
│
└── docs/
    ├── installation.md
    ├── configuration.md
    ├── api-reference.md
    ├── user-guide.md
    └── developer-guide.md
```

---

## 8. Detailed Feature Specifications

### 8.1 Chart of Accounts

```yaml
Features:
  - Hierarchical structure (unlimited depth)
  - Standard account types (Asset, Liability, Equity, Revenue, Expense)
  - Sub-types (Current Asset, Fixed Asset, etc.)
  - Account codes (flexible format: 1000, 1.1.001, etc.)
  - Opening balances
  - Multi-currency accounts
  - Active/inactive status
  - System vs user accounts
  - Quick account search
  - Account merge tool

Account Types:
  Asset:
    - Current Assets (1000-1499)
    - Fixed Assets (1500-1799)
    - Other Assets (1800-1999)
  Liability:
    - Current Liabilities (2000-2499)
    - Long-term Liabilities (2500-2999)
  Equity:
    - Owner's Equity (3000-3499)
    - Retained Earnings (3500-3999)
  Revenue:
    - Operating Revenue (4000-4499)
    - Other Revenue (4500-4999)
  Expense:
    - Cost of Goods Sold (5000-5499)
    - Operating Expenses (5500-5999)
    - Other Expenses (6000-6999)

API Endpoints:
  GET    /accounts              - List accounts (tree/flat)
  POST   /accounts              - Create account
  GET    /accounts/{id}         - Get account
  PUT    /accounts/{id}         - Update account
  DELETE /accounts/{id}         - Delete account
  GET    /accounts/{id}/balance - Get balance
  GET    /accounts/{id}/transactions - Get transactions
  GET    /accounts/tree         - Get full tree
  POST   /accounts/import       - Import from CSV
```

### 8.2 Journal Entries

```yaml
Features:
  - Double-entry validation (debits = credits)
  - Draft and posted states
  - Void and reverse capabilities
  - Recurring journals
  - Multi-line entries
  - Attachments
  - Cross-currency entries
  - Auto-numbering
  - Reference linking (invoice, bill, etc.)
  - Audit trail
  - Batch posting
  - Template journals

Statuses:
  - draft: Not yet posted
  - posted: Posted to ledger
  - voided: Cancelled, no effect
  - reversed: Reversed by another journal

Validation Rules:
  - Total debits must equal total credits
  - Date must be in open fiscal period
  - Account must be active
  - Amount must be positive
  - At least 2 lines required

API Endpoints:
  GET    /journals              - List journals
  POST   /journals              - Create journal
  GET    /journals/{id}         - Get journal
  PUT    /journals/{id}         - Update (if draft)
  DELETE /journals/{id}         - Delete (if draft)
  POST   /journals/{id}/post    - Post to ledger
  POST   /journals/{id}/void    - Void journal
  POST   /journals/{id}/reverse - Create reversing entry
  GET    /journals/recurring    - List recurring
  POST   /journals/batch-post   - Post multiple
```

### 8.3 Invoicing (Sales)

```yaml
Features:
  - Professional invoice templates
  - Line items with quantity, rate, tax
  - Multiple tax rates
  - Discounts (line and total)
  - Auto-calculation
  - PDF generation
  - Email delivery
  - Payment tracking
  - Overdue management
  - Recurring invoices
  - Credit notes
  - Multi-currency
  - Custom fields
  - Online payment links

Statuses:
  - draft: Not sent
  - sent: Sent to customer
  - viewed: Opened by customer
  - partial: Partially paid
  - paid: Fully paid
  - overdue: Past due date
  - void: Cancelled

Workflow:
  1. Create invoice (draft)
  2. Add line items
  3. Calculate totals
  4. Send to customer
  5. Track payments
  6. Mark paid/overdue

API Endpoints:
  GET    /invoices                     - List invoices
  POST   /invoices                     - Create invoice
  GET    /invoices/{id}                - Get invoice
  PUT    /invoices/{id}                - Update invoice
  DELETE /invoices/{id}                - Delete (if draft)
  POST   /invoices/{id}/send           - Send email
  POST   /invoices/{id}/record-payment - Record payment
  GET    /invoices/{id}/pdf            - Get PDF
  POST   /invoices/{id}/duplicate      - Duplicate
  POST   /invoices/{id}/credit-note    - Create credit note
```

### 8.4 Banking

```yaml
Features:
  - Multiple bank accounts
  - Transaction import (CSV, OFX, QIF)
  - Auto-categorization rules
  - Smart matching suggestions
  - Manual matching
  - Split transactions
  - Reconciliation workflow
  - Unreconciled item tracking
  - Bank feeds integration (premium)

Import Formats:
  - CSV (customizable mapping)
  - OFX (Open Financial Exchange)
  - QIF (Quicken Interchange Format)

Matching Rules:
  - Exact amount match
  - Reference/description match
  - Payee match
  - Date range tolerance
  - Fuzzy matching

Reconciliation Process:
  1. Import bank statement
  2. Match transactions
  3. Review unmatched items
  4. Create missing entries
  5. Verify balance
  6. Complete reconciliation

API Endpoints:
  GET    /bank-accounts                         - List accounts
  POST   /bank-accounts                         - Create account
  GET    /bank-accounts/{id}/transactions       - Get transactions
  POST   /bank-accounts/{id}/import             - Import statement
  GET    /bank-accounts/{id}/reconciliation     - Get reconciliation
  POST   /bank-accounts/{id}/reconcile          - Complete reconciliation
  POST   /bank-accounts/{id}/match              - Match transactions
```

### 8.5 Reports

```yaml
Financial Statements:
  Balance Sheet:
    - Assets, Liabilities, Equity
    - Comparative (prior period)
    - Drill-down to accounts
    
  Profit & Loss:
    - Revenue, Expenses, Net Income
    - By period (monthly/quarterly)
    - By cost center
    - Comparative analysis
    
  Cash Flow:
    - Operating activities
    - Investing activities
    - Financing activities
    - Direct/indirect method

Detail Reports:
  Trial Balance:
    - All account balances
    - Adjusting entries
    - Post-closing
    
  General Ledger:
    - Account transactions
    - Running balance
    - Date range
    
  Aged Receivables:
    - By customer
    - By aging bucket (30/60/90/120)
    - Summary/detail
    
  Aged Payables:
    - By vendor
    - By aging bucket
    - Payment scheduling

Budget Reports:
  - Budget vs Actual
  - Variance analysis
  - Forecasting

Custom Reports:
  - Report builder
  - Save templates
  - Schedule generation
  - Export formats

Export Formats:
  - PDF
  - Excel
  - CSV
  - JSON (API)
```

---

## 9. Testing Strategy

### 9.1 Test Coverage Goals

```
Component          | Unit | Integration | E2E | Total
-------------------|------|-------------|-----|-------
Models             | 100% | -           | -   | 100%
Services           | 100% | 80%         | -   | 90%
Controllers        | 80%  | 100%        | -   | 90%
API Endpoints      | -    | 100%        | 80% | 90%
Reports            | 90%  | 100%        | -   | 95%
Import/Export      | 80%  | 100%        | -   | 90%
Shortcodes         | 100% | -           | -   | 100%
Field Types        | 100% | -           | -   | 100%
-------------------|------|-------------|-----|-------
Overall            | 90%  | 90%         | 80% | 90%
```

### 9.2 Critical Test Scenarios

```
Accounting Integrity:
  ✓ Double-entry balance validation
  ✓ Journal posting accuracy
  ✓ Account balance calculations
  ✓ Period close integrity
  ✓ Multi-currency conversions

Business Workflows:
  ✓ Invoice → Payment → Reconciliation flow
  ✓ Bill → Payment → Bank match flow
  ✓ Month-end close process
  ✓ Year-end close process
  ✓ Opening balance setup

Edge Cases:
  ✓ Rounding differences
  ✓ Negative balances
  ✓ Zero-amount transactions
  ✓ Same-day reversals
  ✓ Cross-period adjustments

Performance:
  ✓ Large chart of accounts (1000+ accounts)
  ✓ High transaction volume (100k+ journals)
  ✓ Report generation speed
  ✓ API response times
```

---

## 10. Timeline & Milestones

### Gantt Chart Overview

```
Week 1-2:   [████████] Foundation & Plugin Setup
Week 3-4:   [████████] Core Accounting (COA, Ledger)
Week 5-6:   [████████] Sales & Purchases
Week 7-8:   [████████] Banking & Reports
Week 9-10:  [████████] Premium Features
Week 11-12: [████████] Testing & Documentation
```

### Milestone Checklist

```
□ M1 (Week 2):  Plugin installs, activates, license validates
□ M2 (Week 4):  Chart of Accounts + Journals working
□ M3 (Week 6):  Full invoicing + payments working
□ M4 (Week 8):  Banking + Reports complete
□ M5 (Week 10): Premium features complete
□ M6 (Week 12): Release candidate ready
```

### Definition of Done

```
Each feature must have:
  □ Working implementation
  □ Unit tests (90%+ coverage)
  □ API tests
  □ Documentation
  □ Hook integration
  □ Permission checks
  □ Audit logging
```

---

## Summary

This Accounting Plugin will serve as the **gold standard** for plugin development, demonstrating:

1. **All 9 phases** integrated naturally
2. **Enterprise-grade** accounting features
3. **Best practices** for code organization
4. **Comprehensive testing** strategy
5. **Complete documentation**

The plugin showcases how to build production-ready plugins that leverage every capability of the plugin system.

---

**Next Steps:**
1. Review and approve this plan
2. Set up development environment
3. Begin Phase A: Foundation
