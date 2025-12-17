# Plugin Plan Template Standard v1.0

> This template ensures consistency across all plugin plans (Sales, Purchase, POS, HR, Inventory, etc.)
> Based on Odoo-like ERP architecture principles.

---

## Document Structure (Required Sections)

Every plugin plan MUST include these 15 sections:

```
1.  Overview & Dependencies
2.  Entity Definitions (Full Schema)
3.  State Machines & Workflows
4.  Computed Fields & On-Change Logic
5.  Business Rules & Validations
6.  Inter-Plugin Communication
7.  UI Views (Form/List/Kanban)
8.  API Endpoints
9.  Hooks (Actions & Filters)
10. Access Control (Permissions + Record Rules)
11. Menus & Navigation
12. Scheduled Tasks & Automation
13. Print & Email Templates
14. Settings & Configuration
15. Testing & Timeline
```

---

## 1. Overview & Dependencies

### 1.1 Plugin Metadata
```yaml
slug: "plugin-name"
name: "Plugin Display Name"
version: "1.0.0"
category: "sales|purchase|inventory|hr|accounting|pos|crm"
description: "Brief description"
author: "Author Name"
is_premium: true|false
min_core_version: "2.0.0"
```

### 1.2 Dependencies (CRITICAL)
```yaml
required:
  - plugin: "core"
    min_version: "2.0.0"
    reason: "Base entities and user management"
  - plugin: "contacts"
    min_version: "1.0.0"
    reason: "Customer/Vendor management"
    
optional:
  - plugin: "accounting"
    min_version: "1.0.0"
    features_enabled: ["auto_journal_entries", "payment_integration"]
  - plugin: "inventory"
    min_version: "1.0.0"
    features_enabled: ["stock_movements", "reservations"]
```

### 1.3 Plugin Interaction Matrix
```
                 | Accounting | Inventory | Sales | Purchase | POS | HR |
-----------------|------------|-----------|-------|----------|-----|----|
This Plugin      |     →      |     →     |   ↔   |    ↔     |  →  | →  |
Creates in       |  Journals  |  Moves    |   -   |    -     |  -  | -  |
Reads from       |  Accounts  |  Products |   -   |    -     |  -  | -  |
Triggers hooks   |     ✓      |     ✓     |   -   |    -     |  -  | -  |
```

---

## 2. Entity Definitions (Full Schema)

### 2.1 Entity List Summary
```
Entity              | Table Name      | Type    | Records Est. | Priority
--------------------|-----------------|---------|--------------|----------
Order               | {pfx}_orders    | Master  | 100k+        | P0
Order Line          | {pfx}_lines     | Detail  | 500k+        | P0
```

### 2.2 Detailed Entity Specification
For EACH entity, provide:

```yaml
entity: orders
table: {prefix}_orders
type: master|detail|lookup|settings|log

# Fields with FULL specifications
fields:
  - name: id
    type: bigint
    primary: true
    auto_increment: true
    
  - name: number
    type: string
    length: 32
    unique: true
    sequence: "ORDER-{YYYY}-{####}"
    label: "Order Number"
    required: true
    searchable: true
    sortable: true
    
  - name: partner_id
    type: foreign_key
    references: contacts.id
    on_delete: restrict
    label: "Customer"
    required: true
    filterable: true
    
  - name: state
    type: enum
    values: [draft, confirmed, done, cancelled]
    default: draft
    label: "Status"
    state_machine: true  # See Section 3
    
  - name: amount_total
    type: decimal
    precision: 15
    scale: 2
    computed: true  # See Section 4
    store: true
    depends: [lines.subtotal, tax_amount, discount]
    formula: "SUM(lines.subtotal) + tax_amount - discount"

# Indexes
indexes:
  - columns: [number]
    unique: true
  - columns: [partner_id, state]
    name: idx_partner_state
  - columns: [date_order]
    name: idx_date

# Soft delete
soft_delete: true
soft_delete_column: deleted_at
```

### 2.3 Relationship Diagram
```
[ASCII or Mermaid diagram showing all entity relationships]
```

---

## 3. State Machines & Workflows

### 3.1 State Definition
```yaml
entity: orders
states:
  - name: draft
    label: "Quotation"
    color: gray
    is_initial: true
    
  - name: confirmed
    label: "Confirmed"
    color: blue
    
  - name: done
    label: "Done"
    color: green
    is_final: true
    
  - name: cancelled
    label: "Cancelled"
    color: red
    is_final: true
```

