# Spec Requirements Document

> Spec: Prompt Markdown Extraction
> Created: 2025-10-27
> Status: Planning

## Overview

Extract all AI prompts, provider configurations, and model settings from application code into manageable markdown files with frontmatter. This enables non-technical users to manage prompts, adjust AI models and providers, and tune parameters like temperature without modifying PHP code.

## User Stories

### Content Manager Managing AI Prompts

As a content manager, I want to edit AI prompts in markdown files with clear documentation, so that I can improve name generation quality without touching application code or requiring developer assistance.

**Workflow**: The content manager navigates to `resources/prompts/`, opens a markdown file (e.g., `name-generation-creative.md`), reads the frontmatter documentation explaining provider/model/temperature settings, edits the prompt text to improve results, saves the file, and the changes take effect immediately on the next generation request without code deployment.

### Administrator Switching AI Providers

As an administrator, I want to change which AI provider and model is used for logo generation by editing frontmatter in a markdown file, so that I can quickly switch between OpenAI, Anthropic, or other providers based on cost, quality, or availability.

**Workflow**: The administrator opens `resources/prompts/logo-generation.md`, updates the frontmatter from `provider: openai` and `model: dall-e-2` to `provider: openai` and `model: dall-e-3`, saves the file, and the next logo generation request automatically uses the new model configuration.

### Product Owner Tuning Generation Parameters

As a product owner, I want to adjust AI temperature and max_tokens settings in prompt files, so that I can fine-tune the creativity and length of generated names without deploying new code.

**Workflow**: The product owner opens `resources/prompts/name-generation-professional.md`, changes `temperature: 0.7` to `temperature: 0.5` in the frontmatter for more focused results, saves the file, and subsequent professional mode generations use the new temperature setting.

## Spec Scope

1. **Prompt Markdown File Structure** - Create markdown files in `resources/prompts/` directory with YAML frontmatter containing provider, model, temperature, max_tokens, and other AI configuration parameters, followed by the actual prompt text.

2. **Prompt Loading Service** - Build a `PromptLoaderService` that reads markdown files, parses YAML frontmatter, validates configuration, and returns structured data containing provider enum, model string, parameters object, and prompt text.

3. **Name Generation Prompt Extraction** - Extract all name generation prompts from `PromptBuilder` service into separate markdown files for each generation mode (creative, professional, brandable, tech-focused), including deep thinking variants.

4. **Logo Generation Prompt Extraction** - Extract logo generation prompt templates from `OpenAILogoService` into markdown files for each logo style (minimalist, modern, playful, corporate).

5. **Vision Analysis Prompt Extraction** - Extract vision analysis prompt from `VisionAnalysisService` into a dedicated markdown file with appropriate OpenAI Vision model configuration.

6. **Service Integration** - Update all services (`AIGenerationService`, `OpenAILogoService`, `VisionAnalysisService`, `PromptBuilder`) to use `PromptLoaderService` instead of hardcoded prompts, providers, and model configurations.

7. **Documentation README** - Create comprehensive `resources/prompts/README.md` documenting the markdown file structure, frontmatter schema, available providers and models, how prompts are loaded, where each prompt is used in the application, and best practices for editing prompts.

## Out of Scope

- Prompt versioning or revision history (use Git for this)
- UI-based prompt editing interface within the application
- Real-time prompt reloading without file system changes
- Automated prompt optimization or A/B testing
- Multi-language prompt support
- Prompt templates with dynamic variable replacement beyond existing interpolation

## Expected Deliverable

1. All AI prompts successfully extracted to markdown files in `resources/prompts/` with valid YAML frontmatter and all services loading prompts from these files instead of hardcoded strings.

2. The application generates names, logos, and analyzes images using configuration and prompts loaded from markdown files, with all tests passing.

3. Comprehensive README.md in `resources/prompts/` directory documenting the system architecture, file structure, editing guidelines, and usage examples for all prompt types.

## Spec Documentation

- Tasks: @.agent-os/specs/2025-10-27-prompt-markdown-extraction/tasks.md
- Technical Specification: @.agent-os/specs/2025-10-27-prompt-markdown-extraction/sub-specs/technical-spec.md
- Tests Specification: @.agent-os/specs/2025-10-27-prompt-markdown-extraction/sub-specs/tests.md
