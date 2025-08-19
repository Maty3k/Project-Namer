# Technical Stack

> Last Updated: 2025-08-19
> Version: 1.0.0

## Application Framework

- **Framework:** Laravel 12+
- **Version:** v12
- **PHP Version:** 8.4.11

## Database

- **Database System:** SQLite
- **Environment Strategy:** SQLite for all environments (local, testing, production)
- **Rationale:** Simplified deployment, zero-configuration, self-contained database file

## JavaScript

- **Framework:** Livewire 3 + Volt
- **Version:** v3 + v1

## CSS Framework

- **Framework:** TailwindCSS
- **Version:** v4

## UI Components

- **Primary Library:** FluxUI Pro
- **Version:** v2
- **Admin Panel:** FilamentPHP
- **Admin Version:** v4

## Typography & Icons

- **Fonts:** System fonts / Google Fonts
- **Icon Libraries:** 
  - Heroicons (default)
  - Lucide

## Quality Assurance

- **Testing Framework:** PestPHP
- **Testing Version:** v3
- **Static Analysis:** PHPStan (Larastan)
- **Static Analysis Version:** v3
- **Code Formatting:** Laravel Pint
- **Pint Version:** v1
- **Code Refactoring:** Rector
- **Rector Version:** v2

## Development Environment

- **Local Development:** Laravel Sail + Docker
- **Package Manager:** Composer (PHP), NPM (JavaScript)
- **Asset Bundling:** Vite

## Hosting & Deployment

- **Application Hosting:** Laravel Forge/Vapor
- **Database Hosting:** SQLite file-based (no separate hosting required)
- **Asset Hosting:** Laravel Storage (local/S3)
- **Deployment Solution:** Docker + Laravel Sail, GitHub Actions CI/CD

## Repository

- **Code Repository:** https://github.com/user/project-namer
- **Version Control:** Git
- **CI/CD:** GitHub Actions

## Key Architectural Decisions

- **AI-First Development:** Optimized for AI code generation and assistance
- **TALL Stack:** TailwindCSS, Alpine.js, Laravel, Livewire architecture
- **Server-Driven UI:** Livewire/Volt for interactive components
- **Test-Driven Development:** Comprehensive testing with PestPHP
- **Strict Code Quality:** PHPStan Level 9, automated formatting with Pint