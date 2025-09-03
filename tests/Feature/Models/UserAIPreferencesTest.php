<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserAIPreferences;

test('UserAIPreferences model can be created with required attributes', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::create([
        'user_id' => $user->id,
        'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
        'default_generation_mode' => 'creative',
        'default_deep_thinking' => true,
        'model_priorities' => [
            'gpt-4' => 1,
            'claude-3.5-sonnet' => 2,
            'gemini-1.5-pro' => 3,
            'grok-beta' => 4,
        ],
        'custom_parameters' => [
            'temperature' => 0.8,
            'max_tokens' => 1000,
        ],
        'notification_settings' => [
            'email_on_completion' => true,
            'email_on_failure' => false,
        ],
    ]);

    expect($preferences)->toBeInstanceOf(UserAIPreferences::class)
        ->and($preferences->user_id)->toBe($user->id)
        ->and($preferences->preferred_models)->toBe(['gpt-4', 'claude-3.5-sonnet'])
        ->and($preferences->default_generation_mode)->toBe('creative')
        ->and($preferences->default_deep_thinking)->toBeTrue()
        ->and($preferences->model_priorities)->toBeArray()
        ->and($preferences->custom_parameters)->toBeArray()
        ->and($preferences->notification_settings)->toBeArray();
});

test('UserAIPreferences belongs to user', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($preferences->user)->toBeInstanceOf(User::class)
        ->and($preferences->user->id)->toBe($user->id);
});

test('UserAIPreferences casts arrays and boolean properly', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::create([
        'user_id' => $user->id,
        'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
        'default_generation_mode' => 'creative',
        'default_deep_thinking' => 1,
        'model_priorities' => ['gpt-4' => 1],
        'custom_parameters' => ['temperature' => 0.8],
        'notification_settings' => ['email_on_completion' => true],
    ]);

    expect($preferences->preferred_models)->toBeArray()
        ->and($preferences->default_deep_thinking)->toBeBool()
        ->and($preferences->model_priorities)->toBeArray()
        ->and($preferences->custom_parameters)->toBeArray()
        ->and($preferences->notification_settings)->toBeArray();
});

test('UserAIPreferences can find or create for user', function (): void {
    $user = User::factory()->create();

    // First call should create new record with defaults
    $preferences1 = UserAIPreferences::findOrCreateForUser($user->id);

    expect($preferences1)->toBeInstanceOf(UserAIPreferences::class)
        ->and($preferences1->user_id)->toBe($user->id)
        ->and($preferences1->preferred_models)->toBe(['gpt-4', 'claude-3.5-sonnet'])
        ->and($preferences1->default_generation_mode)->toBe('creative')
        ->and($preferences1->default_deep_thinking)->toBeFalse();

    // Second call should return existing record
    $preferences2 = UserAIPreferences::findOrCreateForUser($user->id);

    expect($preferences2->id)->toBe($preferences1->id);
});

test('UserAIPreferences can get preferred models in priority order', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
        'preferred_models' => ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'],
        'model_priorities' => [
            'claude-3.5-sonnet' => 1,
            'gpt-4' => 2,
            'gemini-1.5-pro' => 3,
        ],
    ]);

    $ordered = $preferences->getPreferredModelsOrdered();

    expect($ordered)->toBe(['claude-3.5-sonnet', 'gpt-4', 'gemini-1.5-pro']);
});

test('UserAIPreferences returns preferred models as-is when no priorities set', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
        'preferred_models' => ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'],
        'model_priorities' => [],
    ]);

    $ordered = $preferences->getPreferredModelsOrdered();

    expect($ordered)->toBe(['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro']);
});

test('UserAIPreferences can update preferred models', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
        'preferred_models' => ['gpt-4'],
    ]);

    $preferences->updatePreferredModels(['claude-3.5-sonnet', 'gemini-1.5-pro']);

    expect($preferences->fresh()->preferred_models)->toBe(['claude-3.5-sonnet', 'gemini-1.5-pro']);
});

