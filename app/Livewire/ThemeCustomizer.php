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
use Livewire\WithFileUploads;

final class ThemeCustomizer extends Component
{
    use WithFileUploads;

    #[Rule(['required', 'string', 'size:7', 'starts_with:#'])]
    public string $primaryColor = '#3b82f6';

    #[Rule(['nullable', 'string', 'size:7', 'starts_with:#'])]
    public ?string $accentColor = '#10b981';

    #[Rule(['required', 'string', 'size:7', 'starts_with:#'])]
    public string $backgroundColor = '#ffffff';

    #[Rule(['required', 'string', 'size:7', 'starts_with:#'])]
    public string $textColor = '#111827';

    #[Rule('required|string|max:50')]
    public string $themeName = 'custom';

    #[Rule('boolean')]
    public bool $isDarkMode = false;

    #[Rule('nullable|file|mimetypes:application/json|max:1024')]
    public mixed $themeFile = null;

    /** @var array<string, array<string>> */
    public array $accessibilityFeedback = [];

    public float $accessibilityScore = 1.0;

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
                $this->primaryColor = $preference->primary_color;
                $this->accentColor = $preference->accent_color;
                $this->backgroundColor = $preference->background_color;
                $this->textColor = $preference->text_color;
                $this->themeName = $preference->theme_name;
                $this->isDarkMode = $preference->is_dark_mode;
            } else {
                // Fall back to User model theme settings if no detailed preferences exist
                $this->isDarkMode = $user->prefers_dark_mode ?? false;
                $this->themeName = $user->current_theme ?? 'default';

                // Set appropriate colors based on dark mode setting
                if ($this->isDarkMode) {
                    $this->backgroundColor = '#1f2937';
                    $this->textColor = '#f9fafb';
                } else {
                    $this->backgroundColor = '#ffffff';
                    $this->textColor = '#111827';
                }
            }
        }

        $this->loadSeasonalRecommendation();
        $this->validateAccessibility();
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
            $this->primaryColor = is_string($theme['primary_color'] ?? null) ? $theme['primary_color'] : '#3b82f6';
            $this->accentColor = is_string($theme['accent_color'] ?? null) ? $theme['accent_color'] : null;
            $this->backgroundColor = is_string($theme['background_color'] ?? null) ? $theme['background_color'] : '#ffffff';
            $this->textColor = is_string($theme['text_color'] ?? null) ? $theme['text_color'] : '#111827';
            $this->themeName = is_string($theme['theme_name'] ?? null) ? $theme['theme_name'] : 'default';
            $this->isDarkMode = is_bool($theme['is_dark_mode'] ?? null) ? $theme['is_dark_mode'] : false;

            // For predefined themes, trust the design but still validate accessibility
            $this->validateAccessibility();

            // Automatically save the preset theme to persist it
            $this->applyTheme();
        }
    }

    /**
     * Apply and save current theme preferences with enhanced feedback.
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
                    'primary_color' => $this->primaryColor,
                    'accent_color' => $this->accentColor,
                    'background_color' => $this->backgroundColor,
                    'text_color' => $this->textColor,
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

            // Validate accessibility and provide feedback
            $this->validateAccessibility();

            // Clear theme cache to ensure consistency and force fresh data
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

            // Dispatch events for UI updates
            $this->dispatch('theme-saved');
            $this->dispatch('theme-updated');
            $this->dispatch('theme-applied', [
                'primaryColor' => $this->primaryColor,
                'accentColor' => $this->accentColor,
                'backgroundColor' => $this->backgroundColor,
                'textColor' => $this->textColor,
                'isDarkMode' => $this->isDarkMode,
            ]);

            // Force refresh of all theme-dependent Livewire components
            $this->dispatch('refresh-theme-components');

            // Instantly apply theme changes with comprehensive sync
            $isDarkModeJs = $this->isDarkMode ? 'true' : 'false';
            $this->js("
                const html = document.documentElement;
                const isDark = {$isDarkModeJs};

                console.log('THEME CUSTOMIZER: Applying theme', isDark ? 'DARK' : 'LIGHT');

                // Authorize this theme change with the protection system
                if (window.authorizeThemeChange) {
                    window.authorizeThemeChange(isDark, 15000); // 15 second authorization for theme customizer
                }

                // Apply CSS custom properties
                html.style.setProperty('--primary-color', '{$this->primaryColor}');
                html.style.setProperty('--accent-color', '{$this->accentColor}');
                html.style.setProperty('--background-color', '{$this->backgroundColor}');
                html.style.setProperty('--text-color', '{$this->textColor}');

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

                // Force a repaint to ensure styles update
                html.style.display = 'none';
                html.offsetHeight; // Trigger reflow
                html.style.display = '';

                // Dispatch global theme change event
                window.dispatchEvent(new CustomEvent('theme-changed', {
                    detail: {
                        isDark: isDark,
                        primaryColor: '{$this->primaryColor}',
                        accentColor: '{$this->accentColor}',
                        backgroundColor: '{$this->backgroundColor}',
                        textColor: '{$this->textColor}'
                    }
                }));

                // Update any theme toggle buttons in the UI
                window.dispatchEvent(new CustomEvent('theme-customizer-updated', {
                    detail: { isDarkMode: isDark }
                }));

                // Force global state synchronization
                setTimeout(() => {
                    // Refresh the current page to ensure all server-side components reflect the new theme
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
        // Redirect to the new unified apply method
        $this->applyTheme();
    }

    /**
     * Import theme from uploaded file.
     */
    public function importTheme(): void
    {
        try {
            $this->validate(['themeFile']);

            if (! $this->themeFile) {
                $this->dispatch('theme-error', 'No theme file provided');

                return;
            }

            if (! $this->themeFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $this->dispatch('theme-error', 'Invalid file upload');

                return;
            }

            $content = file_get_contents($this->themeFile->path());
            if ($content === false) {
                $this->dispatch('theme-error', 'Could not read theme file');

                return;
            }

            $themeData = json_decode($content, true);

            if (! $themeData || ! is_array($themeData)) {
                $this->dispatch('theme-error', 'Invalid theme file format. Please upload a valid JSON theme file.');

                return;
            }

            // Validate required theme properties
            $requiredFields = ['primary_color', 'background_color', 'text_color'];
            foreach ($requiredFields as $field) {
                $value = $themeData[$field] ?? null;
                if (! is_string($value) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    $this->dispatch('theme-error', "Invalid or missing {$field} in theme file");

                    return;
                }
            }

            $this->primaryColor = is_string($themeData['primary_color'] ?? null) ? $themeData['primary_color'] : '#3b82f6';
            $this->accentColor = is_string($themeData['accent_color'] ?? null) ? $themeData['accent_color'] : $this->primaryColor;
            $this->backgroundColor = is_string($themeData['background_color'] ?? null) ? $themeData['background_color'] : '#ffffff';
            $this->textColor = is_string($themeData['text_color'] ?? null) ? $themeData['text_color'] : '#111827';
            $this->themeName = is_string($themeData['theme_name'] ?? null) ? $themeData['theme_name'] : 'imported';
            $this->isDarkMode = is_bool($themeData['is_dark_mode'] ?? null) ? $themeData['is_dark_mode'] : false;

            $this->themeFile = null;
            $this->validateAccessibility();

            // Automatically apply the imported theme so user doesn't need to refresh
            $this->applyTheme();

            $this->dispatch('theme-imported');
        } catch (\Exception $e) {
            logger()->error('Theme import failed: '.$e->getMessage());
            $this->dispatch('theme-error', 'Failed to import theme file');
        }
    }

    /**
     * Export current theme as downloadable file.
     */
    public function exportTheme(): void
    {
        $themeData = [
            'theme_name' => $this->themeName,
            'primary_color' => $this->primaryColor,
            'accent_color' => $this->accentColor,
            'background_color' => $this->backgroundColor,
            'text_color' => $this->textColor,
            'is_dark_mode' => $this->isDarkMode,
        ];

        $this->dispatch('download-theme', json_encode($themeData, JSON_PRETTY_PRINT));
    }

    /**
     * Reset theme to default values.
     */
    public function resetToDefault(): void
    {
        $this->primaryColor = '#3b82f6';
        $this->accentColor = '#10b981';
        $this->backgroundColor = '#ffffff';
        $this->textColor = '#111827';
        $this->themeName = 'default';
        $this->isDarkMode = false;

        $this->validateAccessibility();
        $this->dispatch('theme-updated');
    }

    /**
     * Toggle dark mode and update colors accordingly.
     */
    public function toggleDarkMode(): void
    {
        $this->isDarkMode = ! $this->isDarkMode;

        // Auto-adjust theme colors based on mode to ensure good visibility
        if ($this->isDarkMode) {
            // Set appropriate dark mode colors with good contrast
            $this->backgroundColor = '#1f2937';
            $this->textColor = '#f9fafb';

            // Adjust primary color if it's too light for dark backgrounds
            if ($this->isColorTooLight($this->primaryColor)) {
                $this->primaryColor = $this->darkenColor($this->primaryColor, 0.3);
            }
        } else {
            // Set appropriate light mode colors with good contrast
            $this->backgroundColor = '#ffffff';
            $this->textColor = '#111827';

            // Adjust primary color if it's too dark for light backgrounds
            if ($this->isColorTooDark($this->primaryColor)) {
                $this->primaryColor = $this->lightenColor($this->primaryColor, 0.2);
            }
        }

        // Ensure text readability after mode change
        $this->ensureTextReadability();
        $this->validateAccessibility();

        // Auto-save the theme with the new dark mode setting using comprehensive sync
        $this->applyTheme();
    }

    /**
     * Validate accessibility of current color combination.
     */
    protected function validateAccessibility(): void
    {
        $themeService = app(ThemeService::class);

        $this->accessibilityScore = $themeService->calculateAccessibilityScore(
            $this->primaryColor,
            $this->backgroundColor,
            $this->textColor
        );

        $this->accessibilityFeedback = $themeService->generateAccessibilityFeedback(
            $this->primaryColor,
            $this->backgroundColor,
            $this->textColor
        );
    }

    /**
     * Listen for color updates from external sources.
     */
    #[On('color-updated')]
    public function onColorUpdated(): void
    {
        $this->ensureTextReadability();
        $this->validateAccessibility();
    }

    /**
     * React to primary color changes.
     */
    public function updatedPrimaryColor(): void
    {
        $this->ensureTextReadability();
        $this->validateAccessibility();
    }

    /**
     * React to background color changes.
     */
    public function updatedBackgroundColor(): void
    {
        $this->ensureTextReadability();
        $this->validateAccessibility();
    }

    /**
     * React to text color changes.
     */
    public function updatedTextColor(): void
    {
        $this->validateAccessibility();
    }

    /**
     * Listen for theme changes from ThemeQuickToggle.
     *
     * @param  array{isDarkMode: bool, backgroundColor: string, textColor: string}  $data
     */
    #[On('theme-quick-toggle-changed')]
    public function onThemeQuickToggleChanged(array $data): void
    {
        // Update the theme customizer properties to match the quick toggle
        if (isset($data['isDarkMode'])) {
            $this->isDarkMode = (bool) $data['isDarkMode'];
        }
        if (isset($data['backgroundColor'])) {
            $this->backgroundColor = (string) $data['backgroundColor'];
        }
        if (isset($data['textColor'])) {
            $this->textColor = (string) $data['textColor'];
        }

        $this->validateAccessibility();
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

    /**
     * Get generated CSS for current theme.
     */
    #[Computed]
    public function generatedCss(): string
    {
        $themeService = app(ThemeService::class);

        return $themeService->generateCssProperties([
            'primary_color' => $this->primaryColor,
            'accent_color' => $this->accentColor,
            'background_color' => $this->backgroundColor,
            'text_color' => $this->textColor,
        ]);
    }

    /**
     * Get contrasting color for text on a given background.
     */
    public function getContrastingColor(string $backgroundColor): string
    {
        // Remove # if present
        $hex = str_replace('#', '', $backgroundColor);

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        // Return white or black based on luminance
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    /**
     * Get a good hover background color with proper contrast.
     */
    public function getHoverBackgroundColor(string $baseColor, float $opacity = 0.1): string
    {
        // For hover states, we want a subtle tint of the primary color
        return $baseColor.sprintf('%02x', (int) ($opacity * 255));
    }

    /**
     * Ensure text readability by setting appropriate text colors based on background and theme mode.
     */
    protected function ensureTextReadability(): void
    {
        // Calculate luminance of background to determine if it's light or dark
        $backgroundHex = ltrim($this->backgroundColor, '#');
        $r = hexdec(substr($backgroundHex, 0, 2));
        $g = hexdec(substr($backgroundHex, 2, 2));
        $b = hexdec(substr($backgroundHex, 4, 2));

        // Calculate relative luminance using the WCAG formula
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        // If background is dark (luminance < 0.5), use light text
        // If background is light (luminance >= 0.5), use dark text
        if ($luminance < 0.5) {
            // Dark background - ensure light text
            if (! $this->isDarkMode) {
                $this->isDarkMode = true;
            }
            // Use light text colors for dark backgrounds
            if ($this->textColor === '#111827' || $this->textColor === '#1f2937' || $luminance < 0.3) {
                $this->textColor = '#f9fafb';
            }
        } else {
            // Light background - ensure dark text
            if ($this->isDarkMode) {
                $this->isDarkMode = false;
            }
            // Use dark text colors for light backgrounds
            if ($this->textColor === '#f9fafb' || $this->textColor === '#ffffff' || $luminance > 0.7) {
                $this->textColor = '#111827';
            }
        }
    }

    /**
     * Check if color is too light for dark backgrounds.
     */
    protected function isColorTooLight(string $color): bool
    {
        $hex = ltrim($color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.8; // Too light if luminance > 80%
    }

    /**
     * Check if color is too dark for light backgrounds.
     */
    protected function isColorTooDark(string $color): bool
    {
        $hex = ltrim($color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance < 0.2; // Too dark if luminance < 20%
    }

    /**
     * Darken a color by a given percentage.
     */
    protected function darkenColor(string $color, float $percentage): string
    {
        $hex = ltrim($color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, (int) ($r * (1 - $percentage)));
        $g = max(0, (int) ($g * (1 - $percentage)));
        $b = max(0, (int) ($b * (1 - $percentage)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Lighten a color by a given percentage.
     */
    protected function lightenColor(string $color, float $percentage): string
    {
        $hex = ltrim($color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = min(255, (int) ($r + (255 - $r) * $percentage));
        $g = min(255, (int) ($g + (255 - $g) * $percentage));
        $b = min(255, (int) ($b + (255 - $b) * $percentage));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.theme-customizer');
    }
}
