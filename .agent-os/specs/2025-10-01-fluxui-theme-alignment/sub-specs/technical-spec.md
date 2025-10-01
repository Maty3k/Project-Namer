# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-01-fluxui-theme-alignment/spec.md

> Created: 2025-10-01
> Version: 1.0.0

## Technical Requirements

### FluxUI Standard Color Variables

**Base Color System:**
- Use `zinc` as the default base color (already hardcoded in FluxUI)
- Support remapping `zinc` to other gray shades (slate, neutral, gray, stone) via @theme block
- All gray-scale references use `zinc-*` utilities (zinc-50, zinc-100, ..., zinc-950)

**Accent Color System:**
- Define three accent variables in @theme block:
  - `--color-accent`: Main accent color for button backgrounds
  - `--color-accent-content`: Darker shade for readable text content
  - `--color-accent-foreground`: Color for text on accent backgrounds (typically white)
- Define dark mode variants in @layer theme .dark block
- Use Tailwind utilities: `bg-accent`, `text-accent-content`, `text-accent-foreground`

**Required Color Variables (from FluxUI docs):**
```css
@theme {
    /* Accent colors for light mode */
    --color-accent: var(--color-[palette]-[shade]);
    --color-accent-content: var(--color-[palette]-[shade]);
    --color-accent-foreground: var(--color-white);
}

@layer theme {
    .dark {
        /* Accent colors for dark mode */
        --color-accent: var(--color-[palette]-[shade]);
        --color-accent-content: var(--color-[palette]-[shade]);
        --color-accent-foreground: var(--color-white);
    }
}
```

### CSS File Structure

**Keep:**
- Font configuration (--font-sans)
- Custom breakpoints (--breakpoint-xs)
- Animation variables (--ease-*, --duration-*)
- FluxUI custom variant for dark mode: `@custom-variant dark (&:where(.dark, .dark *))`
- Accessibility-related utilities (sr-only, focus-visible)
- Mobile-specific optimizations (hardware acceleration, touch targets)
- Animation keyframes (@keyframes ripple, shimmer, fadeIn, etc.)

**Remove:**
- All custom primary color variables (--color-primary-50 through --color-primary-900)
- Custom color variables (--color-background, --color-text)
- Manually defined color scales (--color-zinc-*, --color-green-*, --color-red-* hex values)
- Custom theme classes (.theme-hover, .theme-text-primary, .theme-interactive, etc.)
- Color override selectors (.themed-generator-dashboard *, .themed-sidebar *, etc.)
- Header text color overrides (header [data-flux-profile] selectors)

**Modify:**
- Flux field/input focus states to use `ring-accent` instead of hardcoded accent color
- Border color defaults to use zinc instead of custom gray variables

### Template Color Migration

**Replace Hardcoded Colors:**
- `bg-blue-*` → `bg-accent` or `bg-zinc-*` (depending on context)
- `text-blue-*` → `text-accent-content` or `text-zinc-*`
- `border-blue-*` → `border-accent` or `border-zinc-*`
- `bg-gray-*` → `bg-zinc-*`
- `text-gray-*` → `text-zinc-*`
- `border-gray-*` → `border-zinc-*`
- `bg-green-*` → `bg-accent` (when used for success states)
- `bg-red-*` → Keep for error states (use standard Tailwind red-*)
- `dark:bg-gray-*` → `dark:bg-zinc-*`

**Component-Specific Migrations:**
- Buttons: Use FluxUI button variants instead of custom background colors
- Links: Use `text-accent-content` or `:accent` prop
- Inputs/Forms: Use `border-zinc-*` and `focus:ring-accent`
- Cards: Use `bg-white dark:bg-zinc-900` and `border-zinc-*`
- Skeletons: Use `bg-zinc-200 dark:bg-zinc-700`

### Theme Customizer Component Changes

**Data Structure:**
- Remove: `primaryColor`, `backgroundColor`, `textColor` properties
- Add: `baseColorShade` (slate, neutral, gray, stone, or zinc-default)
- Keep: `accentColor`, `themeName`, `isDarkMode`
- Modify: Accent color properties to use FluxUI's three-variable system

**Generated CSS Format:**
```css
@theme {
    /* Optional: Remap zinc to different gray if not using default */
    --color-zinc-50: var(--color-[baseShade]-50);
    /* ... all shades ... */

    /* Accent colors */
    --color-accent: var(--color-[palette]-[shade]);
    --color-accent-content: var(--color-[palette]-[darker-shade]);
    --color-accent-foreground: var(--color-white);
}

@layer theme {
    .dark {
        --color-accent: var(--color-[palette]-[shade]);
        --color-accent-content: var(--color-[palette]-[lighter-shade]);
        --color-accent-foreground: var(--color-white);
    }
}
```

**Accessibility Calculation:**
- Calculate contrast ratio between accent and accent-foreground
- Calculate contrast ratio between accent-content and backgrounds
- Warn if any ratio is below WCAG AA standard (4.5:1 for normal text, 3:1 for large text)

## Approach Options

### Option A: Manual Template Migration (Selected)

**Approach:**
1. Create a migration guide listing all color class replacements
2. Manually update each Blade template file
3. Remove custom CSS classes and variables from app.css
4. Update theme customizer component
5. Test each page/component visually

**Pros:**
- Complete control over each change
- Opportunity to improve markup while migrating
- Easy to verify changes visually
- Can handle edge cases contextually

**Cons:**
- Time-consuming for large codebases
- Potential for human error or missed instances
- Requires thorough manual testing

**Rationale:** Given the complexity of template contexts (some blues should become accent, others should stay as utility colors for specific purposes like info badges), manual migration allows for intelligent decision-making about which color to use in each context. Automated search-and-replace would be too rigid.

### Option B: Automated Script Migration

**Approach:**
1. Write PHP script to parse Blade files and replace color classes
2. Run script across all templates
3. Manual review and adjustment of edge cases
4. Update CSS and theme customizer

**Pros:**
- Faster for bulk replacements
- Consistent replacements across files
- Reduces manual labor

**Cons:**
- Cannot handle contextual decisions (when to use accent vs. zinc)
- May introduce errors that require extensive debugging
- Still requires manual review of every file
- Script complexity for handling Blade syntax

**Rationale:** Rejected because the contextual nature of color choices (accent for interactive primary elements vs. zinc for general UI) requires human judgment.

## External Dependencies

None required. This spec uses existing FluxUI and Tailwind CSS functionality without adding new libraries.

## Performance Considerations

**Improvements:**
- Smaller CSS file size (removing ~300 lines of custom color classes and variables)
- Better browser performance (fewer custom color calculations via color-mix)
- Faster theme switching (fewer CSS variables to update)

**No Impact:**
- Runtime performance (still using CSS variables)
- Bundle size (no new dependencies)
