# FrontAccounting API for PHP 8.4

A new Slim 4 JSON API for FrontAccounting 2.4.x.

This branch is a modern API rewrite for PHP 8.4. It is not backward-compatible with the old Slim 2 API route structure.

## Target runtime

- FrontAccounting 2.4.20
- PHP 8.4
- Apache/mod_php
- Slim 4

## API base

```text
/modules/api/public/index.php/v1
```

## Authentication

```bash
curl -i \
  -H 'Content-Type: application/json' \
  -d '{"company":0,"username":"demouser","password":"password"}' \
  http://localhost:8080/modules/api/public/index.php/v1/auth/login
```

Use the returned token:

```bash
curl -i \
  -H 'Authorization: Bearer <token>' \
  http://localhost:8080/modules/api/public/index.php/v1/items/categories
```

## Endpoints

### System/Auth

```text
GET  /v1/health
POST /v1/auth/login
GET  /v1/companies
```

### Read endpoints

```text
GET /v1/items/categories
GET /v1/items
GET /v1/customers
GET /v1/suppliers
GET /v1/currencies
GET /v1/exchange-rates
GET /v1/bank-accounts
GET /v1/gl/accounts
GET /v1/tax/types
GET /v1/tax/groups
GET /v1/locations
```

### Write endpoints

```text
POST /v1/items/categories
PUT  /v1/items/categories/{id}
POST /v1/items
PUT  /v1/items/{stockId}
POST /v1/customers
PUT  /v1/customers/{id}
POST /v1/suppliers
PUT  /v1/suppliers/{id}
GET  /v1/journal-entries/{id}
POST /v1/journal-entries
POST /v1/stock-adjustments
```

## Response format

Success:

```json
{"success":true,"data":{},"meta":{}}
```

Error:

```json
{"success":false,"error":{"code":"VALIDATION_ERROR","message":"..."}}
```

## Stage 6/7 additions

- OpenAPI draft: `docs/openapi.yaml`
- Configurable token settings:
  - `FA_API_JWT_SECRET` should be set in production.
  - `FA_API_TOKEN_TTL` controls token lifetime in seconds; default is `3600`.
- List endpoints support simple in-memory pagination:
  - `?page=1&perPage=50`
- Single-resource endpoints exist for items, categories, customers, suppliers, bank accounts, and GL accounts.
- Inactive patch endpoints prefer soft deactivation over hard deletes.
- Business read endpoints exist for sales orders, invoices, purchase orders, customer payments, and supplier payments.
- Payment creation endpoints exist for customer and supplier payments.

## Business workflow examples

Set a reusable base URL and token first:

```bash
BASE_URL=http://localhost:8080/modules/api/public/index.php/v1
TOKEN=<access-token-from-/auth/login>
```

### Sales order → delivery → invoice

```bash
curl -sS -X POST "$BASE_URL/sales/orders" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "customerId": 1,
    "branchId": 0,
    "date": "2026-05-16",
    "deliveryDate": "2026-05-16",
    "reference": "API-SO-1001",
    "location": "MEL",
    "lines": [{"stockId":"ITEM-001","quantity":2,"price":25.00}]
  }'

curl -sS -X POST "$BASE_URL/sales/deliveries" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "orderId": 123,
    "date": "2026-05-16",
    "deliveryDate": "2026-05-16",
    "reference": "API-DN-1001",
    "location": "MEL",
    "lines": [{"stockId":"ITEM-001","quantity":2}]
  }'

curl -sS -X POST "$BASE_URL/sales/invoices" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "deliveryId": 456,
    "date": "2026-05-16",
    "dueDate": "2026-05-16",
    "reference": "API-INV-1001",
    "lines": [{"stockId":"ITEM-001","quantity":2}]
  }'
```

### Purchase order → receipt → supplier invoice

