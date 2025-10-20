# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-10-20-simplify-theme-system/spec.md

> Created: 2025-10-20
> Version: 1.0.0

## Test Coverage Overview

All theme functionality must be covered by automated tests to ensure:
- Theme persistence works correctly
- Theme switching is reliable
- No flash of wrong theme on page load
- All 24 themes render correctly in both light and dark modes
- Cross-user theme isolation

## Unit Tests

### ThemeHelper Tests

**File**: `tests/Unit/Helpers/ThemeHelperTest.php`

**Test Cases**:

```php
// Theme Name Retrieval
- test('returns default theme for guest users')
- test('returns user theme preference from database')
- test('returns default theme when user has no preference')
- test('caches theme name within request')
- test('validates theme name against allowed themes')
- test('falls back to default for invalid theme names')

// Dark Mode Detection
- test('returns false for guest users')
- test('returns user dark mode preference from database')
- test('returns false when user has no preference')
- test('caches dark mode preference within request')

// Cache Management
- test('clears user theme cache correctly')
- test('clears tagged cache when supported')
- test('handles cache errors gracefully')

// Multiple Users
- test('returns correct theme for different users in same request')
- test('does not leak theme preferences between users')
```

### ThemeService Tests

**File**: `tests/Unit/Services/ThemeServiceTest.php`

**Test Cases**:

```php
// Theme Retrieval
- test('returns all 24 predefined themes')
- test('returns correct theme by name')
- test('throws exception for invalid theme name')

// Theme Structure Validation
- test('each theme has required color properties')
- test('each theme has both light and dark variants')
- test('theme colors are valid hex codes')
```

### Model Tests

**File**: `tests/Unit/Models/UserThemePreferenceTest.php`

**Test Cases**:

```php
// Model Relationships
- test('belongs to user')
- test('user can have theme preference')

// Model Attributes
- test('casts is_dark_mode to boolean')
- test('casts theme_name to string')

// Validation
- test('requires user_id')
- test('requires theme_name')
- test('requires is_dark_mode')
```

## Feature Tests

### Theme Persistence Tests

**File**: `tests/Feature/ThemePersistenceTest.php`

**Test Cases**:

```php
// Database Persistence
- test('authenticated user theme preference saves to database')
- test('theme preference persists across requests')
- test('theme preference syncs between User and UserThemePreference models')

// Guest Handling
- test('guest users always get default theme')
- test('guest users cannot save theme preferences')

// Multiple Sessions
- test('user theme persists when logging in from different browser')
- test('user theme persists after logout and login')

// Default Behavior
- test('new user gets default theme on first login')
- test('user without preference falls back to default theme')
```

### Theme Switching Tests

**File**: `tests/Feature/ThemeSwitchingTest.php`

**Test Cases**:

```php
// Theme Toggle
- test('quick toggle switches between light and dark mode')
- test('quick toggle updates database correctly')
- test('quick toggle clears cache')
- test('quick toggle triggers page refresh')

// Theme Selection
- test('can switch to any of the 24 predefined themes')
- test('theme selection updates User model')
- test('theme selection updates UserThemePreference model')

// Edge Cases
- test('switching theme multiple times in succession works correctly')
- test('theme persists after Livewire navigation')
- test('theme remains stable during form submissions')
```

## Livewire Component Tests

### ThemeQuickToggle Tests

**File**: `tests/Feature/Livewire/ThemeQuickToggleTest.php`

**Test Cases**:

```php
use Livewire\Livewire;
use App\Livewire\ThemeQuickToggle;

// Component Rendering
- test('renders for authenticated users')
- test('does not render for guests')
- test('displays current theme state correctly')

// Toggle Functionality
- test('toggleTheme method switches dark mode on')
  Livewire::actingAs($user)
      ->test(ThemeQuickToggle::class)
      ->call('toggleTheme')
      ->assertDispatched('$refresh');

  expect($user->fresh()->prefers_dark_mode)->toBeTrue();

- test('toggleTheme method switches dark mode off')
- test('toggleTheme updates both User and UserThemePreference')
- test('toggleTheme clears cache')

// Event Dispatching
- test('dispatches refresh event after toggle')
- test('does not allow unauthenticated toggle')
```

## Integration Tests

### Page Load Tests

**File**: `tests/Feature/ThemePageLoadTest.php`

**Test Cases**:

