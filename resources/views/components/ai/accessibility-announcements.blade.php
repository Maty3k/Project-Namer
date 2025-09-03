{{-- 
AI Accessibility Announcements Component

Provides ARIA live regions for screen reader announcements during AI generation processes.
This component ensures that important status updates are communicated to assistive technology users.
--}}

@props([
    'announcements' => [],
    'liveRegion' => 'polite',
    'atomic' => true,
])

<!-- Screen Reader Only Skip Links -->
<div class="sr-only">
    <a href="#ai-form" class="ai-skip-link">Skip to AI name generation form</a>
    <a href="#ai-results" class="ai-skip-link">Skip to generated results</a>
    <a href="#main-content" class="ai-skip-link">Skip to main content</a>
</div>

<!-- ARIA Live Region for Status Announcements -->
<div 
    id="ai-live-region"
    class="sr-only" 
    aria-live="{{ $liveRegion }}"
    aria-atomic="{{ $atomic ? 'true' : 'false' }}"
    role="{{ $liveRegion === 'assertive' ? 'alert' : 'status' }}">
    @if(!empty($announcements))
        {{ end($announcements) }}
    @endif
</div>

<!-- Additional Live Region for Error Messages -->
<div 
    id="ai-error-region"
    class="sr-only"
    aria-live="assertive"
    aria-atomic="true"
    role="alert">
    <!-- Error messages will be injected here via JavaScript -->
</div>

<!-- Keyboard Navigation Instructions (Hidden by default) -->
<div 
    id="ai-keyboard-instructions" 
    class="sr-only"
    role="region"
    aria-label="Keyboard navigation instructions">
    <h2>Keyboard Navigation for AI Interface</h2>
    <ul>
        <li>Use Tab key to navigate between interactive elements</li>
        <li>Use Enter or Space to activate buttons and select models</li>
        <li>Use Arrow keys to navigate through AI model options</li>
        <li>Use Escape key to cancel operations or close modals</li>
        <li>Press Alt+G to start name generation</li>
        <li>Press Alt+C to clear the form</li>
    </ul>
</div>

<!-- Progress Announcements -->
<div 
    id="ai-progress-region"
    class="sr-only"
    aria-live="polite"
    aria-atomic="false"
    role="status">
    <!-- Progress updates will be announced here -->
</div>

<!-- Focus Management Landmark -->
<div 
    id="ai-focus-target" 
    tabindex="-1" 
    class="sr-only"
    role="region"
    aria-label="AI generation interface">
    <!-- This div helps manage focus after dynamic content updates -->
</div>