### 3.2 Transitions
```yaml
transitions:
  - name: confirm
    from: [draft]
    to: confirmed
    label: "Confirm Order"
    button: true
    permission: orders.confirm
    conditions:
      - type: field
        field: lines
        operator: "not_empty"
        message: "Order must have at least one line"
      - type: field
        field: partner_id
        operator: "not_null"
    actions:
      - type: set_field
        field: date_confirmed
        value: "now()"
      - type: hook
        action: "{prefix}_order_confirmed"
      - type: create_record
        target: accounting.journals
        when: plugin_enabled(accounting)
        
  - name: cancel
    from: [draft, confirmed]
    to: cancelled
    label: "Cancel"
    permission: orders.cancel
    confirm: "Are you sure you want to cancel this order?"
    actions:
      - type: hook
        action: "{prefix}_order_cancelled"
```

### 3.3 State Diagram (Visual)
```
    ┌─────────┐     confirm      ┌───────────┐      done       ┌────────┐
    │  draft  │ ───────────────► │ confirmed │ ──────────────► │  done  │
    └────┬────┘                  └─────┬─────┘                 └────────┘
         │                             │
         │         cancel              │ cancel
         └─────────────┬───────────────┘
                       ▼
                 ┌───────────┐
                 │ cancelled │
                 └───────────┘
```

---

## 4. Computed Fields & On-Change Logic

### 4.1 Computed Fields
```yaml
computed_fields:
  - entity: orders
    field: amount_untaxed
    type: decimal
    depends: [lines.subtotal]
    formula: "SUM(lines.subtotal)"
    store: true
    recompute_on: [lines.create, lines.update, lines.delete]
    
  - entity: order_lines
    field: subtotal
    type: decimal
    depends: [quantity, unit_price, discount]
    formula: "quantity * unit_price * (1 - discount/100)"
    store: true
```

### 4.2 On-Change Triggers
```yaml
on_change:
  - entity: order_lines
    trigger_field: product_id
    actions:
      - set: unit_price
        value: "product.list_price"
      - set: name
        value: "product.name"
      - set: tax_ids
        value: "product.taxes_id"
        
  - entity: orders
    trigger_field: partner_id
    actions:
      - set: pricelist_id
        value: "partner.property_product_pricelist"
      - set: payment_term_id
        value: "partner.property_payment_term_id"
```

### 4.3 Default Values
```yaml
defaults:
  - entity: orders
    field: date_order
    value: "today()"
    
  - entity: orders
    field: user_id
    value: "current_user()"
    
  - entity: orders
    field: company_id
    value: "current_company()"
```

---

## 5. Business Rules & Validations

### 5.1 Field-Level Validations
```yaml
validations:
  - entity: orders
    field: date_order
    rules:
      - rule: required
      - rule: date
      - rule: after_or_equal
        value: "fiscal_year.start_date"
        message: "Date must be within current fiscal year"
        
  - entity: order_lines
    field: quantity
    rules:
      - rule: required
      - rule: numeric
      - rule: min
        value: 0.001
      - rule: custom
        handler: "OrderLineValidator@checkStockAvailability"
        when: "order.state == 'confirmed'"
```

### 5.2 Entity-Level Constraints
```yaml
constraints:
  - entity: orders
    name: at_least_one_line
    type: custom
    handler: "OrderConstraints@hasLines"
    on: [confirm]
    message: "Order must have at least one line"
    
  - entity: orders
    name: valid_amounts
    type: expression
    expression: "amount_total >= 0"
    message: "Total amount cannot be negative"
```

### 5.3 Cross-Entity Validations
```yaml
cross_validations:
  - name: check_credit_limit
    entities: [orders, contacts]
    handler: "CreditLimitValidator@check"
    on: [orders.confirm]
    message: "Customer has exceeded credit limit"
```

---

## 6. Inter-Plugin Communication

### 6.1 Hooks This Plugin FIRES
```yaml
actions_fired:
  - name: "{prefix}_order_creating"
    params: [order_data]
    timing: before_save
    
  - name: "{prefix}_order_created"
    params: [order]
    timing: after_save
    async_allowed: true
    
  - name: "{prefix}_order_confirmed"
    params: [order]
    listeners_expected:
      - plugin: accounting
        handler: "CreateOrderJournal"
      - plugin: inventory
        handler: "ReserveStock"

filters_fired:
  - name: "{prefix}_order_total"
    params: [total, order]
    return: decimal
    description: "Filter final order total"
    
  - name: "{prefix}_line_price"
    params: [price, line, pricelist]
    return: decimal
```

