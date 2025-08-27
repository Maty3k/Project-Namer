# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-sidebar/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## Technical Requirements

### Session Management System
- **Session Creation**: Instant new session creation with unique IDs
- **Session Storage**: SQLite database storage for persistence
- **Session State**: Complete state preservation including:
  - Business description
  - Generation mode settings
  - Generated names and domain results
  - Logo generation status
  - Timestamps and metadata
- **Session Switching**: Instant context switching without page reload
- **Auto-save**: Automatic session saving on every significant action

### History Interface Requirements
- **List Rendering**: Virtual scrolling for performance with large histories
- **Grouping Logic**: Intelligent date-based grouping:
  - Today
  - Yesterday
  - Previous 7 Days
  - Previous 30 Days
  - Older (grouped by month)
- **Preview Generation**: Automatic title and preview text from business description
- **Search Implementation**: Full-text search using SQLite FTS5 or similar
- **Sorting Options**: By date, alphabetically, or by frequency of access

### Focus Mode Implementation
- **Animation**: Smooth slide-out animation (300ms ease-out)
- **State Persistence**: LocalStorage for user preference
- **Responsive Behavior**: Automatic focus mode on mobile devices
- **Keyboard Shortcuts**: Cmd/Ctrl + / to toggle
- **Visual Indicators**: Subtle floating button when sidebar hidden

### Animation Requirements

#### Sidebar Animations
- **Slide In/Out**: 300ms cubic-bezier(0.4, 0, 0.2, 1) for smooth acceleration
- **Width Transition**: Animate from 320px to 0 when hiding
- **Backdrop Fade**: 200ms fade for mobile overlay backdrop
- **Content Shift**: Main content smoothly expands as sidebar collapses

#### Session Card Animations
- **Hover Effects**: 150ms ease-out for all hover states
  - Scale: subtle 1.02 scale on hover
  - Shadow: elevation change from 1px to 4px
  - Background: smooth color transition
- **Click Feedback**: 100ms scale(0.98) on click for tactile feel
- **Stagger Load**: 50ms stagger delay between cards on initial load
- **Slide In**: New sessions slide in from top with fade (300ms)
- **Slide Out**: Deleted sessions slide out to left with fade (200ms)

#### Interactive Elements
- **New Session Button**:
  - Hover: rotate icon 90deg (200ms)
  - Click: pulse animation (300ms)
  - Success: checkmark morph animation
- **Search Bar**:
  - Focus: smooth border color change (150ms)
  - Expand: width expansion on focus (200ms)
  - Loading: skeleton shimmer effect
- **Action Menus**:
  - Open: scale from 0.8 to 1 with fade (150ms)
  - Close: fade out (100ms)
  - Item hover: background slide effect

#### Micro-interactions
- **Star/Favorite**: 
  - Click: bounce animation (400ms)
  - Fill: star fills with gold gradient (200ms)
- **Delete Confirmation**:
  - Shake animation on dangerous actions (300ms)
  - Red pulse for attention (600ms)
- **Rename Inline**:
  - Smooth height adjustment (200ms)
  - Focus ring expansion (150ms)
- **Loading States**:
  - Skeleton screens with wave animation
  - Spinner with smooth rotation
  - Progress bars with easing

#### Page Transitions
- **Session Switching**: 
  - Old content fades out (150ms)
  - New content fades in (150ms)
  - Smooth scroll to top (300ms)
- **Infinite Scroll**:
  - New batch slides up with fade (200ms)
  - Loading indicator at bottom
- **Error States**:
  - Shake animation for errors (300ms)
  - Smooth appearance of error messages

#### Performance Optimizations
- **GPU Acceleration**: Use transform and opacity for animations
- **Will-Change**: Apply to frequently animated properties
- **Reduced Motion**: Respect prefers-reduced-motion setting
- **Frame Rate**: Target 60fps for all animations
- **Throttling**: Limit animation triggers to prevent jank

