# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-08-ai-progress-indicator/spec.md

> Created: 2025-10-08
> Version: 1.0.0

## Technical Requirements

### Progress Bar Component

**Livewire Component: `AIProgressIndicator`**
- Real-time progress tracking via Livewire polling
- Smooth CSS animations using Tailwind transitions
- Responsive design for mobile and desktop
- Accessible with ARIA attributes for screen readers

**Visual States:**
- **Normal Mode:** Blue/purple progress bar, standard speed
- **Deep Thinking Mode:** Purple/violet gradient, slower speed, brain icon
- **Estimated Time:** Dynamic countdown showing remaining seconds
- **Completion:** Brief success animation before hiding

### Progress Tracking Logic

**Backend Progress Tracking:**
- Track progress in `AIGeneration` model or cache
- Update progress percentage at key milestones:
  - 0%: Job dispatched
  - 25%: AI API request sent
  - 50%: Partial response received
  - 75%: Processing names
  - 100%: Complete

**Progress Update Events:**
- Livewire wire:poll every 500ms during generation
- Server-side events for progress updates
- Optimistic UI updates for smooth experience

### Timing Differences

**Normal Mode:**
- Expected duration: 3-5 seconds
- Progress increment: ~20% per second
- Smooth linear progression

**Deep Thinking Mode:**
- Expected duration: 8-12 seconds
- Progress increment: ~8-10% per second
- Slower, more deliberate progression
- Visual indicator showing enhanced processing

### Component Structure

```php
// app/Livewire/Components/AIProgressIndicator.php
namespace App\Livewire\Components;

use Livewire\Component;

class AIProgressIndicator extends Component
{
    public ?int $generationId = null;
    public int $progress = 0;
    public bool $isDeepThinking = false;
    public ?int $estimatedTimeRemaining = null;
    public bool $isComplete = false;

    protected $listeners = [
        'ai-generation-started' => 'startProgress',
        'ai-generation-progress' => 'updateProgress',
        'ai-generation-complete' => 'completeProgress',
    ];

    public function startProgress($data): void
    {
        $this->generationId = $data['generation_id'];
        $this->isDeepThinking = $data['is_deep_thinking'] ?? false;
        $this->progress = 0;
        $this->isComplete = false;
        $this->estimateTime();
    }

    public function updateProgress($data): void
    {
        $this->progress = $data['progress'];
        $this->estimateTime();
    }

    public function completeProgress(): void
    {
        $this->progress = 100;
        $this->isComplete = true;
        $this->estimatedTimeRemaining = 0;
    }

    public function getProgress(): array
    {
        if (!$this->generationId) {
            return ['progress' => 0];
        }

        $generation = AIGeneration::find($this->generationId);

        if (!$generation) {
            return ['progress' => 0];
        }

        return [
            'progress' => $generation->progress ?? 0,
            'status' => $generation->status,
        ];
    }

    private function estimateTime(): void
    {
        if ($this->progress >= 100) {
            $this->estimatedTimeRemaining = 0;
            return;
        }

        $totalTime = $this->isDeepThinking ? 10 : 4; // seconds
        $elapsedProgress = $this->progress / 100;
        $remaining = $totalTime * (1 - $elapsedProgress);

        $this->estimatedTimeRemaining = max(0, (int) ceil($remaining));
    }
}
```

### UI Implementation

**Blade Template:**
```blade
<div wire:poll.500ms="getProgress"
     class="@if(!$generationId || $isComplete) hidden @endif">

    <!-- Progress Bar Container -->
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
        <div class="h-full transition-all duration-500 ease-out
                    @if($isDeepThinking)
                        bg-gradient-to-r from-purple-500 via-violet-500 to-purple-600
                    @else
                        bg-blue-500
                    @endif"
             style="width: {{ $progress }}%">
        </div>
    </div>

    <!-- Status Text -->
    <div class="flex items-center justify-between mt-2 text-sm">
        <div class="flex items-center gap-2">
            @if($isDeepThinking)
                <flux:icon.academic-cap class="w-4 h-4 text-purple-600" />
                <span class="text-purple-700 dark:text-purple-300 font-medium">
                    Deep Thinking Mode
                </span>
            @else
                <flux:icon.sparkles class="w-4 h-4 text-blue-600" />
                <span class="text-gray-700 dark:text-gray-300">
                    Generating Names
                </span>
            @endif
        </div>

        <div class="text-gray-600 dark:text-gray-400">
            @if($estimatedTimeRemaining > 0)
                ~{{ $estimatedTimeRemaining }}s remaining
            @else
                Almost done...
            @endif
        </div>
    </div>
</div>
```

### Integration Points

**NameGeneratorDashboard Component:**
```php
// Dispatch event when generation starts
$this->dispatch('ai-generation-started', [
    'generation_id' => $generation->id,
    'is_deep_thinking' => $this->deepThinking,
]);

// Update progress in job
// app/Jobs/GenerateNamesWithModelJob.php
$generation->update(['progress' => 25]);
Event::dispatch('ai-generation-progress', [
    'generation_id' => $generation->id,
    'progress' => 25,
]);
```

**AIGeneration Model Update:**
```php
// Add progress column to ai_generations table
Schema::table('ai_generations', function (Blueprint $table) {
    $table->integer('progress')->default(0)->after('status');
});
```

## Approach Options

**Option A: Polling-based Progress (Selected)**
- Pros: Simple implementation, works with existing Livewire architecture
- Cons: Additional server requests every 500ms during generation

**Option B: WebSocket-based Progress**
- Pros: Real-time updates, no polling overhead
- Cons: Requires WebSocket server (Laravel Echo, Pusher), more complex setup

**Option C: Simulated Progress**
- Pros: No backend changes, pure frontend animation
- Cons: Not accurate, doesn't reflect actual AI processing

**Rationale:** Option A (polling-based) is selected because it integrates seamlessly with Livewire's existing polling mechanism, requires minimal infrastructure changes, and provides accurate progress tracking without the complexity of WebSockets.

## External Dependencies

None - uses existing stack (Livewire, TailwindCSS, FluxUI)

## Performance Considerations

- **Polling Impact:** 500ms polling interval during active generation (typically 3-10 seconds)
- **Mitigation:** Only poll when generation is active, stop polling when complete
- **Database Load:** Single query per poll to check generation progress
- **Frontend Performance:** CSS transitions handled by GPU, minimal JavaScript
