# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-11-18-project-sharing/spec.md

> Created: 2025-11-18
> Version: 1.0.0

## Test Coverage

### Unit Tests

**Share Model (`tests/Unit/Models/ShareTest.php`)**
- it creates a share with required attributes
- it generates token automatically if not provided
- it handles password-protected shares with proper hashing
- it belongs to a user
- it has polymorphic relationship to shareable models (Session)
- it has many share accesses for analytics
- it provides share URL generation
- it checks if share is expired
- it checks if share is accessible (active, not expired)
- it records share access and updates view count
- it validates password for protected shares
- it scopes to active shares
- it scopes to accessible shares (active and not expired)
- it casts settings as array
- it has proper fillable attributes

**ShareAccess Model (`tests/Unit/Models/ShareAccessTest.php`)**
- it creates a share access record with required attributes
- it belongs to a share
- it automatically sets accessed_at timestamp
- it handles IPv6 addresses
- it handles long user agent strings
- it handles null optional fields gracefully
- it scopes by date range
- it provides analytics methods (most viewed, unique IPs, etc.)
- it has proper fillable attributes
- it cascades delete when share is deleted

**Export Model (`tests/Unit/Models/ExportTest.php`)**
- it creates an export with required attributes
- it generates UUID automatically if not provided
- it belongs to a user
- it has polymorphic relationship to exportable models (Share, Session)
- it provides download URL generation
- it checks if export is expired
- it increments download count
- it formats file size for display
- it handles null file size gracefully
- it checks file existence
- it deletes associated file when export is deleted
- it scopes to non-expired exports
- it scopes by export type
- it validates export type enum values
- it provides content type based on export type
- it generates appropriate filename based on export type
- it has proper fillable attributes

**ShareService (`tests/Unit/Services/ShareServiceTest.php`)**
- it generates unique share tokens
- it creates share with privacy settings
- it validates share creation data
- it checks share accessibility rules
- it verifies passwords correctly
- it records share access with analytics
- it calculates share statistics
- it handles share expiration logic
- it generates social media share URLs for all platforms
- it validates Open Graph meta tag generation

**ExportService (`tests/Unit/Services/ExportServiceTest.php`)**
- it generates PDF exports with proper formatting
- it generates CSV exports with correct headers
- it generates JSON exports with proper structure
- it stores export files in correct location
- it cleans up old export files
- it handles large data sets efficiently
- it validates export options
- it generates unique filenames to avoid conflicts

### Integration Tests

**Share Creation Flow (`tests/Feature/Shares/ShareCreationTest.php`)**
- authenticated user can create a public share
- authenticated user can create password-protected share
- authenticated user can set share expiration
- unauthenticated user cannot create shares
- share generation increments session share_count
- share token is unique across database
- share creation validates session ownership
- share stores names data correctly as JSON
- share creation handles invalid session_id gracefully

**Public Share Viewing (`tests/Feature/Shares/PublicShareViewingTest.php`)**
- public share displays content correctly
- password-protected share requires password
- expired share shows 404 error
- inactive share shows 404 error
- share access is recorded with analytics (IP, user agent)
- share view count increments on each view
- password verification works correctly
- invalid password shows error message
- share page includes proper Open Graph meta tags

**Social Media Sharing (`tests/Feature/Shares/SocialMediaSharingTest.php`)**
- share modal displays all social media buttons
- Twitter share URL is generated correctly
- LinkedIn share URL is generated correctly
- Facebook share URL is generated correctly
- Reddit share URL is generated correctly
- WhatsApp share URL is generated correctly
- share URLs are properly encoded
- pre-filled text includes share link
- Open Graph meta tags render on share page

**Export Generation (`tests/Feature/Exports/ExportGenerationTest.php`)**
- authenticated user can generate PDF export
- authenticated user can generate CSV export
- authenticated user can generate JSON export
- PDF export contains all project data
- CSV export has proper headers and formatting
- JSON export is valid and properly structured
- export file is stored in correct location
- export download URL is generated correctly
- unauthenticated user cannot generate exports
- export generation validates session ownership

**Export Download (`tests/Feature/Exports/ExportDownloadTest.php`)**
- anyone with UUID can download export file
- download serves correct MIME type
- download sets proper headers for file download
- expired export shows 404 error
- invalid UUID shows 404 error
- download count increments on each download
- file is served with correct filename

**Share Management Dashboard (`tests/Feature/Shares/ShareManagementTest.php`)**
- user can view list of their shares
- share list shows view counts and analytics
- user can delete their own shares
- user cannot delete other users' shares
- share list filters active/inactive shares
- share list sorts by date and view count
- deleted share returns 404 on public access

**Analytics Tracking (`tests/Feature/Shares/AnalyticsTest.php`)**
- share access records IP address
- share access records user agent
- share access records referer
- share access records timestamp
- analytics aggregates unique views correctly
- analytics tracks geographic data if available
- analytics respects privacy settings

### Livewire Component Tests

**ShareModal Component (`tests/Feature/Livewire/ShareModalTest.php`)**
- modal opens on button click
- user can configure privacy settings
- user can set password protection
- user can set expiration time
- share link is generated and displayed
- user can copy link to clipboard
- social media buttons are rendered
- export options are available
- modal validates required fields
- modal shows success message on share creation

**PublicSharePage Component (`tests/Feature/Livewire/PublicSharePageTest.php`)**
- component displays shared names correctly
- component shows password form for protected shares
- component validates password input
- component tracks view on mount
- component handles expired shares
- component displays share metadata (title, description)
- component is mobile responsive

### API Endpoint Tests

**Share API (`tests/Feature/Api/ShareApiTest.php`)**
- POST /api/shares creates new share
- POST /api/shares requires authentication
- POST /api/shares validates request data
- GET /api/shares returns user's shares
- GET /api/shares paginates results
- DELETE /api/shares/{id} deletes share
- DELETE /api/shares/{id} requires ownership
- endpoints respect rate limiting

**Export API (`tests/Feature/Api/ExportApiTest.php`)**
- POST /api/exports generates export
- POST /api/exports requires authentication
- POST /api/exports validates export type
- GET /api/exports returns user's exports
- GET /download/{uuid} serves file
- GET /download/{uuid} handles missing files
- endpoints respect rate limiting

### Mocking Requirements

**External Services:**
- No external services to mock (all internal generation)

**File System:**
- Mock file storage for export tests to avoid actual file creation
- Use `Storage::fake('exports')` for testing file operations

**Time-Based Tests:**
- Use Carbon::setTestNow() for testing expiration logic
- Mock current time for consistent test results

### Browser/Feature Tests

**Complete User Flows:**
- User generates names, creates share, views public page
- User creates password-protected share, verifies password works
- User generates PDF export, downloads file successfully
- User shares on Twitter, LinkedIn, Facebook with proper formatting
- User manages shares from dashboard, deletes old shares
