# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-10-01-fluxui-theme-alignment/spec.md

> Created: 2025-10-01
> Version: 1.0.0

## Test Coverage

### Unit Tests

**ThemeCustomizer Livewire Component**
- Test accent color validation (must be valid hex color)
- Test base color shade validation (must be one of: slate, neutral, gray, stone, zinc)
- Test CSS generation with FluxUI variables
- Test theme name validation and sanitization
- Test dark mode toggle functionality
- Test accessibility score calculation with new color system
- Test predefined theme application (ensure they use FluxUI variables)
- Test seasonal theme recommendations

**Theme Service/Helper (if applicable)**
- Test contrast ratio calculations for accent colors
- Test color shade selection for light/dark modes
- Test zinc remapping logic for different base shades

### Integration Tests

**Theme Application Workflow**
- Test applying predefined theme updates CSS variables correctly
- Test custom theme creation and persistence
- Test theme import/export with new variable structure
- Test dark mode transition maintains proper accent colors
- Test theme switching between light and dark modes
- Test accessibility warnings for low-contrast combinations

**Theme Persistence**
- Test theme settings saved to user preferences
- Test theme restored on page reload
- Test theme applied across different sessions

### Feature Tests

**Visual Regression Testing**
- Test all major pages render correctly with new zinc-based colors:
  - Home/dashboard page
  - Session detail page
  - Settings pages
  - Theme customizer page
- Test accent colors appear consistently:
  - Primary buttons use bg-accent
  - Links use text-accent-content
  - Form focus states use ring-accent
- Test dark mode appearance for all pages
- Test predefined themes look correct

**Accessibility Compliance**
- Test contrast ratios meet WCAG AA standards (4.5:1 for normal text, 3:1 for large text)
- Test focus indicators visible in both light and dark modes
- Test form inputs have proper border contrast in all states
- Test interactive elements have adequate touch targets (44px minimum)

**Cross-Browser Testing**
- Test theme rendering in Chrome, Firefox, Safari
- Test CSS variable support and fallbacks
- Test dark mode detection and application

### Mocking Requirements

**External Services:**
- None required (theme system is entirely client-side)

**Browser APIs:**
- Mock `localStorage` for theme persistence tests
- Mock `window.matchMedia('(prefers-color-scheme: dark)')` for dark mode detection tests
- Mock file upload/download for theme import/export tests

### Test Data

**Predefined Themes:**
- Verify all predefined themes use only FluxUI standard variables
- Test themes cover various accent colors (blue, green, purple, red, orange)
- Test themes include both light and dark mode variants

**Color Combinations:**
- Test high-contrast combinations (black/white)
- Test low-contrast combinations (should trigger warnings)
- Test edge cases (very light accent on white background, very dark on black)

### Regression Prevention

**Before/After Comparisons:**
- Screenshot tests for major pages before and after migration
- Verify no visual regressions in component appearance
- Verify theme switching still works smoothly
- Verify custom theme creation still functional

**Migration Validation:**
- Test that no hardcoded color classes remain in templates (bg-blue-*, text-gray-*, etc.)
- Test that no custom color variables are referenced in templates
- Test that all custom theme CSS classes have been replaced
- Verify app.css contains only FluxUI standard variables

## Test Execution Strategy

1. **Pre-Migration Baseline:**
   - Capture screenshots of all major pages with current theme system
   - Document current theme behavior and features

2. **Post-Migration Verification:**
   - Run full test suite to ensure no functionality broken
   - Compare screenshots to identify any visual regressions
   - Manually test theme customizer with various combinations
   - Verify accessibility scores maintain or improve

3. **Continuous Testing:**
   - Run tests on every template file migration
   - Test theme customizer after component refactor
   - Test final integrated system before deployment
