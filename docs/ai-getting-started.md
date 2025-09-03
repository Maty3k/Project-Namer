# AI Integration - Getting Started Guide

> Version: 1.0.0
> Last Updated: September 2, 2025

## Quick Start

This guide will get you up and running with the AI integration system in under 10 minutes.

## Prerequisites

- PHP 8.4+
- Laravel 12+
- Composer
- Node.js & NPM
- SQLite/MySQL/PostgreSQL
- AI Provider API keys (OpenAI, Anthropic, etc.)

## Step 1: Environment Setup

### 1.1 Copy Environment File

```bash
cp .env.example .env
```

### 1.2 Configure AI Settings

Add these to your `.env` file:

```env
# AI Model Configuration
AI_GPT4_ENABLED=true
AI_GPT35_ENABLED=true
AI_CLAUDE_ENABLED=false
AI_GEMINI_ENABLED=false

# API Keys (get these from provider websites)
OPENAI_API_KEY=sk-your-openai-key-here
ANTHROPIC_API_KEY=your-anthropic-key-here

# Usage Limits
AI_MAX_GENERATIONS_PER_HOUR=50
AI_MAX_GENERATIONS_PER_DAY=200

# System Features
AI_ENABLE_ANALYTICS=true
AI_ENABLE_CACHING=true
AI_ENABLE_COST_TRACKING=true

# Budget Limits
AI_DAILY_BUDGET_LIMIT=10.00
AI_MONTHLY_BUDGET_LIMIT=100.00
```

### 1.3 Generate Application Key

```bash
php artisan key:generate
```

## Step 2: Database Setup

### 2.1 Run Migrations

```bash
php artisan migrate
```

This will create tables for:
- `ai_usage_logs` - Usage and cost tracking
- `ai_generations` - Generation history
- `user_ai_preferences` - User preferences

### 2.2 Seed Demo Data (Optional)

```bash
php artisan db:seed --class=AISeeder
```

## Step 3: Install Dependencies

### 3.1 PHP Dependencies

```bash
composer install
```

### 3.2 JavaScript Dependencies

```bash
npm install
npm run build
```

## Step 4: Test the Setup

### 4.1 Start Development Server

```bash
php artisan serve
```

### 4.2 Check AI Model Status

```bash
php artisan ai:check-models
```

Expected output:
```
✓ OpenAI GPT-4: Available
✓ OpenAI GPT-3.5 Turbo: Available
✗ Anthropic Claude: Missing API Key
✗ Google Gemini: Disabled
```

### 4.3 Test Name Generation

```bash
php artisan ai:test-generation
```

## Step 5: First AI Generation

### 5.1 Using the Web Interface

1. Visit `http://localhost:8000`
2. Navigate to the name generator
3. Enter a business description
4. Select an AI model
5. Click "Generate Names"

### 5.2 Using the API

```bash
curl -X POST http://localhost:8000/api/ai/generate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d '{
    "business_description": "A modern tech startup focused on AI solutions",
    "style": "creative",
    "count": 5
  }'
```

### 5.3 Using PHP Code

```php
use App\Services\AIGenerationService;

$service = app(AIGenerationService::class);

$result = $service->generateNames([
    'business_description' => 'A modern tech startup focused on AI solutions',
    'style' => 'creative',
    'model' => 'openai-gpt-4',
    'count' => 5,
]);

dd($result);
```

## Step 6: Configure Models (Optional)

### 6.1 Access Admin Panel

Visit `http://localhost:8000/admin/ai/configuration` (requires admin account)

### 6.2 Adjust Model Settings

- Enable/disable models
- Adjust cost limits
- Configure rate limits
- Set system preferences

### 6.3 Monitor Usage

Check analytics at `http://localhost:8000/admin/ai/analytics`

## Common Setup Issues

### Issue: "Model Unavailable"

**Solution:** Check API key configuration
```bash
# Test API key
php artisan ai:test-api-key openai
```

### Issue: "Rate Limit Exceeded"

**Solution:** Adjust limits in `.env`
```env
AI_MAX_GENERATIONS_PER_HOUR=100
AI_MAX_GENERATIONS_PER_DAY=500
```

### Issue: "High Costs"

**Solution:** Configure budget limits
```env
AI_DAILY_BUDGET_LIMIT=5.00
AI_MONTHLY_BUDGET_LIMIT=50.00
```

### Issue: "Slow Responses"

**Solution:** Enable caching
```env
AI_ENABLE_CACHING=true
AI_CACHE_TTL_MINUTES=60
```

## Development Workflow

### 1. Create Custom AI Service

```php
<?php

namespace App\Services\AI;

class CustomAIService
{
    public function generateBusinessNames(array $params): array
    {
        $service = app(AIGenerationService::class);
        
        // Add custom logic here
        $customParams = array_merge($params, [
            'style' => 'custom',
            'temperature' => 0.9,
        ]);
        
        return $service->generateNames($customParams);
    }
}
```

### 2. Add Custom Model

Edit `config/ai.php`:

```php
'models' => [
    // ... existing models
    'custom-model' => [
        'name' => 'Custom Model',
        'provider' => 'openai',
        'model_id' => 'gpt-4',
        'enabled' => true,
        'max_tokens' => 200,
        'temperature' => 0.9,
        'cost_per_1k_tokens' => 0.03,
    ],
],
```

