# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-10-27-prompt-markdown-extraction/spec.md

> Created: 2025-10-27
> Status: Ready for Implementation

## Tasks

- [x] 1. Create PromptLoaderService and PromptData DTO
  - [x] 1.1 Write tests for PromptData DTO (creation, validation, readonly properties)
  - [x] 1.2 Create PromptData DTO class with all required and optional fields
  - [x] 1.3 Write tests for PromptLoaderService (load, parse, validate, cache, errors)
  - [x] 1.4 Create PromptLoaderService with YAML parsing and caching
  - [x] 1.5 Implement provider enum validation
  - [x] 1.6 Implement template variable interpolation
  - [x] 1.7 Create custom exceptions (PromptNotFoundException, InvalidPromptException)
  - [x] 1.8 Verify all PromptLoaderService tests pass

- [x] 2. Extract Name Generation Prompts to Markdown
  - [x] 2.1 Create `resources/prompts/` directory structure
  - [x] 2.2 Extract core rules to `name-generation-core-rules.md`
  - [x] 2.3 Extract creative mode prompts to markdown files (system + user)
  - [x] 2.4 Extract professional mode prompts to markdown files (system + user)
  - [x] 2.5 Extract brandable mode prompts to markdown files (system + user)
  - [x] 2.6 Extract tech-focused mode prompts to markdown files (system + user)
  - [x] 2.7 Add appropriate frontmatter (provider, model, temperature, etc.) to each file
  - [x] 2.8 Verify markdown files are valid and loadable

- [x] 3. Update PromptBuilder to Use Markdown Prompts
  - [x] 3.1 Write integration tests for PromptBuilder with PromptLoaderService
  - [x] 3.2 Inject PromptLoaderService into PromptBuilder
  - [x] 3.3 Update buildSystemPrompt() to load from markdown files
  - [x] 3.4 Update buildUserPrompt() to load from markdown files
  - [x] 3.5 Implement variable interpolation for user prompts
  - [x] 3.6 Add buildWithConfig() method returning prompt + configuration
  - [x] 3.7 Maintain backward compatibility with existing build() method
  - [x] 3.8 Remove hardcoded prompt strings
  - [x] 3.9 Verify all PromptBuilder tests pass

- [x] 4. Update AIGenerationService to Use Markdown Configuration
  - [x] 4.1 Write integration tests for AIGenerationService with markdown configs
  - [x] 4.2 Remove MODEL_CONFIGS constant
  - [x] 4.3 Update generateNamesForModel() to load config from markdown
  - [x] 4.4 Update temperature and max_tokens logic to use markdown values
  - [x] 4.5 Maintain caching, fallback, and retry logic
  - [x] 4.6 Verify all AIGenerationService tests pass

- [x] 5. Extract Logo Generation Prompts and Update Service
  - [x] 5.1 Create logo generation markdown files for each style
  - [x] 5.2 Add appropriate frontmatter (provider: openai, model: dall-e-2, etc.)
  - [x] 5.3 Write integration tests for OpenAILogoService with markdown prompts
  - [x] 5.4 Update OpenAILogoService to use PromptLoaderService
  - [x] 5.5 Remove LOGO_STYLES constant
  - [x] 5.6 Update buildPrompt() to use loaded markdown template
  - [x] 5.7 Update callDalleApi() to use provider/model from markdown
  - [x] 5.8 Implement template variable interpolation for logo prompts
  - [x] 5.9 Verify all OpenAILogoService tests pass

- [x] 6. Extract Vision Analysis Prompt and Update Service
  - [x] 6.1 Create vision analysis markdown file with JSON schema prompt
  - [x] 6.2 Add appropriate frontmatter (provider: openai, model: gpt-4o, temperature: 0.3, max_tokens: 500)
  - [x] 6.3 Write integration tests for VisionAnalysisService with markdown prompt
  - [x] 6.4 Update VisionAnalysisService to use PromptLoaderService
  - [x] 6.5 Remove buildAnalysisPrompt() method
  - [x] 6.6 Update analyzeImage() to use loaded markdown prompt and config
  - [x] 6.7 Verify all VisionAnalysisService tests pass

- [x] 7. Update GenerateNamesWithModelJob
  - [x] 7.1 Write integration tests for job with markdown-based configs
  - [x] 7.2 Remove MODEL_CONFIGS constant from job
  - [x] 7.3 Update handle() method to use PromptBuilder's markdown configs
  - [x] 7.4 Maintain all queue, retry, and error handling logic
  - [x] 7.5 Verify all job tests pass

- [ ] 8. Create Comprehensive Documentation
  - [ ] 8.1 Create `resources/prompts/README.md` with complete documentation
  - [ ] 8.2 Document markdown file structure and frontmatter schema
  - [ ] 8.3 Document all available providers and models
  - [ ] 8.4 Document how prompts are loaded and cached
  - [ ] 8.5 Document where each prompt is used in the application
  - [ ] 8.6 Document editing guidelines and best practices
  - [ ] 8.7 Provide examples for common editing scenarios
  - [ ] 8.8 Document cache clearing commands

- [ ] 9. Run Full Test Suite and Verify
  - [ ] 9.1 Run all unit tests and verify 100% pass
  - [ ] 9.2 Run all integration tests and verify 100% pass
  - [ ] 9.3 Run all feature tests and verify 100% pass
  - [ ] 9.4 Verify no test regressions from original functionality
  - [ ] 9.5 Verify all 1,985+ tests still pass
  - [ ] 9.6 Manual test: Generate names using each generation mode
  - [ ] 9.7 Manual test: Generate logos for each style
  - [ ] 9.8 Manual test: Analyze image with vision service
  - [ ] 9.9 Verify all operations use markdown-based prompts
