# Spec Requirements Document

> Spec: Project Workflow UI
> Created: 2025-09-01
> Status: Planning

## Overview

Implement a streamlined project workflow that allows users to create, manage, and iterate on business naming projects. The interface will provide a dashboard for project creation, dedicated project pages for name generation and refinement, and a sidebar for easy project navigation.

## User Stories

### Project Creation Workflow

As an entrepreneur, I want to quickly describe my business idea and start generating names, so that I can move from concept to branded identity efficiently.

The user begins at the dashboard where they see a prominent textarea for describing their project idea. After typing their description, they click "Save & Generate Names" which creates a project record with a default editable name, saves it to the database, and redirects them to the project page where they can immediately start generating and evaluating names.

### Name Generation and Selection

As a user, I want to generate multiple name ideas and evaluate them with domain availability and logo options, so that I can make an informed decision about my brand identity.

On the project page, users see their project description in an editable textarea at the top. Below that, generated names appear in a Flux table format where each row is a full-width card containing the name as a header, available domains for that name, and a button to generate logos. Users can hide rejected names, filter between active and hidden names, and select their preferred name which updates the project and UI accordingly.

### Project Management

As a user with multiple projects, I want to easily switch between different naming projects and create new ones, so that I can manage multiple ventures or client projects efficiently.

The sidebar displays a "New Project" button at the top and lists all existing projects chronologically with the most recent first. Clicking any project navigates to its dedicated page. The selected project name appears in the sidebar and header for context.

## Spec Scope

1. **Dashboard Page** - Create a clean interface with a textarea for project descriptions and a button to save and generate names
2. **Project Page** - Build a comprehensive project workspace with editable description, name generation results table, and name selection functionality
3. **Sidebar Navigation** - Implement a persistent sidebar with new project creation and chronological project listing
4. **Name Result Cards** - Design expandable result rows that progressively display names, domains, and logos as content is generated
5. **State Management** - Handle project selection, name selection, and result filtering states across the application

## Out of Scope

- User authentication and multi-tenancy (assumed to be handled by existing Laravel auth)
- AI integration for name generation (will be handled separately)
- Domain checking API integration (will be handled separately)
- Logo generation API integration (will be handled separately)
- Performance optimizations beyond framework defaults
- Security measures beyond framework defaults
- Accessibility features beyond framework defaults

## Expected Deliverable

1. A functional dashboard at `/dashboard` where users can input project descriptions and initiate the naming workflow
2. Dynamic project pages at `/project/{uuid}` with full CRUD capabilities for project details and name management
3. A working sidebar that persists across pages showing all projects and allowing quick navigation
4. Fully interactive name result cards with show/hide functionality and visual state changes based on selection
5. Complete state persistence where project names, selections, and filtering preferences are maintained across sessions

## Spec Documentation

- Tasks: @.agent-os/specs/2025-09-01-project-workflow-ui/tasks.md
- Technical Specification: @.agent-os/specs/2025-09-01-project-workflow-ui/sub-specs/technical-spec.md
- Database Schema: @.agent-os/specs/2025-09-01-project-workflow-ui/sub-specs/database-schema.md
- Tests Specification: @.agent-os/specs/2025-09-01-project-workflow-ui/sub-specs/tests.md