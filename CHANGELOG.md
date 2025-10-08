# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **AI Progress Indicator** - Real-time visual progress tracking for AI name generation
  - Live progress bar with smooth CSS transitions (500ms)
  - Differentiated visual modes: normal (blue) and deep thinking (purple gradient)
  - Estimated time remaining display (4s normal, 10s deep thinking)
  - Polling-based progress updates every 500ms
  - Database-backed progress tracking with 5 milestone updates (0%, 25%, 50%, 75%, 100%)
  - Green success state animation on completion with fade-out effect
  - Pulsing light-bulb icon animation for deep thinking mode
  - Comprehensive ARIA accessibility attributes (role, aria-valuenow, aria-label)
  - Full mobile responsive design with dark mode support
  - Graceful error handling and edge case management
  - Event-driven architecture with Livewire events (ai-generation-started, ai-generation-progress, ai-generation-complete)

- **DNS Pre-screening Domain Filtering** - Intelligent domain availability checking using DNS lookups before API calls
  - Pre-screen domains using DNS records (A, AAAA, CNAME, MX) to identify taken domains
  - Skip expensive API calls for domains with existing DNS records
  - Background job processing for non-blocking DNS checks
  - Real-time status updates as DNS checks complete
  - Dual caching strategy: 7-day TTL for DNS checks, 24-hour TTL for API checks
  - Performance optimization: reduced domain checking time by up to 70%
  - Added `DNSLookupService` for DNS resolution with 3-second timeout
  - Enhanced `DomainCheckService` with DNS pre-screening integration
  - New `CheckDomainDNSJob` for background DNS checking
  - Updated domain result cards to show DNS check status
  - Database schema updates to track DNS check method and records

### Changed
- Domain checking workflow now prioritizes DNS pre-screening over API calls
- Cache expiration now varies based on check method (DNS vs API)
- UI domain status indicators updated to reflect DNS checking states

### Performance
- DNS pre-screening reduces API calls by ~60-70% for common domain checks
- Background job processing prevents UI blocking during DNS lookups
- Intelligent caching minimizes redundant DNS queries

## [0.2.0] - 2025-09-03

### Added
- **Enhanced UI/UX with FluxUI Pro**
  - ChatGPT-style sidebar with session management and search
  - Virtual scrolling and performance optimization for large session lists
  - Advanced session actions (rename, duplicate, star, delete)
  - Focus mode with keyboard shortcuts and responsive design
  - Accessibility compliance (WCAG standards)
  - Loading states and optimistic UI updates

- **Mobile-Responsive Design**
  - Responsive sidebar with collapsible design for mobile devices
  - Touch-friendly interface elements and button sizing
  - Mobile-optimized session management and navigation
  - Floating focus mode toggle for small screens

- **Logo Generation Feature**
  - AI-powered logo creation using DALL-E API
  - Multiple style options: Minimalist, Modern, Playful, Corporate
  - Generate multiple logo variations for inspiration
  - Clear disclaimer that logos are for inspiration purposes
  - SVG and PNG export capabilities
  - Color palette customization with 10 predefined schemes
  - Batch download functionality for all generated logos

- **Sharing & Saving**
  - Generate shareable public URLs for name lists
  - Private sharing with password protection
  - Export to PDF, CSV, and JSON formats
  - Social media sharing integration (Twitter, LinkedIn, Facebook)
  - Share access monitoring and analytics
  - Rate limiting and CSRF protection

- **Performance Optimizations**
  - Caching strategies for API responses
  - Optimized database queries and indexing
  - Compressed and optimized static assets
  - Lazy loading for results

## [0.1.0] - 2025-08-19

### Added
- **MVP Core Features**
  - Clean textarea interface with 2000 character limit
  - Input validation and character counter
  - AI Name Generation with multiple modes (Creative, Professional, Brandable, Tech-focused)
  - GPT-5 API integration as default model
  - 10 names generated per request
  - Clean table layout with name and domain status columns
  - Visual indicators for domain availability (available/taken/unknown)
  - Responsive table design for various screen sizes
  - Basic domain checking for .com, .io, .co, .net extensions
  - Real-time availability checking with caching
  - Error handling for API failures and rate limits
  - Search history with browser persistence
  - Display of previous 10 searches with timestamps
  - Deep Thinking Mode for enhanced AI name generation
  - Loading states during domain checking

### Technical
- Laravel 12+ framework with TALL stack
- SQLite database for all environments
- TailwindCSS v4 for styling
- FluxUI Pro v2 for UI components
- FilamentPHP v4 for admin panel
- PestPHP v3 for testing
- PHPStan Level 9 for static analysis
- Laravel Pint for code formatting
- Comprehensive test suite with 2300+ tests

[Unreleased]: https://github.com/user/project-namer/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/user/project-namer/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/user/project-namer/releases/tag/v0.1.0
