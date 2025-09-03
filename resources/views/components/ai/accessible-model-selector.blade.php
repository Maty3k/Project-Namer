@props([
    'models' => [],
    'selectedModel' => null,
    'onModelSelect' => null,
    'disabled' => false,
])

@php
    $accessibilityService = app(\App\Services\AI\AIAccessibilityService::class);
@endphp

<div class="ai-model-selector" role="group" aria-labelledby="model-selector-label">
    <h3 id="model-selector-label" class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Choose AI Model
    </h3>
    
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6" id="model-selector-description">
        {{ $accessibilityService->generateKeyboardInstructions('model_selection') }}
    </p>
    
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3" 
         role="radiogroup" 
         aria-labelledby="model-selector-label"
         aria-describedby="model-selector-description">
        
        @foreach($models as $modelId => $model)
            @php
                $isSelected = $selectedModel === $modelId;
                $isDisabled = $disabled || !($model['enabled'] ?? true);
                $modelDescription = $accessibilityService->generateModelDescription($model);
                $attributes = $accessibilityService->getElementAttributes('model_card', [
                    'selected' => $isSelected,
                    'description_id' => "model-desc-{$modelId}",
                ]);
            @endphp
            
            <div class="ai-model-card {{ $isSelected ? 'selected' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                 @foreach($attributes as $attr => $value)
                     {{ $attr }}="{{ $value }}"
                 @endforeach
                 @if($onModelSelect && !$isDisabled)
                     wire:click="{{ $onModelSelect }}('{{ $modelId }}')"
                 @endif
                 @if(!$isDisabled)
                     onclick="selectModel('{{ $modelId }}')"
                     onkeydown="handleModelKeydown(event, '{{ $modelId }}')"
                 @endif
                 data-model-id="{{ $modelId }}">
                
                <!-- Model Header -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            @if($isSelected)
                                <div class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center" 
                                     aria-hidden="true">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-full" 
                                     aria-hidden="true"></div>
                            @endif
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                {{ $model['name'] }}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">
                                by {{ $model['provider'] }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Status Indicator -->
                    <div class="flex-shrink-0">
                        @php
                            $status = $model['status'] ?? 'available';
                            $statusClass = match($status) {
                                'available' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                'disabled' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
                                'maintenance' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                                'missing_api_key' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
                            };
                            $statusText = match($status) {
                                'available' => 'Available',
                                'disabled' => 'Disabled',
                                'maintenance' => 'Maintenance',
                                'missing_api_key' => 'No API Key',
                                default => 'Unknown',
                            };
                        @endphp
                        
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}"
                              aria-label="Status: {{ $statusText }}">
                            {{ $statusText }}
                        </span>
                    </div>
                </div>
                
                <!-- Model Description -->
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                    {{ $model['description'] ?? 'No description available.' }}
                </p>
                
                <!-- Model Details -->
                <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                    @if(isset($model['max_tokens']))
                        <div class="flex justify-between">
                            <span>Max tokens:</span>
                            <span class="font-medium">{{ number_format($model['max_tokens']) }}</span>
                        </div>
                    @endif
                    
                    @if(isset($model['cost_per_1k_tokens']))
                        <div class="flex justify-between">
                            <span>Cost/1K:</span>
                            <span class="font-medium">${{ number_format($model['cost_per_1k_tokens'], 4) }}</span>
                        </div>
                    @endif
                    
                    @if(isset($model['temperature']))
                        <div class="flex justify-between">
                            <span>Temperature:</span>
                            <span class="font-medium">{{ $model['temperature'] }}</span>
                        </div>
                    @endif
                    
                    @if(isset($model['rate_limit_per_minute']))
                        <div class="flex justify-between">
                            <span>Rate limit:</span>
                            <span class="font-medium">{{ $model['rate_limit_per_minute'] }}/min</span>
                        </div>
                    @endif
                </div>
                
                <!-- Hidden description for screen readers -->
                <div id="model-desc-{{ $modelId }}" class="sr-only">
                    {{ $modelDescription }}
                    @if($isSelected)
                        This model is currently selected.
                    @endif
                    @if($isDisabled)
                        This model is currently disabled and cannot be selected.
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Selected Model Announcement for Screen Readers -->
    <div aria-live="polite" aria-atomic="true" class="sr-only" id="model-selection-announcement"></div>
