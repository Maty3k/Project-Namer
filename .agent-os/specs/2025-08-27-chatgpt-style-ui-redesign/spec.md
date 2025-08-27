# Spec Requirements Document

> Spec: Dashboard Textarea & ChatGPT-Style Sidebar
> Created: 2025-08-27
> Status: Planning

## Overview

Fix the dashboard to actually show a textarea where users can immediately type in their business idea, and implement ChatGPT-style sidebar navigation for managing multiple ideas. The dashboard must be ready for input the moment it loads, with the textarea auto-focused and prominently displayed.

## User Stories

### Dashboard Input Flow

As a user, I want to land on a dashboard with a prominent textarea ready for my input, so that I can immediately start typing my business idea.

The dashboard page shows a large, centered textarea with placeholder text like "Describe your business idea..." that is automatically focused when the page loads. No clicking required - I can just start typing. When I submit my idea (via Enter key or button), a new idea record is created with an auto-generated slug, and I'm taken to that idea's dedicated page where I can generate names, create logos, and iterate.

### Sidebar Navigation Flow  

As a user with multiple ideas, I want a ChatGPT-style sidebar on the left that shows all my past ideas, so I can easily switch between them.

The sidebar lists all my ideas chronologically (newest first) with a title preview. Clicking any idea in the sidebar takes me to its dedicated page with all previous work intact. At the top of the sidebar is a "New Idea" button that returns me to the dashboard with a fresh, empty, auto-focused textarea.

### Persistent Idea Pages

As a user, I want each idea to have its own permanent page that maintains all my work, so I can pick up where I left off.

Each idea page (accessible via /idea/{slug}) preserves all generated names, logos, and other work. The page shows the idea description at the top with options to generate names, generate logos, etc. All previous generations are displayed and remain available when I return to the page later.

## Spec Scope

1. **Working Dashboard Textarea** - A prominent, centered textarea on the dashboard that auto-focuses on page load and accepts idea input
2. **Idea Submission & Redirect** - Form submission creates an idea with auto-generated slug and redirects to the idea's page
3. **ChatGPT-Style Sidebar** - Left sidebar listing all ideas with "New Idea" button at top, clicking ideas navigates to their pages
4. **Dedicated Idea Pages** - Individual pages at /idea/{slug} that show the idea and all generation options/history
5. **Persistent State** - Each idea page maintains all previous work (generated names, logos, etc.)

## Out of Scope

- Mobile-specific optimizations (FluxUI handles responsive design)
- Accessibility features (handled by FluxUI and Laravel starter)
- Complex performance optimizations (framework handles this)
- Real-time features or WebSockets
- Multi-user support

## Expected Deliverable

1. Dashboard at `/` with a working textarea that auto-focuses on load and allows users to type in their business idea
2. Submission of the textarea creates an idea record and redirects to `/idea/{slug}`
3. Left sidebar showing all created ideas with a "New Idea" button that returns to the dashboard

## Spec Documentation

- Tasks: @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/tasks.md
- Technical Specification: @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/sub-specs/technical-spec.md
- Database Schema: @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/sub-specs/database-schema.md
- API Specification: @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/sub-specs/api-spec.md
- Tests Specification: @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/sub-specs/tests.md