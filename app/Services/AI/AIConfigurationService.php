<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * AI Configuration Management Service.
 *
 * Manages AI model availability, settings, API keys, and system-wide
 * configuration for the AI generation system.
 */
class AIConfigurationService
{
    /** @var array<string, array<string, mixed>> */
    protected array $defaultModels = [
        'openai-gpt-4' => [
            'name' => 'GPT-4',
            'provider' => 'openai',
            'model_id' => 'gpt-4',
            'enabled' => true,
            'max_tokens' => 150,
            'temperature' => 0.7,
            'cost_per_1k_tokens' => 0.03,
            'rate_limit_per_minute' => 60,
            'capabilities' => ['text_generation', 'creative_writing'],
            'description' => 'Most capable GPT model for high-quality name generation',
        ],
        'openai-gpt-3.5-turbo' => [
            'name' => 'GPT-3.5 Turbo',
            'provider' => 'openai',
            'model_id' => 'gpt-3.5-turbo',
            'enabled' => true,
            'max_tokens' => 150,
            'temperature' => 0.8,
            'cost_per_1k_tokens' => 0.002,
            'rate_limit_per_minute' => 90,
            'capabilities' => ['text_generation', 'fast_generation'],
            'description' => 'Fast and cost-effective model for name generation',
        ],
        'anthropic-claude' => [
            'name' => 'Claude',
            'provider' => 'anthropic',
            'model_id' => 'claude-3-sonnet-20240229',
            'enabled' => false,
            'max_tokens' => 150,
            'temperature' => 0.7,
            'cost_per_1k_tokens' => 0.015,
            'rate_limit_per_minute' => 50,
            'capabilities' => ['text_generation', 'nuanced_context'],
            'description' => 'Context-aware model for thoughtful name suggestions',
        ],
    ];

    /** @var array<string, mixed> */
    protected array $defaultSettings = [
        'default_model' => 'openai-gpt-4',
        'fallback_model' => 'openai-gpt-3.5-turbo',
        'max_generations_per_user_per_hour' => 50,
        'max_generations_per_user_per_day' => 200,
        'enable_analytics' => true,
        'enable_caching' => true,
        'cache_ttl_minutes' => 60,
        'timeout_seconds' => 30,
        'retry_attempts' => 3,
        'enable_cost_tracking' => true,
        'maintenance_mode' => false,
        'admin_notifications' => true,
    ];

    /**
     * Get all available AI models with their configurations.
     *
     * @return array<string, mixed>
     */
    public function getAvailableModels(): array
    {
        return Cache::remember('ai_models_config', 300, function () {
            $models = config('ai.models', $this->defaultModels);

            /** @var array<string, array<string, mixed>> $models */
            return collect($models)->map(fn ($config, $key) =>
                /** @var array<string, mixed> $config */
                /** @var string $key */
                array_merge($config, [
                    'id' => $key,
                    'is_available' => $this->checkModelAvailability($key, $config),
                    'status' => $this->determineModelStatus($key, $config),
                ]))->all();
        });
    }

    /**
     * Get configuration for a specific model.
     *
     * @return array<string, mixed>|null
     */
    public function getModelConfig(string $modelId): ?array
    {
        $models = Cache::get('ai_models_config');
        if ($models) {
            return $models[$modelId] ?? null;
        }

        // Fallback to config if cache is empty
        $allModels = config('ai.models', $this->defaultModels);

        return $allModels[$modelId] ?? null;
    }

    /**
     * Get enabled models only.
     *
     * @return array<string, mixed>
     */
    public function getEnabledModels(): array
    {
        return collect($this->getAvailableModels())
            ->filter(fn ($model) => $model['enabled'])
            ->all();
    }

    /**
     * Check if a specific model is available and enabled.
     */
    public function isModelAvailable(string $modelId): bool
    {
        $model = $this->getModelConfig($modelId);

        if (! $model) {
            return false;
        }

        return $this->checkModelAvailability($modelId, $model);
    }

    /**
     * Get model status information.
     */
    public function getModelStatus(string $modelId): string
    {
        $model = $this->getModelConfig($modelId);

        if (! $model) {
            return 'not_found';
        }

        return $this->determineModelStatus($modelId, $model);
    }

    /**
     * Check model availability without recursion.
     */
    /**
     * @param  array<string, mixed>  $model
     */
    protected function checkModelAvailability(string $modelId, array $model): bool
    {
        if (! $model['enabled']) {
            return false;
        }

        // Check if API key is available
        $apiKey = $this->getApiKey($model['provider']);
        if (! $apiKey) {
            return false;
        }

        // Check if model is not in maintenance mode
        return ! $this->checkMaintenanceMode($modelId, $model);
    }

