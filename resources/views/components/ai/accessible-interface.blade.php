@props([
    'title' => 'AI Name Generator',
    'description' => 'Generate creative business names using artificial intelligence',
    'skipLinks' => [],
])

@php
    $accessibilityService = app(\App\Services\AI\AIAccessibilityService::class);
    
    // Default skip links if none provided
    $defaultSkipLinks = [
        'main-content' => 'main content',
        'ai-input' => 'name generator input',
        'ai-results' => 'generated results',
    ];
    
    $allSkipLinks = array_merge($defaultSkipLinks, $skipLinks);
    $skipLinkList = $accessibilityService->generateSkipLinks($allSkipLinks);
@endphp

<div class="ai-interface min-h-screen bg-gray-50 dark:bg-gray-900" role="application" aria-label="{{ $title }}">
    <!-- Skip Links for Keyboard Navigation -->
    <div class="sr-only" id="skip-links">
        @foreach($skipLinkList as $link)
            <a href="{{ $link['href'] }}" class="{{ $link['class'] }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
    
    <!-- Screen Reader Announcements -->
    <div aria-live="polite" aria-atomic="true" class="sr-only" id="ai-announcements"></div>
    <div aria-live="assertive" aria-atomic="true" class="sr-only" id="ai-alerts"></div>
    
    <!-- Main Application Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white" id="app-title">
                        {{ $title }}
                    </h1>
                    @if($description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" id="app-description">
                            {{ $description }}
                        </p>
                    @endif
                </div>
                
                <!-- Accessibility Controls -->
                <div class="flex items-center space-x-3">
                    <!-- High Contrast Toggle -->
                    <button 
                        type="button"
                        class="ai-accessibility-toggle p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onclick="toggleHighContrast()"
                        aria-label="Toggle high contrast mode"
                        title="Toggle high contrast mode">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                    
                    <!-- Font Size Controls -->
                    <div class="flex items-center space-x-1 border-l border-gray-200 dark:border-gray-700 pl-3">
                        <button 
                            type="button"
                            class="ai-font-size-btn p-1 rounded text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700"
                            onclick="decreaseFontSize()"
                            aria-label="Decrease font size"
                            title="Decrease font size">
                            A-
                        </button>
                        <button 
                            type="button"
                            class="ai-font-size-btn p-1 rounded text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700"
                            onclick="resetFontSize()"
                            aria-label="Reset font size to default"
                            title="Reset font size">
                            A
                        </button>
                        <button 
                            type="button"
                            class="ai-font-size-btn p-1 rounded text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700"
                            onclick="increaseFontSize()"
                            aria-label="Increase font size"
                            title="Increase font size">
                            A+
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content Area -->
    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" role="main">
        {{ $slot }}
    </main>
    
    <!-- Keyboard Navigation Help -->
    <div id="keyboard-help-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50" role="dialog" aria-modal="true" aria-labelledby="keyboard-help-title">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 id="keyboard-help-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                        Keyboard Navigation Help
                    </h2>
                    <button 
                        type="button"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        onclick="hideKeyboardHelp()"
                        aria-label="Close help dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <div>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Tab</kbd>
                        <span class="ml-2">Navigate between interactive elements</span>
                    </div>
                    <div>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Shift+Tab</kbd>
                        <span class="ml-2">Navigate backwards</span>
                    </div>
                    <div>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Enter/Space</kbd>
                        <span class="ml-2">Activate buttons and select options</span>
                    </div>
                    <div>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Arrow Keys</kbd>
                        <span class="ml-2">Navigate within groups (model selection, results)</span>
                    </div>
                    <div>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Escape</kbd>
                        <span class="ml-2">Close dialogs or return to previous context</span>
                    </div>
                    <div>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">?</kbd>
                        <span class="ml-2">Show this help dialog</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Accessibility Enhancement Functions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize accessibility features
    initializeAccessibilityFeatures();
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Show keyboard help with '?' key
        if (e.key === '?' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            showKeyboardHelp();
        }
        
        // Close modals with Escape
        if (e.key === 'Escape') {
            hideKeyboardHelp();
        }
    });
});