### 6.2 Hooks This Plugin LISTENS TO
```yaml
actions_listened:
  - hook: "inventory_stock_updated"
    handler: "Listeners\UpdateOrderAvailability"
    priority: 10
    
  - hook: "accounting_payment_received"
    handler: "Listeners\MarkOrderPaid"
    conditions:
      - field: source_model
        equals: "{prefix}_orders"

filters_listened:
  - hook: "accounting_journal_lines"
    handler: "Filters\AddOrderReference"
```

### 6.3 Service Contracts (APIs for other plugins)
```yaml
services_exposed:
  - name: OrderService
    methods:
      - name: createOrder
        params: [partner_id, lines, options]
        returns: Order
        
      - name: confirmOrder
        params: [order_id]
        returns: bool
        throws: [ValidationException, StockException]
        
      - name: getOrderTotals
        params: [order_id]
        returns: {untaxed, tax, total}
```

---

## 7. UI Views (Form/List/Kanban)

### 7.1 Form View
```yaml
views:
  - type: form
    entity: orders
    name: order_form
    
    header:
      - type: status_bar
        field: state
        clickable: [draft]
        
      - type: buttons
        items:
          - label: "Confirm"
            action: confirm
            state: [draft]
            type: primary
          - label: "Print"
            action: print
            type: secondary
    
    sheet:
      - type: group
        name: main_info
        columns: 2
        fields:
          - name: partner_id
            widget: many2one
            domain: "[['is_customer', '=', true]]"
          - name: date_order
          - name: pricelist_id
            groups: "sales.group_pricelist"
          - name: payment_term_id
            
      - type: notebook
        pages:
          - name: lines
            label: "Order Lines"
            content:
              - type: one2many
                field: lines
                tree_view: order_line_tree
                form_view: order_line_form
                editable: bottom
                
          - name: other_info
            label: "Other Information"
            content:
              - type: group
                fields: [user_id, team_id, company_id]
                
    sidebar:
      - type: field_group
        fields:
          - name: amount_untaxed
            widget: monetary
          - name: amount_tax
            widget: monetary
          - name: amount_total
            widget: monetary
            class: "text-lg font-bold"
```

### 7.2 List View
```yaml
  - type: list
    entity: orders
    name: order_list
    
    columns:
      - field: number
        width: 120
      - field: partner_id
        width: 200
      - field: date_order
        width: 100
      - field: amount_total
        widget: monetary
        sum: true
      - field: state
        widget: badge
        
    default_order: "date_order desc"
    
    filters:
      - name: my_orders
        label: "My Orders"
        domain: "[['user_id', '=', uid]]"
      - name: draft
        domain: "[['state', '=', 'draft']]"
      - name: this_month
        domain: "[['date_order', '>=', start_of_month]]"
        
    group_by:
      - field: state
      - field: partner_id
      - field: date_order:month
```

### 7.3 Kanban View
```yaml
  - type: kanban
    entity: orders
    name: order_kanban
    
    group_by: state
    
    card:
      title: number
      subtitle: partner_id.name
      
      fields:
        - amount_total
        - date_order
        
      footer:
        left: user_id.avatar
        right: activity_count
        
    quick_create: true
    quick_create_view: order_form_quick
```

### 7.4 Search View
```yaml
  - type: search
    entity: orders
    name: order_search
    
    fields:
      - field: number
        operator: ilike
      - field: partner_id
      - field: product_id  # searches in lines
        
    filters:
      - name: draft
        string: "Quotations"
        domain: "[['state', '=', 'draft']]"
      - name: to_invoice
        string: "To Invoice"
        domain: "[['invoice_status', '=', 'to invoice']]"
```

---

## 8. API Endpoints

### 8.1 REST Endpoints
```yaml
endpoints:
  # List with filters
  - method: GET
    path: /api/v1/{prefix}/orders
    handler: OrderController@index
    permission: orders.view
    params:
      - name: state
        type: enum
        in: query
      - name: partner_id
        type: integer
        in: query
      - name: date_from
        type: date
        in: query
      - name: date_to
        type: date
        in: query
      - name: page
        type: integer
        default: 1
      - name: per_page
        type: integer
        default: 20
        max: 100
    response:
      type: paginated
      resource: OrderResource
      
  # Create
  - method: POST
    path: /api/v1/{prefix}/orders
    handler: OrderController@store
    permission: orders.create
    request: StoreOrderRequest
    response:
      type: single
      resource: OrderResource
      status: 201
      
  # Action endpoints
  - method: POST
    path: /api/v1/{prefix}/orders/{id}/confirm
    handler: OrderController@confirm
    permission: orders.confirm
    
  # Batch operations
  - method: POST
    path: /api/v1/{prefix}/orders/batch-confirm
    handler: OrderController@batchConfirm
    permission: orders.confirm
    request:
      body:
        ids: array|required|min:1
```

