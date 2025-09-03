# AI API Reference

> Version: 1.0.0
> Last Updated: September 2, 2025

## Overview

This document provides detailed API reference for the Project Namer AI integration system, including REST endpoints, WebSocket events, and service method signatures.

## Table of Contents

1. [REST API Endpoints](#rest-api-endpoints)
2. [WebSocket Events](#websocket-events)
3. [Service Classes](#service-classes)
4. [Error Codes](#error-codes)
5. [Rate Limits](#rate-limits)
6. [Authentication](#authentication)

## REST API Endpoints

### Generate Names

Generate business names using AI.

**Endpoint:** `POST /api/ai/generate`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "business_description": "A modern tech startup focused on AI-powered solutions",
    "industry": "technology",
    "style": "creative",
    "model": "openai-gpt-4",
    "count": 10,
    "include_explanations": true,
    "check_domains": true
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "suggestions": [
            {
                "name": "TechFlow",
                "explanation": "Modern tech name suggesting smooth data flow",
                "domain_available": true,
                "confidence_score": 0.85,
                "domain_info": {
                    "com": true,
                    "io": false,
                    "co": true
                }
            }
        ],
        "metadata": {
            "model_used": "openai-gpt-4",
            "generation_time": 2.3,
            "cost": 0.045,
            "token_usage": {
                "input_tokens": 120,
                "output_tokens": 85,
                "total_tokens": 205
            }
        }
    }
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `business_description` | string | Yes | Description of the business (max 2000 chars) |
| `industry` | string | No | Target industry category |
| `style` | string | No | Generation style: `creative`, `professional`, `brandable`, `tech` |
| `model` | string | No | AI model ID. Defaults to system default |
| `count` | integer | No | Number of suggestions (1-20, default: 10) |
| `include_explanations` | boolean | No | Include explanation for each suggestion |
| `check_domains` | boolean | No | Check domain availability |

**Error Response:**
```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Hourly usage limit exceeded",
        "details": {
            "limit": 50,
            "used": 50,
            "reset_time": "2025-09-02T15:00:00Z"
        }
    }
}
```

### Get Available Models

Retrieve available AI models and their configurations.

**Endpoint:** `GET /api/ai/models`

**Response:**
```json
{
    "success": true,
    "data": {
        "openai-gpt-4": {
            "name": "GPT-4",
            "provider": "openai",
            "description": "Most capable GPT model",
            "enabled": true,
            "available": true,
            "cost_per_1k_tokens": 0.03,
            "max_tokens": 150,
            "temperature": 0.7,
            "capabilities": ["text_generation", "creative_writing"]
        },
        "openai-gpt-3.5-turbo": {
            "name": "GPT-3.5 Turbo",
            "provider": "openai",
            "description": "Fast and cost-effective",
            "enabled": true,
            "available": true,
            "cost_per_1k_tokens": 0.002,
            "max_tokens": 150,
            "temperature": 0.8,
            "capabilities": ["text_generation", "fast_generation"]
        }
    }
}
```

### Estimate Cost

Estimate the cost of a generation request.

**Endpoint:** `POST /api/ai/estimate-cost`

**Request Body:**
```json
{
    "business_description": "A modern tech startup...",
    "model": "openai-gpt-4",
    "count": 10
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "estimated_cost": 0.045,
        "estimated_tokens": {
            "input": 120,
            "output": 85,
            "total": 205
        },
        "cost_breakdown": {
            "input_cost": 0.0036,
            "output_cost": 0.0051,
            "total_cost": 0.0087
        }
    }
}
```

### Get User Usage Stats

Get usage statistics for the authenticated user.

**Endpoint:** `GET /api/ai/usage/stats`

**Query Parameters:**
- `period`: `hour`, `day`, `week`, `month` (default: `day`)

**Response:**
```json
{
    "success": true,
    "data": {
        "period": "day",
        "total_requests": 15,
        "successful_requests": 14,
        "failed_requests": 1,
        "success_rate": 93.33,
        "total_tokens": 1250,
        "total_cost": 0.67,
        "average_response_time": 2.1,
        "model_breakdown": {
            "openai-gpt-4": {
                "requests": 10,
                "tokens": 850,
                "cost": 0.51
            },
            "openai-gpt-3.5-turbo": {
                "requests": 5,
                "tokens": 400,
                "cost": 0.16
            }
        },
        "limits": {
            "hourly": {
                "limit": 50,
                "used": 3,
                "remaining": 47,
                "percentage": 6.0
            },
            "daily": {
                "limit": 200,
                "used": 15,
                "remaining": 185,
                "percentage": 7.5
            }
        }
    }
}
```

### Get User Preferences

Get AI preferences for the authenticated user.

**Endpoint:** `GET /api/ai/preferences`

**Response:**
```json
{
    "success": true,
    "data": {
        "preferred_model": "openai-gpt-4",
        "default_style": "creative",
        "preferred_count": 10,
        "auto_check_domains": true,
        "include_explanations": true,
        "notification_preferences": {
            "generation_complete": true,
            "usage_limits": true,
            "cost_alerts": false
        }
    }
}
```

### Update User Preferences

Update AI preferences for the authenticated user.

**Endpoint:** `PUT /api/ai/preferences`

**Request Body:**
```json
{
    "preferred_model": "openai-gpt-3.5-turbo",
    "default_style": "professional",
    "preferred_count": 15,
    "auto_check_domains": false
}
```

**Response:**
```json
{
    "success": true,
    "message": "Preferences updated successfully"
}
```

## Admin API Endpoints

### Get System Analytics

Get system-wide analytics (admin only).

**Endpoint:** `GET /api/admin/ai/analytics`

**Query Parameters:**
- `period`: `hour`, `day`, `week`, `month` (default: `day`)

**Response:**
```json
{
    "success": true,
    "data": {
        "period": "day",
        "total_requests": 1250,
        "successful_requests": 1198,
        "success_rate": 95.84,
        "total_cost": 45.67,
        "active_users": 156,
        "model_usage": {
            "openai-gpt-4": {
                "requests": 750,
                "success_rate": 97.2,
                "cost": 32.45,
                "avg_response_time": 2.3
            },
            "openai-gpt-3.5-turbo": {
                "requests": 500,
                "success_rate": 93.8,
                "cost": 13.22,
                "avg_response_time": 1.8
            }
        },
        "real_time_metrics": {
            "current_success_rate": 98.5,
            "error_rate_last_hour": 1.2,
            "queue_length": 5,
            "avg_response_time": 2.1
        }
    }
}
```

### Update Model Configuration

Update AI model configuration (admin only).

**Endpoint:** `PUT /api/admin/ai/models/{model_id}`

**Request Body:**
```json
{
    "enabled": true,
    "max_tokens": 200,
    "temperature": 0.8,
    "cost_per_1k_tokens": 0.035,
    "rate_limit_per_minute": 60
}
```

### Get System Budget Status

Check system budget limits (admin only).

**Endpoint:** `GET /api/admin/ai/budget`

**Response:**
```json
{
    "success": true,
    "data": {
        "daily": {
            "budget": 100.0,
            "spent": 45.67,
            "remaining": 54.33,
            "percentage": 45.7,
            "exceeded": false,
            "alert_needed": false
        },
        "monthly": {
            "budget": 2000.0,
            "spent": 1234.56,
            "remaining": 765.44,
            "percentage": 61.7,
            "exceeded": false,
            "alert_needed": false
        }
    }
}
```

## WebSocket Events

### AI Generation Events

Subscribe to real-time generation updates:

**Connection:** `ws://localhost/ai-websocket`

**Authentication:** Include JWT token in connection query: `?token={jwt_token}`

#### generation.started
```json
{
    "event": "generation.started",
    "data": {
        "session_id": "gen_123456",
        "model": "openai-gpt-4",
        "user_id": 1,
        "timestamp": "2025-09-02T14:30:00Z"
    }
}
```

#### generation.progress
```json
{
    "event": "generation.progress",
    "data": {
        "session_id": "gen_123456",
        "progress": 75,
        "stage": "processing",
        "estimated_completion": "2025-09-02T14:30:15Z"
    }
}
```

#### generation.completed
```json
{
    "event": "generation.completed",
    "data": {
        "session_id": "gen_123456",
        "result_count": 10,
        "cost": 0.045,
        "generation_time": 2.3,
        "success": true
    }
}
```

#### generation.error
```json
{
    "event": "generation.error",
    "data": {
        "session_id": "gen_123456",
        "error": {
            "code": "MODEL_UNAVAILABLE",
            "message": "Selected AI model is currently unavailable"
        }
    }
}
```

### Admin Events

#### system.alert
```json
{
    "event": "system.alert",
    "data": {
        "type": "budget_exceeded",
        "severity": "high",
        "message": "Daily budget limit exceeded",
        "details": {
            "budget": 100.0,
            "spent": 105.67
        }
    }
}
```

#### usage.limit_warning
```json
{
    "event": "usage.limit_warning",
    "data": {
        "user_id": 1,
        "limit_type": "hourly",
        "usage_percentage": 85,
        "remaining_requests": 7
    }
}
```

## Service Classes

### AIGenerationService

```php
class AIGenerationService
{
    /**
     * Generate business names using AI.
     *
     * @param array $request Generation parameters
     * @return array Generated names with metadata
     * @throws ModelUnavailableException
     * @throws RateLimitExceededException
     * @throws InsufficientCreditsException
     */
    public function generateNames(array $request): array;

    /**
     * Get available AI models.
     *
     * @return array Available models with configurations
     */
    public function getAvailableModels(): array;

    /**
     * Estimate cost for a generation request.
     *
     * @param array $request Request parameters
     * @return array Cost estimation details
     */
    public function estimateCost(array $request): array;

    /**
     * Validate generation request.
     *
     * @param array $request Request to validate
     * @return array Validation errors
     */
    public function validateRequest(array $request): array;
}
```

### AIConfigurationService

```php
class AIConfigurationService
{
    /**
     * Get all available models with status.
     *
     * @return array Models with availability status
     */
    public function getAvailableModels(): array;

    /**
     * Check if model is available for use.
     *
     * @param string $modelId Model identifier
     * @return bool True if available
     */
    public function isModelAvailable(string $modelId): bool;

    /**
     * Get model status information.
     *
     * @param string $modelId Model identifier
     * @return string Status code
     */
    public function getModelStatus(string $modelId): string;

    /**
     * Update model configuration.
     *
     * @param string $modelId Model identifier
     * @param array $config Configuration updates
     * @return bool Success status
     */
    public function updateModelConfig(string $modelId, array $config): bool;

    /**
     * Get system settings.
     *
     * @return array Current system settings
     */
    public function getSystemSettings(): array;
}
```

### AICostTrackingService

```php
class AICostTrackingService
{
    /**
     * Record API usage and calculate cost.
     *
     * @param User|null $user User making request
     * @param string $modelId Model used
     * @param int $inputTokens Input token count
     * @param int $outputTokens Output token count
     * @param float $responseTime Response time in seconds
     * @param bool $successful Whether request succeeded
     * @return float Calculated cost
     */
    public function recordUsage(
        ?User $user,
        string $modelId,
        int $inputTokens,
        int $outputTokens,
        float $responseTime,
        bool $successful = true
    ): float;

    /**
     * Get user usage statistics.
     *
     * @param User $user Target user
     * @param string $period Time period
     * @return array Usage statistics
     */
    public function getUserUsageStats(User $user, string $period = 'day'): array;

    /**
     * Check user usage limits.
     *
     * @param User $user Target user
     * @return array Limit status information
     */
    public function checkUserLimits(User $user): array;

    /**
     * Get system-wide cost statistics.
     *
     * @param string $period Time period
     * @return array System cost statistics
     */
    public function getSystemCostStats(string $period = 'day'): array;
}
```

## Error Codes

### Client Errors (4xx)

| Code | Message | Description |
|------|---------|-------------|
| `INVALID_REQUEST` | Invalid request parameters | Request validation failed |
| `MISSING_PARAMETER` | Required parameter missing | Required field not provided |
| `INVALID_MODEL` | Invalid model specified | Model ID not recognized |
| `RATE_LIMIT_EXCEEDED` | Rate limit exceeded | User exceeded usage limits |
| `INSUFFICIENT_CREDITS` | Insufficient credits | User doesn't have enough credits |
| `UNAUTHORIZED` | Unauthorized access | Authentication required |
| `FORBIDDEN` | Access forbidden | User doesn't have required permissions |

### Server Errors (5xx)

| Code | Message | Description |
|------|---------|-------------|
| `MODEL_UNAVAILABLE` | AI model unavailable | Selected model is not available |
| `API_ERROR` | External API error | Error from AI provider |
| `TIMEOUT` | Request timeout | Request took too long to process |
| `INTERNAL_ERROR` | Internal server error | Unexpected server error |
| `SERVICE_UNAVAILABLE` | Service unavailable | AI service is down |
| `QUOTA_EXCEEDED` | System quota exceeded | System-wide limits reached |

### Error Response Format

```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Hourly usage limit exceeded",
        "details": {
            "limit": 50,
            "used": 50,
            "reset_time": "2025-09-02T15:00:00Z",
            "retry_after": 3600
        },
        "suggestions": [
            "Wait until the reset time",
            "Upgrade your account for higher limits",
            "Use a more efficient model"
        ]
    }
}
```

## Rate Limits

### User Limits

| Endpoint | Limit | Window |
|----------|--------|--------|
| `POST /api/ai/generate` | 50 requests | 1 hour |
| `POST /api/ai/generate` | 200 requests | 1 day |
| `GET /api/ai/models` | 100 requests | 1 hour |
| `POST /api/ai/estimate-cost` | 200 requests | 1 hour |

### System Limits

| Resource | Limit |
|----------|-------|
| Total daily generations | 10,000 |
| Concurrent generations | 50 |
| Max tokens per request | 4,000 |
| Max request size | 10 MB |

### Rate Limit Headers

All API responses include rate limit information:

```
X-RateLimit-Limit: 50
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1693747200
X-RateLimit-Retry-After: 3600
```

## Authentication

### API Token Authentication

Include your API token in the Authorization header:

```
Authorization: Bearer {your_api_token}
```

### Session Authentication

For web interface, use Laravel's built-in session authentication:

```php
Auth::user(); // Returns authenticated user
```

### Scopes

API tokens can have different scopes:

- `ai:generate` - Generate names
- `ai:read` - Read usage stats and preferences
- `ai:write` - Update preferences
- `ai:admin` - Admin access to system analytics and configuration

### Token Generation

Generate API tokens through the user dashboard or programmatically:

```php
$token = $user->createToken('AI API Access', ['ai:generate', 'ai:read'])->plainTextToken;
```

---

## SDK Examples

### PHP SDK

```php
use ProjectNamer\AI\Client;

$client = new Client('your_api_token');

// Generate names
$result = $client->generateNames([
    'business_description' => 'Modern tech startup',
    'style' => 'creative',
    'count' => 10,
]);

// Get usage stats
$stats = $client->getUsageStats('day');

// Estimate cost
$estimate = $client->estimateCost([
    'business_description' => 'Tech startup',
    'model' => 'openai-gpt-4',
]);
```

### JavaScript SDK

```javascript
import { AIClient } from '@project-namer/ai-client';

const client = new AIClient('your_api_token');

// Generate names
const result = await client.generateNames({
    businessDescription: 'Modern tech startup',
    style: 'creative',
    count: 10,
});

// Get usage stats
const stats = await client.getUsageStats('day');

// Listen to real-time events
client.on('generation.completed', (data) => {
    console.log('Generation completed:', data);
});
```

---

*This API reference is automatically generated from the codebase. Last updated: September 2, 2025*