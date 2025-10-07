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

// Register Alpine components
document.addEventListener('alpine:init', () => {
    Alpine.data('lazyLoadObserver', lazyLoadObserver);
    Alpine.data('keyboardShortcuts', keyboardShortcuts);
    Alpine.data('optimisticUI', optimisticUI);
    Alpine.data('rippleEffect', rippleEffect);
});

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