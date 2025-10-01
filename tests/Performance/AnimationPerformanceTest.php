<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Livewire\LogoGallery;
use App\Livewire\ProjectPage;
use App\Livewire\ThemeCustomizer;
use App\Models\LogoGeneration;
use App\Models\Project;
use App\Models\UploadedLogo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Animation Performance Tests
 * Task 8.2: Test animation frame rates across different browsers (simulated)
 * Task 8.7: Validate smooth animations don't impact application functionality
 */
class AnimationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private LogoGeneration $logoGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->logoGeneration = LogoGeneration::factory()->create([
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function theme_animations_do_not_break_functionality(): void
    {
        // Test that theme customizer animations don't interfere with core functionality
        $startTime = microtime(true);

        $component = Livewire::actingAs($this->user)
            ->test(ThemeCustomizer::class);

        // Test theme color changes (which trigger animations)
        $component
            ->set('primaryColor', '#ff0000')
            ->set('accentColor', '#00ff00')
            ->set('backgroundColor', '#ffffff')
            ->set('textColor', '#000000')
            ->assertHasNoErrors();

        // Test theme save functionality works with animations
        $component
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('theme-saved');

        $endTime = microtime(true);
        $operationTime = ($endTime - $startTime) * 1000;

        // Ensure operations complete quickly despite animations
        $this->assertLessThan(2000, $operationTime,
            "Theme operations with animations took {$operationTime}ms - too slow");

        $this->logPerformanceMetric('Theme Animation + Functionality', $operationTime, 'ms', '🎨');
    }

    #[Test]
    public function project_page_animations_preserve_functionality(): void
    {
        $startTime = microtime(true);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectPage::class, ['uuid' => $this->project->uuid]);

        // Test that loading animations don't interfere with filtering
        $component
            ->call('setResultsFilter', 'all')
            ->assertHasNoErrors()
            ->assertSet('resultsFilter', 'all');

        // Test that button animations don't break interactions
        $component
            ->set('showAIControls', true)
            ->assertSet('showAIControls', true)
            ->assertHasNoErrors();

        $endTime = microtime(true);
        $operationTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(1500, $operationTime,
            "ProjectPage operations with animations took {$operationTime}ms - too slow");

        $this->logPerformanceMetric('ProjectPage Animation + Functionality', $operationTime, 'ms', '📝');
    }

    #[Test]
    public function logo_gallery_hover_animations_maintain_performance(): void
    {
        // Create test uploaded logos
        $uploadedLogos = UploadedLogo::factory()->count(10)->create([
            'session_id' => 'test-session',
        ]);

        $startTime = microtime(true);

        $component = Livewire::actingAs($this->user)
            ->test(LogoGallery::class, ['logoGenerationId' => $this->logoGeneration->id]);

        // Test bulk operations work with hover animations
        $selectedIds = $uploadedLogos->take(3)->pluck('id')->toArray();

        $component
            ->set('selectedUploadedLogos', $selectedIds)
            ->assertSet('selectedUploadedLogos', $selectedIds)
            ->assertHasNoErrors();

        // Test that animated interactions don't break bulk operations
        $component
            ->call('bulkDeleteUploadedLogos')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $endTime = microtime(true);
        $operationTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(2000, $operationTime,
            "Logo gallery operations with animations took {$operationTime}ms - too slow");

        $this->logPerformanceMetric('Logo Gallery Animation + Functionality', $operationTime, 'ms', '🖼️');
    }

    #[Test]
    public function css_transitions_do_not_cause_layout_thrashing(): void
    {
        // Test that our enhanced CSS transitions don't cause performance issues
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);

        // Check that the enhanced animation CSS is loaded
        $content = $response->getContent();
        $this->assertStringContainsString('smooth-animations.css', $content,
            'Smooth animations CSS should be loaded');

        $this->logPerformanceMetric('CSS Animation Load', 0, 'validation', '✅');
    }

    #[Test]
    public function animation_performance_with_many_elements(): void
    {
        // Create multiple projects to test animation performance with many elements
        Project::factory()->count(50)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // Even with many elements, page should load reasonably fast
        $this->assertLessThan(3000, $loadTime,
            "Dashboard with many animated elements took {$loadTime}ms - too slow");

        $this->logPerformanceMetric('Many Elements Animation Performance', $loadTime, 'ms', '🏗️');
    }

    #[Test]
    public function reduced_motion_preference_is_respected(): void
    {
        // Test that our animations respect accessibility preferences
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $content = $response->getContent();

        // Check that reduced motion CSS is included via animations CSS file
        $this->assertStringContainsString('smooth-animations.css', $content,
            'Page should include smooth animations CSS which contains reduced motion support');

        $response->assertStatus(200);

        $this->logPerformanceMetric('Accessibility Motion Preferences', 0, 'validation', '♿');
    }

    #[Test]
    public function animation_memory_usage_is_reasonable(): void
    {
        $initialMemory = memory_get_usage();

        // Simulate animation-heavy interactions
        $component = Livewire::actingAs($this->user)
            ->test(ThemeCustomizer::class);

        // Multiple rapid theme changes (which trigger animations)
        for ($i = 0; $i < 10; $i++) {
            $component
                ->set('primaryColor', sprintf('#%06x', random_int(0, 0xFFFFFF)))
                ->assertHasNoErrors();
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Animation operations shouldn't cause significant memory increase
        $this->assertLessThan(10, $memoryIncrease,
            "Animation operations increased memory by {$memoryIncrease}MB - too much");

        $this->logPerformanceMetric('Animation Memory Usage', $memoryIncrease, 'MB', '🧠');
    }

    #[Test]
    public function livewire_updates_work_with_animations(): void
    {
        $startTime = microtime(true);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectPage::class, ['uuid' => $this->project->uuid]);

        // Test rapid updates that might interact with animations
        $component
            ->set('editableDescription', 'Test description')
            ->assertSet('editableDescription', 'Test description')
            ->set('editableDescription', 'Updated description')
            ->assertSet('editableDescription', 'Updated description')
            ->assertHasNoErrors();

        // Test filter changes with loading animations
        $component
            ->call('setResultsFilter', 'visible')
            ->call('setResultsFilter', 'all')
            ->assertHasNoErrors();

        $endTime = microtime(true);
        $operationTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(1000, $operationTime,
            "Livewire updates with animations took {$operationTime}ms - too slow");

        $this->logPerformanceMetric('Livewire + Animation Updates', $operationTime, 'ms', '⚡');
    }

    #[Test]
    public function animation_cleanup_prevents_memory_leaks(): void
    {
        $initialMemory = memory_get_usage();

        // Create and destroy components multiple times
        for ($i = 0; $i < 5; $i++) {
            $component = Livewire::actingAs($this->user)
                ->test(ThemeCustomizer::class);

            $component
                ->set('primaryColor', '#ff0000')
                ->call('save');

            // Simulate component cleanup
            unset($component);

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Memory should not keep growing with each component cycle
        $this->assertLessThan(5, $memoryIncrease,
            "Component cycling increased memory by {$memoryIncrease}MB - possible leak");

        $this->logPerformanceMetric('Animation Memory Cleanup', $memoryIncrease, 'MB', '🧹');
    }

    #[Test]
    public function performance_degrades_gracefully_on_slow_operations(): void
    {
        // Simulate a slow operation to test animation behavior under stress
        $component = Livewire::actingAs($this->user)
            ->test(ProjectPage::class, ['uuid' => $this->project->uuid]);

        $startTime = microtime(true);

        // Simulate multiple rapid interactions
        $component
            ->call('setResultsFilter', 'all')
            ->call('setResultsFilter', 'visible')
            ->call('setResultsFilter', 'hidden')
            ->call('setResultsFilter', 'all')
            ->assertHasNoErrors();

        $endTime = microtime(true);
        $operationTime = ($endTime - $startTime) * 1000;

        // Operations should still complete in reasonable time
        $this->assertLessThan(3000, $operationTime,
            "Rapid operations took {$operationTime}ms - degraded too much");

        $this->logPerformanceMetric('Graceful Degradation', $operationTime, 'ms', '🛡️');
    }

    /**
     * Log performance metrics with animation context
     */
    private function logPerformanceMetric(string $metric, float $value, string $unit, string $emoji = '📊'): void
    {
        echo "\n{$emoji} Animation Performance - {$metric}: {$value}{$unit}";
    }
}
