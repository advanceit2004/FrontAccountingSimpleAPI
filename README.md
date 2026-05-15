# FrontAccounting API for PHP 8.4

A new Slim 4 JSON API for FrontAccounting 2.4.x.

This branch is a modern API rewrite for PHP 8.4. It is not backward-compatible with the old Slim 2 API route structure.

## Target runtime

- FrontAccounting 2.4.20
- PHP 8.4
- Apache/mod_php
- Slim 4

## API base

Direct front-controller URL:

```text
/modules/api/public/index.php/v1
```

If Apache rewrite is enabled from the module folder, clean URLs can be exposed later as:

```text
/modules/api/v1
```

## Milestone 1 endpoints

```text
GET  /v1/health
POST /v1/auth/login
GET  /v1/items/categories
```

## Authentication

Login against FrontAccounting:

```bash
curl -i \
  -H 'Content-Type: application/json' \
  -d '{"company":0,"username":"admin","password":"secret"}' \
  http://localhost:8080/modules/api/public/index.php/v1/auth/login
```

Use the returned token:

```bash
curl -i \
  -H 'Authorization: Bearer <token>' \
  http://localhost:8080/modules/api/public/index.php/v1/items/categories
```

## Development status

This is a new API under active development. The first milestone proves:

- Slim 4 boots under PHP 8.4
- FrontAccounting 2.4.20 can be bootstrapped safely enough for API use
- FrontAccounting user login can issue a Bearer token
- A protected read endpoint can return JSON
