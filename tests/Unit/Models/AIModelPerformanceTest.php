<?php

declare(strict_types=1);

use App\Models\AIModelPerformance;
use App\Models\User;

test('AIModelPerformance model can be created with required attributes', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::create([
        'user_id' => $user->id,
        'model_name' => 'gpt-4o',
        'total_requests' => 10,
        'successful_requests' => 8,
        'failed_requests' => 2,
        'average_response_time_ms' => 1500,
        'total_tokens_used' => 5000,
        'total_cost_cents' => 250,
        'last_used_at' => now(),
    ]);

    expect($performance)->toBeInstanceOf(AIModelPerformance::class)
        ->and($performance->user_id)->toBe($user->id)
        ->and($performance->model_name)->toBe('gpt-4o')
        ->and($performance->total_requests)->toBe(10)
        ->and($performance->successful_requests)->toBe(8)
        ->and($performance->failed_requests)->toBe(2)
        ->and($performance->average_response_time_ms)->toBe(1500)
        ->and($performance->total_tokens_used)->toBe(5000)
        ->and($performance->total_cost_cents)->toBe(250);
});

test('AIModelPerformance belongs to user', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($performance->user)->toBeInstanceOf(User::class)
        ->and($performance->user->id)->toBe($user->id);
});

test('AIModelPerformance casts last_used_at to datetime', function () {
    $user = User::factory()->create();
    $timestamp = '2024-01-15 10:30:00';
    
    $performance = AIModelPerformance::create([
        'user_id' => $user->id,
        'model_name' => 'gpt-4o',
        'total_requests' => 5,
        'successful_requests' => 5,
        'failed_requests' => 0,
        'average_response_time_ms' => 1200,
        'total_tokens_used' => 2500,
        'total_cost_cents' => 100,
        'last_used_at' => $timestamp,
    ]);

    expect($performance->last_used_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('AIModelPerformance has scope for specific model', function () {
    $user = User::factory()->create();
    
    AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'model_name' => 'gpt-4o',
    ]);
    
    AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'model_name' => 'claude-3.5-sonnet',
    ]);
    
    AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'model_name' => 'gpt-4o',
    ]);

    $gptPerformance = AIModelPerformance::forModel('gpt-4o')->get();
    $claudePerformance = AIModelPerformance::forModel('claude-3.5-sonnet')->get();
    
    expect($gptPerformance)->toHaveCount(2)
        ->and($claudePerformance)->toHaveCount(1);
});

test('AIModelPerformance has scope for recent usage', function () {
    $user = User::factory()->create();
    
    // Create old performance record
    AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'last_used_at' => now()->subDays(10),
    ]);
    
    // Create recent performance record
    AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'last_used_at' => now()->subHours(2),
    ]);

    $recentPerformance = AIModelPerformance::recentlyUsed()->get();
    
    expect($recentPerformance)->toHaveCount(1);
});

test('AIModelPerformance can calculate success rate', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'total_requests' => 20,
        'successful_requests' => 18,
        'failed_requests' => 2,
    ]);

    expect($performance->getSuccessRate())->toBe(90.0);
});

test('AIModelPerformance handles zero requests for success rate', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
    ]);

    expect($performance->getSuccessRate())->toBe(0.0);
});

test('AIModelPerformance can calculate failure rate', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'total_requests' => 25,
        'successful_requests' => 22,
        'failed_requests' => 3,
    ]);

    expect($performance->getFailureRate())->toBe(12.0);
});

test('AIModelPerformance can calculate cost per request', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'total_requests' => 10,
        'total_cost_cents' => 500,
    ]);

    expect($performance->getCostPerRequest())->toBe(50.0);
});

test('AIModelPerformance handles zero requests for cost calculation', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'total_requests' => 0,
        'total_cost_cents' => 0,
    ]);

    expect($performance->getCostPerRequest())->toBe(0.0);
});

test('AIModelPerformance can update metrics', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'model_name' => 'gpt-4o',
        'total_requests' => 5,
        'successful_requests' => 5,
        'failed_requests' => 0,
        'average_response_time_ms' => 1000,
        'total_tokens_used' => 2000,
        'total_cost_cents' => 100,
    ]);

    $performance->updateMetrics(
        responseTime: 1500,
        tokensUsed: 800,
        costCents: 40,
        wasSuccessful: true
    );

    $fresh = $performance->fresh();
    
    expect($fresh->total_requests)->toBe(6)
        ->and($fresh->successful_requests)->toBe(6)
        ->and($fresh->failed_requests)->toBe(0)
        ->and($fresh->average_response_time_ms)->toBe(1083) // Weighted average
        ->and($fresh->total_tokens_used)->toBe(2800)
        ->and($fresh->total_cost_cents)->toBe(140);
});

test('AIModelPerformance can update metrics for failed request', function () {
    $user = User::factory()->create();
    
    $performance = AIModelPerformance::factory()->create([
        'user_id' => $user->id,
        'model_name' => 'gpt-4o',
        'total_requests' => 5,
        'successful_requests' => 4,
        'failed_requests' => 1,
        'average_response_time_ms' => 1000,
        'total_tokens_used' => 2000,
        'total_cost_cents' => 100,
    ]);

    $performance->updateMetrics(
        responseTime: 2000,
        tokensUsed: 0,
        costCents: 0,
        wasSuccessful: false
    );

    $fresh = $performance->fresh();
    
    expect($fresh->total_requests)->toBe(6)
        ->and($fresh->successful_requests)->toBe(4)
        ->and($fresh->failed_requests)->toBe(2)
        ->and($fresh->average_response_time_ms)->toBe(1167) // Weighted average including failed request
        ->and($fresh->total_tokens_used)->toBe(2000) // No tokens for failed request
        ->and($fresh->total_cost_cents)->toBe(100); // No cost for failed request
});

test('AIModelPerformance can find or create record for user and model', function () {
    $user = User::factory()->create();
    
    // First call should create new record
    $performance1 = AIModelPerformance::findOrCreateForUser($user->id, 'gpt-4o');
    
    expect($performance1)->toBeInstanceOf(AIModelPerformance::class)
        ->and($performance1->user_id)->toBe($user->id)
        ->and($performance1->model_name)->toBe('gpt-4o')
        ->and($performance1->total_requests)->toBe(0);
    
    // Second call should return existing record
    $performance2 = AIModelPerformance::findOrCreateForUser($user->id, 'gpt-4o');
    
    expect($performance2->id)->toBe($performance1->id);
});

test('AIModelPerformance has proper fillable attributes', function () {
    $user = User::factory()->create();
    
    $performance = new AIModelPerformance([
        'user_id' => $user->id,
        'model_name' => 'gpt-4o',
        'total_requests' => 10,
        'successful_requests' => 8,
        'failed_requests' => 2,
        'average_response_time_ms' => 1500,
        'total_tokens_used' => 5000,
        'total_cost_cents' => 250,
        'last_used_at' => now(),
    ]);

    expect($performance->user_id)->toBe($user->id)
        ->and($performance->model_name)->toBe('gpt-4o')
        ->and($performance->total_requests)->toBe(10);
});