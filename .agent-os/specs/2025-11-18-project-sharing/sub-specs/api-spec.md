# API Specification

This is the API specification for the spec detailed in @.agent-os/specs/2025-11-18-project-sharing/spec.md

> Created: 2025-11-18
> Version: 1.0.0

## Routes

### Share Management Routes (Authenticated)

#### POST `/api/shares`
Create a new shareable link for a session.

**Purpose:** Generate a shareable link with privacy settings

**Authentication:** Required

**Parameters:**
```json
{
  "session_id": "integer (required)",
  "title": "string (optional, max:255)",
  "description": "string (optional, max:1000)",
  "password": "string (optional, min:4)",
  "expires_in": "string (optional: '1h', '1d', '1w', 'never')",
  "settings": "object (optional)"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "url": "https://projectnamer.com/share/a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "title": "My Project Names",
    "description": "Check out these awesome names I generated!",
    "is_password_protected": true,
    "expires_at": "2025-11-19T12:00:00Z",
    "created_at": "2025-11-18T12:00:00Z"
  }
}
```

**Errors:**
- `401 Unauthorized` - User not authenticated
- `404 Not Found` - Session not found
- `422 Unprocessable Entity` - Validation errors

---

#### GET `/api/shares`
Get all shares created by the authenticated user.

**Purpose:** List user's shareable links with analytics

**Authentication:** Required

**Parameters:**
- `page` (optional, integer) - Pagination page number
- `per_page` (optional, integer, max:50) - Items per page
- `sort` (optional, enum: 'created_at', 'view_count') - Sort field
- `order` (optional, enum: 'asc', 'desc') - Sort order

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "token": "a1b2c3d4",
      "url": "https://projectnamer.com/share/a1b2c3d4",
      "title": "My Project Names",
      "view_count": 42,
      "is_active": true,
      "expires_at": "2025-11-19T12:00:00Z",
      "created_at": "2025-11-18T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 10,
    "per_page": 15
  }
}
```

---

#### DELETE `/api/shares/{id}`
Delete a shareable link.

**Purpose:** Remove share and make link inaccessible

**Authentication:** Required (must own share)

**Response (204 No Content)**

**Errors:**
- `401 Unauthorized` - User not authenticated
- `403 Forbidden` - User doesn't own share
- `404 Not Found` - Share not found

---

### Export Routes (Authenticated)

#### POST `/api/exports`
Generate an export file for a session.

**Purpose:** Create downloadable PDF, CSV, or JSON export

**Authentication:** Required

**Parameters:**
```json
{
  "session_id": "integer (required)",
  "type": "string (required, enum: 'pdf', 'csv', 'json')",
  "options": "object (optional)"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "download_url": "https://projectnamer.com/download/550e8400-e29b-41d4-a716-446655440000",
    "type": "pdf",
    "filename": "project-names-2025-11-18.pdf",
    "file_size": 245760,
    "expires_at": "2025-11-25T12:00:00Z",
    "created_at": "2025-11-18T12:00:00Z"
  }
}
```

**Errors:**
- `401 Unauthorized` - User not authenticated
- `404 Not Found` - Session not found
- `422 Unprocessable Entity` - Validation errors

---

#### GET `/api/exports`
Get all exports created by the authenticated user.

**Purpose:** List user's generated exports

**Authentication:** Required

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "type": "pdf",
      "filename": "project-names.pdf",
      "file_size": 245760,
      "download_count": 3,
      "expires_at": "2025-11-25T12:00:00Z",
      "created_at": "2025-11-18T12:00:00Z"
    }
  ]
}
```

---

### Public Routes (No Authentication)

#### GET `/share/{token}`
View a shared project (web page, not API).

**Purpose:** Display shared content to anyone with link

**Authentication:** Not required

**Response:** HTML page with shared content

**Errors:**
- `404 Not Found` - Share doesn't exist or expired
- `401 Unauthorized` - Password required (redirects to password form)

---

#### POST `/share/{token}/verify`
Verify password for protected share.

**Purpose:** Unlock password-protected shares

**Authentication:** Not required

**Parameters:**
```json
{
  "password": "string (required)"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Password verified"
}
```

**Errors:**
- `401 Unauthorized` - Invalid password
- `404 Not Found` - Share not found

---

#### GET `/download/{uuid}`
Download an export file.

**Purpose:** Serve generated export files

**Authentication:** Not required (UUID acts as security token)

**Response:** File download

**Errors:**
- `404 Not Found` - Export doesn't exist or expired

---

## Controllers

### ShareController
**Namespace:** `App\Http\Controllers\Api`

**Methods:**
- `store(StoreShareRequest $request)` - Create new share
- `index(Request $request)` - List user's shares
- `destroy(Share $share)` - Delete share

**Authorization:** All methods require authentication and ownership verification

---

### ExportController
**Namespace:** `App\Http\Controllers\Api`

**Methods:**
- `store(StoreExportRequest $request)` - Generate export
- `index(Request $request)` - List user's exports
- `download(string $uuid)` - Download export file

**Authorization:** Store and index require authentication; download is public with UUID security

---

### PublicShareController
**Namespace:** `App\Http\Controllers`

**Methods:**
- `show(string $token)` - Display share page
- `verifyPassword(Request $request, string $token)` - Verify password

**Authorization:** Public access

---

## Rate Limiting

- Share creation: 10 per hour per user
- Export generation: 20 per hour per user
- Public share viewing: 100 per hour per IP
- Password verification: 5 attempts per hour per IP/token

## Caching Strategy

- Cache share data for 5 minutes to reduce database load
- Cache export metadata for 10 minutes
- Invalidate cache on share/export deletion or updates
