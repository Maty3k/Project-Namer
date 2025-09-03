<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\AI\AICostTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Run the migration for ai_usage_logs table
    $this->artisan('migrate');
});

it('can record API usage', function (): void {
    $user = User::factory()->create();
    $service = new AICostTrackingService;

    $cost = $service->recordUsage(
        $user,
        'openai-gpt-4',
        100,
        50,
        2.5,
        true
    );

    expect($cost)->toBeFloat()
        ->and($cost)->toBeGreaterThan(0);

    // Check that the usage was recorded in the database
    $this->assertDatabaseHas('ai_usage_logs', [
        'user_id' => $user->id,
        'model_id' => 'openai-gpt-4',
        'input_tokens' => 100,
        'output_tokens' => 50,
        'total_tokens' => 150,
        'successful' => true,
    ]);
});

it('can calculate cost for different models', function (): void {
    $service = new AICostTrackingService;

    $gpt4Cost = $service->calculateCost('openai-gpt-4', 1000, 1000);
    $gpt35Cost = $service->calculateCost('openai-gpt-3.5-turbo', 1000, 1000);

    expect($gpt4Cost)->toBeFloat()
        ->and($gpt4Cost)->toBeGreaterThan(0)
        ->and($gpt4Cost)->toBeGreaterThan($gpt35Cost);

    // Test unknown model
    $unknownCost = $service->calculateCost('unknown-model', 1000, 1000);
    expect($unknownCost)->toBe(0.0);
});

it('can get user usage statistics', function (): void {
    $user = User::factory()->create();
    $service = new AICostTrackingService;

    // Record some usage
    $service->recordUsage($user, 'openai-gpt-4', 100, 50, 2.5, true);
    $service->recordUsage($user, 'openai-gpt-3.5-turbo', 200, 100, 1.8, true);
    $service->recordUsage($user, 'openai-gpt-4', 150, 75, 3.1, false);

    $stats = $service->getUserUsageStats($user, 'day');

    expect($stats)->toBeArray()
        ->and($stats['total_requests'])->toBe(3)
        ->and($stats['successful_requests'])->toBe(2)
        ->and($stats['failed_requests'])->toBe(1)
        ->and($stats['success_rate'])->toBe(66.67)
        ->and($stats['total_tokens'])->toBe(675)
        ->and($stats['total_cost'])->toBeFloat()
        ->and($stats['model_breakdown'])->toHaveCount(2);
});

it('can check user limits', function (): void {
    $user = User::factory()->create();
    $service = new AICostTrackingService;

    // Record some usage within limits
    for ($i = 0; $i < 5; $i++) {
        $service->recordUsage($user, 'openai-gpt-4', 100, 50, 2.5, true);
    }

    $limits = $service->checkUserLimits($user);

    expect($limits)->toBeArray()
        ->and($limits)->toHaveKeys(['hourly', 'daily'])
        ->and($limits['hourly']['used'])->toBe(5)
        ->and($limits['hourly']['exceeded'])->toBeFalse()
        ->and($limits['daily']['used'])->toBe(5)
        ->and($limits['daily']['exceeded'])->toBeFalse();
});

it('can detect when user limits are exceeded', function (): void {
    $user = User::factory()->create();
    $service = new AICostTrackingService;

    // Set low limits for testing
    config(['ai.settings.max_generations_per_user_per_hour' => 2]);
    config(['ai.settings.max_generations_per_user_per_day' => 5]);

    // Record usage that exceeds hourly limit
    for ($i = 0; $i < 3; $i++) {
        $service->recordUsage($user, 'openai-gpt-4', 100, 50, 2.5, true);
    }

    $limits = $service->checkUserLimits($user);

    expect($limits['hourly']['used'])->toBe(3)
        ->and($limits['hourly']['exceeded'])->toBeTrue()
        ->and($limits['daily']['used'])->toBe(3)
        ->and($limits['daily']['exceeded'])->toBeFalse();
});

it('can get system cost statistics', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $service = new AICostTrackingService;

    // Record usage from multiple users
    $service->recordUsage($user1, 'openai-gpt-4', 100, 50, 2.5, true);
    $service->recordUsage($user2, 'openai-gpt-3.5-turbo', 200, 100, 1.8, true);
    $service->recordUsage($user1, 'openai-gpt-4', 150, 75, 3.1, false);

    $stats = $service->getSystemCostStats('day');

    expect($stats)->toBeArray()
        ->and($stats['total_requests'])->toBe(3)
        ->and($stats['successful_requests'])->toBe(2)
        ->and($stats['active_users'])->toBe(2)
        ->and($stats['model_breakdown'])->toHaveCount(2);
});

