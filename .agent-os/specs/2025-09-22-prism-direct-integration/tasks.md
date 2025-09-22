# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-09-22-prism-direct-integration/spec.md

> Created: 2025-09-22
> Status: Ready for Implementation

## Tasks

- [ ] 1. Create PromptBuilder Service and Extract Prompt Logic
  - [ ] 1.1 Write tests for PromptBuilder service
  - [ ] 1.2 Create PromptBuilder service class
  - [ ] 1.3 Extract prompt building methods from PrismAIService
  - [ ] 1.4 Add system prompt generation for all modes
  - [ ] 1.5 Add user prompt building with business analysis
  - [ ] 1.6 Add prompt optimization for deep thinking mode
  - [ ] 1.7 Verify all PromptBuilder tests pass

- [ ] 2. Update AIGenerationService to Use Prism Directly
  - [ ] 2.1 Write tests for updated AIGenerationService
  - [ ] 2.2 Remove PrismAIService dependency injection
  - [ ] 2.3 Add direct Prism::text() calls with Provider enum
  - [ ] 2.4 Implement provider configuration mapping
  - [ ] 2.5 Add fallback provider logic using try-catch
  - [ ] 2.6 Update error handling to map Prism exceptions
  - [ ] 2.7 Integrate PromptBuilder service
  - [ ] 2.8 Verify all AIGenerationService tests pass

- [ ] 3. Update NameGeneratorDashboard Livewire Component
  - [ ] 3.1 Write tests for updated dashboard component
  - [ ] 3.2 Remove PrismAIService dependency injection
  - [ ] 3.3 Update component to use AIGenerationService directly
  - [ ] 3.4 Update any direct AI service calls to use new pattern
  - [ ] 3.5 Verify all dashboard component tests pass

- [ ] 4. Update Background Job Classes
  - [ ] 4.1 Write tests for updated job classes
  - [ ] 4.2 Update ProcessAIGenerationBatch to use Prism directly
  - [ ] 4.3 Update GenerateNamesWithModelJob to use Prism directly
  - [ ] 4.4 Remove PrismAIService dependencies from jobs
  - [ ] 4.5 Update job error handling for Prism exceptions
  - [ ] 4.6 Verify all job tests pass

- [ ] 5. Update All Test Files to Use Prism::fake()
  - [ ] 5.1 Update tests/Feature/Services/AIGenerationServiceTest.php
  - [ ] 5.2 Update tests/Feature/Livewire/DashboardComponentTest.php
  - [ ] 5.3 Update tests/Feature/Integration/AIWorkflowIntegrationTest.php
  - [ ] 5.4 Update tests/Feature/Volt/NameGeneratorComponentTest.php
  - [ ] 5.5 Remove HTTP::fake() usage from AI-related tests
  - [ ] 5.6 Create test response fixtures for consistent mocking
  - [ ] 5.7 Verify all updated tests pass

- [ ] 6. Remove PrismAIService Classes and Tests
  - [ ] 6.1 Verify no remaining references to PrismAIService
  - [ ] 6.2 Delete app/Services/PrismAIService.php
  - [ ] 6.3 Delete app/Services/AI/PrismAIService.php
  - [ ] 6.4 Delete tests/Feature/Services/PrismAIServiceTest.php
  - [ ] 6.5 Remove any import statements for PrismAIService
  - [ ] 6.6 Verify all tests still pass after deletion

- [ ] 7. Final Testing and Validation
  - [ ] 7.1 Run complete test suite to ensure no regressions
  - [ ] 7.2 Test manual name generation workflow in browser
  - [ ] 7.3 Test different AI providers and modes
  - [ ] 7.4 Test error handling scenarios
  - [ ] 7.5 Verify caching functionality still works
  - [ ] 7.6 Run code quality checks (PHPStan, Pint)
  - [ ] 7.7 Confirm all tests pass and code quality is maintained