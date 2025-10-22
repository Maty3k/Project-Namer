# Spec Requirements Document

> Spec: Prism Integration Refactor
> Created: 2025-10-22
> Status: Planning

## Overview

Refactor all LLM API interactions to use Prism consistently across the application. Currently, VisionAnalysisService and OpenAILogoService use direct HTTP calls to OpenAI's API, while name generation services use Prism. This refactoring will unify all LLM interactions under Prism, improving code consistency, maintainability, and testability.

## User Stories

### Consistent AI Integration
As a developer, I want all LLM API calls to use Prism, so that the codebase has a consistent approach to AI service integration with unified error handling and configuration.

**Detailed Workflow:** Developer works with any AI service in the application and finds consistent Prism usage patterns, making it easier to understand, maintain, and extend AI functionality across all features.

### Improved Testability
As a developer, I want to use Prism's built-in testing utilities, so that I can easily mock AI responses in tests without creating custom HTTP mocks for each service.

**Detailed Workflow:** Developer writes tests for AI features and uses `PrismFake` to mock responses consistently across vision analysis, logo generation, and name generation, reducing test complexity and improving reliability.

### Simplified Configuration
As a developer, I want all AI provider configuration centralized in Prism's config, so that API keys and provider settings are managed in one place.

**Detailed Workflow:** Developer updates AI provider credentials or configuration and only needs to modify `config/prism.php`, with all services automatically using the updated settings.

## Spec Scope

1. **VisionAnalysisService Refactor** - Replace direct HTTP calls with Prism's multi-modal input API
2. **OpenAILogoService Refactor** - Replace direct HTTP calls with Prism's image generation API
3. **Configuration Migration** - Consolidate API key management into Prism configuration
4. **Test Suite Updates** - Update all tests to use PrismFake instead of HTTP mocks
5. **Documentation Updates** - Update docs/llms.md to reflect 100% Prism usage

## Out of Scope

- Changes to existing Prism-based services (AIGenerationService, OpenAINameService)
- Functionality changes or feature additions
- UI/UX modifications
- Performance optimizations beyond those inherent to Prism

## Expected Deliverable

1. VisionAnalysisService fully migrated to Prism with multi-modal support
2. OpenAILogoService fully migrated to Prism's image generation API
3. All tests passing with PrismFake implementations
4. Updated documentation showing 100% Prism coverage
5. Removed redundant configuration from config/ai.php (if applicable)

## Spec Documentation

- Tasks: @.agent-os/specs/2025-10-22-prism-integration-refactor/tasks.md
- Technical Specification: @.agent-os/specs/2025-10-22-prism-integration-refactor/sub-specs/technical-spec.md
- Tests Specification: @.agent-os/specs/2025-10-22-prism-integration-refactor/sub-specs/tests.md
