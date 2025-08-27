# API Specification

This is the API specification for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-sidebar/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## Livewire Components

### `SessionSidebar` Component

**Purpose:** Manages the ChatGPT-style sidebar interface with session history and controls

**Properties:**
```php
public array $sessions = [];
public ?string $currentSessionId = null;
public string $searchQuery = '';
public bool $focusMode = false;
public int $page = 1;
public int $perPage = 20;
```

**Actions:**
```php
public function createNewSession(): void
public function loadSession(string $sessionId): void
public function deleteSession(string $sessionId): void
public function renameSession(string $sessionId, string $newTitle): void
public function toggleStarred(string $sessionId): void
public function searchSessions(): void
public function loadMoreSessions(): void
public function toggleFocusMode(): void
public function duplicateSession(string $sessionId): void
```

**Events:**
- `session-created` - Dispatched when new session is created
- `session-loaded` - Dispatched when session is loaded
- `session-deleted` - Dispatched when session is deleted
- `focus-mode-toggled` - Dispatched when focus mode changes

### `Dashboard` Component Updates

**New Properties:**
```php
public ?string $sessionId = null;
public bool $autoSave = true;
```

**New Methods:**
```php
public function initializeSession(?string $sessionId = null): void
public function saveToSession(): void
public function loadFromSession(array $sessionData): void
```

**Event Listeners:**
```php
#[On('session-created')]
public function handleNewSession(string $sessionId): void

#[On('session-loaded')]
public function handleSessionLoad(array $sessionData): void

#[On('focus-mode-toggled')]
public function handleFocusModeToggle(bool $focusMode): void
```

## RESTful API Endpoints (Optional Future Enhancement)

### Session Management

#### GET /api/sessions
**Purpose:** Retrieve user's naming sessions
**Parameters:**
- `page` (integer): Page number for pagination
- `search` (string): Search query
- `starred` (boolean): Filter starred sessions only

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "title": "Tech Startup Names",
      "preview": "AI-powered productivity tool...",
      "created_at": "2025-08-27T10:00:00Z",
      "is_starred": true,
      "last_accessed_at": "2025-08-27T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 20
  }
}
```

#### POST /api/sessions
**Purpose:** Create new naming session
**Body:**
```json
{
  "title": "New Project",
  "business_description": "Description here",
  "generation_mode": "creative"
}
```

#### GET /api/sessions/{id}
**Purpose:** Retrieve specific session with full data
**Response:**
```json
{
  "id": "uuid",
  "title": "Tech Startup Names",
  "business_description": "Full description...",
  "generation_mode": "creative",
  "deep_thinking": false,
  "results": {
    "generated_names": [...],
    "domain_results": [...],
    "selected_for_logos": [...]
  },
  "created_at": "2025-08-27T10:00:00Z",
  "updated_at": "2025-08-27T12:00:00Z"
}
```

#### PUT /api/sessions/{id}
**Purpose:** Update session data
**Body:**
```json
{
  "title": "Updated Title",
  "is_starred": true
}
```

#### DELETE /api/sessions/{id}
**Purpose:** Delete a session
**Response:** 204 No Content

## Alpine.js Components

### Focus Mode Toggle
```javascript
Alpine.data('focusMode', () => ({
    isCollapsed: Alpine.$persist(false).as('sidebar-collapsed'),
    
    toggle() {
        this.isCollapsed = !this.isCollapsed;
        this.$dispatch('focus-mode-changed', this.isCollapsed);
    },
    
    handleKeyboard(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === '/') {
            e.preventDefault();
            this.toggle();
        }
    }
}))
```

### Session Search
```javascript
Alpine.data('sessionSearch', () => ({
    query: '',
    results: [],
    loading: false,
    
    search() {
        this.loading = true;
        this.$wire.searchSessions(this.query).then(() => {
            this.loading = false;
        });
    },
    
    debounceSearch: Alpine.debounce(function() {
        this.search();
    }, 300)
}))
```

## WebSocket Events (Future Enhancement)

- `session.updated` - Real-time session updates across devices
- `session.deleted` - Sync session deletion
- `focus.mode.changed` - Sync focus mode preference