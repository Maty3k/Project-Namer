# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/spec.md

> Created: 2025-08-27
> Status: Ready for Implementation

## Tasks

- [ ] 1. Dashboard Textarea Implementation (PRIORITY)
  - [ ] 1.1 Write tests for dashboard textarea functionality
  - [ ] 1.2 Create/update dashboard route to show textarea page
  - [ ] 1.3 Create dashboard.blade.php with prominent centered textarea
  - [ ] 1.4 Add auto-focus to textarea on page load
  - [ ] 1.5 Add placeholder text "Describe your business idea..."
  - [ ] 1.6 Implement character counter (2000 limit)
  - [ ] 1.7 Create IdeaCreator Livewire component for form handling
  - [ ] 1.8 Verify dashboard loads with working, focused textarea

- [ ] 2. Database Setup for Ideas
  - [ ] 2.1 Write tests for Idea model
  - [ ] 2.2 Create migration for ideas table (id, slug, title, description, session_id, timestamps)
  - [ ] 2.3 Create Idea model with slug generation
  - [ ] 2.4 Create factory for testing
  - [ ] 2.5 Implement title extraction from description
  - [ ] 2.6 Verify all model tests pass

- [ ] 3. Idea Submission & Redirect
  - [ ] 3.1 Write tests for idea creation flow
  - [ ] 3.2 Implement form submission in IdeaCreator component
  - [ ] 3.3 Generate unique slug on submission
  - [ ] 3.4 Save idea to database
  - [ ] 3.5 Redirect to /idea/{slug} after creation
  - [ ] 3.6 Verify submission creates idea and redirects correctly

- [ ] 4. ChatGPT-Style Sidebar
  - [ ] 4.1 Write tests for sidebar display
  - [ ] 4.2 Update app layout to include left sidebar
  - [ ] 4.3 Create IdeaSidebar Livewire component
  - [ ] 4.4 Display all ideas in reverse chronological order
  - [ ] 4.5 Add "New Idea" button at top linking to dashboard
  - [ ] 4.6 Make idea items clickable to navigate
  - [ ] 4.7 Style with FluxUI components
  - [ ] 4.8 Verify sidebar shows all ideas and navigation works

- [ ] 5. Individual Idea Pages
  - [ ] 5.1 Write tests for idea detail pages
  - [ ] 5.2 Create IdeaController with show method
  - [ ] 5.3 Create idea/show.blade.php view
  - [ ] 5.4 Display idea description at top of page
  - [ ] 5.5 Integrate existing NameGenerator component
  - [ ] 5.6 Integrate existing LogoGenerator component
  - [ ] 5.7 Ensure all previous generations are displayed
  - [ ] 5.8 Verify idea pages load and maintain state

- [ ] 6. Polish & Testing
  - [ ] 6.1 Test complete user flow: dashboard → create idea → redirect → sidebar navigation
  - [ ] 6.2 Add loading states for form submission
  - [ ] 6.3 Add validation error display
  - [ ] 6.4 Test with multiple ideas (10+)
  - [ ] 6.5 Ensure FluxUI styling is consistent
  - [ ] 6.6 Run full test suite
  - [ ] 6.7 Manual testing of all features