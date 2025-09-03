<?php

declare(strict_types=1);

use App\Models\AIGeneration;
use App\Models\AIModelPerformance;
use App\Models\GenerationSession;
use App\Models\Project;
use App\Models\User;
use App\Models\UserAIPreferences;
use App\Services\AI\AIAnalyticsService;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->service = app(AIAnalyticsService::class);
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    // Clear cache before each test
    Cache::flush();
});

describe('AIAnalyticsService', function (): void {
    it('can get user analytics overview', function (): void {
        // Create test data
        $session = GenerationSession::create([
            'session_id' => 'test_session_1',
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'business_description' => 'Test business',
            'generation_mode' => 'creative',
            'status' => 'completed',
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'deep_thinking' => false,
        ]);

        AIGeneration::factory()->completed()->create([
            'generation_session_id' => $session->session_id,
            'user_id' => $this->user->id,
            'models_requested' => ['gpt-4'],
            'status' => 'completed',
            'results_data' => [
                'names' => ['TestName1', 'TestName2', 'TestName3'],
                'metadata' => ['source' => 'dashboard'],
            ],
            'total_response_time_ms' => 1500,
            'total_cost_cents' => 5,
        ]);

        $analytics = $this->service->getUserAnalytics($this->user, 'month');

        expect($analytics)->toBeArray()
            ->and($analytics)->toHaveKeys([
                'overview',
                'model_usage',
                'generation_trends',
                'performance_metrics',
                'cost_analysis',
                'success_rates',
                'preferences_evolution',
            ]);

        expect($analytics['overview'])->toHaveKey('total_generations');
    });

    it('can get system-wide analytics', function (): void {
        // Create test data across multiple users
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $project = Project::factory()->create(['user_id' => $user->id]);
            $session = GenerationSession::create([
                'session_id' => "test_session_{$user->id}",
                'user_id' => $user->id,
                'project_id' => $project->id,
                'business_description' => 'Test business',
                'generation_mode' => 'creative',
                'status' => 'completed',
                'requested_models' => ['gpt-4'],
                'generation_strategy' => 'quick',
                'deep_thinking' => false,
            ]);

            AIGeneration::factory()->completed()->create([
                'generation_session_id' => $session->session_id,
                'user_id' => $user->id,
                'models_requested' => ['gpt-4'],
                'status' => 'completed',
                'results_data' => ['names' => ['Name1', 'Name2']],
                'total_response_time_ms' => 1200,
                'total_cost_cents' => 3,
            ]);
        }

        $analytics = $this->service->getSystemAnalytics('month');

        expect($analytics)->toBeArray()
            ->and($analytics)->toHaveKeys([
                'overview',
                'model_performance',
                'usage_patterns',
                'cost_breakdown',
                'error_analysis',
                'user_engagement',
                'capacity_metrics',
            ]);
    });

    it('can get real-time metrics', function (): void {
        // Create active generation
        $session = GenerationSession::create([
            'session_id' => 'active_session',
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'business_description' => 'Active generation',
            'generation_mode' => 'creative',
            'status' => 'pending',
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'deep_thinking' => false,
        ]);

        AIGeneration::factory()->running()->create([
            'generation_session_id' => $session->session_id,
            'user_id' => $this->user->id,
            'models_requested' => ['gpt-4'],
            'status' => 'running',
            'results_data' => [],
            'total_response_time_ms' => null,
            'total_cost_cents' => 0,
        ]);

        $metrics = $this->service->getRealTimeMetrics();

        expect($metrics)->toBeArray()
            ->and($metrics)->toHaveKeys([
                'active_generations',
                'generations_last_hour',
                'average_response_time',
                'error_rate',
                'top_models',
                'queue_depth',
            ]);

        expect($metrics['active_generations'])->toBeGreaterThan(0);
    });

    it('can track model performance over time', function (): void {
        // Create performance data for different models
        $models = ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'];

        foreach ($models as $model) {
            AIModelPerformance::create([
                'user_id' => $this->user->id,
                'model_name' => $model,
                'total_requests' => 10,
                'successful_requests' => 9,
                'failed_requests' => 1,
                'average_response_time_ms' => random_int(1000, 3000),
                'total_cost_cents' => random_int(50, 150),
                'last_used_at' => now(),
                'performance_metrics' => json_encode([
                    'quality_score' => random_int(85, 98) / 10,
                    'user_satisfaction' => random_int(80, 95) / 10,
                ]),
            ]);
        }

        $analytics = $this->service->getUserAnalytics($this->user, 'month');

        expect($analytics['model_usage'])->toBeArray();
        expect($analytics['performance_metrics'])->toBeArray();
    });

    it('can analyze cost trends', function (): void {
        // Create cost data over multiple days
        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays($i);
            $session = GenerationSession::create([
                'session_id' => "cost_session_{$i}",
                'user_id' => $this->user->id,
                'project_id' => $this->project->id,
                'business_description' => 'Cost analysis test',
                'generation_mode' => 'creative',
                'status' => 'completed',
                'requested_models' => ['gpt-4'],
                'generation_strategy' => 'quick',
                'deep_thinking' => false,
                'created_at' => $date,
            ]);

            AIGeneration::factory()->completed()->create([
                'generation_session_id' => $session->session_id,
                'user_id' => $this->user->id,
                'models_requested' => ['gpt-4'],
                'status' => 'completed',
                'results_data' => ['names' => ['Name1', 'Name2']],
                'total_response_time_ms' => 1500,
                'total_cost_cents' => ($i + 1), // Increasing cost in cents
                'created_at' => $date,
            ]);
        }

        $analytics = $this->service->getUserAnalytics($this->user, 'week');

        expect($analytics['cost_analysis'])->toBeArray()
            ->and($analytics['cost_analysis'])->toHaveKey('total_cost')
            ->and($analytics['cost_analysis']['total_cost'])->toBeGreaterThan(0);
    });

    it('can track user preference evolution', function (): void {
        // Create user preferences at different times
        UserAIPreferences::create([
            'user_id' => $this->user->id,
            'preferred_models' => ['gpt-4'],
            'model_priorities' => ['gpt-4' => 1],
            'default_generation_mode' => 'creative',
            'deep_thinking_preference' => false,
            'custom_parameters' => [],
            'created_at' => now()->subWeeks(2),
        ]);

        $analytics = $this->service->getUserAnalytics($this->user, 'month');

        expect($analytics['preferences_evolution'])->toBeArray();
    });

    it('can handle empty data gracefully', function (): void {
        $emptyUser = User::factory()->create();

        $analytics = $this->service->getUserAnalytics($emptyUser, 'month');

        expect($analytics)->toBeArray();
        expect($analytics['overview']['total_generations'])->toBe(0);
        expect($analytics['cost_analysis']['total_cost'])->toBe(0.0);
    });

    it('can filter analytics by different time periods', function (): void {
        // Clear cache to ensure clean state
        Cache::flush();

        // Create a dedicated test user to avoid data contamination
        $testUser = User::factory()->create();
        $testProject = Project::factory()->create(['user_id' => $testUser->id]);

        // Create data from different time periods - make sure old data is definitely outside week range
        $oldGeneration = AIGeneration::factory()->completed()->create([
            'generation_session_id' => 'old_session_period_test',
            'user_id' => $testUser->id,
            'project_id' => $testProject->id,
            'models_requested' => ['gpt-4'],
            'status' => 'completed',
            'created_at' => now()->subWeeks(3), // 3 weeks ago to ensure it's outside current week
        ]);

        $recentGeneration = AIGeneration::factory()->completed()->create([
            'generation_session_id' => 'recent_session_period_test',
            'user_id' => $testUser->id,
            'project_id' => $testProject->id,
            'models_requested' => ['gpt-4'],
            'status' => 'completed',
            'created_at' => now()->subDays(1),
        ]);

        // Verify the generations were created with correct timestamps
        expect($oldGeneration->created_at)->toBeLessThan(now()->subWeeks(2));
        expect($recentGeneration->created_at)->toBeGreaterThan(now()->subDays(2));

        // Test different periods
        $weekAnalytics = $this->service->getUserAnalytics($testUser, 'week');
        $allTimeAnalytics = $this->service->getUserAnalytics($testUser, 'all');

        expect($weekAnalytics['overview']['total_generations'])
            ->toBe(1) // Should only include recent generation
            ->and($allTimeAnalytics['overview']['total_generations'])
            ->toBe(2) // Should include both generations
            ->and($weekAnalytics['overview']['total_generations'])
            ->toBeLessThan($allTimeAnalytics['overview']['total_generations']);
    });

    it('caches analytics results to improve performance', function (): void {
        $analytics1 = $this->service->getUserAnalytics($this->user, 'month');

        // Second call should be cached
        $start = microtime(true);
        $analytics2 = $this->service->getUserAnalytics($this->user, 'month');
        $duration = microtime(true) - $start;

        expect($analytics1)->toEqual($analytics2);
        expect($duration)->toBeLessThan(0.01); // Should be very fast from cache
    });

    it('can export analytics data', function (): void {
        // Create sample data
        $session = GenerationSession::create([
            'session_id' => 'export_session',
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'business_description' => 'Export test',
            'generation_mode' => 'creative',
            'status' => 'completed',
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'deep_thinking' => false,
        ]);

        AIGeneration::factory()->completed()->create([
            'generation_session_id' => $session->session_id,
            'user_id' => $this->user->id,
            'models_requested' => ['gpt-4'],
            'status' => 'completed',
            'results_data' => ['names' => ['ExportName1', 'ExportName2']],
            'total_response_time_ms' => 1500,
            'total_cost_cents' => 5,
        ]);

        $exportData = $this->service->exportUserAnalytics($this->user, 'month');

        expect($exportData)->toBeArray()
            ->and($exportData)->toHaveKeys(['user_info', 'period', 'analytics', 'generated_at'])
            ->and($exportData['user_info']['id'])->toBe($this->user->id);
    });
});
