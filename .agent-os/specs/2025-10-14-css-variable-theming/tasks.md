# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-10-14-css-variable-theming/spec.md

> Created: 2025-10-14
> Status: Ready for Implementation

## Tasks

- [x] 1. Create Theme CSS Files Infrastructure
  - [x] 1.1 Write tests for CSS file structure and variable naming
  - [x] 1.2 Create `public/css/themes/` directory structure
  - [x] 1.3 Create all 18 theme CSS files with light and dark mode variants
  - [x] 1.4 Validate all CSS files contain required variables
  - [x] 1.5 Test CSS files load correctly via HTTP requests
  - [x] 1.6 Verify all tests pass

- [x] 2. Simplify Database Schema
  - [x] 2.1 Write tests for simplified UserThemePreference model
  - [x] 2.2 Create migration to remove color columns from user_theme_preferences
  - [x] 2.3 Update UserThemePreference model to remove color-related methods
  - [x] 2.4 Update UserThemePreferenceFactory to generate valid theme names only
  - [x] 2.5 Add `getThemeCssPath()` method to model
  - [x] 2.6 Run migration and verify schema changes
  - [x] 2.7 Verify all tests pass

- [ ] 3. Update ThemeService and ThemeHelper
  - [ ] 3.1 Write tests for updated theme service methods
  - [ ] 3.2 Remove hex-based methods from ThemeService
  - [ ] 3.3 Update `getPredefinedThemes()` to return theme metadata only (no hex colors)
  - [ ] 3.4 Update ThemeHelper to return theme name and dark mode status
  - [ ] 3.5 Add helper method to construct theme CSS path
  - [ ] 3.6 Verify all tests pass

- [ ] 4. Implement Dynamic Theme Loading System
  - [ ] 4.1 Write tests for theme CSS file injection
  - [ ] 4.2 Update `resources/views/partials/head.blade.php` to remove inline style block
  - [ ] 4.3 Add dynamic `<link>` tag for theme CSS file based on user preference
  - [ ] 4.4 Implement Alpine.js dark mode toggle with persistence
  - [ ] 4.5 Add `.dark` class toggle to root HTML element
  - [ ] 4.6 Test theme switching without page reload
  - [ ] 4.7 Verify all tests pass

- [ ] 5. Remove Hex Colors from Templates (Batch 1: Critical Files)
  - [ ] 5.1 Write tests to verify no hex colors in templates
  - [ ] 5.2 Update `resources/views/partials/head.blade.php` (remove entire inline style block)
  - [ ] 5.3 Update `resources/views/livewire/theme-customizer.blade.php` (theme previews)
  - [ ] 5.4 Update `resources/views/components/layouts/project-workflow.blade.php`
  - [ ] 5.5 Replace all hex values with CSS variable references
  - [ ] 5.6 Verify all tests pass

- [ ] 6. Remove Hex Colors from Templates (Batch 2: Sidebar & Navigation)
  - [ ] 6.1 Update `resources/views/livewire/session-sidebar.blade.php`
  - [ ] 6.2 Update `resources/views/livewire/sidebar.blade.php`
  - [ ] 6.3 Update `resources/views/components/desktop-user-menu.blade.php`
  - [ ] 6.4 Update `resources/views/components/layouts/app/sidebar.blade.php`
  - [ ] 6.5 Update `resources/views/components/layouts/app/header.blade.php`
  - [ ] 6.6 Replace all hex values with CSS variable references
  - [ ] 6.7 Verify all tests pass

- [ ] 7. Remove Hex Colors from Templates (Batch 3: Components & Utilities)
  - [ ] 7.1 Update `resources/views/livewire/image-uploader.blade.php`
  - [ ] 7.2 Update `resources/views/livewire/mood-board-canvas.blade.php`
  - [ ] 7.3 Update `resources/views/components/ai/accessible-interface.blade.php`
  - [ ] 7.4 Update `resources/views/components/ai/accessible-model-selector.blade.php`
  - [ ] 7.5 Update `resources/views/components/ai/accessibility-announcements.blade.php`
  - [ ] 7.6 Replace all hex values with CSS variable references
  - [ ] 7.7 Verify all tests pass

- [ ] 8. Remove Hex Colors from Templates (Batch 4: Dashboards & Exports)
  - [ ] 8.1 Update `resources/views/livewire/name-generator-dashboard-backup.blade.php`
  - [ ] 8.2 Update `resources/views/shares/mood-board.blade.php`
  - [ ] 8.3 Update `resources/views/exports/pdf/template.blade.php`
  - [ ] 8.4 Update `resources/views/welcome.blade.php`
  - [ ] 8.5 Replace all hex values with CSS variable references
  - [ ] 8.6 Verify all tests pass

- [ ] 9. Update Theme Customizer Component
  - [ ] 9.1 Write tests for theme customizer with CSS variables
  - [ ] 9.2 Update ThemeCustomizer Livewire component to work with new theme system
  - [ ] 9.3 Update theme preview rendering to use CSS variables
  - [ ] 9.4 Remove color picker inputs (if any)
  - [ ] 9.5 Test theme selection updates user preference correctly
  - [ ] 9.6 Verify all tests pass

- [ ] 10. Update Theme Quick Toggle Component
  - [ ] 10.1 Write tests for dark mode toggle functionality
  - [ ] 10.2 Update ThemeQuickToggle Livewire component for new schema
  - [ ] 10.3 Ensure dark mode toggle updates `is_dark_mode` in database
  - [ ] 10.4 Test Alpine.js persistence of dark mode state
  - [ ] 10.5 Verify all tests pass

- [ ] 11. Update Existing Theme Tests
  - [ ] 11.1 Update `tests/Feature/ThemeConsistencyTest.php` for CSS variables
  - [ ] 11.2 Update `tests/Feature/ThemeTextReadabilityTest.php` to read from CSS files
  - [ ] 11.3 Update `tests/Feature/ThemeSynchronizationTest.php` for file-based themes
  - [ ] 11.4 Update `tests/Feature/Api/ThemeCustomizationApiTest.php` for new responses
  - [ ] 11.5 Update `tests/Browser/ThemeAccessibilityBrowserTest.php` for new structure
  - [ ] 11.6 Remove obsolete tests (CustomThemeAccessibilityTest, etc.)
  - [ ] 11.7 Verify all tests pass

- [ ] 12. Final Integration Testing & Quality Assurance
  - [ ] 12.1 Run full test suite and ensure 100% pass rate
  - [ ] 12.2 Manually test all 18 themes in both light and dark modes
  - [ ] 12.3 Verify no hex colors exist in any Blade template (grep check)
  - [ ] 12.4 Test theme switching performance (<500ms)
  - [ ] 12.5 Verify WCAG AA accessibility compliance for all themes
  - [ ] 12.6 Run `composer ready` and fix any issues
  - [ ] 12.7 Final verification that all tests pass
