<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\ThemeHelper;
use App\Models\CustomTheme;
use App\Models\UserThemePreference;
use App\Services\ThemeService;
use Livewire\Component;

class Appearance extends Component
{
    public string $currentTheme = 'default';

    public ?array $selectedThemeData = null;

    // Custom theme creation/editing
    public bool $showCreateModal = false;

    public ?int $editingThemeId = null;

    public string $customThemeName = '';

    public string $customPrimaryColor = '#3b82f6';

    public string $customAccentColor = '#059669';

    public bool $customIsDarkMode = false;

    // Delete confirmation
    public bool $showDeleteModal = false;

    public ?int $deletingThemeId = null;

    public string $deletingThemeName = '';

    public function mount(): void
    {
        $user = auth()->user();

        if ($user) {
            $preference = UserThemePreference::where('user_id', $user->id)->first();
            $this->currentTheme = $preference ? $preference->theme_name : ($user->current_theme ?? 'default');
        }

        // Load theme data from session if available
        if (session()->has('theme_selected')) {
            $themeData = session('theme_selected');
            $this->selectedThemeData = $themeData['colors'];
        }
    }

    public function selectTheme(string $themeName): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        // Get theme details from service to get is_dark_mode
        $themeService = app(ThemeService::class);
        $themes = collect($themeService->getPredefinedThemes());
        $theme = $themes->firstWhere('name', $themeName);

        if (! $theme) {
            return;
        }

        $isDarkMode = $theme['is_dark_mode'] ?? false;

        // Update User model
        $user->update([
            'current_theme' => $themeName,
            'prefers_dark_mode' => $isDarkMode,
        ]);

