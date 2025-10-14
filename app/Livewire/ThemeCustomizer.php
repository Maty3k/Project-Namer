<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\ThemeHelper;
use App\Models\UserThemePreference;
use App\Services\ThemeService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;

final class ThemeCustomizer extends Component
{
    #[Rule('required|string|max:50')]
    public string $themeName = 'default';

    #[Rule('boolean')]
    public bool $isDarkMode = false;

    public string $selectedCategory = 'all';

    /** @var array<string, mixed>|null */
    public ?array $recommendedSeasonalTheme = null;

    /**
     * Initialize component with user's current theme.
     */
    public function mount(): void
    {
        $user = auth()->user();

        if ($user) {
            $preference = UserThemePreference::where('user_id', $user->id)->first();

            if ($preference) {
                $this->themeName = $preference->theme_name;
                $this->isDarkMode = $preference->is_dark_mode;
            } else {
                // Fall back to User model theme settings if no preferences exist
                $this->isDarkMode = $user->prefers_dark_mode ?? false;
                $this->themeName = $user->current_theme ?? 'default';
            }
        }

        $this->loadSeasonalRecommendation();
    }

    /**
     * Apply predefined theme.
     */
    public function applyPreset(string $themeName): void
    {
        $themeService = app(ThemeService::class);
        $themes = $themeService->getPredefinedThemes();

        $theme = collect($themes)->firstWhere('name', $themeName);

        if ($theme && is_array($theme)) {
            $this->themeName = is_string($theme['name'] ?? null) ? $theme['name'] : 'default';
            $this->isDarkMode = is_bool($theme['is_dark_mode'] ?? null) ? $theme['is_dark_mode'] : false;

            // Automatically save the preset theme to persist it
            $this->applyTheme();
        }
    }

    /**
     * Apply and save current theme preferences.
     */
    public function applyTheme(): void
    {
        try {
            $this->validate();

            $user = auth()->user();

            if (! $user) {
                $this->dispatch('theme-error', 'You must be logged in to apply themes');

                return;
            }

            // Update UserThemePreference model
            UserThemePreference::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'theme_name' => $this->themeName,
                    'is_dark_mode' => $this->isDarkMode,
                ]
            );

            // Synchronize with User model theme fields and disable auto-switching
            $user->update([
                'current_theme' => $this->themeName,
                'prefers_dark_mode' => $this->isDarkMode,
                'theme_auto_switch' => false, // Disable auto-switching when manually setting theme
            ]);

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

            // Get theme CSS path for loading
            $themeCssPath = "/css/themes/{$this->themeName}.css";

            // Dispatch events for UI updates
            $this->dispatch('theme-saved');
            $this->dispatch('theme-updated');
            $this->dispatch('theme-applied', [
                'themeName' => $this->themeName,
                'isDarkMode' => $this->isDarkMode,
                'themeCssPath' => $themeCssPath,
            ]);

            // Force refresh of all theme-dependent Livewire components
            $this->dispatch('refresh-theme-components');

            // Instantly apply theme changes
            $isDarkModeJs = $this->isDarkMode ? 'true' : 'false';
            $this->js("
                const html = document.documentElement;
                const isDark = {$isDarkModeJs};

                console.log('THEME CUSTOMIZER: Applying theme', '{$this->themeName}', isDark ? 'DARK' : 'LIGHT');

                // Authorize this theme change with the protection system
                if (window.authorizeThemeChange) {
                    window.authorizeThemeChange(isDark, 15000); // 15 second authorization
                }

                // Update theme CSS link
                let themeLink = document.getElementById('theme-css-link');
                if (!themeLink) {
                    themeLink = document.createElement('link');
                    themeLink.id = 'theme-css-link';
                    themeLink.rel = 'stylesheet';
                    document.head.appendChild(themeLink);
                }
                themeLink.href = '{$themeCssPath}';

                // Apply dark mode class and localStorage sync
                if (isDark) {
                    html.classList.add('dark');
                    localStorage.setItem('darkMode', 'true');
                } else {
                    html.classList.remove('dark');
                    localStorage.setItem('darkMode', 'false');
                }

                // Update the global current theme preference
                window.currentThemePreference = isDark;

                // Dispatch global theme change event
                window.dispatchEvent(new CustomEvent('theme-changed', {
                    detail: {
                        themeName: '{$this->themeName}',
                        isDark: isDark,
                        themeCssPath: '{$themeCssPath}'
                    }
                }));

                // Update any theme toggle buttons in the UI
                window.dispatchEvent(new CustomEvent('theme-customizer-updated', {
                    detail: { isDarkMode: isDark, themeName: '{$this->themeName}' }
                }));

                // Force global state synchronization
                setTimeout(() => {
                    if (window.Livewire) {
                        window.Livewire.dispatch('refresh-theme-components');
                    }
                }, 500);
            ");

        } catch (\Exception $e) {
            logger()->error('Theme application failed: '.$e->getMessage());
            $this->dispatch('theme-error', 'Failed to apply theme preferences');
        }
    }

    /**
     * Save current theme preferences.
     */
    public function save(): void
    {
        // Redirect to the unified apply method
        $this->applyTheme();
    }

    /**
     * Reset theme to default values.
     */
    public function resetToDefault(): void
    {
        $this->themeName = 'default';
        $this->isDarkMode = false;

        $this->applyTheme();
        $this->dispatch('theme-updated');
    }

    /**
     * Toggle dark mode and apply immediately.
     */
    public function toggleDarkMode(): void
    {
        $this->isDarkMode = ! $this->isDarkMode;

        // Auto-save the theme with the new dark mode setting
        $this->applyTheme();
    }

    /**
     * Listen for theme changes from ThemeQuickToggle.
     *
     * @param  array{isDarkMode: bool, themeName?: string}  $data
     */
    #[On('theme-quick-toggle-changed')]
    public function onThemeQuickToggleChanged(array $data): void
    {
        // Update the theme customizer properties to match the quick toggle
        if (isset($data['isDarkMode'])) {
            $this->isDarkMode = (bool) $data['isDarkMode'];
        }
        if (isset($data['themeName'])) {
            $this->themeName = (string) $data['themeName'];
        }

        $this->dispatch('theme-updated');
    }

    /**
     * Change theme category filter.
     */
    public function changeCategory(string $category): void
    {
        $this->selectedCategory = $category;
    }

    /**
     * Load seasonal theme recommendation.
     */
    protected function loadSeasonalRecommendation(): void
    {
        $themeService = app(ThemeService::class);
        $this->recommendedSeasonalTheme = $themeService->getCurrentSeasonalTheme();
    }

    /**
     * Apply the recommended seasonal theme.
     */
    public function applySeasonalRecommendation(): void
    {
        if ($this->recommendedSeasonalTheme && is_array($this->recommendedSeasonalTheme)) {
            $themeName = $this->recommendedSeasonalTheme['name'] ?? null;
            if (is_string($themeName)) {
                $this->applyPreset($themeName);
            }
        }
    }

    /**
     * Get predefined themes for display.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function predefinedThemes(): array
    {
        $themeService = app(ThemeService::class);

        return $themeService->getThemesByCategory($this->selectedCategory);
    }

    /**
     * Get available theme categories.
     *
     * @return list<string>
     */
    #[Computed]
    public function availableCategories(): array
    {
        $themeService = app(ThemeService::class);

        return $themeService->getAvailableCategories();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.theme-customizer');
    }
}
