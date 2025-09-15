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

            // Ensure proper text contrast based on mode
            $this->ensureTextReadability();

            $this->validateAccessibility();

            // Automatically save the preset theme to persist it
            $this->applyTheme();

            $this->dispatch('theme-updated');
            $this->dispatch('theme-applied', [
                'primaryColor' => $this->primaryColor,
                'accentColor' => $this->accentColor,
                'backgroundColor' => $this->backgroundColor,
                'textColor' => $this->textColor,
                'isDarkMode' => $this->isDarkMode,
            ]);
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

            // Validate accessibility and provide feedback
            $this->validateAccessibility();

            // Clear theme cache to ensure consistency
            ThemeHelper::clearUserThemeCache();

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

        if ($this->isDarkMode) {
            // Set appropriate dark mode colors
            $this->backgroundColor = '#1f2937';
            $this->textColor = '#f9fafb';
        } else {
            // Set appropriate light mode colors
            $this->backgroundColor = '#ffffff';
            $this->textColor = '#111827';
        }

        $this->validateAccessibility();
        $this->dispatch('theme-updated');
        $this->dispatch('theme-applied', [
            'primaryColor' => $this->primaryColor,
            'accentColor' => $this->accentColor,
            'backgroundColor' => $this->backgroundColor,
            'textColor' => $this->textColor,
            'isDarkMode' => $this->isDarkMode,
        ]);
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
        $this->validateAccessibility();
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.theme-customizer');
    }
}
