# API Specification

This is the API specification for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## Web Routes

### Dashboard & Navigation

#### GET /
**Purpose:** Display dashboard with new idea input form
**Controller:** `App\Http\Controllers\DashboardController@index`
**Middleware:** `web`
**Response:** Blade view with auto-focused textarea

#### GET /idea/{slug}
**Purpose:** Display individual idea detail page
**Controller:** `App\Http\Controllers\IdeaController@show`
**Parameters:** 
- `slug` (string): Unique idea identifier
**Middleware:** `web`
**Response:** Blade view with idea details, generation history
**Errors:** 
- 404: Idea not found
- 403: Access denied (if future multi-user support)

## Livewire Endpoints

### IdeaSidebar Component

#### Method: loadMore()
**Purpose:** Load additional ideas for infinite scroll
**Parameters:**
- `page` (int): Current page number
**Response:** Array of idea objects with pagination
**Emits:** `ideas-loaded` event

#### Method: search()
**Purpose:** Filter ideas in sidebar
**Parameters:**
- `query` (string): Search term
**Response:** Filtered array of ideas
**Throttle:** 300ms debounce

#### Method: toggleStar()
**Purpose:** Star/unstar an idea
**Parameters:**
- `ideaId` (int): Idea to toggle
**Response:** Updated idea object
**Emits:** `idea-starred` event

### IdeaCreator Component

#### Method: create()
**Purpose:** Create new idea from dashboard input
**Validation:**
```php
[
    'description' => 'required|string|min:10|max:2000'
]
```
**Response:** Redirect to new idea page
**Side Effects:**
- Creates idea record
- Generates slug
- Sets session association

### IdeaSession Component

#### Method: updateTitle()
**Purpose:** Rename idea from detail page
**Parameters:**
- `title` (string): New title
**Validation:**
```php
[
    'title' => 'required|string|max:255'
]
```
**Response:** Updated idea object
**Emits:** `idea-renamed` event

#### Method: delete()
**Purpose:** Soft delete an idea
**Confirmation:** Required via modal
**Response:** Redirect to dashboard
**Side Effects:**
- Soft deletes idea
- Cascades to generations

#### Method: generateNames()
**Purpose:** Trigger name generation for current idea
**Parameters:**
- `mode` (string): Generation mode (creative, professional, etc.)
- `count` (int): Number of names to generate
**Response:** Array of generated names
**Queue:** Dispatches to background job
**Emits:** `generation-started`, `generation-completed` events

#### Method: generateLogos()
**Purpose:** Trigger logo generation for selected name
**Parameters:**
- `name` (string): Selected name for logo
- `style` (string): Logo style preference
**Response:** Array of logo URLs
**Queue:** Dispatches to background job
**Emits:** `logo-generation-started`, `logo-generation-completed` events

## API Routes (JSON)

### GET /api/ideas
**Purpose:** Fetch paginated list of ideas
**Headers:** `Accept: application/json`
**Parameters:**
- `page` (int): Page number
- `per_page` (int): Items per page (max 50)
- `search` (string): Optional search query
- `starred` (boolean): Filter starred only
**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "slug": "ai-fitness-app-1234567890",
            "title": "AI Fitness App",
            "description": "AI-powered fitness app for busy professionals",
            "is_starred": false,
            "last_accessed_at": "2025-01-27T10:30:00Z",
            "created_at": "2025-01-26T14:00:00Z",
            "generations_count": 3
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 150,
        "per_page": 20,
        "last_page": 8
    }
}
```
**Rate Limit:** 60 requests per minute

### GET /api/ideas/{slug}
**Purpose:** Fetch single idea with full details
**Parameters:**
- `slug` (string): Idea identifier
- `include` (string): Comma-separated includes (generations,favorites)
**Response:**
```json
{
    "data": {
        "id": 1,
        "slug": "ai-fitness-app-1234567890",
        "title": "AI Fitness App",
        "description": "Full description text...",
        "metadata": {},
        "is_starred": false,
        "generations": [
            {
                "id": 1,
                "type": "names",
                "results": [...],
                "created_at": "2025-01-26T14:05:00Z"
            }
        ],
        "favorites": []
    }
}
```
**Errors:**
- 404: Idea not found

### POST /api/ideas
**Purpose:** Create new idea via API
**Headers:** 
- `Accept: application/json`
- `Content-Type: application/json`
**Body:**
```json
{
    "description": "Your business idea description",
    "title": "Optional custom title"
}
```
**Validation:**
```php
[
    'description' => 'required|string|min:10|max:2000',
    'title' => 'nullable|string|max:255'
]
```
**Response:**
```json
{
    "data": {
        "id": 2,
        "slug": "generated-slug-1234567891",
        "title": "Generated or provided title",
        "description": "Your business idea description",
        "created_at": "2025-01-27T15:00:00Z"
    }
}
```
**Rate Limit:** 10 requests per minute

### PATCH /api/ideas/{slug}
**Purpose:** Update idea details
**Body:**
```json
{
    "title": "New title",
    "is_starred": true
}
```
**Validation:**
```php
[
    'title' => 'sometimes|string|max:255',
    'is_starred' => 'sometimes|boolean'
]
```
**Response:** Updated idea object

### DELETE /api/ideas/{slug}
**Purpose:** Soft delete an idea
**Response:**
```json
{
    "message": "Idea deleted successfully",
    "deleted_at": "2025-01-27T16:00:00Z"
}
```

### POST /api/ideas/{slug}/generations
**Purpose:** Create new generation for idea
**Body:**
```json
{
    "type": "names",
    "parameters": {
        "mode": "creative",
        "count": 10
    }
}
```
**Response:**
```json
{
    "data": {
        "id": 3,
        "job_id": "uuid-here",
        "status": "processing",
        "estimated_time": 5000
    }
}
```
**Queue:** Dispatches background job

### GET /api/ideas/{slug}/generations/{id}
**Purpose:** Check generation status or fetch results
**Response:**
```json
{
    "data": {
        "id": 3,
        "status": "completed",
        "results": [...],
        "processing_time_ms": 3500
    }
}
```

## WebSocket Events (Future)

### Channel: idea.{slug}
**Events:**
- `generation.started` - When generation begins
- `generation.progress` - Progress updates
- `generation.completed` - Results ready
- `generation.failed` - Error occurred

## Middleware & Security

### Rate Limiting
- API endpoints: 60 req/min for reads, 10 req/min for writes
- Livewire actions: 30 req/min per component

### CSRF Protection
- All POST/PATCH/DELETE requests require CSRF token
- Livewire handles automatically

### Session Security
- Session timeout: 120 minutes
- Regenerate session ID on idea creation
- HttpOnly cookies for session management

### Input Sanitization
- XSS protection via Laravel's e() helper
- SQL injection prevention via Eloquent ORM
- File upload validation (for future logo uploads)

## Error Responses

### Standard Error Format
```json
{
    "message": "Human readable error message",
    "errors": {
        "field": ["Validation error message"]
    },
    "code": "ERROR_CODE",
    "status": 422
}
```

### Common Error Codes
- `IDEA_NOT_FOUND` - 404
- `VALIDATION_FAILED` - 422
- `RATE_LIMITED` - 429
- `SERVER_ERROR` - 500
- `GENERATION_FAILED` - 500