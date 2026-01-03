# Database Table Analysis Report

**Date:** Generated automatically  
**Total Tables:** 126  
**Application Tables:** 118 (excluding 8 Laravel system tables)

---

## Executive Summary

### Key Findings:
- ✅ **30 tables** have corresponding Eloquent models
- ⚠️ **47 tables** appear to be unused (no models, no code usage, 0 rows)
- ✗ **12 tables** have duplicate definitions in multiple migrations
- 📊 **34 tables** are actively used in code

---

## 🔴 Critical Issues: Duplicate Table Definitions

These tables are defined in **multiple migrations**, which can cause conflicts:

### 1. User Tables (8 duplicates)
- `console_users` - Created in 2 migrations
- `console_password_reset_tokens` - Created in 2 migrations
- `owners` - Created in 2 migrations
- `owner_password_reset_tokens` - Created in 2 migrations
- `admins` - Created in 2 migrations
- `admin_password_reset_tokens` - Created in 2 migrations
- `clients` - Created in 2 migrations
- `client_password_reset_tokens` - Created in 2 migrations

**Issue:** The migration `2025_12_23_210000_consolidate_users_table.php` DROPS these tables (lines 74-81), but the `down()` method recreates them for rollback. The original migrations (`0001_01_02_000000_create_console_users_table.php`, etc.) still exist.

**Status:** This is actually intentional - the consolidation migration drops the old tables. However, if running migrations from scratch, the old migrations will create these tables first, then the consolidation migration will try to drop them (which is fine). The duplicates in `down()` are for rollback purposes.

**Recommendation:** Consider removing the old user table migrations if you're sure you won't need to rollback before the consolidation. Or add `Schema::dropIfExists()` checks in the old migrations.

### 2. Workflow History
- `workflow_history` - Created in:
  - `2024_01_01_000001_create_platform_tables.php`
  - `2025_12_24_220000_create_workflow_history_table.php`

**Status:** The later migration (`2025_12_24_220000_create_workflow_history_table.php`) has a safety check that skips creation if the table already exists (line 15). This is actually safe, but redundant.

**Recommendation:** Remove the table creation from `2025_12_24_220000_create_workflow_history_table.php` since it already exists from the platform tables migration. Or remove it from the platform tables migration if the later one has a better structure.

### 3. Plugin Tables
- `plugin_licenses` - Created in:
  - `2025_01_01_000080_create_marketplace_tables.php`
  - `2025_12_17_162652_create_plugin_licenses_table.php`

- `plugin_updates` - Created in:
  - `2025_01_01_000080_create_marketplace_tables.php`
  - `2025_12_17_162652_create_plugin_updates_table.php`

**Recommendation:** These appear to be different implementations. Review which structure is correct and consolidate.

### 4. Audit Logs
- `audit_logs` - Created in:
  - `2025_01_01_000101_create_audit_logs_table.php`
  - `2025_12_31_000004_create_enterprise_tables.php`

**Recommendation:** Check if the enterprise version extends the original. If so, modify the enterprise migration to only add columns, not recreate the table.

---

## ⚠️ Potentially Unused Tables (47 tables)

These tables have:
- ❌ No Eloquent model
- ❌ No code references found
- ❌ 0 rows of data

### Marketplace Tables (11 tables)
Created but not yet implemented:
- `marketplace_listings`
- `marketplace_versions`
- `marketplace_submissions`
- `marketplace_review_results`
- `marketplace_installations`
- `marketplace_reviews`
- `marketplace_subscriptions`
- `marketplace_featured`
- `marketplace_plugins` (duplicate/old version)
- `installed_plugins` (old version)

**Status:** These are from `2025_12_31_000002_create_marketplace_tables.php` - likely planned for future marketplace feature.

### Plugin Security Tables (6 tables)
- `plugin_permissions`
- `plugin_scopes`
- `plugin_api_keys`
- `plugin_audit_logs`
- `plugin_sandbox_violations`
- `plugin_migrations`

**Status:** Security features not yet implemented.

### Plugin Management Tables (4 tables)
- `plugin_dependencies`
- `plugin_events`
- `plugin_settings`
- `plugin_licenses` (also has duplicate definition)

**Status:** Plugin management features not yet implemented.

### Integration Tables (2 tables)
- `integration_credentials`
- `integration_credential_access_logs`

**Status:** Credential management not yet implemented (other integration tables are used).

### API Tables (3 tables)
- `api_endpoints`
- `api_keys`
- `api_request_logs`

**Status:** API management system not yet implemented.

### Scheduler Tables (4 tables)
- `scheduled_tasks`
- `task_logs`
- `event_subscriptions`
- `recurring_jobs`

**Status:** Scheduler system not yet implemented (models exist but not used).

### Import/Export Tables (2 tables)
- `import_jobs`
- `export_jobs`

**Status:** Import/export system not yet implemented.

### View System Tables (3 tables)
- `view_definitions`
- `view_extensions`
- `compiled_views`

**Status:** View system not yet implemented (note: `ui_view_definitions` is used).

### Workflow Tables (2 tables)
- `workflow_definitions`
- `workflow_instances`

**Status:** Workflow system not yet implemented (but `workflow_history` is used).

