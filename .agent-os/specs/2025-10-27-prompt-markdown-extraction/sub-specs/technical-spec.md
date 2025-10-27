# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-27-prompt-markdown-extraction/spec.md

> Created: 2025-10-27
> Version: 1.0.0

## Technical Requirements

### Markdown File Structure

**Configuration Ownership Rule**: For prompt pairs (system + user), **all configuration is stored in the system prompt file only**. User prompt files contain a `config_source` field pointing to the system prompt file.

#### System Prompt File Structure (Configuration Owner)

```markdown
---
provider: openai
model: gpt-4o
temperature: 0.7
max_tokens: 200
deep_thinking_temperature: 0.3
description: "Creative mode system prompt with generation rules"
---

[System prompt text here with instructions and rules]
```

**Frontmatter Fields**:
- `provider` (required): Prism provider enum value (openai, anthropic, gemini, xai)
- `model` (required): Model identifier string (gpt-4o, claude-3-5-sonnet-20241022, gemini-1.5-pro, grok-beta, dall-e-2, dall-e-3)
- `temperature` (optional): Float value 0.0-2.0 for generation randomness
- `max_tokens` (optional): Integer for maximum output tokens
- `deep_thinking_temperature` (optional): Alternative temperature for deep thinking mode
- `description` (optional): Human-readable description of prompt purpose
- Any additional provider-specific options (e.g., `n`, `size`, `response_format` for DALL-E)

#### User Prompt File Structure (Configuration Reference)

```markdown
---
config_source: name-generation-creative-system.md
description: "Creative mode user prompt template for business idea"
---

Business concept: {$businessIdea}

[Additional user prompt context and examples...]
```

**Frontmatter Fields**:
- `config_source` (required): Filename of the system prompt that owns configuration
- `description` (optional): Human-readable description of prompt purpose

**Rationale**: System prompts define HOW the AI should generate (provider, model, temperature, rules), while user prompts define WHAT to generate (business idea, context). Configuration naturally belongs with the system prompt. The `config_source` field makes it immediately clear where configuration lives when viewing user prompt files.

### Prompt Loader Service Architecture

**Class**: `App\Services\PromptLoaderService`
**Purpose**: Centralized service for loading and parsing prompt markdown files

**Public Methods**:
```php
public function load(string $promptName): PromptData
public function loadWithCache(string $promptName): PromptData
public function clearCache(string $promptName): void
public function getAllPrompts(): array
```

**PromptData DTO**:
```php
readonly class PromptData
{
    public function __construct(
        public Provider $provider,
        public string $model,
        public string $promptText,
        public ?float $temperature = null,
        public ?int $maxTokens = null,
        public ?float $deepThinkingTemperature = null,
        public ?string $description = null,
        public array $clientOptions = []
    ) {}
}
```

**Implementation Details**:
- Use `Symfony\Component\Yaml\Yaml` for YAML frontmatter parsing
- Cache parsed prompts in Laravel cache with 1-hour TTL
- Validate provider enum values against `Prism\Prism\Enums\Provider`
- Throw descriptive exceptions for missing files, invalid YAML, or invalid provider values
- Support template variable interpolation in prompt text (e.g., `{$businessName}`, `{$styleDescription}`)

**Configuration Resolution Logic**:
When loading a prompt file:
1. Parse frontmatter and check for `config_source` field
2. If `config_source` exists:
   - Load the referenced configuration file (e.g., `name-generation-creative-system.md`)
   - Extract provider, model, temperature, max_tokens from the config source
   - Use the current file's prompt text
3. If `config_source` does NOT exist:
   - Use provider, model, temperature, max_tokens from current file
   - Use the current file's prompt text
4. Return PromptData with resolved configuration + prompt text

**Example Usage**:
```php
// Loading user prompt automatically resolves config from system prompt
$userPrompt = $promptLoader->load('name-generation-creative-user');
// Returns PromptData with:
// - provider: from name-generation-creative-system.md
// - model: from name-generation-creative-system.md
// - temperature: from name-generation-creative-system.md
// - promptText: from name-generation-creative-user.md

// Loading system prompt uses its own config
$systemPrompt = $promptLoader->load('name-generation-creative-system');
// Returns PromptData with:
// - provider: from name-generation-creative-system.md
// - model: from name-generation-creative-system.md
// - temperature: from name-generation-creative-system.md
// - promptText: from name-generation-creative-system.md
```

### Prompt File Organization

**Directory**: `resources/prompts/`

**Name Generation Prompts** (from PromptBuilder):
- `name-generation-creative-system.md` - Creative mode system prompt
- `name-generation-creative-user.md` - Creative mode user prompt template
- `name-generation-professional-system.md` - Professional mode system prompt
- `name-generation-professional-user.md` - Professional mode user prompt template
- `name-generation-brandable-system.md` - Brandable mode system prompt
- `name-generation-brandable-user.md` - Brandable mode user prompt template
- `name-generation-tech-focused-system.md` - Tech-focused mode system prompt
- `name-generation-tech-focused-user.md` - Tech-focused mode user prompt template

**Logo Generation Prompts** (from OpenAILogoService):
- `logo-generation-base.md` - Base logo generation prompt template
- Individual style configurations embedded in frontmatter or separate files per style

