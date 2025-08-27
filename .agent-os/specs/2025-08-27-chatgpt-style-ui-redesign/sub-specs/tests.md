# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## Test Coverage Overview

Comprehensive test coverage ensuring the ChatGPT-style UI functions correctly across all user flows, from idea creation to navigation and session management.

## Unit Tests

### Models

**Idea Model**
- Test slug generation is unique and URL-safe
- Test soft delete functionality
- Test relationship with generations
- Test relationship with favorites
- Test metadata JSON casting
- Test scope methods (starred, recent, by session)
- Test title auto-generation from description

**IdeaGeneration Model**
- Test belongs to idea relationship
- Test JSON casting for results and parameters
- Test generation type validation
- Test processing time tracking

**IdeaFavorite Model**
- Test unique constraint on generation + index
- Test cascade delete behavior
- Test JSON result data storage

### Services

**IdeaService**
- Test idea creation with slug generation
- Test duplicate slug handling with timestamp
- Test session association
- Test title extraction from description
- Test idea update operations
- Test soft delete and restore

**SlugGenerator**
- Test basic slug generation
- Test special character handling
- Test uniqueness with timestamp suffix
- Test max length constraints
- Test Unicode character handling

### Helpers

**SessionHelper**
- Test session ID generation
- Test session persistence
- Test session cleanup after expiry

## Integration Tests

### Controllers

**DashboardController**
- Test dashboard loads with focused textarea
- Test displays recent ideas in correct order
- Test empty state for new users
- Test session persistence across requests

**IdeaController**
- Test show page loads with valid slug
- Test 404 for non-existent slug
- Test loads generation history
- Test maintains scroll position
- Test last_accessed_at updates

### Livewire Components

**IdeaSidebar Component**
- Test initial load shows first 20 ideas
- Test infinite scroll loads more ideas
- Test search filters results in real-time
- Test starred items appear first when filtered
- Test maintains selection during navigation
- Test responsive collapse on mobile
- Test keyboard navigation (up/down arrows)

**IdeaCreator Component**
- Test validation for minimum description length
- Test validation for maximum description length
- Test successful idea creation and redirect
- Test rate limiting (10 per minute)
- Test XSS prevention in input
- Test handles special characters properly

**IdeaSession Component**
- Test loads existing idea data
- Test inline title editing
- Test delete confirmation modal
- Test generation trigger integration
- Test favorite toggling
- Test real-time updates via events

## Feature Tests

### End-to-End User Flows

**New User Flow**
```php
test('new user can create first idea and navigate', function () {
    // Visit dashboard
    // Assert textarea is focused
    // Enter idea description
    // Submit form
    // Assert redirect to idea page
    // Assert idea appears in sidebar
    // Click "New Idea" button
    // Assert back on dashboard with empty form
});
```

**Returning User Flow**
```php
test('returning user can access previous ideas', function () {
    // Create multiple ideas in database
    // Visit dashboard
    // Assert sidebar shows all ideas
    // Click on previous idea
    // Assert loads correct idea page
    // Assert generation history is preserved
    // Navigate between ideas via sidebar
});
```

**Idea Management Flow**
```php
test('user can manage ideas lifecycle', function () {
    // Create idea
    // Edit title inline
    // Star idea
    // Generate names
    // Generate logos
    // Add to favorites
    // Delete idea
    // Assert soft deleted
});
```

**Search and Filter Flow**
```php
test('user can search and filter ideas', function () {
    // Create 50+ ideas
    // Type in search box
    // Assert real-time filtering
    // Clear search
    // Scroll to trigger infinite load
    // Assert more ideas load
    // Filter by starred
    // Assert only starred show
});
```

### Performance Tests

**Load Time Tests**
```php
test('dashboard loads under 500ms', function () {
    // Measure dashboard load time
    // Assert under threshold
});

test('sidebar handles 1000+ ideas efficiently', function () {
    // Create 1000 ideas
    // Load dashboard
    // Assert virtual scrolling active
    // Assert initial render under 100ms
});
```

**Concurrent User Tests**
```php
test('handles multiple simultaneous generations', function () {
    // Trigger multiple generations
    // Assert queue processes correctly
    // Assert no race conditions
});
```

