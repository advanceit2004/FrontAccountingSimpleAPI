# FrontAccounting Simple API Roadmap

This roadmap tracks the next stages for the new Slim 4 / PHP 8.4 JSON API for FrontAccounting 2.4.20.

## Current validated baseline

- Branch: `new-slim4-api`
- Latest validated image: `advanceit2004/frontaccounting:2.4.20-php8.4-api-slim4.10`
- Latest completed implementation commit: `Add safe lifecycle void APIs`
- Runtime target: FrontAccounting `2.4.20`, PHP `8.4.21`, Slim 4
- API base path: `/modules/api/public/index.php/v1`

## Original plan recap

The work originally started as a PHP 8.4 compatibility update for the legacy Slim 2 API. After review, the direction changed to a new API instead of a backward-compatible patch.

The final product goal is:

- A clean `/v1` JSON API
- Slim 4 and PSR-7 request/response handling
- Bearer token authentication backed by FrontAccounting login
- FrontAccounting 2.4.20 integration through isolated bootstrap/service layers
- Thorough smoke testing in the VPS Docker environment
- Docker images rebuilt only after tests pass
- Production readiness through docs, CI, security settings, and release process

## Completed API foundation

- [x] Slim 4 application structure
- [x] Composer modernization from Slim 2 to Slim 4
- [x] FrontAccounting bootstrap isolation
- [x] JSON success/error response envelope
- [x] Bearer token login flow
- [x] FrontAccounting role/permission checks on protected routes
- [x] PHP 8.4 runtime validation in Docker
- [x] Smoke test suite at `tests/api/smoke.sh`
- [x] Fresh-container validation after image builds

## Completed core API workflows

The API currently covers these validated workflows:

- [x] Authentication with Bearer token
- [x] Health check
- [x] Company discovery
- [x] Items and item categories
- [x] Customers and suppliers
- [x] Currencies and exchange rates
- [x] Bank accounts
- [x] GL accounts
- [x] Tax types and tax groups
- [x] Locations
- [x] Journal entries
- [x] Stock adjustments
- [x] Customer payments
- [x] Supplier payments
- [x] Sales order → sales delivery → sales invoice
- [x] Purchase order → purchase receipt → supplier invoice
- [x] Posted document detail reads for sales deliveries, sales invoices, purchase receipts, and supplier invoices
- [x] Customer branch management APIs
- [x] Setup reference endpoints for sales areas, salesmen, shippers, payment terms, item units, item tax types, dimensions, and fiscal years
- [x] Setup validation for customer branches, suppliers, item categories, and items

The smoke test suite validates the core read/write workflows against both the running FrontAccounting container and fresh containers created from rebuilt images.

---

## Stage 8 — Production readiness

