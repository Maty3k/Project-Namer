// Import performance monitoring
import './performance-monitor.js';

// Import global keyboard shortcuts (runs independently of Alpine)
import './global-keyboard-shortcuts.js';

// Import lazy load observer
import lazyLoadObserver from './components/lazyLoadObserver.js';

// Import keyboard shortcuts component (for help overlay UI)
import keyboardShortcuts from './components/keyboardShortcuts.js';

// Import optimistic UI
import optimisticUI from './components/optimisticUI.js';

// Import ripple effect
import rippleEffect from './components/rippleEffect.js';

// Function to register Alpine components
function registerAlpineComponents() {
    console.log('[Alpine] Attempting to register components...');
    console.log('[Alpine] window.Alpine exists:', !!window.Alpine);

    if (window.Alpine) {
        window.Alpine.data('lazyLoadObserver', lazyLoadObserver);
        window.Alpine.data('keyboardShortcuts', keyboardShortcuts);
        window.Alpine.data('optimisticUI', optimisticUI);
        window.Alpine.data('rippleEffect', rippleEffect);
        console.log('[Alpine] ✓ All components registered successfully');
        return true;
    } else {
        console.warn('[Alpine] window.Alpine not available yet, will retry...');
        return false;
    }
}

// Try to register on alpine:init event
document.addEventListener('alpine:init', () => {
    console.log('[Alpine] alpine:init event fired');
    registerAlpineComponents();
});

// Fallback: Try to register when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[Alpine] DOMContentLoaded - checking if components need registration');
        // Wait a bit for Alpine to load via @fluxScripts
        setTimeout(() => {
            registerAlpineComponents();
        }, 100);
    });
} else {
    // DOM is already loaded, try immediately and retry if needed
    console.log('[Alpine] DOM already loaded, attempting registration');
    if (!registerAlpineComponents()) {
        // Retry after a short delay
        setTimeout(registerAlpineComponents, 100);
    }
}

// Suppress non-critical ResizeObserver warnings in development
// These warnings occur during CSS animations and layout changes
const originalConsoleError = console.error;
console.error = function(...args) {
    // Suppress ResizeObserver loop completed warnings
    if (args[0] && args[0].toString().includes('ResizeObserver loop completed')) {
        return;
    }
    originalConsoleError.apply(console, args);
};

// Sidebar state management with localStorage
document.addEventListener('livewire:init', function () {
    const SIDEBAR_STATE_KEY = 'project-namer-sidebar-collapsed';

    // Listen for loadSidebarState event from Livewire
    Livewire.on('loadSidebarState', () => {
        const savedState = localStorage.getItem(SIDEBAR_STATE_KEY);
        if (savedState !== null) {
            const isCollapsed = savedState === 'true';
            
            // Find the SessionSidebar component and set its state
            const sidebarElements = document.querySelectorAll('[wire\\:id]');
            for (const element of sidebarElements) {
                const componentId = element.getAttribute('wire:id');
                const component = Livewire.find(componentId);
                
                if (component && component.name === 'session-sidebar') {
                    component.set('isCollapsed', isCollapsed);
                    break;
                }
            }
        }
    });

    // Listen for focus mode toggle events to save state
    Livewire.on('focusModeToggled', (event) => {
        localStorage.setItem(SIDEBAR_STATE_KEY, event.enabled.toString());
    });
});