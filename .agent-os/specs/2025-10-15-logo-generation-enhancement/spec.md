# Spec Requirements Document

> Spec: Logo Generation Enhancement
> Created: 2025-10-15
> Status: Planning

## Overview

Enhance the existing logo generation workflow by reducing the generated logo count from 12 to 5 per request, lowering image resolution for faster generation and reduced costs, and utilizing Prism for OpenAI API calls to ensure consistent architecture. This refinement maintains the inspiration-focused purpose while improving performance, reducing API costs, and streamlining the user experience with a more manageable number of logo concepts.

## User Stories

### Streamlined Logo Generation

As an indie hacker, I want to generate a smaller, focused set of logo concepts (5 instead of 12), so that I can review inspiration options more quickly without being overwhelmed by too many choices.

When a user clicks "Generate Logos" from an expanded name card, the system generates exactly 5 logo variations using OpenAI's DALL-E API via Prism. The generation process is faster due to fewer API calls and lower resolution images (512x512 instead of 1024x1024), providing logo inspiration concepts in less time and at a lower cost. The logos remain clearly marked as inspiration only, with disclaimers encouraging users to hire professional designers or create refined versions using other tools.

### Cost-Efficient Logo Inspiration

As a product agency working with multiple clients, I want logo generation to be cost-effective, so that I can generate inspiration concepts for many different naming options without excessive API costs.

By generating 5 logos instead of 12 and using 512x512 resolution instead of 1024x1024, the cost per generation request is reduced by approximately 83% (from ~$0.48 to ~$0.08). This makes it practical to generate logo concepts for multiple name variations without budget concerns, while still providing sufficient visual inspiration for clients to understand potential brand direction.

### Consistent Architecture via Prism

As a developer maintaining the codebase, I want all AI API calls to use Prism, so that we have a unified architecture for API communication with consistent error handling, rate limiting, and logging across all AI features.

The logo generation service is refactored to use Prism for OpenAI API calls instead of direct HTTP client calls. This ensures that logo generation follows the same patterns as name generation, benefits from Prism's built-in error handling and retry logic, and maintains architectural consistency across the entire application.

## Spec Scope

1. **Reduce Logo Count** - Change generation from 12 logos (4 styles × 3 variations) to 5 logos total with simplified style distribution
2. **Lower Image Resolution** - Reduce DALL-E image generation from 1024x1024 to 512x512 for faster generation and lower costs
3. **Prism Integration** - Refactor OpenAILogoService to use Prism instead of direct HTTP client for API calls
4. **Update UI Expectations** - Modify user-facing messaging and progress indicators to reflect 5 logo generation
5. **Cost Tracking Updates** - Adjust cost calculation to reflect new 512x512 pricing (~$0.016 per image)
6. **Maintain Existing Features** - Preserve all current functionality including color customization, export options, and gallery display

## Out of Scope

- Changes to the existing color customization system or color palette options
- Modifications to the export functionality (SVG/PNG downloads)
- Advanced logo editing capabilities or custom prompt inputs
- Changes to the logo gallery UI layout or display
- Professional logo design services or trademark checking
- Logo style selection interface changes (users don't select styles, system generates automatically)

## Expected Deliverable

1. Users click "Generate Logos" button and system generates exactly 5 logo concepts (reduced from 12)
2. Logo generation uses 512x512 resolution images for faster processing and lower costs (~$0.08 per request vs. ~$0.48)
3. OpenAILogoService uses Prism for all OpenAI API calls with consistent error handling and retry logic
4. Cost tracking accurately reflects new $0.016 per image pricing for 512x512 resolution
5. All existing features (color customization, batch downloads, gallery display) continue to work unchanged with the 5-logo format

## Spec Documentation

- Spec Summary: @.agent-os/specs/2025-10-15-logo-generation-enhancement/spec-lite.md
- Tasks: @.agent-os/specs/2025-10-15-logo-generation-enhancement/tasks.md
- Technical Specification: @.agent-os/specs/2025-10-15-logo-generation-enhancement/sub-specs/technical-spec.md
- Tests Specification: @.agent-os/specs/2025-10-15-logo-generation-enhancement/sub-specs/tests.md
