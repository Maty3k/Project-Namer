# Spec Requirements Document

> Spec: CSS Variable-Based Theming System
> Created: 2025-10-14
> Status: Planning

## Overview

Migrate from inline hex color styles to a CSS variable-based theming system that eliminates light/dark mode inconsistencies and provides maintainable, robust color management throughout the application.

## User Stories

### Consistent Theme Experience

As a user, I want my selected theme to display correctly in both light and dark modes without any color inconsistencies, so that I can enjoy a professional, polished interface that adapts properly to my preference.

**Workflow:** User selects a theme from the theme customizer, toggles between light and dark modes, and sees all UI elements correctly styled with appropriate contrast and no color inversions or light-text-on-light-background issues.

### Developer-Friendly Theme System

As a developer, I want to define themes using CSS variables in dedicated theme files, so that I can easily create, modify, and maintain themes without touching template code or worrying about hex values scattered throughout the codebase.

**Workflow:** Developer creates a new theme by adding a single CSS file with light and dark mode variants, and the theme becomes immediately available throughout the application without any template modifications.

## Spec Scope

1. **Theme CSS File Structure** - Create one CSS file per predefined theme in `public/css/themes/`, each defining both light and dark mode color variants using CSS variables

2. **CSS Variable Standardization** - Define a consistent set of CSS variables (--color-primary, --color-background, --color-text, etc.) that all templates will reference instead of hex values

3. **Template Hex Removal** - Remove all inline hex color values from Blade templates (15 files identified) and replace with CSS variable references

4. **Database Schema Simplification** - Update user_theme_preferences table to store only theme_name and is_dark_mode, removing individual color columns

5. **Theme Loading System** - Implement dynamic CSS file loading based on user's selected theme and dark mode preference

6. **Migration & Cleanup** - Deprecate ThemeService hex-based methods, update ThemeHelper, and ensure backward compatibility during transition

## Out of Scope

- Custom user-created themes (users will select from predefined themes only)
- Theme preview generation or screenshots
- Migration of existing user theme preferences (users will make fresh selections)
- Theme import/export functionality

## Expected Deliverable

1. User can select from 18 predefined themes with consistent light/dark mode rendering
2. Zero hex color values exist in any Blade template files
3. All theme colors are defined in dedicated CSS files using CSS variables
4. Theme changes apply immediately without page reload via Livewire
5. Comprehensive test suite ensures theme consistency and accessibility compliance

## Spec Documentation

- Tasks: @.agent-os/specs/2025-10-14-css-variable-theming/tasks.md
- Technical Specification: @.agent-os/specs/2025-10-14-css-variable-theming/sub-specs/technical-spec.md
- Database Schema: @.agent-os/specs/2025-10-14-css-variable-theming/sub-specs/database-schema.md
- Tests Specification: @.agent-os/specs/2025-10-14-css-variable-theming/sub-specs/tests.md
