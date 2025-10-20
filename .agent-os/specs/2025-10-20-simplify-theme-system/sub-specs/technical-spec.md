# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-20-simplify-theme-system/spec.md

> Created: 2025-10-20
> Version: 1.0.0

## Technical Requirements

### Core Functionality

- **Database-Only Persistence**: Remove all `localStorage.getItem()` and `localStorage.setItem()` calls related to theme preferences (`darkMode`, `themeName`)
- **Single Source of Truth**: All theme state must be read from `ThemeHelper::getThemeName()` and `ThemeHelper::isDarkMode()` which query the database
- **Server-Side Rendering**: Theme CSS and dark class must be applied server-side before page load to prevent flash of wrong theme
- **Clean Initialization**: Theme initialization script in `head.blade.php` must be simplified to ~20 lines with no defensive logic
- **Livewire Integration**: Theme changes via `ThemeQuickToggle` must update database and trigger page re-render without manual JavaScript

### Files to Remove

- **ThemeCustomizer Component**: Delete `/app/Livewire/ThemeCustomizer.php` and its view file
- **Flux Appearance**: Remove `@fluxAppearance` directive from `head.blade.php`
- **Appearance Settings**: Remove or simplify `/resources/views/livewire/settings/appearance.blade.php` to only use custom theme system
- **matchMedia Override**: Remove all code that intercepts `window.matchMedia()` in `sidebar.blade.php`

### Files to Modify

- **head.blade.php**: Simplify theme initialization script, remove:
  - All localStorage checks and fallbacks
  - MutationObserver setup and monitoring
  - Livewire navigation event listeners
  - Authorization flags (`window.__allowingThemeChange`)
  - All console.log debugging statements

- **ThemeQuickToggle**: Simplify to only update database and dispatch Livewire refresh event

- **ThemeHelper**: Ensure it returns consistent, cached values within a single request

- **Database Models**: Ensure `User` and `UserThemePreference` models are properly synchronized

### Performance Considerations

- Theme CSS files must be preloaded in the head to prevent render blocking
- Database queries for theme preferences must be cached per-request to avoid N+1 issues
- Theme changes should trigger a simple Livewire page refresh rather than manual DOM manipulation

## Approach Options

### Option A: Pure Livewire (Server-Side Only) - **SELECTED**

**Description**: Completely remove client-side theme logic. Theme state lives only in the database and is applied server-side on every page load. Theme changes trigger a Livewire page refresh.

**Pros**:
- Simplest implementation with minimal JavaScript
- No synchronization issues between client and server
- Impossible for client and server state to diverge
- Easy to debug and maintain
- Works perfectly with Livewire's wire:navigate

**Cons**:
- Requires page refresh for theme changes (acceptable UX with Livewire's smooth transitions)
- Cannot persist theme preference for logged-out users (acceptable trade-off)

**Rationale**: This approach aligns with Livewire's server-driven philosophy and eliminates the entire class of bugs caused by client/server state synchronization. The slight UX compromise of page refresh is acceptable given Livewire's fast transitions, and the benefits of simplicity far outweigh this minor cost.

### Option B: Hybrid with localStorage Fallback

**Description**: Use database as primary source but keep localStorage for faster initial page loads and logged-out user support.

**Pros**:
- Potentially faster perceived performance
- Supports theme preference for non-authenticated users

**Cons**:
- Reintroduces synchronization complexity
- Doesn't solve the core problem of conflicting state sources
- Still requires defensive code to handle mismatches
- More code to maintain and test

**Rationale**: **REJECTED** - This maintains the problematic architecture that caused the current bugs.

## External Dependencies

**No new dependencies required.**

All necessary functionality exists in the current Laravel/Livewire stack:
- Laravel's authentication and database ORM for persistence
- Livewire for reactive UI updates
- Existing `ThemeHelper`, `ThemeService`, and model classes
- Existing CSS theme files in `/public/css/themes/`

## Implementation Strategy

### Phase 1: Remove Client-Side Code

1. Remove all localStorage usage from theme-related files
2. Remove MutationObserver and defensive JavaScript
3. Remove Flux appearance integration
4. Remove matchMedia override
5. Delete ThemeCustomizer component

### Phase 2: Simplify Server-Side Logic

1. Ensure ThemeHelper returns consistent values from database
2. Simplify ThemeQuickToggle to just update database and refresh
3. Streamline theme initialization in head.blade.php
4. Remove authorization flags and timing-based workarounds

### Phase 3: Test and Verify

1. Test theme persistence across page navigations
2. Test theme changes with ThemeQuickToggle
3. Verify no flash of wrong theme on page load
4. Ensure all 24 themes work correctly in both light and dark modes
5. Test cross-device persistence with same user account

## CSS Theme File Structure

Each of the 24 theme CSS files follows this structure:

```css
/* Light Mode */
:root {
  --color-primary: #3b82f6;
  --color-background: #ffffff;
  --color-text-primary: #111827;
  /* ... other CSS variables ... */
}

/* Dark Mode */
:root.dark {
  --color-primary: #3b82f6;
  --color-background: #111827;
  --color-text-primary: #f9fafb;
  /* ... other CSS variables ... */
}
```

The dark mode is activated by adding the `dark` class to the `<html>` element. This must be done server-side to prevent flash.

## Database Schema Reference

The theme preferences are stored in two locations:

**`users` table:**
- `prefers_dark_mode` (boolean)
- `current_theme` (string)
- `theme_auto_switch` (boolean) - will be deprecated/ignored

**`user_theme_preferences` table:**
- `user_id` (FK to users)
- `theme_name` (string)
- `is_dark_mode` (boolean)
- `border_radius` (string) - will be deprecated
- `font_size` (string) - will be deprecated
- `compact_mode` (boolean) - will be deprecated

Only `theme_name` and `is_dark_mode` will be actively used after this refactor.
