<?php

declare(strict_types=1);

use App\Services\AI\AIConfigurationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

it('can get available AI models', function (): void {
    $service = new AIConfigurationService;
    $models = $service->getAvailableModels();

    expect($models)->toBeArray()
        ->and($models)->not->toBeEmpty();

    // Check that each model has required fields
    foreach ($models as $model) {
        expect($model)
            ->toHaveKeys(['name', 'provider', 'model_id', 'enabled', 'max_tokens', 'temperature']);
    }
});

it('can check if a model is available', function (): void {
    $service = new AIConfigurationService;

    // Mock API key availability
    Config::set('services.openai.api_key', 'test-key');

    $isAvailable = $service->isModelAvailable('openai-gpt-4');
    expect($isAvailable)->toBeTrue();

    $isAvailable = $service->isModelAvailable('non-existent-model');
    expect($isAvailable)->toBeFalse();
});

it('can get model configuration', function (): void {
    $service = new AIConfigurationService;

    $config = $service->getModelConfig('openai-gpt-4');
    expect($config)->toBeArray()
        ->and($config['name'])->toBe('GPT-4')
        ->and($config['provider'])->toBe('openai');

    $config = $service->getModelConfig('non-existent');
    expect($config)->toBeNull();
});

it('can get enabled models only', function (): void {
    $service = new AIConfigurationService;

    $enabledModels = $service->getEnabledModels();
    expect($enabledModels)->toBeArray();

    foreach ($enabledModels as $model) {
        expect($model['enabled'])->toBeTrue();
    }
});

it('can update model configuration', function (): void {
    $service = new AIConfigurationService;

    $result = $service->updateModelConfig('openai-gpt-4', [
        'name' => 'Updated GPT-4',
        'max_tokens' => 200,
    ]);

    expect($result)->toBeTrue();

    // Verify the update
    Cache::forget('ai_models_config');
    $updatedConfig = $service->getModelConfig('openai-gpt-4');
    expect($updatedConfig['name'])->toBe('Updated GPT-4')
        ->and($updatedConfig['max_tokens'])->toBe(200);
});

it('can toggle model enabled status', function (): void {
    $service = new AIConfigurationService;

    // First, ensure model is enabled
    $result = $service->toggleModel('openai-gpt-4', true);
    expect($result)->toBeTrue();

    Cache::forget('ai_models_config');
    $config = $service->getModelConfig('openai-gpt-4');
    expect($config['enabled'])->toBeTrue();

    // Then disable it
    $result = $service->toggleModel('openai-gpt-4', false);
    expect($result)->toBeTrue();

    Cache::forget('ai_models_config');
    $config = $service->getModelConfig('openai-gpt-4');
    expect($config['enabled'])->toBeFalse();
});

it('can get system settings', function (): void {
    $service = new AIConfigurationService;

    $settings = $service->getSystemSettings();
    expect($settings)->toBeArray()
        ->and($settings)->toHaveKeys(['default_model', 'fallback_model', 'enable_analytics']);
});

it('can update system settings', function (): void {
    $service = new AIConfigurationService;

    $result = $service->updateSystemSettings([
        'enable_analytics' => false,
        'max_generations_per_user_per_hour' => 25,
    ]);

    expect($result)->toBeTrue();

    // Verify the update
    Cache::forget('ai_system_settings');
    $settings = $service->getSystemSettings();
    expect($settings['enable_analytics'])->toBeFalse()
        ->and($settings['max_generations_per_user_per_hour'])->toBe(25);
});

it('can get default model', function (): void {
    $service = new AIConfigurationService;

    // Mock API key to make model available
    Config::set('services.openai.api_key', 'test-key');

    $defaultModel = $service->getDefaultModel();
    expect($defaultModel)->toBeString()
        ->and($defaultModel)->not->toBeEmpty();
});

it('throws exception when no models are available', function (): void {
    $service = new AIConfigurationService;

    // Remove API keys to make models unavailable
    Config::set('services.openai.api_key', null);
    Config::set('services.anthropic.api_key', null);

    expect(fn () => $service->getDefaultModel())
        ->toThrow(RuntimeException::class, 'No AI models are currently available');
});

