# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-11-18-project-sharing/spec.md

> Created: 2025-11-18
> Version: 1.0.0

## Technical Requirements

### Share Link Generation
- Generate cryptographically secure random tokens using `Str::random(32)`
- Store share data in database with relationships to user and session
- Support configurable expiration times (1 hour, 1 day, 1 week, never)
- Implement password hashing using bcrypt for protected shares
- Create unique URLs in format: `{app_url}/share/{token}`

### Public Share Viewing
- Create public route accessible without authentication
- Display shared content with responsive Livewire components
- Implement password verification modal for protected shares
- Track view analytics (IP address, user agent, timestamp)
- Handle expired/deleted shares with 404 responses
- Implement proper caching for better performance

### Social Media Integration
- **Twitter/X**: Use Twitter Web Intent API `https://twitter.com/intent/tweet`
- **LinkedIn**: Use LinkedIn Share URL `https://www.linkedin.com/sharing/share-offsite/`
- **Facebook**: Use Facebook Share Dialog `https://www.facebook.com/sharer/sharer.php`
- **Reddit**: Use Reddit Submit URL `https://reddit.com/submit`
- **WhatsApp**: Use WhatsApp Share API `https://wa.me/`
- Pre-fill share text with engaging copy and share URL
- Implement Open Graph meta tags for rich previews

### Export System
- **PDF**: Use Laravel DomPDF or Snappy for PDF generation
- **CSV**: Use Laravel CSV package or native fputcsv()
- **JSON**: Use native json_encode() with proper formatting
- Store generated exports temporarily with automatic cleanup
- Implement download routes with proper headers and MIME types
- Track export generation for analytics

### Privacy & Security
- Implement CSRF protection on all forms
- Use Laravel's rate limiting for share generation
- Sanitize all user input before storage
- Implement proper access control checks
- Use database transactions for data integrity
- Implement soft deletes for shares

### Performance Optimization
- Cache share data to reduce database queries
- Lazy load social media buttons to improve page speed
- Implement database indexing on token and user_id columns
- Use queue jobs for large export generation
- Implement CDN for static assets

## Approach Options

### Option A: Livewire-Based Share Modal (Selected)
**Pros:**
- Consistent with existing architecture
- Real-time reactivity without page reloads
- Easy state management
- Built-in CSRF protection

**Cons:**
- Requires JavaScript enabled
- Slightly more server requests

### Option B: Traditional Blade Forms with AJAX
**Pros:**
- Simpler implementation
- Less overhead
- Works without Livewire

**Cons:**
- More manual JavaScript
- Less reactive
- Not consistent with current stack

**Rationale:** Option A is selected because the application already uses Livewire extensively, and it provides better UX with real-time updates and state management.

## External Dependencies

### PDF Generation
- **Package**: `barryvdh/laravel-dompdf`
- **Version**: `^3.0`
- **Justification**: Well-maintained, Laravel-friendly PDF generation library with good documentation and wide adoption.

### CSV Handling
- **Package**: `league/csv` (optional)
- **Version**: `^9.0`
- **Justification**: While PHP has native CSV functions, this package provides better CSV handling, validation, and formatting capabilities. Can use native functions if simplicity preferred.

### Social Share Meta Tags
- **Package**: `artesaos/seotools` (optional)
- **Version**: `^1.3`
- **Justification**: Simplifies Open Graph and Twitter Card meta tag generation. Can implement manually if package overhead not desired.

## Implementation Notes

### Share Token Generation
```php
use Illuminate\Support\Str;

$token = Str::random(32);
// Ensure uniqueness in database
while (Share::where('token', $token)->exists()) {
    $token = Str::random(32);
}
```

### Social Media Share URLs
```php
// Twitter/X
$twitterUrl = "https://twitter.com/intent/tweet?text=" . urlencode($text) . "&url=" . urlencode($shareUrl);

// LinkedIn
$linkedInUrl = "https://www.linkedin.com/sharing/share-offsite/?url=" . urlencode($shareUrl);

// Facebook
$facebookUrl = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($shareUrl);

// Reddit
$redditUrl = "https://reddit.com/submit?url=" . urlencode($shareUrl) . "&title=" . urlencode($title);

// WhatsApp
$whatsappUrl = "https://wa.me/?text=" . urlencode($text . " " . $shareUrl);
```

### Open Graph Meta Tags
```html
<meta property="og:title" content="{{ $shareTitle }}" />
<meta property="og:description" content="{{ $shareDescription }}" />
<meta property="og:url" content="{{ $shareUrl }}" />
<meta property="og:type" content="website" />
<meta property="og:image" content="{{ $previewImage }}" />
<meta name="twitter:card" content="summary_large_image" />
```
