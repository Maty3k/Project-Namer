/**
 * Keyboard Shortcuts Manager
 *
 * Handles global keyboard shortcuts for the application
 */

// Global singleton instance
let globalShortcutsInstance = null;

export default () => ({
    helpOverlayOpen: false,
    shortcuts: [],

    init() {
        // If this is the first instance, set it as the global singleton
        if (!globalShortcutsInstance) {
            globalShortcutsInstance = this;

            // Register keyboard event listener ONCE globally
            window.addEventListener('keydown', (e) => globalShortcutsInstance.handleKeydown(e));

            // Define available shortcuts with handlers bound to the global instance
            this.shortcuts = [
                { action: 'newProject', key: 'n', modifiers: ['ctrl'], description: 'New project', handler: () => globalShortcutsInstance.newProject() },
                { action: 'settings', key: 's', modifiers: ['ctrl'], description: 'Open settings', handler: () => globalShortcutsInstance.openSettings() },
                { action: 'themeCustomizer', key: 't', modifiers: ['ctrl'], description: 'Theme customizer', handler: () => globalShortcutsInstance.themeCustomizer() },
                { action: 'logoGallery', key: 'l', modifiers: ['ctrl'], description: 'Logo gallery', handler: () => globalShortcutsInstance.logoGallery() },
                { action: 'keyboardShortcuts', key: 'k', modifiers: ['ctrl'], description: 'Keyboard shortcuts settings', handler: () => globalShortcutsInstance.keyboardShortcuts() },
                { action: 'help', key: 'h', modifiers: ['ctrl'], description: 'Show keyboard shortcuts', handler: () => globalShortcutsInstance.toggleHelpOverlay() },
                { action: 'escape', key: 'Escape', modifiers: [], description: 'Close modals', handler: () => globalShortcutsInstance.closeModals() },
            ];
        } else {
            // For subsequent instances, sync state from the global instance
            setInterval(() => {
                if (globalShortcutsInstance) {
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
