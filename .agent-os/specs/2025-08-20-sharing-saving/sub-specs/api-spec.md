# API Specification

This is the API specification for the spec detailed in @.agent-os/specs/2025-08-20-sharing-saving/spec.md

> Created: 2025-08-20
> Version: 1.0.0

## Endpoints

### POST /api/shares

**Purpose:** Create a new share for name generation results
**Parameters:** 
- `name_generation_id` (required): ID of the name generation to share
- `share_type` (required): 'public' or 'password_protected'
- `password` (optional): Required if share_type is 'password_protected'
- `title` (optional): Custom title for the share
- `description` (optional): Description for the share
- `expires_at` (optional): Expiration timestamp

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "share_url": "https://app.com/shared/550e8400-e29b-41d4-a716-446655440000",
    "share_type": "public",
    "expires_at": null,
    "created_at": "2025-08-20T14:30:00Z"
  }
}
```

**Errors:** 400 (Invalid parameters), 404 (Generation not found), 429 (Rate limit exceeded)

### GET /api/shares

**Purpose:** List user's created shares with pagination and filtering
**Parameters:**
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (max 50)
- `share_type` (optional): Filter by share type
- `search` (optional): Search in title and description

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Startup Names - Project Alpha",
      "share_type": "password_protected",
      "view_count": 15,
      "created_at": "2025-08-20T14:30:00Z",
      "expires_at": null,
      "is_active": true
    }
  ],
  "pagination": {
    "current_page": 1,
    "total": 25,
    "per_page": 10,
    "last_page": 3
  }
}
```

### GET /api/shares/{uuid}

**Purpose:** Get detailed information about a specific share
**Parameters:** None (UUID in path)

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Startup Names - Project Alpha",
    "description": "Potential names for our new fintech startup",
    "share_type": "password_protected",
    "view_count": 15,
    "last_viewed_at": "2025-08-20T16:45:00Z",
    "created_at": "2025-08-20T14:30:00Z",
    "expires_at": null,
    "name_generation": {
      "id": 123,
      "business_idea": "Fintech startup for small businesses",
      "generated_names": [
        {
          "name": "FinanceFlow",
          "domain_status": "available",
          "ai_model": "gpt-4"
        }
      ]
    }
  }
}
```

**Errors:** 404 (Share not found), 403 (Access denied)

### PUT /api/shares/{uuid}

**Purpose:** Update share settings and information
**Parameters:**
- `title` (optional): Update share title
- `description` (optional): Update description
- `expires_at` (optional): Set or update expiration
- `is_active` (optional): Enable/disable share

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Updated Title",
    "description": "Updated description",
    "expires_at": "2025-12-31T23:59:59Z",
    "is_active": true,
    "updated_at": "2025-08-20T17:00:00Z"
  }
}
```

### DELETE /api/shares/{uuid}

**Purpose:** Delete/deactivate a share
**Parameters:** None

**Response:**
```json
{
  "success": true,
  "message": "Share has been deleted successfully"
}
```

### POST /api/exports

**Purpose:** Create an export of name generation results
**Parameters:**
- `name_generation_id` (required): ID of the generation to export
- `export_type` (required): 'pdf', 'csv', or 'json'
- `include_domains` (optional): Include domain availability data
- `include_metadata` (optional): Include generation metadata

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "660f9511-f39c-52e5-b827-557766551111",
    "export_type": "pdf",
    "download_url": "/api/exports/660f9511-f39c-52e5-b827-557766551111/download",
    "file_size": 245760,
    "expires_at": "2025-08-27T14:30:00Z",
    "created_at": "2025-08-20T14:30:00Z"
  }
}
```

### GET /api/exports/{uuid}/download

**Purpose:** Download the generated export file
**Parameters:** None

**Response:** File download with appropriate Content-Type headers

## Public Endpoints (No Authentication Required)

### GET /shared/{uuid}

**Purpose:** View shared name generation results publicly
**Parameters:** None (UUID in path)

**Response:** HTML page with shared content, or password prompt for protected shares

### POST /shared/{uuid}/authenticate

**Purpose:** Authenticate for password-protected shares
**Parameters:**
- `password` (required): Share password

**Response:** Sets session authentication for protected share viewing

## Controllers

### ShareController
- `index()` - List user's shares with filtering and pagination
- `store()` - Create new share with validation and rate limiting
- `show()` - Display share details with access analytics
- `update()` - Update share settings with authorization checks
- `destroy()` - Delete share with confirmation

### PublicShareController
- `view()` - Display shared content publicly
- `authenticate()` - Handle password authentication for protected shares
- `track()` - Record share access for analytics

### ExportController
- `create()` - Generate export files in requested format
- `download()` - Serve export files with proper headers and tracking
- `cleanup()` - Remove expired export files (scheduled job)

## Rate Limiting

- Share creation: 10 requests per hour per user
- Export generation: 5 requests per hour per user
- Public share viewing: 100 requests per hour per IP
- Authentication attempts: 5 attempts per 15 minutes per IP