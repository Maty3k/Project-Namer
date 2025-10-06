<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use App\Models\UserThemePreference;
use App\Services\ThemeService;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser-based accessibility tests for themes using Pest's accessibility checker.
 *
 * These tests load actual theme implementations in the browser and verify
 * they meet accessibility standards using automated accessibility testing.
 */
final class ThemeAccessibilityBrowserTest extends DuskTestCase
{
    private User $user;

    private ThemeService $themeService;

    protected function setUp(): void
    {
        $this->user = User::factory()->create();
        $this->themeService = new ThemeService;
    }

    /**
     * Test accessibility for all predefined themes in the browser.
     */
    public function test_all_themes_pass_browser_accessibility_checks(): void
    {
        $themes = $this->themeService->getPredefinedThemes();
        $failedThemes = [];

        $this->browse(function (Browser $browser) use ($themes, &$failedThemes): void {
            $browser->loginAs($this->user);

            foreach ($themes as $theme) {
                try {
                    // Apply the theme
                    $this->applyThemeToUser($theme);

                    // Navigate to dashboard with the theme applied
                    $browser->visit('/dashboard')
                        ->waitFor('.name-generator-container', 10)
                        ->pause(1000); // Allow theme to fully load

                    // Run accessibility checks
                    $browser->assertNoAccessibilityIssues();

                } catch (\Exception $e) {
                    $failedThemes[] = [
                        'theme' => $theme['name'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        $this->assertEmpty($failedThemes,
            'The following themes failed browser accessibility tests: '.
            json_encode($failedThemes, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Test accessibility for each theme on different pages.
     *
     * @dataProvider themeAndPageProvider
     */
    public function test_theme_accessibility_across_pages(array $theme, string $routeName, string $selector): void
    {
        $this->browse(function (Browser $browser) use ($theme, $routeName, $selector): void {
            $browser->loginAs($this->user);

            // Apply the theme
            $this->applyThemeToUser($theme);

            // Visit the specific page
            $browser->visit(route($routeName))
                ->waitFor($selector, 10)
                ->pause(1000); // Allow theme and page to fully load

            // Assert no accessibility issues
            $browser->assertNoAccessibilityIssues();
        });
    }

    /**
     * Test dark mode themes specifically for accessibility.
     */
    public function test_dark_mode_themes_accessibility(): void
    {
        $themes = $this->themeService->getPredefinedThemes();
        $darkThemes = array_filter($themes, fn ($theme) => $theme['is_dark_mode'] === true);

        $this->assertNotEmpty($darkThemes, 'No dark themes found to test');

        $this->browse(function (Browser $browser) use ($darkThemes): void {
            $browser->loginAs($this->user);

            foreach ($darkThemes as $theme) {
                // Apply dark theme
                $this->applyThemeToUser($theme);

                // Test on dashboard
                $browser->visit('/dashboard')
                    ->waitFor('.name-generator-container', 10)
                    ->pause(1000);

                // Verify dark mode is applied
                $browser->assertAttribute('html', 'class', 'dark')
                    ->assertNoAccessibilityIssues();

                // Test theme customizer in dark mode
                $browser->visit('/theme-customizer')
                    ->waitFor('.theme-customizer', 10)
                    ->pause(1000)
                    ->assertNoAccessibilityIssues();
            }
        });
    }

    /**
     * Test seasonal themes for accessibility compliance.
     */
    public function test_seasonal_themes_accessibility(): void
    {
        $seasonalThemes = $this->themeService->getThemesByCategory('seasonal');

        $this->assertNotEmpty($seasonalThemes, 'No seasonal themes found to test');

        $this->browse(function (Browser $browser) use ($seasonalThemes): void {
            $browser->loginAs($this->user);

            foreach ($seasonalThemes as $theme) {
                // Apply seasonal theme
                $this->applyThemeToUser($theme);

                $browser->visit('/dashboard')
                    ->waitFor('.name-generator-container', 10)
                    ->pause(1000)
                    ->assertNoAccessibilityIssues();

                // Test on logo gallery as well
                $browser->visit('/logos')
                    ->waitFor('.logo-gallery', 10)
                    ->pause(1000)
                    ->assertNoAccessibilityIssues();
            }
        });
    }

    /**
     * Test theme customizer accessibility with different themes.
     */
    public function test_theme_customizer_accessibility_with_all_themes(): void
    {
        $themes = $this->themeService->getPredefinedThemes();

        $this->browse(function (Browser $browser) use ($themes): void {
            $browser->loginAs($this->user);

            foreach ($themes as $theme) {
                // Apply theme
                $this->applyThemeToUser($theme);

                $browser->visit('/theme-customizer')
                    ->waitFor('.theme-customizer', 10)
                    ->pause(1000);

                // Test accessibility of the customizer interface
                $browser->assertNoAccessibilityIssues();

                // Test color picker interactions
                if ($browser->element('.color-picker-trigger')) {
                    $browser->click('.color-picker-trigger')
                        ->pause(500)
                        ->assertNoAccessibilityIssues()
                        ->press('Escape'); // Close color picker
                }
            }
        });
    }

    /**
     * Test form accessibility with different themes.
     */
    public function test_form_accessibility_across_themes(): void
    {
        $themes = $this->themeService->getPredefinedThemes();

        $this->browse(function (Browser $browser) use ($themes): void {
            $browser->loginAs($this->user);

            foreach ($themes as $theme) {
                // Apply theme
                $this->applyThemeToUser($theme);

                // Test name generator form
                $browser->visit('/dashboard')
                    ->waitFor('.name-generator-container', 10)
                    ->pause(1000);

                // Focus on form elements to test focus states
                $browser->click('textarea[wire\\:model="businessIdea"]')
                    ->assertNoAccessibilityIssues();

                // Test dropdown accessibility
                if ($browser->element('select[wire\\:model="generationMode"]')) {
                    $browser->click('select[wire\\:model="generationMode"]')
                        ->pause(300)
                        ->assertNoAccessibilityIssues();
                }

                // Test button accessibility
                $browser->mouseover('.generate-button')
                    ->assertNoAccessibilityIssues();
            }
        });
    }

    /**
     * Test high contrast mode for themes that support it.
     */
    public function test_high_contrast_accessibility(): void
    {
        $themes = $this->themeService->getPredefinedThemes();

        // Filter themes that should have high contrast (AAA level)
        $highContrastThemes = array_filter($themes, function ($theme) {
            $textBgRatio = $this->themeService->calculateContrastRatio(
                $theme['text_color'],
                $theme['background_color']
            );

            return $textBgRatio >= 7.0;
        });

        $this->browse(function (Browser $browser) use ($highContrastThemes): void {
            $browser->loginAs($this->user);

            foreach ($highContrastThemes as $theme) {
                $this->applyThemeToUser($theme);

                $browser->visit('/dashboard')
                    ->waitFor('.name-generator-container', 10)
                    ->pause(1000)
                    ->assertNoAccessibilityIssues();

                // Test that text is clearly visible
                $browser->assertVisible('.text-gray-900, .text-white, .dark\\:text-white');
            }
        });
    }

    /**
     * Apply a theme to the test user.
     */
    private function applyThemeToUser(array $theme): void
    {
        UserThemePreference::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'theme_name' => $theme['name'],
                'primary_color' => $theme['primary_color'],
                'accent_color' => $theme['accent_color'],
                'background_color' => $theme['background_color'],
                'text_color' => $theme['text_color'],
                'is_dark_mode' => $theme['is_dark_mode'],
            ]
        );

        // Clear any cached theme data
        cache()->forget("user_theme_{$this->user->id}");
    }

    /**
     * Provide theme and page combinations for comprehensive testing.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function themeAndPageProvider(): array
    {
        $themeService = new ThemeService;
        $themes = $themeService->getPredefinedThemes();

        $pages = [
            ['dashboard', '.name-generator-container'],
            ['logos.index', '.logo-gallery'],
            ['projects.show', '.project-details'],
        ];

        $combinations = [];

        foreach ($themes as $theme) {
            foreach ($pages as [$route, $selector]) {
                $key = "{$theme['name']}_on_{$route}";
                $combinations[$key] = [$theme, $route, $selector];
            }
        }

        return $combinations;
    }
}