```bash
curl -sS -X POST "$BASE_URL/purchase/orders" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "supplierId": 1,
    "date": "2026-05-16",
    "deliveryDate": "2026-05-16",
    "reference": "API-PO-1001",
    "supplierReference": "SUP-REF-1001",
    "location": "MEL",
    "lines": [{"stockId":"ITEM-001","quantity":5,"price":12.50}]
  }'

curl -sS -X POST "$BASE_URL/purchase/receipts" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "orderId": 123,
    "date": "2026-05-16",
    "reference": "API-GRN-1001",
    "location": "MEL",
    "lines": [{"stockId":"ITEM-001","quantity":5}]
  }'

curl -sS -X POST "$BASE_URL/purchase/invoices" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "receiptId": 456,
    "date": "2026-05-16",
    "dueDate": "2026-05-16",
    "reference": "API-SI-1001",
    "supplierReference": "SUP-INV-1001",
    "lines": [{"stockId":"ITEM-001","quantity":5,"price":12.50}]
  }'
```

## Payment allocation examples

Customer payment allocated to a sales invoice:

```bash
curl -sS -X POST "$BASE_URL/customer-payments" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "customerId": 1,
    "branchId": 0,
    "bankAccount": 1,
    "date": "2026-05-16",
    "reference": "API-CP-1001",
    "amount": 50.00,
    "memo": "API customer payment"
  }'

curl -sS -X POST "$BASE_URL/customer-payments/789/allocations" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"targetId":456,"amount":50.00,"date":"2026-05-16"}'

curl -sS -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/customer-payments/789/allocations"
```

Supplier payment allocated to a supplier invoice:

```bash
curl -sS -X POST "$BASE_URL/supplier-payments" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "supplierId": 1,
    "bankAccount": 1,
    "date": "2026-05-16",
    "reference": "API-SP-1001",
    "amount": 62.50,
    "memo": "API supplier payment"
  }'

curl -sS -X POST "$BASE_URL/supplier-payments/789/allocations" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"targetId":456,"amount":62.50,"date":"2026-05-16"}'
```

Allocation endpoints validate that the payment and target transaction exist, belong to the same customer/supplier, and that the requested amount does not exceed available unallocated balances.

## Safe lifecycle operations

Void endpoints use FrontAccounting's native `void_transaction()` logic. They do not delete records directly.

```bash
curl -sS -X POST "$BASE_URL/sales/invoices/456/void" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"date":"2026-05-16","memo":"Voided via API"}'
```

Available void endpoints:

```text
POST /v1/sales/deliveries/{id}/void
POST /v1/sales/invoices/{id}/void
POST /v1/sales/credit-notes/{id}/void
POST /v1/purchase/receipts/{id}/void
POST /v1/purchase/invoices/{id}/void
POST /v1/purchase/credit-notes/{id}/void
POST /v1/customer-payments/{id}/void
POST /v1/supplier-payments/{id}/void
POST /v1/journal-entries/{id}/void
POST /v1/stock-adjustments/{id}/void
```

These endpoints require `SA_VOIDTRANSACTION` plus the relevant document permission. FrontAccounting handles GL, tax, stock, allocation, audit-trail, and `voided` table effects.

## Credit note examples

Customer credit note against a sales invoice:

```bash
curl -sS -X POST "$BASE_URL/sales/credit-notes" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "invoiceId": 456,
    "date": "2026-05-16",
    "reference": "API-CN-1001",
    "location": "MEL",
    "memo": "API customer credit note",
    "lines": [{"stockId":"ITEM-001","quantity":1}]
  }'
```

Supplier credit note against a supplier invoice:

```bash
curl -sS -X POST "$BASE_URL/purchase/credit-notes" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{
    "invoiceId": 456,
    "date": "2026-05-16",
    "dueDate": "2026-05-16",
    "reference": "API-SC-1001",
    "supplierReference": "SUP-CREDIT-1001",
    "memo": "API supplier credit note",
    "lines": [{"stockId":"ITEM-001","quantity":1,"price":12.50}]
  }'
```

Customer and supplier credit notes are created through FrontAccounting's native credit-note internals and can then be voided through the matching `/void` endpoints.

## Validation and testing

The executable smoke test is the deployment gate:

```bash
BASE_URL=http://localhost:8080/modules/api/public/index.php/v1 \
API_USER=api_test \
API_PASSWORD='ApiTest!2026' \
bash tests/api/smoke.sh
```

The current validated Docker image is:

```text
advanceit2004/frontaccounting:2.4.20-php8.4-api-slim4.10
```
