# Plugin System - Database Schema Standards

## Document Version
- **Version**: 1.0.0
- **Last Updated**: December 2024
- **Scope**: Database Design for 30 Plugin Modules

---

## Table of Contents

1. [Schema Design Principles](#1-schema-design-principles)
2. [Table Naming Conventions](#2-table-naming-conventions)
3. [Column Standards](#3-column-standards)
4. [Index Strategy](#4-index-strategy)
5. [Foreign Keys & Relationships](#5-foreign-keys--relationships)
6. [Multi-Tenancy Pattern](#6-multi-tenancy-pattern)
7. [JSON Column Usage](#7-json-column-usage)
8. [Audit & Versioning](#8-audit--versioning)
9. [Performance Patterns](#9-performance-patterns)
10. [Migration Best Practices](#10-migration-best-practices)

---

## 1. Schema Design Principles

### 1.1 Core Principles

```
1. NORMALIZE first, DENORMALIZE only for proven performance needs
2. EXPLICIT foreign keys with appropriate cascade rules
3. INDEXES for every foreign key and frequently queried column
4. CONSISTENT naming across all modules
5. SOFT DELETES for user-facing data
6. AUDIT TRAILS for sensitive operations
7. TENANT ISOLATION for multi-tenant tables
```

### 1.2 Table Categories

| Category | Prefix | Example | Characteristics |
|----------|--------|---------|-----------------|
| Core System | (none) | `users`, `roles` | Shared across tenants |
| Plugin Data | `{plugin}_` | `crm_contacts` | Plugin-specific |
| Configuration | `config_` | `config_versions` | Settings storage |
| Audit/Log | `audit_`, `log_` | `audit_logs` | Append-only |
| Queue/Temp | `job_`, `temp_` | `job_batches` | Transient data |

---

## 2. Table Naming Conventions

### 2.1 General Rules

```sql
-- ✅ CORRECT: Plural, snake_case
CREATE TABLE orders (...);
CREATE TABLE order_items (...);
CREATE TABLE user_permissions (...);

-- ❌ WRONG: Singular, camelCase, PascalCase
CREATE TABLE Order (...);
CREATE TABLE orderItem (...);
CREATE TABLE UserPermission (...);
```

### 2.2 Pivot Tables

```sql
-- Alphabetical order, singular form
CREATE TABLE order_product (...);      -- orders <-> products
CREATE TABLE permission_role (...);    -- permissions <-> roles
CREATE TABLE category_post (...);      -- categories <-> posts

-- With additional data
CREATE TABLE order_product (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE INDEX unq_order_product (order_id, product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);
```

### 2.3 Plugin Tables

```sql
-- Prefix with plugin slug (underscores)
CREATE TABLE feature_flags (...);           -- feature-flags plugin
CREATE TABLE integration_connections (...); -- integration-hub plugin
CREATE TABLE ai_conversations (...);        -- ai-assistant-hub plugin

-- Related tables keep the prefix
CREATE TABLE feature_flags (...);
CREATE TABLE feature_flag_rules (...);
CREATE TABLE feature_flag_evaluations (...);
```

---

## 3. Column Standards

### 3.1 Primary Keys

```sql
-- Always use auto-incrementing BIGINT
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

-- For UUID requirement, add separate column
uuid CHAR(36) NOT NULL UNIQUE
```

### 3.2 Foreign Keys

```sql
-- Pattern: {singular_table}_id
user_id BIGINT UNSIGNED NOT NULL,
order_id BIGINT UNSIGNED NOT NULL,
parent_category_id BIGINT UNSIGNED NULL,  -- Self-referencing

-- With foreign key constraint
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

### 3.3 Common Column Types

```sql
-- Identifiers
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid CHAR(36) NOT NULL UNIQUE
slug VARCHAR(100) NOT NULL UNIQUE

-- Strings
name VARCHAR(255) NOT NULL
title VARCHAR(500) NOT NULL
description TEXT NULL
short_description VARCHAR(1000) NULL
code VARCHAR(50) NOT NULL           -- Short codes
email VARCHAR(255) NOT NULL
phone VARCHAR(50) NULL
url VARCHAR(2048) NULL

-- Numbers
quantity INT UNSIGNED DEFAULT 0
position INT UNSIGNED DEFAULT 0
sort_order INT DEFAULT 0
level INT UNSIGNED DEFAULT 0
count BIGINT UNSIGNED DEFAULT 0
percentage DECIMAL(5,2) DEFAULT 0   -- 0.00 to 100.00
amount DECIMAL(15,2) DEFAULT 0      -- Currency
price DECIMAL(15,4) DEFAULT 0       -- Price with precision
latitude DECIMAL(10,8) NULL
longitude DECIMAL(11,8) NULL

-- Booleans
is_active BOOLEAN DEFAULT TRUE
is_featured BOOLEAN DEFAULT FALSE
is_default BOOLEAN DEFAULT FALSE
is_system BOOLEAN DEFAULT FALSE     -- System-managed, non-deletable

-- Status/Type (use VARCHAR, not ENUM)
status VARCHAR(50) DEFAULT 'draft'
type VARCHAR(50) NOT NULL
state VARCHAR(50) DEFAULT 'pending'
visibility VARCHAR(50) DEFAULT 'public'

-- JSON
settings JSON NULL
metadata JSON NULL
config JSON NULL
rules JSON NULL
attributes JSON NULL

-- Timestamps
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
deleted_at TIMESTAMP NULL           -- Soft delete
published_at TIMESTAMP NULL
expires_at TIMESTAMP NULL
started_at TIMESTAMP NULL
completed_at TIMESTAMP NULL
last_used_at TIMESTAMP NULL
last_login_at TIMESTAMP NULL

-- User tracking
created_by BIGINT UNSIGNED NULL
updated_by BIGINT UNSIGNED NULL
deleted_by BIGINT UNSIGNED NULL
assigned_to BIGINT UNSIGNED NULL
owned_by BIGINT UNSIGNED NULL
```

### 3.4 Column Ordering Convention

```sql
CREATE TABLE example (
    -- 1. Primary key
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- 2. UUIDs/External IDs
    uuid CHAR(36) NOT NULL UNIQUE,
    external_id VARCHAR(100) NULL,
    
    -- 3. Tenant/Parent foreign keys
    tenant_id BIGINT UNSIGNED NULL,
    parent_id BIGINT UNSIGNED NULL,
    
    -- 4. Main foreign keys
    user_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    
    -- 5. Core identifying fields
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    
    -- 6. Content fields
    description TEXT NULL,
    content LONGTEXT NULL,
    
    -- 7. Status/Type fields
    status VARCHAR(50) DEFAULT 'draft',
    type VARCHAR(50) NOT NULL,
    
    -- 8. Numeric fields
    sort_order INT DEFAULT 0,
    view_count BIGINT UNSIGNED DEFAULT 0,
    
    -- 9. Boolean flags
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    
    -- 10. JSON fields
    settings JSON NULL,
    metadata JSON NULL,
    
    -- 11. Timestamp fields
    published_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    
    -- 12. Audit timestamps (always last)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

---

## 4. Index Strategy

### 4.1 Index Types

```sql
-- Primary Key (automatic)
PRIMARY KEY (id)

-- Unique constraint
UNIQUE INDEX unq_users_email (email)
UNIQUE INDEX unq_slugs_tenant (tenant_id, slug)

-- Foreign key index (required)
INDEX idx_orders_user_id (user_id)

-- Query optimization index
INDEX idx_orders_status (status)
INDEX idx_orders_created (created_at)

-- Composite index (order matters!)
INDEX idx_orders_user_status (user_id, status)
INDEX idx_orders_tenant_status_date (tenant_id, status, created_at)

-- Partial index (MySQL 8.0+)
INDEX idx_active_orders (status) WHERE deleted_at IS NULL

-- Full-text index (for search)
FULLTEXT INDEX ft_products_search (name, description)
```

### 4.2 Index Naming Convention

```sql
-- Pattern: {type}_{table}_{columns}

-- Primary key (automatic)
PRIMARY

-- Unique index
unq_users_email
unq_orders_reference

-- Foreign key index
idx_orders_user_id
idx_orders_customer_id

-- Regular index
idx_orders_status
idx_orders_created_at

-- Composite index
idx_orders_user_status
idx_orders_tenant_status_created

-- Full-text index
ft_products_name_description
```

### 4.3 When to Index

```sql
-- ✅ ALWAYS INDEX:
-- Foreign keys
INDEX idx_orders_user_id (user_id)

-- Status/Type columns used in WHERE
INDEX idx_orders_status (status)

-- Date columns used in ORDER BY or WHERE
INDEX idx_orders_created_at (created_at)

-- Columns used in JOIN conditions
INDEX idx_items_order_id (order_id)

-- ❌ AVOID INDEXING:
-- Low cardinality boolean (use composite instead)
-- INDEX (is_active)  -- Bad

-- Better: composite with other filters
INDEX idx_items_active_created (is_active, created_at)

-- Frequently updated columns (index maintenance overhead)
-- Very large TEXT/BLOB columns
```

### 4.4 Composite Index Strategy

```sql
-- Rule: Most selective column first, then filter columns, then sort columns

-- Query: WHERE tenant_id = ? AND status = ? ORDER BY created_at DESC
INDEX idx_orders_tenant_status_created (tenant_id, status, created_at)

-- Query: WHERE user_id = ? AND type IN ('a','b') ORDER BY sort_order
INDEX idx_items_user_type_sort (user_id, type, sort_order)

-- Covering index (includes all columns needed by query)
INDEX idx_orders_list (tenant_id, status, created_at, id, total)
-- Allows: SELECT id, total FROM orders WHERE tenant_id=? AND status=? ORDER BY created_at
```

---

## 5. Foreign Keys & Relationships

### 5.1 Cascade Rules

```sql
-- ON DELETE CASCADE: Child deleted when parent deleted
-- Use for: Dependent data that has no meaning without parent
FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
-- order_items deleted when order deleted

-- ON DELETE SET NULL: Child kept, FK set to NULL
-- Use for: Optional relationships, audit preservation
FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
-- Task preserved but unassigned when user deleted

-- ON DELETE RESTRICT: Prevent parent deletion if children exist
-- Use for: Critical references, data integrity
FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
-- Cannot delete category with products

-- ON UPDATE CASCADE: Update child FK when parent PK changes
-- Use for: Natural keys that might change (rare)
FOREIGN KEY (category_code) REFERENCES categories(code) ON UPDATE CASCADE
```

### 5.2 Relationship Patterns

```sql
-- One-to-Many
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Many-to-Many
CREATE TABLE product_tag (
    product_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, tag_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Self-Referencing (Hierarchical)
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    level INT UNSIGNED DEFAULT 0,
    path VARCHAR(1000) NULL, -- Materialized path: "/1/5/12/"
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Polymorphic (avoid if possible, use concrete FKs)
CREATE TABLE comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commentable_type VARCHAR(100) NOT NULL, -- 'posts', 'products'
    commentable_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    INDEX idx_commentable (commentable_type, commentable_id)
    -- Note: No FK constraint possible for polymorphic
);

-- Better alternative: Separate tables
CREATE TABLE post_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE product_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

---

## 6. Multi-Tenancy Pattern

### 6.1 Tenant Column

```sql
-- Standard tenant column (nullable for system records)
tenant_id BIGINT UNSIGNED NULL,
FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
INDEX idx_table_tenant (tenant_id)

-- Composite unique with tenant scope
UNIQUE INDEX unq_products_tenant_slug (tenant_id, slug)
UNIQUE INDEX unq_users_tenant_email (tenant_id, email)
```

### 6.2 Tenant-Scoped Migration

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    
    // Tenant scope
    $table->foreignId('tenant_id')
          ->nullable()
          ->constrained()
          ->cascadeOnDelete();
    
    $table->string('name');
    $table->string('slug', 100);
    
    // ... other columns
    
    $table->timestamps();
    $table->softDeletes();
    
    // Tenant-scoped unique
    $table->unique(['tenant_id', 'slug']);
    
    // Tenant-scoped index
    $table->index(['tenant_id', 'status']);
});
```

### 6.3 Global Scope in Model

```php
// app/Scopes/TenantScope.php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = tenant()?->id) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}

// In Model
protected static function booted(): void
{
    static::addGlobalScope(new TenantScope());
    
    static::creating(function ($model) {
        if (!$model->tenant_id && tenant()) {
            $model->tenant_id = tenant()->id;
        }
    });
}
```

---

## 7. JSON Column Usage

### 7.1 When to Use JSON

```sql
-- ✅ GOOD use cases:
-- Dynamic attributes that vary by record
attributes JSON NULL     -- Product specs, form fields

-- Configuration/Settings
settings JSON NULL       -- User preferences, plugin config

-- Metadata from external systems
metadata JSON NULL       -- API responses, import data

-- Nested structures that are always read/written together
address JSON NULL        -- {street, city, state, zip, country}

-- ❌ BAD use cases (use normalized tables instead):
-- Data that needs to be queried/filtered frequently
-- Data with referential integrity requirements
-- Data that needs to be indexed for search
-- Lists that need individual item manipulation
```

### 7.2 JSON Column Patterns

```sql
-- Structured JSON with defaults
settings JSON NOT NULL DEFAULT '{}',
rules JSON NOT NULL DEFAULT '[]',
translations JSON NOT NULL DEFAULT '{}',

-- JSON with generated columns for indexing (MySQL 8.0+)
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attributes JSON NULL,
    -- Generated column from JSON
    brand VARCHAR(100) GENERATED ALWAYS AS (attributes->>'$.brand') STORED,
    INDEX idx_products_brand (brand)
);

-- Querying JSON
SELECT * FROM products 
WHERE JSON_EXTRACT(attributes, '$.color') = 'red';

-- Or using arrow syntax (MySQL 8.0+)
SELECT * FROM products 
WHERE attributes->>'$.color' = 'red';
```

### 7.3 JSON Structure Documentation

```php
/**
 * Settings JSON structure:
 * {
 *     "notifications": {
 *         "email": true,
 *         "sms": false,
 *         "push": true
 *     },
 *     "display": {
 *         "theme": "dark",
 *         "language": "en",
 *         "timezone": "UTC"
 *     },
 *     "limits": {
 *         "max_items": 100,
 *         "page_size": 25
 *     }
 * }
 */
protected $casts = [
    'settings' => 'array',
];

// Access with defaults
public function getSetting(string $path, mixed $default = null): mixed
{
    return data_get($this->settings, $path, $default);
}

// Usage
$theme = $user->getSetting('display.theme', 'light');
```

---

## 8. Audit & Versioning

### 8.1 Audit Log Table

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- What was affected
    auditable_type VARCHAR(100) NOT NULL,  -- 'orders', 'users'
    auditable_id BIGINT UNSIGNED NOT NULL,
    
    -- What happened
    event VARCHAR(50) NOT NULL,  -- 'created', 'updated', 'deleted'
    
    -- Changes
    old_values JSON NULL,
    new_values JSON NULL,
    
    -- Who did it
    user_type VARCHAR(50) NULL,  -- 'console', 'admin', 'api'
    user_id BIGINT UNSIGNED NULL,
    
    -- Context
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    url VARCHAR(2048) NULL,
    
    -- Tenant
    tenant_id BIGINT UNSIGNED NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_audit_auditable (auditable_type, auditable_id),
    INDEX idx_audit_user (user_type, user_id),
    INDEX idx_audit_tenant_created (tenant_id, created_at),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

-- Partition by month for performance
ALTER TABLE audit_logs
PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p_2024_01 VALUES LESS THAN (UNIX_TIMESTAMP('2024-02-01')),
    PARTITION p_2024_02 VALUES LESS THAN (UNIX_TIMESTAMP('2024-03-01')),
    -- ... more partitions
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### 8.2 Version History Table

```sql
CREATE TABLE entity_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Reference
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    
    -- Version info
    version INT UNSIGNED NOT NULL,
    
    -- Snapshot
    data JSON NOT NULL,
    
    -- Metadata
    change_summary VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE INDEX unq_entity_version (entity_type, entity_id, version),
    INDEX idx_entity_versions (entity_type, entity_id)
);
```

### 8.3 Soft Delete Pattern

```sql
-- Standard soft delete
deleted_at TIMESTAMP NULL,
INDEX idx_table_deleted (deleted_at)

-- Query active records
SELECT * FROM orders WHERE deleted_at IS NULL;

-- Include soft-deleted
SELECT * FROM orders; -- With withTrashed() in Eloquent

-- Only soft-deleted
SELECT * FROM orders WHERE deleted_at IS NOT NULL;
```

---

## 9. Performance Patterns

### 9.1 Table Partitioning

```sql
-- Range partitioning for time-series data
CREATE TABLE event_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    payload JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id, created_at)  -- Must include partition key
) PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p_2024_q1 VALUES LESS THAN (UNIX_TIMESTAMP('2024-04-01')),
    PARTITION p_2024_q2 VALUES LESS THAN (UNIX_TIMESTAMP('2024-07-01')),
    PARTITION p_2024_q3 VALUES LESS THAN (UNIX_TIMESTAMP('2024-10-01')),
    PARTITION p_2024_q4 VALUES LESS THAN (UNIX_TIMESTAMP('2025-01-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- List partitioning for tenant isolation
CREATE TABLE tenant_data (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    data JSON NULL,
    PRIMARY KEY (id, tenant_id)
) PARTITION BY LIST (tenant_id) (
    PARTITION p_tenant_1 VALUES IN (1),
    PARTITION p_tenant_2 VALUES IN (2),
    PARTITION p_default VALUES IN (0)
);
```

### 9.2 Read Replica Patterns

```sql
-- Use read replica connection for reports
-- In config/database.php
'mysql' => [
    'read' => [
        'host' => env('DB_READ_HOST', '127.0.0.1'),
    ],
    'write' => [
        'host' => env('DB_WRITE_HOST', '127.0.0.1'),
    ],
    // ... other config
],

-- In code
DB::connection('mysql')->select(...);           // Uses read replica
DB::connection('mysql')->insert(...);           // Uses write server
Model::query()->useWritePdo()->get();          // Force write connection
```

### 9.3 Counter Caches

```sql
-- Denormalized counter for performance
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    products_count INT UNSIGNED DEFAULT 0,  -- Counter cache
    active_products_count INT UNSIGNED DEFAULT 0
);

-- Update via trigger or application code
CREATE TRIGGER update_category_count AFTER INSERT ON products
FOR EACH ROW
UPDATE categories SET products_count = products_count + 1 
WHERE id = NEW.category_id;
```

---

## 10. Migration Best Practices

### 10.1 Migration Template

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
        Schema::create('table_name', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Tenant (if multi-tenant)
            $table->foreignId('tenant_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();
            
            // ... columns
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->unique(['tenant_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

### 10.2 Safe Migration Practices

```php
// ✅ SAFE: Add nullable column
$table->string('new_column')->nullable()->after('existing_column');

// ✅ SAFE: Add index (use algorithm for large tables)
Schema::table('large_table', function (Blueprint $table) {
    $table->index('column_name');
});

// ⚠️ CAREFUL: Add non-nullable column (needs default or backfill)
$table->string('required_column')->default('');
// Then update existing records and remove default

// ⚠️ CAREFUL: Change column type (may lock table)
DB::statement('ALTER TABLE table_name MODIFY column_name VARCHAR(500)');

// ❌ DANGEROUS: Drop column in production
// Always soft-deprecate first, then remove in later migration
```

### 10.3 Data Migration Pattern

```php
// Separate schema and data migrations

// 1. Schema migration (fast)
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('full_name')->nullable()->after('last_name');
    });
}

// 2. Data migration (separate file, can be run async)
public function up(): void
{
    DB::table('users')
        ->whereNull('full_name')
        ->orderBy('id')
        ->chunk(1000, function ($users) {
            foreach ($users as $user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'full_name' => trim($user->first_name . ' ' . $user->last_name)
                    ]);
            }
        });
}

// 3. Cleanup migration (after data verified)
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['first_name', 'last_name']);
    });
}
```

---

## Appendix A: Common Table Templates

### Users Table

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    
    avatar VARCHAR(500) NULL,
    phone VARCHAR(50) NULL,
    
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    
    status VARCHAR(50) DEFAULT 'active',
    settings JSON NULL,
    
    remember_token VARCHAR(100) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE INDEX unq_users_email (tenant_id, email),
    INDEX idx_users_tenant (tenant_id),
    INDEX idx_users_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### Generic Entity Table

```sql
CREATE TABLE entities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    tenant_id BIGINT UNSIGNED NULL,
    
    user_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    status VARCHAR(50) DEFAULT 'draft',
    type VARCHAR(50) NULL,
    
    sort_order INT DEFAULT 0,
    
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    
    settings JSON NULL,
    metadata JSON NULL,
    
    published_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE INDEX unq_entities_tenant_slug (tenant_id, slug),
    INDEX idx_entities_tenant_status (tenant_id, status),
    INDEX idx_entities_user (user_id),
    INDEX idx_entities_category (category_id),
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

---

## Appendix B: Query Patterns

### Efficient Pagination

```sql
-- Keyset pagination (for large tables)
SELECT * FROM orders
WHERE id > :last_id
ORDER BY id ASC
LIMIT 20;

-- Offset pagination (for small tables / random access)
SELECT * FROM orders
ORDER BY created_at DESC
LIMIT 20 OFFSET 40;
```

### Efficient Counting

```sql
-- For exact count (slow on large tables)
SELECT COUNT(*) FROM orders WHERE status = 'active';

-- For approximate count (fast)
SELECT TABLE_ROWS FROM information_schema.TABLES 
WHERE TABLE_NAME = 'orders';

-- For filtered approximate (use covering index)
SELECT COUNT(id) FROM orders USE INDEX (idx_orders_status)
WHERE status = 'active';
```

---

**End of Database Schema Standards**
