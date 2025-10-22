# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-22-prism-integration-refactor/spec.md

> Created: 2025-10-22
> Version: 1.0.0

## Technical Requirements

### 1. VisionAnalysisService Refactor

**Current Implementation:**
- Uses Laravel's `Http::withHeaders()` to make direct POST requests to `https://api.openai.com/v1/chat/completions`
- Manually encodes images as base64
- Manually constructs multimodal message arrays
- Uses `config('ai.openai_api_key')` for authentication

**Target Implementation:**
- Use `Prism::text()` with multi-modal input support
- Use `Image::fromLocalPath()` to handle image encoding automatically
- Let Prism handle authentication via `config('prism.providers.openai.api_key')`
- Maintain existing caching and error handling logic

**Code Changes Required:**
- Replace HTTP facade calls with Prism text generation
- Replace base64 encoding with `Image::fromLocalPath()`
- Update authentication to use Prism configuration
- Update error handling to catch Prism exceptions

### 2. OpenAILogoService Refactor

**Current Implementation:**
- Uses Laravel's `Http::withHeaders()` to POST to `https://api.openai.com/v1/images/generations`
- Manually constructs request payload with model, prompt, size, etc.
- Manually handles authentication with API key
- Downloads images from URLs and saves to storage

**Target Implementation:**
- Use `Prism::image()` API for DALL-E 2 generation
- Let Prism handle provider configuration and authentication
- Use Prism's response object methods (`firstImage()`, `images`, etc.)
- Maintain existing image downloading and storage logic

**Code Changes Required:**
- Replace HTTP facade calls with Prism image generation
- Replace manual payload construction with Prism's fluent API
- Update authentication to use Prism configuration
- Update response parsing to use Prism's response object

### 3. Configuration Migration

**Current State:**
- `config/ai.php` contains OpenAI API configuration
- Some services reference `config('ai.openai_api_key')`
- `config/prism.php` already exists with provider configuration

**Target State:**
- All OpenAI configuration consolidated in `config/prism.php`
- Remove redundant configuration from `config/ai.php`
- All services reference Prism configuration only

**Migration Steps:**
1. Audit all configuration references
2. Ensure `config/prism.php` has all necessary keys
3. Update service constructors and configuration references
4. Remove unused configuration entries
5. Update environment variable documentation if needed

### 4. Dependency Injection Updates

**VisionAnalysisService:**
- Remove manual API key injection in constructor
- Prism will handle authentication automatically

**OpenAILogoService:**
- Remove `$apiKey` property and constructor parameter
- Prism will handle authentication automatically

## Approach Options

### Option A: Incremental Refactor (Selected)
**Description:** Refactor one service at a time, starting with VisionAnalysisService, then OpenAILogoService.

**Pros:**
- Lower risk - can test each service independently
- Easier to identify and fix issues
- Can deploy incrementally if needed
- Better for code review

**Cons:**
- Takes slightly longer overall
- Temporary inconsistency during transition

**Rationale:** This approach minimizes risk and allows for thorough testing at each step. Given that these services have different responsibilities (vision analysis vs image generation), refactoring separately makes sense.

### Option B: All-at-Once Refactor
**Description:** Refactor both services simultaneously in a single PR.

**Pros:**
- Faster overall completion
- Single deployment event
- Consistent state throughout

**Cons:**
- Higher risk if issues arise
- Harder to isolate problems
- Larger PR to review
- If one service fails, both are blocked

## External Dependencies

None - Prism is already installed and configured in the application.

## Technical Decisions

### Multi-Modal Input for Vision Analysis

**Decision:** Use `Image::fromLocalPath()` with temporary file storage.

**Rationale:**
- The current implementation already reads images from disk (`Storage::disk('public')->path($image->file_path)`)
- `Image::fromLocalPath()` is the recommended Prism approach for local files
- No need to maintain base64 encoding logic
- Cleaner, more readable code

**Implementation:**
```php
$imagePath = Storage::disk('public')->path($image->file_path);

$response = Prism::text()
    ->using(Provider::OpenAI, 'gpt-4o')
    ->withPrompt(
        $this->buildAnalysisPrompt(),
        [Image::fromLocalPath($imagePath)]
    )
    ->withClientOptions([
        'max_tokens' => 500,
        'temperature' => 0.3,
    ])
    ->asText();
```

