<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\AI\AIConfigurationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * AI Configuration Dashboard for Admin Users.
 *
 * Provides interface for managing AI model availability, settings,
 * and system configuration in real-time.
 */
class AIConfigurationDashboard extends Component
{
    /** @var array<string, mixed> */
    public array $models = [];

    /** @var array<string, mixed> */
    public array $systemSettings = [];

    /** @var array<string, mixed> */
    public array $configHealth = [];

    public bool $loading = false;

    public string $selectedModel = '';

    /** @var array<string, mixed> */
    public array $modelEditForm = [];

    public bool $showModelEditModal = false;

    public bool $showSystemSettingsModal = false;

    /** @var array<string, string> */
    protected $listeners = [
        'refreshConfig' => 'loadConfiguration',
        'modelUpdated' => 'handleModelUpdate',
    ];

    /**
     * Component initialization.
     */
    public function mount(): void
    {
        // Check if user is admin
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Unauthorized access to AI configuration dashboard');
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
            $this->configHealth = $configService->getConfigurationHealth();
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
     * Toggle model availability.
     */
    public function toggleModel(string $modelId): void
    {
        try {
            $configService = app(AIConfigurationService::class);
            $currentModel = $this->models[$modelId] ?? null;

            if (! $currentModel) {
                throw new \Exception("Model {$modelId} not found");
            }

            $newStatus = ! $currentModel['enabled'];
            $result = $configService->toggleModel($modelId, $newStatus);

            if ($result) {
                $this->models[$modelId]['enabled'] = $newStatus;

                $this->dispatch('show-toast', [
                    'message' => "Model {$currentModel['name']} ".($newStatus ? 'enabled' : 'disabled'),
                    'type' => 'success',
                ]);

                // Refresh configuration to get updated status
                $this->loadConfiguration();
            } else {
                throw new \Exception('Failed to update model status');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Error updating model: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Set model maintenance mode.
     */
    public function setModelMaintenanceMode(string $modelId, bool $maintenanceMode): void
    {
        try {
            $configService = app(AIConfigurationService::class);
            $result = $configService->updateModelConfig($modelId, [
                'maintenance_mode' => $maintenanceMode,
            ]);

            if ($result) {
                $this->models[$modelId]['maintenance_mode'] = $maintenanceMode;

                $modelName = $this->models[$modelId]['name'];
                $status = $maintenanceMode ? 'maintenance mode' : 'active';

                $this->dispatch('show-toast', [
                    'message' => "Model {$modelName} set to {$status}",
                    'type' => 'success',
                ]);

                $this->loadConfiguration();
            } else {
                throw new \Exception('Failed to update maintenance mode');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Error updating maintenance mode: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Open model edit modal.
     */
    public function editModel(string $modelId): void
    {
        $model = $this->models[$modelId] ?? null;

        if (! $model) {
            $this->dispatch('show-toast', [
                'message' => 'Model not found',
                'type' => 'error',
            ]);

            return;
        }

        $this->selectedModel = $modelId;
        $this->modelEditForm = [
            'name' => $model['name'],
            'max_tokens' => $model['max_tokens'],
            'temperature' => $model['temperature'],
            'rate_limit_per_minute' => $model['rate_limit_per_minute'],
            'cost_per_1k_tokens' => $model['cost_per_1k_tokens'],
            'description' => $model['description'],
        ];

        $this->showModelEditModal = true;
    }

    /**
     * Save model configuration changes.
     */
    public function saveModelConfig(): void
    {
        $this->validate([
            'modelEditForm.name' => 'required|string|max:255',
            'modelEditForm.max_tokens' => 'required|integer|min:1|max:4000',
            'modelEditForm.temperature' => 'required|numeric|min:0|max:2',
            'modelEditForm.rate_limit_per_minute' => 'required|integer|min:1|max:1000',
            'modelEditForm.cost_per_1k_tokens' => 'required|numeric|min:0|max:100',
            'modelEditForm.description' => 'required|string|max:500',
        ]);

        try {
            $configService = app(AIConfigurationService::class);
            $result = $configService->updateModelConfig($this->selectedModel, $this->modelEditForm);

            if ($result) {
                $this->dispatch('show-toast', [
                    'message' => 'Model configuration updated successfully',
                    'type' => 'success',
                ]);

                $this->showModelEditModal = false;
                $this->loadConfiguration();
            } else {
                throw new \Exception('Failed to update model configuration');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Error saving configuration: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Open system settings modal.
     */
    public function openSystemSettings(): void
    {
        $this->showSystemSettingsModal = true;
    }

    /**
     * Update system settings.
     */
    public function updateSystemSettings(): void
    {
        $this->validate([
            'systemSettings.max_generations_per_user_per_hour' => 'required|integer|min:1|max:1000',
            'systemSettings.max_generations_per_user_per_day' => 'required|integer|min:1|max:10000',
            'systemSettings.timeout_seconds' => 'required|integer|min:5|max:300',
            'systemSettings.retry_attempts' => 'required|integer|min:1|max:10',
            'systemSettings.cache_ttl_minutes' => 'required|integer|min:1|max:1440',
        ]);

        try {
            $configService = app(AIConfigurationService::class);
            $result = $configService->updateSystemSettings($this->systemSettings);

            if ($result) {
                $this->dispatch('show-toast', [
                    'message' => 'System settings updated successfully',
                    'type' => 'success',
                ]);

                $this->showSystemSettingsModal = false;
                $this->loadConfiguration();
            } else {
                throw new \Exception('Failed to update system settings');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Error updating settings: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Toggle system maintenance mode.
     */
    public function toggleSystemMaintenance(): void
    {
        try {
            $configService = app(AIConfigurationService::class);
            $newMode = ! $this->systemSettings['maintenance_mode'];

            $result = $configService->updateSystemSettings([
                'maintenance_mode' => $newMode,
            ]);

            if ($result) {
                $this->systemSettings['maintenance_mode'] = $newMode;

                $status = $newMode ? 'enabled' : 'disabled';
                $this->dispatch('show-toast', [
                    'message' => "System maintenance mode {$status}",
                    'type' => $newMode ? 'warning' : 'success',
                ]);

                $this->loadConfiguration();
            } else {
                throw new \Exception('Failed to update maintenance mode');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Error updating maintenance mode: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Clear all configuration cache.
     */
    public function clearCache(): void
    {
        try {
            $configService = app(AIConfigurationService::class);
            $configService->clearConfigCache();

            $this->dispatch('show-toast', [
                'message' => 'Configuration cache cleared successfully',
                'type' => 'success',
            ]);

            $this->loadConfiguration();
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Error clearing cache: '.$e->getMessage(),
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
            $result = $configService->resetToDefaults();

            if ($result) {
                $this->dispatch('show-toast', [
                    'message' => 'Configuration reset to defaults successfully',
                    'type' => 'success',
                ]);

                $this->loadConfiguration();
            } else {
                throw new \Exception('Failed to reset configuration');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Error resetting configuration: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Get health status badge class.
     */
    public function getHealthStatusClass(): string
    {
        return match ($this->configHealth['status'] ?? 'unknown') {
            'healthy' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'degraded' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * Get model status badge class.
     */
    public function getModelStatusClass(string $status): string
    {
        return match ($status) {
            'available' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'maintenance' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'disabled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'missing_api_key' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * Handle model update event.
     */
    public function handleModelUpdate(string $modelId): void
    {
        $this->loadConfiguration();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.ai-configuration-dashboard');
    }
}
