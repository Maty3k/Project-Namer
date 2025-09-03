<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        AI Configuration Dashboard
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Manage AI model availability, settings, and system configuration
                    </p>
                </div>
                
                <div class="flex space-x-3">
                    <flux:button
                        wire:click="clearCache"
                        variant="outline"
                        size="sm"
                        :loading="$loading">
                        Clear Cache
                    </flux:button>
                    
                    <flux:button
                        wire:click="loadConfiguration"
                        variant="primary"
                        size="sm"
                        :loading="$loading">
                        Refresh
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- System Health Overview -->
        <div class="mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    System Health Status
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $configHealth['total_models'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Models</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ $configHealth['enabled_models'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Enabled Models</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ $configHealth['maintenance_models'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">In Maintenance</div>
                    </div>
                    
                    <div class="text-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $this->getHealthStatusClass() }}">
                            {{ ucfirst($configHealth['status'] ?? 'unknown') }}
                        </span>
                    </div>
                </div>

                @if($configHealth['invalid_models'] ?? 0 > 0)
                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                        <div class="flex">
                            <flux:icon.exclamation-triangle class="h-5 w-5 text-red-400" />
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    Configuration Issues Detected
                                </h3>
                                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                    {{ $configHealth['invalid_models'] }} model(s) have invalid configurations that need attention.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- System Settings Card -->
        <div class="mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        System Settings
                    </h2>
                    
                    <div class="flex space-x-3">
                        <flux:button
                            wire:click="toggleSystemMaintenance"
                            variant="{{ ($systemSettings['maintenance_mode'] ?? false) ? 'danger' : 'outline' }}"
                            size="sm">
                            {{ ($systemSettings['maintenance_mode'] ?? false) ? 'Disable' : 'Enable' }} Maintenance
                        </flux:button>
                        
                        <flux:button
                            wire:click="openSystemSettings"
                            variant="primary"
                            size="sm">
                            Edit Settings
                        </flux:button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Hourly Limit</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $systemSettings['max_generations_per_user_per_hour'] ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Daily Limit</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $systemSettings['max_generations_per_user_per_day'] ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Cache TTL</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $systemSettings['cache_ttl_minutes'] ?? 'N/A' }}m
                        </div>
                    </div>
                </div>

                @if($systemSettings['maintenance_mode'] ?? false)
                    <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                        <div class="flex">
                            <flux:icon.exclamation-triangle class="h-5 w-5 text-yellow-400" />
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    System Maintenance Mode Active
                                </h3>
                                <div class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                    All AI generation features are currently disabled for maintenance.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- AI Models Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    AI Models Configuration
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Model
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Provider
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Cost/1K
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Rate Limit
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($models as $modelId => $model)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $model['name'] }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $model['model_id'] }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white capitalize">
                                        {{ $model['provider'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getModelStatusClass($model['status'] ?? 'unknown') }}">
                                        {{ ucfirst($model['status'] ?? 'unknown') }}
                                    </span>
                                    @if($model['maintenance_mode'] ?? false)
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                Maintenance
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        ${{ number_format($model['cost_per_1k_tokens'] ?? 0, 4) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $model['rate_limit_per_minute'] ?? 'N/A' }}/min
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center space-x-2">
                                        <flux:button
                                            wire:click="toggleModel('{{ $modelId }}')"
                                            variant="{{ $model['enabled'] ? 'danger' : 'primary' }}"
                                            size="xs">
                                            {{ $model['enabled'] ? 'Disable' : 'Enable' }}
                                        </flux:button>
                                        
                                        <flux:button
                                            wire:click="setModelMaintenanceMode('{{ $modelId }}', {{ ($model['maintenance_mode'] ?? false) ? 'false' : 'true' }})"
                                            variant="outline"
                                            size="xs">
                                            {{ ($model['maintenance_mode'] ?? false) ? 'Exit' : 'Enter' }} Maintenance
                                        </flux:button>
                                        
                                        <flux:button
                                            wire:click="editModel('{{ $modelId }}')"
                                            variant="ghost"
                                            size="xs">
                                            Edit
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No AI models configured
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-red-200 dark:border-red-700">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">
                    Danger Zone
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    These actions will reset all configuration to defaults and cannot be undone.
                </p>
                
                <flux:button
                    wire:click="resetToDefaults"
                    variant="danger"
                    size="sm"
                    wire:confirm="Are you sure you want to reset all AI configuration to defaults? This cannot be undone.">
                    Reset to Defaults
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Model Edit Modal -->
    <flux:modal name="model-edit" :show="$showModelEditModal" wire:model="showModelEditModal">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Edit Model Configuration
            </h2>

            <form wire:submit="saveModelConfig" class="space-y-4">
                <flux:field>
                    <flux:label>Model Name</flux:label>
                    <flux:input
                        wire:model="modelEditForm.name"
                        placeholder="Enter model name" />
                    <flux:error name="modelEditForm.name" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Max Tokens</flux:label>
                        <flux:input
                            type="number"
                            wire:model="modelEditForm.max_tokens"
                            min="1"
                            max="4000" />
                        <flux:error name="modelEditForm.max_tokens" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Temperature</flux:label>
                        <flux:input
                            type="number"
                            step="0.1"
                            wire:model="modelEditForm.temperature"
                            min="0"
                            max="2" />
                        <flux:error name="modelEditForm.temperature" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Rate Limit (per minute)</flux:label>
                        <flux:input
                            type="number"
                            wire:model="modelEditForm.rate_limit_per_minute"
                            min="1"
                            max="1000" />
                        <flux:error name="modelEditForm.rate_limit_per_minute" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Cost per 1K tokens ($)</flux:label>
                        <flux:input
                            type="number"
                            step="0.0001"
                            wire:model="modelEditForm.cost_per_1k_tokens"
                            min="0" />
                        <flux:error name="modelEditForm.cost_per_1k_tokens" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea
                        wire:model="modelEditForm.description"
                        placeholder="Enter model description"
                        rows="3" />
                    <flux:error name="modelEditForm.description" />
                </flux:field>

                <div class="flex justify-end space-x-3 pt-4">
                    <flux:button 
                        type="button" 
                        wire:click="$set('showModelEditModal', false)"
                        variant="outline">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- System Settings Modal -->
    <flux:modal name="system-settings" :show="$showSystemSettingsModal" wire:model="showSystemSettingsModal">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                System Settings
            </h2>

            <form wire:submit="updateSystemSettings" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Hourly Generation Limit</flux:label>
                        <flux:input
                            type="number"
                            wire:model="systemSettings.max_generations_per_user_per_hour"
                            min="1"
                            max="1000" />
                        <flux:error name="systemSettings.max_generations_per_user_per_hour" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Daily Generation Limit</flux:label>
                        <flux:input
                            type="number"
                            wire:model="systemSettings.max_generations_per_user_per_day"
                            min="1"
                            max="10000" />
                        <flux:error name="systemSettings.max_generations_per_user_per_day" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>Timeout (seconds)</flux:label>
                        <flux:input
                            type="number"
                            wire:model="systemSettings.timeout_seconds"
                            min="5"
                            max="300" />
                        <flux:error name="systemSettings.timeout_seconds" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Retry Attempts</flux:label>
                        <flux:input
                            type="number"
                            wire:model="systemSettings.retry_attempts"
                            min="1"
                            max="10" />
                        <flux:error name="systemSettings.retry_attempts" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Cache TTL (minutes)</flux:label>
                        <flux:input
                            type="number"
                            wire:model="systemSettings.cache_ttl_minutes"
                            min="1"
                            max="1440" />
                        <flux:error name="systemSettings.cache_ttl_minutes" />
                    </flux:field>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <flux:button 
                        type="button" 
                        wire:click="$set('showSystemSettingsModal', false)"
                        variant="outline">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Settings
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>