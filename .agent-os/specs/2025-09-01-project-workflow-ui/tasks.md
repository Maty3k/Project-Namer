# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-09-01-project-workflow-ui/spec.md

> Created: 2025-09-01
> Status: Ready for Implementation

## Tasks

- [ ] 1. Database Setup and Models
  - [ ] 1.1 Write tests for Project and NameSuggestion models
  - [ ] 1.2 Create migration for projects table
  - [ ] 1.3 Create migration for name_suggestions table  
  - [ ] 1.4 Create Project model with relationships and scopes
  - [ ] 1.5 Create NameSuggestion model with relationships and scopes
  - [ ] 1.6 Create model factories for testing
  - [ ] 1.7 Run migrations and verify database structure
  - [ ] 1.8 Verify all model tests pass

- [ ] 2. Dashboard Implementation
  - [ ] 2.1 Write tests for Dashboard Livewire component
  - [ ] 2.2 Create Dashboard Livewire component
  - [ ] 2.3 Create dashboard Blade view with textarea and button
  - [ ] 2.4 Implement project creation logic with validation
  - [ ] 2.5 Add UUID generation and default naming
  - [ ] 2.6 Implement redirect to project page after save
  - [ ] 2.7 Create route for dashboard page
  - [ ] 2.8 Verify all dashboard tests pass

- [ ] 3. Project Page Core Functionality
  - [ ] 3.1 Write tests for ProjectPage Livewire component
  - [ ] 3.2 Create ProjectPage Livewire component
  - [ ] 3.3 Create project page Blade view structure
  - [ ] 3.4 Implement editable project name with inline editing
  - [ ] 3.5 Implement editable description textarea with auto-save
  - [ ] 3.6 Create route for project page with UUID parameter
  - [ ] 3.7 Add authorization checks for project access
  - [ ] 3.8 Verify all project page tests pass

- [ ] 4. Sidebar Navigation
  - [ ] 4.1 Write tests for Sidebar Livewire component
  - [ ] 4.2 Create Sidebar Livewire component
  - [ ] 4.3 Create sidebar Blade view with project listing
  - [ ] 4.4 Implement chronological project ordering
  - [ ] 4.5 Add "New Project" button with navigation
  - [ ] 4.6 Implement active project highlighting
  - [ ] 4.7 Add real-time updates using Livewire events
  - [ ] 4.8 Integrate sidebar into main layout
  - [ ] 4.9 Verify all sidebar tests pass

- [ ] 5. Name Results Table and Cards
  - [ ] 5.1 Write tests for NameResultCard component
  - [ ] 5.2 Create NameResultCard Livewire component
  - [ ] 5.3 Implement Flux table structure for results display
  - [ ] 5.4 Create expandable card UI with name, domains, and logo sections
  - [ ] 5.5 Implement hide/show functionality for individual cards
  - [ ] 5.6 Add filter toggle for active/hidden results
  - [ ] 5.7 Implement name selection functionality
  - [ ] 5.8 Add visual feedback for selected name
  - [ ] 5.9 Verify all name result tests pass

- [ ] 6. State Management and Polish
  - [ ] 6.1 Write integration tests for complete workflows
  - [ ] 6.2 Implement name selection persistence in database
  - [ ] 6.3 Add name deselection with UI revert functionality
  - [ ] 6.4 Ensure selected name appears in sidebar
  - [ ] 6.5 Add loading states and transitions
  - [ ] 6.6 Implement toast notifications for user feedback
  - [ ] 6.7 Add responsive design adjustments
  - [ ] 6.8 Verify all integration tests pass
  - [ ] 6.9 Run full test suite and ensure 100% pass rate