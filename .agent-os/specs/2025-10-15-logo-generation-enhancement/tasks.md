# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-10-15-logo-generation-enhancement/spec.md

> Created: 2025-10-15
> Status: Ready for Implementation

## Tasks

- [ ] 1. Update Logo Generation Service
  - [ ] 1.1 Write tests for OpenAILogoService with 512x512 resolution
  - [ ] 1.2 Update OpenAILogoService::makeApiRequest() to use '512x512' size
  - [ ] 1.3 Update OpenAILogoService::DALLE_3_COST_CENTS to 160 (verify pricing first)
  - [ ] 1.4 Investigate Prism integration for image generation API calls
  - [ ] 1.5 Implement Prism integration if feasible, otherwise document decision
  - [ ] 1.6 Run OpenAILogoService tests and verify all pass

- [ ] 2. Update Logo Generation Job
  - [ ] 2.1 Write tests for GenerateLogosJob with 5-logo generation
  - [ ] 2.2 Update GenerateLogosJob to generate exactly 5 logos
  - [ ] 2.3 Implement style distribution logic (2 of one style, 1 each of others)
  - [ ] 2.4 Update progress tracking for 5-logo generation
  - [ ] 2.5 Verify cost calculation sums correctly for 5 logos
  - [ ] 2.6 Run GenerateLogosJob tests and verify all pass

- [ ] 3. Update All Existing Tests
  - [ ] 3.1 Search codebase for tests expecting 12 logos
  - [ ] 3.2 Update test assertions to expect 5 logos
  - [ ] 3.3 Update cost calculation assertions to use new pricing
  - [ ] 3.4 Update factory data if needed for 5-logo scenarios
  - [ ] 3.5 Run full test suite and fix any failures
  - [ ] 3.6 Verify all logo-related tests pass

- [ ] 4. Verify UI Components
  - [ ] 4.1 Check NameResultCard for hardcoded 12-logo references
  - [ ] 4.2 Check logo progress components for logo count references
  - [ ] 4.3 Update any UI text or tooltips referencing logo counts
  - [ ] 4.4 Test logo gallery display with 5 logos in browser
  - [ ] 4.5 Verify color customization works with 5 logos
  - [ ] 4.6 Verify batch download works with 5 logos

- [ ] 5. Integration Testing & Documentation
  - [ ] 5.1 Run complete end-to-end test in development environment
  - [ ] 5.2 Generate logos and verify exactly 5 are created
  - [ ] 5.3 Verify cost tracking shows correct amounts
  - [ ] 5.4 Test all existing features (color, export, gallery)
  - [ ] 5.5 Update any inline code comments referencing old logo counts
  - [ ] 5.6 Run full test suite and verify all tests pass
