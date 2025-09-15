# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-09-02-ui-ux-fixes/spec.md

> Created: 2025-09-03
> Version: 1.0.0

## Technical Requirements

### Dashboard Auto-Generation Fix

**Problem:** The "Save & Generate Names" button on the dashboard only creates a project and redirects to the project page, but does not automatically trigger name generation as the button text promises.

**Current Flow:**
1. User clicks "Save & Generate Names" button
2. Dashboard.php `createProject()` method creates project
3. User redirected to `/project/{uuid}` 
4. ProjectPage loads normally without auto-generation
5. User must manually click "Generate More Names" to actually generate names

**Required Flow:**
1. User clicks "Save & Generate Names" button
2. Dashboard.php `createProject()` method creates project
3. User redirected to `/project/{uuid}?generate=true`
4. ProjectPage detects `generate=true` parameter
5. ProjectPage automatically enables AI controls and triggers name generation
6. Names are generated immediately without additional user action

### Implementation Details

**Dashboard Component Changes:**
- Modify `createProject()` method in `app/Livewire/Dashboard.php`
- Change redirect URL to include `?generate=true` parameter
- No other changes needed to dashboard functionality

**ProjectPage Component Changes:**
- Modify `mount()` method in `app/Livewire/ProjectPage.php` 
- Detect `request()->has('generate')` and `request()->get('generate') === 'true'`
- When auto-generation is detected:
  - Set `$this->useAIGeneration = true`
  - Set `$this->showAIControls = true` 
  - Dispatch JavaScript event to trigger generation after component mount
  - Or call `generateMoreNames()` method directly after mount completes

**User Experience Requirements:**
- Zero additional clicks required after "Save & Generate Names"
- Clear visual feedback that generation is starting automatically
- User can still cancel or modify generation if desired
- Maintains all existing AI generation functionality and controls

## Approach Options

**Option A: URL Parameter + JavaScript Event (Recommended)**
- Dashboard redirects with `?generate=true`
- ProjectPage mount detects parameter and dispatches JS event
- Frontend JavaScript listens for event and calls Livewire method
- Pros: Clean separation, easy to test, non-blocking
- Cons: Requires JavaScript event handling

**Option B: URL Parameter + Direct Method Call**  
- Dashboard redirects with `?generate=true`
- ProjectPage mount detects parameter and calls `generateMoreNames()` directly
- Pros: Simpler implementation, no JavaScript needed
- Cons: May block page load, harder to provide loading feedback

**Option C: Session Flag**
- Dashboard sets session flag before redirect
- ProjectPage checks session flag and triggers generation
- Pros: No URL pollution, works without JavaScript
- Cons: Session management complexity, harder to test

**Rationale:** Option A provides the cleanest user experience with proper loading states and maintains the existing architecture patterns used in the application.

### Theme Customizer Real-Time Updates

**Problem:** The theme customizer exists and appears functional, but may not provide immediate visual feedback when themes are changed, and JavaScript event handling for real-time updates may not be working properly.

**Current Implementation Analysis:**
- ThemeCustomizer component exists with proper Livewire structure
- ThemeService provides color validation and CSS generation
- JavaScript exists for handling `theme-updated` events
- UserThemePreference model handles persistence

**Potential Issues:**
1. JavaScript events may not be firing properly on theme changes
2. CSS custom properties may not be injected correctly into document head
3. Real-time preview may not update immediately when colors change
4. Theme persistence across page navigation may be inconsistent
5. User feedback for save operations may be missing

**Required Improvements:**
- Ensure `theme-updated` event is dispatched on all color changes
- Verify CSS custom properties are properly injected and scoped
- Add immediate visual feedback for theme save operations
- Test theme persistence across browser sessions
- Improve error handling for theme operations
- Add smooth CSS transitions for color changes (transition: all 0.3s ease)

### Seasonal Themes Implementation

**Seasonal Theme Collection:**
1. **Summer Theme**: Bright, warm color palette
   - Primary: #ff6b35 (coral orange)
   - Accent: #ffcc02 (sunny yellow)
   - Background: #fff8f0 (warm cream)
   - Text: #2d1810 (dark brown)

2. **Winter Theme**: Cool, crisp color palette
   - Primary: #4a90e2 (ice blue)
   - Accent: #87ceeb (sky blue)
   - Background: #f0f8ff (alice blue)
   - Text: #1e3a8a (deep blue)

3. **Halloween Theme**: Spooky, festive color palette
   - Primary: #ff6600 (bright orange)
   - Accent: #800080 (purple)
   - Background: #1a0d00 (dark brown/black)
   - Text: #ff6600 (orange text on dark)

4. **Spring Theme**: Fresh, growth-inspired palette
   - Primary: #32cd32 (lime green)
   - Accent: #ff69b4 (hot pink)
   - Background: #f0fff0 (honeydew)
   - Text: #006400 (dark green)

5. **Autumn Theme**: Warm, earthy color palette
   - Primary: #d2691e (chocolate)
   - Accent: #ff4500 (red orange)
   - Background: #faf0e6 (linen)
   - Text: #8b4513 (saddle brown)

