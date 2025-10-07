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
    preferencesLoaded: false,

    async init() {
        // If this is the first instance, set it as the global singleton
        if (!globalShortcutsInstance) {
            globalShortcutsInstance = this;

            // Load user preferences from API
            await this.loadUserPreferences();

            // Register keyboard event listener ONCE globally
            window.addEventListener('keydown', (e) => globalShortcutsInstance.handleKeydown(e));

            // Listen for shortcuts-updated event from Livewire
            window.addEventListener('shortcuts-updated', () => {
                console.log('[Keyboard Shortcuts] shortcuts-updated event received, reloading preferences...');
                globalShortcutsInstance.loadUserPreferences();
            });

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
                    this.preferencesLoaded = globalShortcutsInstance.preferencesLoaded;
                }
            }, 50); // Check every 50ms for state changes
        }
    },

    async loadUserPreferences() {
        try {
            console.log('[Keyboard Shortcuts] Loading user preferences from API...');
            this.preferencesLoaded = false; // Mark as loading

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
                this.preferencesLoaded = true; // Mark as loaded
                console.log('[Keyboard Shortcuts] Preferences loaded:', {
                    enabled: this.shortcutsEnabled,
                    shortcuts: this.userPreferences
                });
            } else {
                this.preferencesLoaded = true; // Even if failed, mark as loaded to use defaults
            }
        } catch (error) {
            console.error('Failed to load keyboard shortcuts preferences:', error);
            this.preferencesLoaded = true; // Mark as loaded to continue with defaults
        }
    },

    isShortcutEnabled(action) {
        // If shortcuts are globally disabled, return false
        if (!this.shortcutsEnabled) {
            console.log(`[Keyboard Shortcuts] ${action} disabled - global shortcuts off`);
            return false;
        }

        // Check if user has specific preferences for this action
        if (this.userPreferences && this.userPreferences[action]) {
            const enabled = this.userPreferences[action].enabled;
            console.log(`[Keyboard Shortcuts] ${action} enabled: ${enabled}`);
            return enabled;
        }

        // Default to enabled
        console.log(`[Keyboard Shortcuts] ${action} enabled by default`);
        return true;
    },

    handleKeydown(event) {
        // Don't trigger shortcuts when typing in inputs
        const target = event.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable) {
            return;
        }

        // Don't process shortcuts while preferences are loading
        if (!this.preferencesLoaded) {
            console.log('[Keyboard Shortcuts] Preferences not loaded yet, ignoring key press');
            return;
        }

        console.log('[Keyboard Shortcuts] Key pressed:', event.key, 'Ctrl:', event.ctrlKey, 'Meta:', event.metaKey);
        console.log('[Keyboard Shortcuts] Global enabled state:', this.shortcutsEnabled);

        // Check each shortcut dynamically based on user preferences
        for (const shortcut of this.shortcuts) {
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
                console.log(`[Keyboard Shortcuts] Matched shortcut: ${shortcut.action}`);

                // NOW check if shortcut is enabled AFTER we know it matched
                if (!this.isShortcutEnabled(shortcut.action)) {
                    console.log(`[Keyboard Shortcuts] Shortcut ${shortcut.action} is disabled, not executing`);
                    event.preventDefault(); // Still prevent default behavior
                    return;
                }

                console.log(`[Keyboard Shortcuts] Executing shortcut: ${shortcut.action}`);
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