### 8.2 API Resources (Response Transformers)
```yaml
resources:
  - name: OrderResource
    entity: orders
    fields:
      - id
      - number
      - partner_id
      - partner: {nested: ContactResource, when: include}
      - state
      - date_order
      - lines: {nested: OrderLineResource, when: include}
      - amount_untaxed
      - amount_tax
      - amount_total
      - created_at
      - updated_at
```

---

## 9. Hooks (Actions & Filters)

### 9.1 Actions (Events)
```yaml
actions:
  # Lifecycle hooks
  - name: "{prefix}_order_creating"
    params: [data]
    timing: before
    description: "Before order record is created"
    
  - name: "{prefix}_order_created"
    params: [order]
    timing: after
    async: true
    description: "After order is created"
    
  # Business process hooks
  - name: "{prefix}_order_confirming"
    params: [order]
    timing: before
    stoppable: true
    description: "Before confirmation - can prevent"
    
  - name: "{prefix}_order_confirmed"
    params: [order]
    timing: after
    async: true
    description: "After confirmation completes"
```

### 9.2 Filters (Transformers)
```yaml
filters:
  - name: "{prefix}_order_number_format"
    params: [format, order]
    returns: string
    default: "ORD-{YYYY}-{####}"
    
  - name: "{prefix}_line_price_compute"
    params: [price, line, context]
    returns: decimal
    description: "Filter computed line price"
    
  - name: "{prefix}_available_states"
    params: [states, order, user]
    returns: array
    description: "Filter which state transitions user can perform"
```

---

## 10. Access Control

### 10.1 Permissions
```yaml
permissions:
  - name: "{prefix}.orders.view"
    label: "View Orders"
    category: "Orders"
    
  - name: "{prefix}.orders.create"
    label: "Create Orders"
    implies: ["{prefix}.orders.view"]
    
  - name: "{prefix}.orders.update"
    label: "Edit Orders"
    implies: ["{prefix}.orders.view"]
    
  - name: "{prefix}.orders.delete"
    label: "Delete Orders"
    implies: ["{prefix}.orders.view"]
    dangerous: true
    
  - name: "{prefix}.orders.confirm"
    label: "Confirm Orders"
    implies: ["{prefix}.orders.view"]
    
  - name: "{prefix}.orders.view_all"
    label: "View All Orders"
    description: "Bypass record rules"
```

### 10.2 Roles
```yaml
roles:
  - slug: "{prefix}_manager"
    name: "Sales Manager"
    level: 700
    permissions:
      - "{prefix}.orders.*"
      - "{prefix}.reports.*"
      
  - slug: "{prefix}_user"
    name: "Salesperson"
    level: 400
    permissions:
      - "{prefix}.orders.view"
      - "{prefix}.orders.create"
      - "{prefix}.orders.update"
      - "{prefix}.orders.confirm"
```

### 10.3 Record Rules (Row-Level Security)
```yaml
record_rules:
  - name: "sales_own_orders"
    entity: orders
    domain: "[['user_id', '=', user.id]]"
    applies_to: [create, read, update]
    roles: ["{prefix}_user"]
    
  - name: "sales_team_orders"
    entity: orders
    domain: "[['team_id', 'in', user.team_ids]]"
    applies_to: [read]
    roles: ["{prefix}_team_leader"]
    
  - name: "sales_all_orders"
    entity: orders
    domain: "[]"  # No restriction
    applies_to: [create, read, update, delete]
    roles: ["{prefix}_manager"]
```

---

## 11. Menus & Navigation

```yaml
menus:
  - location: sidebar
    items:
      - label: "Sales"
        icon: "shopping-cart"
        permission: "{prefix}.orders.view"
        children:
          - label: "Orders"
            route: "{prefix}.orders.index"
            badge: "{prefix}Service@getDraftCount"
          - type: divider
          - label: "Customers"
            route: "{prefix}.customers.index"
          - label: "Products"
            route: "{prefix}.products.index"
          - type: header
            label: "Reporting"
          - label: "Dashboard"
            route: "{prefix}.dashboard"
            permission: "{prefix}.reports.view"
            
  - location: quick_actions
    items:
      - label: "New Order"
        route: "{prefix}.orders.create"
        icon: "plus"
        shortcut: "alt+n"
```

---

## 12. Scheduled Tasks & Automation

