# Spec Requirements Document

> Spec: FluxUI Theme Alignment
> Created: 2025-10-01
> Status: Planning

## Overview

Simplify and align the theme system to use only FluxUI's standard theming variables, ensuring consistent contrast and accessibility in both light and dark modes. The refactored system will use FluxUI's base color (zinc), accent color variables (`--color-accent`, `--color-accent-content`, `--color-accent-foreground`), and standard Tailwind utilities throughout all templates.

## User Stories

### Theme Consistency Across the Application

As a user, I want consistent theming throughout the application, so that color choices automatically ensure proper contrast and accessibility without custom overrides.

**Workflow:**
- User selects a predefined or custom theme from the theme customizer
- Theme applies consistently across all UI components using FluxUI's standard variables
- Light and dark modes automatically maintain proper contrast ratios
- All interactive elements (buttons, links, inputs) use the same accent color system
- No visual inconsistencies between different pages or components

**Problem Solved:** Eliminates the current inconsistency where custom color variables (primary, accent, background, text) create varying contrast ratios and visual discrepancies across different parts of the application.

### Simplified Theme Customization

As a developer or designer, I want to customize themes using only FluxUI's standard variables, so that I don't need to worry about contrast calculations or accessibility compliance.

**Workflow:**
- Define base color by remapping zinc to any gray shade (slate, neutral, gray, stone)
- Set accent colors using FluxUI's three-variable system
- All components automatically inherit proper colors for light and dark modes
- No need to define custom color scales or contrast calculations

**Problem Solved:** Removes the complexity of maintaining custom color scales (primary-50 through primary-900) and theme-aware CSS classes, leveraging FluxUI's built-in accessibility features instead.

### Easier Theme Maintenance

As a developer, I want all color references to use standard Tailwind utilities, so that updating themes doesn't require searching for custom CSS classes or inline styles.

**Workflow:**
- Search codebase for color usage by looking at standard classes only (`bg-zinc-*`, `text-zinc-*`, `bg-accent`, etc.)
- Update theme by changing CSS variables in one location (app.css)
- No need to update inline styles or custom theme classes throughout templates

**Problem Solved:** Eliminates the need to maintain custom CSS classes like `theme-hover`, `theme-text-primary`, `theme-interactive`, etc., and removes inline style color overrides scattered across templates.

## Spec Scope

1. **FluxUI Standard Variables** - Replace all custom color variables (--color-primary-*, --color-background, --color-text) with FluxUI's standard accent color system (--color-accent, --color-accent-content, --color-accent-foreground) and base color (zinc or remapped gray shades).

2. **Template Color Cleanup** - Update all Blade templates to use only standard Tailwind utilities (bg-zinc-*, text-zinc-*, bg-accent, text-accent-foreground, etc.) instead of hardcoded colors (bg-blue-500, text-gray-900, etc.) or custom variables.

3. **CSS Simplification** - Remove custom theme-aware CSS classes (theme-hover, theme-text-primary, theme-interactive, etc.) and replace their usage with standard FluxUI/Tailwind utilities.

4. **Theme Customizer Refactor** - Update the theme customizer component to work with FluxUI's standard variables, allowing users to select base gray shade and accent colors that automatically maintain proper contrast.

5. **Dark Mode Consistency** - Ensure dark mode uses FluxUI's standard @layer theme approach with proper accent color adjustments for optimal visibility.

## Out of Scope

- Adding new theme customization features beyond what FluxUI provides
- Creating custom color palettes outside of standard Tailwind color scales
- Implementing theme animations or transitions (keep existing behavior)
- Modifying FluxUI components themselves or their internal styling

## Expected Deliverable

1. **Simplified CSS File** - resources/css/app.css contains only FluxUI standard variables (@theme and @layer theme blocks) with base color remapping (if not using zinc) and accent colors, with all custom color variables and theme classes removed.

2. **Clean Templates** - All Blade templates use standard Tailwind utilities (bg-zinc-100, text-zinc-900, bg-accent, etc.) with no hardcoded color values (no bg-blue-500, text-gray-900, etc.) or custom color variables.

3. **Functional Theme Customizer** - Theme customizer component allows selection of base gray shade and accent colors, generating valid FluxUI CSS that maintains accessibility standards, with import/export functionality preserved.

## Spec Documentation

- Tasks: @.agent-os/specs/2025-10-01-fluxui-theme-alignment/tasks.md
- Technical Specification: @.agent-os/specs/2025-10-01-fluxui-theme-alignment/sub-specs/technical-spec.md
- Tests Specification: @.agent-os/specs/2025-10-01-fluxui-theme-alignment/sub-specs/tests.md
