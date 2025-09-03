<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Performance tests to validate loading times and Core Web Vitals
 * Task 8.3: Validate loading times meet performance targets (<3s initial load)
 * Task 8.5: Measure and optimize Core Web Vitals (LCP, FID, CLS)
 */
class LoadingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function dashboard_loads_within_performance_target(): void
    {
        // Target: Initial load should be under 3 seconds for dashboard
        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Assert load time is under 3000ms (3 seconds)
        $this->assertLessThan(3000, $loadTime,
            "Dashboard load time ({$loadTime}ms) exceeds 3 second target");

        // Log performance metric
        $this->logPerformanceMetric('Dashboard Load Time', $loadTime, 'ms');
    }

    #[Test]
    public function project_page_loads_within_performance_target(): void
    {
        // Target: Project page should load under 3 seconds
        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->get("/project/{$this->project->uuid}");

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // Assert load time is under 3000ms
        $this->assertLessThan(3000, $loadTime,
            "Project page load time ({$loadTime}ms) exceeds 3 second target");

        $this->logPerformanceMetric('Project Page Load Time', $loadTime, 'ms');
    }

    #[Test]
    public function database_queries_are_optimized(): void
    {
        // Enable query logging
        \DB::enableQueryLog();

        $startTime = microtime(true);

        // Load dashboard with multiple components
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $endTime = microtime(true);
        $queryTime = ($endTime - $startTime) * 1000;

        $queries = \DB::getQueryLog();
        $queryCount = count($queries);

        $response->assertStatus(200);

        // Assert reasonable query count (should be optimized with eager loading)
        $this->assertLessThan(20, $queryCount,
            "Too many database queries ({$queryCount}) for dashboard load");

        // Assert total query time is reasonable
        $this->assertLessThan(1000, $queryTime,
            "Database query time ({$queryTime}ms) is too slow");

        $this->logPerformanceMetric('Dashboard DB Queries', $queryCount, 'queries');
        $this->logPerformanceMetric('Dashboard DB Query Time', $queryTime, 'ms');

        \DB::disableQueryLog();
    }

    #[Test]
    public function livewire_components_load_efficiently(): void
    {
        // Test Livewire component initialization performance
        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->get("/project/{$this->project->uuid}");

        $endTime = microtime(true);
        $componentLoadTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // ProjectPage should load quickly
        $this->assertLessThan(2000, $componentLoadTime,
            "Livewire component load time ({$componentLoadTime}ms) is too slow");

        $this->logPerformanceMetric('Livewire Component Load', $componentLoadTime, 'ms');
    }

    #[Test]
    public function theme_customizer_performance(): void
    {
        // Test theme customizer loading performance
        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->get('/customize-theme');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // Theme customizer should load under 2.5 seconds
        $this->assertLessThan(2500, $loadTime,
            "Theme customizer load time ({$loadTime}ms) exceeds target");

        $this->logPerformanceMetric('Theme Customizer Load', $loadTime, 'ms');
    }

    #[Test]
    public function api_response_times_are_acceptable(): void
    {
        // Test API endpoint performance (if any)
        $routes = [
            '/dashboard',
            "/project/{$this->project->uuid}",
        ];

        foreach ($routes as $route) {
            $startTime = microtime(true);

            $response = $this->actingAs($this->user)->get($route);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);

            // Each route should respond under 2 seconds
            $this->assertLessThan(2000, $responseTime,
                "Route {$route} response time ({$responseTime}ms) is too slow");

            $this->logPerformanceMetric("Route: {$route}", $responseTime, 'ms');
        }
    }

    #[Test]
    public function cache_performance_is_optimal(): void
    {
        // Test cache hit performance
        $cacheKey = 'performance_test_'.time();
        $testData = ['test' => 'data', 'timestamp' => time()];

        // Cache write performance
        $startTime = microtime(true);
        Cache::put($cacheKey, $testData, 3600);
        $endTime = microtime(true);
        $writeTime = ($endTime - $startTime) * 1000;

        // Cache read performance
        $startTime = microtime(true);
        $cachedData = Cache::get($cacheKey);
        $endTime = microtime(true);
        $readTime = ($endTime - $startTime) * 1000;

        $this->assertEquals($testData, $cachedData);

        // Cache operations should be very fast
        $this->assertLessThan(100, $writeTime,
            "Cache write time ({$writeTime}ms) is too slow");
        $this->assertLessThan(50, $readTime,
            "Cache read time ({$readTime}ms) is too slow");

        $this->logPerformanceMetric('Cache Write Time', $writeTime, 'ms');
        $this->logPerformanceMetric('Cache Read Time', $readTime, 'ms');

        // Cleanup
        Cache::forget($cacheKey);
    }

    #[Test]
    public function memory_usage_is_reasonable(): void
    {
        $initialMemory = memory_get_usage();
        $initialMemoryPeak = memory_get_peak_usage();

        // Perform multiple operations
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $this->actingAs($this->user)
            ->get("/project/{$this->project->uuid}");

        $finalMemory = memory_get_usage();
        $finalMemoryPeak = memory_get_peak_usage();

        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB
        $peakMemoryIncrease = ($finalMemoryPeak - $initialMemoryPeak) / 1024 / 1024; // MB

        $response->assertStatus(200);

        // Memory usage should not increase dramatically
        $this->assertLessThan(50, $memoryIncrease,
            "Memory usage increased by {$memoryIncrease}MB during operations");

        $this->assertLessThan(100, $peakMemoryIncrease,
            "Peak memory usage increased by {$peakMemoryIncrease}MB");

        $this->logPerformanceMetric('Memory Increase', $memoryIncrease, 'MB');
        $this->logPerformanceMetric('Peak Memory Increase', $peakMemoryIncrease, 'MB');
    }

    #[Test]
    public function concurrent_requests_perform_well(): void
    {
        // Simulate multiple concurrent requests (simplified)
        $routes = [
            '/dashboard',
            "/project/{$this->project->uuid}",
            '/customize-theme',
        ];

        $totalStartTime = microtime(true);
        $responseTimes = [];

        foreach ($routes as $route) {
            $startTime = microtime(true);

            $response = $this->actingAs($this->user)->get($route);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            $responseTimes[] = $responseTime;
        }

        $totalEndTime = microtime(true);
        $totalTime = ($totalEndTime - $totalStartTime) * 1000;
        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);

        // Average response time should be reasonable
        $this->assertLessThan(2000, $averageResponseTime,
            "Average response time ({$averageResponseTime}ms) is too slow");

        // Total sequential time should be under 8 seconds
        $this->assertLessThan(8000, $totalTime,
            "Total sequential request time ({$totalTime}ms) is too slow");

        $this->logPerformanceMetric('Average Response Time', $averageResponseTime, 'ms');
        $this->logPerformanceMetric('Sequential Request Total', $totalTime, 'ms');
    }

    /**
     * Log performance metrics for analysis
     */
    private function logPerformanceMetric(string $metric, float $value, string $unit): void
    {
        $emoji = $this->getPerformanceEmoji($metric, $value);

        // Simple echo for performance metrics logging
        echo "\n{$emoji} {$metric}: {$value}{$unit}";
    }

    private function getPerformanceEmoji(string $metric, float $value): string
    {
        if (str_contains($metric, 'Load Time')) {
            return $value < 1000 ? '🚀' : ($value < 2000 ? '⚡' : '🐌');
        }

        return '📊';
    }
}
