/**
 * Keyboard Shortcuts Manager
 *
 * This Alpine component now only handles the UI for the help overlay.
 * The actual keyboard shortcuts are handled globally in global-keyboard-shortcuts.js
 */

export default () => ({
    helpOverlayOpen: false,
    commandPaletteOpen: false,
    shortcuts: [],

    init() {
        console.log('[Keyboard Shortcuts Component] Initializing UI component');

        // Get shortcuts from global handler
        if (window.globalKeyboardShortcuts) {
            this.shortcuts = window.globalKeyboardShortcuts.getShortcuts();
        }

        // Listen for global events from the keyboard handler
        window.addEventListener('toggle-help-overlay', () => {
            console.log('[Keyboard Shortcuts Component] Toggle help overlay event received');
            this.toggleHelpOverlay();
        });

        window.addEventListener('close-modals', () => {
            console.log('[Keyboard Shortcuts Component] Close modals event received');
            this.closeModals();
        });
    },

    toggleHelpOverlay() {
        this.helpOverlayOpen = !this.helpOverlayOpen;
    },

    closeHelpOverlay() {
        this.helpOverlayOpen = false;
    },

    closeModals() {
        this.helpOverlayOpen = false;
        this.commandPaletteOpen = false;
    },

    openCommandPalette() {
        this.commandPaletteOpen = true;
    },

    closeCommandPalette() {
        this.commandPaletteOpen = false;
    }
});