### Image Generation with DALL-E

**Decision:** Use DALL-E 2 via Prism's image generation API.

**Rationale:**
- Already using DALL-E 2 in current implementation
- Prism fully supports DALL-E 2 and DALL-E 3
- Can easily upgrade to DALL-E 3 later if desired
- Maintains existing behavior and cost structure

**Implementation:**
```php
$response = Prism::image()
    ->using(Provider::OpenAI, 'dall-e-2')
    ->withPrompt($prompt)
    ->withClientOptions([
        'n' => 1,
        'size' => '256x256',
        'response_format' => 'url',
    ])
    ->generate();

$imageUrl = $response->firstImage()->url;
```

### Error Handling Strategy

**Decision:** Map Prism exceptions to application-specific exceptions.

**Rationale:**
- Maintains existing error handling patterns
- Doesn't break existing error logging or monitoring
- Allows for Prism-specific error categorization

**Implementation:**
```php
try {
    $response = Prism::image()->...->generate();
} catch (\Prism\Exceptions\PrismException $e) {
    // Map to LogoGenerationException or return graceful error
    throw new LogoGenerationException(
        'Logo generation failed: ' . $e->getMessage()
    );
}
```

### Testing Strategy

**Decision:** Use `PrismFake` for all AI service tests.

**Rationale:**
- Built-in Prism testing utilities
- Consistent mocking across all AI services
- Eliminates need for HTTP facade mocks
- More reliable and maintainable tests

**Implementation:**
```php
use EchoLabs\Prism\Testing\PrismFake;

test('analyzes image with vision API', function () {
    PrismFake::fake([
        PrismFake::text()->withResponse([
            'text' => json_encode([
                'description' => 'A modern office space',
                'mood' => 'professional',
                // ... more fields
            ]),
        ]),
    ]);

    $service = app(VisionAnalysisService::class);
    $result = $service->analyzeImage($image);

    expect($result)->toBeArray()
        ->and($result['description'])->toBe('A modern office space');
});
```

## Performance Considerations

### Caching
- Maintain existing caching strategy in VisionAnalysisService (1-hour cache)
- No changes needed to caching logic, only the API call mechanism

### Image Processing
- Prism's `Image::fromLocalPath()` may perform some internal optimizations
- No significant performance impact expected
- Image downloading and storage logic remains unchanged

### Rate Limiting
- Prism respects provider rate limits
- Existing retry logic should be maintained
- No changes to rate limiting strategy needed

## Security Considerations

### API Key Management
- API keys moved to `config/prism.php` (better centralization)
- Environment variables remain the same (`OPENAI_API_KEY`)
- No changes to .env or deployment configuration needed

### Image Data Handling
- Images still stored locally before analysis (no security change)
- No sensitive data logged (maintain existing logging practices)
- Prism handles secure transmission to OpenAI

## Migration Path

### Phase 1: VisionAnalysisService
1. Update service implementation to use Prism
2. Update related job (AnalyzeImageWithAIJob) if needed
3. Update all tests to use PrismFake
4. Verify caching still works correctly
5. Deploy and monitor

### Phase 2: OpenAILogoService
1. Update service implementation to use Prism
2. Update related job (GenerateLogosJob) if needed
3. Update all tests to use PrismFake
4. Verify logo generation and storage works
5. Deploy and monitor

### Phase 3: Cleanup
1. Remove unused HTTP facade code
2. Clean up configuration files
3. Update documentation
4. Verify all tests pass

## Rollback Plan

If issues arise:
1. Revert the specific service commit
2. All existing tests should still pass (if done incrementally)
3. No database migrations involved, so rollback is safe
4. Monitor error logs for Prism-specific issues

## Success Criteria

1. All tests passing with PrismFake mocks
2. Vision analysis produces identical results to current implementation
3. Logo generation produces identical results to current implementation
4. No increase in error rates or API failures
5. Documentation updated to reflect 100% Prism usage
6. Code coverage maintained or improved