### 12.1 Scheduled Tasks
```yaml
scheduled_tasks:
  - slug: "{prefix}.check_overdue"
    name: "Check Overdue Orders"
    handler: "Services\OrderService@markOverdue"
    expression: "0 0 * * *"  # Daily midnight
    
  - slug: "{prefix}.send_reminders"
    name: "Send Order Reminders"
    handler: "Services\OrderService@sendReminders"
    expression: "0 9 * * *"  # Daily 9 AM
```

### 12.2 Automated Actions
```yaml
automations:
  - name: "auto_confirm_paid"
    trigger:
      type: field_changed
      entity: orders
      field: payment_status
      to: paid
    conditions:
      - field: state
        equals: draft
    actions:
      - type: transition
        to: confirmed
      - type: email
        template: order_confirmed
        
  - name: "notify_large_order"
    trigger:
      type: record_created
      entity: orders
    conditions:
      - field: amount_total
        operator: ">="
        value: 10000
    actions:
      - type: notify
        users: "{prefix}.managers"
        template: large_order_notification
```

---

## 13. Print & Email Templates

### 13.1 Print Templates
```yaml
print_templates:
  - slug: order_standard
    name: "Standard Order"
    entity: orders
    format: pdf
    default: true
    
  - slug: order_proforma
    name: "Proforma Invoice"
    entity: orders
    format: pdf
```

### 13.2 Email Templates
```yaml
email_templates:
  - slug: order_confirmation
    name: "Order Confirmation"
    subject: "Order {{ order.number }} Confirmed"
    entity: orders
    auto_send:
      on_action: confirm
      to: partner_id.email
      
  - slug: order_reminder
    name: "Order Reminder"
    subject: "Reminder: Order {{ order.number }}"
    entity: orders
```

---

## 14. Settings & Configuration

```yaml
settings:
  - group: "General"
    settings:
      - key: "{prefix}.default_payment_term"
        type: select
        options_from: payment_terms
        label: "Default Payment Term"
        
      - key: "{prefix}.auto_confirm"
        type: boolean
        default: false
        label: "Auto-confirm paid orders"
        
  - group: "Numbering"
    settings:
      - key: "{prefix}.order_sequence"
        type: string
        default: "ORD-{YYYY}-{####}"
        label: "Order Number Format"
```

---

## 15. Testing & Timeline

### 15.1 Test Scenarios
```yaml
test_scenarios:
  unit:
    - "Order total calculation"
    - "State transition validation"
    - "Permission checks"
    
  integration:
    - "Full order → confirm → invoice flow"
    - "Order with accounting integration"
    - "Order with inventory reservation"
    
  e2e:
    - "Create order via UI"
    - "API CRUD operations"
```

### 15.2 Timeline
```yaml
timeline:
  - phase: "Foundation"
    weeks: [1, 2]
    deliverables:
      - "Plugin skeleton"
      - "Core entities"
      - "Basic CRUD"
      
  - phase: "Features"
    weeks: [3, 6]
    deliverables:
      - "State machine"
      - "Business rules"
      - "API endpoints"
```

### 15.3 Definition of Done Checklist
```yaml
definition_of_done:
  - "Working implementation"
  - "Unit tests (90%+ coverage)"
  - "API tests"
  - "Documentation"
  - "Hooks integration"
  - "Permission checks"
  - "Audit logging"
  - "Record rules tested"
  - "UI views defined"
  - "Print templates"
```

---

## Naming Conventions

```yaml
naming:
  tables: "{plugin_prefix}_{entity_plural}"      # sales_orders
  models: "{Entity}"                              # Order
  services: "{Entity}Service"                     # OrderService
  controllers: "{Entity}Controller"               # OrderController
  permissions: "{prefix}.{entity_plural}.{action}" # sales.orders.create
  hooks_action: "{prefix}_{entity}_{action}"      # sales_order_created
  hooks_filter: "{prefix}_{what}_{verb}"          # sales_order_total_calculate
  routes_api: "/api/v1/{prefix}/{entity_plural}"  # /api/v1/sales/orders
  routes_web: "/{prefix}/{entity_plural}"         # /sales/orders
```

---

## Checklist for Plan Review

Before approving any plugin plan, verify:

```
□ All 15 sections present
□ Dependencies clearly defined
□ Entity schemas complete with all field details
□ State machines visualized
□ Computed fields documented with formulas
□ Inter-plugin hooks mapped
□ UI views specified (not just "will have forms")
□ API endpoints with request/response specs
□ Record rules defined (not just permissions)
□ Print/email templates listed
□ Test scenarios cover integrations
□ Timeline realistic (based on accounting: ~12 weeks per major plugin)
```

---

**Version:** 1.0.0  
**Last Updated:** 2025-01-XX  
**Based on:** Accounting Plugin Master Plan