test('UserAIPreferences can update model priorities', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
        'model_priorities' => ['gpt-4' => 1, 'claude-3.5-sonnet' => 2],
    ]);

    $newPriorities = [
        'claude-3.5-sonnet' => 1,
        'gpt-4' => 2,
        'gemini-1.5-pro' => 3,
    ];

    $preferences->updateModelPriorities($newPriorities);

    expect($preferences->fresh()->model_priorities)->toBe($newPriorities);
});

test('UserAIPreferences can update custom parameters', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
        'custom_parameters' => ['temperature' => 0.7],
    ]);

    $newParameters = [
        'temperature' => 0.9,
        'max_tokens' => 1500,
        'top_p' => 0.95,
    ];

    $preferences->updateCustomParameters($newParameters);

    expect($preferences->fresh()->custom_parameters)->toBe($newParameters);
});

test('UserAIPreferences can update notification settings', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
        'notification_settings' => ['email_on_completion' => false],
    ]);

    $newSettings = [
        'email_on_completion' => true,
        'email_on_failure' => true,
        'push_notifications' => false,
    ];

    $preferences->updateNotificationSettings($newSettings);

    expect($preferences->fresh()->notification_settings)->toBe($newSettings);
});

test('UserAIPreferences can check if model is preferred', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
        'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
    ]);

    expect($preferences->isModelPreferred('gpt-4'))->toBeTrue()
        ->and($preferences->isModelPreferred('claude-3.5-sonnet'))->toBeTrue()
        ->and($preferences->isModelPreferred('gemini-1.5-pro'))->toBeFalse();
});

test('UserAIPreferences can get default generation parameters', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create([
        'user_id' => $user->id,
        'default_generation_mode' => 'professional',
        'default_deep_thinking' => true,
        'custom_parameters' => [
            'temperature' => 0.6,
            'max_tokens' => 1200,
        ],
    ]);

    $defaults = $preferences->getDefaultGenerationParameters();

    expect($defaults['mode'])->toBe('professional')
        ->and($defaults['deep_thinking'])->toBeTrue()
        ->and($defaults['custom_params']['temperature'])->toBe(0.6)
        ->and($defaults['custom_params']['max_tokens'])->toBe(1200);
});

test('UserAIPreferences can validate generation mode', function (): void {
    $user = User::factory()->create();

    $preferences = UserAIPreferences::factory()->create(['user_id' => $user->id]);

    expect($preferences->isValidGenerationMode('creative'))->toBeTrue()
        ->and($preferences->isValidGenerationMode('professional'))->toBeTrue()
        ->and($preferences->isValidGenerationMode('brandable'))->toBeTrue()
        ->and($preferences->isValidGenerationMode('tech-focused'))->toBeTrue()
        ->and($preferences->isValidGenerationMode('invalid-mode'))->toBeFalse();
});

test('UserAIPreferences has scope for users with preferred model', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    UserAIPreferences::factory()->create([
        'user_id' => $user1->id,
        'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
    ]);

    UserAIPreferences::factory()->create([
        'user_id' => $user2->id,
        'preferred_models' => ['claude-3.5-sonnet', 'gemini-1.5-pro'],
    ]);

    UserAIPreferences::factory()->create([
        'user_id' => $user3->id,
        'preferred_models' => ['gemini-1.5-pro'],
    ]);

    $gptUsers = UserAIPreferences::withPreferredModel('gpt-4')->get();
    $claudeUsers = UserAIPreferences::withPreferredModel('claude-3.5-sonnet')->get();

    expect($gptUsers)->toHaveCount(1)
        ->and($claudeUsers)->toHaveCount(2);
});

test('UserAIPreferences has proper fillable attributes', function (): void {
    $user = User::factory()->create();

    $preferences = new UserAIPreferences([
        'user_id' => $user->id,
        'preferred_models' => ['gpt-4'],
        'default_generation_mode' => 'creative',
        'default_deep_thinking' => true,
        'model_priorities' => ['gpt-4' => 1],
        'custom_parameters' => ['temperature' => 0.8],
        'notification_settings' => ['email_on_completion' => true],
    ]);

    expect($preferences->user_id)->toBe($user->id)
        ->and($preferences->preferred_models)->toBe(['gpt-4'])
        ->and($preferences->default_generation_mode)->toBe('creative');
});