**Vision Analysis Prompts** (from VisionAnalysisService):
- `vision-analysis.md` - Image analysis prompt for business context

**Shared Core Rules**:
- `name-generation-core-rules.md` - Shared rules included in all name generation modes

### Service Integration Approach

**PromptBuilder Service**:
- Remove all hardcoded prompt strings
- Load system and user prompts from respective markdown files based on mode
- Interpolate business-specific variables into user prompts
- Use PromptLoaderService to get provider, model, and temperature settings
- Keep build() method signature the same for backward compatibility
- Add new method: `buildWithConfig(string $businessIdea, string $mode, bool $deepThinking): array` that returns prompt + config

**AIGenerationService**:
- Remove MODEL_CONFIGS constant
- Load provider, model, temperature, max_tokens from prompt markdown files via PromptLoaderService
- Update generateNamesForModel() to use loaded configuration
- Maintain all existing caching, fallback, and retry logic

**OpenAILogoService**:
- Remove LOGO_STYLES constant
- Load logo prompt template and provider/model config from markdown
- Interpolate style descriptions, business name, and description into prompt template
- Update callDalleApi() to use loaded provider and model
- Keep IMAGE_SIZE as constant (can be moved to frontmatter in future)

**VisionAnalysisService**:
- Remove buildAnalysisPrompt() hardcoded prompt
- Load vision analysis prompt from markdown file
- Use loaded provider, model, temperature, max_tokens from frontmatter
- Keep caching logic unchanged

**GenerateNamesWithModelJob**:
- Remove MODEL_CONFIGS constant
- Load provider and model from PromptBuilder/PromptLoaderService
- Keep all queue logic, retry, and error handling unchanged

### External Dependencies

None - all required packages already exist in the project:
- `symfony/yaml` - Already installed as Laravel dependency for YAML parsing
- Prism package - Already installed and used throughout application
- Laravel Cache - Built-in framework feature

### Caching Strategy

**Cache Keys**: `prompt:{prompt_name}:parsed`
**Cache Duration**: 3600 seconds (1 hour)
**Cache Driver**: Application default cache driver (configured in config/cache.php)

**Cache Invalidation**:
- Automatic expiration after 1 hour
- Manual cache clear via `PromptLoaderService::clearCache($promptName)`
- Artisan command: `php artisan cache:forget prompt:{prompt_name}:parsed`
- Full cache clear: `php artisan cache:clear` or `php artisan optimize:clear`

**Rationale**: Prompts change infrequently in production, so 1-hour caching provides excellent performance without sacrificing flexibility. Development environments can clear cache easily when editing prompts.

### Error Handling

**File Not Found**:
```php
throw new PromptNotFoundException("Prompt file not found: {$promptName}");
```

**Invalid YAML**:
```php
throw new InvalidPromptException("Invalid YAML frontmatter in: {$promptName}");
```

**Missing Required Fields** (when no config_source present):
```php
throw new InvalidPromptException("Missing required field 'provider' in: {$promptName}");
throw new InvalidPromptException("Missing required field 'model' in: {$promptName}");
```

**Config Source Not Found**:
```php
throw new PromptNotFoundException("Config source file not found: {$configSource} referenced in {$promptName}");
```

**Invalid Config Source** (config_source references itself):
```php
throw new InvalidPromptException("Config source cannot reference itself in: {$promptName}");
```

**Invalid Provider**:
```php
throw new InvalidPromptException("Invalid provider '{$provider}' in: {$promptName}. Must be one of: openai, anthropic, gemini, xai");
```

All exceptions should extend `Exception` and be caught by calling services with appropriate logging and fallback behavior.

### Template Variable Interpolation

Support PHP-style variable interpolation in prompt text:

**Example Markdown**:
```markdown
---
provider: openai
model: dall-e-2
---

Create a {$styleDescription} logo for a business called '{$businessName}' that {$businessDescription}.
```

**Implementation**:
```php
public function interpolate(string $template, array $variables): string
{
    foreach ($variables as $key => $value) {
        $template = str_replace('{$' . $key . '}', $value, $template);
    }
    return $template;
}
```

**Usage**:
```php
$promptData = $promptLoader->load('logo-generation-base');
$finalPrompt = $promptLoader->interpolate($promptData->promptText, [
    'styleDescription' => 'minimalist, clean, simple',
    'businessName' => 'TechCo',
    'businessDescription' => 'provides cloud hosting'
]);
```

### Backward Compatibility

- All existing service public method signatures remain unchanged
- Internal implementation switches to markdown-based prompts transparently
- All existing tests continue to work without modification
- Legacy PromptBuilder::optimizePrompt() method maintained for compatibility

### Performance Considerations

- Prompt markdown files are small (typically < 5KB), so file I/O overhead is minimal
- Caching eliminates repeated file reads and YAML parsing
- Cache warming on application boot for frequently used prompts (optional optimization)
- No impact on API response times due to caching

### Security Considerations

- Prompt files are in `resources/prompts/` (not web-accessible)
- No user-provided content in prompt file paths (prevent directory traversal)
- Validate all frontmatter values before use
- Log prompt load failures for monitoring
- Cache invalidation prevents stale prompt injection