GitHub parent issue: [#1 Roadmap: Stage 8 — production readiness and future API updates](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/1)

### 8.1 Deployment and security checklist

GitHub issue: [#3 Stage 8: Production deployment and security checklist](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/3)

- [ ] Decide production image tag to deploy, currently recommended: `2.4.20-php8.4-api-slim4.7`
- [ ] Set `FA_API_JWT_SECRET` to a long random production secret
- [ ] Set `FA_API_TOKEN_TTL`, for example `3600`
- [ ] Review API user permissions and create least-privilege API roles
- [ ] Decide whether to keep, disable, or remove the `api_test` smoke-test user
- [ ] Switch Docker Compose `FA_IMAGE_TAG` to the latest tested image
- [ ] Restart stack and rerun `tests/api/smoke.sh`
- [ ] Document production environment variables

### 8.2 OpenAPI and documentation completion

GitHub issue: [#2 Stage 8: Complete OpenAPI and README documentation](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/2)

- [ ] Expand `docs/openapi.yaml` with all implemented endpoints
- [ ] Add request/response schemas for create/update endpoints
- [ ] Add detail response schemas for posted documents
- [ ] Add error response examples
- [ ] Add authentication examples
- [ ] Add business workflow examples to README
- [ ] Document smoke-test prerequisites and required test data
- [ ] Document FrontAccounting permission area requirements per endpoint

### 8.3 CI/CD automation

GitHub issue: [#5 Stage 8: Add CI/CD smoke-test pipeline](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/5)

- [ ] Add GitHub Actions or equivalent CI pipeline
- [ ] Run Composer install
- [ ] Run PHP syntax lint over `app/` and `public/`
- [ ] Build Docker image
- [ ] Start a test FrontAccounting stack
- [ ] Run `tests/api/smoke.sh`
- [ ] Publish Docker image only after tests pass
- [ ] Add release artifact/version summary

### 8.4 Posted document detail endpoints

GitHub issue: [#6 Stage 8: Add posted document detail endpoints](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/6) — **completed**

- [x] `GET /v1/sales/deliveries/{id}`
- [x] `GET /v1/sales/invoices/{id}` with line details
- [x] `GET /v1/purchase/receipts/{id}`
- [x] `GET /v1/purchase/invoices/{id}` with line details
- [x] Add smoke-test coverage for each detail endpoint
- [ ] Add OpenAPI schemas for detail responses under issue #2

### 8.5 Payment allocation and reconciliation

GitHub issue: [#4 Stage 8: Add payment allocation and reconciliation workflows](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/4) — **completed**

- [x] Investigate FrontAccounting allocation internals for customer payments
- [x] Investigate supplier payment allocations against supplier invoices
- [x] Design safe API payloads for allocation workflows
- [x] Implement customer payment allocation endpoint
- [x] Implement supplier payment allocation endpoint
- [x] Add smoke tests for allocation scenarios
- [ ] Add OpenAPI/README details under issue #2

### 8.6 Release and versioning process

GitHub issue: [#7 Stage 8: Add release and versioning process](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/7)

- [ ] Decide tag naming convention for API releases
- [ ] Add changelog process for API endpoint changes
- [ ] Document Docker image tag policy
- [ ] Create a release checklist
- [ ] Include validated FrontAccounting/PHP/Slim versions in each release
- [ ] Link releases to smoke-test results

### 8.7 Setup/dependency visibility and validation

Completed in commit `cbbc9b5 Add setup dependency APIs and validation`.

- [x] `GET /v1/customers/{id}/branches`
- [x] `GET /v1/customers/{id}/branches/{branchId}`
- [x] `POST /v1/customers/{id}/branches`
- [x] `PUT /v1/customers/{id}/branches/{branchId}`
- [x] `PATCH /v1/customers/{id}/branches/{branchId}/inactive`
- [x] `GET /v1/sales/areas`
- [x] `GET /v1/sales/salesmen`
- [x] `GET /v1/shippers`
- [x] `GET /v1/payment-terms`
- [x] `GET /v1/item-units`
- [x] `GET /v1/item-tax-types`
- [x] `GET /v1/dimensions`
- [x] `GET /v1/fiscal-years`
- [x] Validate customer branch setup dependencies before writes
- [x] Validate supplier setup dependencies before writes
- [x] Validate item/category setup dependencies before writes
- [x] Resolve default customer branch for customer payments when omitted
- [x] Add smoke-test coverage for setup references and customer branch workflows

---

## Stage 9 — Safer accounting lifecycle operations

GitHub issue: [#8 Stage 9: Plan safe accounting lifecycle operations](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/8) — **core void operations completed**

These operations are implemented through FrontAccounting's native `void_transaction()` path, so GL, stock, bank, allocation, tax, audit trail, and `voided` table side effects remain under FrontAccounting's own accounting lifecycle logic.

Implemented endpoints:

- [x] `POST /v1/sales/deliveries/{id}/void`
- [x] `POST /v1/sales/invoices/{id}/void`
- [x] `POST /v1/purchase/receipts/{id}/void`
- [x] `POST /v1/purchase/invoices/{id}/void`
- [x] `POST /v1/customer-payments/{id}/void`
- [x] `POST /v1/supplier-payments/{id}/void`
- [x] `POST /v1/journal-entries/{id}/void`
- [x] `POST /v1/stock-adjustments/{id}/void`
- [x] `POST /v1/sales/credit-notes`
- [x] `POST /v1/sales/credit-notes/{id}/void`
- [x] `POST /v1/purchase/credit-notes`
- [x] `POST /v1/purchase/credit-notes/{id}/void`
- [x] Add `SA_VOIDTRANSACTION` permission checks plus document-specific permissions
- [x] Add smoke tests covering void success for each endpoint
- [x] Customer credit note workflows against sales invoices
- [x] Supplier credit note workflows against supplier invoices
- [ ] Add OpenAPI/README details under issue #2

---

## Stage 10 — Production quality and integrations

GitHub issue: [#9 Stage 10: Operational hardening and integrations](https://github.com/advanceit2004/FrontAccountingSimpleAPI/issues/9)

- [ ] Add structured API logging
- [ ] Add request IDs/correlation IDs
- [ ] Add rate limiting or reverse-proxy guidance
- [ ] Add backup/restore guidance before destructive workflows
- [ ] Add SDK/client examples
- [ ] Add Postman/Insomnia collection if useful
- [ ] Add upgrade notes for future FrontAccounting versions

---

## Recommended next implementation order

1. Deploy/security checklist (#3)
2. OpenAPI and README completion (#2)
3. CI/CD smoke-test pipeline (#5)
4. Release/versioning process (#7)
5. Payment allocation workflows (#4)
6. Safe lifecycle operations (#8)
7. Operational hardening/integrations (#9)
