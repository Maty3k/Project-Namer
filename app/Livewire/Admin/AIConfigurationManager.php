<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\AI\AIConfigurationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * AI Configuration Manager Livewire Component.
 *
 * Admin interface for managing AI model configurations, system settings,
 * and monitoring AI service health and performance.
 */
class AIConfigurationManager extends Component
{
    /** @var array<string, mixed> */
    public array $models = [];

    /** @var array<string, mixed> */
    public array $systemSettings = [];

    /** @var array<string, mixed> */
    public array $performanceMetrics = [];

    public string $activeTab = 'models';

    public ?string $editingModel = null;

    /** @var array<string, mixed> */
    public array $editForm = [];

    public bool $loading = false;

    public bool $isAdmin = false;

    /** @var array<string, string> */
    protected $listeners = [
        'refreshConfiguration' => 'loadConfiguration',
        'modelToggled' => 'handleModelToggle',
    ];

    /** @var array<string, string> */
    protected array $rules = [
        'editForm.name' => 'required|string|max:100',
        'editForm.enabled' => 'boolean',
        'editForm.max_tokens' => 'required|integer|min:1|max:4000',
        'editForm.temperature' => 'required|numeric|min:0|max:2',
        'editForm.cost_per_1k_tokens' => 'required|numeric|min:0',
        'editForm.rate_limit_per_minute' => 'required|integer|min:1|max:1000',
        'editForm.description' => 'required|string|max:500',
    ];

    /**
     * Component initialization.
     */
    public function mount(): void
    {
        $this->isAdmin = Auth::user()?->isAdmin() ?? false;

        if (! $this->isAdmin) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $this->loadConfiguration();
    }

    /**
     * Load all configuration data.
     */
    public function loadConfiguration(): void
    {
        $this->loading = true;

        try {
            $configService = app(AIConfigurationService::class);

            $this->models = $configService->getAvailableModels();
            $this->systemSettings = $configService->getSystemSettings();
            $this->performanceMetrics = $configService->getModelPerformanceMetrics();
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to load configuration: '.$e->getMessage(),
                'type' => 'error',
            ]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Switch active tab.
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'performance') {
            $this->refreshPerformanceMetrics();
        }
    }

    /**
     * Start editing a model.
     */
    public function editModel(string $modelId): void
    {
        if (! isset($this->models[$modelId])) {
            $this->dispatch('show-toast', [
                'message' => 'Model not found',
                'type' => 'error',
            ]);

            return;
        }

        $this->editingModel = $modelId;
        $model = $this->models[$modelId];

        $this->editForm = [
            'name' => $model['name'],
            'enabled' => $model['enabled'],
            'max_tokens' => $model['max_tokens'],
            'temperature' => $model['temperature'],
            'cost_per_1k_tokens' => $model['cost_per_1k_tokens'],
            'rate_limit_per_minute' => $model['rate_limit_per_minute'],
            'description' => $model['description'],
        ];
    }

    /**
     * Cancel editing.
     */
    public function cancelEdit(): void
    {
        $this->editingModel = null;
        $this->editForm = [];
        $this->resetValidation();
    }

    /**
     * Save model configuration.
     */
    public function saveModel(): void
    {
        $this->validate();

        try {
            $configService = app(AIConfigurationService::class);

            $success = $configService->updateModelConfig($this->editingModel, $this->editForm);

            if ($success) {
                $this->dispatch('show-toast', [
                    'message' => 'Model configuration updated successfully',
                    'type' => 'success',
                ]);

                $this->cancelEdit();
                $this->loadConfiguration();
            } else {
                $this->dispatch('show-toast', [
                    'message' => 'Failed to update model configuration',
                    'type' => 'error',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Error updating configuration: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Toggle model enabled/disabled status.
     */
    public function toggleModel(string $modelId): void
    {
        if (! isset($this->models[$modelId])) {
            return;
        }

        try {
            $configService = app(AIConfigurationService::class);
            $newStatus = ! $this->models[$modelId]['enabled'];

            $success = $configService->toggleModel($modelId, $newStatus);

            if ($success) {
                $this->models[$modelId]['enabled'] = $newStatus;

                $this->dispatch('show-toast', [
                    'message' => 'Model '.($newStatus ? 'enabled' : 'disabled').' successfully',
                    'type' => 'success',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to toggle model: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Update system setting.
     */
    public function updateSystemSetting(string $key, mixed $value): void
    {
        try {
            $configService = app(AIConfigurationService::class);

            $success = $configService->updateSystemSettings([$key => $value]);

            if ($success) {
                $this->systemSettings[$key] = $value;

                $this->dispatch('show-toast', [
                    'message' => 'System setting updated successfully',
                    'type' => 'success',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to update setting: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Test model connection.
     */
    public function testModel(string $modelId): void
    {
        try {
            $configService = app(AIConfigurationService::class);
            $isAvailable = $configService->isModelAvailable($modelId);
            $status = $configService->getModelStatus($modelId);

            if ($isAvailable) {
                $this->dispatch('show-toast', [
                    'message' => 'Model connection test successful',
                    'type' => 'success',
                ]);
            } else {
                $this->dispatch('show-toast', [
                    'message' => "Model connection failed. Status: {$status}",
                    'type' => 'error',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Connection test failed: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Refresh performance metrics.
     */
    public function refreshPerformanceMetrics(): void
    {
        try {
            $configService = app(AIConfigurationService::class);
            $this->performanceMetrics = $configService->getModelPerformanceMetrics();

            $this->dispatch('show-toast', [
                'message' => 'Performance metrics refreshed',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to refresh metrics: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Reset configuration to defaults.
     */
    public function resetToDefaults(): void
    {
        try {
            $configService = app(AIConfigurationService::class);

            $success = $configService->resetToDefaults();

            if ($success) {
                $this->dispatch('show-toast', [
                    'message' => 'Configuration reset to defaults successfully',
                    'type' => 'success',
                ]);

                $this->loadConfiguration();
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to reset configuration: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Get model status badge class.
     */
    public function getModelStatusClass(string $status): string
    {
        return match ($status) {
            'available' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
            'disabled' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
            'missing_api_key' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
            'maintenance' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
        };
    }

    /**
     * Get model status display text.
     */
    public function getModelStatusText(string $status): string
    {
        return match ($status) {
            'available' => 'Available',
            'disabled' => 'Disabled',
            'missing_api_key' => 'Missing API Key',
            'maintenance' => 'Maintenance',
            'not_found' => 'Not Found',
            default => 'Unknown',
        };
    }

    /**
     * Get performance score class.
     */
    public function getPerformanceScoreClass(float $score): string
    {
        if ($score >= 80) {
            return 'text-green-600 dark:text-green-400';
        } elseif ($score >= 60) {
            return 'text-yellow-600 dark:text-yellow-400';
        } else {
            return 'text-red-600 dark:text-red-400';
        }
    }

    /**
     * Format performance score.
     */
    public function formatPerformanceScore(float $score): string
    {
        return number_format($score, 1);
    }

    /**
     * Format currency.
     */
    public function formatCurrency(float $amount): string
    {
        return '$'.number_format($amount, 4);
    }

    /**
     * Format percentage.
     */
    public function formatPercentage(float $value): string
    {
        return number_format($value, 1).'%';
    }

    /**
     * Format response time.
     */
    public function formatResponseTime(float $seconds): string
    {
        return number_format($seconds, 2).'s';
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.ai-configuration-manager');
    }
}
