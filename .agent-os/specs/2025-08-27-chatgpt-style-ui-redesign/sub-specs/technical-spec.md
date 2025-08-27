# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## Core Requirements

### Dashboard Textarea Implementation
- Create a dashboard page at `/` with a prominent textarea
- Textarea must auto-focus on page load using Alpine.js or vanilla JS
- Placeholder text: "Describe your business idea..."
- Submit via Enter key (with Shift+Enter for new lines) or submit button
- Character limit: 2000 characters with counter display

### Idea Creation Flow
- On submission, create idea record with:
  - Auto-generated slug using Laravel's Str::slug() + timestamp
  - Title extracted from first line or first 50 characters
  - Full description stored in database
  - Session association for anonymous users
- Redirect to `/idea/{slug}` immediately after creation
- No loading screens or intermediate steps

### Sidebar Implementation
- Fixed left sidebar (similar to ChatGPT layout)
- Display all ideas in reverse chronological order (newest first)
- Each item shows truncated title/preview
- "New Idea" button at top that links to dashboard
- Click any idea to navigate to its page
- Simple scrolling (infinite scroll can be added later if needed)

## Implementation Approach

Use Livewire components for interactive elements while keeping the core functionality simple:
- Dashboard uses a basic Livewire component for the textarea form
- Sidebar is a Livewire component that updates when new ideas are created
- Idea pages use existing name/logo generation components
- No need for complex state management or SPA architecture

## Key Components

### Dashboard Components
- `dashboard.blade.php` - Main dashboard view with textarea
- `IdeaCreator` - Livewire component handling form submission

### Layout Components  
- `app-layout.blade.php` - Main layout with sidebar
- `IdeaSidebar` - Livewire component for idea list

### Idea Page Components
- `idea/show.blade.php` - Individual idea view
- Reuse existing `NameGenerator` and `LogoGenerator` components

## Database Structure

Simple structure focused on core needs:
- `ideas` table: id, slug, title, description, session_id, created_at, updated_at
- `idea_generations` table: Links to existing generation data
- Use existing tables for names/logos where possible

## URL Structure

- `/` - Dashboard with textarea
- `/idea/{slug}` - Individual idea page
- All other existing routes remain unchanged