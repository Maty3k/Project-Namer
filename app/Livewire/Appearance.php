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

    public function mount(): void
    {
        $user = auth()->user();

        if ($user) {
            $preference = UserThemePreference::where('user_id', $user->id)->first();
            $this->currentTheme = $preference ? $preference->theme_name : ($user->current_theme ?? 'default');
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

        // Refresh page to apply theme server-side
        $this->dispatch('$refresh');
    }

    public function render(): \Illuminate\View\View
    {
        $themeService = app(ThemeService::class);

        return view('livewire.appearance', [
            'themes' => $themeService->getPredefinedThemes(),
            'isDarkMode' => ThemeHelper::isDarkMode(),
        ]);
    }
}