<!-- Character Count Announcements -->
<div 
    id="ai-character-count-region"
    class="sr-only"
    aria-live="polite"
    aria-atomic="true">
    <!-- Character count updates will be announced here -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Accessibility enhancement JavaScript
    const aiInterface = {
        liveRegion: document.getElementById('ai-live-region'),
        errorRegion: document.getElementById('ai-error-region'),
        progressRegion: document.getElementById('ai-progress-region'),
        characterCountRegion: document.getElementById('ai-character-count-region'),
        
        /**
         * Announce message to screen readers
         */
        announce: function(message, type = 'polite') {
            const region = type === 'assertive' ? this.errorRegion : this.liveRegion;
            if (region) {
                region.textContent = message;
                // Clear after a delay to avoid repeated announcements
                setTimeout(() => {
                    region.textContent = '';
                }, 1000);
            }
        },
        
        /**
         * Announce progress updates
         */
        announceProgress: function(message) {
            if (this.progressRegion) {
                this.progressRegion.textContent = message;
                setTimeout(() => {
                    this.progressRegion.textContent = '';
                }, 2000);
            }
        },
        
        /**
         * Announce character count updates
         */
        announceCharacterCount: function(message) {
            if (this.characterCountRegion) {
                this.characterCountRegion.textContent = message;
                setTimeout(() => {
                    this.characterCountRegion.textContent = '';
                }, 1500);
            }
        },
        
        /**
         * Announce error messages
         */
        announceError: function(message) {
            this.announce(message, 'assertive');
        },
        
        /**
         * Focus management for dynamic content
         */
        manageFocus: function(targetId) {
            const target = document.getElementById(targetId) || document.getElementById('ai-focus-target');
            if (target) {
                target.focus();
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },
        
        /**
         * Enhanced keyboard navigation
         */
        setupKeyboardNavigation: function() {
            // Global keyboard shortcuts
            document.addEventListener('keydown', function(event) {
                // Alt + G: Start generation
                if (event.altKey && event.key.toLowerCase() === 'g') {
                    event.preventDefault();
                    const generateButton = document.querySelector('[data-action="generate"]');
                    if (generateButton && !generateButton.disabled) {
                        generateButton.click();
                        aiInterface.announce('AI name generation started');
                    }
                }
                
                // Alt + C: Clear form
                if (event.altKey && event.key.toLowerCase() === 'c') {
                    event.preventDefault();
                    const clearButton = document.querySelector('[data-action="clear"]');
                    if (clearButton) {
                        clearButton.click();
                        aiInterface.announce('Form cleared');
                    }
                }
                
                // Escape: Cancel/close
                if (event.key === 'Escape') {
                    const modal = document.querySelector('[data-modal][data-open="true"]');
                    const cancelButton = document.querySelector('[data-action="cancel"]');
                    
                    if (modal) {
                        // Close modal
                        const closeButton = modal.querySelector('[data-action="close"]');
                        if (closeButton) {
                            closeButton.click();
                        }
                    } else if (cancelButton && !cancelButton.disabled) {
                        cancelButton.click();
                        aiInterface.announce('Operation cancelled');
                    }
                }
            });
            
            // Enhanced focus indicators for model cards
            const modelCards = document.querySelectorAll('[data-ai-model]');
            modelCards.forEach(card => {
                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');
                
                card.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        card.click();
                        const modelName = card.getAttribute('data-ai-model');
                        aiInterface.announce(`${modelName} AI model selected`);
                    }
                });
            });
            
            // Arrow key navigation for model selection
            let currentModelIndex = 0;
            const models = Array.from(modelCards);
            
            document.addEventListener('keydown', function(event) {
                if (models.length === 0) return;
                
                const focusedElement = document.activeElement;
                const isInModelSelection = models.includes(focusedElement);
                
                if (isInModelSelection && (event.key === 'ArrowDown' || event.key === 'ArrowRight')) {
                    event.preventDefault();
                    currentModelIndex = (currentModelIndex + 1) % models.length;
                    models[currentModelIndex].focus();
                }
                
                if (isInModelSelection && (event.key === 'ArrowUp' || event.key === 'ArrowLeft')) {
                    event.preventDefault();
                    currentModelIndex = (currentModelIndex - 1 + models.length) % models.length;
                    models[currentModelIndex].focus();
                }
            });
        }
    };
    
    // Initialize accessibility features
    aiInterface.setupKeyboardNavigation();
    
    // Expose to global scope for Livewire integration
    window.aiAccessibility = aiInterface;
    
    // Listen for Livewire events
    document.addEventListener('ai-status-update', function(event) {
        aiInterface.announce(event.detail.message || 'Status updated');
    });
    
    document.addEventListener('ai-progress-update', function(event) {
        aiInterface.announceProgress(event.detail.message || 'Progress updated');
    });
    
    document.addEventListener('ai-error', function(event) {
        aiInterface.announceError(event.detail.message || 'An error occurred');
    });
    
    document.addEventListener('ai-character-count', function(event) {
        if (event.detail.message) {
            aiInterface.announceCharacterCount(event.detail.message);
        }
    });
    
    // Focus management for dynamic content updates
    document.addEventListener('ai-results-updated', function(event) {
        // Announce results and focus on results area
        setTimeout(() => {
            aiInterface.manageFocus('ai-results');
            if (event.detail.summary) {
                aiInterface.announce(event.detail.summary);
            }
        }, 100);
    });
    
    // Reduced motion support
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.body.classList.add('ai-no-motion');
    }
    
    // High contrast mode support
    if (window.matchMedia('(prefers-contrast: high)').matches) {
        document.body.classList.add('ai-high-contrast');
    }
});
</script>

<style>
/* Ensure skip links are accessible when focused */
.ai-skip-link:focus {
    position: fixed;
    top: 6px;
    left: 6px;
    width: auto;
    height: auto;
    padding: 8px 16px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    z-index: 9999;
    clip: unset;
    overflow: visible;
}

/* Enhanced focus indicators for AI interface */
.ai-interface *:focus-visible {
    outline: 3px solid #2563eb;
    outline-offset: 2px;
    box-shadow: 0 0 0 2px white, 0 0 0 5px #2563eb;
}

/* Dark mode focus indicators */
.dark .ai-interface *:focus-visible {
    outline: 3px solid #60a5fa;
    box-shadow: 0 0 0 2px #1f2937, 0 0 0 5px #60a5fa;
}

/* Ensure interactive elements meet minimum touch target size */
.ai-interface button,
.ai-interface [role="button"],
.ai-interface [tabindex="0"] {
    min-height: 44px;
    min-width: 44px;
}

/* High contrast mode enhancements */
@media (prefers-contrast: high) {
    .ai-high-contrast .ai-interface * {
        border: 2px solid currentColor !important;
    }
    
    .ai-high-contrast .ai-interface button:focus,
    .ai-high-contrast .ai-interface [role="button"]:focus {
        background: #ffff00 !important;
        color: #000000 !important;
        border: 4px solid #000000 !important;
    }
}

/* Reduced motion alternatives */
@media (prefers-reduced-motion: reduce) {
    .ai-no-motion *,
    .ai-no-motion *::before,
    .ai-no-motion *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        transition-delay: 0s !important;
    }
}
</style>