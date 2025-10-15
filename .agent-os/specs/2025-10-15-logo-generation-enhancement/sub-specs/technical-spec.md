# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-15-logo-generation-enhancement/spec.md

> Created: 2025-10-15
> Version: 1.0.0

## Technical Requirements

### Logo Generation Count

**Current Implementation:**
- Generates 12 logos per request (4 styles × 3 variations each)
- Defined in `GenerateLogosJob::VARIATIONS_PER_STYLE = 3`
- Uses all 4 logo styles: minimalist, modern, playful, corporate

**New Implementation:**
- Generate exactly 5 logos per request
- Simplified style distribution: 2 logos from one style, 1 each from three other styles
- Alternative approach: Use only 5 specific style/prompt combinations without categorization
- Update constant in `GenerateLogosJob` to reflect new count

### Image Resolution

**Current Implementation:**
- Uses DALL-E 3 with 1024x1024 resolution
- Defined in `OpenAILogoService::makeApiRequest()` as `'size' => '1024x1024'`
- Cost: $0.04 per image (standard quality)

**New Implementation:**
- Change to 512x512 resolution for all logo generations
- Approximate cost: $0.016 per image (estimated based on DALL-E pricing tiers)
- Update size parameter in API request
- Maintain 'standard' quality setting (not 'hd')

### Prism Integration

**Current Implementation:**
- `OpenAILogoService` uses Laravel's `Http` facade directly
- API calls made to `https://api.openai.com/v1/images/generations`
- Manual error handling and retry logic

**New Implementation:**
- Refactor `OpenAILogoService` to use Prism for OpenAI API calls
- Prism configuration already exists in `config/prism.php` with OpenAI provider
- Leverage Prism's built-in error handling, retry logic, and rate limiting
- Maintain same API endpoint and request structure through Prism
- Follow patterns from existing `OpenAINameService` or `AIGenerationService` if they use Prism

**Integration Approach:**
```php
// Current approach (HTTP facade):
$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
    'Content-Type' => 'application/json',
])->post('https://api.openai.com/v1/images/generations', [...]);

// New approach (Prism):
// Use Prism's OpenAI provider for image generation
// Exact implementation depends on Prism's image generation support
// May require custom Prism request or continue using HTTP with Prism config
```

### Cost Tracking Updates

**Current Implementation:**
- `OpenAILogoService::DALLE_3_COST_CENTS = 400` (4 cents per 1024x1024 image)
- Total cost per request: 12 × $0.04 = $0.48

**New Implementation:**
- Update constant to `DALLE_3_COST_CENTS = 160` (1.6 cents per 512x512 image, estimated)
- Total cost per request: 5 × $0.016 = $0.08
- Maintain cost tracking in `LogoGeneration` model and database
- Note: Actual 512x512 pricing should be verified with OpenAI's current pricing

### Database and Model Changes

**No schema changes required:**
- `LogoGeneration` model tracks total cost and logos_completed
- `GeneratedLogo` model stores individual logo records
- Both models work with any number of logos

**Updates needed:**
- Adjust expectations in tests and UI for 5 logos instead of 12
- Update progress indicators if they show "X of 12" to show "X of 5"

### UI/UX Updates

**Components to Update:**
- `NameResultCard` component: Update any hardcoded expectations for 12 logos
- Logo progress modal/component: Adjust progress bar calculations for 5 logos
- Any loading messages or tooltips referencing logo count
- Gallery grid layout should automatically adapt to 5 logos

### Performance Considerations

**Expected Improvements:**
- Generation time reduced by ~60% (5 API calls vs 12)
- API cost reduced by ~83% ($0.08 vs $0.48)
- Storage requirements reduced (512×512 images are 1/4 the data size)
- Faster page loads with smaller image files

**Potential Issues:**
- None expected; changes are purely reductive
- All existing functionality (color customization, exports, gallery) works with any number of logos

## Approach Options

### Option A: Simplified Style Distribution (Recommended)

**Description:**
- Generate 5 logos with simple style distribution
- Example: 2 minimalist, 1 modern, 1 playful, 1 corporate
- Keep existing LOGO_STYLES array intact
- Update job loop logic to generate specific counts per style

**Pros:**
- Maintains existing style categorization
- Simple to implement with minimal changes
- Easy to adjust style distribution in future

**Cons:**
- Still requires multiple style iterations in job

### Option B: Single-Pass Generation

**Description:**
- Remove style categorization entirely
- Generate 5 logos with varied prompts in single loop
- Each logo gets a unique, descriptive prompt without style labels

**Pros:**
- Simpler job logic with single loop
- More flexibility in prompt variation
- Reduces code complexity

**Cons:**
- Loses style categorization for user reference
- Requires more significant refactoring
- May confuse users who expect style labels

**Selected Approach: Option A**

**Rationale:**
Option A maintains the existing architecture and style categorization that users may have come to expect, while still achieving the goal of reducing logo count and costs. The implementation changes are minimal and focused, reducing risk of breaking existing functionality. The style labels also provide useful context for users when reviewing logo concepts.

## External Dependencies

**No new dependencies required:**
- Prism is already installed and configured
- OpenAI API access already exists
- All necessary packages are present

**Verification needed:**
- Confirm Prism supports image generation endpoints
- If not, may need to continue using HTTP facade with Prism configuration for API key management
- Verify OpenAI's current pricing for 512x512 DALL-E images

## Implementation Notes

### Files to Modify

1. **app/Jobs/GenerateLogosJob.php**
   - Update VARIATIONS_PER_STYLE or create new TOTAL_LOGOS constant
   - Adjust loop logic to generate exactly 5 logos
   - Update style distribution logic

2. **app/Services/OpenAILogoService.php**
   - Change size parameter from '1024x1024' to '512x512'
   - Update DALLE_3_COST_CENTS constant to reflect 512x512 pricing
   - Refactor to use Prism if feasible, otherwise document why HTTP facade is retained

3. **Tests (all logo-related test files)**
   - Update expectations from 12 to 5 logos
   - Adjust cost calculations in assertions
   - Verify all tests pass with new logo count

4. **UI Components (if any hardcoded references exist)**
   - Search for "12" or references to 12 logos in Blade templates
   - Update progress indicators or loading messages
   - Verify gallery displays 5 logos correctly

### Testing Strategy

- Unit tests: Verify OpenAILogoService generates correct API requests
- Job tests: Confirm GenerateLogosJob creates exactly 5 logos
- Integration tests: Verify end-to-end workflow with 5 logos
- Cost tests: Validate cost calculations with new pricing
- UI tests: Ensure logo gallery and progress indicators work correctly
