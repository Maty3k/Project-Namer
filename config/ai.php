<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI Models Configuration
    |--------------------------------------------------------------------------
    |
    | Configure available AI models, their capabilities, costs, and limits.
    | Each model requires provider API credentials to be configured in services.php
    |
    */

    'models' => [
        'openai-gpt-4' => [
            'name' => 'GPT-4',
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'enabled' => env('AI_GPT4_ENABLED', true),
            'max_tokens' => 150,
            'temperature' => 0.7,
            'cost_per_1k_tokens' => 0.03,
            'rate_limit_per_minute' => 60,
            'capabilities' => ['text_generation', 'creative_writing'],
            'description' => 'Most capable GPT model for high-quality name generation',
            'maintenance_mode' => false,
        ],

        'openai-gpt-3.5-turbo' => [
            'name' => 'GPT-3.5 Turbo',
            'provider' => 'openai',
            'model_id' => 'gpt-3.5-turbo',
            'enabled' => env('AI_GPT35_ENABLED', true),
            'max_tokens' => 150,
            'temperature' => 0.8,
            'cost_per_1k_tokens' => 0.002,
            'rate_limit_per_minute' => 90,
            'capabilities' => ['text_generation', 'fast_generation'],
            'description' => 'Fast and cost-effective model for name generation',
            'maintenance_mode' => false,
        ],

        'anthropic-claude' => [
            'name' => 'Claude',
            'provider' => 'anthropic',
            'model_id' => 'claude-3-sonnet-20240229',
            'enabled' => env('AI_CLAUDE_ENABLED', false),
            'max_tokens' => 150,
            'temperature' => 0.7,
            'cost_per_1k_tokens' => 0.015,
            'rate_limit_per_minute' => 50,
            'capabilities' => ['text_generation', 'nuanced_context'],
            'description' => 'Context-aware model for thoughtful name suggestions',
            'maintenance_mode' => false,
        ],

        'google-gemini' => [
            'name' => 'Gemini Pro',
            'provider' => 'google',
            'model_id' => 'gemini-pro',
            'enabled' => env('AI_GEMINI_ENABLED', false),
            'max_tokens' => 150,
            'temperature' => 0.8,
            'cost_per_1k_tokens' => 0.0005,
            'rate_limit_per_minute' => 120,
            'capabilities' => ['text_generation', 'multilingual'],
            'description' => 'Google\'s multimodal AI for diverse creative approaches',
            'maintenance_mode' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Settings
    |--------------------------------------------------------------------------
    |
    | Global system settings for AI generation functionality
    |
    */

    'settings' => [
        // Default model configuration
        'default_model' => env('AI_DEFAULT_MODEL', 'openai-gpt-4'),
        'fallback_model' => env('AI_FALLBACK_MODEL', 'openai-gpt-3.5-turbo'),

        // Usage limits
        'max_generations_per_user_per_hour' => (int) env('AI_MAX_GENERATIONS_PER_HOUR', 50),
        'max_generations_per_user_per_day' => (int) env('AI_MAX_GENERATIONS_PER_DAY', 200),

        // System features
        'enable_analytics' => env('AI_ENABLE_ANALYTICS', true),
        'enable_caching' => env('AI_ENABLE_CACHING', true),
        'enable_cost_tracking' => env('AI_ENABLE_COST_TRACKING', true),

        // Performance settings
        'cache_ttl_minutes' => (int) env('AI_CACHE_TTL_MINUTES', 60),
        'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 30),
        'retry_attempts' => (int) env('AI_RETRY_ATTEMPTS', 3),

        // System status
        'maintenance_mode' => env('AI_MAINTENANCE_MODE', false),
        'admin_notifications' => env('AI_ADMIN_NOTIFICATIONS', true),

        // Generation modes
        'available_modes' => [
            'creative' => [
                'name' => 'Creative',
                'description' => 'Imaginative and unique names with artistic flair',
                'temperature' => 0.9,
                'max_tokens' => 150,
            ],
            'professional' => [
                'name' => 'Professional',
                'description' => 'Business-appropriate names with gravitas',
                'temperature' => 0.6,
                'max_tokens' => 120,
            ],
            'brandable' => [
                'name' => 'Brandable',
                'description' => 'Memorable names perfect for branding',
                'temperature' => 0.8,
                'max_tokens' => 100,
            ],
            'tech' => [
                'name' => 'Tech-Focused',
                'description' => 'Modern names suited for technology companies',
                'temperature' => 0.7,
                'max_tokens' => 130,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Provider-specific settings and API configurations
    |
    */

    'providers' => [
        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'default_headers' => [
                'User-Agent' => 'Project-Namer/1.0',
            ],
        ],

        'anthropic' => [
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        ],

        'google' => [
            'base_url' => env('GOOGLE_AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Tracking
    |--------------------------------------------------------------------------
    |
    | Settings for tracking and managing API costs
    |
    */

    'cost_tracking' => [
        'enabled' => env('AI_COST_TRACKING_ENABLED', true),
        'currency' => 'USD',
        'daily_budget_limit' => (float) env('AI_DAILY_BUDGET_LIMIT', 100.0),
        'monthly_budget_limit' => (float) env('AI_MONTHLY_BUDGET_LIMIT', 2000.0),
        'alert_threshold_percentage' => (int) env('AI_COST_ALERT_THRESHOLD', 80),

        // Cost calculation methods
        'token_counting_method' => env('AI_TOKEN_COUNTING_METHOD', 'estimated'), // 'estimated' or 'actual'
        'include_prompt_tokens' => env('AI_INCLUDE_PROMPT_TOKENS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for analytics, logging, and monitoring
    |
    */

    'analytics' => [
        'enabled' => env('AI_ANALYTICS_ENABLED', true),
        'retention_days' => (int) env('AI_ANALYTICS_RETENTION_DAYS', 90),
        'track_user_behavior' => env('AI_TRACK_USER_BEHAVIOR', true),
        'track_model_performance' => env('AI_TRACK_MODEL_PERFORMANCE', true),
        'real_time_metrics' => env('AI_REAL_TIME_METRICS', true),

        // Metrics collection intervals
        'performance_check_interval' => (int) env('AI_PERFORMANCE_CHECK_INTERVAL', 300), // seconds
        'health_check_interval' => (int) env('AI_HEALTH_CHECK_INTERVAL', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Security settings and rate limiting configurations
    |
    */

    'security' => [
        'api_key_rotation_days' => (int) env('AI_API_KEY_ROTATION_DAYS', 90),
        'log_api_requests' => env('AI_LOG_API_REQUESTS', true),
        'log_sensitive_data' => env('AI_LOG_SENSITIVE_DATA', false),

        // Content filtering
        'enable_content_filter' => env('AI_ENABLE_CONTENT_FILTER', true),
        'blocked_patterns' => [
            // Add patterns for content that should be filtered
        ],

        // Rate limiting
        'global_rate_limit_per_minute' => (int) env('AI_GLOBAL_RATE_LIMIT', 1000),
        'per_user_rate_limit_per_minute' => (int) env('AI_USER_RATE_LIMIT', 20),
    ],
];