### UI/UX Components
- **Sidebar Width**: 320px on desktop, full-width overlay on mobile
- **Session Cards**: Compact design with:
  - Auto-generated icon or emoji
  - Title (truncated to 40 chars)
  - Preview text (truncated to 80 chars)
  - Timestamp (relative format)
  - Action menu (three dots)
- **New Session Button**: Prominent placement with icon
- **Search Bar**: Sticky positioning at top of history

## Approach Options

**Option A: Full Client-Side State Management**
- Pros: Fast, no server roundtrips, works offline
- Cons: Limited storage, no cross-device sync, data loss risk

**Option B: Server-Side Session Management with Livewire** (Selected)
- Pros: Unlimited storage, data persistence, cross-device access, real-time updates
- Cons: Requires server calls, slightly slower switching

**Option C: Hybrid Approach**
- Pros: Best of both worlds, offline capability
- Cons: Complex synchronization logic, potential conflicts

**Rationale:** Option B selected for data integrity, unlimited session storage, and seamless integration with existing Livewire architecture. Performance impact minimal with proper caching.

## External Dependencies

None required - all functionality can be built with existing Laravel/Livewire/Alpine.js stack.

## Animation Implementation Details

### CSS Custom Properties
```css
:root {
  --sidebar-width: 320px;
  --animation-fast: 150ms;
  --animation-base: 300ms;
  --animation-slow: 600ms;
  --ease-out: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-in-out: cubic-bezier(0.4, 0, 0.6, 1);
  --ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
}
```

### Tailwind Animation Classes
```javascript
// tailwind.config.js extensions
animation: {
  'slide-in-left': 'slideInLeft 300ms var(--ease-out)',
  'slide-out-left': 'slideOutLeft 200ms var(--ease-out)',
  'slide-in-top': 'slideInTop 300ms var(--ease-out)',
  'fade-in-scale': 'fadeInScale 200ms var(--ease-out)',
  'pulse-soft': 'pulseSoft 2s infinite',
  'shake': 'shake 300ms var(--ease-out)',
  'bounce-subtle': 'bounceSubtle 400ms var(--ease-bounce)',
  'shimmer': 'shimmer 2s infinite',
}
```

### Alpine.js Transitions
```javascript
// Sidebar collapse/expand
x-transition:enter="transition ease-out duration-300"
x-transition:enter-start="transform -translate-x-full opacity-0"
x-transition:enter-end="transform translate-x-0 opacity-100"
x-transition:leave="transition ease-in duration-200"
x-transition:leave-start="transform translate-x-0 opacity-100"
x-transition:leave-end="transform -translate-x-full opacity-0"

// Session cards
x-transition:enter="transition ease-out duration-200 delay-[index*50]ms"
x-transition:enter-start="transform translate-y-4 opacity-0"
x-transition:enter-end="transform translate-y-0 opacity-100"
```

### Livewire Loading States
```html
<!-- Loading skeleton animation -->
<div wire:loading class="animate-pulse">
  <div class="h-4 bg-gray-200 rounded animate-shimmer"></div>
</div>

<!-- Smooth content replacement -->
<div wire:loading.remove wire:target="loadSession" 
     x-transition:leave="transition-opacity duration-150"
     x-transition:leave-end="opacity-0">
  <!-- Current content -->
</div>
```

## Performance Considerations

- **Lazy Loading**: Load session history in batches of 20
- **Debounced Search**: 300ms debounce on search input
- **Optimistic UI**: Immediate visual feedback before server confirmation
- **Caching Strategy**: Cache recent 10 sessions in memory
- **Database Indexing**: Index on user_id, created_at, and search fields

## Accessibility Requirements

- **Keyboard Navigation**: Full keyboard support for all actions
- **ARIA Labels**: Proper labeling for screen readers
- **Focus Management**: Logical focus flow when toggling modes
- **Announcements**: Live regions for state changes
- **High Contrast**: Support for high contrast mode