    /**
     * Determine model status without recursion.
     */
    /**
     * @param  array<string, mixed>  $model
     */
    protected function determineModelStatus(string $modelId, array $model): string
    {
        if (! $model['enabled']) {
            return 'disabled';
        }

        if (! $this->getApiKey($model['provider'])) {
            return 'missing_api_key';
        }

        if ($this->checkMaintenanceMode($modelId, $model)) {
            return 'maintenance';
        }

        return 'available';
    }

    /**
     * Check maintenance mode without recursion.
     */
    /**
     * @param  array<string, mixed>  $model
     */
    protected function checkMaintenanceMode(string $modelId, array $model): bool
    {
        $settings = config('ai.settings', $this->defaultSettings);

        if ($settings['maintenance_mode']) {
            return true;
        }

        return $model['maintenance_mode'] ?? false;
    }

    /**
     * Get API key for a provider.
     */
    public function getApiKey(string $provider): ?string
    {
        return match ($provider) {
            'openai' => config('services.openai.api_key'),
            'anthropic' => config('services.anthropic.api_key'),
            'google' => config('services.google.api_key'),
            default => null,
        };
    }

    /**
     * Update model configuration.
     *
     * @param  array<string, mixed>  $config
     */
    public function updateModelConfig(string $modelId, array $config): bool
    {
        try {
            $models = config('ai.models', $this->defaultModels);
            $models[$modelId] = array_merge($models[$modelId] ?? [], $config);

            // In a real application, this would save to database or config file
            // For now, we'll update the runtime config and clear cache
            Config::set('ai.models', $models);
            Cache::forget('ai_models_config');

            Log::info('AI model configuration updated', [
                'model_id' => $modelId,
                'config' => $config,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update AI model configuration', [
                'model_id' => $modelId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enable or disable a model.
     */
    public function toggleModel(string $modelId, bool $enabled): bool
    {
        return $this->updateModelConfig($modelId, ['enabled' => $enabled]);
    }

    /**
     * Get system-wide AI settings.
     *
     * @return array<string, mixed>
     */
    public function getSystemSettings(): array
    {
        return Cache::remember('ai_system_settings', 300, fn () => config('ai.settings', $this->defaultSettings));
    }

    /**
     * Update system settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateSystemSettings(array $settings): bool
    {
        try {
            $currentSettings = $this->getSystemSettings();
            $updatedSettings = array_merge($currentSettings, $settings);

            Config::set('ai.settings', $updatedSettings);
            Cache::forget('ai_system_settings');

            Log::info('AI system settings updated', [
                'settings' => $settings,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update AI system settings', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the default model to use.
     */
    public function getDefaultModel(): string
    {
        $settings = $this->getSystemSettings();
        $defaultModel = $settings['default_model'];

        // Check if default model is available
        if ($this->isModelAvailable($defaultModel)) {
            return $defaultModel;
        }

        // Fall back to fallback model
        $fallbackModel = $settings['fallback_model'];
        if ($this->isModelAvailable($fallbackModel)) {
            return $fallbackModel;
        }

        // Find first available model
        $enabledModels = $this->getEnabledModels();
        foreach ($enabledModels as $modelId => $model) {
            if ($this->isModelAvailable($modelId)) {
                return $modelId;
            }
        }

        throw new \RuntimeException('No AI models are currently available');
    }

    /**
     * Check if system is in maintenance mode.
     */
    public function isInMaintenanceMode(?string $modelId = null): bool
    {
        $settings = $this->getSystemSettings();

        if ($settings['maintenance_mode']) {
            return true;
        }

        if ($modelId) {
            $model = $this->getModelConfig($modelId);

            return $model['maintenance_mode'] ?? false;
        }

        return false;
    }

    /**
     * Get usage limits for a user.
     *
     * @return array<string, int>
     */
    public function getUserLimits(): array
    {
        $settings = $this->getSystemSettings();

        return [
            'max_generations_per_hour' => $settings['max_generations_per_user_per_hour'],
            'max_generations_per_day' => $settings['max_generations_per_user_per_day'],
        ];
    }

    /**
     * Get model performance metrics.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getModelPerformanceMetrics(): array
    {
        return Cache::remember('ai_model_performance', 600, function () {
            $models = $this->getAvailableModels();
            $metrics = [];

            foreach ($models as $modelId => $model) {
                $metrics[$modelId] = [
                    'average_response_time' => $this->getAverageResponseTime($modelId),
                    'success_rate' => $this->getSuccessRate($modelId),
                    'cost_efficiency' => $this->calculateCostEfficiency($modelId),
                    'usage_count_24h' => $this->getUsageCount($modelId, 24),
                    'error_rate' => $this->getErrorRate($modelId),
                ];
            }

            return $metrics;
        });
    }

    /**
     * Validate model configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    public function validateModelConfig(array $config): array
    {
        $errors = [];

        if (empty($config['name'])) {
            $errors[] = 'Model name is required';
        }

        if (empty($config['provider'])) {
            $errors[] = 'Provider is required';
        }

        if (empty($config['model_id'])) {
            $errors[] = 'Model ID is required';
        }

        if (isset($config['max_tokens']) && (! is_int($config['max_tokens']) || $config['max_tokens'] < 1)) {
            $errors[] = 'Max tokens must be a positive integer';
        }

        if (isset($config['temperature']) && (! is_numeric($config['temperature']) || $config['temperature'] < 0 || $config['temperature'] > 2)) {
            $errors[] = 'Temperature must be between 0 and 2';
        }

        if (isset($config['cost_per_1k_tokens']) && (! is_numeric($config['cost_per_1k_tokens']) || $config['cost_per_1k_tokens'] < 0)) {
            $errors[] = 'Cost per 1k tokens must be a non-negative number';
        }

        return $errors;
    }

    /**
     * Reset configuration to defaults.
     */
    public function resetToDefaults(): bool
    {
        try {
            Config::set('ai.models', $this->defaultModels);
            Config::set('ai.settings', $this->defaultSettings);

            Cache::forget('ai_models_config');
            Cache::forget('ai_system_settings');
            Cache::forget('ai_model_performance');

            Log::info('AI configuration reset to defaults');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reset AI configuration', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get average response time for a model.
     */
    protected function getAverageResponseTime(string $modelId): float
    {
        // This would typically query your analytics database
        // For now, return simulated data
        return match ($modelId) {
            'openai-gpt-4' => 2.5,
            'openai-gpt-3.5-turbo' => 1.2,
            'anthropic-claude' => 3.1,
            default => 2.0,
        };
    }

    /**
     * Get success rate for a model.
     */
    protected function getSuccessRate(string $modelId): float
    {
        // This would typically query your analytics database
        return match ($modelId) {
            'openai-gpt-4' => 98.5,
            'openai-gpt-3.5-turbo' => 97.2,
            'anthropic-claude' => 96.8,
            default => 95.0,
        };
    }

    /**
     * Calculate cost efficiency score for a model.
     */
    protected function calculateCostEfficiency(string $modelId): float
    {
        $model = $this->getModelConfig($modelId);
        if (! $model) {
            return 0;
        }

        $successRate = $this->getSuccessRate($modelId);
        $cost = $model['cost_per_1k_tokens'];

        // Higher success rate and lower cost = better efficiency
        return ($successRate / 100) / max($cost, 0.001) * 100;
    }

    /**
     * Get usage count for a model in the last N hours.
     */
    protected function getUsageCount(string $modelId, int $hours): int
    {
        // This would typically query your usage logs
        return match ($modelId) {
            'openai-gpt-4' => random_int(100, 300),
            'openai-gpt-3.5-turbo' => random_int(150, 400),
            'anthropic-claude' => random_int(50, 150),
            default => random_int(25, 100),
        };
    }

    /**
     * Get error rate for a model.
     */
    protected function getErrorRate(string $modelId): float
    {
        return 100 - $this->getSuccessRate($modelId);
    }

    /**
     * Get configuration health status.
     *
     * @return array<string, mixed>
     */
    public function getConfigurationHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'models' => [],
            'api_keys' => [],
        ];

        // Check each model's health
        foreach ($this->getAvailableModels() as $modelId => $config) {
            $modelHealth = [
                'enabled' => $config['enabled'] ?? false,
                'api_key_present' => ! empty($this->getApiKey($config['provider'] ?? '')),
                'last_checked' => now()->toISOString(),
                'status' => 'unknown',
            ];

            if (! $modelHealth['api_key_present']) {
                $health['issues'][] = "Missing API key for {$modelId}";
                $modelHealth['status'] = 'error';
                $health['status'] = 'degraded';
            } elseif ($modelHealth['enabled']) {
                $modelHealth['status'] = 'healthy';
            } else {
                $modelHealth['status'] = 'disabled';
            }

            $health['models'][$modelId] = $modelHealth;
        }

        // Check API key status
        $providers = ['openai', 'anthropic', 'google', 'xai'];
        foreach ($providers as $provider) {
            $health['api_keys'][$provider] = ! empty($this->getApiKey($provider));
        }

        return $health;
    }

    /**
     * Clear configuration cache.
     */
    public function clearConfigCache(): bool
    {
        try {
            $cacheKeys = [
                'ai_models_config',
                'ai_system_settings',
                'ai_model_performance',
                'ai_configuration_health',
                'ai_enabled_models',
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            Log::info('AI configuration cache cleared');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear AI configuration cache', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
