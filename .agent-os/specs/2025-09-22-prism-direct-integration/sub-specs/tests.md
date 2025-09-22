# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-09-22-prism-direct-integration/spec.md

> Created: 2025-09-22
> Version: 1.0.0

## Test Coverage

### Unit Tests

**AIGenerationService**
- Test name generation with different providers using Prism::fake()
- Test prompt building functionality
- Test caching integration with GenerationCache
- Test error handling and exception mapping
- Test fallback provider logic

**PromptBuilder Service (New)**
- Test system prompt generation for different modes
- Test user prompt building with business analysis
- Test mode-specific optimizations
- Test deep thinking prompt enhancements

### Integration Tests

**Name Generation Workflow**
- Test complete name generation flow with Prism integration
- Test multiple provider fallback scenarios
- Test caching behavior with real Prism calls
- Test error recovery and retry logic

**Livewire Component Integration**
- Test NameGeneratorDashboard with Prism::fake()
- Test real-time name generation updates
- Test error state handling in UI
- Test progress tracking and user feedback

**Background Job Processing**
- Test ProcessAIGenerationBatch with Prism mocking
- Test GenerateNamesWithModelJob with different providers
- Test job failure handling and retry logic
- Test concurrent job processing

### Feature Tests

**End-to-End Name Generation**
- Test complete user workflow from input to results
- Test different generation modes (creative, professional, brandable, tech-focused)
- Test deep thinking mode functionality
- Test session management and result persistence

**Multi-Provider Scenarios**
- Test provider switching and fallback
- Test provider-specific optimizations
- Test mixed provider batch processing
- Test provider availability checking

### Mocking Requirements

**Prism Testing Strategy**
- Use `Prism::fake([TextResponseFake::make()->withText($response)])` for all AI mocking
- Remove all HTTP::fake() usage from AI-related tests
- Create reusable test response fixtures for different scenarios
- Mock provider failures with exception throwing

**Test Response Fixtures**
- Creative mode responses (unique, artistic names)
- Professional mode responses (corporate, trustworthy names)
- Brandable mode responses (catchy, memorable names)
- Tech-focused mode responses (developer-friendly names)
- Error responses for failure testing

### Performance Tests

**Response Time Validation**
- Test that direct Prism usage maintains or improves response times
- Benchmark against current PrismAIService performance
- Test concurrent request handling
- Test memory usage optimization

### Migration Tests

**Compatibility Testing**
- Ensure existing cached results remain valid
- Test that name generation output remains consistent
- Verify that all existing test scenarios still pass
- Validate that error handling behavior is equivalent

## Test Structure Changes

### Files to Update

1. `tests/Feature/Services/AIGenerationServiceTest.php` - Replace PrismAIService mocking
2. `tests/Feature/Livewire/DashboardComponentTest.php` - Update to use Prism::fake()
3. `tests/Feature/Integration/AIWorkflowIntegrationTest.php` - Remove PrismAIService references
4. `tests/Feature/Volt/NameGeneratorComponentTest.php` - Update Prism mocking approach

### Files to Delete

1. `tests/Feature/Services/PrismAIServiceTest.php` - No longer needed with direct Prism usage

### New Test Files

1. `tests/Feature/Services/PromptBuilderTest.php` - Test extracted prompt building logic
2. `tests/Feature/Services/PrismIntegrationTest.php` - Test direct Prism usage patterns

## Test Execution Strategy

### Pre-Migration Testing
- Run full test suite to establish baseline
- Document current test coverage metrics
- Identify any flaky tests related to AI services

### During Migration Testing
- Test each component individually after conversion
- Maintain green test suite throughout migration
- Add temporary integration tests if needed

### Post-Migration Validation
- Verify 100% test pass rate
- Confirm test coverage maintains or improves
- Performance test comparison with before/after metrics
- End-to-end functionality verification

## Mock Data Strategy

### Consistent Test Responses
```php
// Example test fixture
const CREATIVE_MODE_RESPONSE = "1. CreativeFlow\n2. InnovateLab\n3. BrightSpark\n4. FlowForge\n5. NextLevel\n6. ThinkTank\n7. LaunchPad\n8. StreamLine\n9. VisionCraft\n10. IdeaForge";
```

### Error Simulation
```php
// Provider failure simulation
Prism::fake([
    new \Exception('Rate limit exceeded'),
]);
```

### Response Validation
- Ensure all test responses follow expected format (numbered list)
- Validate response parsing logic works correctly
- Test edge cases like malformed responses
- Verify error message consistency