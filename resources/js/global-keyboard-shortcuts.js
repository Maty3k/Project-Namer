/**
 * Global Keyboard Shortcuts Handler
 *
 * This runs independently of Alpine.js components and persists across page navigations.
 * It handles all keyboard shortcuts globally and dispatches events that components can listen to.
 */

// Global shortcuts configuration
const SHORTCUTS = [
    { action: 'newProject', key: 'n', modifiers: ['ctrl'], description: 'New project', route: '/dashboard' },
    { action: 'settings', key: 's', modifiers: ['ctrl'], description: 'Open settings', route: '/settings/profile' },
    { action: 'appearance', key: 't', modifiers: ['ctrl'], description: 'Appearance settings', route: '/settings/appearance' },
    { action: 'logoGallery', key: 'l', modifiers: ['ctrl'], description: 'Logo gallery', route: '/logos' },
    { action: 'keyboardShortcuts', key: 'k', modifiers: ['ctrl'], description: 'Keyboard shortcuts settings', route: '/settings/keyboard-shortcuts' },
    { action: 'help', key: 'h', modifiers: ['ctrl'], description: 'Show keyboard shortcuts', dispatchEvent: 'toggle-help-overlay' },
    { action: 'escape', key: 'Escape', modifiers: [], description: 'Close modals', dispatchEvent: 'close-modals' },
];

// Global state for disabled shortcuts
let disabledShortcuts = [];

/**
 * Update disabled shortcuts from injected window variable or API
 */
function updateDisabledShortcuts() {
    if (window.__disabledShortcuts) {
        disabledShortcuts = window.__disabledShortcuts;
        console.log('[Global Keyboard Shortcuts] Using injected disabled shortcuts:', disabledShortcuts);
    } else {
        disabledShortcuts = [];
        console.log('[Global Keyboard Shortcuts] No disabled shortcuts found');
    }
}

/**
 * Initialize global keyboard shortcuts
 */
function initGlobalKeyboardShortcuts() {
    console.log('[Global Keyboard Shortcuts] Initializing global keyboard handler');

    // Register global keydown listener
    window.addEventListener('keydown', handleGlobalKeydown);

    // Load disabled shortcuts from injected data
    updateDisabledShortcuts();

    // Setup Livewire event listener
    const setupLivewireListener = () => {
        if (typeof Livewire !== 'undefined') {
            console.log('[Global Keyboard Shortcuts] Livewire available, setting up event listener');

            Livewire.on('shortcuts-updated', async (data) => {
                console.log('[Global Keyboard Shortcuts] Shortcuts updated event received, fetching latest state');

                // Fetch fresh data from API to get updated disabled shortcuts
                try {
                    const response = await fetch('/api/keyboard-shortcuts', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (response.ok) {
                        const apiData = await response.json();
                        disabledShortcuts = apiData.disabled_shortcuts || [];
                        window.__disabledShortcuts = disabledShortcuts; // Update window variable
                        console.log('[Global Keyboard Shortcuts] Updated disabled shortcuts from API:', disabledShortcuts);
                    }
                } catch (error) {
                    console.error('[Global Keyboard Shortcuts] Failed to fetch disabled shortcuts:', error);
                }
            });
        } else {
            console.log('[Global Keyboard Shortcuts] Livewire not ready yet, will retry');
        }
    };

    // Try to setup listener immediately if Livewire is ready
    setupLivewireListener();

    // Also listen for livewire:init event as fallback
    document.addEventListener('livewire:init', () => {
        console.log('[Global Keyboard Shortcuts] Livewire initialized event fired');
        setupLivewireListener();
    });

    console.log('[Global Keyboard Shortcuts] Global keyboard handler registered');
}

/**
 * Handle global keydown events
 */
function handleGlobalKeydown(event) {
    // Don't trigger shortcuts when typing in inputs
    const target = event.target;
    if (target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.tagName === 'SELECT' ||
        target.isContentEditable) {
        return;
    }

    // Check each shortcut
    for (const shortcut of SHORTCUTS) {
        // Check if the key matches first (optimization)
        const keyMatches = event.key.toLowerCase() === shortcut.key.toLowerCase() ||
                          event.key === shortcut.key;

        if (!keyMatches) {
            continue;
        }

        // Check modifiers
        const needsCtrl = shortcut.modifiers.includes('ctrl');
        const needsAlt = shortcut.modifiers.includes('alt');
        const needsShift = shortcut.modifiers.includes('shift');

        const hasCtrl = event.ctrlKey || event.metaKey;
        const hasAlt = event.altKey;
        const hasShift = event.shiftKey;

        // All required modifiers must be present, and no extra modifiers
        if (needsCtrl === hasCtrl && needsAlt === hasAlt && needsShift === hasShift) {
            // NOW check if shortcut is disabled (after confirming key+modifiers match)
            if (disabledShortcuts.includes(shortcut.action)) {
                console.log(`[Global Keyboard Shortcuts] Shortcut "${shortcut.action}" is disabled - ignoring`);
                event.preventDefault(); // Still prevent default browser behavior
                return;
            }

            event.preventDefault();

            console.log(`[Global Keyboard Shortcuts] Shortcut triggered: ${shortcut.action}`);

            // Handle route navigation
            if (shortcut.route) {
                window.location.href = shortcut.route;
                return;
            }

            // Handle event dispatch (for modals, overlays, etc.)
            if (shortcut.dispatchEvent) {
                window.dispatchEvent(new CustomEvent(shortcut.dispatchEvent, {
                    detail: { action: shortcut.action }
                }));
                return;
            }
        }
    }
}

/**
 * Get all shortcuts (for help overlay)
 */
function getShortcuts() {
    return SHORTCUTS.filter(s => !disabledShortcuts.includes(s.action));
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGlobalKeyboardShortcuts);
} else {
    initGlobalKeyboardShortcuts();
}

// Export for use in Alpine components if needed
window.globalKeyboardShortcuts = {
    getShortcuts,
};
