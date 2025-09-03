# API Specification

This is the API specification for the spec detailed in @.agent-os/specs/2025-09-01-ai-integration-prism/spec.md

> Created: 2025-09-01
> Version: 1.0.0

## API Endpoints

### POST /api/ai/generate-names

**Purpose:** Generate business names using selected AI models with specified parameters
**Parameters:** 
- `business_idea` (string, required): Project description (10-2000 characters)
- `project_id` (integer, optional): Associated project ID for contextual generation
- `models` (array, required): Array of model names to use ['gpt-4o', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta']
- `generation_mode` (string, required): One of 'creative', 'professional', 'brandable', 'tech-focused'
- `deep_thinking` (boolean, optional): Enable enhanced reasoning mode, default false
- `count` (integer, optional): Number of names per model (5-20), default 10

**Response:** 
```json
{
    "success": true,
    "data": {
        "generation_id": "uuid-string",
        "status": "processing",
        "models_requested": ["gpt-4o", "claude-3.5-sonnet"],
        "estimated_completion_time": 15000,
        "partial_results": []
    }
}
```

**Errors:** 400 (Invalid parameters), 429 (Rate limit exceeded), 500 (Generation failed)

### GET /api/ai/generation/{generationId}

**Purpose:** Get generation status and results for a specific generation session
**Parameters:** 
- `generationId` (string, required): UUID of the generation session

**Response:**
```json
{
    "success": true,
    "data": {
        "generation_id": "uuid-string",
        "status": "completed",
        "progress": 100,
        "models_completed": ["gpt-4o", "claude-3.5-sonnet"],
        "results": {
            "gpt-4o": {
                "names": ["TechFlow", "InnovateLab", "..."],
                "generation_time_ms": 2500,
                "tokens_used": 150
            },
            "claude-3.5-sonnet": {
                "names": ["CreativeCore", "DesignPulse", "..."],
                "generation_time_ms": 3200,
                "tokens_used": 180
            }
        },
        "total_names": 20,
        "generation_metadata": {
            "mode": "creative",
            "deep_thinking": false,
            "created_at": "2025-09-01T12:00:00Z"
        }
    }
}
```

**Errors:** 404 (Generation not found), 403 (Unauthorized access)

### POST /api/ai/cancel-generation/{generationId}

**Purpose:** Cancel an in-progress AI generation session
**Parameters:**
- `generationId` (string, required): UUID of the generation session to cancel

**Response:**
```json
{
    "success": true,
    "message": "Generation cancelled successfully",
    "data": {
        "generation_id": "uuid-string",
        "status": "cancelled",
        "partial_results_count": 5
    }
}
```

**Errors:** 404 (Generation not found), 409 (Already completed), 403 (Unauthorized)

### GET /api/ai/models

**Purpose:** Get available AI models and their current status
**Parameters:** None

**Response:**
```json
{
    "success": true,
    "data": {
        "models": [
            {
                "name": "gpt-4o",
                "display_name": "GPT-4 Omni",
                "provider": "openai",
                "status": "available",
                "average_response_time_ms": 2000,
                "rate_limit_remaining": 95,
                "specialties": ["creative", "professional"],
                "cost_per_1k_tokens": 0.03
            },
            {
                "name": "claude-3.5-sonnet",
                "display_name": "Claude 3.5 Sonnet",
                "provider": "anthropic",
                "status": "available",
                "average_response_time_ms": 3500,
                "rate_limit_remaining": 48,
                "specialties": ["professional", "brandable"],
                "cost_per_1k_tokens": 0.015
            }
        ]
    }
}
```

**Errors:** 500 (Service unavailable)

### PUT /api/ai/preferences

**Purpose:** Update user's AI model preferences and default settings
**Parameters:**
- `preferred_models` (array, required): Ordered array of preferred model names
- `default_generation_mode` (string, required): Default mode for new generations
- `enable_deep_thinking` (boolean, optional): Default deep thinking setting
- `auto_generate_on_create` (boolean, optional): Auto-generate when creating projects

**Response:**
```json
{
    "success": true,
    "message": "Preferences updated successfully",
    "data": {
        "preferred_models": ["claude-3.5-sonnet", "gpt-4o"],
        "default_generation_mode": "professional",
        "enable_deep_thinking": true,
        "auto_generate_on_create": false
    }
}
```

**Errors:** 400 (Invalid preferences), 401 (Unauthorized)

### GET /api/ai/history

**Purpose:** Get user's AI generation history with pagination
**Parameters:**
- `page` (integer, optional): Page number, default 1
- `per_page` (integer, optional): Items per page (10-50), default 20
- `project_id` (integer, optional): Filter by specific project
- `model` (string, optional): Filter by AI model

**Response:**
```json
{
    "success": true,
    "data": {
        "generations": [
            {
                "id": "uuid-string",
                "project_name": "My Project",
                "business_idea": "E-commerce platform...",
                "models_used": ["gpt-4o", "claude-3.5-sonnet"],
                "total_names": 20,
                "selected_names": 2,
                "created_at": "2025-09-01T12:00:00Z",
                "generation_time_ms": 5700
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_count": 87,
            "per_page": 20
        }
    }
}
```

**Errors:** 401 (Unauthorized), 400 (Invalid parameters)

## Livewire Actions Integration

### Dashboard Component Actions

#### generateNamesWithAI()
- Validates business idea input and generation parameters
- Creates AIGeneration record with pending status
- Dispatches async job for multi-model name generation
- Updates UI with generation progress and streaming results
- Creates NameSuggestion records as results arrive from each model

#### toggleAIGeneration()
- Shows/hides AI generation controls in dashboard form
- Loads user's preferred models and default settings
- Provides real-time model status and availability indicators

### ProjectPage Component Actions

#### generateMoreNames()
- Uses existing project context (name, description, selected names) for enhanced prompts
- Supports contextual generation with previous selections as reference
- Integrates new suggestions with existing NameResultCard display
- Provides bulk actions for managing AI-generated suggestions

#### compareModels()
- Triggers parallel generation across all available models
- Creates tabbed interface showing results from each model
- Enables side-by-side comparison of naming approaches
- Allows selective integration of preferred suggestions

### Real-time Updates

#### Livewire Events
- `ai-generation-started`: Fired when generation begins with session ID
- `ai-generation-progress`: Updates with partial results as models complete
- `ai-generation-completed`: Final results with performance metrics
- `ai-generation-failed`: Error handling with retry options and fallback suggestions

#### Server-Sent Events (SSE)
- Stream live updates for generation progress across models
- Provide real-time status updates for rate limits and model availability
- Enable cancellation of in-progress generations
- Support multiple concurrent generation sessions per user

## Error Handling

### Client-Side Validation
- Business idea length and content validation
- Model selection requirement (at least one model)
- Rate limit checking before request submission
- Network connectivity verification

### Server-Side Error Recovery
- Automatic retry with exponential backoff for transient failures
- Fallback to alternative models when primary choice is unavailable
- Graceful degradation with partial results when some models fail
- Comprehensive error logging with request tracing for debugging

### User Feedback
- Clear error messages with suggested actions
- Progress indicators with time estimates
- Cancellation options for long-running generations
- Historical error tracking to improve user experience