**Implementation Requirements:**
- Update ThemeService::getPredefinedThemes() to include seasonal themes
- Add theme categorization (Basic, Seasonal) in UI display
- Include seasonal theme previews with representative icons/imagery
- Ensure all seasonal themes meet WCAG accessibility standards
- Add smooth transitions when switching between seasonal themes

### AI Generation Mode Button State Management

**Current Issue Analysis:**
The AI generation mode selection uses HTML radio buttons, which by design cannot be deselected once selected. Users expect to be able to click a selected mode button again to deselect it.

**Required Solution:**
Replace radio button behavior with custom toggle button logic that allows both selection and deselection.

**Implementation Approach:**
1. **Custom Button Components**: Replace `flux:radio` with `flux:button` or custom button elements
2. **State Management**: Update Livewire component to handle null/empty generation mode state
3. **Toggle Logic**: Implement click handler that selects if unselected, deselects if already selected
4. **Visual States**: Provide clear visual feedback for unselected, selected, hover, and focus states

**Technical Requirements:**
- Convert radio buttons to toggle buttons in both dashboard and project page
- Update `generationMode` property to accept null values (nullable string)
- Implement `toggleGenerationMode(string $mode)` method in Livewire components
- Add CSS classes for different button states (unselected, selected, hover, focus)
- Ensure keyboard accessibility (Enter/Space to toggle, Tab navigation)
- Maintain smooth transitions between states (0.2s ease transition)
- Test touch interactions work properly on mobile devices

**Button State Logic:**
```php
public function toggleGenerationMode(string $mode): void
{
    if ($this->generationMode === $mode) {
        $this->generationMode = null; // Deselect
    } else {
        $this->generationMode = $mode; // Select new mode
    }
}
```

**Visual Design Requirements:**
- **Unselected**: Gray border, white/transparent background
- **Selected**: Blue border, blue background, white text
- **Hover**: Light blue background, smooth transition
- **Focus**: Clear focus outline for accessibility
- **Touch**: Appropriate touch target size (minimum 44x44px)

### Smooth Animations and Performance Requirements

**Animation Performance Standards:**
- All animations must maintain 60fps (16.67ms frame budget)
- Maximum animation duration: 500ms for most interactions
- Use CSS transforms and opacity for better performance (GPU acceleration)
- Implement `will-change` property judiciously for animation optimization

**Required Animation Types:**
1. **Loading States**: Smooth spinner/skeleton animations during AI generation
2. **Button Interactions**: Hover, active, and disabled state transitions  
3. **Theme Changes**: Smooth color transitions across all UI elements
4. **Component State Changes**: Fade in/out for showing/hiding elements
5. **Navigation**: Smooth page/component transitions
6. **Feedback Animations**: Success/error message animations

**Performance Optimization Techniques:**
- Use `transform3d()` to trigger hardware acceleration
- Minimize layout thrashing with transform and opacity-only animations
- Implement animation debouncing for rapid state changes
- Use `requestAnimationFrame` for JavaScript-driven animations
- Add `prefers-reduced-motion` media query support for accessibility

**Performance Benchmarks:**
- Initial page load: < 3 seconds (measured via Lighthouse)
- Largest Contentful Paint (LCP): < 2.5 seconds
- First Input Delay (FID): < 100 milliseconds  
- Cumulative Layout Shift (CLS): < 0.1
- Animation frame rate: Consistent 60fps during interactions

### Logo Gallery File Upload System

**File Upload Requirements:**
- Support for PNG, JPG, and SVG file formats
- Maximum file size: 10MB per file
- Maximum total upload: 50MB per session
- Image dimension validation: minimum 100x100px, maximum 4000x4000px
- Duplicate file detection based on file hash

**Drag-and-Drop Implementation:**
- HTML5 File API for drag-and-drop functionality
- Visual feedback with CSS animations during drag hover
- Support for multiple file selection and drop
- File preview generation before upload confirmation
- Progress tracking for individual files during upload

**File Storage and Management:**
- Laravel file storage system for secure file handling
- Automatic file organization by user and project
- Image optimization and thumbnail generation
- File metadata storage (original name, size, upload date)
- Secure file serving with user authentication

**User Experience Features:**
- Upload queue with batch processing capability
- Real-time progress indicators with smooth animations
- Error handling with clear, actionable feedback messages
- Mobile-friendly file browser fallback for touch devices
- Bulk operations for managing multiple uploaded files

## External Dependencies

No new external dependencies required. This fix uses existing Laravel/Livewire patterns and the existing AI generation service infrastructure.

## Testing Requirements

**Unit Tests:**
- Test Dashboard `createProject()` method redirects with correct URL parameter
- Test ProjectPage `mount()` method detects auto-generation parameter correctly
- Test auto-generation sets correct component properties

**Integration Tests:**
- Test complete flow from dashboard button click to project page with names generated
- Test that auto-generation respects user's existing AI preferences  
- Test that regular project page access (without parameter) works unchanged
- Test that invalid or missing generate parameter is handled gracefully

**Browser Tests:**
- End-to-end test clicking "Save & Generate Names" and verifying automatic generation
- Test user can cancel auto-generation if desired
- Test loading states and visual feedback during auto-generation
- Test responsive behavior on mobile devices