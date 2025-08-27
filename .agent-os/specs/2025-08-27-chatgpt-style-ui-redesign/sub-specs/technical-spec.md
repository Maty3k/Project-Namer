# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## Technical Requirements

### Interface Architecture
- Implement a two-panel layout with fixed left sidebar and fluid right content area
- Sidebar width: 260px on desktop, collapsible on mobile
- Content area: Responsive with max-width constraint for readability
- Maintain scroll position when navigating between ideas
- Implement virtual scrolling for sidebar when ideas exceed viewport

### Session Management System
- Generate unique slugs for each idea using Laravel's Str::slug() with timestamp suffix
- Store session state in database with JSON column for generation history
- Implement session versioning to track iterations and changes
- Support soft-deletes for idea recovery
- Cache frequently accessed sessions using Laravel's cache system

### Real-time UI Updates
- Use Livewire for reactive components without full page reloads
- Implement optimistic UI updates for immediate feedback
- WebSocket support for future real-time features (prepared but not active)
- Loading states using Flux UI skeleton components

### Performance Requirements
- Page load time < 500ms for cached sessions
- Sidebar render < 100ms even with 1000+ ideas
- Implement lazy loading for idea previews in sidebar
- Database indexing on slug, created_at, and user session identifiers
- Use Laravel's query optimization and eager loading

## Approach Options

**Option A: Full SPA with Inertia.js**
- Pros: Smooth navigation, no page refreshes, excellent UX
- Cons: More complex setup, requires Vue/React knowledge, larger bundle size

**Option B: Livewire with Turbo** (Selected)
- Pros: Stays within Laravel/Blade ecosystem, simpler implementation, progressive enhancement
- Cons: Slightly less smooth than SPA for some interactions

**Option C: Traditional Server-Rendered with HTMX**
- Pros: Minimal JavaScript, very fast initial loads
- Cons: Less interactive feel, more server requests

**Rationale:** Option B selected to maintain consistency with existing TALL stack while providing modern interactive experience. Livewire's recent improvements make it ideal for this chat-like interface pattern.

## Component Architecture

### Layout Components
- `AppLayout.blade.php` - Main application shell with sidebar
- `Sidebar.blade.php` - Livewire component for idea navigation
- `IdeaListItem.blade.php` - Individual sidebar item component

### Page Components
- `Dashboard.blade.php` - Landing page with input form
- `IdeaDetail.blade.php` - Individual idea page with all features
- `IdeaInput.blade.php` - Reusable input component

### Livewire Components
- `IdeaSidebar` - Manages sidebar state and infinite scroll
- `IdeaCreator` - Handles new idea submission
- `IdeaSession` - Manages individual idea page state
- `NameGenerator` - Existing component, integrated into new flow
- `LogoGenerator` - Existing component, integrated into new flow

## State Management

### Frontend State
- Use Alpine.js for local UI state (sidebar open/closed, etc.)
- Livewire properties for server-synced state
- LocalStorage for user preferences (sidebar width, theme, etc.)

### Backend State
- Session-based storage for anonymous users
- Database persistence for all ideas
- Redis caching for frequently accessed data
- Queue system for background processing

## URL Structure

- `/` - Dashboard with new idea input
- `/idea/{slug}` - Individual idea page
- `/idea/{slug}/names` - Name generation view (optional, could be tabs)
- `/idea/{slug}/logos` - Logo generation view (optional, could be tabs)
- `/api/ideas` - JSON endpoint for sidebar data

## External Dependencies

- **FluxUI Pro** - Enhanced UI components for professional interface
  - Justification: Already in use, provides consistent design system
  
- **Laravel Livewire** - Version 3.x for reactive components
  - Justification: Core to TALL stack, already integrated
  
- **Alpine.js** - Version 3.x for client-side interactivity
  - Justification: Lightweight, integrates perfectly with Livewire
  
- **Floating UI** - For tooltip and popover positioning
  - Justification: Better positioning than native browser APIs

## Security Considerations

- CSRF protection on all forms
- XSS prevention through Laravel's automatic escaping
- Rate limiting on idea creation (10 per minute)
- Slug validation to prevent injection attacks
- Session isolation for multi-user future-proofing

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers: iOS Safari 14+, Chrome Mobile

## Accessibility Requirements

- WCAG 2.1 AA compliance
- Keyboard navigation for all interactive elements
- ARIA labels for screen readers
- Focus management during navigation
- High contrast mode support