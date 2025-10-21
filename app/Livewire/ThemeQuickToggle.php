<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\ThemeHelper;
use App\Models\UserThemePreference;
use Livewire\Component;

final class ThemeQuickToggle extends Component
{
    /**
     * Toggle between dark and light mode.
     * Database-only update with server-side rendering via Livewire refresh.
     */
    public function toggleTheme(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        // Clear cache first to ensure we get fresh data
        ThemeHelper::clearUserThemeCache();

        $currentIsDarkMode = ThemeHelper::isDarkMode();
        $newIsDarkMode = ! $currentIsDarkMode;

        // Update User model and disable auto-switching
        $user->update([
            'prefers_dark_mode' => $newIsDarkMode,
            'theme_auto_switch' => false, // Disable auto-switching when manually toggling
        ]);

        // Update UserThemePreference if it exists, create if not
        $preference = UserThemePreference::where('user_id', $user->id)->first();
        if ($preference) {
            $preference->update([
                'is_dark_mode' => $newIsDarkMode,
            ]);
            $preference->refresh(); // Reload to get updated values
        } else {
            // Create new preference with current theme
            $preference = UserThemePreference::create([
                'user_id' => $user->id,
                'theme_name' => $user->current_theme ?? 'default',
                'is_dark_mode' => $newIsDarkMode,
            ]);
        }

        // Clear theme cache to ensure next request gets fresh data
        ThemeHelper::clearUserThemeCache();

        // Get theme name for event dispatch
        $themeName = $preference->theme_name;

        // Dispatch events for UI updates
        $this->dispatch('theme-updated');
        $this->dispatch('theme-quick-toggle-changed', [
            'isDarkMode' => $newIsDarkMode,
            'themeName' => $themeName,
        ]);

        // Reload the page to apply theme server-side (prevents flash)
        $this->dispatch('$refresh');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.theme-quick-toggle', [
            'isDarkMode' => ThemeHelper::isDarkMode(),
        ]);
    }
}
