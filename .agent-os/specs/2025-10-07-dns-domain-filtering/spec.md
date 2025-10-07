# Spec Requirements Document

> Spec: DNS-Based Domain Filtering
> Created: 2025-10-07
> Status: Planning

## Overview

Implement DNS-based domain filtering to pre-screen generated business names before displaying them to users. This reduces costs and API rate limits by eliminating domains with existing DNS records (which are definitely unavailable) before making expensive calls to registrar APIs for actual availability checks.

## User Stories

### Domain Filtering During Name Generation

As a user generating business names, I want to only see domains that could potentially be available, so that I don't waste time considering names that are already registered and in use.

**Workflow:**
1. User enters business idea and clicks "Generate Names"
2. AI generates 10 business name suggestions
3. System checks DNS records for all TLDs (.com, .net, .org, .io, .co, .app, .dev, .ai, .tech, .studio)
4. Domains with DNS records are filtered out (not shown to user)
5. User sees only names where at least one TLD has no DNS records
6. Each domain shows availability status based on DNS check

### Background Processing

As a user, I want domain checks to happen quickly without blocking the UI, so that I can see results immediately and domain filtering happens in the background.

**Workflow:**
1. User sees generated names immediately with "Checking..." status
2. DNS checks run in background for all domain variations
3. As checks complete, domains without DNS records appear
4. Domains with DNS records are quietly filtered out
5. Final list shows only potentially available domains

## Spec Scope

1. **DNS Lookup Service** - Create service to check DNS records for domains
2. **Integration with DomainCheckService** - Add DNS pre-screening before API calls
3. **Domain Filtering Logic** - Filter out domains with A, AAAA, CNAME, or MX records
4. **Background Job Processing** - Queue DNS checks for non-blocking execution
5. **Cache DNS Results** - Store DNS check results to avoid repeated lookups

## Out of Scope

- Actual registrar API integration (future feature)
- WHOIS lookups or detailed domain information
- Domain price checking
- Domain suggestion alternatives when all TLDs are taken
- UI changes beyond showing/hiding domains based on DNS status

## Expected Deliverable

1. DNS lookup functionality integrated into domain checking workflow
2. Generated names automatically filtered to exclude domains with DNS records
3. Only potentially available domains displayed to users
4. DNS check results cached to improve performance
5. Background job processing for non-blocking DNS checks
6. All tests passing with >90% coverage for DNS filtering logic

## Spec Documentation

- Tasks: @.agent-os/specs/2025-10-07-dns-domain-filtering/tasks.md
- Technical Specification: @.agent-os/specs/2025-10-07-dns-domain-filtering/sub-specs/technical-spec.md
- Database Schema: @.agent-os/specs/2025-10-07-dns-domain-filtering/sub-specs/database-schema.md
- Tests Specification: @.agent-os/specs/2025-10-07-dns-domain-filtering/sub-specs/tests.md