it('can check system budget limits', function (): void {
    $service = new AICostTrackingService;

    // Set test budget limits
    config(['ai.cost_tracking.daily_budget_limit' => 10.0]);
    config(['ai.cost_tracking.monthly_budget_limit' => 100.0]);
    config(['ai.cost_tracking.alert_threshold_percentage' => 80]);

    $budgetLimits = $service->checkSystemBudgetLimits();

    expect($budgetLimits)->toBeArray()
        ->and($budgetLimits)->toHaveKeys(['daily', 'monthly'])
        ->and($budgetLimits['daily']['budget'])->toBe(10.0)
        ->and($budgetLimits['monthly']['budget'])->toBe(100.0)
        ->and($budgetLimits['alert_threshold'])->toBe(80);
});

it('can get top spending users', function (): void {
    $user1 = User::factory()->create(['name' => 'High Spender']);
    $user2 = User::factory()->create(['name' => 'Low Spender']);
    $service = new AICostTrackingService;

    // User1 spends more (higher cost model, more tokens)
    for ($i = 0; $i < 5; $i++) {
        $service->recordUsage($user1, 'openai-gpt-4', 200, 100, 2.5, true);
    }

    // User2 spends less
    for ($i = 0; $i < 2; $i++) {
        $service->recordUsage($user2, 'openai-gpt-3.5-turbo', 100, 50, 1.8, true);
    }

    $topUsers = $service->getTopSpendingUsers('day', 5);

    expect($topUsers)->toBeArray()
        ->and(count($topUsers))->toBe(2)
        ->and($topUsers[0]['name'])->toBe('High Spender')
        ->and($topUsers[0]['total_cost'])->toBeGreaterThan($topUsers[1]['total_cost'])
        ->and($topUsers[1]['name'])->toBe('Low Spender');
});

it('can get cost trends', function (): void {
    $user = User::factory()->create();
    $service = new AICostTrackingService;

    // Record some usage
    $service->recordUsage($user, 'openai-gpt-4', 100, 50, 2.5, true);

    $trends = $service->getCostTrends('week');

    expect($trends)->toBeArray()
        ->and(count($trends))->toBe(7);

    foreach ($trends as $trend) {
        expect($trend)->toHaveKeys(['date', 'requests', 'cost', 'tokens'])
            ->and($trend['date'])->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    }
});

it('can estimate cost for planned requests', function (): void {
    $service = new AICostTrackingService;

    $estimate = $service->estimateCost('openai-gpt-4', 1000);

    expect($estimate)->toBeArray()
        ->and($estimate)->toHaveKeys([
            'model_id', 'estimated_input_tokens', 'estimated_output_tokens',
            'estimated_total_tokens', 'input_cost', 'output_cost', 'estimated_cost',
        ])
        ->and($estimate['model_id'])->toBe('openai-gpt-4')
        ->and($estimate['estimated_input_tokens'])->toBe(1000)
        ->and($estimate['estimated_cost'])->toBeFloat()
        ->and($estimate['estimated_cost'])->toBeGreaterThan(0);

    // Test unknown model
    $unknownEstimate = $service->estimateCost('unknown-model', 1000);
    expect($unknownEstimate)->toHaveKey('error')
        ->and($unknownEstimate['estimated_cost'])->toBe(0.0);
});

it('can cleanup old logs', function (): void {
    $user = User::factory()->create();
    $service = new AICostTrackingService;

    // Record some recent usage
    $service->recordUsage($user, 'openai-gpt-4', 100, 50, 2.5, true);

    // Create old usage record manually
    DB::table('ai_usage_logs')->insert([
        'user_id' => $user->id,
        'model_id' => 'openai-gpt-4',
        'input_tokens' => 100,
        'output_tokens' => 50,
        'total_tokens' => 150,
        'cost' => 0.01,
        'response_time' => 2.5,
        'successful' => true,
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDays(100),
    ]);

    // Should have 2 records before cleanup
    expect(DB::table('ai_usage_logs')->count())->toBe(2);

    $deletedCount = $service->cleanupOldLogs(90);

    // Should delete 1 old record
    expect($deletedCount)->toBe(1)
        ->and(DB::table('ai_usage_logs')->count())->toBe(1);
});

it('handles null user for anonymous usage', function (): void {
    $service = new AICostTrackingService;

    $cost = $service->recordUsage(
        null,
        'openai-gpt-4',
        100,
        50,
        2.5,
        true
    );

    expect($cost)->toBeFloat()
        ->and($cost)->toBeGreaterThan(0);

    // Check that the usage was recorded with null user_id
    $this->assertDatabaseHas('ai_usage_logs', [
        'user_id' => null,
        'model_id' => 'openai-gpt-4',
        'input_tokens' => 100,
        'output_tokens' => 50,
    ]);
});
