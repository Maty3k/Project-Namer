# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-09-22-prism-direct-integration/spec.md

> Created: 2025-09-22
> Version: 1.0.0

## Technical Requirements

### Prism Direct Usage Pattern

All AI generation calls should follow this pattern:
```php
$response = Prism::text()
    ->using(Provider::OpenAI, 'gpt-4o')
    ->withSystemPrompt($systemPrompt)
    ->withPrompt($userPrompt)
    ->withClientOptions([
        'max_tokens' => 200,
        'temperature' => 0.7,
    ])
    ->asText();
```

### Provider Configuration

- Use Provider enum instead of string mappings
- Maintain existing model configurations but apply them through Prism's withClientOptions()
- Support for fallback providers through try-catch blocks around individual Prism calls

### Testing Requirements

- Replace all PrismAIService mocking with Prism::fake()
- Use TextResponseFake::make()->withText() for consistent responses
- Maintain existing test scenarios and coverage levels
- Remove HTTP::fake() usage from AI-related tests

### Error Handling

- Use Prism's native exception handling
- Map Prism exceptions to application-specific exceptions where needed
- Maintain existing retry logic but implement at the calling code level
- Preserve error categorization for user-facing error messages

## Approach Options

**Option A:** Gradual Migration
- Pros: Lower risk, incremental testing
- Cons: Temporary code duplication, longer timeline

**Option B:** Complete Replacement (Selected)
- Pros: Clean cut, no temporary code duplication, faster timeline
- Cons: Higher risk, requires comprehensive testing

**Rationale:** Option B is selected because the existing PrismAIService is not heavily integrated and the application already has good test coverage. A complete replacement will result in cleaner code and leverage Prism's full capabilities immediately.

## External Dependencies

No new dependencies required. The existing Prism library (echolabsdev/prism v0.85.0) provides all necessary functionality.

## Implementation Strategy

### Phase 1: Core Service Replacement
1. Update AIGenerationService to use Prism directly
2. Remove PrismAIService dependency injections
3. Update related tests

### Phase 2: Component Updates
1. Update NameGeneratorDashboard Livewire component
2. Update background job classes
3. Remove PrismAIService references

### Phase 3: Cleanup
1. Delete PrismAIService classes
2. Delete PrismAIService tests
3. Update any remaining references

## Model Configuration Mapping

Current PrismAIService MODEL_CONFIGS will be replaced with direct Prism usage:

```php
// Instead of custom model configs, use:
$providers = [
    'gpt-4' => ['provider' => Provider::OpenAI, 'model' => 'gpt-4o'],
    'claude-3.5-sonnet' => ['provider' => Provider::Anthropic, 'model' => 'claude-3-5-sonnet-20241022'],
    'gemini-1.5-pro' => ['provider' => Provider::Gemini, 'model' => 'gemini-1.5-pro'],
    'grok-beta' => ['provider' => Provider::XAI, 'model' => 'grok-beta'],
];
```

## Prompt Optimization

Existing prompt building logic from PrismAIService will be extracted into a dedicated PromptBuilder service to maintain the sophisticated prompt engineering while using Prism directly.

## Caching Strategy

Maintain existing GenerationCache model and approach. Only the AI service layer changes, not the caching mechanism.