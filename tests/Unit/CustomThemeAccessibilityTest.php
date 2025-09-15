<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ThemeService;
use Tests\TestCase;

/**
 * Accessibility tests for custom theme combinations and edge cases.
 *
 * These tests validate accessibility for user-generated color combinations
 * and edge cases that might not be covered by predefined themes.
 */
final class CustomThemeAccessibilityTest extends TestCase
{
    private ThemeService $themeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themeService = new ThemeService;
    }

    /**
     * Test custom color combinations for accessibility compliance.
     *
     * @dataProvider customColorCombinationProvider
     */
    public function test_custom_color_combinations_accessibility(
        array $colorCombination,
        bool $shouldPassAA,
        bool $shouldPassAAA
    ): void {
        $textBgRatio = $this->themeService->calculateContrastRatio(
            $colorCombination['text_color'],
            $colorCombination['background_color']
        );

        $primaryBgRatio = $this->themeService->calculateContrastRatio(
            $colorCombination['primary_color'],
            $colorCombination['background_color']
        );

        if ($shouldPassAA) {
            $this->assertGreaterThanOrEqual(4.5, $textBgRatio,
                'Custom theme should pass WCAG AA text contrast requirements'
            );
            $this->assertGreaterThanOrEqual(3.0, $primaryBgRatio,
                'Custom theme should pass WCAG AA primary color contrast requirements'
            );
        } else {
            $this->assertLessThan(4.5, $textBgRatio,
                'Custom theme should fail WCAG AA requirements as expected'
            );
        }

        if ($shouldPassAAA) {
            $this->assertGreaterThanOrEqual(7.0, $textBgRatio,
                'Custom theme should pass WCAG AAA text contrast requirements'
            );
        }
    }

    /**
     * Test accessibility feedback for problematic color combinations.
     */
    public function test_accessibility_feedback_for_poor_combinations(): void
    {
        $problematicCombinations = [
            // Light gray text on white background
            [
                'primary_color' => '#cccccc',
                'background_color' => '#ffffff',
                'text_color' => '#dddddd',
            ],
            // Dark blue on black background
            [
                'primary_color' => '#000080',
                'background_color' => '#000000',
                'text_color' => '#003366',
            ],
            // Yellow on white background
            [
                'primary_color' => '#ffff00',
                'background_color' => '#ffffff',
                'text_color' => '#ffff66',
            ],
        ];

        foreach ($problematicCombinations as $combination) {
            $feedback = $this->themeService->generateAccessibilityFeedback(
                $combination['primary_color'],
                $combination['background_color'],
                $combination['text_color']
            );

            $this->assertNotEmpty($feedback['warnings'],
                'Problematic color combination should generate warnings'
            );
            $this->assertNotEmpty($feedback['suggestions'],
                'Problematic color combination should generate improvement suggestions'
            );
        }
    }

    /**
     * Test edge cases in contrast calculation.
     */
    public function test_contrast_calculation_edge_cases(): void
    {
        // Pure black and white should have maximum contrast
        $maxContrast = $this->themeService->calculateContrastRatio('#000000', '#ffffff');
        $this->assertEquals(21.0, $maxContrast, 'Black on white should have 21:1 contrast ratio');

        // Same colors should have minimum contrast
        $minContrast = $this->themeService->calculateContrastRatio('#888888', '#888888');
        $this->assertEquals(1.0, $minContrast, 'Same colors should have 1:1 contrast ratio');

        // Test with various gray combinations
        $grayContrasts = [
            ['#000000', '#333333'], // Very dark
            ['#666666', '#999999'], // Mid grays
            ['#cccccc', '#ffffff'], // Light grays
        ];

        foreach ($grayContrasts as [$color1, $color2]) {
            $ratio = $this->themeService->calculateContrastRatio($color1, $color2);
            $this->assertGreaterThan(1.0, $ratio,
                "Gray combination {$color1} and {$color2} should have some contrast"
            );
            $this->assertLessThanOrEqual(21.0, $ratio,
                'Contrast ratio should not exceed theoretical maximum'
            );
        }
    }

    /**
     * Test WCAG level classification accuracy.
     */
    public function test_wcag_level_classification(): void
    {
        $testCases = [
            // AAA level
            ['ratio' => 7.5, 'expected' => 'AAA'],
            ['ratio' => 10.0, 'expected' => 'AAA'],
            ['ratio' => 21.0, 'expected' => 'AAA'],

            // AA level
            ['ratio' => 4.5, 'expected' => 'AA'],
            ['ratio' => 6.9, 'expected' => 'AA'],

            // A level (obsolete but still classified)
            ['ratio' => 3.0, 'expected' => 'A'],
            ['ratio' => 4.4, 'expected' => 'A'],

            // Fail level
            ['ratio' => 2.9, 'expected' => 'FAIL'],
            ['ratio' => 1.5, 'expected' => 'FAIL'],
            ['ratio' => 1.0, 'expected' => 'FAIL'],
        ];

        foreach ($testCases as $case) {
            $level = $this->themeService->getWcagLevel($case['ratio']);
            $this->assertEquals($case['expected'], $level,
                "Ratio {$case['ratio']} should be classified as {$case['expected']}"
            );
        }
    }

    /**
     * Test accessibility score consistency.
     */
    public function test_accessibility_score_consistency(): void
    {
        // Perfect accessibility (21:1 contrast)
        $perfectScore = $this->themeService->calculateAccessibilityScore(
            '#000000', '#ffffff', '#000000'
        );
        $this->assertEquals(1.0, $perfectScore, 'Perfect contrast should yield score of 1.0');

        // Poor accessibility
        $poorScore = $this->themeService->calculateAccessibilityScore(
            '#cccccc', '#ffffff', '#dddddd'
        );
        $this->assertLessThan(0.5, $poorScore, 'Poor contrast should yield low score');

        // Score should increase with better contrast
        $betterScore = $this->themeService->calculateAccessibilityScore(
            '#333333', '#ffffff', '#111111'
        );
        $this->assertGreaterThan($poorScore, $betterScore,
            'Better contrast should yield higher accessibility score'
        );
    }

    /**
     * Test color validation edge cases.
     */
    public function test_color_validation_edge_cases(): void
    {
        $validColors = [
            '#000000', '#ffffff', '#FF0000', '#00ff00', '#0000FF',
            '#123456', '#abcdef', '#ABCDEF', '#999999',
        ];

        $invalidColors = [
            '', '#', '#fff', '#ffff', '#fffff', '#ggg111',
            'ffffff', 'blue', 'rgb(255,0,0)', 'hsl(0,100%,50%)',
            '#12345', '#1234567', '#xyz123',
        ];

        foreach ($validColors as $color) {
            $this->assertTrue($this->themeService->validateColor($color),
                "Color {$color} should be valid"
            );
        }

        foreach ($invalidColors as $color) {
            $this->assertFalse($this->themeService->validateColor($color),
                "Color '{$color}' should be invalid"
            );
        }
    }

    /**
     * Test CSS property generation for custom themes.
     */
    public function test_css_property_generation(): void
    {
        $themeData = [
            'primary_color' => '#3b82f6',
            'accent_color' => '#10b981',
            'background_color' => '#ffffff',
            'text_color' => '#111827',
        ];

        $css = $this->themeService->generateCssProperties($themeData);

        $this->assertStringContainsString('--color-primary: #3b82f6', $css);
        $this->assertStringContainsString('--color-accent: #10b981', $css);
        $this->assertStringContainsString('--color-background: #ffffff', $css);
        $this->assertStringContainsString('--color-text: #111827', $css);
        $this->assertStringStartsWith(':root {', $css);
        $this->assertStringEndsWith('}', $css);
    }

    /**
     * Test extreme contrast combinations.
     */
    public function test_extreme_contrast_combinations(): void
    {
        // Test very high contrast combinations
        $highContrastCombos = [
            ['#000000', '#ffffff'], // Classic black and white
            ['#ffffff', '#000000'], // Inverted
            ['#001122', '#ffffff'], // Very dark blue on white
            ['#000000', '#ffffcc'], // Black on light yellow
        ];

        foreach ($highContrastCombos as [$color1, $color2]) {
            $ratio = $this->themeService->calculateContrastRatio($color1, $color2);
            $this->assertGreaterThan(10.0, $ratio,
                "High contrast combination {$color1} and {$color2} should have very high ratio"
            );

            $level = $this->themeService->getWcagLevel($ratio);
            $this->assertEquals('AAA', $level,
                'High contrast combination should achieve AAA level'
            );
        }
    }

    /**
     * Provide custom color combinations for testing.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function customColorCombinationProvider(): array
    {
        return [
            'high_contrast_dark' => [
                [
                    'primary_color' => '#ffffff',
                    'background_color' => '#000000',
                    'text_color' => '#ffffff',
                ],
                true,  // Should pass AA
                true,  // Should pass AAA
            ],
            'good_contrast_blue' => [
                [
                    'primary_color' => '#1e40af',
                    'background_color' => '#f8fafc',
                    'text_color' => '#1f2937',
                ],
                true,  // Should pass AA
                false, // Should not pass AAA
            ],
            'poor_contrast_light' => [
                [
                    'primary_color' => '#e5e7eb',
                    'background_color' => '#ffffff',
                    'text_color' => '#d1d5db',
                ],
                false, // Should not pass AA
                false, // Should not pass AAA
            ],
            'borderline_contrast' => [
                [
                    'primary_color' => '#6b7280',
                    'background_color' => '#ffffff',
                    'text_color' => '#374151',
                ],
                true,  // Should pass AA (borderline)
                false, // Should not pass AAA
            ],
            'red_on_dark_green' => [
                [
                    'primary_color' => '#dc2626',
                    'background_color' => '#065f46',
                    'text_color' => '#f87171',
                ],
                false, // Should not pass AA (text contrast too low)
                false, // Should not pass AAA
            ],
        ];
    }
}
