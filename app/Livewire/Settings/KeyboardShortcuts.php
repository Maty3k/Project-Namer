<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\UserKeyboardShortcut;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class KeyboardShortcuts extends Component
{
    public bool $enabled = true;

    /** @var array<string, mixed> */
    public array $shortcuts = [];

    /** @var array<int, string> */
    public array $disabledShortcuts = [];

    public ?string $editingAction = null;

    public string $editingKey = '';

    /** @var array<string, bool> */
    public array $editingModifiers = [
        'cmd' => false,
        'alt' => false,
        'shift' => false,
    ];

    public bool $showEditModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());

        $this->enabled = $userShortcuts->enabled;
        $this->shortcuts = $userShortcuts->getMergedShortcuts();
        $this->disabledShortcuts = $userShortcuts->disabled_shortcuts ?? [];
    }

    /**
     * Livewire updater method called when enabled property changes.
     */
    public function updatedEnabled(): void
    {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());
        $userShortcuts->update(['enabled' => $this->enabled]);

        // Refresh shortcuts
        $this->shortcuts = $userShortcuts->getMergedShortcuts();

        $this->dispatch('shortcuts-updated');
        $this->dispatch('toast', message: 'Keyboard shortcuts '.($this->enabled ? 'enabled' : 'disabled'));
    }

    /**
     * Toggle global keyboard shortcuts on/off.
     */
    public function toggleGlobalShortcuts(): void
    {
        $this->enabled = ! $this->enabled;
        $this->updatedEnabled();
    }

    /**
     * Toggle a specific shortcut on/off.
     */
    public function toggleShortcut(string $action): void
    {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());
        $userShortcuts->toggleShortcut($action);

        // Refresh state
        $this->shortcuts = $userShortcuts->getMergedShortcuts();
        $this->disabledShortcuts = $userShortcuts->disabled_shortcuts ?? [];

        $this->dispatch('shortcuts-updated');
        $this->dispatch('toast', message: 'Shortcut updated');
    }

    /**
     * Reset a shortcut to default.
     */
    public function resetShortcut(string $action): void
    {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());
        $userShortcuts->resetShortcut($action);

        // Refresh state
        $this->shortcuts = $userShortcuts->getMergedShortcuts();

        $this->dispatch('shortcuts-updated');
        $this->dispatch('toast', message: 'Shortcut reset to default');
    }

    /**
     * Reset all shortcuts to defaults.
     */
    public function resetAllShortcuts(): void
    {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());
        $userShortcuts->resetAllShortcuts();

        // Refresh state
        $this->shortcuts = $userShortcuts->getMergedShortcuts();
        $this->disabledShortcuts = [];

        $this->dispatch('shortcuts-updated');
        $this->dispatch('toast', message: 'All shortcuts reset to defaults');
    }

    /**
     * Format key combination for display.
     */
    public function formatKeyCombo(string $action): string
    {
        $shortcut = $this->shortcuts[$action] ?? null;

        if (! $shortcut) {
            return '';
        }

        $parts = [];

        foreach ($shortcut['modifiers'] as $modifier) {
            if ($modifier === 'cmd') {
                $parts[] = '⌘';
            } elseif ($modifier === 'ctrl') {
                $parts[] = 'Ctrl';
            } elseif ($modifier === 'alt') {
                $parts[] = 'Alt';
            } elseif ($modifier === 'shift') {
                $parts[] = 'Shift';
            }
        }

        $parts[] = strtoupper($shortcut['key']);

        return implode(' + ', $parts);
    }

    /**
     * Open edit modal for a shortcut.
     */
    public function openEditModal(string $action): void
    {
        $this->editingAction = $action;
        $shortcut = $this->shortcuts[$action] ?? null;

        if ($shortcut) {
            $this->editingKey = $shortcut['key'];
            $this->editingModifiers = [
                'cmd' => in_array('ctrl', $shortcut['modifiers']),
                'alt' => in_array('alt', $shortcut['modifiers']),
                'shift' => in_array('shift', $shortcut['modifiers']),
            ];
        }

        $this->showEditModal = true;
    }

    /**
     * Close edit modal.
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingAction = null;
        $this->editingKey = '';
        $this->editingModifiers = [
            'cmd' => false,
            'alt' => false,
            'shift' => false,
        ];
    }

    /**
     * Capture key press from input.
     */
    public function captureKey(string $key): void
    {
        // Normalize the key
        $key = strtolower($key);

        // Handle special keys
        if ($key === 'escape') {
            $this->editingKey = 'Escape';
        } else {
            $this->editingKey = $key;
        }
    }

    /**
     * Get preview of the key combination.
     */
    public function getPreviewKeyCombo(): string
    {
        $parts = [];

        if ($this->editingModifiers['cmd']) {
            $parts[] = 'Ctrl';
        }

        if ($this->editingModifiers['alt']) {
            $parts[] = 'Alt';
        }

        if ($this->editingModifiers['shift']) {
            $parts[] = 'Shift';
        }

        if ($this->editingKey) {
            $parts[] = strtoupper($this->editingKey);
        }

        return implode(' + ', $parts);
    }

    /**
     * Save the edited shortcut.
     */
    public function saveShortcut(): void
    {
        if (! $this->editingAction || ! $this->editingKey) {
            return;
        }

        $modifiers = [];
        if ($this->editingModifiers['cmd']) {
            $modifiers[] = 'ctrl';
        }

        if ($this->editingModifiers['alt']) {
            $modifiers[] = 'alt';
        }

        if ($this->editingModifiers['shift']) {
            $modifiers[] = 'shift';
        }

        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());
        $userShortcuts->updateShortcut($this->editingAction, [
            'key' => $this->editingKey,
            'modifiers' => $modifiers,
        ]);

        // Refresh state
        $this->shortcuts = $userShortcuts->getMergedShortcuts();

        $this->dispatch('shortcuts-updated');
        $this->dispatch('toast', message: 'Shortcut updated successfully');

        $this->closeEditModal();
    }

    /**
     * Reset the currently editing shortcut to default.
     */
    public function resetEditingShortcut(): void
    {
        if (! $this->editingAction) {
            return;
        }

        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());
        $userShortcuts->resetShortcut($this->editingAction);

        // Refresh state
        $this->shortcuts = $userShortcuts->getMergedShortcuts();

        $this->dispatch('shortcuts-updated');
        $this->dispatch('toast', message: 'Shortcut reset to default');

        $this->closeEditModal();
    }
}
