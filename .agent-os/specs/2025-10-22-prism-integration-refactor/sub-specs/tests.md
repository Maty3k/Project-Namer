# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-10-22-prism-integration-refactor/spec.md

> Created: 2025-10-22
> Version: 1.0.0

## Test Coverage

### Unit Tests

**VisionAnalysisService**
- Test image analysis with valid image file
- Test image analysis with multi-modal Prism response
- Test caching behavior (cache hit and cache miss)
- Test error handling when image file doesn't exist
- Test error handling when Prism API fails
- Test JSON parsing of vision analysis response
- Test cache clearing functionality
- Test getImageContextForGeneration with single image
- Test getImageContextForGeneration with multiple images
- Test getImageContextForGeneration with empty array

**OpenAILogoService**
- Test generateLogos for all 4 logo styles
- Test successful logo generation and storage
- Test prompt building with business name and description
- Test DALL-E API call via Prism with correct parameters
- Test image URL extraction from Prism response
- Test image downloading and storage
- Test error handling for failed logo generation
- Test partial success (some styles succeed, others fail)
- Test getAvailableStyles static method

### Integration Tests

**Vision Analysis Integration**
- Test AnalyzeImageWithAIJob dispatches correctly
- Test AnalyzeImageWithAIJob calls VisionAnalysisService
- Test image analysis results stored in database
- Test failed analysis doesn't break the job queue
- Test vision analysis integrates with name generation
- Test AIGenerationService.generateNamesWithContext uses vision data correctly

**Logo Generation Integration**
- Test GenerateLogosJob dispatches correctly
- Test GenerateLogosJob calls OpenAILogoService
- Test logo generation updates LogoGeneration status
- Test all 4 logos generated and stored correctly
- Test logo file paths stored in GeneratedLogo records
- Test failed logo generation marks logo as failed
- Test partial logo generation completion handling

**End-to-End Workflow Tests**
- Test complete image upload → vision analysis → name generation flow
- Test complete logo request → generation → storage flow
- Test image context influences name generation results
- Test vision analysis cache improves performance

### Mocking Requirements

**PrismFake Usage - Vision Analysis**
```php
use EchoLabs\Prism\Testing\PrismFake;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;

// Mock successful vision analysis
PrismFake::fake([
    PrismFake::text()->withResponse([
        'text' => json_encode([
            'description' => 'A modern office space',
            'mood' => 'professional',
            'colors' => ['#FFFFFF', '#000000'],
            'objects' => ['desk', 'computer'],
            'style' => 'modern',
            'business_relevance' => 'Corporate services',
        ]),
    ]),
]);

// Mock API failure
PrismFake::fake([
    PrismFake::text()->throwConnectException(),
]);
```

**PrismFake Usage - Logo Generation**
```php
// Mock successful logo generation
PrismFake::fake([
    PrismFake::image()->withResponse([
        'url' => 'https://example.com/generated-logo.png',
    ]),
]);

// Mock generation failure
PrismFake::fake([
    PrismFake::image()->throwConnectException(),
]);
```

### Test Migration Strategy

**Files to Update:**
1. `tests/Feature/Services/VisionAnalysisServiceTest.php`
   - Replace `Http::fake()` with `PrismFake::fake()`
   - Update assertions for Prism response structure
   - Verify caching logic still works

2. `tests/Feature/Jobs/AnalyzeImageWithAIJobTest.php`
   - Replace HTTP mocks with PrismFake
   - Verify job still handles failures gracefully

3. `tests/Feature/Livewire/LogoGenerationsTest.php` (if exists)
   - Replace HTTP mocks with PrismFake
   - Update for Prism response structure

4. `tests/Feature/Livewire/LogoGalleryTest.php`
   - Update any logo generation mocks
   - Verify UI still displays correctly

5. `tests/Feature/Feature/AIVisionIntegrationTest.php`
   - Update all vision analysis mocks
   - Verify name generation integration

6. `tests/Feature/Integration/CompleteUserWorkflowTest.php` (if includes vision/logo)
   - Update end-to-end mocks for Prism

### New Test Cases

