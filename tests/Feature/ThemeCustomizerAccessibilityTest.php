<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ThemeCustomizer;
use App\Models\User;
use App\Models\UserThemePreference;
use App\Services\ThemeService;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature tests for theme customizer accessibility validation.
 *
 * These tests ensure that the theme customizer properly validates
 * accessibility when users create custom themes.
 */
final class ThemeCustomizerAccessibilityTest extends TestCase
{
    private User $user;

    private ThemeService $themeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->themeService = new ThemeService;
    }

    /**
     * Test that theme customizer validates accessibility when applying themes.
     */
    public function test_theme_customizer_validates_accessibility(): void
    {
        $this->actingAs($this->user);

        // Test with good accessibility colors
        $component = Livewire::test(ThemeCustomizer::class)
            ->set('primaryColor', '#1e40af')
            ->set('backgroundColor', '#ffffff')
            ->set('textColor', '#1f2937')
            ->call('validateAccessibility');

        // Should not have accessibility warnings for good contrast
        $warnings = $component->get('accessibilityWarnings');
        $this->assertEmpty($warnings, 'Good contrast theme should not have warnings');

        $score = $component->get('accessibilityScore');
        $this->assertGreaterThan(0.6, $score, 'Good contrast theme should have decent accessibility score');
    }

    /**
     * Test that theme customizer warns about poor accessibility.
     */
    public function test_theme_customizer_warns_about_poor_accessibility(): void
    {
        $this->actingAs($this->user);

        // Test with poor accessibility colors
        $component = Livewire::test(ThemeCustomizer::class)
            ->set('primaryColor', '#cccccc')
            ->set('backgroundColor', '#ffffff')
            ->set('textColor', '#dddddd')
            ->call('validateAccessibility');

        // Should have accessibility warnings for poor contrast
        $warnings = $component->get('accessibilityWarnings');
        $this->assertNotEmpty($warnings, 'Poor contrast theme should generate warnings');

        $score = $component->get('accessibilityScore');
        $this->assertLessThan(0.6, $score, 'Poor contrast theme should have low accessibility score');
    }

    /**
     * Test that theme customizer applies predefined themes correctly.
     */
    public function test_theme_customizer_applies_predefined_themes_with_accessibility(): void
    {
        $this->actingAs($this->user);
        $themes = $this->themeService->getPredefinedThemes();

        foreach ($themes as $theme) {
            $component = Livewire::test(ThemeCustomizer::class)
                ->call('applyPreset', $theme['name']);

            // Verify theme colors are applied
            $component->assertSet('primaryColor', $theme['primary_color']);
            $component->assertSet('backgroundColor', $theme['background_color']);
            $component->assertSet('textColor', $theme['text_color']);

            // Verify accessibility is maintained
            $score = $component->get('accessibilityScore');
            $this->assertGreaterThan(0.6, $score,
                "Predefined theme '{$theme['name']}' should maintain good accessibility"
            );

            // Verify no critical warnings
            $warnings = $component->get('accessibilityWarnings');
            $criticalWarnings = array_filter($warnings,
                fn ($warning) => str_contains(strtolower((string) $warning), 'insufficient contrast')
            );
            $this->assertEmpty($criticalWarnings,
                "Predefined theme '{$theme['name']}' should not have critical contrast warnings"
            );
        }
    }

    /**
     * Test theme customizer saves accessibility-compliant themes.
     */
    public function test_theme_customizer_saves_accessible_themes(): void
    {
        $this->actingAs($this->user);

        // Create accessible theme
        Livewire::test(ThemeCustomizer::class)
            ->set('primaryColor', '#2563eb')
            ->set('accentColor', '#059669')
            ->set('backgroundColor', '#ffffff')
            ->set('textColor', '#111827')
            ->set('themeName', 'test-accessible-theme')
            ->call('saveTheme');

        // Verify theme was saved
        $savedTheme = UserThemePreference::where('user_id', $this->user->id)->first();
        $this->assertNotNull($savedTheme, 'Theme should be saved');

        // Verify saved theme meets accessibility standards
        $textBgRatio = $this->themeService->calculateContrastRatio(
            $savedTheme->text_color,
            $savedTheme->background_color
        );
        $this->assertGreaterThanOrEqual(4.5, $textBgRatio,
            'Saved theme should meet WCAG AA text contrast standards'
        );

        $primaryBgRatio = $this->themeService->calculateContrastRatio(
            $savedTheme->primary_color,
            $savedTheme->background_color
        );
        $this->assertGreaterThanOrEqual(3.0, $primaryBgRatio,
            'Saved theme should meet WCAG AA UI component contrast standards'
        );
    }

    /**
     * Test theme customizer rejects severely inaccessible themes.
     */
    public function test_theme_customizer_rejects_inaccessible_themes(): void
    {
        $this->actingAs($this->user);

        // Attempt to save severely inaccessible theme
        $component = Livewire::test(ThemeCustomizer::class)
            ->set('primaryColor', '#f0f0f0')
            ->set('backgroundColor', '#ffffff')
            ->set('textColor', '#f5f5f5')
            ->set('themeName', 'test-bad-theme')
            ->call('saveTheme');

        // Should have validation errors or warnings
        $errors = $component->get('errors');
        $warnings = $component->get('accessibilityWarnings');

        $this->assertTrue(
            ! empty($errors) || ! empty($warnings),
            'Severely inaccessible theme should trigger errors or warnings'
        );
    }

    /**
     * Test accessibility feedback generation.
     */
    public function test_accessibility_feedback_generation(): void
    {
        $this->actingAs($this->user);

        $testCases = [
            // Good accessibility
            [
                'colors' => ['#2563eb', '#ffffff', '#111827'],
                'expectWarnings' => false,
                'expectSuggestions' => false,
            ],
            // Moderate issues
            [
                'colors' => ['#6b7280', '#ffffff', '#4b5563'],
                'expectWarnings' => false,
                'expectSuggestions' => true,
            ],
            // Poor accessibility
            [
                'colors' => ['#e5e7eb', '#ffffff', '#d1d5db'],
                'expectWarnings' => true,
                'expectSuggestions' => true,
            ],
        ];

        foreach ($testCases as $case) {
            [$primary, $background, $text] = $case['colors'];

            $component = Livewire::test(ThemeCustomizer::class)
                ->set('primaryColor', $primary)
                ->set('backgroundColor', $background)
                ->set('textColor', $text)
                ->call('validateAccessibility');

            $warnings = $component->get('accessibilityWarnings');
            $suggestions = $component->get('accessibilitySuggestions');

            if ($case['expectWarnings']) {
                $this->assertNotEmpty($warnings,
                    "Colors {$primary}, {$background}, {$text} should generate warnings"
                );
            } else {
                $this->assertEmpty($warnings,
                    "Colors {$primary}, {$background}, {$text} should not generate warnings"
                );
            }

            if ($case['expectSuggestions']) {
                $this->assertNotEmpty($suggestions,
                    "Colors {$primary}, {$background}, {$text} should generate suggestions"
                );
            }
        }
    }

    /**
     * Test dark mode accessibility validation.
     */
    public function test_dark_mode_accessibility_validation(): void
    {
        $this->actingAs($this->user);

        // Test good dark mode colors
        $component = Livewire::test(ThemeCustomizer::class)
            ->set('primaryColor', '#60a5fa')
            ->set('backgroundColor', '#111827')
            ->set('textColor', '#f9fafb')
            ->set('isDarkMode', true)
            ->call('validateAccessibility');

        $score = $component->get('accessibilityScore');
        $this->assertGreaterThan(0.8, $score, 'Good dark theme should have high accessibility score');

        // Test poor dark mode colors
        $component = Livewire::test(ThemeCustomizer::class)
            ->set('primaryColor', '#1e40af')
            ->set('backgroundColor', '#374151')
            ->set('textColor', '#6b7280')
            ->set('isDarkMode', true)
            ->call('validateAccessibility');

        $warnings = $component->get('accessibilityWarnings');
        $this->assertNotEmpty($warnings, 'Poor dark theme should generate warnings');
    }

    /**
     * Test seasonal theme accessibility maintenance.
     */
    public function test_seasonal_theme_accessibility_maintenance(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ThemeCustomizer::class);

        // Test applying seasonal recommendation
        $seasonalTheme = $this->themeService->getCurrentSeasonalTheme();
        if ($seasonalTheme) {
            $component->call('applySeasonalRecommendation');

            // Verify seasonal theme maintains accessibility
            $score = $component->get('accessibilityScore');
            $this->assertGreaterThan(0.6, $score,
                'Seasonal theme should maintain good accessibility'
            );

            $warnings = $component->get('accessibilityWarnings');
            $criticalWarnings = array_filter($warnings,
                fn ($warning) => str_contains(strtolower((string) $warning), 'insufficient')
            );
            $this->assertEmpty($criticalWarnings,
                'Seasonal theme should not have critical accessibility issues'
            );
        }
    }

    /**
     * Test accessibility validation during theme preview.
     */
    public function test_theme_preview_accessibility_validation(): void
    {
        $this->actingAs($this->user);

        $themes = $this->themeService->getPredefinedThemes();

        foreach ($themes as $theme) {
            $component = Livewire::test(ThemeCustomizer::class)
                ->call('applyPreset', $theme['name'])
                ->call('validateAccessibility');

            // All predefined themes should pass accessibility validation
            $score = $component->get('accessibilityScore');
            $this->assertGreaterThan(0.6, $score,
                "Theme preview for '{$theme['name']}' should maintain accessibility"
            );

            // Verify theme data integrity during preview
            $component->assertSet('primaryColor', $theme['primary_color']);
            $component->assertSet('backgroundColor', $theme['background_color']);
            $component->assertSet('textColor', $theme['text_color']);
        }
    }

    /**
     * Test that accessibility validation prevents saving bad themes.
     */
    public function test_accessibility_validation_prevents_bad_themes(): void
    {
        $this->actingAs($this->user);

        // Attempt to save theme with terrible contrast
        $component = Livewire::test(ThemeCustomizer::class)
            ->set('primaryColor', '#ffffff')
            ->set('backgroundColor', '#ffffff')
            ->set('textColor', '#ffffff')
            ->set('themeName', 'invisible-theme')
            ->call('saveTheme');

        // Should not save theme with no contrast
        $savedTheme = UserThemePreference::where([
            'user_id' => $this->user->id,
            'theme_name' => 'invisible-theme',
        ])->first();

        // Either should not save, or should have validation errors
        $hasErrors = $component->get('errors');
        $hasWarnings = $component->get('accessibilityWarnings');

        $this->assertTrue(
            $savedTheme === null || ! empty($hasErrors) || ! empty($hasWarnings),
            'Theme with no contrast should either not save or generate errors/warnings'
        );
    }
}
