# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-11-18-project-sharing/spec.md

> Created: 2025-11-18
> Status: Ready for Implementation

## Tasks

- [ ] 1. Database Schema & Models Setup
  - [ ] 1.1 Write tests for Share model
  - [ ] 1.2 Create shares table migration
  - [ ] 1.3 Create Share model with relationships and methods
  - [ ] 1.4 Write tests for ShareAccess model
  - [ ] 1.5 Create share_accesses table migration
  - [ ] 1.6 Create ShareAccess model
  - [ ] 1.7 Write tests for Export model
  - [ ] 1.8 Create exports table migration
  - [ ] 1.9 Create Export model with polymorphic relationships
  - [ ] 1.10 Create migration to add sharing fields to sessions table
  - [ ] 1.11 Run migrations and verify database structure
  - [ ] 1.12 Verify all model tests pass

- [ ] 2. Share Service & Business Logic
  - [ ] 2.1 Write tests for ShareService
  - [ ] 2.2 Create ShareService class
  - [ ] 2.3 Implement token generation logic
  - [ ] 2.4 Implement share creation with privacy settings
  - [ ] 2.5 Implement password hashing and verification
  - [ ] 2.6 Implement share access tracking
  - [ ] 2.7 Implement expiration checking logic
  - [ ] 2.8 Implement share analytics methods
  - [ ] 2.9 Verify all ShareService tests pass

- [ ] 3. Export Service & File Generation
  - [ ] 3.1 Write tests for ExportService
  - [ ] 3.2 Install barryvdh/laravel-dompdf package
  - [ ] 3.3 Create ExportService class
  - [ ] 3.4 Implement PDF export generation
  - [ ] 3.5 Implement CSV export generation
  - [ ] 3.6 Implement JSON export generation
  - [ ] 3.7 Implement file storage and cleanup
  - [ ] 3.8 Create export file templates (PDF layout)
  - [ ] 3.9 Verify all ExportService tests pass

- [ ] 4. API Routes & Controllers
  - [ ] 4.1 Write tests for Share API endpoints
  - [ ] 4.2 Create ShareController with store, index, destroy methods
  - [ ] 4.3 Create StoreShareRequest form request validation
  - [ ] 4.4 Define share API routes with authentication middleware
  - [ ] 4.5 Write tests for Export API endpoints
  - [ ] 4.6 Create ExportController with store, index methods
  - [ ] 4.7 Create StoreExportRequest form request validation
  - [ ] 4.8 Define export API routes
  - [ ] 4.9 Implement rate limiting for all endpoints
  - [ ] 4.10 Verify all API tests pass

- [ ] 5. Public Share Viewing
  - [ ] 5.1 Write tests for public share viewing page
  - [ ] 5.2 Create PublicShareController
  - [ ] 5.3 Create public share viewing Livewire component
  - [ ] 5.4 Create public share Blade template
  - [ ] 5.5 Implement password verification modal
  - [ ] 5.6 Implement analytics tracking on page load
  - [ ] 5.7 Add Open Graph meta tags for social preview
  - [ ] 5.8 Implement responsive design for mobile
  - [ ] 5.9 Add proper error handling for expired/invalid shares
  - [ ] 5.10 Verify all public share tests pass

- [ ] 6. Share Modal & UI Components
  - [ ] 6.1 Write tests for ShareModal Livewire component
  - [ ] 6.2 Create ShareModal Livewire component
  - [ ] 6.3 Create share modal Blade template
  - [ ] 6.4 Implement privacy settings form (password, expiration)
  - [ ] 6.5 Implement share link generation and display
  - [ ] 6.6 Add copy-to-clipboard functionality
  - [ ] 6.7 Implement loading states and success messages
  - [ ] 6.8 Add share button to project page
  - [ ] 6.9 Style modal with Tailwind CSS
  - [ ] 6.10 Verify all ShareModal tests pass

- [ ] 7. Social Media Integration
  - [ ] 7.1 Write tests for social media sharing
  - [ ] 7.2 Add social media share buttons to share modal
  - [ ] 7.3 Implement Twitter/X share URL generation
  - [ ] 7.4 Implement LinkedIn share URL generation
  - [ ] 7.5 Implement Facebook share URL generation
  - [ ] 7.6 Implement Reddit share URL generation
  - [ ] 7.7 Implement WhatsApp share URL generation
  - [ ] 7.8 Create engaging pre-filled share text templates
  - [ ] 7.9 Add social media icons (use Heroicons or similar)
  - [ ] 7.10 Verify all social media tests pass

- [ ] 8. Export UI & Download
  - [ ] 8.1 Write tests for export generation UI
  - [ ] 8.2 Add export buttons to share modal
  - [ ] 8.3 Create export format selector (PDF, CSV, JSON)
  - [ ] 8.4 Implement export generation trigger
  - [ ] 8.5 Show export progress/loading state
  - [ ] 8.6 Display download link after generation
  - [ ] 8.7 Create download route and controller method
  - [ ] 8.8 Implement proper file serving with headers
  - [ ] 8.9 Add download tracking
  - [ ] 8.10 Verify all export tests pass

- [ ] 9. Share Management Dashboard
  - [ ] 9.1 Write tests for share management interface
  - [ ] 9.2 Create ShareManagement Livewire component
  - [ ] 9.3 Create share management page Blade template
  - [ ] 9.4 Display list of user's shares with analytics
  - [ ] 9.5 Implement share deletion functionality
  - [ ] 9.6 Add filtering (active/inactive, expired)
  - [ ] 9.7 Add sorting (date, view count)
  - [ ] 9.8 Add pagination for large lists
  - [ ] 9.9 Style dashboard with responsive design
  - [ ] 9.10 Verify all dashboard tests pass

- [ ] 10. Cleanup Jobs & Maintenance
  - [ ] 10.1 Write tests for cleanup jobs
  - [ ] 10.2 Create CleanupExpiredShares scheduled job
  - [ ] 10.3 Create CleanupOldExports scheduled job
  - [ ] 10.4 Register jobs in console kernel
  - [ ] 10.5 Implement soft delete for shares
  - [ ] 10.6 Implement file deletion for exports
  - [ ] 10.7 Add logging for cleanup operations
  - [ ] 10.8 Verify cleanup jobs work correctly

- [ ] 11. Security & Performance
  - [ ] 11.1 Implement CSRF protection verification
  - [ ] 11.2 Add rate limiting configuration
  - [ ] 11.3 Implement share data caching
  - [ ] 11.4 Add database indexes optimization
  - [ ] 11.5 Implement input sanitization
  - [ ] 11.6 Add authorization policies for shares
  - [ ] 11.7 Implement secure token generation
  - [ ] 11.8 Add SQL injection prevention checks
  - [ ] 11.9 Run security audit
  - [ ] 11.10 Run performance benchmarks

- [ ] 12. Integration & End-to-End Testing
  - [ ] 12.1 Write complete user flow tests
  - [ ] 12.2 Test share creation to public viewing flow
  - [ ] 12.3 Test password-protected share flow
  - [ ] 12.4 Test social media sharing flow
  - [ ] 12.5 Test export generation and download flow
  - [ ] 12.6 Test share management dashboard flow
  - [ ] 12.7 Test mobile responsive behavior
  - [ ] 12.8 Test error handling scenarios
  - [ ] 12.9 Verify all integration tests pass
  - [ ] 12.10 Run full test suite and ensure 100% pass rate
