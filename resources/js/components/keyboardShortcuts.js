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
    userPreferences: null,
    shortcutsEnabled: true,

    async init() {
        // If this is the first instance, set it as the global singleton
        if (!globalShortcutsInstance) {
            globalShortcutsInstance = this;

            // Load user preferences from API
            await this.loadUserPreferences();

            // Register keyboard event listener ONCE globally
            window.addEventListener('keydown', (e) => globalShortcutsInstance.handleKeydown(e));

            // Listen for shortcuts-updated event from Livewire
            window.addEventListener('shortcuts-updated', () => globalShortcutsInstance.loadUserPreferences());

            // Define available shortcuts with handlers bound to the global instance
            this.shortcuts = [
                { action: 'newProject', key: 'n', modifiers: ['ctrl'], description: 'New project', handler: () => globalShortcutsInstance.newProject() },
                { action: 'settings', key: 's', modifiers: ['ctrl'], description: 'Open settings', handler: () => globalShortcutsInstance.openSettings() },
                { action: 'themeCustomizer', key: 't', modifiers: ['ctrl'], description: 'Theme customizer', handler: () => globalShortcutsInstance.themeCustomizer() },
                { action: 'logoGallery', key: 'l', modifiers: ['ctrl'], description: 'Logo gallery', handler: () => globalShortcutsInstance.logoGallery() },
                { action: 'help', key: 'h', modifiers: ['ctrl'], description: 'Show keyboard shortcuts', handler: () => globalShortcutsInstance.toggleHelpOverlay() },
                { action: 'escape', key: 'Escape', modifiers: [], description: 'Close modals', handler: () => globalShortcutsInstance.closeModals() },
            ];
        } else {
            // For subsequent instances, proxy state changes to/from the global instance
            // Use Alpine's $watch to keep instances in sync
            this.$watch('helpOverlayOpen', (value) => {
                if (globalShortcutsInstance) {
                    globalShortcutsInstance.helpOverlayOpen = value;
                }
            });

            // Watch the global instance and update local state
            setInterval(() => {
                if (globalShortcutsInstance) {
                    this.helpOverlayOpen = globalShortcutsInstance.helpOverlayOpen;
                    this.shortcuts = globalShortcutsInstance.shortcuts;
                    this.userPreferences = globalShortcutsInstance.userPreferences;
                    this.shortcutsEnabled = globalShortcutsInstance.shortcutsEnabled;
                }
            }, 50); // Check every 50ms for state changes
        }
    },

    async loadUserPreferences() {
        try {
            const response = await fetch('/api/keyboard-shortcuts', {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                this.userPreferences = data.shortcuts;
                this.shortcutsEnabled = data.enabled;
            }
        } catch (error) {
            console.error('Failed to load keyboard shortcuts preferences:', error);
            // Continue with defaults if API fails
        }
    },

    isShortcutEnabled(action) {
        // If shortcuts are globally disabled, return false
        if (!this.shortcutsEnabled) {
            return false;
        }

        // Check if user has specific preferences for this action
        if (this.userPreferences && this.userPreferences[action]) {
            return this.userPreferences[action].enabled;
        }

        // Default to enabled
        return true;
    },

    handleKeydown(event) {
        // Don't trigger shortcuts when typing in inputs
        const target = event.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable) {
            return;
        }

        console.log('Key pressed:', event.key, 'Ctrl:', event.ctrlKey, 'Meta:', event.metaKey);

        // Check each shortcut dynamically based on user preferences
        for (const shortcut of this.shortcuts) {
            if (!this.isShortcutEnabled(shortcut.action)) {
                continue;
            }

            // Get the actual key configuration from user preferences
            const config = this.userPreferences && this.userPreferences[shortcut.action]
                ? this.userPreferences[shortcut.action]
                : { key: shortcut.key, modifiers: shortcut.modifiers };

            // Check if the key matches
            const keyMatches = event.key.toLowerCase() === config.key.toLowerCase() ||
                              event.key === config.key;

            if (!keyMatches) {
                continue;
            }

            // Check modifiers
            const needsCtrl = config.modifiers.includes('ctrl');
            const needsAlt = config.modifiers.includes('alt');
            const needsShift = config.modifiers.includes('shift');

            const hasCtrl = event.ctrlKey || event.metaKey; // Support both Ctrl and Cmd
            const hasAlt = event.altKey;
            const hasShift = event.shiftKey;

            // All required modifiers must be present, and no extra modifiers
            if (needsCtrl === hasCtrl && needsAlt === hasAlt && needsShift === hasShift) {
                console.log('Keyboard shortcut triggered:', shortcut.action);
                event.preventDefault();
                shortcut.handler();
                return;
            }
        }
    },

    newProject() {
        // Navigate to dashboard to start a new project
        window.location.href = '/dashboard';
    },

    openSettings() {
        // Navigate to settings profile page
        window.location.href = '/settings/profile';
    },

    themeCustomizer() {
        // Navigate to theme customizer
        window.location.href = '/themes';
    },

    logoGallery() {
        // Navigate to logo gallery
        window.location.href = '/logos';
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
