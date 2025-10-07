/**
 * Keyboard Shortcuts Manager
 *
 * Handles global keyboard shortcuts for the application
 */

// Global singleton instance and event listener tracking
let globalShortcutsInstance = null;
let globalEventListenerRegistered = false;

export default () => ({
    helpOverlayOpen: false,
    shortcuts: [],

    init() {
        console.log('[Keyboard Shortcuts] Initializing component');

        // Define shortcuts for this instance
        this.shortcuts = [
            { action: 'newProject', key: 'n', modifiers: ['ctrl'], description: 'New project', handler: () => this.newProject() },
            { action: 'settings', key: 's', modifiers: ['ctrl'], description: 'Open settings', handler: () => this.openSettings() },
            { action: 'themeCustomizer', key: 't', modifiers: ['ctrl'], description: 'Theme customizer', handler: () => this.themeCustomizer() },
            { action: 'logoGallery', key: 'l', modifiers: ['ctrl'], description: 'Logo gallery', handler: () => this.logoGallery() },
            { action: 'keyboardShortcuts', key: 'k', modifiers: ['ctrl'], description: 'Keyboard shortcuts settings', handler: () => this.keyboardShortcuts() },
            { action: 'help', key: 'h', modifiers: ['ctrl'], description: 'Show keyboard shortcuts', handler: () => this.toggleHelpOverlay() },
            { action: 'escape', key: 'Escape', modifiers: [], description: 'Close modals', handler: () => this.closeModals() },
        ];

        // If this is the first instance OR if page was reloaded, set it as the global singleton
        if (!globalShortcutsInstance || !document.body.contains(globalShortcutsInstance.$el)) {
            console.log('[Keyboard Shortcuts] Setting up global instance');
            globalShortcutsInstance = this;
            globalEventListenerRegistered = false; // Reset listener flag on new instance
        }

        // Register keyboard event listener ONCE globally
        if (!globalEventListenerRegistered) {
            console.log('[Keyboard Shortcuts] Registering global event listener');
            window.addEventListener('keydown', (e) => {
                if (globalShortcutsInstance) {
                    globalShortcutsInstance.handleKeydown(e);
                }
            });
            globalEventListenerRegistered = true;
        }

        // If this is not the primary instance, sync state from the global instance
        if (globalShortcutsInstance !== this) {
            console.log('[Keyboard Shortcuts] Secondary instance - syncing with primary');
            setInterval(() => {
                if (globalShortcutsInstance && globalShortcutsInstance !== this) {
                    this.helpOverlayOpen = globalShortcutsInstance.helpOverlayOpen;
                }
            }, 50);
        }
    },

    handleKeydown(event) {
        // Don't trigger shortcuts when typing in inputs
        const target = event.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable) {
            return;
        }

        // Check each shortcut
        for (const shortcut of this.shortcuts) {
            // Check if the key matches
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
                event.preventDefault();
                shortcut.handler();
                return;
            }
        }
    },

    newProject() {
        window.location.href = '/dashboard';
    },

    openSettings() {
        window.location.href = '/settings/profile';
    },

    themeCustomizer() {
        window.location.href = '/themes';
    },

    logoGallery() {
        window.location.href = '/logos';
    },

    keyboardShortcuts() {
        window.location.href = '/settings/keyboard-shortcuts';
    },

    toggleHelpOverlay() {
        this.helpOverlayOpen = !this.helpOverlayOpen;
    },

    closeHelpOverlay() {
        this.helpOverlayOpen = false;
    },

    closeModals() {
        this.helpOverlayOpen = false;
    }
});
