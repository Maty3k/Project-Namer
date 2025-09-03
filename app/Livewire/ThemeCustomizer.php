<?php

declare(strict_types=1);

namespace App\Livewire;

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

    #[Rule('required|string|regex:/^#[0-9a-fA-F]{6}$/')]
    public string $primaryColor = '#3b82f6';

    #[Rule('nullable|string|regex:/^#[0-9a-fA-F]{6}$/')]
    public ?string $accentColor = '#10b981';

    #[Rule('required|string|regex:/^#[0-9a-fA-F]{6}$/')]
    public string $backgroundColor = '#ffffff';

    #[Rule('required|string|regex:/^#[0-9a-fA-F]{6}$/')]
    public string $textColor = '#111827';

    #[Rule('required|string|max:50')]
    public string $themeName = 'custom';

    #[Rule('boolean')]
    public bool $isDarkMode = false;

    #[Rule('nullable|file|mimetypes:application/json|max:1024')]
    public mixed $themeFile = null;

    public bool $showColorPicker = false;

    public string $activeColorField = '';

    /** @var array<string, array<string>> */
    public array $accessibilityFeedback = [];

    public float $accessibilityScore = 1.0;

    public string $selectedCategory = 'all';

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

        if ($theme) {
            $this->primaryColor = $theme['primary_color'];
            $this->accentColor = $theme['accent_color'];
            $this->backgroundColor = $theme['background_color'];
            $this->textColor = $theme['text_color'];
            $this->themeName = $theme['theme_name'];
            $this->isDarkMode = $theme['is_dark_mode'];

            $this->validateAccessibility();
            $this->dispatch('theme-updated');
        }
    }

    /**
     * Save current theme preferences.
     */
    public function save(): void
    {
        try {
            $this->validate();

            $user = auth()->user();

            if (! $user) {
                $this->dispatch('theme-error', 'You must be logged in to save themes');
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

            $this->dispatch('theme-saved');
            $this->dispatch('theme-updated'); // Apply theme immediately
        } catch (\Exception $e) {
            logger()->error('Theme save failed: ' . $e->getMessage());
            $this->dispatch('theme-error', 'Failed to save theme preferences');
        }
    }

    /**
     * Open color picker for specific field.
     */
    public function openColorPicker(string $field): void
    {
        $this->activeColorField = $field;
        $this->showColorPicker = true;
    }

    /**
     * Update color and close picker.
     */
    public function updateColor(string $color): void
    {
        if ($this->activeColorField === 'primary') {
            $this->primaryColor = $color;
        } elseif ($this->activeColorField === 'accent') {
            $this->accentColor = $color;
        } elseif ($this->activeColorField === 'background') {
            $this->backgroundColor = $color;
        } elseif ($this->activeColorField === 'text') {
            $this->textColor = $color;
        }

        $this->showColorPicker = false;
        $this->activeColorField = '';
        $this->validateAccessibility();
        $this->dispatch('theme-updated');
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

            $content = file_get_contents($this->themeFile->path());
            $themeData = json_decode($content, true);

            if (! $themeData || ! is_array($themeData)) {
                $this->dispatch('theme-error', 'Invalid theme file format. Please upload a valid JSON theme file.');
                return;
            }

            // Validate required theme properties
            $requiredFields = ['primary_color', 'background_color', 'text_color'];
            foreach ($requiredFields as $field) {
                if (! isset($themeData[$field]) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $themeData[$field])) {
                    $this->dispatch('theme-error', "Invalid or missing {$field} in theme file");
                    return;
                }
            }

            $this->primaryColor = $themeData['primary_color'];
            $this->accentColor = $themeData['accent_color'] ?? $themeData['primary_color'];
            $this->backgroundColor = $themeData['background_color'];
            $this->textColor = $themeData['text_color'];
            $this->themeName = $themeData['theme_name'] ?? 'imported';
            $this->isDarkMode = $themeData['is_dark_mode'] ?? false;

            $this->themeFile = null;
            $this->validateAccessibility();
            $this->dispatch('theme-imported');
        } catch (\Exception $e) {
            logger()->error('Theme import failed: ' . $e->getMessage());
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
        if ($this->recommendedSeasonalTheme) {
            $this->applyPreset($this->recommendedSeasonalTheme['name']);
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.theme-customizer');
    }
}
