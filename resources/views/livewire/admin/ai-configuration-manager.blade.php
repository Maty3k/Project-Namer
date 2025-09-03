<div class="max-w-7xl mx-auto p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col space-y-4
                sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                AI Configuration Manager
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Manage AI models, system settings, and monitor performance
            </p>
        </div>
        
        <div class="flex items-center space-x-3">
            <flux:button
                wire:click="refreshPerformanceMetrics"
                variant="ghost"
                icon="arrow-path"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="refreshPerformanceMetrics">Refresh</span>
                <span wire:loading wire:target="refreshPerformanceMetrics">Refreshing...</span>
            </flux:button>
            
            <flux:button
                wire:click="resetToDefaults"
                variant="danger"
                wire:confirm="Are you sure you want to reset all configuration to defaults? This cannot be undone.">
                Reset to Defaults
            </flux:button>
        </div>
    </div>

    <!-- Loading State -->
    <div wire:loading.delay wire:target="loadConfiguration" class="flex justify-center py-8">
        <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
            <div class="animate-spin w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full"></div>
            <span>Loading configuration...</span>
        </div>
    </div>

    <!-- Main Content -->
    <div wire:loading.remove wire:target="loadConfiguration">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8" aria-label="Tabs">
                <button
                    wire:click="setActiveTab('models')"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors
                           {{ $activeTab === 'models' 
                              ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    AI Models
                </button>
                <button
                    wire:click="setActiveTab('system')"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors
                           {{ $activeTab === 'system' 
                              ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    System Settings
                </button>
                <button
                    wire:click="setActiveTab('performance')"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors
                           {{ $activeTab === 'performance' 
                              ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Performance
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="mt-6">
            @if($activeTab === 'models')
                <!-- AI Models Tab -->
                <div class="space-y-6">
                    @if($editingModel)
                        <!-- Edit Model Form -->
                        <flux:card>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Edit Model: {{ $models[$editingModel]['name'] ?? 'Unknown' }}
                                </h3>
                                <flux:button
                                    wire:click="cancelEdit"
                                    variant="ghost"
                                    icon="x-mark">
                                    Cancel
                                </flux:button>
                            </div>
                            
                            <form wire:submit.prevent="saveModel" class="space-y-4">
                                <div class="grid grid-cols-1 gap-4
                                            md:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Model Name</flux:label>
                                        <flux:input 
                                            wire:model="editForm.name"
                                            placeholder="Enter model name"
                                            required />
                                        <flux:error name="editForm.name" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Max Tokens</flux:label>
                                        <flux:input 
                                            wire:model="editForm.max_tokens"
                                            type="number"
                                            min="1"
                                            max="4000"
                                            required />
                                        <flux:error name="editForm.max_tokens" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Temperature</flux:label>
                                        <flux:input 
                                            wire:model="editForm.temperature"
                                            type="number"
                                            step="0.1"
                                            min="0"
                                            max="2"
                                            required />
                                        <flux:error name="editForm.temperature" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Cost per 1k Tokens ($)</flux:label>
                                        <flux:input 
                                            wire:model="editForm.cost_per_1k_tokens"
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            required />
                                        <flux:error name="editForm.cost_per_1k_tokens" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Rate Limit (per minute)</flux:label>
                                        <flux:input 
                                            wire:model="editForm.rate_limit_per_minute"
                                            type="number"
                                            min="1"
                                            max="1000"
                                            required />
                                        <flux:error name="editForm.rate_limit_per_minute" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:checkbox wire:model="editForm.enabled">
                                            Model Enabled
                                        </flux:checkbox>
                                    </flux:field>
                                </div>

                                <flux:field>
                                    <flux:label>Description</flux:label>
                                    <flux:textarea 
                                        wire:model="editForm.description"
                                        placeholder="Enter model description"
                                        rows="3"
                                        required />
                                    <flux:error name="editForm.description" />
                                </flux:field>

                                <div class="flex justify-end space-x-3">
                                    <flux:button
                                        type="button"
                                        wire:click="cancelEdit"
                                        variant="ghost">
                                        Cancel
                                    </flux:button>
                                    <flux:button
                                        type="submit"
                                        variant="primary">
                                        Save Changes
                                    </flux:button>
                                </div>
                            </form>
                        </flux:card>
                    @endif

                    <!-- Models List -->
                    <div class="grid gap-6">
                        @forelse($models as $modelId => $model)
                            <flux:card class="{{ !$model['enabled'] ? 'opacity-75' : '' }}">
                                <div class="flex flex-col space-y-4
                                            sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $model['name'] }}
                                            </h3>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $this->getModelStatusClass($model['status']) }}">
                                                {{ $this->getModelStatusText($model['status']) }}
                                            </span>
                                            @if($model['enabled'])
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Enabled
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                            {{ $model['description'] }}
                                        </p>
                                        
                                        <div class="grid grid-cols-2 gap-4 text-sm
                                                    md:grid-cols-4">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Provider:</span>
                                                <span class="ml-1 font-medium text-gray-900 dark:text-white capitalize">{{ $model['provider'] }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Max Tokens:</span>
                                                <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $model['max_tokens'] }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Temperature:</span>
                                                <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $model['temperature'] }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Cost:</span>
                                                <span class="ml-1 font-medium text-gray-900 dark:text-white">${{ $model['cost_per_1k_tokens'] }}/1k</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col space-y-2
                                                sm:flex-row sm:space-y-0 sm:space-x-2">
                                        <flux:button
                                            wire:click="testModel('{{ $modelId }}')"
                                            variant="ghost"
                                            icon="signal">
                                            Test
                                        </flux:button>
                                        <flux:button
                                            wire:click="editModel('{{ $modelId }}')"
                                            variant="ghost"
                                            icon="pencil">
                                            Edit
                                        </flux:button>
                                        <flux:button
                                            wire:click="toggleModel('{{ $modelId }}')"
                                            variant="{{ $model['enabled'] ? 'danger' : 'primary' }}"
                                            icon="{{ $model['enabled'] ? 'pause' : 'play' }}">
                                            {{ $model['enabled'] ? 'Disable' : 'Enable' }}
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:card>
                        @empty
                            <flux:card class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">No AI models configured</p>
                            </flux:card>
                        @endforelse
                    </div>
                </div>

            @elseif($activeTab === 'system')
                <!-- System Settings Tab -->
                <div class="space-y-6">
                    <flux:card>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            General Settings
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">Maintenance Mode</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Temporarily disable AI generation system</p>
                                </div>
                                <flux:checkbox 
                                    :checked="$systemSettings['maintenance_mode'] ?? false"
                                    wire:change="updateSystemSetting('maintenance_mode', $event.target.checked)" />
                            </div>
                            
                            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">Enable Analytics</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Track usage and performance metrics</p>
                                </div>
                                <flux:checkbox 
                                    :checked="$systemSettings['enable_analytics'] ?? false"
                                    wire:change="updateSystemSetting('enable_analytics', $event.target.checked)" />
                            </div>
                            
                            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">Enable Cost Tracking</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Monitor API usage costs</p>
                                </div>
                                <flux:checkbox 
                                    :checked="$systemSettings['enable_cost_tracking'] ?? false"
                                    wire:change="updateSystemSetting('enable_cost_tracking', $event.target.checked)" />
                            </div>
                            
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">Admin Notifications</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Receive system alerts and updates</p>
                                </div>
                                <flux:checkbox 
                                    :checked="$systemSettings['admin_notifications'] ?? false"
                                    wire:change="updateSystemSetting('admin_notifications', $event.target.checked)" />
                            </div>
                        </div>
                    </flux:card>
                    
                    <flux:card>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Usage Limits
                        </h3>
                        
                        <div class="grid grid-cols-1 gap-4
                                    md:grid-cols-2">
                            <flux:field>
                                <flux:label>Max Generations per User per Hour</flux:label>
                                <flux:input 
                                    type="number"
                                    min="1"
                                    max="1000"
                                    :value="$systemSettings['max_generations_per_user_per_hour'] ?? 50"
                                    wire:change="updateSystemSetting('max_generations_per_user_per_hour', $event.target.value)" />
                            </flux:field>
                            
                            <flux:field>
                                <flux:label>Max Generations per User per Day</flux:label>
                                <flux:input 
                                    type="number"
                                    min="1"
                                    max="10000"
                                    :value="$systemSettings['max_generations_per_user_per_day'] ?? 200"
                                    wire:change="updateSystemSetting('max_generations_per_user_per_day', $event.target.value)" />
                            </flux:field>
                            
                            <flux:field>
                                <flux:label>Cache TTL (minutes)</flux:label>
                                <flux:input 
                                    type="number"
                                    min="1"
                                    max="1440"
                                    :value="$systemSettings['cache_ttl_minutes'] ?? 60"
                                    wire:change="updateSystemSetting('cache_ttl_minutes', $event.target.value)" />
                            </flux:field>
                            
                            <flux:field>
                                <flux:label>API Timeout (seconds)</flux:label>
                                <flux:input 
                                    type="number"
                                    min="5"
                                    max="300"
                                    :value="$systemSettings['timeout_seconds'] ?? 30"
                                    wire:change="updateSystemSetting('timeout_seconds', $event.target.value)" />
                            </flux:field>
                        </div>
                    </flux:card>
                </div>

            @elseif($activeTab === 'performance')
                <!-- Performance Tab -->
                <div class="space-y-6">
                    @if(!empty($performanceMetrics))
                        @foreach($performanceMetrics as $modelId => $metrics)
                            @php $model = $models[$modelId] ?? null @endphp
                            @if($model)
                                <flux:card>
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $model['name'] }}
                                        </h3>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $this->getModelStatusClass($model['status']) }}">
                                            {{ $this->getModelStatusText($model['status']) }}
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4
                                                md:grid-cols-5">
                                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Response Time</div>
                                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $this->formatResponseTime($metrics['average_response_time'] ?? 0) }}
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Success Rate</div>
                                            <div class="text-lg font-semibold {{ $metrics['success_rate'] >= 95 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                                {{ $this->formatPercentage($metrics['success_rate'] ?? 0) }}
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">24h Usage</div>
                                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ number_format($metrics['usage_count_24h'] ?? 0) }}
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Error Rate</div>
                                            <div class="text-lg font-semibold {{ $metrics['error_rate'] <= 5 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $this->formatPercentage($metrics['error_rate'] ?? 0) }}
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Efficiency Score</div>
                                            <div class="text-lg font-semibold {{ $this->getPerformanceScoreClass($metrics['cost_efficiency'] ?? 0) }}">
                                                {{ $this->formatPerformanceScore($metrics['cost_efficiency'] ?? 0) }}
                                            </div>
                                        </div>
                                    </div>
                                </flux:card>
                            @endif
                        @endforeach
                    @else
                        <flux:card class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No performance data available</p>
                            <flux:button
                                wire:click="refreshPerformanceMetrics"
                                variant="ghost"
                                class="mt-2">
                                Load Performance Data
                            </flux:button>
                        </flux:card>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>