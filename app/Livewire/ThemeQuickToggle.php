<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\ThemeHelper;
use App\Models\UserThemePreference;
use Livewire\Attributes\On;
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
            ]);
        } else {
            // Create new preference
            UserThemePreference::create([
                'user_id' => $user->id,
                'theme_name' => $user->current_theme ?? 'default',
                'is_dark_mode' => $newIsDarkMode,
            ]);
        }

        // Clear theme cache to ensure consistency
        ThemeHelper::clearUserThemeCache();

        // Clear Laravel cache to ensure all cached theme data is fresh
        try {
            if (function_exists('cache')) {
                cache()->forget("user_theme_{$user->id}");
                // Only use cache tags if the cache driver supports it
                if (method_exists(cache()->getStore(), 'tags')) {
                    cache()->tags(['user_themes', "user_{$user->id}"])->flush();
                }
            }
        } catch (\Exception $e) {
            // Silently continue if cache operations fail
            logger()->debug('Cache clearing failed: '.$e->getMessage());
        }

        // Get theme name for CSS file path
        $themeName = $preference ? $preference->theme_name : ($user->current_theme ?? 'default');
        $themeCssPath = "/css/themes/{$themeName}.css";

        // Instantly apply dark mode to HTML element
        $isDarkModeJs = $newIsDarkMode ? 'true' : 'false';
        $this->js("
            const html = document.documentElement;
            const isDark = {$isDarkModeJs};

            console.log('THEME QUICK TOGGLE: Switching to', isDark ? 'DARK' : 'LIGHT');

            // Authorize this theme change (bypass MutationObserver protection)
            window.__allowingThemeChange = true;
            console.log('✓ Theme change authorized for 2 seconds');

            // Update theme CSS link (ensure it's loaded)
            let themeLink = document.getElementById('theme-css-link');
            if (!themeLink) {
                themeLink = document.createElement('link');
                themeLink.id = 'theme-css-link';
                themeLink.rel = 'stylesheet';
                document.head.appendChild(themeLink);
            }
            themeLink.href = '{$themeCssPath}';

            // Toggle dark mode class
            if (isDark) {
                html.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                html.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            }

            // Update the global current theme preference
            window.currentThemePreference = isDark;

            // Also refresh any theme-dependent components
            window.dispatchEvent(new CustomEvent('theme-changed', {
                detail: {
                    isDark,
                    themeName: '{$themeName}',
                    themeCssPath: '{$themeCssPath}'
                }
            }));

            // Force global state synchronization
            setTimeout(() => {
                if (window.Livewire) {
                    window.Livewire.dispatch('refresh-theme-components');
                }
            }, 100);

            // Re-enable theme protection after change completes
            setTimeout(() => {
                window.__allowingThemeChange = false;
                console.log('✓ Theme protection re-enabled');
            }, 2000);
        ");

        // Dispatch events for UI updates and theme customizer synchronization
        $this->dispatch('theme-updated');
        $this->dispatch('theme-quick-toggle-changed', [
            'isDarkMode' => $newIsDarkMode,
            'themeName' => $themeName,
        ]);
    }

    /**
     * Listen for theme component refresh requests.
     */
    #[On('refresh-theme-components')]
    public function refreshThemeComponents(): void
    {
        // This will cause the component to re-render with updated theme state
        $this->render();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.theme-quick-toggle', [
            'isDarkMode' => ThemeHelper::isDarkMode(),
        ]);
    }
}
