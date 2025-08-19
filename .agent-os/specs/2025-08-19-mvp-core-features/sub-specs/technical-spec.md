
# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-19-mvp-core-features/spec.md

> Created: 2025-08-19
> Version: 1.0.0

## Technical Requirements

### Frontend Interface Requirements

- **Idea Input Component**
  - Livewire/Volt component with 2000 character textarea using FluxUI Pro
  - Real-time character counter with visual feedback at 1800+ characters
  - Input validation preventing submission of empty or excessive content
  - Responsive design supporting mobile (320px+) to desktop (1920px+)

- **Generation Mode Selection**
  - Radio button group or select component for 4 modes: Creative, Professional, Brandable, Tech-focused
  - Mode descriptions with clear differentiation of output styles
  - Default selection with persistent user preference in browser storage

- **Deep Thinking Toggle**
  - Checkbox component with clear label and explanation
  - Visual indicator when enabled showing extended processing expectations
  - Integration with loading states to show appropriate wait times

- **Results Display System**
  - FluxUI Pro table component with sortable columns
  - Real-time domain status updates using Livewire wire:poll or similar
  - Visual indicators with hover tooltips: green (available), red (taken), yellow (checking), gray (error)
  - Tooltip text: "Available", "Taken", "Checking availability...", "Error checking domain"
  - Responsive table design with horizontal scrolling on mobile
  - Loading skeleton states during name generation and domain checking

- **Search History Interface**
  - Collapsible section showing last 30-50 generated names with timestamps
  - Click-to-reload functionality for previous search results
  - Clear history button with confirmation dialog
  - Compact display showing search date, input preview, and result count

### Backend API Integration Requirements

- **OpenAI GPT-5 Integration**
  - HTTP client service class for API communication
  - Request timeout: 30 seconds standard, 60 seconds for Deep Thinking Mode
  - Rate limiting: 10 requests per minute per IP address
  - Error handling for API failures, invalid responses, rate limits
  - Prompt engineering for each of the 4 generation modes
  - Response parsing to extract exactly 10 names per request

- **Domain Availability Checking**
  - Integration with domain availability API (Namecheap, GoDaddy, or WHOIS service)
  - Concurrent checking of .com, .io, .co, .net for each generated name
  - Request timeout: 5 seconds per domain check
  - Caching mechanism: 24-hour cache for domain availability results
  - Fallback handling when domain APIs are unavailable

- **Data Persistence**
  - SQLite database table for caching domain availability results
  - Local storage management for search history (JSON format)
  - Session storage for current generation state and preferences

### Performance Requirements

- **Response Time Targets**
  - Page load: < 2 seconds
  - Name generation: < 15 seconds standard, < 45 seconds Deep Thinking Mode
  - Domain checking: < 10 seconds for all 4 TLDs per name
  - Search history reload: < 1 second

- **Caching Strategy**
  - Domain availability results cached for 24 hours
  - AI generation results cached for 1 hour per unique input hash
  - Static assets cached with appropriate browser cache headers

## Approach

### Selected Technical Approach: Server-Driven UI with Livewire

**Rationale:** Leveraging Laravel's TALL stack (TailwindCSS, Alpine.js, Laravel, Livewire) provides optimal development speed while maintaining rich interactivity. Livewire/Volt components eliminate the complexity of separate frontend framework while delivering real-time updates essential for domain checking and AI generation feedback.

**Component Architecture:**
- Single Livewire Volt component managing entire naming workflow
- Separate service classes for OpenAI integration and domain checking
- Repository pattern for domain cache management
- Job queue system for background domain checking to prevent UI blocking

**State Management:**
- Server-side state in Livewire component properties
- Browser local storage for search history and user preferences
- SQLite caching for API response data

### Alternative Approaches Considered

**Option A: Vue.js SPA with Laravel API Backend**
- Pros: More interactive UI, better mobile experience
- Cons: Increased complexity, longer development time, doesn't leverage Laravel starter kit strengths

**Option B: Inertia.js with Vue Components**
- Pros: Server-side routing with SPA feel, good developer experience
- Cons: Added complexity for MVP, not aligned with starter kit's Livewire focus

## External Dependencies

### Required API Services

- **OpenAI API (GPT-5 or GPT-4 Turbo)**
  - Purpose: AI-powered business name generation
  - Justification: Industry-leading language model with superior creative naming capabilities
  - Cost consideration: ~$0.01-0.05 per generation request
  - Rate limits: 5,000 requests per day on standard tier

- **Domain Availability API**
  - Options: Namecheap API, GoDaddy API, or WHOIS lookup service
  - Purpose: Real-time domain availability checking for generated names
  - Justification: Essential core functionality, eliminates manual domain verification
  - Cost consideration: ~$0.001-0.01 per domain lookup

### PHP/Laravel Dependencies

All following dependencies are already included in the Laravel starter kit and require no additional installation:

- **Livewire 3** - Server-driven UI components
- **FluxUI Pro** - Professional UI component library  
- **TailwindCSS 4** - Utility-first CSS framework
- **PestPHP** - Testing framework
- **PHPStan/Larastan** - Static analysis
- **Laravel Pint** - Code formatting

### JavaScript Dependencies

- **Alpine.js** - Lightweight JavaScript framework (included with Livewire)
- **No additional frontend dependencies required** - Leveraging server-driven approach

## Security Considerations

### API Key Management
- Environment-based configuration for all API keys
- No API keys exposed to frontend/client-side code
- Rate limiting on all external API calls to prevent abuse

### Input Validation
- Server-side validation of all user inputs
- XSS prevention through Laravel's built-in protection
- CSRF protection on all form submissions
- Input length limits enforced at multiple levels

### Data Privacy
- No personal data collection or storage
- Search history stored only in user's browser local storage
- Domain cache includes no user-identifying information
- Optional clear history functionality for privacy compliance

## Monitoring and Logging

### Error Tracking
- Laravel logging for all API failures and system errors
- Structured logging for debugging AI generation and domain checking issues
- User-friendly error messages without exposing system details

### Performance Monitoring
- Response time tracking for AI generation requests
- Domain checking API performance monitoring
- Database query performance analysis during development

### Usage Analytics
- Track generation mode preferences (anonymous)
- Monitor API usage patterns for cost optimization
- Performance metrics for optimization opportunities
