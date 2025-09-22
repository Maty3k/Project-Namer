# Spec Requirements Document

> Spec: Replace PrismAIService with Direct Prism Integration
> Created: 2025-09-22
> Status: Planning

## Overview

Replace the custom PrismAIService wrapper class with direct usage of the Prism library throughout the application. This will eliminate unnecessary abstraction layers and leverage Prism's built-in testing affordances, provider management, and error handling capabilities.

## User Stories

### Simplified AI Integration

As a developer, I want to use Prism directly without custom wrapper services, so that I can leverage all of Prism's native features including testing fakes, provider switching, and built-in error handling.

**Detailed Workflow:**
- Remove the PrismAIService class that acts as an unnecessary abstraction layer
- Update all components to use Prism::text() directly with appropriate providers and models
- Utilize Prism's native provider enum system for model configuration
- Leverage Prism's built-in testing fakes instead of custom HTTP mocking

### Enhanced Testing Experience

As a developer writing tests, I want to use Prism's native testing affordances, so that I can easily mock AI responses without complex HTTP interception or custom service mocking.

**Detailed Workflow:**
- Replace custom PrismAIService mocking with Prism::fake()
- Use TextResponseFake::make()->withText() for consistent test responses
- Eliminate HTTP::fake() usage in AI-related tests
- Simplify test setup and improve test reliability

### Better Provider Management

As a developer, I want to use Prism's native provider system, so that I can easily switch between AI providers and models without custom configuration mappings.

**Detailed Workflow:**
- Use Provider enum directly (Provider::OpenAI, Provider::Anthropic, etc.)
- Configure models through Prism's native withClientOptions() method
- Remove custom model configuration mappings from PrismAIService
- Leverage Prism's provider-specific optimizations

## Spec Scope

1. **Remove PrismAIService Classes** - Delete both PrismAIService.php files and their corresponding tests
2. **Update Livewire Components** - Modify NameGeneratorDashboard to use Prism directly
3. **Refactor Background Jobs** - Update ProcessAIGenerationBatch and GenerateNamesWithModelJob to use Prism directly
4. **Update Service Classes** - Modify AIGenerationService to use Prism instead of PrismAIService
5. **Replace All Tests** - Update all tests to use Prism::fake() instead of custom mocking
6. **Remove Custom Error Handling** - Replace custom error categorization with Prism's native error handling
7. **Simplify Provider Configuration** - Use Prism's Provider enum instead of custom string mappings

## Out of Scope

- Changes to domain availability checking functionality
- Modifications to logo generation features
- Updates to session management functionality that doesn't involve AI
- Changes to caching strategy (will maintain existing GenerationCache approach)

## Expected Deliverable

1. **Complete PrismAIService Removal** - Both PrismAIService classes are deleted and no longer referenced
2. **Functional AI Generation** - All name generation functionality works identically using direct Prism integration
3. **Comprehensive Test Coverage** - All tests pass using Prism::fake() and maintain equivalent test scenarios
4. **Simplified Codebase** - Reduced complexity with elimination of unnecessary abstraction layer
5. **Enhanced Error Handling** - Better error messages and handling through Prism's native capabilities

## Spec Documentation

- Tasks: @.agent-os/specs/2025-09-22-prism-direct-integration/tasks.md
- Technical Specification: @.agent-os/specs/2025-09-22-prism-direct-integration/sub-specs/technical-spec.md
- Tests Specification: @.agent-os/specs/2025-09-22-prism-direct-integration/sub-specs/tests.md
- File Manifest: @.agent-os/specs/2025-09-22-prism-direct-integration/sub-specs/file-manifest.md