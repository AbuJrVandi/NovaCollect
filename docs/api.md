# Novate Platform API Documentation

## Base URL

```
http://localhost:8000/api/v1
```

## Authentication

All endpoints except `auth/register`, `auth/login`, `auth/forgot-password`, and `auth/reset-password` require authentication via Sanctum Bearer token.

**Header:**
```
Authorization: Bearer {token}
Accept: application/json
```

## Standard Response Format

### Success
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {},
  "meta": {}
}
```

### Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

### Paginated
```json
{
  "success": true,
  "message": "Items retrieved",
  "data": [],
  "meta": {
    "pagination": {
      "current_page": 1,
      "from": 1,
      "last_page": 5,
      "per_page": 15,
      "to": 15,
      "total": 75
    }
  }
}
```

---

## Authentication Endpoints

### Register
```
POST /auth/register
```

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "StrongP@ss123",
  "password_confirmation": "StrongP@ss123",
  "organization_name": "My Workspace",
  "organization_slug": "my-workspace",
  "role": "admin"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Registration successful.",
  "data": {
    "user": { "uuid": "...", "name": "John Doe", "email": "john@example.com" },
    "organization_uuid": "...",
    "token": "1|abc123..."
  }
}
```

### Login
```
POST /auth/login
```

**Request:**
```json
{
  "email": "john@example.com",
  "password": "StrongP@ss123",
  "device_name": "web"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": { "uuid": "...", "name": "John Doe", "email": "john@example.com" },
    "token": "2|xyz789..."
  }
}
```

### Logout
```
POST /auth/logout
```

### Refresh Token
```
POST /auth/refresh
```
**Request:** `{ "device_name": "web" }`

### Forgot Password
```
POST /auth/forgot-password
```
**Request:** `{ "email": "john@example.com" }`

### Reset Password
```
POST /auth/reset-password
```
**Request:** `{ "token": "...", "email": "john@example.com", "password": "NewP@ss123", "password_confirmation": "NewP@ss123" }`

### Get Profile
```
GET /auth/profile
```

### Update Profile
```
PUT /auth/profile
```
**Request:** `{ "name": "...", "email": "...", "phone": "...", "job_title": "..." }`

### Verify Email
```
GET /auth/email/verify/{id}/{hash}
```

### Resend Verification
```
POST /auth/email/verification-notification
```

---

## Organization Endpoints

### List Organizations
```
GET /organizations?search=&status=&sort=&per_page=15
```

### Create Organization
```
POST /organizations
```
```json
{
  "name": "My Organization",
  "slug": "my-org",
  "description": "...",
  "country": "US",
  "timezone": "America/New_York",
  "settings": {}
}
```

### Get Organization
```
GET /organizations/{uuid}
```

### Update Organization
```
PUT /organizations/{uuid}
```

### Delete Organization
```
DELETE /organizations/{uuid}
```

### Invite User
```
POST /organizations/{uuid}/invite
```
```json
{ "email": "user@example.com", "role": "member" }
```

### Switch Organization
```
POST /organizations/{uuid}/switch
```

### Get Organization Users
```
GET /organizations/{uuid}/users
```

---

## Form Endpoints

### List Forms
```
GET /forms?search=&status=&sort=&per_page=15
```

### Create Form
```
POST /forms
```
```json
{
  "name": "Survey Form",
  "slug": "survey-form",
  "description": "Field survey form",
  "status": "draft",
  "project_uuid": null,
  "settings": {},
  "sections": [
    {
      "title": "Personal Info",
      "description": "Basic information",
      "sort_order": 0,
      "fields": [
        {
          "key": "full_name",
          "label": "Full Name",
          "type": "text",
          "is_required": true,
          "sort_order": 0
        },
        {
          "key": "age",
          "label": "Age",
          "type": "number",
          "is_required": true,
          "validation_rules": ["min:1", "max:150"],
          "sort_order": 1
        },
        {
          "key": "region",
          "label": "Region",
          "type": "dropdown",
          "is_required": true,
          "options": [{"label": "North", "value": "north"}, {"label": "South", "value": "south"}],
          "sort_order": 2
        }
      ]
    }
  ]
}
```

### Get Form
```
GET /forms/{uuid}
```

### Update Form
```
PUT /forms/{uuid}
```

### Delete Form
```
DELETE /forms/{uuid}
```

### Publish Form
```
POST /forms/{uuid}/publish
```

### Archive Form
```
POST /forms/{uuid}/archive
```

---

## Submission Endpoints

### List Submissions
```
GET /submissions?form_uuid=&status=&sort=&per_page=15
```

### Create Submission
```
POST /submissions
```
```json
{
  "form_uuid": "...",
  "status": "submitted",
  "payload": {
    "full_name": "Jane Doe",
    "age": 28,
    "region": "north"
  },
  "device_metadata": {
    "browser": "Chrome",
    "platform": "iOS",
    "version": "15.0"
  },
  "latitude": 40.7128,
  "longitude": -74.006,
  "external_id": "sync-001",
  "project_uuid": null,
  "files": [
    { "field_key": "photo", "file": "<binary>" }
  ]
}
```

### Get Submission
```
GET /submissions/{uuid}
```

### Update Submission
```
PUT /submissions/{uuid}
```

### Delete Submission
```
DELETE /submissions/{uuid}
```

---

## Project Endpoints

### List Projects
```
GET /projects?search=&status=&sort=&per_page=15
```

### Create Project
```
POST /projects
```
```json
{
  "name": "Baseline Study",
  "description": "Initial data collection",
  "status": "active",
  "start_date": "2026-01-01",
  "end_date": "2026-12-31",
  "members": [
    { "user_uuid": "...", "role": "manager" }
  ]
}
```

### Get Project
```
GET /projects/{uuid}
```

### Update Project
```
PUT /projects/{uuid}
```

### Delete Project
```
DELETE /projects/{uuid}
```

### Create Task
```
POST /projects/{uuid}/tasks
```
```json
{
  "title": "Collect data from region A",
  "description": "Visit 50 households",
  "priority": "high",
  "assigned_to_uuid": "...",
  "due_date": "2026-06-01"
}
```

### Update Task
```
PUT /tasks/{uuid}
```

### Delete Task
```
DELETE /tasks/{uuid}
```

---

## Analytics Endpoints

### Get Analytics
```
GET /analytics
```
Returns totals, submission trends (14 days), and top forms.

### Get Dashboard Stats
```
GET /dashboard/stats
```
Returns active projects, published forms, submissions today, task completion rate.

---

## Export Endpoints

### List Exports
```
GET /exports?per_page=15
```

### Create Export
```
POST /exports
```
```json
{
  "type": "submissions",
  "format": "csv",
  "filters": {
    "form_uuid": "...",
    "status": "submitted"
  }
}
```

### Get Export
```
GET /exports/{uuid}
```

---

## Notification Endpoints

### List Notifications
```
GET /notifications?per_page=15
```

### Get Unread Count
```
GET /notifications/unread-count
```

### Mark as Read
```
POST /notifications/{id}/read
```

### Mark All Read
```
POST /notifications/read-all
```

### Delete Notification
```
DELETE /notifications/{id}
```

---

## Health Check
```
GET /health
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 202 | Accepted (queued) |
| 400 | Bad Request |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

## Rate Limiting

- Authenticated: 180 requests per minute
- Guest: 60 requests per minute
