# AI Prompts Documentation

This directory contains all AI prompts used throughout the Project Namer application. Prompts are stored as markdown files with YAML frontmatter for configuration, enabling non-technical users to modify prompts without touching application code.

## Table of Contents

- [Markdown File Structure](#markdown-file-structure)
- [Frontmatter Schema](#frontmatter-schema)
- [Available Providers and Models](#available-providers-and-models)
- [How Prompts are Loaded](#how-prompts-are-loaded)
- [Prompt Files Overview](#prompt-files-overview)
- [Editing Guidelines](#editing-guidelines)
- [Cache Management](#cache-management)
- [Common Editing Scenarios](#common-editing-scenarios)

## Markdown File Structure

Each prompt file follows this structure:

```markdown
---
provider: openai
model: gpt-4o
temperature: 0.7
max_tokens: 200
deep_thinking_temperature: 0.3
description: "Brief description of this prompt's purpose"
---

Your prompt text goes here. You can use template variables like {$variableName}
for dynamic content that will be interpolated at runtime.
```

### Components:

1. **YAML Frontmatter** (between `---` markers): Configuration metadata
2. **Prompt Text**: The actual prompt sent to the AI model
3. **Template Variables**: Placeholders in the format `{$variableName}` that get replaced with dynamic values

## Frontmatter Schema

### Required Fields

- **`provider`**: AI provider (openai, anthropic, gemini, xai)
- **`model`**: Specific model identifier (e.g., gpt-4o, claude-3-5-sonnet-20241022)

### Optional Fields

- **`temperature`**: Controls randomness (0.0-1.0, default: 0.7)
- **`max_tokens`**: Maximum tokens in response (default: 200)
- **`deep_thinking_temperature`**: Temperature when deep thinking mode is enabled
- **`description`**: Human-readable description of the prompt
- **Custom fields**: Any additional fields are stored in the `metadata` array

### Temperature Guide

- **0.0-0.3**: Very deterministic, consistent outputs (good for structured tasks)
- **0.3-0.7**: Balanced creativity and consistency (most common)
- **0.7-1.0**: Higher creativity and variation (good for brainstorming)

## Available Providers and Models

### OpenAI (provider: openai)
- `gpt-4o` - Latest GPT-4 Optimized model
- `gpt-4` - GPT-4 base model
- `dall-e-2` - Image generation model

### Anthropic (provider: anthropic)
- `claude-3-5-sonnet-20241022` - Claude 3.5 Sonnet (latest)

### Google Gemini (provider: gemini)
- `gemini-1.5-pro` - Gemini 1.5 Pro

### xAI (provider: xai)
- `grok-beta` - Grok Beta

## How Prompts are Loaded

1. **PromptLoaderService** loads markdown files from `resources/prompts/`
2. YAML frontmatter is parsed into configuration
3. Prompt text is extracted and prepared for variable interpolation
4. Results are cached for 1 hour to improve performance
5. Template variables are interpolated with actual values at runtime

### Caching Behavior

- Prompts are cached for **1 hour** after loading
- Cache key format: `prompt_cache:{filename}`
- Cache automatically expires after 1 hour
- Use `php artisan cache:clear` to force refresh

## Prompt Files Overview

### Name Generation Prompts

**Purpose**: Generate business name suggestions based on user input

#### System Prompts (Model + Mode Specific)

Each generation mode has 4 model-specific system prompt files:

**Creative Mode:**
- `name-generation-creative-gpt4-system.md` - GPT-4, temp: 0.7
- `name-generation-creative-claude-system.md` - Claude, temp: 0.7
- `name-generation-creative-gemini-system.md` - Gemini, temp: 0.8
- `name-generation-creative-grok-system.md` - Grok, temp: 0.9

**Professional Mode:**
- `name-generation-professional-gpt4-system.md` - GPT-4, temp: 0.7
- `name-generation-professional-claude-system.md` - Claude, temp: 0.7
- `name-generation-professional-gemini-system.md` - Gemini, temp: 0.8
- `name-generation-professional-grok-system.md` - Grok, temp: 0.9

**Brandable Mode:**
- `name-generation-brandable-gpt4-system.md` - GPT-4, temp: 0.7
- `name-generation-brandable-claude-system.md` - Claude, temp: 0.7
- `name-generation-brandable-gemini-system.md` - Gemini, temp: 0.8
- `name-generation-brandable-grok-system.md` - Grok, temp: 0.9

**Tech-Focused Mode:**
- `name-generation-tech-focused-gpt4-system.md` - GPT-4, temp: 0.7
- `name-generation-tech-focused-claude-system.md` - Claude, temp: 0.7
- `name-generation-tech-focused-gemini-system.md` - Gemini, temp: 0.8
- `name-generation-tech-focused-grok-system.md` - Grok, temp: 0.9

**Variables:** `{$count}`, `{$deepThinkingInstructions}`

**Used by:** `App\Services\PromptBuilder::buildSystemPrompt()`

#### User Prompt

- **File**: `name-generation-user.md`
- **Variables**: `{$businessIdea}`, `{$businessType}`, `{$audience}`, `{$examples}`
- **Used by**: `App\Services\PromptBuilder::buildUserPrompt()`

### Logo Generation Prompts

**Purpose**: Generate logo variations for selected business names

- **Files**:
  - `logo-generation-minimalist.md` - Minimalist, clean, simple geometric shapes
  - `logo-generation-modern.md` - Modern, sleek, contemporary design
  - `logo-generation-playful.md` - Playful, fun, vibrant and colorful
  - `logo-generation-corporate.md` - Professional, corporate, business-focused

- **Configuration**: provider: openai, model: dall-e-2, size: 256x256
- **Variables**: `{$businessName}`, `{$styleDescription}`, `{$businessDescriptionClause}`
- **Used by**: `App\Services\OpenAILogoService`

### Vision Analysis Prompt

**Purpose**: Analyze uploaded images to extract business context

- **File**: `vision-analysis.md`
- **Configuration**: provider: openai, model: gpt-4o, temperature: 0.3, max_tokens: 500
- **Output**: JSON with description, mood, colors, objects, style, business_relevance
- **Used by**: `App\Services\VisionAnalysisService`

## Editing Guidelines

### Best Practices

1. **Always test changes**: Generate names/logos after editing to verify results
2. **Keep backups**: Save original prompts before making significant changes
3. **Clear cache**: Run `php artisan cache:clear` after editing prompts
4. **Maintain structure**: Don't remove required frontmatter fields
5. **Preserve variables**: Keep template variable names exactly as shown

### What You Can Safely Edit

✅ **Prompt text content**: Modify instructions, examples, tone
✅ **Temperature values**: Adjust creativity/consistency balance
✅ **Max tokens**: Increase/decrease response length
✅ **Description field**: Update prompt documentation
✅ **Custom metadata fields**: Add extra configuration data

### What You Should NOT Edit

❌ **Provider/model fields**: Changing these may break API calls
❌ **Template variable names**: Must match what code expects (e.g., `{$count}`)
❌ **File names**: Code references specific filenames
❌ **YAML syntax**: Incorrect YAML will cause parse errors

### Making Mode-Specific Changes

Since each generation mode has 4 model-specific system prompts, you can:

- Edit **all 4 files** to change behavior across all models for that mode
- Edit **specific model files** to customize behavior per model
- **Temperature differences** by model are intentional (Grok uses higher temps)

## Cache Management

### Clearing Cache

```bash
# Clear all application cache (including prompts)
php artisan cache:clear

# Specific to prompt cache
php artisan cache:forget prompt_cache:name-generation-creative-gpt4-system
```

### When to Clear Cache

- After editing any prompt file
- After changing frontmatter configuration
- When testing prompt modifications
- If prompts seem outdated

### Cache Behavior

- **TTL**: 1 hour (3600 seconds)
- **Key Format**: `prompt_cache:{filename}` (without .md extension)
- **Storage**: Uses application's configured cache driver
- **Auto-refresh**: Prompts reload automatically after cache expires

## Common Editing Scenarios

### Scenario 1: Make Names More Professional

**Goal**: Adjust creative mode to generate more business-appropriate names

**Steps**:
1. Edit all creative mode system prompts: `name-generation-creative-*-system.md`
2. Modify the mode-specific instructions at the end:
   ```markdown
   CREATIVE MODE: Generate creative yet professional names that evoke
   confidence and credibility. Think of names like "Stripe", "Notion", or
   "Figma" - unique and memorable while maintaining business credibility.
   ```
3. Clear cache: `php artisan cache:clear`
4. Test generation with different business ideas

### Scenario 2: Adjust AI Creativity

**Goal**: Make Gemini generate more conservative names

**Steps**:
1. Edit `name-generation-creative-gemini-system.md`
2. Change temperature in frontmatter:
   ```yaml
   temperature: 0.6  # Was 0.8, now more conservative
   deep_thinking_temperature: 0.3
   ```
3. Clear cache: `php artisan cache:clear`
4. Generate names and compare results

### Scenario 3: Add More Context to User Prompts

**Goal**: Include additional business context in name generation

**Steps**:
1. Edit `name-generation-user.md`
2. Add new template variable placeholder: `{$targetMarket}`
3. Update `App\Services\PromptBuilder::buildUserPrompt()` to pass the variable:
   ```php
   return $this->promptLoader->interpolate($promptData->promptText, [
       'businessIdea' => $businessIdea,
       // ... existing variables ...
       'targetMarket' => $targetMarket, // New variable
   ]);
   ```
4. Modify prompt text to use `{$targetMarket}` variable
5. Clear cache and test

### Scenario 4: Change Logo Style Instructions

**Goal**: Make minimalist logos even simpler

**Steps**:
1. Edit `logo-generation-minimalist.md`
2. Modify prompt text:
   ```markdown
   Create a {$styleDescription} logo for a business called '{$businessName}'{$businessDescriptionClause}.
   The logo should be extremely simple with maximum 2-3 shapes, memorable, and
   work well at tiny sizes. Absolutely no text, gradients, or complex details.
   ```
3. Update style_description in frontmatter if needed:
   ```yaml
   style_description: ultra-minimalist, extremely simple, 1-2 geometric shapes only
   ```
4. Clear cache: `php artisan cache:clear`
5. Generate logos to verify changes

### Scenario 5: Modify Vision Analysis Output

**Goal**: Add brand personality insights to image analysis

**Steps**:
1. Edit `vision-analysis.md`
2. Update JSON schema in prompt:
   ```markdown
   {
     "description": "A clear, detailed description of what you see",
     "mood": "The emotional tone and atmosphere",
     "colors": ["Array of dominant colors"],
     "objects": ["Key objects visible"],
     "style": "The visual style or aesthetic",
     "business_relevance": "Business types this appeals to",
     "brand_personality": "Personality traits this conveys (e.g., innovative, trustworthy, playful)"
   }
   ```
3. Optionally adjust temperature for more/less creative analysis
4. Clear cache and test image analysis

### Scenario 6: Add New Generation Mode

**Goal**: Create a "Local Business" name generation mode

**Steps**:
1. Create 4 new system prompt files:
   - `name-generation-local-gpt4-system.md`
   - `name-generation-local-claude-system.md`
   - `name-generation-local-gemini-system.md`
   - `name-generation-local-grok-system.md`

2. Copy frontmatter from existing mode files, adjust mode-specific instructions:
   ```markdown
   LOCAL BUSINESS MODE: Generate names perfect for local community businesses.
   Think of names like "Main Street Coffee", "Corner Bakery", or "City Plumbing" -
   approachable, memorable, and geographically or service-focused. Avoid overly
   creative or abstract names that might confuse local customers.
   ```

3. Update `App\Services\PromptBuilder` to handle new mode:
   ```php
   $modeSlug = match ($mode) {
       'creative' => 'creative',
       'professional' => 'professional',
       'brandable' => 'brandable',
       'tech-focused' => 'tech-focused',
       'local' => 'local', // New mode
       default => 'creative',
   };
   ```

4. Update UI to include "Local Business" mode option
5. Clear cache and test new mode

## Where Each Prompt is Used

### Name Generation System Prompts
- **Service**: `App\Services\PromptBuilder`
- **Method**: `buildSystemPrompt()`
- **Job**: `App\Jobs\GenerateNamesWithModelJob`
- **Controller**: Name generation endpoints

### Name Generation User Prompt
- **Service**: `App\Services\PromptBuilder`
- **Method**: `buildUserPrompt()`
- **Job**: `App\Jobs\GenerateNamesWithModelJob`
- **Controller**: Name generation endpoints

### Logo Generation Prompts
- **Service**: `App\Services\OpenAILogoService`
- **Methods**: `generateSingleLogo()`, `buildPrompt()`, `callDalleApi()`
- **Job**: Logo generation jobs
- **Controller**: Logo generation endpoints

### Vision Analysis Prompt
- **Service**: `App\Services\VisionAnalysisService`
- **Method**: `analyzeImage()`
- **Controller**: Image upload/analysis endpoints

## Technical Details

### PromptLoaderService

Located at: `App\Services\PromptLoaderService`

**Responsibilities:**
- Load markdown files from `resources/prompts/`
- Parse YAML frontmatter into `PromptData` DTO
- Cache parsed prompts for 1 hour
- Interpolate template variables
- Validate provider enums

**Key Methods:**
- `load(string $filename)`: Load and parse prompt file
- `loadWithCache(string $filename)`: Load with 1-hour cache
- `interpolate(string $template, array $variables)`: Replace template variables

### PromptData DTO

Located at: `App\DataTransferObjects\PromptData`

**Properties:**
- `provider`: Prism\Prism\Enums\Provider enum
- `model`: string
- `temperature`: ?float
- `maxTokens`: ?int
- `deepThinkingTemperature`: ?float
- `description`: ?string
- `promptText`: string (the actual prompt)
- `metadata`: array (all other frontmatter fields)

## Troubleshooting

### Prompts Not Updating After Edit

**Problem**: Changes to prompt files don't reflect in generated content

**Solutions**:
1. Clear Laravel cache: `php artisan cache:clear`
2. Check file permissions (must be readable)
3. Verify YAML frontmatter syntax is correct
4. Ensure no trailing whitespace in frontmatter

### YAML Parse Errors

**Problem**: `InvalidPromptException` when loading prompt

**Solutions**:
1. Validate YAML syntax (use online YAML validator)
2. Ensure proper indentation (use spaces, not tabs)
3. Quote string values with special characters
4. Check for matching `---` markers around frontmatter

### Template Variables Not Interpolating

**Problem**: Variables like `{$count}` appear literally in output

**Solutions**:
1. Verify exact variable name matches what code provides
2. Check for typos in variable names (case-sensitive)
3. Ensure code passes all required variables to `interpolate()`
4. Clear cache after modifying variables

### Model Not Found Errors

**Problem**: `AI model {model} is not configured`

**Solutions**:
1. Verify provider/model combination is valid
2. Check API keys are configured in `.env`
3. Ensure model file exists for the requested mode
4. Verify file naming convention matches expectations

## Support and Contributions

For questions, issues, or contributions related to prompt engineering:

1. Test changes thoroughly before deploying
2. Document significant prompt modifications
3. Share prompt improvements with the team
4. Consider A/B testing major prompt changes

---

**Last Updated**: 2025-10-27
**Version**: 1.0.0
