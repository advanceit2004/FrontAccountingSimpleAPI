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
