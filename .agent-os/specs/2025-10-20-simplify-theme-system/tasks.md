# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-10-20-simplify-theme-system/spec.md

> Created: 2025-10-20
> Status: Ready for Implementation

## Tasks

- [x] 1. Remove Defensive JavaScript and localStorage Code
  - [x] 1.1 Write tests for theme persistence without localStorage
  - [x] 1.2 Remove all localStorage.getItem() and localStorage.setItem() calls from head.blade.php
  - [x] 1.3 Remove MutationObserver setup and monitoring code from head.blade.php
  - [x] 1.4 Remove Livewire navigation event listeners (livewire:navigating, livewire:navigated)
  - [x] 1.5 Remove authorization flags (window.__allowingThemeChange)
  - [x] 1.6 Remove matchMedia override from sidebar.blade.php
  - [x] 1.7 Remove all console.log debugging statements related to theme
  - [x] 1.8 Verify tests pass and no JavaScript errors in browser console

- [x] 2. Remove ThemeCustomizer Component and Flux Integration
  - [x] 2.1 Write tests to ensure theme selection works without ThemeCustomizer
  - [x] 2.2 Delete app/Livewire/ThemeCustomizer.php file
  - [x] 2.3 Delete resources/views/livewire/theme-customizer.blade.php view file
  - [x] 2.4 Remove @fluxAppearance directive from resources/views/partials/head.blade.php
  - [x] 2.5 Update or remove appearance settings UI that references ThemeCustomizer
  - [x] 2.6 Remove any routes or navigation links to ThemeCustomizer
  - [x] 2.7 Remove any tests specific to ThemeCustomizer component
  - [x] 2.8 Verify all tests pass and no broken links exist

- [x] 3. Simplify Theme Initialization Script
  - [x] 3.1 Write tests for server-side theme rendering without flash
  - [x] 3.2 Refactor head.blade.php initialization to use only server-side data
  - [x] 3.3 Apply dark class server-side based on ThemeHelper::isDarkMode()
  - [x] 3.4 Load theme CSS file server-side based on ThemeHelper::getThemeName()
  - [x] 3.5 Remove client-side theme correction logic
  - [x] 3.6 Ensure theme CSS is preloaded to prevent render blocking
  - [x] 3.7 Simplify script to ~20 lines with no defensive logic
  - [x] 3.8 Verify tests pass and no flash of wrong theme on page load

- [ ] 4. Refactor ThemeQuickToggle for Database-Only Updates
  - [ ] 4.1 Write tests for ThemeQuickToggle with database persistence
  - [ ] 4.2 Update toggleTheme() method to only update database (User and UserThemePreference)
  - [ ] 4.3 Remove any localStorage manipulation from ThemeQuickToggle
  - [ ] 4.4 Clear theme cache after database update
  - [ ] 4.5 Dispatch Livewire $refresh event to reload page with new theme
  - [ ] 4.6 Remove any manual DOM manipulation or authorization flags
  - [ ] 4.7 Ensure both User.prefers_dark_mode and UserThemePreference.is_dark_mode stay synchronized
  - [ ] 4.8 Verify tests pass and theme switching works smoothly

- [ ] 5. Verify Complete System Integration
  - [ ] 5.1 Run full test suite to ensure no regressions
  - [ ] 5.2 Test theme persistence across Livewire wire:navigate transitions
  - [ ] 5.3 Test all 24 predefined themes in both light and dark modes
  - [ ] 5.4 Test cross-device persistence by logging in from different browsers
  - [ ] 5.5 Verify ThemeHelper caching works correctly within requests
  - [ ] 5.6 Check browser console for any JavaScript errors
  - [ ] 5.7 Verify no flash of wrong theme during page loads or navigation
  - [ ] 5.8 Run composer ready to ensure code quality standards are met
