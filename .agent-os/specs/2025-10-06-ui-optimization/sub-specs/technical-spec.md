# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-06-ui-optimization/spec.md

> Created: 2025-10-06
> Version: 1.0.0

## Technical Requirements

### 1. Loading Skeletons

**Implementation:**
- Create reusable Blade skeleton components for common patterns (cards, lists, forms)
- Use Tailwind's `animate-pulse` utility for shimmer effect
- Match skeleton structure to actual content layout
- Replace spinners with contextual skeleton screens

**Components Needed:**
- `skeleton-name-card.blade.php` - For name suggestion cards
- `skeleton-logo-card.blade.php` - For logo gallery items
- `skeleton-session-list.blade.php` - For session sidebar items
- `skeleton-project-card.blade.php` - For project dashboard cards

**Technical Approach:**
```blade
<!-- Example skeleton component -->
<div class="animate-pulse">
    <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-3/4 mb-2"></div>
    <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded w-1/2"></div>
</div>
```

### 2. Lazy Loading

**Image Lazy Loading:**
- Use native `loading="lazy"` attribute for images
- Add `IntersectionObserver` polyfill for older browsers
- Implement progressive image loading (blur-up technique)

**Component Lazy Loading:**
- Defer loading heavy components (chart libraries, image editors)
- Use Livewire's `wire:init` for deferred loading
- Load below-the-fold components after initial render

**Technical Approach:**
```php
// Livewire component with deferred loading
public bool $readyToLoad = false;

public function mount()
{
    // Don't load heavy data on mount
}

public function loadData()
{
    $this->readyToLoad = true;
    // Load heavy data here
}
```

```blade
<div wire:init="loadData">
    @if($readyToLoad)
        <!-- Heavy content -->
    @else
        <x-skeleton-loader />
    @endif
</div>
```

### 3. Keyboard Shortcuts

**Global Shortcuts:**
- `Cmd/Ctrl + K` - Command palette
- `Cmd/Ctrl + N` - New project
- `Cmd/Ctrl + /` - Toggle sidebar (already exists)
- `Cmd/Ctrl + G` - Generate names
- `Esc` - Close modals/cancel actions
- `?` - Show keyboard shortcuts help

**Implementation:**
- Use Alpine.js `x-on:keydown.window` for global listeners
- Prevent default browser shortcuts
- Show keyboard shortcut hints in tooltips
- Add visual overlay showing all shortcuts when `?` pressed

**Technical Approach:**
```javascript
// Alpine.js component
Alpine.data('keyboardShortcuts', () => ({
    init() {
        window.addEventListener('keydown', (e) => {
            // Cmd/Ctrl + K
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.openCommandPalette();
            }
            // More shortcuts...
        });
    }
}));
```

### 4. Optimistic UI Updates

**Actions Supporting Optimistic Updates:**
- Hide/show name suggestions
- Star/favorite items
- Toggle settings
- Delete items (with undo)

**Implementation Pattern:**
```javascript
// Update UI immediately
Alpine.effect(() => {
    item.hidden = !item.hidden;
});

// Then sync with server
$wire.toggleVisibility(item.id);
```

**Rollback Strategy:**
- Store previous state before optimistic update
- Listen for Livewire errors
- Revert to previous state if server operation fails
- Show toast notification on rollback

### 5. Micro-interactions

**Button States:**
- Hover: scale(1.02) with 150ms transition
- Active: scale(0.98) with 100ms transition
- Loading: pulse animation with disabled state
- Success: brief checkmark animation

**Card Interactions:**
- Hover: subtle shadow increase and border color change
- Click: ripple effect from click point
- Selection: smooth border and background transition

**Form Feedback:**
- Input focus: border color transition (200ms)
- Validation: shake animation for errors
- Success: brief green glow on save

**Technical Approach:**
```css
/* Tailwind classes for micro-interactions */
.btn-modern {
    @apply transition-all duration-150 ease-out
           hover:scale-102 active:scale-98
           focus:ring-2 focus:ring-offset-2;
}

.card-interactive {
    @apply transition-all duration-200
           hover:shadow-lg hover:border-accent-300;
}
```

### 6. Database Query Optimization

**Missing Indexes:**
```sql
-- Add indexes for common queries
CREATE INDEX idx_name_suggestions_project_hidden
    ON name_suggestions(project_id, is_hidden);

CREATE INDEX idx_name_suggestions_ai_model
    ON name_suggestions(ai_model_used, project_id);

CREATE INDEX idx_ai_generations_status_user
    ON ai_generations(status, user_id, created_at);

CREATE INDEX idx_sessions_user_created
    ON sessions(user_id, created_at DESC);
```

**N+1 Query Prevention:**
- Add eager loading in ProjectPage: `$project->load('nameSuggestions', 'aiGenerations')`
- Use `with()` in dashboard queries
- Add `select()` to limit columns fetched
- Use `chunk()` for large datasets

**Query Optimization:**
```php
// Before (N+1)
foreach ($projects as $project) {
    $suggestions = $project->nameSuggestions; // Query per project
}

// After (optimized)
$projects = Project::with('nameSuggestions')->get(); // Single query
```

### 7. Error State Improvements

**Better Error Messages:**
- Replace generic "Something went wrong" with specific messages
- Show actionable next steps
- Include error codes for support
- Provide retry buttons for transient errors

**Error Categories:**
- **Network errors:** "Connection lost. Check your internet and try again."
- **AI errors:** "AI service is busy. Retrying in 3 seconds..."
- **Validation errors:** "Project name must be at least 3 characters."
- **Permission errors:** "You don't have access to this project."

**Graceful Degradation:**
```php
try {
    $result = $aiService->generate($prompt);
} catch (RateLimitException $e) {
    return [
        'error' => true,
        'message' => 'AI service rate limit reached. Please try again in a few minutes.',
        'retry_after' => $e->retryAfter,
        'can_retry' => true
    ];
} catch (NetworkException $e) {
    return [
        'error' => true,
        'message' => 'Connection error. Please check your internet connection.',
        'can_retry' => true
    ];
}
```

## Approach Options

**Option A: Incremental Implementation**
- Pros: Lower risk, can ship improvements progressively, easier testing
- Cons: Longer total timeline, potential inconsistency during rollout
- **Selected** ✓

**Option B: Big Bang Refactor**
- Pros: Consistent UX all at once, one-time testing effort
- Cons: Higher risk, delayed value delivery, harder to QA

**Rationale:** Incremental approach allows us to ship value faster, get user feedback, and adjust priorities based on impact. Each optimization can be tested and verified independently.

## External Dependencies

**None** - All optimizations use existing libraries and frameworks:
- Tailwind CSS (already installed)
- Alpine.js (already installed via Livewire)
- Livewire 3 (already installed)
- Native browser APIs (IntersectionObserver, loading attribute)

## Performance Targets

**Page Load Time:**
- Dashboard: < 1.5s (current: ~2.3s)
- Project Page: < 1.2s (current: ~1.8s)
- Logo Gallery: < 2s (current: ~3.2s)

**Time to Interactive:**
- All pages: < 2.5s (current: ~3.5s)

**Database Query Count:**
- Dashboard: < 15 queries (current: ~28 queries)
- Project Page: < 20 queries (current: ~35 queries)

**Lighthouse Scores:**
- Performance: 90+ (current: ~72)
- Accessibility: 95+ (current: 88)
- Best Practices: 95+ (current: 91)