```php
// Initial Page Load
- test('page loads with correct theme CSS link')
- test('page loads with dark class when user prefers dark mode')
- test('page loads without dark class when user prefers light mode')
- test('page loads with default theme for guest users')

// Theme CSS File
- test('theme CSS link points to correct file path')
- test('theme CSS file exists for all 24 themes')
- test('invalid theme falls back to default CSS file')

// No Flash Behavior
- test('theme applied server-side before page render')
- test('no JavaScript required for initial theme application')
```

### Livewire Navigation Tests

**File**: `tests/Feature/ThemeLivewireNavigationTest.php`

**Test Cases**:

```php
// wire:navigate Compatibility
- test('theme persists during wire:navigate transitions')
- test('theme CSS remains loaded during navigation')
- test('dark class remains stable during navigation')

// Multiple Page Navigation
- test('theme stays consistent across multiple page navigations')
- test('switching theme during navigation works correctly')
```

## Browser Tests (Optional - Playwright/Dusk)

### Visual Theme Tests

**File**: `tests/Browser/ThemeVisualTest.php`

**Test Cases**:

```php
// Visual Consistency
- test('all 24 themes render without CSS errors')
- test('dark mode applies correct background colors')
- test('light mode applies correct background colors')
- test('theme switching has smooth visual transition')

// No Flash Verification
- test('no flash of wrong theme on page load')
- test('no flash during Livewire navigation')

// Interactive Elements
- test('theme quick toggle button works visually')
- test('all UI components respect theme colors')
```

## Mocking Requirements

### No External Service Mocking Needed

Since theme functionality is entirely internal:
- No API calls to mock
- No external services
- All data comes from database

### Time-Based Testing

No time-based or scheduled theme switching, so no time mocking needed.

### Database Mocking

Use Laravel's standard testing database:
- SQLite in-memory for fast tests
- Database transactions for isolation
- Factory-generated test data

## Test Data Factories

### UserThemePreference Factory

**File**: `database/factories/UserThemePreferenceFactory.php`

```php
public function definition(): array
{
    return [
        'user_id' => User::factory(),
        'theme_name' => fake()->randomElement([
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            // ... all 24 themes
        ]),
        'is_dark_mode' => fake()->boolean(),
        'border_radius' => 'medium',
        'font_size' => 'medium',
        'compact_mode' => false,
    ];
}

// Custom states
public function lightMode(): static
{
    return $this->state(fn (array $attributes) => [
        'is_dark_mode' => false,
    ]);
}

public function darkMode(): static
{
    return $this->state(fn (array $attributes) => [
        'is_dark_mode' => true,
    ]);
}

public function withTheme(string $themeName): static
{
    return $this->state(fn (array $attributes) => [
        'theme_name' => $themeName,
    ]);
}
```

## Test Execution Strategy

### During Development

Run tests for the specific functionality being changed:

```bash
# Unit tests only
php artisan test --filter=ThemeHelper

# Feature tests only
php artisan test --filter=ThemePersistence

# Livewire component tests
php artisan test --filter=ThemeQuickToggle
```

### Before Committing

Run all theme-related tests:

```bash
php artisan test --filter=Theme
```

### Full Test Suite

Run complete test suite to ensure no regressions:

```bash
php artisan test
```

All tests must pass before creating a pull request.

## Coverage Goals

- **Unit Tests**: 100% coverage of ThemeHelper and ThemeService methods
- **Feature Tests**: All user-facing theme functionality covered
- **Integration Tests**: All page load and navigation scenarios covered
- **Component Tests**: All Livewire component methods covered

Minimum acceptable coverage: **95% for theme-related code**

## Regression Prevention

### Critical Test Cases

These tests prevent the original bugs from reoccurring:

```php
// Prevents theme switching itself unexpectedly
- test('theme does not change without user action')
- test('dark mode does not toggle automatically')
- test('theme persists across Livewire navigation')

// Prevents flash of wrong theme
- test('correct theme applied before page render')
- test('no temporary wrong theme during navigation')

// Prevents localStorage conflicts
- test('theme persistence uses only database')
- test('no localStorage access in theme code')
```

### Continuous Monitoring

After deployment, monitor for:
- User reports of theme switching unexpectedly
- Browser console errors related to theme CSS
- Database query performance for theme lookups
- Cache hit rates for theme preferences
