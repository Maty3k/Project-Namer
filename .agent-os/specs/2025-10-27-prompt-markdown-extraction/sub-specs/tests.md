# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-10-27-prompt-markdown-extraction/spec.md

> Created: 2025-10-27
> Version: 1.0.0

## Test Coverage

### Unit Tests

**PromptLoaderService**
- It loads valid prompt markdown file with complete frontmatter
- It parses YAML frontmatter correctly
- It extracts prompt text without frontmatter delimiters
- It maps provider string to Provider enum
- It caches loaded prompts for 1 hour
- It returns cached prompt on subsequent loads
- It throws PromptNotFoundException when file does not exist
- It throws InvalidPromptException when YAML is malformed
- It throws InvalidPromptException when provider field is missing (no config_source)
- It throws InvalidPromptException when model field is missing (no config_source)
- It throws InvalidPromptException when provider value is invalid
- It validates temperature is float between 0.0 and 2.0
- It validates max_tokens is positive integer
- It handles optional deep_thinking_temperature field
- It handles optional description field
- It parses client_options array from frontmatter
- It clears cache for specific prompt
- It returns all available prompts in directory

**PromptLoaderService Config Source Resolution**
- It loads user prompt with config_source and resolves config from system prompt
- It returns prompt text from user file and config from system file
- It caches both user and system prompts separately
- It throws PromptNotFoundException when config_source file does not exist
- It throws InvalidPromptException when config_source references itself
- It handles nested config_source resolution (user -> system that has its own config)
- It validates that resolved provider from config_source is valid
- It validates that resolved model from config_source exists
- It uses temperature from config_source when available
- It uses max_tokens from config_source when available
- It uses deep_thinking_temperature from config_source when available

**PromptLoaderService Interpolation**
- It interpolates single variable in prompt text
- It interpolates multiple variables in prompt text
- It handles missing variables gracefully (leaves placeholder)
- It escapes special characters in variable values
- It handles empty variable values
- It handles numeric variable values
- It handles array variable values (JSON encode)

**PromptData DTO**
- It creates PromptData with required fields only
- It creates PromptData with all optional fields
- It provides readonly properties
- It validates provider enum type
- It exports to array for serialization
- It creates from array (named constructor)

### Integration Tests

**PromptBuilder with PromptLoaderService**
- It builds system prompt from markdown file for creative mode
- It builds system prompt from markdown file for professional mode
- It builds system prompt from markdown file for brandable mode
- It builds system prompt from markdown file for tech-focused mode
- It builds user prompt from markdown file with business idea interpolation
- It includes deep thinking instructions when enabled
- It loads provider and model from prompt frontmatter
- It loads temperature from prompt frontmatter
- It uses deep_thinking_temperature when deep thinking enabled
- It falls back to default temperature when frontmatter missing
- It maintains backward compatibility with build() method
- It provides buildWithConfig() method returning prompt + config

**AIGenerationService with Markdown Prompts**
- It generates names using provider and model from markdown
- It uses temperature from markdown frontmatter
- It uses max_tokens from markdown frontmatter
- It applies deep_thinking_temperature when deep thinking enabled
- It generates names for all generation modes (creative, professional, brandable, tech-focused)
- It maintains caching behavior with markdown-based prompts
- It maintains fallback behavior with markdown-based prompts
- It maintains retry logic with markdown-based prompts

**OpenAILogoService with Markdown Prompts**
- It loads logo generation prompt from markdown file
- It interpolates style description into prompt template
- It interpolates business name into prompt template
- It interpolates business description into prompt template
- It uses provider and model from markdown frontmatter
- It generates logos for all styles using markdown prompts
- It applies client_options from frontmatter (n, size, response_format)

**VisionAnalysisService with Markdown Prompts**
- It loads vision analysis prompt from markdown file
- It uses provider and model from markdown frontmatter (gpt-4o)
- It applies temperature from markdown frontmatter
- It applies max_tokens from markdown frontmatter
- It analyzes image using markdown-defined prompt
- It maintains caching behavior with markdown-based prompts

**GenerateNamesWithModelJob with Markdown Prompts**
- It loads provider and model from markdown for each AI model
- It applies temperature from markdown frontmatter
- It generates names using markdown-based prompts in queue
- It handles job failures with markdown-based configuration

### Feature Tests

**Name Generation Workflow**
- It generates names using creative mode markdown prompts
- It generates names using professional mode markdown prompts
- It generates names using brandable mode markdown prompts
- It generates names using tech-focused mode markdown prompts
- It applies deep thinking mode using markdown configuration
- It generates names with all AI models using markdown configs
- Generated names meet quality criteria defined in markdown prompts

**Logo Generation Workflow**
- It generates minimalist logo using markdown prompt
- It generates modern logo using markdown prompt
- It generates playful logo using markdown prompt
- It generates corporate logo using markdown prompt
- Generated logos are saved correctly
- Logo generation uses provider/model from markdown

**Vision Analysis Workflow**
- It analyzes project image using markdown prompt
- It returns JSON structure defined in markdown prompt
- Vision analysis uses gpt-4o model from markdown config

**End-to-End Generation Workflow**
- It generates names and logos using only markdown-based prompts
- It analyzes image context and generates names using markdown prompts
- Complete workflow functions without hardcoded prompts

### Mocking Requirements

**File System**
- Mock prompt markdown file reads for testing error conditions
- Mock YAML parsing failures
- Use real markdown files from `tests/fixtures/prompts/` for happy path tests

**Cache**
- Use Laravel's array cache driver in tests (in-memory)
- Verify cache keys and TTL values
- Test cache invalidation behavior

**Prism API**
- Mock Prism::text() and Prism::image() calls as in existing tests
- Verify provider, model, and parameters from markdown are passed to Prism correctly
- Mock AI API responses for consistent test results

### Test Fixtures

Create test prompt markdown files in `tests/fixtures/prompts/`:
- `test-valid-prompt.md` - Valid prompt with all fields
- `test-minimal-prompt.md` - Minimal valid prompt (provider + model only)
- `test-invalid-yaml.md` - Malformed YAML frontmatter
- `test-missing-provider.md` - Missing required provider field (no config_source)
- `test-missing-model.md` - Missing required model field (no config_source)
- `test-invalid-provider.md` - Invalid provider value
- `test-interpolation.md` - Prompt with template variables
- `test-system-prompt.md` - System prompt with full configuration
- `test-user-prompt-with-config-source.md` - User prompt referencing test-system-prompt.md
- `test-config-source-not-found.md` - User prompt referencing non-existent config file
- `test-config-source-self-reference.md` - Prompt with config_source pointing to itself
- `test-config-source-missing-fields.md` - User prompt referencing system prompt that lacks required fields

### Performance Tests

**Caching Performance**
- First load reads file and parses YAML (~5-10ms)
- Subsequent loads use cache (~0.5-1ms)
- Cache miss rate in production < 5%

**Generation Performance**
- Name generation with markdown prompts matches current performance (±5%)
- Logo generation with markdown prompts matches current performance (±5%)
- Vision analysis with markdown prompts matches current performance (±5%)

### Regression Tests

**Existing Functionality**
- All existing name generation tests pass without modification
- All existing logo generation tests pass without modification
- All existing vision analysis tests pass without modification
- All existing integration tests pass without modification
- All existing feature tests pass without modification

**Test Count**: Maintain or increase current test count (1,985+ tests)
**Test Coverage**: Maintain 100% coverage of new PromptLoaderService code