</div>

<script>
function selectModel(modelId) {
    // Update selection state
    document.querySelectorAll('.ai-model-card').forEach(card => {
        const isSelected = card.dataset.modelId === modelId;
        card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        card.classList.toggle('selected', isSelected);
    });
    
    // Announce selection to screen readers
    const selectedModel = document.querySelector(`[data-model-id="${modelId}"]`);
    const modelName = selectedModel?.querySelector('h4')?.textContent;
    if (modelName) {
        const announcement = `${modelName} model selected`;
        document.getElementById('model-selection-announcement').textContent = announcement;
        
        // Also use the global announcement system if available
        if (window.aiAccessibility) {
            window.aiAccessibility.announce(announcement, 'polite');
        }
    }
    
    // Dispatch custom event for parent components
    document.dispatchEvent(new CustomEvent('model-selected', {
        detail: { modelId: modelId }
    }));
}

function handleModelKeydown(event, modelId) {
    const card = event.currentTarget;
    const allCards = Array.from(document.querySelectorAll('.ai-model-card:not(.disabled)'));
    const currentIndex = allCards.indexOf(card);
    
    switch (event.key) {
        case 'Enter':
        case ' ':
            event.preventDefault();
            selectModel(modelId);
            break;
            
        case 'ArrowRight':
        case 'ArrowDown':
            event.preventDefault();
            const nextIndex = (currentIndex + 1) % allCards.length;
            allCards[nextIndex]?.focus();
            break;
            
        case 'ArrowLeft':
        case 'ArrowUp':
            event.preventDefault();
            const prevIndex = (currentIndex - 1 + allCards.length) % allCards.length;
            allCards[prevIndex]?.focus();
            break;
            
        case 'Home':
            event.preventDefault();
            allCards[0]?.focus();
            break;
            
        case 'End':
            event.preventDefault();
            allCards[allCards.length - 1]?.focus();
            break;
    }
}

// Initialize model selector accessibility
document.addEventListener('DOMContentLoaded', function() {
    // Set initial focus on first available model if none selected
    const selectedCard = document.querySelector('.ai-model-card[aria-pressed="true"]');
    if (!selectedCard) {
        const firstCard = document.querySelector('.ai-model-card:not(.disabled)');
        if (firstCard) {
            firstCard.focus();
        }
    }
    
    // Add roving tabindex for better keyboard navigation
    const cards = document.querySelectorAll('.ai-model-card:not(.disabled)');
    cards.forEach((card, index) => {
        card.setAttribute('tabindex', index === 0 ? '0' : '-1');
        
        card.addEventListener('focus', function() {
            cards.forEach(c => c.setAttribute('tabindex', '-1'));
            this.setAttribute('tabindex', '0');
        });
    });
});
</script>

<style>
/* Model selector specific styles */
.ai-model-card {
    transition: all 0.2s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.ai-model-card:not(.disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.ai-model-card.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.dark .ai-model-card.selected {
    border-color: #60a5fa;
    background-color: #1e40af;
}

.ai-model-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

.ai-model-card:focus-visible {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.dark .ai-model-card:focus-visible {
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
}

/* High contrast mode enhancements */
@media (prefers-contrast: high) {
    .ai-model-card {
        border: 2px solid currentColor !important;
    }
    
    .ai-model-card.selected {
        background-color: #ffff00 !important;
        color: #000000 !important;
    }
    
    .ai-model-card:focus-visible {
        border: 4px solid #000000 !important;
        background-color: #ffff00 !important;
        color: #000000 !important;
    }
}

/* Reduced motion preferences */
@media (prefers-reduced-motion: reduce) {
    .ai-model-card {
        transition: none;
    }
    
    .ai-model-card:not(.disabled):hover {
        transform: none;
    }
}
</style>