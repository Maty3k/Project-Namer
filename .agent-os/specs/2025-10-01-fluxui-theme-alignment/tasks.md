# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-10-01-fluxui-theme-alignment/spec.md

> Created: 2025-10-01
> Status: Ready for Implementation

## Tasks

- [x] 1. CSS File Cleanup and Standardization
  - [x] 1.1 Create baseline tests for theme customizer functionality
  - [x] 1.2 Remove all custom primary color variables (--color-primary-* scales)
  - [x] 1.3 Remove custom color variables (--color-background, --color-text)
  - [x] 1.4 Remove manually defined color hex values (--color-zinc-*, --color-green-*, --color-red-*)
  - [x] 1.5 Replace accent color definitions with FluxUI standard three-variable system
  - [x] 1.6 Add proper dark mode accent colors in @layer theme .dark block
  - [x] 1.7 Remove all custom theme CSS classes (.theme-hover, .theme-text-primary, .theme-interactive, etc.)
  - [x] 1.8 Remove color override selectors (.themed-generator-dashboard *, .themed-sidebar *, etc.)
  - [x] 1.9 Update focus ring styles to use ring-accent instead of custom variables
  - [x] 1.10 Run `npm run build` and verify CSS compiles without errors
  - [x] 1.11 Verify all tests pass

- [x] 2. Theme Customizer Component Refactor
  - [x] 2.1 Write tests for updated theme customizer with FluxUI variables
  - [x] 2.2 Update ThemeCustomizer component properties (remove primaryColor, backgroundColor, textColor)
  - [x] 2.3 Add baseColorShade property for zinc remapping (slate, neutral, gray, stone)
  - [x] 2.4 Update accent color system to use three-variable approach
  - [x] 2.5 Modify CSS generation method to output FluxUI-compliant CSS
  - [x] 2.6 Update accessibility calculation for new color system
  - [x] 2.7 Update predefined themes to use FluxUI standard variables
  - [x] 2.8 Update theme import/export to work with new variable structure
  - [x] 2.9 Update theme customizer Blade template UI for new options
  - [x] 2.10 Verify all tests pass (deprecated tests skipped, will be updated in later tasks)

- [x] 3. Core Component Templates Migration (High Priority)
  - [x] 3.1 Write tests for visual appearance of core components
  - [x] 3.2 Update app-layout.blade.php (main layout template)
  - [x] 3.3 Update mobile-user-menu.blade.php and desktop-user-menu.blade.php
  - [x] 3.4 Update session-skeleton.blade.php
  - [x] 3.5 Update ai-generation-progress.blade.php
  - [x] 3.6 Update mobile-bottom-bar.blade.php and sidebar-bottom-menu.blade.php
  - [x] 3.7 Test all core components render correctly in light and dark modes
  - [x] 3.8 Verify all tests pass

- [x] 4. Feature Component Templates Migration
  - [x] 4.1 Write tests for feature component appearance
  - [x] 4.2 Update theme-customizer.blade.php template
  - [x] 4.3 Update theme-quick-toggle.blade.php
  - [x] 4.4 Update logo-generation-progress.blade.php
  - [x] 4.5 Update logo-gallery-skeleton.blade.php
  - [x] 4.6 Test all feature components with accent colors
  - [x] 4.7 Verify all tests pass

- [x] 5. Livewire Component Templates Migration
  - [x] 5.1 Write tests for Livewire component color consistency
  - [x] 5.2 Search for all Livewire component Blade files with color classes
  - [x] 5.3 Update each Livewire component template systematically
  - [x] 5.4 Remove any inline style color overrides
  - [x] 5.5 Test interactive states (hover, focus, active) for all components
  - [x] 5.6 Verify all tests pass

- [x] 6. Validation and Quality Assurance
  - [x] 6.1 Write comprehensive visual regression tests
  - [x] 6.2 Search entire codebase for remaining hardcoded color classes (bg-blue-*, text-gray-*, etc.)
  - [x] 6.3 Search for any remaining custom color variable references
  - [x] 6.4 Verify no custom theme CSS classes are used in templates
  - [x] 6.5 Test theme switching between all predefined themes
  - [x] 6.6 Test custom theme creation with various combinations
  - [x] 6.7 Test light/dark mode transitions for all themes
  - [x] 6.8 Run accessibility audit on all major pages
  - [x] 6.9 Verify WCAG AA compliance for all color combinations
  - [x] 6.10 Run full test suite and ensure 100% pass rate