it('can check maintenance mode', function (): void {
    $service = new AIConfigurationService;

    // Test system-wide maintenance mode
    $service->updateSystemSettings(['maintenance_mode' => true]);
    Cache::forget('ai_system_settings');

    expect($service->isInMaintenanceMode())->toBeTrue();

    // Test model-specific maintenance mode
    $service->updateSystemSettings(['maintenance_mode' => false]);
    $service->updateModelConfig('openai-gpt-4', ['maintenance_mode' => true]);
    Cache::forget('ai_system_settings');
    Cache::forget('ai_models_config');

    expect($service->isInMaintenanceMode('openai-gpt-4'))->toBeTrue();
    expect($service->isInMaintenanceMode())->toBeFalse();
});

it('can get user limits', function (): void {
    $service = new AIConfigurationService;

    $limits = $service->getUserLimits();
    expect($limits)->toBeArray()
        ->and($limits)->toHaveKeys(['max_generations_per_hour', 'max_generations_per_day'])
        ->and($limits['max_generations_per_hour'])->toBeInt()
        ->and($limits['max_generations_per_day'])->toBeInt();
});

it('can get model performance metrics', function (): void {
    $service = new AIConfigurationService;

    $metrics = $service->getModelPerformanceMetrics();
    expect($metrics)->toBeArray();

    if (! empty($metrics)) {
        $firstModel = array_values($metrics)[0];
        expect($firstModel)->toHaveKeys([
            'average_response_time',
            'success_rate',
            'cost_efficiency',
            'usage_count_24h',
            'error_rate',
        ]);
    }
});

it('can validate model configuration', function (): void {
    $service = new AIConfigurationService;

    // Valid configuration
    $validConfig = [
        'name' => 'Test Model',
        'provider' => 'openai',
        'model_id' => 'test-model',
        'max_tokens' => 150,
        'temperature' => 0.7,
        'cost_per_1k_tokens' => 0.02,
    ];

    $errors = $service->validateModelConfig($validConfig);
    expect($errors)->toBeEmpty();

    // Invalid configuration
    $invalidConfig = [
        'name' => '',
        'provider' => '',
        'max_tokens' => -1,
        'temperature' => 5,
        'cost_per_1k_tokens' => -0.01,
    ];

    $errors = $service->validateModelConfig($invalidConfig);
    expect($errors)->not->toBeEmpty()
        ->and(count($errors))->toBeGreaterThan(0);
});

it('can reset configuration to defaults', function (): void {
    $service = new AIConfigurationService;

    // Make some changes first
    $service->updateModelConfig('openai-gpt-4', ['name' => 'Modified GPT-4']);
    $service->updateSystemSettings(['enable_analytics' => false]);

    // Reset to defaults
    $result = $service->resetToDefaults();
    expect($result)->toBeTrue();

    // Verify reset
    $model = $service->getModelConfig('openai-gpt-4');
    $settings = $service->getSystemSettings();

    expect($model['name'])->toBe('GPT-4')
        ->and($settings['enable_analytics'])->toBeTrue();
});

it('can get API key for providers', function (): void {
    $service = new AIConfigurationService;

    Config::set('services.openai.api_key', 'test-openai-key');
    Config::set('services.anthropic.api_key', 'test-anthropic-key');

    expect($service->getApiKey('openai'))->toBe('test-openai-key')
        ->and($service->getApiKey('anthropic'))->toBe('test-anthropic-key')
        ->and($service->getApiKey('unknown'))->toBeNull();
});

it('can get model status', function (): void {
    $service = new AIConfigurationService;

    // Test with API key available
    Config::set('services.openai.api_key', 'test-key');
    $status = $service->getModelStatus('openai-gpt-4');
    expect($status)->toBe('available');

    // Test without API key
    Config::set('services.openai.api_key', null);
    Cache::forget('ai_models_config');
    $status = $service->getModelStatus('openai-gpt-4');
    expect($status)->toBe('missing_api_key');

    // Test non-existent model
    $status = $service->getModelStatus('non-existent');
    expect($status)->toBe('not_found');
});
