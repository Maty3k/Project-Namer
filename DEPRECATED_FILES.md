# Deprecated Files Documentation

> Created: 2025-10-27
> Purpose: Track files marked for removal during cleanup process
> Status: In Progress

## Overview

This document tracks all files that have been marked as deprecated using the `DeprecatedFileException`. Each entry includes the file path, reason for deprecation, and validation status.

## Validation Status

- ❓ **Pending Validation**: File marked but not yet tested
- ✅ **Validated Safe**: No exceptions thrown during testing
- ❌ **Still In Use**: Exception thrown, file should NOT be deleted
- 🗑️ **Ready for Deletion**: Confirmed deprecated and safe to remove

---

## Deprecated Files

### Controllers

*No controllers marked as deprecated yet*

### Services

*No services marked as deprecated yet*

### Livewire Components

#### ❓ `app/Livewire/SessionSidebar.php`
- **Reason**: Replaced by `Sidebar.php` component
- **Evidence**: Not referenced in any routes (web.php or api.php), not embedded in any blade views except its own
- **Usage**: Only appears in test files (SessionSidebarTest.php, SessionSidebarVirtualScrollTest.php, etc.)
- **Related Files**: `resources/views/livewire/session-sidebar.blade.php`
- **Status**: Pending validation via Playwright and manual testing

#### ❓ `app/Livewire/NameGeneratorDashboard.php`
- **Reason**: Replaced by Dashboard + ProjectPage workflow
- **Evidence**: Not referenced in any routes (web.php or api.php)
- **Usage**: Only appears in test files (NameGeneratorDashboardAITest.php, etc.)
- **Related Files**: `resources/views/livewire/name-generator-dashboard.blade.php`
- **Status**: Pending validation via Playwright and manual testing

### Jobs & Commands

*No jobs or commands marked as deprecated yet*

### Models

*No models marked as deprecated yet*

### Helpers & Utilities

*No helpers or utilities marked as deprecated yet*

### Tests

*Note: Test files will not be marked as deprecated - they provide valuable regression coverage*

---

## Validation Testing

### Browser Testing (Playwright)
- [ ] User registration flow
- [ ] Name generation workflows
- [ ] Logo generation workflows
- [ ] Sharing and export features
- [ ] Profile and settings

### Manual User Testing
- [ ] Complete registration flow
- [ ] All name generation modes
- [ ] Logo generation
- [ ] Sharing features
- [ ] Any exceptions encountered: *(to be documented)*

---

## Next Steps

1. Systematically review all files in `app/` directory
2. Mark suspected deprecated files with `DeprecatedFileException`
3. Run Playwright browser testing validation
4. Conduct manual user testing
5. Update this document with validation results
6. Remove confirmed deprecated files
