// Import performance monitoring
import './performance-monitor.js';

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