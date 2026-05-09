# Novate Platform API — Testing Guide

## Swagger / OpenAPI Documentation

The API is fully documented with OpenAPI 3.0.0. Two endpoints are available:

| Endpoint | Description |
|---|---|
| `GET /api/documentation` | Swagger UI — interactive API browser |
| `GET /api/docs` | OpenAPI JSON spec (consumed by Swagger UI) |

### Setup

```bash
# Generate/regenerate the OpenAPI spec
php artisan l5-swagger:generate

# If Swagger UI routes are not showing (404), clear stale route cache:
php artisan route:clear
```

The spec is generated from PHP 8 attributes on all controllers in `app/Http/Controllers/Api/V1/`.

## Testing the API

### 1. Start the dev server

```bash
php artisan serve
```

### 2. Authentication Flow

All protected endpoints require a Sanctum Bearer token.

**Register a user (password must be ≥12 chars with mixed case, numbers, and symbols):**

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "StrongP@ss123!",
    "password_confirmation": "StrongP@ss123!"
  }'
```

**Login to get a token:**

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'
```

Save the `token` from the response.

**Use the token for authenticated requests:**

```bash
curl http://localhost:8000/api/v1/auth/profile \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <your-token>"
```

### 3. Key Flows to Test

#### Organizations
- `POST /api/v1/organizations` — Create organization
- `POST /api/v1/organizations/{org}/invite` — Invite user
- `POST /api/v1/organizations/{org}/switch` — Switch active organization

#### Forms
- `POST /api/v1/forms` — Create form (dynamic schema)
- `POST /api/v1/forms/{form}/publish` — Publish form
- `POST /api/v1/forms/{form}/archive` — Archive form

#### Submissions
- `POST /api/v1/submissions` — Submit form data
  - Requires a published form
  - Content-Type can be `application/json` or `multipart/form-data`
- `GET /api/v1/submissions/{submission}` — Retrieve submission

#### Analytics & Reports
- `GET /api/v1/analytics` — Aggregated form analytics
- `POST /api/v1/exports` — Generate report export

### 4. Running Feature Tests

```bash
php artisan test
```

Currently 6 test files exist (3 meaningful feature tests covering Auth, Project, and Form/Submission flows).
All tests pass. Note: coverage is minimal — no auth-failure, validation-error, or cross-tenant isolation tests exist yet.

### 5. Routes Overview

| Group | Routes | Auth Required |
|---|---|---|
| Auth | 10 (register, login, logout, refresh, profile, email verification, password reset) | Mixed |
| Organizations | 8 (CRUD, invite, switch, users) | Yes |
| Forms | 7 (CRUD, publish, archive) | Yes |
| Submissions | 5 (CRUD) | Yes |
| Projects | 8 (CRUD, tasks) | Yes |
| Analytics | 2 (index, dashboard stats) | Yes |
| Reports | 3 (CRUD exports) | Yes |
| Notifications | 5 (list, unread, mark read, mark all read, delete) | Yes |
| Health | 1 | No |

Total: **49 routes**, **48 documented** in OpenAPI via PHP 8 attributes (health endpoint is a Closure).

### 6. Response Format

All API responses follow a consistent format:

```json
{
  "success": true,
  "message": "Operation successful.",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 10
  }
}
```

Error responses:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": { "email": ["The email field is required."] }
}
```
