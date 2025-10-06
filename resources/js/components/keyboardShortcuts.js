/**
 * Keyboard Shortcuts Manager
 *
 * Handles global keyboard shortcuts for the application
 */

export default () => ({
    commandPaletteOpen: false,
    helpOverlayOpen: false,
    shortcuts: [],

    init() {
        // Register keyboard event listener
        window.addEventListener('keydown', (e) => this.handleKeydown(e));

        // Define available shortcuts
        this.shortcuts = [
            { key: 'k', modifiers: ['cmd', 'ctrl'], description: 'Open command palette', action: () => this.openCommandPalette() },
            { key: 'n', modifiers: ['cmd', 'ctrl'], description: 'New project', action: () => this.newProject() },
            { key: 'g', modifiers: ['cmd', 'ctrl'], description: 'Generate names', action: () => this.generateNames() },
            { key: '?', modifiers: [], description: 'Show keyboard shortcuts', action: () => this.toggleHelpOverlay() },
            { key: 'Escape', modifiers: [], description: 'Close modals', action: () => this.closeModals() },
        ];
    },

    handleKeydown(event) {
        // Don't trigger shortcuts when typing in inputs
        const target = event.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable) {
            return;
        }

        const key = event.key.toLowerCase();
        const hasModifier = event.metaKey || event.ctrlKey;

        // Handle Cmd/Ctrl + K (Command Palette)
        if ((event.metaKey || event.ctrlKey) && key === 'k') {
            event.preventDefault();
            this.openCommandPalette();
            return;
        }

        // Handle Cmd/Ctrl + N (New Project)
        if ((event.metaKey || event.ctrlKey) && key === 'n') {
            event.preventDefault();
            this.newProject();
            return;
        }

        // Handle Cmd/Ctrl + G (Generate Names)
        if ((event.metaKey || event.ctrlKey) && key === 'g') {
            event.preventDefault();
            this.generateNames();
            return;
        }

        // Handle ? (Help Overlay)
        if (event.key === '?' && !hasModifier) {
            event.preventDefault();
            this.toggleHelpOverlay();
            return;
        }

        // Handle Escape (Close Modals)
        if (event.key === 'Escape') {
            this.closeModals();
            return;
        }
    },

    openCommandPalette() {
        this.commandPaletteOpen = true;
        this.$nextTick(() => {
            // Focus the command palette input
            const input = document.querySelector('[x-ref="commandPaletteInput"]');
            if (input) {
                input.focus();
            }
        });
    },

    closeCommandPalette() {
        this.commandPaletteOpen = false;
    },

    newProject() {
        // Trigger new project creation
        if (window.Livewire) {
            // Dispatch event that can be caught by Livewire components
            window.Livewire.dispatch('new-project-shortcut');
        } else {
            // Fallback to navigation
            window.location.href = '/dashboard';
        }
    },

    generateNames() {
        // Trigger name generation
        if (window.Livewire) {
            window.Livewire.dispatch('generate-names-shortcut');
        }
    },

    toggleHelpOverlay() {
        this.helpOverlayOpen = !this.helpOverlayOpen;
    },

    closeHelpOverlay() {
        this.helpOverlayOpen = false;
    },

    closeModals() {
        this.commandPaletteOpen = false;
        this.helpOverlayOpen = false;
    },

    executeCommand(command) {
        switch (command) {
            case 'new-project':
                this.newProject();
                break;
            case 'generate-names':
                this.generateNames();
                break;
            case 'dashboard':
                window.location.href = '/dashboard';
                break;
            case 'logos':
                window.location.href = '/logos';
                break;
            default:
                console.log('Unknown command:', command);
        }
        this.closeCommandPalette();
    }
});
