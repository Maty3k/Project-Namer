# API Specification

This is the API specification for the spec detailed in @.agent-os/specs/2025-08-19-mvp-core-features/spec.md

> Created: 2025-08-19
> Version: 1.0.0

## Endpoints

### POST /generate-names

**Purpose:** Generate AI-powered business names based on user input and selected mode
**Controller:** `App\Http\Livewire\NameGeneratorComponent`
**Method:** Livewire component method `generateNames()`
**Authentication:** None required for MVP

**Parameters:**
- `description` (string, required): Business concept description (max 2000 characters)
- `mode` (string, required): Generation mode - one of: "creative", "professional", "brandable", "tech-focused"
- `deep_thinking` (boolean, optional, default: false): Enable enhanced AI processing

**Request Example:**
```json
{
  "description": "A productivity app that helps remote teams collaborate better through video meetings and shared workspaces",
  "mode": "professional",
  "deep_thinking": false
}
```

**Response Format:**
```json
{
  "success": true,
  "data": {
    "names": [
      "TeamSync Pro",
      "CollabSpace",
      "RemoteFlow",
      "WorkTogether",
      "TeamBridge",
      "ConnectHub",
      "SyncPoint",
      "TeamForge",
      "RemoteBase",
      "WorkStream"
    ],
    "generation_id": "gen_1234567890",
    "mode": "professional",
    "deep_thinking": false,
    "timestamp": "2025-08-19T10:30:00Z"
  }
}
```

**Error Responses:**
- `400 Bad Request`: Invalid input parameters
- `429 Too Many Requests`: Rate limit exceeded
- `503 Service Unavailable`: OpenAI API unavailable
- `500 Internal Server Error`: Unexpected server error

### POST /check-domains

**Purpose:** Check domain availability for a list of business names
**Controller:** `App\Http\Livewire\NameGeneratorComponent`
**Method:** Livewire component method `checkDomains()`
**Authentication:** None required for MVP

**Parameters:**
- `names` (array, required): Array of business names to check
- `tlds` (array, optional, default: ["com", "io", "co", "net"]): Top-level domains to check

**Request Example:**
```json
{
  "names": ["TeamSync Pro", "CollabSpace", "RemoteFlow"],
  "tlds": ["com", "io", "co", "net"]
}
```