        // Update or create UserThemePreference
        UserThemePreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'theme_name' => $themeName,
                'is_dark_mode' => $isDarkMode,
            ]
        );

        // Clear theme cache
        ThemeHelper::clearUserThemeCache();

        // Update current theme for UI
        $this->currentTheme = $themeName;

        // Store theme colors in session for confirmation after reload
        session()->flash('theme_selected', [
            'name' => $themeName,
            'colors' => $this->getThemeColors($themeName),
        ]);

        // Reload page to apply theme immediately
        $this->redirect(route('appearance'), navigate: true);
    }

    /**
     * Open the create custom theme modal.
     */
    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->editingThemeId = null;
        $this->customThemeName = '';
        $this->customPrimaryColor = '#3b82f6';
        $this->customAccentColor = '#059669';
        $this->customIsDarkMode = false;
    }

    /**
     * Open the edit custom theme modal.
     */
    public function openEditModal(int $customThemeId): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $customTheme = CustomTheme::where('id', $customThemeId)
            ->where('user_id', $user->id)
            ->first();

        if (! $customTheme) {
            return;
        }

        $this->showCreateModal = true;
        $this->editingThemeId = $customTheme->id;
        $this->customThemeName = $customTheme->name;
        $this->customPrimaryColor = $customTheme->primary_color;
        $this->customAccentColor = $customTheme->accent_color;
        $this->customIsDarkMode = $customTheme->is_dark_mode;
    }

    /**
     * Close the create/edit custom theme modal.
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->editingThemeId = null;
    }

    /**
     * Create or update a custom theme.
     */
    public function createCustomTheme(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->validate([
            'customThemeName' => 'required|string|min:2|max:50',
            'customPrimaryColor' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'customAccentColor' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($this->editingThemeId) {
            // Update existing theme
            $customTheme = CustomTheme::where('id', $this->editingThemeId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $customTheme->update([
                'name' => $this->customThemeName,
                'primary_color' => $this->customPrimaryColor,
                'accent_color' => $this->customAccentColor,
                'is_dark_mode' => $this->customIsDarkMode,
            ]);

            // Regenerate the CSS file
            $customTheme->saveCssFile();

            // Close modal
            $this->showCreateModal = false;
            $this->editingThemeId = null;

            // Re-select the theme to apply changes
            $this->selectCustomTheme($customTheme->id);
        } else {
            // Create the custom theme
            $customTheme = CustomTheme::create([
                'user_id' => $user->id,
                'name' => $this->customThemeName,
                'primary_color' => $this->customPrimaryColor,
                'accent_color' => $this->customAccentColor,
                'is_dark_mode' => $this->customIsDarkMode,
            ]);

            // Generate and save the CSS file
            $customTheme->saveCssFile();

            // Close modal
            $this->showCreateModal = false;

            // Select the new theme
            $this->selectCustomTheme($customTheme->id);
        }
    }

    /**
     * Select a custom theme.
     */
    public function selectCustomTheme(int $customThemeId): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $customTheme = CustomTheme::where('id', $customThemeId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $themeName = $customTheme->getThemeIdentifier();

        // Update User model
        $user->update([
            'current_theme' => $themeName,
            'prefers_dark_mode' => $customTheme->is_dark_mode,
        ]);

        // Update or create UserThemePreference
        UserThemePreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'theme_name' => $themeName,
                'is_dark_mode' => $customTheme->is_dark_mode,
            ]
        );

        // Clear theme cache
        ThemeHelper::clearUserThemeCache();

        // Update current theme for UI
        $this->currentTheme = $themeName;

        // Store theme colors in session for confirmation
        session()->flash('theme_selected', [
            'name' => $customTheme->name,
            'colors' => [
                'primary' => $customTheme->primary_color,
                'accent' => $customTheme->accent_color,
            ],
        ]);

        // Reload page to apply theme
        $this->redirect(route('appearance'), navigate: true);
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDeleteTheme(int $customThemeId): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $customTheme = CustomTheme::where('id', $customThemeId)
            ->where('user_id', $user->id)
            ->first();

        if (! $customTheme) {
            return;
        }

        $this->deletingThemeId = $customTheme->id;
        $this->deletingThemeName = $customTheme->name;
        $this->showDeleteModal = true;
    }

    /**
     * Cancel delete and close modal.
     */
    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingThemeId = null;
        $this->deletingThemeName = '';
    }

    /**
     * Delete a custom theme.
     */
    public function deleteCustomTheme(): void
    {
        $user = auth()->user();

        if (! $user || ! $this->deletingThemeId) {
            return;
        }

        $customTheme = CustomTheme::where('id', $this->deletingThemeId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If this is the current theme, switch to default
        if ($this->currentTheme === $customTheme->getThemeIdentifier()) {
            $this->selectTheme('default');
        }

        // Delete the CSS file
        $customTheme->deleteCssFile();

        // Delete the database record
        $customTheme->delete();

        // Close modal
        $this->showDeleteModal = false;
        $this->deletingThemeId = null;
        $this->deletingThemeName = '';
    }

    /**
     * Get theme colors from CSS file.
     *
     * @return array<string, string>|null
     */
    protected function getThemeColors(string $themeName): ?array
    {
        $cssPath = public_path("css/themes/{$themeName}.css");

        if (! file_exists($cssPath)) {
            return null;
        }

        $cssContent = file_get_contents($cssPath);

        if ($cssContent === false) {
            return null;
        }

        // Extract CSS variables from :root selector
        preg_match_all('/--color-(\w+):\s*(#[0-9a-fA-F]{6});/m', $cssContent, $matches, PREG_SET_ORDER);

        $colors = [];
        foreach ($matches as $match) {
            $colors[$match[1]] = $match[2];
        }

        return $colors;
    }

    /**
     * Get emoji icon for each theme.
     *
     * @return array<string, string>
     */
    protected function getThemeEmojis(): array
    {
        return [
            'default' => '🔵',
            'dark' => '🌙',
            'ocean' => '🌊',
            'sunset' => '🌅',
            'forest' => '🌲',
            'cosmic-violet' => '🌌',
            'coral-reef' => '🪸',
            'cyberpunk' => '🌆',
            'summer' => '🏖️',
            'winter' => '❄️',
            'halloween' => '🎃',
            'spring' => '🌸',
            'autumn' => '🍂',
            'neon-cyber' => '💜',
            'electric-blue' => '⚡',
            'hot-pink' => '💗',
            'lava-red' => '🌋',
            'lime-punch' => '🍋',
            'gold-rush' => '💰',
            'matrix-green' => '🟢',
            'sakura' => '🌸',
            'arctic' => '🧊',
            'mocha' => '☕',
        ];
    }

    public function render(): \Illuminate\View\View
    {
        $themeService = app(ThemeService::class);
        $user = auth()->user();

        $customThemes = $user
            ? CustomTheme::where('user_id', $user->id)->orderBy('name')->get()
            : collect();

        return view('livewire.appearance', [
            'themes' => $themeService->getPredefinedThemes(),
            'customThemes' => $customThemes,
            'isDarkMode' => ThemeHelper::isDarkMode(),
            'themeEmojis' => $this->getThemeEmojis(),
        ]);
    }
}
