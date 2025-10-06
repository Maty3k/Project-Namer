<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ThemeService;
use Tests\TestCase;

/**
 * Accessibility tests for theme contrast and readability.
 *
 * NOTE: These tests are being phased out as themes migrate to FluxUI accent color system.
 * See CustomThemeAccessibilityTest for FluxUI-compatible accessibility tests.
 *
 * @deprecated Will be removed after complete migration to FluxUI theming
 */
final class ThemeAccessibilityTest extends TestCase
{
    private ThemeService $themeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themeService = new ThemeService;
        $this->markTestSkipped('Skipping old theme accessibility tests during FluxUI migration. See CustomThemeAccessibilityTest for FluxUI tests.');
    }

    /**
     * Test that all predefined themes have valid FluxUI accent colors.
     */
    public function test_all_predefined_themes_meet_wcag_aa_standards(): void
    {
        $themes = $this->themeService->getPredefinedThemes();
        $failedThemes = [];

        foreach ($themes as $theme) {
            // FluxUI themes use zinc backgrounds, test accent against standard backgrounds
            $lightBg = '#ffffff';
            $darkBg = '#09090b';  // zinc-950

            $background = $theme['is_dark_mode'] ? $darkBg : $lightBg;

            $accentBgRatio = $this->themeService->calculateContrastRatio(
                $theme['accent_color'],
                $background
            );

            $accentForegroundRatio = $this->themeService->calculateContrastRatio(
                $theme['accent_foreground_color'],
                $theme['accent_color']
            );

            // WCAG AA requires 3:1 for UI components
            if ($accentBgRatio < 3.0) {
                $failedThemes[] = [
                    'theme' => $theme['name'],
                    'issue' => 'Accent color contrast with background',
                    'ratio' => $accentBgRatio,
                    'required' => 3.0,
                ];
            }

            // Accent foreground should have good contrast with accent color
            if ($accentForegroundRatio < 4.5) {
                $failedThemes[] = [
                    'theme' => $theme['name'],
                    'issue' => 'Accent foreground contrast',
                    'ratio' => $accentForegroundRatio,
                    'required' => 4.5,
                ];
            }
        }

        $this->assertEmpty($failedThemes,
            'The following themes failed accessibility requirements: '.json_encode($failedThemes, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Test individual themes for specific accessibility requirements.
     *
     * @dataProvider themeDataProvider
     */
    public function test_individual_theme_accessibility(string $themeName, array $themeData): void
    {
        $textBgRatio = $this->themeService->calculateContrastRatio(
            $themeData['text_color'],
            $themeData['background_color']
        );

        $primaryBgRatio = $this->themeService->calculateContrastRatio(
            $themeData['primary_color'],
            $themeData['background_color']
        );

        $accentBgRatio = $this->themeService->calculateContrastRatio(
            $themeData['accent_color'],
            $themeData['background_color']
        );

        // Test text contrast (AA standard: 4.5:1)
        $this->assertGreaterThanOrEqual(4.5, $textBgRatio,
            "Theme '{$themeName}' text contrast ratio {$textBgRatio}:1 is below WCAG AA standard (4.5:1)"
        );

        // Test primary color contrast (AA standard for UI components: 3:1)
        $this->assertGreaterThanOrEqual(3.0, $primaryBgRatio,
            "Theme '{$themeName}' primary color contrast ratio {$primaryBgRatio}:1 is below WCAG AA standard (3:1)"
        );

        // Test accent color contrast (AA standard for UI components: 3:1)
        $this->assertGreaterThanOrEqual(3.0, $accentBgRatio,
            "Theme '{$themeName}' accent color contrast ratio {$accentBgRatio}:1 is below WCAG AA standard (3:1)"
        );

        // Verify WCAG level classification
        $textLevel = $this->themeService->getWcagLevel($textBgRatio);
        $this->assertContains($textLevel, ['AA', 'AAA'],
            "Theme '{$themeName}' text color does not meet minimum WCAG standards"
        );

        $primaryLevel = $this->themeService->getWcagLevel($primaryBgRatio);
        $this->assertNotEquals('FAIL', $primaryLevel,
            "Theme '{$themeName}' primary color fails basic accessibility requirements"
        );
    }

    /**
     * Test that AAA-compliant themes maintain higher standards.
     */
    public function test_aaa_compliant_themes_exceed_standards(): void
    {
        $themes = $this->themeService->getPredefinedThemes();

        foreach ($themes as $theme) {
            $textBgRatio = $this->themeService->calculateContrastRatio(
                $theme['text_color'],
                $theme['background_color']
            );

            if ($textBgRatio >= 7.0) {
                $wcagLevel = $this->themeService->getWcagLevel($textBgRatio);
                $this->assertEquals('AAA', $wcagLevel,
                    "Theme '{$theme['name']}' has AAA-level contrast but is not classified as AAA"
                );
            }
        }
    }

    /**
     * Test accessibility score calculation for all themes.
     */
    public function test_theme_accessibility_scores(): void
    {
        $themes = $this->themeService->getPredefinedThemes();
        $lowScoringThemes = [];

        foreach ($themes as $theme) {
            $score = $this->themeService->calculateAccessibilityScore(
                $theme['primary_color'],
                $theme['background_color'],
                $theme['text_color']
            );

            // Accessibility score should be at least 0.6 (60%) for acceptable themes
            if ($score < 0.6) {
                $lowScoringThemes[] = [
                    'theme' => $theme['name'],
                    'score' => $score,
                ];
            }

            $this->assertGreaterThanOrEqual(0.6, $score,
                "Theme '{$theme['name']}' has accessibility score {$score}, below acceptable threshold of 0.6"
            );
        }

        $this->assertEmpty($lowScoringThemes,
            'Themes with low accessibility scores: '.json_encode($lowScoringThemes)
        );
    }

    /**
     * Test that dark mode themes have appropriate contrast.
     */
    public function test_dark_mode_themes_contrast(): void
    {
        $themes = $this->themeService->getPredefinedThemes();
        $darkThemes = array_filter($themes, fn ($theme) => $theme['is_dark_mode'] === true);

        $this->assertNotEmpty($darkThemes, 'No dark mode themes found to test');

        foreach ($darkThemes as $theme) {
            $textBgRatio = $this->themeService->calculateContrastRatio(
                $theme['text_color'],
                $theme['background_color']
            );

            // Dark themes often have higher contrast requirements due to eye strain
            $this->assertGreaterThanOrEqual(7.0, $textBgRatio,
                "Dark theme '{$theme['name']}' should have AAA-level contrast (7:1) but has {$textBgRatio}:1"
            );
        }
    }

    /**
     * Test seasonal themes maintain accessibility standards.
     */
    public function test_seasonal_themes_accessibility(): void
    {
        $seasonalThemes = $this->themeService->getThemesByCategory('seasonal');

        $this->assertNotEmpty($seasonalThemes, 'No seasonal themes found to test');

        foreach ($seasonalThemes as $theme) {
            $textBgRatio = $this->themeService->calculateContrastRatio(
                $theme['text_color'],
                $theme['background_color']
            );

            $this->assertGreaterThanOrEqual(4.5, $textBgRatio,
                "Seasonal theme '{$theme['name']}' fails AA accessibility standards with ratio {$textBgRatio}:1"
            );

            // Special validation for Halloween theme (dark theme)
            if ($theme['name'] === 'halloween') {
                $this->assertTrue($theme['is_dark_mode'],
                    'Halloween theme should be marked as dark mode'
                );

                $this->assertGreaterThanOrEqual(7.0, $textBgRatio,
                    'Halloween theme should meet AAA standards for dark themes'
                );
            }
        }
    }

    /**
     * Test that all themes provide accessibility feedback.
     */
    public function test_theme_accessibility_feedback(): void
    {
        $themes = $this->themeService->getPredefinedThemes();

        foreach ($themes as $theme) {
            $feedback = $this->themeService->generateAccessibilityFeedback(
                $theme['primary_color'],
                $theme['background_color'],
                $theme['text_color']
            );

            $this->assertArrayHasKey('warnings', $feedback);
            $this->assertArrayHasKey('suggestions', $feedback);
            $this->assertIsArray($feedback['warnings']);
            $this->assertIsArray($feedback['suggestions']);

            // Themes that meet standards should have no warnings
            $textBgRatio = $this->themeService->calculateContrastRatio(
                $theme['text_color'],
                $theme['background_color']
            );

            if ($textBgRatio >= 4.5) {
                $textWarnings = array_filter($feedback['warnings'],
                    fn ($warning) => str_contains($warning, 'Text color')
                );
                $this->assertEmpty($textWarnings,
                    "Theme '{$theme['name']}' meets standards but has text warnings: ".
                    implode(', ', $textWarnings)
                );
            }
        }
    }

    /**
     * Provide theme data for parameterized tests.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function themeDataProvider(): array
    {
        $themeService = new ThemeService;
        $themes = $themeService->getPredefinedThemes();

        $data = [];
        foreach ($themes as $theme) {
            $data[$theme['name']] = [$theme['name'], $theme];
        }

        return $data;
    }

    /**
     * Test color validation functionality.
     */
    public function test_color_validation(): void
    {
        // Valid colors
        $this->assertTrue($this->themeService->validateColor('#ffffff'));
        $this->assertTrue($this->themeService->validateColor('#000000'));
        $this->assertTrue($this->themeService->validateColor('#3B82F6'));

        // Invalid colors
        $this->assertFalse($this->themeService->validateColor('ffffff'));
        $this->assertFalse($this->themeService->validateColor('#fff'));
        $this->assertFalse($this->themeService->validateColor('#gggggg'));
        $this->assertFalse($this->themeService->validateColor('blue'));
        $this->assertFalse($this->themeService->validateColor(''));
    }
}
