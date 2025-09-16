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
     */
    public function toggleTheme(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $currentIsDarkMode = ThemeHelper::isDarkMode();
        $newIsDarkMode = ! $currentIsDarkMode;

        // Update User model and disable auto-switching
        $user->update([
            'prefers_dark_mode' => $newIsDarkMode,
            'theme_auto_switch' => false, // Disable auto-switching when manually toggling
        ]);

        // Update UserThemePreference if it exists
        $preference = UserThemePreference::where('user_id', $user->id)->first();
        if ($preference) {
            $preference->update([
                'is_dark_mode' => $newIsDarkMode,
                'background_color' => $newIsDarkMode ? '#1f2937' : '#ffffff',
                'text_color' => $newIsDarkMode ? '#f9fafb' : '#111827',
            ]);
        } else {
            // Create new preference
            UserThemePreference::create([
                'user_id' => $user->id,
                'theme_name' => $user->current_theme ?? 'default',
                'is_dark_mode' => $newIsDarkMode,
                'primary_color' => '#3b82f6',
                'accent_color' => '#10b981',
                'background_color' => $newIsDarkMode ? '#1f2937' : '#ffffff',
                'text_color' => $newIsDarkMode ? '#f9fafb' : '#111827',
            ]);
        }

        // Clear theme cache
        ThemeHelper::clearUserThemeCache();

        // Instantly apply dark mode to HTML element
        $isDarkModeJs = $newIsDarkMode ? 'true' : 'false';
        $this->js("
            const html = document.documentElement;
            const isDark = {$isDarkModeJs};

            console.log('THEME QUICK TOGGLE: Switching to', isDark ? 'DARK' : 'LIGHT');

            // Authorize this theme change with the protection system
            if (window.authorizeThemeChange) {
                window.authorizeThemeChange(isDark, 10000); // 10 second authorization for quick toggle
            }

            if (isDark) {
                html.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                html.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            }

            // Update the global current theme preference
            window.currentThemePreference = isDark;

            // Force a repaint to ensure styles update
            html.style.display = 'none';
            html.offsetHeight; // Trigger reflow
            html.style.display = '';

            // Also refresh any theme-dependent components
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark } }));
        ");

        // Dispatch events for UI updates and theme customizer synchronization
        $this->dispatch('theme-updated');
        $this->dispatch('theme-quick-toggle-changed', [
            'isDarkMode' => $newIsDarkMode,
            'backgroundColor' => $newIsDarkMode ? '#1f2937' : '#ffffff',
            'textColor' => $newIsDarkMode ? '#f9fafb' : '#111827',
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.theme-quick-toggle', [
            'isDarkMode' => ThemeHelper::isDarkMode(),
        ]);
    }
}
