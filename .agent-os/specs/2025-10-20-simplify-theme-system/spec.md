# Spec Requirements Document

> Spec: Simplify Theme System
> Created: 2025-10-20
> Status: Planning

## Overview

Refactor the theme management system to use database persistence as the single source of truth, removing defensive JavaScript hacks and localStorage complexity. This will eliminate theme switching bugs and create a clean, maintainable architecture that persists user preferences across devices.

## User Stories

### Reliable Theme Persistence

As a user, I want my theme choice to persist reliably across sessions and devices, so that I don't have to repeatedly select my preferred theme.

**Detailed Workflow:**
When a user selects a theme (e.g., "Ocean" in light mode), that choice is immediately saved to the database and associated with their user account. When they log in from a different device or browser, their theme preference is loaded from the database and applied consistently. The theme never switches unexpectedly during navigation or page loads.

### Simple Theme Selection

As a user, I want to easily switch between predefined themes and toggle between light/dark modes, so that I can customize my experience without complexity.

**Detailed Workflow:**
The user opens a theme selector that displays all 24 predefined themes with visual previews. They can click any theme to apply it, and use a simple toggle to switch between light and dark variants. The change applies immediately with smooth visual feedback, and the selection is saved automatically without requiring confirmation dialogs or page refreshes.

### Consistent Visual Experience

As a user, I want the theme to remain stable during page navigation and interactions, so that I have a professional, glitch-free experience.

**Detailed Workflow:**
When the user navigates between pages using Livewire, the theme remains consistent without flashing, flickering, or reverting to a different color scheme. The application never displays the wrong theme briefly before correcting itself. All UI components, modals, and dropdowns respect the selected theme consistently.

## Spec Scope

1. **Database-Only Persistence** - Remove all localStorage usage and use the database as the single source of truth for theme preferences, ensuring cross-device consistency.

2. **Remove Defensive JavaScript** - Eliminate MutationObserver protection, matchMedia override, authorization flags, and all hacky defensive code that prevents theme changes.

3. **Remove Flux Appearance Integration** - Remove the `@fluxAppearance` directive and Flux's appearance settings that conflict with the custom theme system.

4. **Remove Theme Customizer** - Delete the full ThemeCustomizer Livewire component and keep only ThemeQuickToggle for switching between predefined themes.

5. **Simplified Theme Initialization** - Create a clean, minimal initialization script in the head that loads the theme from server-side data without localStorage checks or defensive logic.

## Out of Scope

- Creating new predefined themes (all 24 existing themes will be retained)
- System preference auto-switching (users explicitly choose light or dark themes)
- Custom theme creation (removing customizer means no user-generated themes)
- Theme sharing or exporting functionality
- Advanced theme scheduling or automatic switching based on time of day

## Expected Deliverable

1. **Clean Theme Switching** - Users can switch between any of the 24 predefined themes and toggle light/dark modes without experiencing unexpected theme changes, flashing, or reverting behavior during navigation or page loads.

2. **Cross-Device Persistence** - Theme preferences persist correctly when users access the application from different devices or browsers, with the database serving as the authoritative source.

3. **Simplified Codebase** - The theme implementation uses minimal, clean code without defensive hacks, making it easy to understand, maintain, and extend in the future.

## Spec Documentation

- Tasks: @.agent-os/specs/2025-10-20-simplify-theme-system/tasks.md
- Technical Specification: @.agent-os/specs/2025-10-20-simplify-theme-system/sub-specs/technical-spec.md
- Database Schema: @.agent-os/specs/2025-10-20-simplify-theme-system/sub-specs/database-schema.md
- Tests Specification: @.agent-os/specs/2025-10-20-simplify-theme-system/sub-specs/tests.md
