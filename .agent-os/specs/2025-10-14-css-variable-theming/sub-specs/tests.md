# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-10-14-css-variable-theming/spec.md

> Created: 2025-10-14
> Version: 1.0.0

## Test Coverage

### Unit Tests

**UserThemePreference Model Tests**

File: `tests/Unit/Models/UserThemePreferenceTest.php`

- Test `getThemeCssPath()` returns correct path format
- Test `getDefaultTheme()` returns valid default structure
- Test casting of boolean fields (`is_dark_mode`, `compact_mode`)
- Test relationship to User model
- Test factory creates valid records with predefined theme names
- Test validation rejects invalid theme names
- Test validation accepts all 18 valid theme names

**ThemeService Tests**

File: `tests/Unit/Services/ThemeServiceTest.php`

- Test `getPredefinedThemes()` returns array of 18 themes
- Test each theme has required keys: name, display_name, category
- Test `getAvailableCategories()` returns correct categories
- Test `getCurrentSeasonalTheme()` returns appropriate theme for current month
- Test accessibility calculation methods still work (preserved from original)
- Test theme visibility methods removed (no longer needed)

**ThemeHelper Tests**

File: `tests/Unit/Helpers/ThemeHelperTest.php`

- Test `getCurrentUserTheme()` returns correct theme name
- Test `isDarkMode()` returns correct boolean
- Test `getThemeCssPath()` constructs correct path
- Test fallback to default theme for guest users
- Test fallback to default theme for users without preferences

### Integration Tests

**Theme Loading Integration**

File: `tests/Feature/ThemeLoadingTest.php`

- Test authenticated user loads correct theme CSS file
- Test dark mode toggle applies/removes `.dark` class
- Test theme switching updates user preference in database
- Test guest users load default theme
- Test theme CSS file exists for all predefined themes
- Test theme CSS files contain required CSS variables
- Test CSS files are accessible via HTTP GET request

**Theme Customizer Component Tests**

File: `tests/Feature/Livewire/ThemeCustomizerTest.php`

- Test theme customizer displays all 18 predefined themes
- Test clicking theme card updates user preference
- Test theme preview displays without hex colors
- Test category filter shows correct themes
- Test seasonal recommendation appears in correct months
- Test theme selection persists after page reload (via Livewire)
- Test accessibility of theme selection interface

**Theme Quick Toggle Tests**

File: `tests/Feature/Livewire/ThemeQuickToggleTest.php`

- Test dark mode toggle updates `is_dark_mode` in database
- Test dark mode toggle applies `.dark` class to DOM
- Test dark mode state persists across page loads
- Test keyboard shortcut triggers dark mode toggle (if implemented)

**Template Hex Removal Verification**

File: `tests/Feature/TemplateHexColorTest.php`

- Test `head.blade.php` contains no inline style block with hex colors
- Test `theme-customizer.blade.php` uses CSS variables for previews
- Test all 15 identified Blade files contain no hex color patterns
- Use regex pattern `#[0-9A-Fa-f]{6}` to detect hex colors
- Test that CSS variable references exist in place of hex values

### Feature Tests

**Complete Theme Workflow**

File: `tests/Feature/CompleteThemeWorkflowTest.php`

- Test user selects theme from customizer
- Test theme applies to all pages (dashboard, sidebar, header)
- Test toggling dark mode affects all UI elements consistently
- Test no visual regressions (light text on light background, etc.)
- Test theme persists after logout/login
- Test theme changes apply without page reload via Livewire

**Cross-Browser Theme Consistency**

File: `tests/Browser/ThemeConsistencyBrowserTest.php`

- Test CSS variables render correctly in Chrome
- Test CSS variables render correctly in Firefox
- Test CSS variables render correctly in Safari
- Test theme switching works in all browsers
- Test dark mode toggle works in all browsers
- Test no FOUC (flash of unstyled content) on page load

**Accessibility Compliance**

File: `tests/Feature/ThemeAccessibilityTest.php`

- Test all themes meet WCAG AA contrast ratio (4.5:1 minimum)
- Test focus indicators visible in all themes
- Test screen reader announcements for theme changes
- Test keyboard navigation works in theme customizer
- Test color-blind simulation for all themes
- Test high contrast mode compatibility

**Performance Tests**

File: `tests/Feature/Performance/ThemeLoadingPerformanceTest.php`

- Test theme CSS file loads in under 100ms
- Test theme switching completes in under 500ms
- Test CSS file size under 10KB per theme
- Test browser caching works correctly for theme files
- Test concurrent theme switches don't cause race conditions

### Mocking Requirements

**File System Mocking**

- Mock `file_exists()` checks for theme CSS files during testing
- Mock `file_get_contents()` for reading theme CSS content in tests

**Livewire Component Mocking**

- Use Livewire testing helpers for component interactions
- Mock Alpine.js persistence for dark mode state

**Database Mocking**

- Use in-memory SQLite for fast test execution
- Use factories to generate test user theme preferences
- Use database transactions for test isolation

## Test Data

### Factory Updates

**UserThemePreferenceFactory:**

```php
public function definition(): array
{
    return [
        'user_id' => User::factory(),
        'theme_name' => fake()->randomElement([
            'default', 'ocean', 'sunset', 'forest', 'cosmic-violet',
            'coral-reef', 'midnight-teal', 'summer', 'winter',
            'halloween', 'spring', 'autumn', 'neon-cyber',
            'electric-blue', 'hot-pink', 'lava-red', 'lime-punch',
            'gold-rush', 'matrix-green'
        ]),
        'is_dark_mode' => fake()->boolean(),
        'border_radius' => fake()->randomElement(['none', 'small', 'medium', 'large', 'full']),
        'font_size' => fake()->randomElement(['small', 'medium', 'large']),
        'compact_mode' => fake()->boolean(),
    ];
}
```

### Test Theme CSS Files

Create minimal test theme CSS files in `tests/fixtures/css/themes/` for testing purposes:

- `test-light.css` - Light theme with valid CSS variables
- `test-dark.css` - Dark theme with valid CSS variables
- `test-invalid.css` - Missing required CSS variables (for error testing)

## Regression Prevention

### Tests to Update

The following existing tests need updates to work with new theme system:

- `tests/Feature/ThemeConsistencyTest.php` - Update to check CSS variables instead of hex
- `tests/Feature/ThemeTextReadabilityTest.php` - Update to read colors from CSS files
- `tests/Feature/ThemeSynchronizationTest.php` - Update to test theme file loading
- `tests/Feature/Api/ThemeCustomizationApiTest.php` - Update API responses to return theme name only
- `tests/Browser/ThemeAccessibilityBrowserTest.php` - Update to test new theme structure

### Tests to Remove

The following tests are no longer relevant and should be removed:

- `tests/Feature/CustomThemeAccessibilityTest.php` - No custom themes in new system
- `tests/Feature/Models/UserThemePreferenceTest.php` - If it tests removed methods

## Coverage Goals

- **Unit Test Coverage:** 100% of new/modified methods
- **Integration Test Coverage:** All theme-related workflows
- **Feature Test Coverage:** End-to-end theme selection and application
- **Browser Test Coverage:** Cross-browser theme rendering
- **Accessibility Test Coverage:** WCAG AA compliance for all themes

## CI/CD Integration

- All tests must pass before merge
- Theme CSS files must exist before deployment
- Performance benchmarks must meet thresholds
- Accessibility audits must pass for all themes