### Other Unused Tables
- `document_templates` - Document template system not implemented
- `messages` - Messaging system not implemented
- `menu_items` - Menu items not implemented (but `menus` table is used)
- `partition_metadata` - Partitioning system not implemented
- `partition_schedules` - Has 4 rows but no model
- `cache_configs` - Cache configuration not implemented
- `config_snapshots` - Config snapshot feature not implemented
- `job_batches_extended` - Extended job batches not implemented
- `rate_limit_buckets` - Rate limiting buckets not implemented
- `tenants` - Multi-tenancy not implemented (0 rows)

### Telescope Tables (3 tables)
- `telescope_entries` - 175,745 rows (Laravel Telescope package)
- `telescope_entries_tags` - 21,737 rows (Laravel Telescope package)
- `telescope_monitoring` - 0 rows (Laravel Telescope package)

**Status:** These are from Laravel Telescope (debugging package). If not needed in production, consider removing.

---

## ✅ Actively Used Tables

These tables are actively used in the codebase:

### Core System (15 tables)
- `users`, `roles`, `permissions`, `user_roles`, `user_permissions`
- `permission_groups`, `access_rules`, `permission_audit`
- `settings`, `plugins`, `themes`
- `menus`, `shortcodes`, `shortcode_usage`
- `translations`

### Entity System (5 tables)
- `entity_definitions`, `entity_fields`, `entity_field_values`
- `entity_records`, `entity_record_terms`

### Taxonomy System (2 tables)
- `taxonomies`, `taxonomy_terms`

### Integration System (9 tables)
- `integration_flows`, `integration_flow_nodes`, `integration_flow_edges`
- `integration_flow_executions`, `integration_flow_step_executions`
- `integration_connections`, `integration_trigger_subscriptions`
- `integration_trigger_events`, `integration_action_executions`

### Enterprise Features (8 tables)
- `audit_logs`, `rate_limit_configs`, `api_quotas`
- `webhook_endpoints`, `webhook_deliveries`
- `performance_metrics`, `health_checks`, `feature_flags`
- `tenant_settings`

### Billing System (9 tables)
- `payment_methods`, `payment_transactions`
- `developer_payment_accounts`, `developer_payouts`
- `marketplace_invoices`, `marketplace_invoice_items`
- `revenue_splits`, `usage_records`
- `discount_codes`, `discount_code_uses`, `refund_requests`

### Other Active Tables
- `ui_view_definitions`, `workflow_history`
- `record_rules`, `activities`, `activity_types`
- `field_types`, `sequences`, `config_versions`
- `config_version_reviews`, `debug_traces`
- `dashboard_widgets`, `plugin_resource_usage`
- `plugin_update_history`

---

## 📋 Recommendations

### Immediate Actions:

1. **Fix Duplicate Definitions** (Priority: HIGH)
   - Review and consolidate duplicate table definitions
   - Remove redundant migrations or modify them to only add columns

2. **Remove Unused Marketplace Tables** (Priority: MEDIUM)
   - If marketplace feature is not planned soon, consider removing:
     - All `marketplace_*` tables from `2025_12_31_000002_create_marketplace_tables.php`
     - Old `marketplace_plugins` and `installed_plugins` from `2025_01_01_000080_create_marketplace_tables.php`

3. **Remove Unused Plugin Security Tables** (Priority: MEDIUM)
   - If plugin security features are not implemented:
     - Remove `plugin_permissions`, `plugin_scopes`, `plugin_api_keys`, `plugin_audit_logs`, `plugin_sandbox_violations`

4. **Remove Telescope Tables** (Priority: LOW)
   - If not using Laravel Telescope in production:
     - Remove Telescope package or disable it
     - Tables will be automatically removed

5. **Document Future Features** (Priority: LOW)
   - Create a `FUTURE_FEATURES.md` document listing:
     - Tables created for future features
     - Expected implementation timeline
     - Dependencies

### Long-term Actions:

1. **Create Models for Active Tables**
   - Many active tables don't have Eloquent models
   - Consider creating models for better code organization

2. **Implement or Remove Planned Features**
   - Decide on timeline for:
     - Marketplace system
     - Plugin security system
     - Scheduler system
     - Import/Export system
   - If not planned, remove the tables

3. **Database Cleanup Script**
   - Create a migration to drop unused tables
   - Run in development/staging first

---

## 📊 Statistics

- **Total Tables:** 126
- **Laravel System Tables:** 8
- **Application Tables:** 118
- **Tables with Models:** 30 (25%)
- **Tables with Code Usage:** 34 (29%)
- **Potentially Unused:** 47 (40%)
- **Duplicate Definitions:** 12 (10%)

---

## 🎯 Action Plan

### Phase 1: Fix Critical Issues (Week 1)
- [ ] Resolve duplicate table definitions
- [ ] Test migrations after fixes

### Phase 2: Clean Up Unused Tables (Week 2)
- [ ] Review each unused table with team
- [ ] Create migration to drop confirmed unused tables
- [ ] Test in development environment

### Phase 3: Documentation (Week 3)
- [ ] Document future feature tables
- [ ] Update migration documentation
- [ ] Create table usage guidelines

---

## Notes

- Some tables may be used via raw SQL queries not detected by the analysis
- Some tables may be planned for future features
- Always backup database before dropping tables
- Test all changes in development/staging first

