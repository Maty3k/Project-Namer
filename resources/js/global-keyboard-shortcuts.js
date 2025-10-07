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
    { action: 'themeCustomizer', key: 't', modifiers: ['ctrl'], description: 'Theme customizer', route: '/themes' },
    { action: 'logoGallery', key: 'l', modifiers: ['ctrl'], description: 'Logo gallery', route: '/logos' },
    { action: 'keyboardShortcuts', key: 'k', modifiers: ['ctrl'], description: 'Keyboard shortcuts settings', route: '/settings/keyboard-shortcuts' },
    { action: 'help', key: 'h', modifiers: ['ctrl'], description: 'Show keyboard shortcuts', dispatchEvent: 'toggle-help-overlay' },
    { action: 'escape', key: 'Escape', modifiers: [], description: 'Close modals', dispatchEvent: 'close-modals' },
];

// Global state for disabled shortcuts
let disabledShortcuts = [];

/**
 * Fetch disabled shortcuts from the server
 */
async function fetchDisabledShortcuts() {
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
            const data = await response.json();
            disabledShortcuts = data.disabled_shortcuts || [];
            console.log('[Global Keyboard Shortcuts] Loaded disabled shortcuts:', disabledShortcuts);
        }
    } catch (error) {
        console.error('[Global Keyboard Shortcuts] Failed to fetch disabled shortcuts:', error);
    }
}

/**
 * Initialize global keyboard shortcuts
 */
function initGlobalKeyboardShortcuts() {
    console.log('[Global Keyboard Shortcuts] Initializing global keyboard handler');

    // Register global keydown listener
    window.addEventListener('keydown', handleGlobalKeydown);

    // Fetch disabled shortcuts from server on init
    fetchDisabledShortcuts();

    // Listen for shortcuts updates from Livewire components
    window.addEventListener('livewire:init', () => {
        Livewire.on('shortcuts-updated', () => {
            console.log('[Global Keyboard Shortcuts] Shortcuts updated, reloading from server');
            fetchDisabledShortcuts();
        });
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
