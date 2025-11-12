<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\ThemeHelper;
use App\Models\UserThemePreference;
use App\Services\ThemeService;
use Livewire\Component;

class Appearance extends Component
{
    public string $currentTheme = 'default';

    public ?array $selectedThemeData = null;

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
        ];
    }

    public function render(): \Illuminate\View\View
    {
        $themeService = app(ThemeService::class);

        return view('livewire.appearance', [
            'themes' => $themeService->getPredefinedThemes(),
            'isDarkMode' => ThemeHelper::isDarkMode(),
            'themeEmojis' => $this->getThemeEmojis(),
        ]);
    }
}