### 3. Create Custom Generation Mode

```php
// In config/ai.php
'settings' => [
    'available_modes' => [
        // ... existing modes
        'startup' => [
            'name' => 'Startup Focused',
            'description' => 'Names perfect for tech startups',
            'temperature' => 0.8,
            'max_tokens' => 120,
        ],
    ],
],
```

### 4. Add Custom Analytics

```php
use App\Services\AI\AIAnalyticsService;

class CustomAnalytics
{
    protected AIAnalyticsService $analytics;
    
    public function getStartupMetrics(User $user): array
    {
        return $this->analytics->getUserAnalytics($user, 'month', [
            'filter_mode' => 'startup',
            'include_costs' => true,
        ]);
    }
}
```

## Testing

### 1. Run AI Tests

```bash
# All AI tests
php artisan test tests/Feature/AI

# Specific test class
php artisan test tests/Feature/Services/AIGenerationServiceTest.php

# With coverage
php artisan test --coverage-html coverage/
```

### 2. Test Specific Scenarios

```php
// Test file: tests/Feature/AI/CustomGenerationTest.php

it('can generate startup names', function () {
    $service = app(AIGenerationService::class);
    
    $result = $service->generateNames([
        'business_description' => 'A tech startup',
        'style' => 'startup',
        'count' => 5,
    ]);
    
    expect($result['suggestions'])->toHaveCount(5);
    expect($result['cost'])->toBeFloat();
});
```

### 3. Mock AI Responses

```php
// In tests
use App\Services\AIGenerationService;
use Mockery;

$mock = Mockery::mock(AIGenerationService::class);
$mock->shouldReceive('generateNames')
     ->once()
     ->andReturn([
         'suggestions' => [
             ['name' => 'TestCorp', 'confidence_score' => 0.9],
         ],
         'cost' => 0.01,
     ]);

$this->app->instance(AIGenerationService::class, $mock);
```

## Production Deployment

### 1. Environment Variables

```env
# Production settings
APP_ENV=production
APP_DEBUG=false

# AI Configuration
AI_ENABLE_CACHING=true
AI_CACHE_TTL_MINUTES=120
AI_TIMEOUT_SECONDS=30

# Monitoring
AI_LOG_API_REQUESTS=true
AI_ENABLE_ANALYTICS=true

# Security
AI_GLOBAL_RATE_LIMIT=5000
AI_USER_RATE_LIMIT=100
```

### 2. Cache Configuration

```bash
# Configure Redis for production caching
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Queue Setup

```bash
# For background processing
php artisan queue:work --daemon
```

### 4. Monitoring

Set up monitoring for:
- API response times
- Error rates
- Cost tracking
- Usage limits

### 5. Backup Strategy

```bash
# Backup AI usage data
php artisan backup:run --only-db

# Archive old usage logs
php artisan ai:cleanup-logs --days=90
```

## Advanced Configuration

### Custom Provider

```php
// app/Services/AI/Providers/CustomProvider.php

namespace App\Services\AI\Providers;

class CustomProvider implements AIProviderInterface
{
    public function generateText(array $params): array
    {
        // Custom implementation
        return [
            'text' => 'Generated text',
            'tokens' => 50,
            'cost' => 0.01,
        ];
    }
}
```

Register in `config/ai.php`:

```php
'providers' => [
    'custom' => [
        'class' => CustomProvider::class,
        'api_key' => env('CUSTOM_API_KEY'),
    ],
],
```

### Custom Analytics

```php
// app/Services/Analytics/CustomAIAnalytics.php

class CustomAIAnalytics extends AIAnalyticsService
{
    public function getIndustryTrends(string $industry): array
    {
        return [
            'popular_styles' => ['creative', 'professional'],
            'avg_cost' => 0.045,
            'success_rate' => 96.5,
        ];
    }
}
```

### Performance Tuning

```php
// config/ai.php - Performance settings

'performance' => [
    'enable_parallel_requests' => true,
    'max_concurrent_requests' => 5,
    'request_timeout' => 30,
    'cache_strategy' => 'aggressive',
    'preload_models' => true,
],
```

## Next Steps

1. **Read the full documentation:** [AI Integration Guide](ai-integration-guide.md)
2. **Explore the API:** [API Reference](ai-api-reference.md)
3. **Check examples:** `/examples/ai/` directory
4. **Join the community:** GitHub Discussions
5. **Report issues:** GitHub Issues

## Getting Help

- **Documentation:** `/docs/` directory
- **Examples:** `/examples/ai/` directory
- **Tests:** `/tests/Feature/AI/` directory
- **Logs:** `storage/logs/laravel.log`
- **Debug:** Set `AI_LOG_API_REQUESTS=true`

## Troubleshooting Commands

```bash
# Check system status
php artisan ai:status

# Test API connections
php artisan ai:test-connections

# Clear AI cache
php artisan cache:forget ai_*

# Reset usage counters (development only)
php artisan ai:reset-usage

# Generate test data
php artisan ai:seed-test-data

# Export configuration
php artisan ai:export-config
```

---

Ready to build amazing AI-powered features! 🚀

For more detailed information, see the [full documentation](ai-integration-guide.md).