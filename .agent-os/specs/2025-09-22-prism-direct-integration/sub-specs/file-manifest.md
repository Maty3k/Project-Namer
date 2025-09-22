# File Manifest

This document details all files that will be added, modified, or removed during the Prism direct integration migration.

> Created: 2025-09-22
> Version: 1.0.0

## Files to be REMOVED

### Service Classes
- `app/Services/PrismAIService.php` - Main PrismAIService wrapper (815 lines)
- `app/Services/AI/PrismAIService.php` - Alternative PrismAIService implementation (376 lines)
- `app/Services/AI/PrismAIService.php.backup` - Backup file (no longer needed)

### Test Files
- `tests/Feature/Services/PrismAIServiceTest.php` - Tests for wrapper service

## Files to be ADDED

### New Service Classes
- `app/Services/PromptBuilder.php` - Extracted prompt building logic
- `tests/Feature/Services/PromptBuilderTest.php` - Tests for PromptBuilder
- `tests/Feature/Services/PrismIntegrationTest.php` - Integration tests for direct Prism usage

## Files to be MODIFIED

### Core Service Classes
- `app/Services/AI/AIGenerationService.php` - Replace PrismAIService usage with direct Prism calls
- `app/Services/AIGenerationService.php` - Update if it exists and uses PrismAIService

### Livewire Components
- `app/Livewire/NameGeneratorDashboard.php` - Remove PrismAIService dependency injection
- `resources/views/livewire/name-generator.blade.php` - Update if it contains PrismAIService references

### Background Jobs
- `app/Jobs/ProcessAIGenerationBatch.php` - Replace PrismAIService with direct Prism usage
- `app/Jobs/GenerateNamesWithModelJob.php` - Replace PrismAIService with direct Prism usage

### Test Files (Converting to Prism::fake())
- `tests/Feature/Services/AIGenerationServiceTest.php` - Replace PrismAIService mocking with Prism::fake()
- `tests/Feature/Integration/AIWorkflowIntegrationTest.php` - Update to use Prism::fake()
- `tests/Feature/Livewire/DashboardComponentTest.php` - Convert to Prism::fake()
- `tests/Feature/Volt/NameGeneratorComponentTest.php` - Update Prism mocking approach
- `tests/Feature/Services/SessionManagerTest.php` - Update if it uses PrismAIService
- `tests/Feature/Feature/AIVisionIntegrationTest.php` - Update if it uses PrismAIService

## Import Statement Changes

### Files with PrismAIService imports to remove:
- `app/Livewire/NameGeneratorDashboard.php`
- `app/Jobs/ProcessAIGenerationBatch.php`
- `app/Jobs/GenerateNamesWithModelJob.php`
- `app/Services/AI/AIGenerationService.php`
- `tests/Feature/Services/AIGenerationServiceTest.php`
- `tests/Feature/Integration/AIWorkflowIntegrationTest.php`

### New Prism imports to add:
```php
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\Testing\TextResponseFake; // In test files
```

## Configuration Files

### No Changes Required
- `composer.json` - Prism dependency already exists
- Configuration files - No Prism-specific config needed
- Environment files - Existing AI API keys will work with Prism

## Migration Impact Summary

### Lines of Code Changes
- **Removed:** ~1,200 lines (PrismAIService classes and tests)
- **Added:** ~400 lines (PromptBuilder service and tests)
- **Modified:** ~800 lines (updated service usage and test conversions)
- **Net Reduction:** ~400 lines of code

### Complexity Reduction
- Eliminates custom provider mapping logic
- Removes custom error categorization system
- Simplifies testing with native Prism fakes
- Reduces maintenance burden of wrapper service

### Risk Assessment
- **Low Risk:** Prism is already used successfully in OpenAINameService
- **Good Test Coverage:** Existing tests will be converted to maintain coverage
- **Gradual Approach:** Can test each component individually during migration
- **Rollback Option:** PrismAIService files can be temporarily retained during migration

## Testing Strategy

### Pre-Migration Baseline
1. Run full test suite and record results
2. Document current performance metrics
3. Test manual workflows to establish expected behavior

### During Migration
1. Convert one service/component at a time
2. Run tests after each conversion
3. Maintain green test suite throughout process

### Post-Migration Validation
1. Full test suite execution
2. Performance comparison testing
3. Manual workflow verification
4. Code quality tool validation (PHPStan, Pint)

## Dependencies

### Required for Implementation
- Prism library (already installed: echolabsdev/prism v0.85.0)
- No additional dependencies needed

### Provider API Keys
- Existing API keys in .env will work with Prism
- No changes to API key configuration required