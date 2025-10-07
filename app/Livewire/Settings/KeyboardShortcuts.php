<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\UserKeyboardShortcut;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class KeyboardShortcuts extends Component
{
    /** @var array<string, mixed> */
    public array $shortcuts = [];

    /** @var array<int, string> */
    public array $disabledShortcuts = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());

        $this->shortcuts = $userShortcuts->getMergedShortcuts();
        $this->disabledShortcuts = $userShortcuts->disabled_shortcuts ?? [];
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

}
