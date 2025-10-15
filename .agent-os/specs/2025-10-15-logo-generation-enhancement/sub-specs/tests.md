# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-10-15-logo-generation-enhancement/spec.md

> Created: 2025-10-15
> Version: 1.0.0

## Test Coverage

### Unit Tests

**OpenAILogoService**
- Test `generateLogo()` creates API request with 512x512 size parameter
- Test cost constant updated to reflect new 512x512 pricing
- Test `generateLogoPrompt()` continues to work with style parameters
- Test Prism integration (if implemented) for API calls
- Test error handling and retry logic remain functional
- Test rate limiting still works correctly
- Test API response processing with 512x512 images
- Test cost calculation returns correct value for new resolution

**GenerateLogosJob**
- Test job generates exactly 5 logos (not 12)
- Test style distribution produces expected logo counts per style
- Test job tracks progress correctly for 5 logos
- Test cost calculation sums correctly for 5 × $0.016
- Test job completion status updates correctly
- Test NameSuggestion.logos field receives 5 logo records
- Test job failure cleanup works with any number of logos

### Integration Tests

**Logo Generation Workflow**
- Test clicking "Generate Logos" button dispatches job correctly
- Test job processes 5 logos and stores them in database
- Test logos appear in NameResultCard gallery
- Test logo file downloads work with 512x512 images
- Test color customization works with 5 logos
- Test batch download includes all 5 logos
- Test progress indicators update correctly during 5-logo generation

**Cost Tracking**
- Test LogoGeneration model records correct total cost ($0.08 for 5 logos)
- Test individual GeneratedLogo records track cost correctly
- Test cost aggregation across multiple generation requests

**API Integration**
- Test OpenAI API receives 512x512 size parameter
- Test API responses with 512x512 images process correctly
- Test error responses handle appropriately with Prism (if implemented)
- Test rate limiting works correctly with reduced generation count

### Feature Tests

**End-to-End Logo Generation**
- User expands name card and clicks "Generate Logos"
- System dispatches GenerateLogosJob to queue
- Job generates exactly 5 logos via OpenAI API
- Logos stored in database and files saved to storage
- NameSuggestion updated with logo data
- User sees 5 logos in gallery display
- User can download all 5 logos individually or as batch
- User can apply color customization to any logo
- Cost tracking shows $0.08 total for the generation

**Logo Gallery Display**
- Gallery renders correctly with 5 logos
- Grid layout adjusts appropriately for 5 logos
- All logos display with proper styling and hover effects
- Logo style labels display correctly (if using Option A)
- Gallery works on mobile and desktop viewports

**Existing Features Compatibility**
- Color palette customization works with 5 logos
- Export functionality (SVG/PNG) works with 5 logos
- Batch download includes all 5 logos
- Logo sharing features work correctly
- Logo deletion (if implemented) works correctly

### Regression Tests

**Verify No Breaking Changes:**
- All existing logo generation tests still pass (with updated expectations)
- Color customization tests pass with 5 logos
- Export tests pass with 5 logos
- Gallery component tests pass with 5 logos
- Job tests pass with updated logo counts
- Cost calculation tests pass with new pricing
- Integration tests pass end-to-end with 5 logos

### Performance Tests

**Generation Performance**
- Verify generation time is reduced compared to 12-logo generation
- Test API call count is exactly 5 per request
- Test storage usage is reduced with 512x512 images
- Test page load time with 5 smaller images

**Cost Verification**
- Verify actual OpenAI API cost matches expected $0.016 per image
- Test total cost calculation matches OpenAI billing
- Verify cost savings compared to previous implementation

### Mocking Requirements

**OpenAI API Responses**
- Mock successful 512x512 image generation responses
- Mock error responses (rate limits, API errors, network failures)
- Mock revised_prompt responses from DALL-E
- Mock image URL responses with appropriate file sizes

**Prism Integration (if implemented)**
- Mock Prism provider responses for OpenAI
- Mock Prism error handling
- Mock Prism retry logic behavior

**File Storage**
- Mock Storage facade for file operations
- Mock successful file saves and retrievals
- Mock file size calculations for 512x512 images

### Test Data

**Logo Generation Contexts:**
- Business names of varying lengths and character sets
- Different style preferences
- Various color schemes for customization testing

**Expected Outcomes:**
- Exactly 5 GeneratedLogo records per generation
- Exactly 5 image files in storage
- LogoGeneration cost_cents = 80 (5 × $0.016 = $0.08 = 80 cents)
- NameSuggestion.logos array contains 5 entries

### Edge Cases

**Error Scenarios:**
- Test behavior when OpenAI API fails mid-generation (e.g., 2 of 5 logos succeed)
- Test behavior with rate limiting after 3 of 5 logos
- Test behavior with invalid business names or special characters
- Test behavior when storage disk is full
- Test concurrent logo generation requests for same name

**Boundary Conditions:**
- Test with very long business names (truncation)
- Test with very short business names (1-2 characters)
- Test with emoji or unicode characters in business names
- Test with maximum rate limit utilization

### Testing Strategy Summary

1. **Update all existing logo tests** to expect 5 logos instead of 12
2. **Verify cost calculations** use new pricing ($0.016 per image)
3. **Test Prism integration** if implemented, or verify HTTP facade still works
4. **Validate 512x512 resolution** in API requests and responses
5. **Confirm UI/UX** works correctly with 5 logos
6. **Run full test suite** to ensure no regressions
7. **Perform manual testing** of complete workflow in development environment
