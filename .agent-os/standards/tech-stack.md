# Tech Stack

> Version: 1.0.0
> Last Updated: 2025-08-31

## Context

This file is part of the Agent OS standards system. These global tech stack defaults are referenced by all product codebases when initializing new projects. Individual projects may override these choices in their `.agent-os/product/tech-stack.md` file.

## Core Technologies

### Application Framework
- **Framework:** Laravel
- **Version:** 12.0+
- **Language:** PHP 8.4+

### Database
- **Primary:** sqlite

## Frontend Stack

### Frontend Framework
- **Framework:** Livewire
- **Version:** Latest stable

### Admin Panel
- **Framework:** FilamentPHP
- **Version:** 4 (Even while in beta)

### CSS Framework
- **Framework:** TailwindCSS
- **Version:** 4.0+
- **PostCSS:** Yes

### UI Components
- **Library:** FluxUI Pro
- **Version:** Latest
- **Installation:** Via composer

## Assets & Media

### Fonts
- **Provider:** Google Fonts
- **Loading Strategy:** Self-hosted for performance

### Icons
- **Library:** Heroicons and Lucide
- **Implementation:** FluxUI Icon Component

## Infrastructure

### Application Hosting
- **Platform:** Laravel Forge

## Deployment

### CI/CD Pipeline
- **Platform:** GitHub Actions
- **Trigger:** Push to main/staging branches
- **Tests:** Run before deployment

### Environments
- **Production:** main branch
- **Staging:** staging branch
- **Review Apps:** PR-based (optional)

## Code Quality
- **Pint** via `composer lint`
- **PHPStan** via `composer stan`
- **PestPHP** via `composer test`
- **Full Suite** via `composer ready`

---

*Customize this file with your organization's preferred tech stack. These defaults are used when initializing new projects with Agent OS.*