**Response Format:**
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "name": "TeamSync Pro",
        "slug": "teamsyncpro",
        "domains": {
          "com": {"available": false, "checked_at": "2025-08-19T10:31:00Z"},
          "io": {"available": true, "checked_at": "2025-08-19T10:31:00Z"},
          "co": {"available": true, "checked_at": "2025-08-19T10:31:00Z"},
          "net": {"available": false, "checked_at": "2025-08-19T10:31:00Z"}
        }
      },
      {
        "name": "CollabSpace",
        "slug": "collabspace",
        "domains": {
          "com": {"available": true, "checked_at": "2025-08-19T10:31:00Z"},
          "io": {"available": true, "checked_at": "2025-08-19T10:31:00Z"},
          "co": {"available": false, "checked_at": "2025-08-19T10:31:00Z"},
          "net": {"available": true, "checked_at": "2025-08-19T10:31:00Z"}
        }
      }
    ],
    "checked_at": "2025-08-19T10:31:00Z",
    "cache_status": "partial"
  }
}
```

**Error Responses:**
- `400 Bad Request`: Invalid names array or TLD format
- `503 Service Unavailable`: Domain checking service unavailable
- `500 Internal Server Error`: Unexpected server error

## Controllers

### NameGeneratorComponent (Livewire Component)

**Location:** `app/Livewire/NameGeneratorComponent.php`

**Purpose:** Main component handling the complete name generation and domain checking workflow

**Properties:**
- `description` (string): User's business concept input
- `mode` (string): Selected generation mode
- `deepThinking` (boolean): Deep thinking mode toggle
- `names` (array): Generated business names
- `domainResults` (array): Domain availability results
- `isGenerating` (boolean): Loading state for name generation
- `isCheckingDomains` (boolean): Loading state for domain checking
- `searchHistory` (array): Recent search history

**Public Methods:**

#### generateNames()
- **Purpose:** Trigger AI name generation based on current form state
- **Validation:** Validates description length, mode selection
- **Side Effects:** Updates `names` property, triggers domain checking
- **Error Handling:** Catches API failures, sets appropriate error messages

#### checkDomains(?array $names = null)
- **Purpose:** Check domain availability for specified names or current names
- **Caching:** Checks cache first, only queries API for uncached results
- **Side Effects:** Updates `domainResults` property
- **Error Handling:** Graceful degradation when domain APIs fail

#### loadSearchHistory()
- **Purpose:** Load and display previous search sessions from browser storage
- **Storage:** Reads from browser localStorage via JavaScript bridge
- **Limit:** Shows most recent 30-50 generated names

#### reloadSearch(string $generationId)
- **Purpose:** Reload a previous search from history
- **Side Effects:** Updates all component state to match historical search
- **Validation:** Ensures generation ID exists in history

#### clearHistory()
- **Purpose:** Clear all search history from browser storage
- **Confirmation:** Requires user confirmation
- **Side Effects:** Empties searchHistory property, clears browser storage

### Supporting Service Classes

#### NameGenerationService

**Location:** `app/Services/NameGenerationService.php`

**Purpose:** Handle OpenAI API integration and name generation logic

**Methods:**

##### generateNames(string $description, string $mode, bool $deepThinking): array
- **Purpose:** Generate business names using OpenAI API
- **Parameters:** Business description, generation mode, deep thinking flag
- **Returns:** Array of 10 generated business names
- **Error Handling:** Throws custom exceptions for API failures
- **Caching:** Caches results based on input hash for 1 hour

##### buildPrompt(string $description, string $mode, bool $deepThinking): string
- **Purpose:** Construct mode-specific prompts for AI generation
- **Mode Handling:** Different prompt strategies per generation mode
- **Deep Thinking:** Enhanced prompt complexity when enabled

#### DomainCheckingService

**Location:** `app/Services/DomainCheckingService.php`

**Purpose:** Handle domain availability checking and caching

**Methods:**

##### checkAvailability(array $names, array $tlds): array
- **Purpose:** Check domain availability for multiple names and TLDs
- **Concurrency:** Parallel checking to minimize response time
- **Returns:** Structured array with availability results per domain
- **Error Handling:** Individual domain failures don't block other checks

##### getCachedResult(string $domain): ?array
- **Purpose:** Retrieve cached domain availability result
- **Storage:** SQLite database with 24-hour cache duration
- **Returns:** Cached result or null if not found/expired

##### cacheResult(string $domain, bool $available): void
- **Purpose:** Store domain availability result in cache
- **Duration:** 24-hour cache lifetime
- **Storage:** SQLite database with indexed domain column

## API Integration Details

### OpenAI API Integration

**Endpoint:** `https://api.openai.com/v1/chat/completions`
**Model:** `gpt-4-turbo` or `gpt-4` (GPT-5 when available)
**Authentication:** Bearer token via `OPENAI_API_KEY` environment variable

**Request Configuration:**
- Temperature: Varies by mode (0.7 creative, 0.4 professional, 0.8 brandable, 0.5 tech-focused)
- Max tokens: 200 for standard, 400 for deep thinking mode
- Top-p: 0.9 for all modes
- Frequency penalty: 0.3 to encourage variety
- Presence penalty: 0.1 to avoid repetition

**Prompt Engineering by Mode:**

**Creative Mode:**
```
Generate 10 creative, unique business names for: {description}

Focus on:
- Memorable and catchy names
- Creative wordplay and invented terms
- Emotional appeal and brand personality
- Names that spark curiosity

Return only the names, one per line, without explanations.
```

**Professional Mode:**
```
Generate 10 professional business names for: {description}

Focus on:
- Clear, trustworthy names
- Industry-appropriate terminology
- Easy to spell and pronounce
- Conveys competence and reliability

Return only the names, one per line, without explanations.
```

**Brandable Mode:**
```
Generate 10 brandable business names for: {description}

Focus on:
- Short, memorable names (1-3 syllables)
- Easy to trademark and protect
- Works well as a logo or brand mark
- Modern and timeless appeal

Return only the names, one per line, without explanations.
```

**Tech-focused Mode:**
```
Generate 10 technology-focused business names for: {description}

Focus on:
- Modern tech terminology
- Scalable and growth-oriented names
- Appeals to technical audiences
- Suggests innovation and forward-thinking

Return only the names, one per line, without explanations.
```

### Domain Availability API Integration

**Primary Option: Namecheap API**
- Endpoint: `https://api.namecheap.com/xml.response`
- Authentication: API key + API user via environment variables
- Method: `namecheap.domains.check`
- Rate Limit: 700 requests per hour

**Fallback Option: WHOIS Lookup**
- Endpoint: Various WHOIS servers per TLD
- Method: Direct WHOIS protocol queries
- Rate Limit: Provider dependent
- Reliability: Lower than commercial APIs

**Response Processing:**
- Parse XML/JSON responses to boolean availability status
- Handle edge cases: premium domains, restricted domains, API errors
- Normalize responses across different providers
- Implement exponential backoff for rate limiting