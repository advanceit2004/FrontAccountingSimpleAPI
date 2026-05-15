# FrontAccounting Simple API Roadmap

This roadmap tracks the next stages for the new Slim 4 / PHP 8.4 JSON API for FrontAccounting 2.4.20.

## Current validated baseline

- Branch: `new-slim4-api`
- Latest validated image: `advanceit2004/frontaccounting:2.4.20-php8.4-api-slim4.5`
- Runtime target: FrontAccounting `2.4.20`, PHP `8.4.21`, Slim 4
- API base path: `/modules/api/public/index.php/v1`

## Completed core API workflows

The API currently covers these validated workflows:

- Authentication with Bearer token
- Health check
- Company discovery
- Items and item categories
- Customers and suppliers
- Currencies and exchange rates
- Bank accounts
- GL accounts
- Tax types and tax groups
- Locations
- Journal entries
- Stock adjustments
- Customer payments
- Supplier payments
- Sales order → sales delivery → sales invoice
- Purchase order → purchase receipt → supplier invoice

The smoke test suite validates the core read/write workflows against a running FrontAccounting container and fresh image containers.

---

## Stage 8 — Production readiness

### 8.1 Deployment and security checklist

- [ ] Decide production image tag to deploy, currently recommended: `2.4.20-php8.4-api-slim4.5`
- [ ] Set `FA_API_JWT_SECRET` to a long random production secret
- [ ] Set `FA_API_TOKEN_TTL`, for example `3600`
- [ ] Review API user permissions and create least-privilege API roles
- [ ] Decide whether to keep, disable, or remove the `api_test` smoke-test user
- [ ] Switch Docker Compose `FA_IMAGE_TAG` to the latest tested image
- [ ] Restart stack and rerun `tests/api/smoke.sh`
- [ ] Document production environment variables

### 8.2 OpenAPI and documentation completion

- [ ] Expand `docs/openapi.yaml` with all implemented endpoints
- [ ] Add request/response schemas for create/update endpoints
- [ ] Add error response examples
- [ ] Add authentication examples
- [ ] Add business workflow examples to README
- [ ] Document smoke-test prerequisites and required test data
- [ ] Document FrontAccounting permission area requirements per endpoint

### 8.3 CI/CD automation

- [ ] Add GitHub Actions or equivalent CI pipeline
- [ ] Run Composer install
- [ ] Run PHP syntax lint over `app/` and `public/`
- [ ] Build Docker image
- [ ] Start a test FrontAccounting stack
- [ ] Run `tests/api/smoke.sh`
- [ ] Publish Docker image only after tests pass
- [ ] Add release artifact/version summary

### 8.4 Posted document detail endpoints

Add detail/read endpoints for operational follow-up after posting documents.

- [ ] `GET /v1/sales/deliveries/{id}`
- [ ] `GET /v1/sales/invoices/{id}` with line details
- [ ] `GET /v1/purchase/receipts/{id}`
- [ ] `GET /v1/purchase/invoices/{id}` with line details
- [ ] Add smoke-test coverage for each detail endpoint
- [ ] Add OpenAPI schemas for detail responses

### 8.5 Payment allocation and reconciliation

- [ ] Investigate FrontAccounting allocation internals for customer payments
- [ ] Investigate supplier payment allocations against supplier invoices
- [ ] Add endpoints only after accounting behavior is validated
- [ ] Add smoke tests for allocation scenarios
- [ ] Document safe usage patterns and limitations

---

## Stage 9 — Safer accounting lifecycle operations

These operations should be added cautiously because they affect accounting history and audit trails.

- [ ] Void/cancel sales delivery
- [ ] Void/cancel sales invoice
- [ ] Void/cancel purchase receipt
- [ ] Void/cancel supplier invoice
- [ ] Credit note workflows
- [ ] Supplier credit note workflows
- [ ] Add permission checks and audit documentation for each operation
- [ ] Add tests proving GL, stock, and allocation side effects are correct

---

## Stage 10 — Production quality and integrations

- [ ] Add structured API logging
- [ ] Add request IDs/correlation IDs
- [ ] Add rate limiting or reverse-proxy guidance
- [ ] Add backup/restore guidance before destructive workflows
- [ ] Add SDK/client examples
- [ ] Add Postman/Insomnia collection if useful
- [ ] Add upgrade notes for future FrontAccounting versions

---

## GitHub Issues note

GitHub Issues are currently disabled for this repository. When Issues are enabled, this roadmap should be split into individual issues, grouped by milestone:

1. Stage 8 — Production readiness
2. Stage 9 — Accounting lifecycle operations
3. Stage 10 — Integrations and operational excellence

Suggested initial issues:

- Production deployment and security checklist
- Complete OpenAPI and README documentation
- Add CI/CD smoke-test pipeline
- Add posted document detail endpoints
- Add payment allocation workflows
- Add release/versioning process
