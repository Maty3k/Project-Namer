# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-09-01-project-workflow-ui/spec.md

> Created: 2025-09-01
> Version: 1.0.0

## Technical Requirements

### Dashboard Implementation
- Single Livewire component for the dashboard page
- Textarea with wire:model binding for real-time data capture
- Form submission handling with validation (minimum 10 characters for project description)
- Automatic project UUID generation using Laravel's Str::uuid()
- Default project name generation (e.g., "Project " + incrementing number or timestamp)
- Redirect to project page using Laravel's redirect()->route() after save

### Project Page Architecture
- Livewire component with UUID route parameter binding
- Real-time textarea updates using wire:model.live.debounce.500ms for description changes
- Inline editing for project name with FluxUI input component
- Dynamic Flux table implementation for name results
- Each table row as a self-contained Livewire component for independent state management
- Optimistic UI updates for better perceived performance

### Sidebar Component
- Persistent Livewire component loaded in the main layout
- Query projects ordered by created_at DESC
- Active project highlighting based on current route
- Click handlers using wire:click for navigation
- Real-time updates when project names change using Livewire events

### Name Result Cards
- FluxUI card components for each result row
- Collapsible/expandable sections for domains and logos
- State tracking for hidden/visible status per card
- Lazy loading for logo generation (only when requested)
- Visual indicators for selected name (highlight, checkmark icon)
- Transition animations using Alpine.js for smooth show/hide

### State Management Strategy
- Project state in database (projects table)
- Name results in database (name_suggestions table)
- UI state (filters, visibility) in Livewire component properties
- Selected name stored in projects table (selected_name_id foreign key)
- Session storage for temporary UI preferences

## UI/UX Specifications

### Layout Structure
- Two-column layout with fixed sidebar (250px width)
- Main content area with max-width constraint for readability
- Responsive breakpoint at 768px where sidebar becomes collapsible
- Consistent spacing using Tailwind's spacing scale

### Component Hierarchy
```
AppLayout
├── Sidebar (Livewire)
│   ├── NewProjectButton
│   └── ProjectList
│       └── ProjectItem (foreach)
└── Main Content Area
    ├── Dashboard (Livewire) OR
    └── ProjectPage (Livewire)
        ├── ProjectHeader
        │   ├── EditableName
        │   └── ActionButtons
        ├── DescriptionTextarea
        ├── FilterBar
        └── NameResultsTable
            └── NameResultCard (foreach, Livewire)
                ├── NameHeader
                ├── DomainsSection
                └── LogosSection
```

### Interactive Elements
- All buttons use FluxUI button components with consistent variants
- Loading states with FluxUI spinners during API calls
- Toast notifications for success/error messages
- Confirmation modals for destructive actions (if needed)

## Integration Requirements

### Routes
- GET /dashboard - Dashboard page
- GET /project/{uuid} - Project detail page
- POST /api/projects - Create project (handled by Livewire)
- PATCH /api/projects/{uuid} - Update project (handled by Livewire)

### Livewire Events
- `project-created` - Emitted when new project is created
- `project-updated` - Emitted when project name/description changes
- `name-selected` - Emitted when a name is selected
- `refresh-sidebar` - Triggers sidebar project list refresh

### Database Queries
- Eager load relationships to prevent N+1 queries
- Use database indexing on uuid and created_at columns
- Implement query scopes for active/hidden filtering

## Approach Options

**Option A: Single Page Application Style**
- Pros: Smooth transitions, no page reloads, unified state management
- Cons: More complex state management, potential memory issues with large datasets

**Option B: Traditional Page Navigation with Livewire Components** (Selected)
- Pros: Simpler state management, better SEO, follows Laravel conventions
- Cons: Page reloads between dashboard and projects

**Rationale:** Option B aligns better with Laravel and Livewire best practices, provides better performance for large numbers of projects, and simplifies the mental model for development and maintenance.

## External Dependencies

No new external dependencies are required for this spec. The implementation will use:
- Existing Laravel 12 framework
- Existing Livewire 3 installation
- Existing FluxUI Pro components
- Existing Alpine.js (bundled with Livewire)