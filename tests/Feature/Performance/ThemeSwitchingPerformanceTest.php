<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Livewire\ThemeCustomizer;
use App\Models\User;
use App\Models\UserThemePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Theme Switching Performance Tests
 * Task 12.4: Test theme switching performance (<500ms)
 */
class ThemeSwitchingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function theme_switching_completes_within_500ms(): void
    {
        $this->actingAs($this->user);

        $themes = ['default', 'ocean', 'sunset', 'forest', 'vintage'];
        $totalTime = 0;
        $switchCount = count($themes);

        foreach ($themes as $theme) {
            $startTime = microtime(true);

            Livewire::test(ThemeCustomizer::class)
                ->set('themeName', $theme)
                ->set('isDarkMode', false)
                ->call('applyTheme')
                ->assertHasNoErrors();

            $endTime = microtime(true);
            $switchTime = ($endTime - $startTime) * 1000;
            $totalTime += $switchTime;

            $this->assertLessThan(500, $switchTime,
                "Theme switch to '{$theme}' took {$switchTime}ms - exceeds 500ms limit");

            $this->logPerformanceMetric("Theme Switch: {$theme}", $switchTime, 'ms', '⚡');
        }

        $averageTime = $totalTime / $switchCount;
        $this->logPerformanceMetric('Average Theme Switch Time', $averageTime, 'ms', '📊');
    }

    #[Test]
    public function theme_switching_with_dark_mode_toggle_is_fast(): void
    {
        $this->actingAs($this->user);

        $startTime = microtime(true);

        Livewire::test(ThemeCustomizer::class)
            ->set('themeName', 'ocean')
            ->set('isDarkMode', true)
            ->call('applyTheme')
            ->assertHasNoErrors();

        $endTime = microtime(true);
        $switchTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(500, $switchTime,
            "Theme switch with dark mode took {$switchTime}ms - exceeds 500ms limit");

        $this->logPerformanceMetric('Theme Switch + Dark Mode', $switchTime, 'ms', '🌙');
    }

    #[Test]
    public function rapid_theme_switching_maintains_performance(): void
    {
        $this->actingAs($this->user);

        $themes = ['default', 'ocean', 'sunset', 'forest', 'vintage'];
        $startTime = microtime(true);

        $component = Livewire::test(ThemeCustomizer::class);

        foreach ($themes as $theme) {
            $component
                ->set('themeName', $theme)
                ->call('applyTheme')
                ->assertHasNoErrors();
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / count($themes);

        $this->assertLessThan(500, $averageTime,
            "Average rapid switch time of {$averageTime}ms exceeds 500ms limit");

        $this->logPerformanceMetric('Rapid Switch (5 themes)', $totalTime, 'ms', '🚀');
        $this->logPerformanceMetric('Average Rapid Switch', $averageTime, 'ms', '⚡');
    }

    #[Test]
    public function theme_persistence_check_is_fast(): void
    {
        // Create preference first
        UserThemePreference::create([
            'user_id' => $this->user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => false,
        ]);

        $this->actingAs($this->user);

        $startTime = microtime(true);

        // Load theme customizer - should retrieve existing preference
        Livewire::test(ThemeCustomizer::class)
            ->assertSet('themeName', 'ocean')
            ->assertSet('isDarkMode', false);

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(200, $loadTime,
            "Theme preference retrieval took {$loadTime}ms - should be under 200ms");

        $this->logPerformanceMetric('Theme Preference Load', $loadTime, 'ms', '📂');
    }

    #[Test]
    public function css_file_path_generation_is_fast(): void
    {
        $preference = UserThemePreference::create([
            'user_id' => $this->user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => false,
        ]);

        $iterations = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $cssPath = $preference->getThemeCssPath();
            $this->assertStringContainsString('ocean.css', $cssPath);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / $iterations;

        $this->assertLessThan(1, $averageTime,
            "CSS path generation took {$averageTime}ms per call - should be under 1ms");

        $this->logPerformanceMetric("CSS Path Gen (x{$iterations})", $totalTime, 'ms', '🔗');
        $this->logPerformanceMetric('Average CSS Path Gen', $averageTime, 'ms', '⚡');
    }

    #[Test]
    public function theme_change_database_update_is_fast(): void
    {
        $this->actingAs($this->user);

        // Create initial preference
        UserThemePreference::create([
            'user_id' => $this->user->id,
            'theme_name' => 'default',
            'is_dark_mode' => false,
        ]);

        $startTime = microtime(true);

        // Update theme preference
        Livewire::test(ThemeCustomizer::class)
            ->set('themeName', 'sunset')
            ->set('isDarkMode', true)
            ->call('applyTheme')
            ->assertHasNoErrors()
            ->assertDispatched('theme-applied');

        $endTime = microtime(true);
        $updateTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(300, $updateTime,
            "Theme preference update took {$updateTime}ms - should be under 300ms");

        // Verify database was updated
        $preference = UserThemePreference::where('user_id', $this->user->id)->first();
        expect($preference->theme_name)->toBe('sunset');
        expect($preference->is_dark_mode)->toBeTrue();

        $this->logPerformanceMetric('Theme DB Update', $updateTime, 'ms', '💾');
    }

    #[Test]
    public function all_18_themes_switch_within_performance_threshold(): void
    {
        $this->actingAs($this->user);

        $themeService = app(\App\Services\ThemeService::class);
        $allThemes = $themeService->getPredefinedThemes();

        $slowThemes = [];
        $totalTime = 0;

        foreach ($allThemes as $theme) {
            $startTime = microtime(true);

            Livewire::test(ThemeCustomizer::class)
                ->set('themeName', $theme['name'])
                ->set('isDarkMode', $theme['is_dark_mode'])
                ->call('applyTheme')
                ->assertHasNoErrors();

            $endTime = microtime(true);
            $switchTime = ($endTime - $startTime) * 1000;
            $totalTime += $switchTime;

            if ($switchTime >= 500) {
                $slowThemes[] = [
                    'name' => $theme['name'],
                    'time' => $switchTime,
                ];
            }
        }

        $averageTime = $totalTime / count($allThemes);

        $this->assertEmpty($slowThemes,
            'Some themes exceeded 500ms threshold: '.json_encode($slowThemes));

        $this->logPerformanceMetric('All 18 Themes Total', $totalTime, 'ms', '🎨');
        $this->logPerformanceMetric('All 18 Themes Average', $averageTime, 'ms', '📊');
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetric(string $metric, float $value, string $unit, string $emoji = '📊'): void
    {
        echo "\n{$emoji} {$metric}: ".number_format($value, 2).$unit;
    }
}
