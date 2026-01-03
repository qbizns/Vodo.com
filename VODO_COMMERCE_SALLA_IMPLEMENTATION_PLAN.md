# Vodo Commerce → Salla Clone: Comprehensive Implementation Plan

**Generated:** 2026-01-03
**Codebase Analysis:** Complete
**Scope:** Transform vodo-commerce plugin into a full Salla API clone
**Constraint:** All code must remain within `app/Plugins/vodo-commerce/` directory

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Gap Analysis](#gap-analysis)
3. [Architecture Strategy](#architecture-strategy)
4. [Implementation Phases](#implementation-phases)
5. [Database Schema Additions](#database-schema-additions)
6. [Service Layer Expansion](#service-layer-expansion)
7. [API Endpoint Mapping](#api-endpoint-mapping)
8. [Models & Relationships](#models--relationships)
9. [Integration with Existing Code](#integration-with-existing-code)
10. [Testing Strategy](#testing-strategy)
11. [Deployment Considerations](#deployment-considerations)

---

## Executive Summary

### Current State
**Vodo Commerce** is a production-ready e-commerce plugin with:
- ✅ 16,959 lines of clean PHP code
- ✅ Core e-commerce features (products, orders, customers, cart, checkout)
- ✅ OAuth 2.0 implementation with 30+ scopes
- ✅ Multi-store SaaS architecture
- ✅ Plugin-based extensibility (payment gateways, shipping, tax)
- ✅ Inventory reservation system
- ✅ Basic webhook infrastructure
- ✅ Admin panel and storefront

### Target State
**Salla API** provides:
- **193 API endpoints** across 47 categories
- Advanced product management (options, tags, images, digital products)
- Customer segmentation (groups, wallet, affiliates, loyalty points)
- Comprehensive order management (custom statuses, histories, invoices, assignments)
- Multi-branch operations with stock allocation
- Shipping integration (companies, zones, routes, shipment tracking)
- Financial management (transactions, coupons, taxes)
- Webhook system with conditional filtering
- Advanced catalog features (brands, reviews, special offers)

### Implementation Approach
**Plugin-First Strategy**: Keep everything within the vodo-commerce plugin by:
1. **Extending existing models** rather than creating new tables where possible
2. **Adding new services** for missing functionality
3. **Creating new models/migrations** only for genuinely new entities
4. **Leveraging hooks** to integrate with platform features
5. **Using registries** for extensibility (payment, shipping, tax)
6. **Building REST API** that mirrors Salla's OpenAPI specification

**Estimated Scope**: ~45,000 additional lines of code across 7 phases

---

## Gap Analysis

### What We Have ✅

| Feature | Status | Notes |
|---------|--------|-------|
| Products (basic) | ✅ Full | CRUD, variants, categories, stock |
| Customers (basic) | ✅ Full | CRUD, addresses, orders |
| Orders (basic) | ✅ Full | Creation, statuses, items |
| Cart & Checkout | ✅ Full | With inventory reservations |
| Discounts/Coupons | ✅ Full | Code-based discounts |
| OAuth 2.0 | ✅ Full | RFC-compliant with PKCE |
| Webhooks (framework) | ✅ Partial | Events defined, needs endpoints |
| Multi-store | ✅ Full | Tenant isolation built-in |
| Admin UI | ✅ Full | Dashboard, CRUD interfaces |
| Storefront | ✅ Full | Product browsing, cart, checkout |

### What We Need to Add ⚠️

#### 🔴 CRITICAL GAPS (Phase 1-2)

| Feature Category | Salla Endpoints | Current Status | Priority |
|------------------|-----------------|----------------|----------|
| **Product Options & Templates** | 8 endpoints | ❌ Missing | HIGH |
| **Product Tags** | 4 endpoints | ❌ Missing | HIGH |
| **Product Images** | 3 endpoints | ❌ Missing | HIGH |
| **Digital Products** | 3 endpoints | ❌ Missing | MEDIUM |
| **Customer Groups** | 5 endpoints | ❌ Missing | HIGH |
| **Customer Wallet** | 3 endpoints | ❌ Missing | MEDIUM |
| **Affiliates** | 6 endpoints | ❌ Missing | MEDIUM |
| **Loyalty Points** | 3 endpoints | ❌ Missing | LOW |
| **Employees** | 2 endpoints | ❌ Missing | MEDIUM |
| **Brands** | 5 endpoints | ❌ Missing | HIGH |
| **Reviews** | 3 endpoints | ❌ Missing | MEDIUM |
| **Special Offers** | 4 endpoints | ❌ Missing | MEDIUM |

#### 🟡 MAJOR GAPS (Phase 3-4)

| Feature Category | Salla Endpoints | Current Status | Priority |
|------------------|-----------------|----------------|----------|
| **Order Statuses (custom)** | 7 endpoints | ⚠️ Partial | HIGH |
| **Order Histories** | 2 endpoints | ❌ Missing | HIGH |
| **Order Invoices** | 5 endpoints | ❌ Missing | HIGH |
| **Order Assignments** | 4 endpoints | ❌ Missing | MEDIUM |
| **Order Options** | 2 endpoints | ❌ Missing | LOW |
| **Shipments** | 7 endpoints | ❌ Missing | HIGH |
| **Shipping Companies** | 5 endpoints | ⚠️ Framework | HIGH |
| **Shipping Zones** | 5 endpoints | ❌ Missing | MEDIUM |
| **Shipping Routes** | 5 endpoints | ❌ Missing | MEDIUM |
| **Transactions** | 2 endpoints | ❌ Missing | HIGH |
| **Taxes (advanced)** | 3 endpoints | ⚠️ Framework | MEDIUM |

#### 🟢 MINOR GAPS (Phase 5-6)

| Feature Category | Salla Endpoints | Current Status | Priority |
|------------------|-----------------|----------------|----------|
| **Multi-branch** | 11 endpoints | ❌ Missing | MEDIUM |
| **Abandoned Carts** | 2 endpoints | ⚠️ Detection only | LOW |
| **Countries/Cities** | 3 endpoints | ❌ Missing | LOW |
| **Currencies** | 3 endpoints | ⚠️ Basic support | MEDIUM |
| **Languages** | 3 endpoints | ❌ Missing | LOW |
| **DNS Records** | 3 endpoints | ❌ Missing | LOW |
| **Custom URLs** | 2 endpoints | ❌ Missing | LOW |
| **Payments List** | 3 endpoints | ⚠️ Partial | MEDIUM |

#### 🔵 EXISTING (Enhancement Only)

| Feature Category | Salla Endpoints | Current Status | Notes |
|------------------|-----------------|----------------|-------|
| **Webhooks** | 5 endpoints | ⚠️ Framework exists | Add filtering & versioning |
| **Products** | 8 endpoints | ✅ Partial | Extend with options/tags/images |
| **Customers** | 8 endpoints | ✅ Basic | Add groups, wallet, ban/import |
| **Orders** | 8 endpoints | ✅ Basic | Add histories, invoices, assignments |
| **Categories** | 8 endpoints | ✅ Full | Minor enhancements |

---

## Architecture Strategy

### 1. Directory Structure Extension

```
app/Plugins/vodo-commerce/
├── Api/                                    # EXISTING
│   ├── CommerceApiDocumentation.php
│   ├── CommerceOpenApiGenerator.php
│   └── WebhookEventCatalog.php
│   └── Transformers/                       # NEW - API response transformers
│       ├── ProductTransformer.php
│       ├── OrderTransformer.php
│       ├── CustomerTransformer.php
│       └── ...
├── Http/
│   ├── Controllers/
│   │   ├── Api/                            # EXTEND
│   │   │   ├── V2/                         # NEW - Salla-compatible API v2
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── ProductVariantController.php
│   │   │   │   ├── ProductOptionController.php
│   │   │   │   ├── ProductTagController.php
│   │   │   │   ├── ProductImageController.php
│   │   │   │   ├── DigitalProductController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── BrandController.php
│   │   │   │   ├── ReviewController.php
│   │   │   │   ├── SpecialOfferController.php
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── CustomerGroupController.php
│   │   │   │   ├── CustomerWalletController.php
│   │   │   │   ├── AffiliateController.php
│   │   │   │   ├── LoyaltyPointController.php
│   │   │   │   ├── EmployeeController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── OrderItemController.php
│   │   │   │   ├── OrderStatusController.php
│   │   │   │   ├── OrderHistoryController.php
│   │   │   │   ├── OrderInvoiceController.php
│   │   │   │   ├── OrderAssignmentController.php
│   │   │   │   ├── ShipmentController.php
│   │   │   │   ├── ShippingCompanyController.php
│   │   │   │   ├── ShippingZoneController.php
│   │   │   │   ├── ShippingRouteController.php
│   │   │   │   ├── BranchController.php
│   │   │   │   ├── BranchAllocationController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── TransactionController.php
│   │   │   │   ├── CouponController.php (extend Discount)
│   │   │   │   ├── TaxController.php
│   │   │   │   ├── WebhookController.php
│   │   │   │   ├── CurrencyController.php
│   │   │   │   ├── LanguageController.php
│   │   │   │   ├── CountryController.php
│   │   │   │   ├── AbandonedCartController.php
│   │   │   │   └── MerchantController.php
│   │   │   └── ...existing...
│   ├── Requests/                           # EXTEND
│   │   ├── Api/                            # NEW - API validation
│   │   │   ├── StoreProductRequest.php
│   │   │   ├── UpdateProductRequest.php
│   │   │   ├── StoreCustomerRequest.php
│   │   │   ├── StoreOrderRequest.php
│   │   │   └── ... (one per POST/PUT endpoint)
│   ├── Resources/                          # NEW - API Resources (Eloquent)
│   │   ├── ProductResource.php
│   │   ├── ProductVariantResource.php
│   │   ├── OrderResource.php
│   │   ├── CustomerResource.php
│   │   ├── ShipmentResource.php
│   │   └── ... (one per entity)
├── Models/                                 # EXTEND
│   ├── Product.php                         # EXISTING - extend
│   ├── ProductOption.php                   # NEW
│   ├── ProductOptionValue.php              # NEW
│   ├── ProductOptionTemplate.php           # NEW
│   ├── ProductTag.php                      # NEW
│   ├── ProductImage.php                    # NEW
│   ├── DigitalProductFile.php              # NEW
│   ├── Brand.php                           # NEW
│   ├── Review.php                          # NEW
│   ├── SpecialOffer.php                    # NEW
│   ├── Customer.php                        # EXISTING - extend
│   ├── CustomerGroup.php                   # NEW
│   ├── CustomerWallet.php                  # NEW
│   ├── CustomerWalletTransaction.php       # NEW
│   ├── Affiliate.php                       # NEW
│   ├── AffiliateLink.php                   # NEW
│   ├── LoyaltyPoint.php                    # NEW
│   ├── Employee.php                        # NEW
│   ├── Order.php                           # EXISTING - extend
│   ├── OrderStatus.php                     # NEW
│   ├── OrderHistory.php                    # NEW
│   ├── OrderInvoice.php                    # NEW
│   ├── OrderAssignment.php                 # NEW
│   ├── OrderAssignmentRule.php             # NEW
│   ├── Shipment.php                        # NEW
│   ├── ShippingCompany.php                 # NEW
│   ├── ShippingZone.php                    # NEW
│   ├── ShippingRoute.php                   # NEW
│   ├── Branch.php                          # NEW
│   ├── BranchAllocation.php                # NEW
│   ├── BranchDeliveryZone.php              # NEW
│   ├── Transaction.php                     # NEW
│   ├── Tax.php                             # NEW
│   ├── Webhook.php                         # NEW
│   ├── Currency.php                        # NEW
│   ├── Language.php                        # NEW
│   ├── Country.php                         # NEW
│   ├── City.php                            # NEW
│   └── ... existing models ...
├── Services/                               # EXTEND
│   ├── CartService.php                     # EXISTING
│   ├── CheckoutService.php                 # EXISTING
│   ├── OrderService.php                    # EXISTING - extend
│   ├── ProductService.php                  # EXISTING - extend
│   ├── InventoryReservationService.php     # EXISTING
│   ├── BrandService.php                    # NEW
│   ├── ReviewService.php                   # NEW
│   ├── CustomerGroupService.php            # NEW
│   ├── CustomerWalletService.php           # NEW
│   ├── AffiliateService.php                # NEW
│   ├── LoyaltyPointService.php             # NEW
│   ├── ShipmentService.php                 # NEW
│   ├── ShippingZoneService.php             # NEW
│   ├── InvoiceService.php                  # NEW
│   ├── TransactionService.php              # NEW
│   ├── TaxCalculationService.php           # NEW
│   ├── WebhookService.php                  # NEW
│   ├── BranchService.php                   # NEW
│   ├── DigitalProductService.php           # NEW
│   └── ... existing services ...
├── database/migrations/                    # EXTEND (add ~40 new migrations)
├── routes/
│   ├── api.php                             # EXTEND - add v2 routes
│   └── ... existing routes ...
└── Tests/                                  # EXTEND
    ├── Feature/
    │   ├── Api/
    │   │   ├── ProductApiTest.php          # NEW
    │   │   ├── CustomerApiTest.php         # NEW
    │   │   ├── OrderApiTest.php            # NEW
    │   │   └── ... (one per controller)
    │   └── ... existing tests ...
    └── Unit/
        ├── Services/
        │   ├── BrandServiceTest.php        # NEW
        │   ├── WalletServiceTest.php       # NEW
        │   └── ...
        └── ... existing tests ...
```

### 2. Leveraging Existing Infrastructure

#### Use What We Have
1. **OAuth Scopes** - Extend `CommerceScopes.php` with new scopes matching Salla's
2. **Entity Registry** - Register all new entities (brands, reviews, etc.)
3. **View Registry** - Add list/kanban views for new entities
4. **Hook System** - Fire commerce events for all new operations
5. **Circuit Breaker** - Wrap external API calls (shipping companies, tax providers)
6. **Idempotency** - Use existing middleware for order/payment endpoints
7. **Multi-store** - Use `BelongsToStore` trait on all new models
8. **Sandbox** - Existing sandbox provisioning for testing

#### Extend What We Have
1. **Product Model** - Add relationships for options, tags, images
2. **Customer Model** - Add relationships for groups, wallet, affiliates
3. **Order Model** - Add relationships for histories, invoices, shipments
4. **CommerceEvents** - Add 50+ new event constants
5. **CommerceOpenApiGenerator** - Generate Salla-compatible OpenAPI spec

---

## Implementation Phases

### Phase 1: Product Management Extensions (2-3 weeks)
**Goal**: Complete Salla's product management capabilities

#### Deliverables
- ✅ Product Options & Option Templates (8 endpoints)
- ✅ Product Tags (4 endpoints)
- ✅ Product Images (3 endpoints)
- ✅ Digital Products (3 endpoints)
- ✅ Brands (5 endpoints)
- ✅ Update Product endpoints to include variants fully

#### New Models
- `ProductOption`, `ProductOptionValue`, `ProductOptionTemplate`
- `ProductTag`, `ProductImage`
- `DigitalProductFile`, `DigitalProductCode`
- `Brand`

#### New Services
- `ProductOptionService`
- `ProductTagService`
- `DigitalProductService`
- `BrandService`

#### New Migrations
1. `create_commerce_brands_table`
2. `create_commerce_product_options_table`
3. `create_commerce_product_option_values_table`
4. `create_commerce_product_option_templates_table`
5. `create_commerce_product_tags_table`
6. `create_commerce_product_tag_pivot_table`
7. `create_commerce_product_images_table`
8. `create_commerce_digital_product_files_table`
9. `create_commerce_digital_product_codes_table`

#### API Endpoints (18 total)
```
POST   /api/admin/v2/products/{product}/variants
GET    /api/admin/v2/products/{product}/variants
PUT    /api/admin/v2/products/{product}/variants/{variant}
PATCH  /api/admin/v2/products/{product}/variants/{variant}/quantity

GET    /api/admin/v2/products/{product}/options
POST   /api/admin/v2/products/{product}/options
PUT    /api/admin/v2/products/{product}/options/{option}
DELETE /api/admin/v2/products/{product}/options/{option}

GET    /api/admin/v2/product-option-templates
POST   /api/admin/v2/product-option-templates
GET    /api/admin/v2/product-option-templates/{template}
PUT    /api/admin/v2/product-option-templates/{template}

GET    /api/admin/v2/tags
POST   /api/admin/v2/tags
GET    /api/admin/v2/tags/{tag}
DELETE /api/admin/v2/tags/{tag}

POST   /api/admin/v2/products/{product}/images
DELETE /api/admin/v2/products/{product}/images/{image}

GET    /api/admin/v2/brands
POST   /api/admin/v2/brands
GET    /api/admin/v2/brands/{brand}
PUT    /api/admin/v2/brands/{brand}
DELETE /api/admin/v2/brands/{brand}

POST   /api/admin/v2/products/{product}/digital-files
GET    /api/admin/v2/products/{product}/digital-codes
POST   /api/admin/v2/products/{product}/digital-codes
```

---

### Phase 2: Customer Management Extensions (2-3 weeks)
**Goal**: Complete customer segmentation and engagement features

#### Deliverables
- ✅ Customer Groups (5 endpoints)
- ✅ Customer Wallet (3 endpoints)
- ✅ Affiliates (6 endpoints)
- ✅ Loyalty Points (3 endpoints)
- ✅ Employee Management (2 endpoints)
- ✅ Enhanced Customer endpoints (ban, import, bulk)

#### New Models
- `CustomerGroup`, `CustomerGroupMembership`
- `CustomerWallet`, `CustomerWalletTransaction`
- `Affiliate`, `AffiliateLink`, `AffiliateCommission`
- `LoyaltyPoint`, `LoyaltyPointTransaction`
- `Employee`, `EmployeeRole`

#### New Services
- `CustomerGroupService`
- `CustomerWalletService`
- `AffiliateService`
- `LoyaltyPointService`
- `EmployeeService`

#### New Migrations
1. `create_commerce_customer_groups_table`
2. `create_commerce_customer_group_memberships_table`
3. `create_commerce_customer_wallets_table`
4. `create_commerce_customer_wallet_transactions_table`
5. `create_commerce_affiliates_table`
6. `create_commerce_affiliate_links_table`
7. `create_commerce_affiliate_commissions_table`
8. `create_commerce_loyalty_points_table`
9. `create_commerce_loyalty_point_transactions_table`
10. `create_commerce_employees_table`
11. `add_groups_to_customers_table` (migration to add JSON column)
12. `add_is_banned_to_customers_table`

#### API Endpoints (19 total)
```
GET    /api/admin/v2/customers
POST   /api/admin/v2/customers
GET    /api/admin/v2/customers/{customer}
PUT    /api/admin/v2/customers/{customer}
DELETE /api/admin/v2/customers/{customer}
POST   /api/admin/v2/customers/{customer}/ban
POST   /api/admin/v2/customers/{customer}/unban
POST   /api/admin/v2/customers/import

GET    /api/admin/v2/customer-groups
POST   /api/admin/v2/customer-groups
GET    /api/admin/v2/customer-groups/{group}
PUT    /api/admin/v2/customer-groups/{group}
DELETE /api/admin/v2/customer-groups/{group}

POST   /api/admin/v2/customers/{customer}/wallet/deposit
POST   /api/admin/v2/customers/{customer}/wallet/withdraw
GET    /api/admin/v2/customers/{customer}/wallet/transactions

GET    /api/admin/v2/affiliates
POST   /api/admin/v2/affiliates
GET    /api/admin/v2/affiliates/{affiliate}
PUT    /api/admin/v2/affiliates/{affiliate}
DELETE /api/admin/v2/affiliates/{affiliate}
GET    /api/admin/v2/affiliates/{affiliate}/links

GET    /api/admin/v2/employees
GET    /api/admin/v2/employees/{employee}
```

---

### Phase 3: Order Management Extensions (2-3 weeks)
**Goal**: Advanced order workflows and tracking

#### Deliverables
- ✅ Custom Order Statuses (7 endpoints)
- ✅ Order Histories (2 endpoints)
- ✅ Order Invoices (5 endpoints)
- ✅ Order Assignments (4 endpoints)
- ✅ Order Items API (4 endpoints)
- ✅ Reviews & Ratings (3 endpoints)
- ✅ Special Offers (4 endpoints)

#### New Models
- `OrderStatus`, `OrderStatusHistory`
- `OrderHistory`
- `OrderInvoice`
- `OrderAssignment`, `OrderAssignmentRule`
- `Review`, `ReviewImage`
- `SpecialOffer`, `SpecialOfferCondition`

#### New Services
- `OrderStatusService`
- `OrderHistoryService`
- `InvoiceService`
- `OrderAssignmentService`
- `ReviewService`
- `SpecialOfferService`

#### New Migrations
1. `create_commerce_order_statuses_table`
2. `create_commerce_order_status_histories_table`
3. `create_commerce_order_histories_table`
4. `create_commerce_order_invoices_table`
5. `create_commerce_order_assignments_table`
6. `create_commerce_order_assignment_rules_table`
7. `create_commerce_reviews_table`
8. `create_commerce_review_images_table`
9. `create_commerce_special_offers_table`
10. `create_commerce_special_offer_conditions_table`

#### API Endpoints (29 total)
```
GET    /api/admin/v2/orders
POST   /api/admin/v2/orders
GET    /api/admin/v2/orders/{order}
PUT    /api/admin/v2/orders/{order}
DELETE /api/admin/v2/orders/{order}

GET    /api/admin/v2/orders/items?order_id={id}
POST   /api/admin/v2/orders/{order}/items
PUT    /api/admin/v2/orders/{order}/items/{item}
DELETE /api/admin/v2/orders/{order}/items/{item}

GET    /api/admin/v2/order-statuses
POST   /api/admin/v2/order-statuses
GET    /api/admin/v2/order-statuses/{status}
PUT    /api/admin/v2/order-statuses/{status}
DELETE /api/admin/v2/order-statuses/{status}
POST   /api/admin/v2/orders/{order}/status
POST   /api/admin/v2/orders/bulk-status

GET    /api/admin/v2/orders/{order}/histories
POST   /api/admin/v2/orders/{order}/histories

GET    /api/admin/v2/orders/{order}/invoices
POST   /api/admin/v2/orders/{order}/invoices
GET    /api/admin/v2/orders/{order}/invoices/{invoice}
POST   /api/admin/v2/orders/{order}/send-invoice
DELETE /api/admin/v2/orders/{order}/invoices/{invoice}

GET    /api/admin/v2/order-assignment-rules
POST   /api/admin/v2/order-assignment-rules
PUT    /api/admin/v2/order-assignment-rules/{rule}
DELETE /api/admin/v2/order-assignment-rules/{rule}

GET    /api/admin/v2/products/{product}/reviews
POST   /api/admin/v2/products/{product}/reviews
DELETE /api/admin/v2/products/{product}/reviews/{review}

GET    /api/admin/v2/special-offers
POST   /api/admin/v2/special-offers
GET    /api/admin/v2/special-offers/{offer}
PUT    /api/admin/v2/special-offers/{offer}
```

---

### Phase 4: Shipping & Logistics (2-3 weeks)
**Goal**: Complete shipping integration and tracking

#### Deliverables
- ✅ Shipments (7 endpoints)
- ✅ Shipping Companies (5 endpoints)
- ✅ Shipping Zones (5 endpoints)
- ✅ Shipping Routes (5 endpoints)
- ✅ Branch Delivery Zones (5 endpoints)

#### New Models
- `Shipment`, `ShipmentItem`, `ShipmentTracking`
- `ShippingCompany`, `ShippingCompanyCredentials`
- `ShippingZone`, `ShippingZonePostalCode`
- `ShippingRoute`, `ShippingRoutePrice`
- `BranchDeliveryZone`

#### New Services
- `ShipmentService`
- `ShippingCompanyService`
- `ShippingZoneService`
- `ShippingRouteService`

#### Extend Existing
- `ShippingCarrierContract` - Add tracking methods
- `ShippingCarrierRegistry` - Register company integrations

#### New Migrations
1. `create_commerce_shipments_table`
2. `create_commerce_shipment_items_table`
3. `create_commerce_shipment_tracking_table`
4. `create_commerce_shipping_companies_table`
5. `create_commerce_shipping_company_credentials_table`
6. `create_commerce_shipping_zones_table`
7. `create_commerce_shipping_zone_postal_codes_table`
8. `create_commerce_shipping_routes_table`
9. `create_commerce_shipping_route_prices_table`
10. `create_commerce_branch_delivery_zones_table`

#### API Endpoints (27 total)
```
GET    /api/admin/v2/shipments
POST   /api/admin/v2/shipments
GET    /api/admin/v2/shipments/{shipment}
PUT    /api/admin/v2/shipments/{shipment}
POST   /api/admin/v2/shipments/{shipment}/track
POST   /api/admin/v2/shipments/{shipment}/cancel
DELETE /api/admin/v2/shipments/{shipment}

GET    /api/admin/v2/shipping-companies
POST   /api/admin/v2/shipping-companies
GET    /api/admin/v2/shipping-companies/{company}
PUT    /api/admin/v2/shipping-companies/{company}
DELETE /api/admin/v2/shipping-companies/{company}

GET    /api/admin/v2/shipping-zones
POST   /api/admin/v2/shipping-zones
GET    /api/admin/v2/shipping-zones/{zone}
PUT    /api/admin/v2/shipping-zones/{zone}
DELETE /api/admin/v2/shipping-zones/{zone}

GET    /api/admin/v2/shipping-routes
POST   /api/admin/v2/shipping-routes
GET    /api/admin/v2/shipping-routes/{route}
PUT    /api/admin/v2/shipping-routes/{route}
DELETE /api/admin/v2/shipping-routes/{route}

GET    /api/admin/v2/branch-delivery-zones
POST   /api/admin/v2/branch-delivery-zones
GET    /api/admin/v2/branch-delivery-zones/{zone}
PUT    /api/admin/v2/branch-delivery-zones/{zone}
DELETE /api/admin/v2/branch-delivery-zones/{zone}
```

---

### Phase 5: Financial Management (1-2 weeks)
**Goal**: Complete financial tracking and reporting

#### Deliverables
- ✅ Payment Methods (3 endpoints)
- ✅ Transactions (2 endpoints)
- ✅ Enhanced Coupons (4 endpoints - extend existing Discounts)
- ✅ Taxes (3 endpoints)

#### New Models
- `PaymentMethod`, `PaymentMethodConfiguration`
- `Transaction`, `TransactionFee`
- Extend `Discount` model with Salla features

#### New Services
- `PaymentMethodService`
- `TransactionService`
- Extend `TaxCalculationService`

#### New Migrations
1. `create_commerce_payment_methods_table`
2. `create_commerce_transactions_table`
3. `create_commerce_transaction_fees_table`
4. `create_commerce_taxes_table`
5. `add_salla_fields_to_discounts_table`

#### API Endpoints (12 total)
```
GET    /api/admin/v2/payment-methods
GET    /api/admin/v2/payment-methods/{method}
GET    /api/admin/v2/payment-methods/{method}/banks

GET    /api/admin/v2/transactions
PUT    /api/admin/v2/transactions/{transaction}

GET    /api/admin/v2/coupons
POST   /api/admin/v2/coupons
GET    /api/admin/v2/coupons/{coupon}
PUT    /api/admin/v2/coupons/{coupon}
POST   /api/admin/v2/coupons/{coupon}/validate

GET    /api/admin/v2/taxes
POST   /api/admin/v2/taxes
DELETE /api/admin/v2/taxes/{tax}
```

---

### Phase 6: Multi-Branch & Localization (1-2 weeks)
**Goal**: Multi-location support and internationalization

#### Deliverables
- ✅ Branches (5 endpoints)
- ✅ Branch Allocations (6 endpoints)
- ✅ Currencies (3 endpoints)
- ✅ Languages (3 endpoints)
- ✅ Countries & Cities (3 endpoints)
- ✅ Abandoned Carts (2 endpoints)

#### New Models
- `Branch`, `BranchSettings`
- `BranchAllocation`, `BranchStockTransfer`
- `Currency`, `CurrencyExchangeRate`
- `Language`, `LanguageTranslation`
- `Country`, `City`

#### New Services
- `BranchService`
- `BranchAllocationService`
- `CurrencyService`
- `LanguageService`
- `GeoLocationService`
- Extend `CartService` for abandoned cart recovery

#### New Migrations
1. `create_commerce_branches_table`
2. `create_commerce_branch_settings_table`
3. `create_commerce_branch_allocations_table`
4. `create_commerce_branch_stock_transfers_table`
5. `create_commerce_currencies_table`
6. `create_commerce_currency_exchange_rates_table`
7. `create_commerce_languages_table`
8. `create_commerce_language_translations_table`
9. `create_commerce_countries_table`
10. `create_commerce_cities_table`
11. `add_branch_id_to_inventory_table`

#### API Endpoints (22 total)
```
GET    /api/admin/v2/branches
POST   /api/admin/v2/branches
GET    /api/admin/v2/branches/{branch}
PUT    /api/admin/v2/branches/{branch}
DELETE /api/admin/v2/branches/{branch}

GET    /api/admin/v2/branch-allocations
POST   /api/admin/v2/branch-allocations
GET    /api/admin/v2/branch-allocations/{allocation}
PUT    /api/admin/v2/branch-allocations/{allocation}
DELETE /api/admin/v2/branch-allocations/{allocation}
POST   /api/admin/v2/branch-allocations/transfer

GET    /api/admin/v2/currencies
POST   /api/admin/v2/currencies
DELETE /api/admin/v2/currencies/{currency}

GET    /api/admin/v2/languages
POST   /api/admin/v2/languages
DELETE /api/admin/v2/languages/{language}

GET    /api/admin/v2/countries
GET    /api/admin/v2/countries/{country}/cities
GET    /api/admin/v2/cities/{city}

GET    /api/admin/v2/abandoned-carts
GET    /api/admin/v2/abandoned-carts/{cart}
```

---

### Phase 7: Webhooks & Integration (1 week)
**Goal**: Complete webhook system with filtering

#### Deliverables
- ✅ Webhook Management (5 endpoints)
- ✅ Event Filtering with Conditions
- ✅ Webhook Versioning (v1, v2)
- ✅ Custom Headers Support
- ✅ Webhook Signing & Verification

#### New Models
- `Webhook`, `WebhookDelivery`, `WebhookLog`
- `WebhookEvent`, `WebhookFilter`

#### Extend Services
- Extend `WebhookService` with Salla features
- Extend `CommerceWebhookBridge`

#### New Migrations
1. `create_commerce_webhooks_table`
2. `create_commerce_webhook_deliveries_table`
3. `create_commerce_webhook_logs_table`
4. `create_commerce_webhook_events_table`

#### API Endpoints (5 total)
```
GET    /api/admin/v2/webhooks
POST   /api/admin/v2/webhooks/subscribe
GET    /api/admin/v2/webhooks/events
PUT    /api/admin/v2/webhooks/{webhook}
POST   /api/admin/v2/webhooks/deactivate
```

---

## Database Schema Additions

### New Tables Summary

| Phase | Tables Added | Total Columns | Indexes |
|-------|--------------|---------------|---------|
| Phase 1 | 9 tables | ~85 columns | ~20 indexes |
| Phase 2 | 11 tables | ~110 columns | ~25 indexes |
| Phase 3 | 10 tables | ~95 columns | ~22 indexes |
| Phase 4 | 10 tables | ~90 columns | ~24 indexes |
| Phase 5 | 5 tables | ~45 columns | ~12 indexes |
| Phase 6 | 11 tables | ~95 columns | ~20 indexes |
| Phase 7 | 4 tables | ~40 columns | ~10 indexes |
| **TOTAL** | **60 tables** | **~560 columns** | **~133 indexes** |

### Key Schema Patterns

#### 1. Multi-Store Scoping (All Tables)
```php
$table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
$table->index(['store_id', 'created_at']);
```

#### 2. Soft Deletes (Most Tables)
```php
$table->softDeletes();
$table->index('deleted_at');
```

#### 3. Audit Fields (All Tables)
```php
$table->timestamps();
$table->foreignId('created_by')->nullable()->constrained('users');
$table->foreignId('updated_by')->nullable()->constrained('users');
```

#### 4. JSON Metadata (Flexible Fields)
```php
$table->json('meta')->nullable();
$table->json('settings')->nullable();
$table->json('conditions')->nullable();
```

#### 5. Status Tracking
```php
$table->enum('status', ['active', 'inactive', 'pending'])->default('active');
$table->index(['store_id', 'status']);
```

### Critical Migrations Detail

#### Phase 1: Product Options Schema
```sql
-- commerce_product_options
id, store_id, product_id, name, type (select/radio/checkbox),
values (JSON), required, position, created_at, updated_at

-- commerce_product_option_templates
id, store_id, name, options (JSON array), created_at, updated_at

-- commerce_product_tags
id, store_id, name, slug, created_at, updated_at

-- commerce_product_tag_pivot
product_id, tag_id

-- commerce_brands
id, store_id, name, slug, logo, description, website,
is_active, created_at, updated_at, deleted_at
```

#### Phase 2: Customer Extensions
```sql
-- commerce_customer_wallets
id, store_id, customer_id, balance (decimal 15,2),
currency, created_at, updated_at

-- commerce_customer_wallet_transactions
id, wallet_id, type (credit/debit), amount, balance_after,
description, reference, created_at

-- commerce_affiliates
id, store_id, customer_id, code, commission_rate,
total_sales, total_commission, status, created_at, updated_at

-- commerce_loyalty_points
id, store_id, customer_id, points_balance,
lifetime_points, created_at, updated_at
```

#### Phase 3: Order Extensions
```sql
-- commerce_order_statuses
id, store_id, name, slug, color, is_system, is_default,
position, created_at, updated_at

-- commerce_order_invoices
id, store_id, order_id, invoice_number, amount,
status, pdf_path, sent_at, created_at, updated_at

-- commerce_shipments
id, store_id, order_id, shipping_company_id,
tracking_number, status (enum), shipped_at,
delivered_at, created_at, updated_at
```

---

## Service Layer Expansion

### New Service Classes (20+ services)

#### 1. **BrandService** (Phase 1)
```php
- createBrand(array $data): Brand
- updateBrand(Brand $brand, array $data): Brand
- deleteBrand(Brand $brand): bool
- getBrands(array $filters = []): Collection
- getBrandBySlug(string $slug): ?Brand
- getProductsByBrand(Brand $brand): Collection
```

#### 2. **ProductOptionService** (Phase 1)
```php
- createOption(Product $product, array $data): ProductOption
- updateOption(ProductOption $option, array $data): ProductOption
- deleteOption(ProductOption $option): bool
- createTemplate(array $data): ProductOptionTemplate
- applyTemplate(Product $product, ProductOptionTemplate $template): void
- syncOptionValues(ProductOption $option, array $values): void
```

#### 3. **DigitalProductService** (Phase 1)
```php
- attachFile(Product $product, UploadedFile $file): DigitalProductFile
- generateCodes(Product $product, int $quantity): Collection
- assignCodeToOrder(OrderItem $item): ?string
- getAvailableCodes(Product $product): Collection
- markCodeAsUsed(string $code): void
```

#### 4. **CustomerGroupService** (Phase 2)
```php
- createGroup(array $data): CustomerGroup
- addCustomersToGroup(CustomerGroup $group, array $customerIds): void
- removeCustomersFromGroup(CustomerGroup $group, array $customerIds): void
- getGroupCustomers(CustomerGroup $group): Collection
- applyGroupDiscount(CustomerGroup $group, Discount $discount): void
```

#### 5. **CustomerWalletService** (Phase 2)
```php
- deposit(Customer $customer, float $amount, string $description): Transaction
- withdraw(Customer $customer, float $amount, string $description): Transaction
- getBalance(Customer $customer): float
- getTransactions(Customer $customer, array $filters = []): Collection
- canAfford(Customer $customer, float $amount): bool
```

#### 6. **AffiliateService** (Phase 2)
```php
- createAffiliate(Customer $customer, array $data): Affiliate
- generateAffiliateLink(Affiliate $affiliate, ?Product $product = null): AffiliateLink
- trackClick(AffiliateLink $link): void
- calculateCommission(Order $order, Affiliate $affiliate): float
- payCommission(Affiliate $affiliate, float $amount): Transaction
```

#### 7. **LoyaltyPointService** (Phase 2)
```php
- awardPoints(Customer $customer, int $points, string $reason): void
- deductPoints(Customer $customer, int $points, string $reason): void
- getPointsBalance(Customer $customer): int
- calculateOrderPoints(Order $order): int
- redeemPointsForDiscount(Customer $customer, int $points): Discount
```

#### 8. **InvoiceService** (Phase 3)
```php
- generateInvoice(Order $order): OrderInvoice
- sendInvoiceEmail(OrderInvoice $invoice): bool
- downloadInvoicePdf(OrderInvoice $invoice): string
- updateInvoiceStatus(OrderInvoice $invoice, string $status): void
- getInvoiceNumber(): string
```

#### 9. **OrderAssignmentService** (Phase 3)
```php
- createRule(array $data): OrderAssignmentRule
- assignOrderToEmployee(Order $order, Employee $employee): OrderAssignment
- autoAssignOrder(Order $order): ?OrderAssignment
- getEmployeeOrders(Employee $employee, array $filters = []): Collection
- reassignOrder(Order $order, Employee $newEmployee): OrderAssignment
```

#### 10. **ShipmentService** (Phase 4)
```php
- createShipment(Order $order, array $data): Shipment
- updateTracking(Shipment $shipment, array $trackingData): void
- cancelShipment(Shipment $shipment): bool
- getTrackingHistory(Shipment $shipment): Collection
- updateStatus(Shipment $shipment, string $status): void
- createShipmentLabel(Shipment $shipment): string
```

#### 11. **ShippingZoneService** (Phase 4)
```php
- createZone(array $data): ShippingZone
- addPostalCodes(ShippingZone $zone, array $postalCodes): void
- removePostalCodes(ShippingZone $zone, array $postalCodes): void
- findZoneByAddress(Address $address): ?ShippingZone
- calculateZoneRate(ShippingZone $zone, Cart $cart): float
```

#### 12. **BranchService** (Phase 6)
```php
- createBranch(array $data): Branch
- updateBranch(Branch $branch, array $data): Branch
- allocateStock(Branch $branch, Product $product, int $quantity): BranchAllocation
- transferStock(Branch $from, Branch $to, Product $product, int $quantity): BranchStockTransfer
- getBranchInventory(Branch $branch): Collection
- findNearestBranch(Address $address): ?Branch
```

#### 13. **TransactionService** (Phase 5)
```php
- recordTransaction(Order $order, array $data): Transaction
- recordRefund(Order $order, float $amount): Transaction
- getTransactionsByDate(Carbon $from, Carbon $to): Collection
- calculateFees(Transaction $transaction): float
- reconcileTransactions(Collection $transactions): array
```

#### 14. **WebhookService** (Phase 7)
```php
- subscribe(array $data): Webhook
- unsubscribe(Webhook $webhook): bool
- updateWebhook(Webhook $webhook, array $data): Webhook
- deliverWebhook(Webhook $webhook, string $event, array $payload): WebhookDelivery
- verifySignature(Request $request): bool
- getAvailableEvents(): Collection
- filterEventsByRule(string $event, array $payload, string $rule): bool
```

---

## API Endpoint Mapping

### Complete Endpoint Inventory

**Total Salla Endpoints**: 193
**Already Implemented**: ~15
**To Implement**: ~178

### Endpoint Structure

All API endpoints follow Salla's convention:
- **Base URL**: `/api/admin/v2/`
- **Authentication**: Bearer token (OAuth 2.0)
- **Response Format**: JSON with standardized structure
- **Pagination**: Query params `page` and `per_page`
- **Filtering**: Query params for status, dates, keywords

### Standard Response Format
```json
{
  "status": 200,
  "success": true,
  "data": { ... },
  "pagination": {
    "count": 10,
    "total": 100,
    "perPage": 10,
    "currentPage": 1,
    "totalPages": 10,
    "links": {
      "next": "...",
      "previous": "..."
    }
  }
}
```

### Endpoint Categories with Completion Status

| Category | Endpoints | Status | Phase |
|----------|-----------|--------|-------|
| **Merchant** | 2 | ⚠️ Partial | 7 |
| **Products** | 8 | ✅ Basic | 1 |
| **Product Variants** | 4 | ⚠️ Partial | 1 |
| **Product Options** | 4 | ❌ Missing | 1 |
| **Product Option Templates** | 4 | ❌ Missing | 1 |
| **Product Tags** | 4 | ❌ Missing | 1 |
| **Product Images** | 2 | ❌ Missing | 1 |
| **Digital Products** | 3 | ❌ Missing | 1 |
| **Categories** | 8 | ✅ Complete | - |
| **Brands** | 5 | ❌ Missing | 1 |
| **Reviews** | 3 | ❌ Missing | 3 |
| **Special Offers** | 4 | ❌ Missing | 3 |
| **Customers** | 8 | ✅ Basic | 2 |
| **Customer Groups** | 5 | ❌ Missing | 2 |
| **Customer Wallet** | 3 | ❌ Missing | 2 |
| **Affiliates** | 6 | ❌ Missing | 2 |
| **Loyalty Points** | 3 | ❌ Missing | 2 |
| **Employees** | 2 | ❌ Missing | 2 |
| **Orders** | 8 | ✅ Basic | 3 |
| **Order Items** | 4 | ❌ Missing | 3 |
| **Order Statuses** | 7 | ❌ Missing | 3 |
| **Order Histories** | 2 | ❌ Missing | 3 |
| **Order Invoices** | 5 | ❌ Missing | 3 |
| **Order Assignments** | 4 | ❌ Missing | 3 |
| **Order Reservations** | 1 | ⚠️ Partial | - |
| **Shipments** | 7 | ❌ Missing | 4 |
| **Shipping Companies** | 5 | ❌ Missing | 4 |
| **Shipping Zones** | 5 | ❌ Missing | 4 |
| **Shipping Routes** | 5 | ❌ Missing | 4 |
| **Branch Delivery Zones** | 5 | ❌ Missing | 4 |
| **Payments** | 3 | ⚠️ Partial | 5 |
| **Transactions** | 2 | ❌ Missing | 5 |
| **Coupons** | 4 | ⚠️ Partial | 5 |
| **Taxes** | 3 | ❌ Missing | 5 |
| **Branches** | 5 | ❌ Missing | 6 |
| **Branch Allocations** | 6 | ❌ Missing | 6 |
| **Abandoned Carts** | 2 | ⚠️ Detection | 6 |
| **Countries/Cities** | 3 | ❌ Missing | 6 |
| **Webhooks** | 5 | ⚠️ Partial | 7 |
| **Currencies** | 3 | ⚠️ Basic | 6 |
| **Languages** | 3 | ❌ Missing | 6 |
| **DNS Records** | 3 | ❌ Missing | - |
| **Custom URLs** | 2 | ❌ Missing | - |
| **Settings** | 2 | ✅ Complete | - |

**Legend**:
- ✅ Complete - Fully implemented
- ⚠️ Partial - Basic functionality exists, needs extension
- ❌ Missing - Not implemented

---

## Models & Relationships

### Model Relationship Map

#### Product Ecosystem
```
Brand
  ├── hasMany Products

Product
  ├── belongsTo Store
  ├── belongsTo Category
  ├── belongsTo Brand
  ├── hasMany Variants (ProductVariant)
  ├── hasMany Options (ProductOption)
  ├── hasMany Images (ProductImage)
  ├── belongsToMany Tags (ProductTag)
  ├── hasMany DigitalFiles (DigitalProductFile)
  ├── hasMany Reviews
  ├── hasMany OrderItems
  └── hasMany CartItems

ProductOption
  ├── belongsTo Product
  ├── hasMany OptionValues (ProductOptionValue)
  └── belongsTo Template (ProductOptionTemplate)

ProductTag
  └── belongsToMany Products

ProductImage
  ├── belongsTo Product
  └── belongsTo Variant (optional)
```

#### Customer Ecosystem
```
Customer
  ├── belongsTo Store
  ├── belongsTo User
  ├── hasMany Addresses
  ├── hasMany Orders
  ├── hasOne Wallet (CustomerWallet)
  ├── belongsToMany Groups (CustomerGroup)
  ├── hasOne Affiliate
  ├── hasOne LoyaltyPoints
  └── hasMany Reviews

CustomerGroup
  ├── belongsTo Store
  └── belongsToMany Customers

CustomerWallet
  ├── belongsTo Customer
  └── hasMany Transactions (CustomerWalletTransaction)

Affiliate
  ├── belongsTo Customer
  ├── hasMany Links (AffiliateLink)
  └── hasMany Commissions (AffiliateCommission)

LoyaltyPoints
  ├── belongsTo Customer
  └── hasMany Transactions (LoyaltyPointTransaction)
```

#### Order Ecosystem
```
Order
  ├── belongsTo Store
  ├── belongsTo Customer
  ├── belongsTo Status (OrderStatus)
  ├── hasMany Items (OrderItem)
  ├── hasMany Histories (OrderHistory)
  ├── hasMany Invoices (OrderInvoice)
  ├── hasMany Shipments
  └── hasOne Assignment (OrderAssignment)

OrderStatus
  ├── belongsTo Store
  └── hasMany Orders

OrderInvoice
  ├── belongsTo Order
  └── belongsTo CreatedBy (Employee)

OrderAssignment
  ├── belongsTo Order
  ├── belongsTo Employee
  └── belongsTo Rule (OrderAssignmentRule)

Shipment
  ├── belongsTo Order
  ├── belongsTo ShippingCompany
  ├── hasMany Items (ShipmentItem)
  └── hasMany TrackingEvents (ShipmentTracking)
```

#### Shipping Ecosystem
```
ShippingCompany
  ├── belongsTo Store
  ├── hasMany Shipments
  └── hasOne Credentials (ShippingCompanyCredentials)

ShippingZone
  ├── belongsTo Store
  ├── hasMany PostalCodes (ShippingZonePostalCode)
  └── hasMany Routes (ShippingRoute)

ShippingRoute
  ├── belongsTo Zone (ShippingZone)
  └── hasMany Prices (ShippingRoutePrice)

Branch
  ├── belongsTo Store
  ├── hasMany Allocations (BranchAllocation)
  ├── hasMany StockTransfers
  └── hasMany DeliveryZones (BranchDeliveryZone)
```

---

## Integration with Existing Code

### 1. Extend Existing Models

#### Product Model Extensions
```php
// app/Plugins/vodo-commerce/Models/Product.php

// Add relationships
public function brand(): BelongsTo
{
    return $this->belongsTo(Brand::class);
}

public function options(): HasMany
{
    return $this->hasMany(ProductOption::class);
}

public function images(): HasMany
{
    return $this->hasMany(ProductImage::class)->orderBy('position');
}

public function tags(): BelongsToMany
{
    return $this->belongsToMany(ProductTag::class, 'commerce_product_tag_pivot');
}

public function digitalFiles(): HasMany
{
    return $this->hasMany(DigitalProductFile::class);
}

public function reviews(): HasMany
{
    return $this->hasMany(Review::class);
}

// Add scopes
public function scopeByBrand($query, $brandId)
{
    return $query->where('brand_id', $brandId);
}

public function scopeWithTags($query, array $tagIds)
{
    return $query->whereHas('tags', fn($q) => $q->whereIn('id', $tagIds));
}

public function scopeDigital($query)
{
    return $query->where('is_downloadable', true);
}
```

#### Customer Model Extensions
```php
// app/Plugins/vodo-commerce/Models/Customer.php

public function wallet(): HasOne
{
    return $this->hasOne(CustomerWallet::class);
}

public function groups(): BelongsToMany
{
    return $this->belongsToMany(CustomerGroup::class, 'commerce_customer_group_memberships');
}

public function affiliate(): HasOne
{
    return $this->hasOne(Affiliate::class);
}

public function loyaltyPoints(): HasOne
{
    return $this->hasOne(LoyaltyPoints::class);
}

public function reviews(): HasMany
{
    return $this->hasMany(Review::class);
}

// Add methods
public function ban(string $reason = null): void
{
    $this->update(['is_banned' => true, 'ban_reason' => $reason]);
}

public function unban(): void
{
    $this->update(['is_banned' => false, 'ban_reason' => null]);
}

public function isBanned(): bool
{
    return $this->is_banned;
}
```

#### Order Model Extensions
```php
// app/Plugins/vodo-commerce/Models/Order.php

public function customStatus(): BelongsTo
{
    return $this->belongsTo(OrderStatus::class, 'order_status_id');
}

public function histories(): HasMany
{
    return $this->hasMany(OrderHistory::class)->orderBy('created_at', 'desc');
}

public function invoices(): HasMany
{
    return $this->hasMany(OrderInvoice::class);
}

public function shipments(): HasMany
{
    return $this->hasMany(Shipment::class);
}

public function assignment(): HasOne
{
    return $this->hasOne(OrderAssignment::class);
}

// Add methods
public function addHistory(string $event, array $data = []): OrderHistory
{
    return $this->histories()->create([
        'store_id' => $this->store_id,
        'event' => $event,
        'data' => $data,
        'created_by' => auth()->id(),
    ]);
}

public function generateInvoice(): OrderInvoice
{
    return app(InvoiceService::class)->generateInvoice($this);
}
```

### 2. Extend OAuth Scopes

```php
// app/Plugins/vodo-commerce/Auth/CommerceScopes.php

// Add new scopes for Salla API compatibility
public const SCOPE_BRANDS_READ = 'brands.read';
public const SCOPE_BRANDS_WRITE = 'brands.read_write';

public const SCOPE_REVIEWS_READ = 'reviews.read';
public const SCOPE_REVIEWS_WRITE = 'reviews.read_write';

public const SCOPE_SHIPMENTS_READ = 'shipments.read';
public const SCOPE_SHIPMENTS_WRITE = 'shipments.read_write';

public const SCOPE_AFFILIATES_READ = 'affiliates.read';
public const SCOPE_AFFILIATES_WRITE = 'affiliates.read_write';

// ... add all 47 categories
```

### 3. Extend Commerce Events

```php
// app/Plugins/vodo-commerce/Events/CommerceEvents.php

// Brand events
public const BRAND_CREATED = 'commerce.brand.created';
public const BRAND_UPDATED = 'commerce.brand.updated';
public const BRAND_DELETED = 'commerce.brand.deleted';

// Review events
public const REVIEW_CREATED = 'commerce.review.created';
public const REVIEW_APPROVED = 'commerce.review.approved';
public const REVIEW_REJECTED = 'commerce.review.rejected';

// Shipment events
public const SHIPMENT_CREATED = 'commerce.shipment.created';
public const SHIPMENT_TRACKING_UPDATED = 'commerce.shipment.tracking_updated';
public const SHIPMENT_DELIVERED = 'commerce.shipment.delivered';

// Affiliate events
public const AFFILIATE_CREATED = 'commerce.affiliate.created';
public const AFFILIATE_COMMISSION_EARNED = 'commerce.affiliate.commission_earned';
public const AFFILIATE_PAID = 'commerce.affiliate.paid';

// ... add all events
```

### 4. Register New Entities

```php
// In VodoCommercePlugin::registerEntities()

// Brand Entity
$this->entityRegistry->register('commerce_brand', [
    'table_name' => 'commerce_brands',
    'model_class' => \VodoCommerce\Models\Brand::class,
    'search_columns' => ['name', 'slug'],
    'labels' => ['singular' => 'Brand', 'plural' => 'Brands'],
    'icon' => 'award',
    'supports' => ['title', 'content', 'thumbnail'],
    'fields' => [
        'name' => ['type' => 'string', 'required' => true],
        'slug' => ['type' => 'slug', 'required' => true],
        'logo' => ['type' => 'image'],
        'website' => ['type' => 'url'],
        'is_active' => ['type' => 'boolean', 'default' => true],
    ],
], self::SLUG);

// ... register all 30+ new entities
```

### 5. Leverage Plugin Registries

```php
// Example: Register shipping company via plugin
// In a separate plugin: salla-shipping-integration

use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Contracts\ShippingCarrierContract;

class SallaShippingIntegration implements ShippingCarrierContract
{
    public function getName(): string
    {
        return 'Salla Shipping';
    }

    public function getRates(Address $destination, Cart $cart): array
    {
        // Call Salla shipping API
        return [
            ['name' => 'Standard', 'price' => 10.00],
            ['name' => 'Express', 'price' => 25.00],
        ];
    }

    public function createShipment(Order $order, array $data): array
    {
        // Create shipment via Salla API
        return ['tracking_number' => 'ABC123'];
    }

    public function trackShipment(string $trackingNumber): array
    {
        // Get tracking info from Salla
        return ['status' => 'in_transit', 'location' => 'Dubai'];
    }
}

// In plugin boot()
app(ShippingCarrierRegistry::class)->register('salla', new SallaShippingIntegration());
```

---

## Testing Strategy

### Test Coverage Goals

| Category | Target Coverage | Test Types |
|----------|----------------|------------|
| **Services** | 85%+ | Unit Tests |
| **API Controllers** | 90%+ | Feature Tests |
| **Models** | 80%+ | Unit Tests |
| **Workflows** | 95%+ | Feature Tests |

### Test Structure

```
app/Plugins/vodo-commerce/Tests/
├── Feature/
│   ├── Api/
│   │   ├── V2/
│   │   │   ├── ProductApiTest.php
│   │   │   ├── BrandApiTest.php
│   │   │   ├── CustomerApiTest.php
│   │   │   ├── OrderApiTest.php
│   │   │   ├── ShipmentApiTest.php
│   │   │   └── WebhookApiTest.php
│   ├── Workflows/
│   │   ├── CheckoutWorkflowTest.php
│   │   ├── OrderFulfillmentWorkflowTest.php
│   │   ├── AffiliateCommissionWorkflowTest.php
│   │   └── InventoryReservationWorkflowTest.php
│   └── Integration/
│       ├── ShippingIntegrationTest.php
│       ├── PaymentIntegrationTest.php
│       └── WebhookDeliveryTest.php
└── Unit/
    ├── Services/
    │   ├── BrandServiceTest.php
    │   ├── CustomerWalletServiceTest.php
    │   ├── InvoiceServiceTest.php
    │   └── ... (one per service)
    └── Models/
        ├── BrandTest.php
        ├── AffiliateTest.php
        └── ... (one per model)
```

### Key Test Scenarios

#### 1. Product Management Tests
```php
// Tests/Feature/Api/V2/ProductApiTest.php
test('can create product with options');
test('can upload product images');
test('can attach digital files to product');
test('can assign product to brand');
test('can add tags to product');
test('product options generate correct variants');
test('can apply option template to product');
```

#### 2. Customer Wallet Tests
```php
// Tests/Unit/Services/CustomerWalletServiceTest.php
test('can deposit to wallet');
test('can withdraw from wallet');
test('cannot withdraw more than balance');
test('wallet transactions are recorded');
test('wallet balance is accurate');
```

#### 3. Order Workflow Tests
```php
// Tests/Feature/Workflows/OrderFulfillmentWorkflowTest.php
test('order creates invoice automatically');
test('order assigns to employee based on rules');
test('order creates shipment on fulfillment');
test('order history tracks all status changes');
test('order updates customer statistics');
```

#### 4. Webhook Tests
```php
// Tests/Feature/Api/V2/WebhookApiTest.php
test('can subscribe to webhook events');
test('webhook filters events by conditions');
test('webhook delivers with correct signature');
test('webhook retries on failure');
test('webhook supports v2 format');
```

### Test Data Factories

```php
// database/factories/BrandFactory.php
BrandFactory::new()->create();

// database/factories/ProductOptionFactory.php
ProductOptionFactory::new()->withValues(['Red', 'Blue'])->create();

// database/factories/AffiliateFactory.php
AffiliateFactory::new()->withCustomer()->create();

// database/factories/ShipmentFactory.php
ShipmentFactory::new()->forOrder($order)->create();
```

---

## Deployment Considerations

### Pre-Deployment Checklist

#### Database
- [ ] Run all migrations in staging environment
- [ ] Verify indexes are created correctly
- [ ] Test migration rollbacks
- [ ] Seed test data for all new tables
- [ ] Verify foreign key constraints

#### API
- [ ] Generate OpenAPI specification
- [ ] Test all 193 endpoints manually
- [ ] Verify OAuth scopes work correctly
- [ ] Test pagination on all list endpoints
- [ ] Verify error responses follow Salla format

#### Performance
- [ ] Add database indexes for common queries
- [ ] Enable query caching for product/category listings
- [ ] Implement rate limiting per OAuth scope
- [ ] Optimize N+1 queries with eager loading
- [ ] Cache computed values (wallet balance, loyalty points)

#### Security
- [ ] Audit all API endpoints for authorization
- [ ] Verify webhook signature verification works
- [ ] Test OAuth token expiration and refresh
- [ ] Validate all input using Form Requests
- [ ] Scan for SQL injection vulnerabilities

#### Testing
- [ ] Achieve 85%+ test coverage
- [ ] Run full test suite (expect 500+ tests)
- [ ] Load test API endpoints (1000 req/min)
- [ ] Test webhook delivery under load
- [ ] Test concurrent order creation

#### Documentation
- [ ] Generate API documentation (Swagger/ReDoc)
- [ ] Document all webhook events
- [ ] Create developer onboarding guide
- [ ] Document OAuth flow
- [ ] Create example API requests for all endpoints

### Deployment Strategy

#### Phase 1-2 (Product & Customer Features)
- Deploy to staging
- Run acceptance tests
- Deploy to production during low-traffic window
- Monitor error logs for 48 hours
- Roll back if error rate > 1%

#### Phase 3-4 (Orders & Shipping)
- Feature flag critical order changes
- Deploy to 10% of stores (canary)
- Monitor order completion rates
- Gradual rollout to 50%, then 100%

#### Phase 5-7 (Financial, Multi-branch, Webhooks)
- Deploy financial features with extra monitoring
- Test webhook delivery with high-volume stores
- Verify branch allocation doesn't cause stock issues

### Monitoring & Alerts

#### Key Metrics
- API response times (target: < 200ms p95)
- Order completion rate (target: > 98%)
- Webhook delivery success rate (target: > 95%)
- Payment success rate (target: > 97%)
- Database query time (target: < 50ms p95)

#### Alerts
- API error rate > 5% for 5 minutes
- Order failures > 10 in 10 minutes
- Webhook delivery failures > 20% for 15 minutes
- Database connection pool exhaustion
- Inventory reservation timeouts

---

## Summary & Recommendations

### Implementation Complexity

| Phase | Complexity | Risk | Dependencies |
|-------|-----------|------|--------------|
| Phase 1 | Medium | Low | None |
| Phase 2 | Medium | Low | Phase 1 |
| Phase 3 | High | Medium | Phase 1, 2 |
| Phase 4 | High | High | Phase 3 |
| Phase 5 | Medium | Medium | Phase 3 |
| Phase 6 | Medium | Low | Phase 4 |
| Phase 7 | Low | Low | All phases |

### Estimated Effort

| Phase | Lines of Code | Days | Team Size |
|-------|--------------|------|-----------|
| Phase 1 | ~8,000 | 15-20 | 2 developers |
| Phase 2 | ~7,000 | 15-20 | 2 developers |
| Phase 3 | ~9,000 | 15-20 | 2 developers |
| Phase 4 | ~10,000 | 15-20 | 2 developers |
| Phase 5 | ~4,000 | 7-10 | 1 developer |
| Phase 6 | ~5,000 | 10-15 | 1 developer |
| Phase 7 | ~2,000 | 5-7 | 1 developer |
| **TOTAL** | **~45,000** | **82-112 days** | **2-3 developers** |

### Success Criteria

**Phase 1 Complete When:**
- ✅ 18 product API endpoints working
- ✅ Product options/variants fully functional
- ✅ Brand management operational
- ✅ Digital products can be sold
- ✅ 90%+ test coverage

**Phase 2 Complete When:**
- ✅ 19 customer API endpoints working
- ✅ Customer wallet transactions functional
- ✅ Affiliate program operational
- ✅ Loyalty points system working
- ✅ 85%+ test coverage

**Phase 3 Complete When:**
- ✅ 29 order API endpoints working
- ✅ Invoice generation functional
- ✅ Order assignments working
- ✅ Reviews system operational
- ✅ 90%+ test coverage

**Phase 4 Complete When:**
- ✅ 27 shipping API endpoints working
- ✅ Shipment tracking functional
- ✅ Shipping companies integrated
- ✅ Zone-based shipping working
- ✅ 85%+ test coverage

**Phase 5 Complete When:**
- ✅ 12 financial API endpoints working
- ✅ Transaction tracking functional
- ✅ Advanced tax calculation working
- ✅ 90%+ test coverage

**Phase 6 Complete When:**
- ✅ 22 multi-branch API endpoints working
- ✅ Branch allocation functional
- ✅ Multi-currency support working
- ✅ 85%+ test coverage

**Phase 7 Complete When:**
- ✅ 5 webhook API endpoints working
- ✅ Webhook filtering operational
- ✅ Event versioning working
- ✅ 95%+ webhook delivery rate
- ✅ 90%+ test coverage

### Risk Mitigation

#### High-Risk Areas
1. **Shipment Integration** - External API dependencies
   - Mitigation: Implement circuit breaker, fallback to manual tracking

2. **Multi-branch Stock** - Race conditions in inventory
   - Mitigation: Use database locks, atomic operations

3. **Webhook Delivery** - High volume can cause delays
   - Mitigation: Queue webhooks, implement retry logic

4. **Payment Processing** - Financial data integrity
   - Mitigation: Use idempotency keys, double-entry accounting

5. **Performance** - API slowdown with 193 endpoints
   - Mitigation: Aggressive caching, query optimization, CDN

### Next Steps

1. **Review & Approve Plan** - Stakeholder sign-off (1 day)
2. **Setup Development Environment** - Branch, CI/CD (2 days)
3. **Create Factories & Seeders** - Test data (3 days)
4. **Begin Phase 1 Implementation** - Products (15-20 days)
5. **Parallel: Setup Monitoring** - Metrics, logging (ongoing)

---

## Appendix

### A. Salla API Endpoint Complete List

See `salla/APIs/` directory for full OpenAPI specifications for all 193 endpoints across 47 categories.

### B. Database ERD

A complete Entity Relationship Diagram showing all 75+ tables (15 existing + 60 new) will be generated after Phase 1 migration completion.

### C. OAuth Scope Mapping

| Salla Scope | Vodo Commerce Scope | Endpoints |
|-------------|---------------------|-----------|
| `products.read` | `commerce.products.view` | GET /products, /products/{id} |
| `products.read_write` | `commerce.products.manage` | POST/PUT/DELETE /products |
| `orders.read` | `commerce.orders.view` | GET /orders, /orders/{id} |
| `orders.read_write` | `commerce.orders.manage` | POST/PUT /orders |
| ... (see CommerceScopes.php for full list) | | |

### D. Code Standards

All code must follow:
- PSR-12 coding standards
- Laravel best practices
- Strict PHP 8.4 type declarations
- Eloquent ORM (no raw queries)
- Service layer for business logic
- Form Requests for validation
- API Resources for responses
- Event-driven architecture via hooks

---

**End of Implementation Plan**

*This plan provides a complete roadmap to transform vodo-commerce into a Salla API clone. All work remains within the plugin boundaries, leveraging the existing Laravel 12 architecture and plugin system.*
