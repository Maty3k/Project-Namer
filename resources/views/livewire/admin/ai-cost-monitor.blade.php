<div class="max-w-7xl mx-auto p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col space-y-4
                sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                AI Cost Monitor
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Track AI usage costs, budget limits, and spending patterns
            </p>
        </div>
        
        <div class="flex items-center space-x-3">
            <flux:select 
                wire:model.live="selectedPeriod"
                class="min-w-32">
                <option value="day">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
            </flux:select>
            
            <flux:button
                wire:click="loadCostData"
                variant="ghost"
                icon="arrow-path"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="loadCostData">Refresh</span>
                <span wire:loading wire:target="loadCostData">Loading...</span>
            </flux:button>
            
            <flux:dropdown>
                <flux:button variant="ghost" icon="arrow-down-tray">
                    Export
                </flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="exportCostData('csv')" icon="document-text">
                        Export CSV
                    </flux:menu.item>
                    <flux:menu.item wire:click="exportCostData('json')" icon="code-bracket">
                        Export JSON
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <!-- Loading State -->
    <div wire:loading.delay wire:target="loadCostData" class="flex justify-center py-8">
        <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
            <div class="animate-spin w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full"></div>
            <span>Loading cost data...</span>
        </div>
    </div>

    <!-- Main Content -->
    <div wire:loading.remove wire:target="loadCostData" class="space-y-6">
        <!-- Budget Overview -->
        <div class="grid gap-6
                    md:grid-cols-2">
            @if(!empty($budgetLimits))
                <!-- Daily Budget -->
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Daily Budget
                        </h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $this->getBudgetStatusClass($budgetLimits['daily']) }}">
                            {{ $this->getBudgetStatusText($budgetLimits['daily']) }}
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $this->formatCurrency($budgetLimits['daily']['spent']) }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                of {{ $this->formatCurrency($budgetLimits['daily']['budget']) }}
                            </span>
                        </div>
                        
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="h-2 rounded-full {{ $budgetLimits['daily']['exceeded'] ? 'bg-red-500' : ($budgetLimits['daily']['alert_needed'] ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min(100, $budgetLimits['daily']['percentage']) }}%"></div>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ $this->formatPercentage($budgetLimits['daily']['percentage']) }} used</span>
                            <span>{{ $this->formatCurrency($budgetLimits['daily']['remaining']) }} remaining</span>
                        </div>
                    </div>
                </flux:card>
                
                <!-- Monthly Budget -->
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Monthly Budget
                        </h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $this->getBudgetStatusClass($budgetLimits['monthly']) }}">
                            {{ $this->getBudgetStatusText($budgetLimits['monthly']) }}
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $this->formatCurrency($budgetLimits['monthly']['spent']) }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                of {{ $this->formatCurrency($budgetLimits['monthly']['budget']) }}
                            </span>
                        </div>
                        
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="h-2 rounded-full {{ $budgetLimits['monthly']['exceeded'] ? 'bg-red-500' : ($budgetLimits['monthly']['alert_needed'] ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min(100, $budgetLimits['monthly']['percentage']) }}%"></div>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ $this->formatPercentage($budgetLimits['monthly']['percentage']) }} used</span>
                            <span>{{ $this->formatCurrency($budgetLimits['monthly']['remaining']) }} remaining</span>
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- System Statistics -->
        @if(!empty($systemStats))
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        System Statistics ({{ ucfirst($selectedPeriod) }})
                    </h3>
                    <div class="flex items-center space-x-2 {{ $this->getTrendClass($costTrends) }}">
                        <flux:icon :name="$this->getTrendIcon($costTrends)" class="w-5 h-5" />
                        <span class="text-sm font-medium">Cost Trend</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4
                            md:grid-cols-4
                            lg:grid-cols-6">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Cost</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->formatCurrency($systemStats['total_cost'] ?? 0) }}
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Requests</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->formatNumber($systemStats['total_requests'] ?? 0) }}
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Success Rate</div>
                        <div class="text-lg font-semibold {{ ($systemStats['success_rate'] ?? 0) >= 95 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                            {{ $this->formatPercentage($systemStats['success_rate'] ?? 0) }}
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Tokens</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->formatNumber($systemStats['total_tokens'] ?? 0) }}
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Avg Response</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($systemStats['average_response_time'] ?? 0, 2) }}s
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Active Users</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $systemStats['active_users'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif

        <!-- Model Breakdown and Top Users -->
        <div class="grid gap-6
                    lg:grid-cols-2">
            <!-- Model Breakdown -->
            @if(!empty($systemStats['model_breakdown']))
                <flux:card>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Cost by Model
                    </h3>
                    
                    <div class="space-y-4">
                        @foreach($systemStats['model_breakdown'] as $modelId => $stats)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ str_replace('-', ' ', ucwords($modelId, '-')) }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $this->formatNumber($stats['requests']) }} requests • {{ $this->formatPercentage($stats['success_rate']) }} success
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $this->formatCurrency($stats['cost']) }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $this->formatNumber($stats['tokens']) }} tokens
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
            
            <!-- Top Users -->
            @if(!empty($topUsers))
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Top Spending Users
                        </h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ ucfirst($selectedPeriod) }}
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($topUsers as $index => $user)
                            <div class="flex items-center justify-between p-3 {{ $index < 3 ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800' }} rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="flex items-center justify-center w-8 h-8 {{ $index < 3 ? 'bg-blue-100 dark:bg-blue-800' : 'bg-gray-200 dark:bg-gray-700' }} rounded-full">
                                        <span class="text-sm font-medium {{ $index < 3 ? 'text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300' }}">
                                            {{ $index + 1 }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $user['name'] }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $this->formatNumber($user['total_requests']) }} requests
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $this->formatCurrency($user['total_cost']) }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $this->formatCurrency($user['avg_cost_per_request']) }}/req
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Actions -->
        <flux:card>
            <div class="flex flex-col space-y-4
                        sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Maintenance Actions
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Manage cost tracking data and system maintenance
                    </p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <flux:button
                        wire:click="cleanupOldLogs"
                        variant="ghost"
                        icon="trash"
                        wire:confirm="This will permanently delete usage logs older than 90 days. Continue?">
                        Cleanup Old Logs
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </div>
</div>