## API Tests

### Endpoint Tests

**Ideas API**
```php
test('GET /api/ideas returns paginated results', function () {
    // Create 30 ideas
    // Request first page
    // Assert correct pagination structure
    // Assert 20 items returned
    // Request with search parameter
    // Assert filtered results
});

test('POST /api/ideas creates new idea', function () {
    // Send valid idea data
    // Assert 201 response
    // Assert slug generated
    // Assert in database
    // Send invalid data
    // Assert 422 validation error
});

test('PATCH /api/ideas/{slug} updates idea', function () {
    // Create idea
    // Send update request
    // Assert changes persisted
    // Assert updated_at changed
});

test('DELETE /api/ideas/{slug} soft deletes', function () {
    // Create idea with generations
    // Send delete request
    // Assert soft deleted
    // Assert generations cascaded
});
```

### Rate Limiting Tests
```php
test('enforces rate limits on API endpoints', function () {
    // Send 61 requests in 1 minute
    // Assert 429 on 61st request
    // Wait 1 minute
    // Assert requests work again
});
```

## Browser Tests (Dusk)

### Visual Regression Tests
```php
test('sidebar matches design specifications', function () {
    // Load dashboard
    // Take screenshot
    // Compare with baseline
});

test('responsive design works on mobile', function () {
    // Set mobile viewport
    // Assert sidebar collapsed
    // Click hamburger menu
    // Assert sidebar slides out
    // Assert touch interactions work
});
```

### JavaScript Interaction Tests
```php
test('auto-focus works on page load', function () {
    // Visit dashboard
    // Assert textarea has focus
    // Type without clicking
    // Assert text appears
});

test('keyboard shortcuts function', function () {
    // Press Cmd+K
    // Assert search focused
    // Press Escape
    // Assert search closed
    // Press Cmd+N
    // Assert new idea page
});
```

## Mocking Requirements

### External Services

**AI API Mocking**
- Mock OpenAI API responses for name generation
- Mock DALL-E responses for logo generation
- Mock timeout scenarios
- Mock rate limit responses

**Domain Checking API**
- Mock domain availability responses
- Mock API failures gracefully
- Mock slow response times

### Queue Jobs
- Mock job dispatching in tests
- Assert jobs dispatched with correct data
- Test job failure handling
- Test job retry logic

### Time-Based Tests
- Mock Carbon::now() for time-based features
- Test session expiry behavior
- Test "last accessed" timestamps
- Test created_at ordering

## Test Data Factories

### IdeaFactory
```php
class IdeaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'session_id' => 'test-session-' . $this->faker->uuid,
            'metadata' => ['tags' => $this->faker->words(3)],
            'is_starred' => $this->faker->boolean(20),
            'last_accessed_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
    
    public function starred(): self
    {
        return $this->state(['is_starred' => true]);
    }
    
    public function withGenerations(int $count = 3): self
    {
        return $this->has(IdeaGeneration::factory()->count($count));
    }
}
```

### IdeaGenerationFactory
```php
class IdeaGenerationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'generation_type' => $this->faker->randomElement(['names', 'logos']),
            'input_parameters' => ['mode' => 'creative'],
            'results' => $this->generateSampleResults(),
            'model_used' => 'gpt-4',
            'processing_time_ms' => $this->faker->numberBetween(500, 3000),
        ];
    }
    
    private function generateSampleResults(): array
    {
        // Generate realistic test data
    }
}
```

## Coverage Requirements

- **Unit Tests:** 90% code coverage
- **Integration Tests:** All critical paths covered
- **Feature Tests:** All user stories validated
- **API Tests:** 100% endpoint coverage
- **Performance Tests:** Key metrics validated

## Testing Best Practices

1. Use factories for all test data creation
2. Clean up after tests (database transactions)
3. Test both happy paths and edge cases
4. Mock external services appropriately
5. Keep tests fast and isolated
6. Use descriptive test names
7. Group related tests logically
8. Avoid testing framework code
9. Focus on behavior, not implementation
10. Maintain test documentation