**VisionAnalysisService with Prism**
```php
test('uses Prism for vision analysis', function () {
    PrismFake::fake([
        PrismFake::text()->withResponse([
            'text' => json_encode([
                'description' => 'Test description',
                'mood' => 'test mood',
                'colors' => ['#FF0000'],
                'objects' => ['test object'],
                'style' => 'test style',
                'business_relevance' => 'test relevance',
            ]),
        ]),
    ]);

    $image = ProjectImage::factory()->create();
    $service = app(VisionAnalysisService::class);
    $result = $service->analyzeImage($image);

    expect($result)->toBeArray()
        ->and($result['description'])->toBe('Test description')
        ->and($result['mood'])->toBe('test mood');
});
```

**OpenAILogoService with Prism**
```php
test('uses Prism for logo generation', function () {
    PrismFake::fake([
        PrismFake::image()->withResponse([
            'url' => 'https://example.com/logo.png',
        ]),
    ]);

    Http::fake([
        'example.com/*' => Http::response('fake-image-content', 200),
    ]);

    $logoGeneration = LogoGeneration::factory()->create();
    $service = app(OpenAILogoService::class);

    $service->generateLogos($logoGeneration);

    expect($logoGeneration->fresh()->status)->toBe('completed')
        ->and($logoGeneration->logos_completed)->toBe(4);
});
```

**Error Handling Tests**
```php
test('handles Prism vision API errors gracefully', function () {
    PrismFake::fake([
        PrismFake::text()->throwConnectException(),
    ]);

    $image = ProjectImage::factory()->create();
    $service = app(VisionAnalysisService::class);

    expect(fn() => $service->analyzeImage($image))
        ->toThrow(Exception::class);
});

test('handles Prism image generation errors gracefully', function () {
    PrismFake::fake([
        PrismFake::image()->throwConnectException(),
    ]);

    $logoGeneration = LogoGeneration::factory()->create();
    $service = app(OpenAILogoService::class);

    $service->generateLogos($logoGeneration);

    $failedLogos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
        ->where('status', 'failed')
        ->count();

    expect($failedLogos)->toBe(4);
});
```

### Coverage Requirements

- **Minimum Line Coverage:** 90%
- **Branch Coverage:** 85%
- **Critical Paths:** 100% (API calls, error handling, caching)

### Test Execution

**Run specific test suites:**
```bash
# Vision analysis tests
php artisan test --filter=VisionAnalysisService

# Logo generation tests
php artisan test --filter=LogoGeneration

# Integration tests
php artisan test --filter=AIVisionIntegration

# All affected tests
php artisan test --filter="VisionAnalysis|LogoGeneration|AIVision"
```

### Regression Testing

**Verify no regressions in:**
1. Existing name generation tests (should still pass)
2. Existing image upload tests (should still pass)
3. Existing Prism-based services tests (should still pass)
4. End-to-end workflow tests (should still pass)

### Performance Testing

**Benchmarks to maintain:**
- Vision analysis response time: < 5 seconds
- Logo generation per style: < 10 seconds
- Cache retrieval: < 100ms

**Test cache effectiveness:**
```php
test('vision analysis caching reduces API calls', function () {
    PrismFake::fake([
        PrismFake::text()->withResponse([
            'text' => json_encode(['description' => 'Cached result']),
        ]),
    ]);

    $image = ProjectImage::factory()->create();
    $service = app(VisionAnalysisService::class);

    // First call hits API
    $result1 = $service->analyzeImage($image);

    // Second call should use cache
    $result2 = $service->analyzeImage($image);

    expect($result1)->toEqual($result2);
    // Verify only 1 Prism call was made
});
```

## Test Quality Checklist

- [ ] All HTTP mocks replaced with PrismFake
- [ ] All tests pass with PrismFake implementations
- [ ] Error scenarios covered
- [ ] Cache behavior verified
- [ ] Integration tests updated
- [ ] No test flakiness introduced
- [ ] Test execution time not significantly increased
- [ ] Coverage maintained or improved
- [ ] Edge cases covered (empty responses, malformed data, etc.)