function initializeAccessibilityFeatures() {
    // Initialize font size from user preference
    const savedFontSize = localStorage.getItem('ai-font-size');
    if (savedFontSize) {
        document.documentElement.style.fontSize = savedFontSize + 'px';
    }
    
    // Initialize high contrast mode
    const highContrast = localStorage.getItem('ai-high-contrast') === 'true';
    if (highContrast) {
        document.documentElement.classList.add('ai-high-contrast');
    }
    
    // Initialize focus management
    setupFocusManagement();
}

function toggleHighContrast() {
    const isHighContrast = document.documentElement.classList.toggle('ai-high-contrast');
    localStorage.setItem('ai-high-contrast', isHighContrast);
    
    // Announce the change
    announceToScreenReader(
        isHighContrast ? 'High contrast mode enabled' : 'High contrast mode disabled',
        'polite'
    );
}

function increaseFontSize() {
    const currentSize = parseInt(getComputedStyle(document.documentElement).fontSize);
    const newSize = Math.min(currentSize + 2, 24);
    document.documentElement.style.fontSize = newSize + 'px';
    localStorage.setItem('ai-font-size', newSize);
    
    announceToScreenReader(`Font size increased to ${newSize} pixels`, 'polite');
}

function decreaseFontSize() {
    const currentSize = parseInt(getComputedStyle(document.documentElement).fontSize);
    const newSize = Math.max(currentSize - 2, 12);
    document.documentElement.style.fontSize = newSize + 'px';
    localStorage.setItem('ai-font-size', newSize);
    
    announceToScreenReader(`Font size decreased to ${newSize} pixels`, 'polite');
}

function resetFontSize() {
    document.documentElement.style.fontSize = '16px';
    localStorage.setItem('ai-font-size', 16);
    announceToScreenReader('Font size reset to default', 'polite');
}

function showKeyboardHelp() {
    const modal = document.getElementById('keyboard-help-modal');
    modal.classList.remove('hidden');
    modal.focus();
}

function hideKeyboardHelp() {
    const modal = document.getElementById('keyboard-help-modal');
    modal.classList.add('hidden');
}

function setupFocusManagement() {
    // Ensure focus is visible for keyboard users
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    // Remove keyboard navigation class on mouse use
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-navigation');
    });
    
    // Focus management for dynamic content
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === Node.ELEMENT_NODE && node.hasAttribute('data-auto-focus')) {
                        setTimeout(() => node.focus(), 100);
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
}

function announceToScreenReader(message, priority = 'polite') {
    const announcer = priority === 'assertive' 
        ? document.getElementById('ai-alerts')
        : document.getElementById('ai-announcements');
    
    if (announcer) {
        announcer.textContent = message;
        
        // Clear after a delay to allow for new announcements
        setTimeout(() => {
            announcer.textContent = '';
        }, 1000);
    }
}

// Export functions for use in Livewire components
window.aiAccessibility = {
    announce: announceToScreenReader,
    showKeyboardHelp: showKeyboardHelp,
    hideKeyboardHelp: hideKeyboardHelp,
    toggleHighContrast: toggleHighContrast,
    increaseFontSize: increaseFontSize,
    decreaseFontSize: decreaseFontSize,
    resetFontSize: resetFontSize,
};
</script>

<style>
/* Additional accessibility styles */
.keyboard-navigation *:focus-visible {
    outline: 3px solid #2563eb !important;
    outline-offset: 2px !important;
}

.ai-high-contrast {
    filter: contrast(150%) brightness(150%);
}

.ai-high-contrast .bg-white {
    background-color: #ffffff !important;
}

.ai-high-contrast .bg-gray-50 {
    background-color: #f8f9fa !important;
}

.ai-high-contrast .text-gray-900 {
    color: #000000 !important;
}

.ai-high-contrast button {
    border: 2px solid currentColor !important;
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>