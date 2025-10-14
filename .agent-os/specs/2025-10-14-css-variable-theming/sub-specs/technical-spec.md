# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-14-css-variable-theming/spec.md

> Created: 2025-10-14
> Version: 1.0.0

## Technical Requirements

### CSS Variable Naming Convention

**Standard Color Variables:**
- `--color-primary`: Primary theme color (buttons, links, accents)
- `--color-secondary`: Secondary theme color (alternative actions)
- `--color-accent`: Accent color (highlights, badges, alerts)
- `--color-background`: Main background color
- `--color-surface`: Surface/card background color
- `--color-text-primary`: Primary text color (headings, body text)
- `--color-text-secondary`: Secondary text color (muted text, descriptions)
- `--color-border`: Border color for dividers and outlines

**UI Preference Variables:**
- `--border-radius-base`: Base border radius (0px, 0.125rem, 0.375rem, 0.75rem, 9999px)
- `--font-size-base`: Base font size (0.875rem, 1rem, 1.125rem)

### Theme CSS File Structure

Each theme file will follow this structure:

```css
/* themes/theme-name.css */

/* Light Mode (Default) */
:root {
  --color-primary: #3b82f6;
  --color-secondary: #8b5cf6;
  --color-accent: #059669;
  --color-background: #ffffff;
  --color-surface: #f8fafc;
  --color-text-primary: #111827;
  --color-text-secondary: #6b7280;
  --color-border: #d1d5db;
}

/* Dark Mode */
:root.dark {
  --color-primary: #3b82f6;
  --color-secondary: #8b5cf6;
  --color-accent: #059669;
  --color-background: #111827;
  --color-surface: #1f2937;
  --color-text-primary: #f9fafb;
  --color-text-secondary: #d1d5db;
  --color-border: #374151;
}
```

### Theme File Organization

**Location:** `public/css/themes/`

**Files to Create:**
- `default.css` - Default Blue theme
- `dark.css` - Dark Mode theme (maintained for backward compatibility, but actually just default in dark mode)
- `ocean.css` - Ocean Breeze theme
- `sunset.css` - Warm Sunset theme
- `forest.css` - Forest Green theme
- `cosmic-violet.css` - Cosmic Violet theme
- `coral-reef.css` - Coral Reef theme
- `midnight-teal.css` - Midnight Teal theme
- `summer.css` - Summer Coral theme
- `winter.css` - Winter Frost theme
- `halloween.css` - Halloween Night theme
- `spring.css` - Spring Bloom theme
- `autumn.css` - Autumn Harvest theme
- `neon-cyber.css` - Neon Cyber theme
- `electric-blue.css` - Electric Blue theme
- `hot-pink.css` - Hot Pink theme
- `lava-red.css` - Lava Red theme
- `lime-punch.css` - Lime Punch theme
- `gold-rush.css` - Gold Rush theme
- `matrix-green.css` - Matrix Green theme

### Dynamic Theme Loading Approach

**Selected Approach: Dynamic Link Tag Injection via Livewire**

The application will use a Livewire component to dynamically inject the appropriate theme CSS file based on user preferences:

**Pros:**
- Real-time theme switching without page reload
- Clean separation of concerns
- No build step required for theme changes
- Easy to add new themes

**Cons:**
- Small flash of unstyled content (mitigated with localStorage)
- Requires JavaScript enabled

**Implementation:**
1. Head partial includes theme link tag placeholder
2. Livewire component determines user's theme + dark mode preference
3. Theme CSS file path injected: `/css/themes/{theme-name}.css`
4. Dark mode toggled via `.dark` class on `:root` or `<html>` element
5. Alpine.js persistence ensures immediate application on page load

### Template Migration Strategy

**Find and Replace Pattern:**

Current inline style:
```blade
<div style="background-color: {{ $userTheme->primary_color }}">
```

New CSS variable reference:
```blade
<div style="background-color: var(--color-primary)">
```

**Affected Template Files:**
1. `resources/views/partials/head.blade.php` (remove entire inline style block)
2. `resources/views/livewire/theme-customizer.blade.php` (theme preview boxes)
3. `resources/views/livewire/session-sidebar.blade.php`
4. `resources/views/livewire/image-uploader.blade.php`
5. `resources/views/components/desktop-user-menu.blade.php`
6. `resources/views/components/layouts/project-workflow.blade.php`
7. `resources/views/livewire/sidebar.blade.php`
8. `resources/views/livewire/mood-board-canvas.blade.php`
9. `resources/views/shares/mood-board.blade.php`
10. `resources/views/livewire/name-generator-dashboard-backup.blade.php`
11. `resources/views/components/ai/accessible-interface.blade.php`
12. `resources/views/components/ai/accessible-model-selector.blade.php`
13. `resources/views/components/ai/accessibility-announcements.blade.php`
14. `resources/views/exports/pdf/template.blade.php`
15. `resources/views/welcome.blade.php`

**Preferred Method:**
- Eliminate inline `style` attributes where possible
- Use Tailwind utility classes that respect CSS variables
- For unavoidable inline styles, use CSS variable references

### Code Quality Requirements

- All CSS files must pass validation
- All color contrast ratios must maintain WCAG AA compliance minimum
- PHPStan must pass with no errors related to theme code
- All existing theme-related tests must pass with updates

## Approach Options

### Option A: CSS Files + Dynamic Link Tag (Selected)

**Description:** Create individual CSS files per theme and dynamically load the correct one via a `<link>` tag managed by Livewire/Alpine.

**Pros:**
- Clean separation between themes
- Easy to add/modify themes
- No build process required
- Leverages browser caching effectively

**Cons:**
- Small potential for FOUC (flash of unstyled content)
- Requires CSS files to be web-accessible

**Rationale:** This approach provides the best balance of maintainability, performance, and developer experience. The FOUC can be mitigated with Alpine.js persistence and localStorage, making this the ideal solution.

### Option B: Single CSS File with All Themes

**Description:** Generate one large CSS file containing all themes with scoped class names (e.g., `.theme-ocean`, `.theme-sunset`).

**Pros:**
- Single file to load
- No FOUC risk
- All themes immediately available

**Cons:**
- Large CSS file size
- Harder to maintain
- Requires build step for changes

**Rationale:** Rejected because it sacrifices maintainability and creates unnecessary complexity for minimal benefit.

### Option C: Inline CSS via Blade Template

**Description:** Generate CSS custom properties dynamically in a `<style>` tag within the head partial based on theme selection.

**Pros:**
- No external files needed
- Immediate theme application

**Cons:**
- Cannot leverage browser caching
- Duplicates CSS on every page load
- Harder to debug

**Rationale:** Rejected because this approach defeats the purpose of moving away from inline styles and creates performance issues.

## External Dependencies

None. This spec uses only existing Laravel, Livewire, Alpine.js, and Tailwind CSS capabilities.

## Implementation Notes

### Dark Mode Toggle

The application will use Alpine.js to toggle the `.dark` class on the root HTML element:

```javascript
Alpine.store('theme', {
    dark: Alpine.$persist(false).as('darkMode'),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
    }
});
```

### Theme Preview in Customizer

The theme customizer will show color swatches by reading the CSS variables from the loaded stylesheet rather than from database hex values.

### Accessibility Considerations

- Maintain minimum 4.5:1 contrast ratio for WCAG AA
- Test all themes with screen readers
- Ensure focus indicators remain visible in all themes
- Verify color-blind friendly palettes
