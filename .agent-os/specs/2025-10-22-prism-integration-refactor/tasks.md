# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-10-22-prism-integration-refactor/spec.md

> Created: 2025-10-22
> Status: ✅ Completed

## Tasks

- [x] 1. Refactor VisionAnalysisService to use Prism
  - [x] 1.1 Write tests for VisionAnalysisService with PrismFake mocks
  - [x] 1.2 Update analyzeImage() method to use Prism::text() with multi-modal input
  - [x] 1.3 Replace base64 encoding with Image::fromLocalPath()
  - [x] 1.4 Update buildAnalysisPrompt() to work with Prism structure
  - [x] 1.5 Update error handling to catch Prism exceptions
  - [x] 1.6 Verify caching logic still works correctly
  - [x] 1.7 Update analyzeImageWithContext() for consistency
  - [x] 1.8 Update getImageContextForGeneration() if needed
  - [x] 1.9 Verify all VisionAnalysisService tests pass

- [x] 2. Update AnalyzeImageWithAIJob for Prism compatibility
  - [x] 2.1 Write/update tests for AnalyzeImageWithAIJob with PrismFake
  - [x] 2.2 Verify job still calls VisionAnalysisService correctly
  - [x] 2.3 Update error handling if needed
  - [x] 2.4 Verify all AnalyzeImageWithAIJob tests pass

- [x] 3. Refactor OpenAILogoService to use Prism
  - [x] 3.1 Write tests for OpenAILogoService with PrismFake mocks
  - [x] 3.2 Remove manual API key injection from constructor
  - [x] 3.3 Update generateLogos() method overview
  - [x] 3.4 Update generateSingleLogo() to use Prism::image()
  - [x] 3.5 Update callDalleApi() to use Prism image generation API
  - [x] 3.6 Update response parsing to use Prism response objects
  - [x] 3.7 Verify image downloading and storage still works
  - [x] 3.8 Update error handling to catch Prism exceptions
  - [x] 3.9 Verify all OpenAILogoService tests pass

- [x] 4. Update GenerateLogosJob for Prism compatibility
  - [x] 4.1 Write/update tests for GenerateLogosJob with PrismFake
  - [x] 4.2 Update service injection if constructor signature changed
  - [x] 4.3 Verify job still calls OpenAILogoService correctly
  - [x] 4.4 Update error handling if needed
  - [x] 4.5 Verify all GenerateLogosJob tests pass

- [x] 5. Update integration tests
  - [x] 5.1 Update AIVisionIntegrationTest to use PrismFake
  - [x] 5.2 Update any CompleteUserWorkflowTest vision/logo mocks
  - [x] 5.3 Update ProjectWorkflowIntegrationTest if it includes vision/logo
  - [x] 5.4 Verify generateNamesWithContext integration still works
  - [x] 5.5 Verify all integration tests pass

- [x] 6. Configuration cleanup
  - [x] 6.1 Audit all configuration references in refactored services
  - [x] 6.2 Verify config/prism.php has all necessary OpenAI settings
  - [x] 6.3 Remove redundant OpenAI configuration from config/ai.php
  - [x] 6.4 Update .env.example if environment variables changed
  - [x] 6.5 Document configuration changes in deployment notes

- [x] 7. Documentation updates
  - [x] 7.1 Update docs/llms.md to reflect 100% Prism usage
  - [x] 7.2 Remove "NOT using Prism" sections from documentation
  - [x] 7.3 Add notes about Prism's multi-modal and image generation support
  - [x] 7.4 Update summary statistics (8 services, 8 using Prism)
  - [x] 7.5 Update recommendations section

- [x] 8. Final verification and cleanup
  - [x] 8.1 Run entire test suite and verify all tests pass
  - [x] 8.2 Run static analysis (PHPStan) and fix any issues
  - [x] 8.3 Run code formatting (Pint) on all modified files
  - [x] 8.4 Verify no unused imports or dead code
  - [x] 8.5 Check error logs for any new Prism-related warnings
  - [x] 8.6 Verify vision analysis still produces correct results
  - [x] 8.7 Verify logo generation still produces correct results
  - [x] 8.8 Verify caching still works for vision analysis
  - [x] 8.9 Test end-to-end: upload image → analyze → generate names
  - [x] 8.10 Test end-to-end: request logos → generate → display
