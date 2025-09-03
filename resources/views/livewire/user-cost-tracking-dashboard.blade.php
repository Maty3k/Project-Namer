<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Usage & Cost Tracking
                </h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    Monitor your AI generation usage, costs, and remaining limits
                </p>
            </div>
            
            <div class="mt-4 md:mt-0 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                <flux:select wire:model.live="selectedPeriod" size="sm">
                    <option value="day">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </flux:select>
                
                <div class="flex space-x-2">
                    <flux:button
                        wire:click="exportUsageData('csv')"
                        variant="outline"
                        size="sm">
                        Export CSV
                    </flux:button>
                    
                    <flux:button
                        wire:click="loadUsageData"
                        variant="primary"
                        size="sm"
                        :loading="$loading">
                        Refresh
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Limits Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Hourly Limits -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Hourly Usage
                </h2>
                <div class="text-2xl font-bold {{ ($usageLimits['hourly']['exceeded'] ?? false) ? 'text-red-500' : 'text-green-500' }}">
                    {{ $usageLimits['hourly']['used'] ?? 0 }}/{{ $usageLimits['hourly']['limit'] ?? 0 }}
                </div>
            </div>
            
            <div class="mb-3">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Usage Progress</span>
                    <span>{{ round($usageLimits['hourly']['percentage'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                        class="h-2 rounded-full transition-all duration-300 {{ $this->getUsagePercentageClass($usageLimits['hourly']['percentage'] ?? 0) }}"
                        style="width: {{ min(100, $usageLimits['hourly']['percentage'] ?? 0) }}%">
                    </div>
                </div>
            </div>
            
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $usageLimits['hourly']['remaining'] ?? 0 }} requests remaining this hour
            </div>
            
            @if($usageLimits['hourly']['exceeded'] ?? false)
                <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                    <div class="flex">
                        <flux:icon.exclamation-triangle class="h-4 w-4 text-red-400 mr-2 mt-0.5" />
                        <div class="text-sm text-red-800 dark:text-red-200">
                            Hourly limit exceeded. Resets at the top of the next hour.
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Daily Limits -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Daily Usage
                </h2>
                <div class="text-2xl font-bold {{ ($usageLimits['daily']['exceeded'] ?? false) ? 'text-red-500' : 'text-green-500' }}">
                    {{ $usageLimits['daily']['used'] ?? 0 }}/{{ $usageLimits['daily']['limit'] ?? 0 }}
                </div>
            </div>
            
            <div class="mb-3">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Usage Progress</span>
                    <span>{{ round($usageLimits['daily']['percentage'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                        class="h-2 rounded-full transition-all duration-300 {{ $this->getUsagePercentageClass($usageLimits['daily']['percentage'] ?? 0) }}"
                        style="width: {{ min(100, $usageLimits['daily']['percentage'] ?? 0) }}%">
                    </div>
                </div>
            </div>
            
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $usageLimits['daily']['remaining'] ?? 0 }} requests remaining today
            </div>
            
            @if($usageLimits['daily']['exceeded'] ?? false)
                <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                    <div class="flex">
                        <flux:icon.exclamation-triangle class="h-4 w-4 text-red-400 mr-2 mt-0.5" />
                        <div class="text-sm text-red-800 dark:text-red-200">
                            Daily limit exceeded. Resets at midnight.
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <flux:icon.chart-bar class="h-8 w-8 text-blue-500" />
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Requests</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($usageStats['total_requests'] ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <flux:icon.currency-dollar class="h-8 w-8 text-green-500" />
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Cost</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format($usageStats['total_cost'] ?? 0, 4) }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <flux:icon.check-circle class="h-8 w-8 text-emerald-500" />
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Success Rate</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ round($usageStats['success_rate'] ?? 0, 1) }}%
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <flux:icon.clock class="h-8 w-8 text-purple-500" />
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Avg Response</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ round($usageStats['average_response_time'] ?? 0, 2) }}s
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Model Usage Breakdown -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Model Usage Breakdown
            </h2>
            
            @if(!empty($usageStats['model_breakdown']))
                <div class="space-y-4">
                    @foreach($usageStats['model_breakdown'] as $modelId => $stats)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ ucfirst(str_replace(['-', '_'], ' ', $modelId)) }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($stats['requests']) }} requests • {{ number_format($stats['tokens']) }} tokens
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    ${{ number_format($stats['cost'], 4) }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    ${{ number_format($stats['cost'] / max($stats['requests'], 1), 4) }}/req
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <flux:icon.chart-pie class="h-12 w-12 mx-auto mb-4 opacity-50" />
                    <p>No usage data for the selected period</p>
                </div>
            @endif
        </div>

        <!-- Usage Insights -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Usage Insights
            </h2>
            
            <div class="space-y-4">
                <!-- Most Used Model -->
                @php $mostUsedModel = $this->getMostUsedModel(); @endphp
                @if($mostUsedModel)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-center">
                            <flux:icon.star class="h-5 w-5 text-blue-500 mr-3" />
                            <div>
                                <div class="font-medium text-blue-900 dark:text-blue-100">
                                    Most Used Model
                                </div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">
                                    {{ ucfirst(str_replace(['-', '_'], ' ', $mostUsedModel['id'])) }} with {{ $mostUsedModel['requests'] }} requests
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Cost Efficiency -->
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center">
                        <flux:icon.currency-dollar class="h-5 w-5 text-green-500 mr-3" />
                        <div>
                            <div class="font-medium text-green-900 dark:text-green-100">
                                Cost Efficiency
                            </div>
                            <div class="text-sm text-green-700 dark:text-green-300">
                                @php $efficiency = $this->getCostEfficiencyRating(); @endphp
                                <span class="capitalize">{{ $efficiency }}</span> 
                                @if($efficiency !== 'unknown')
                                    - ${{ number_format(($usageStats['total_cost'] ?? 0) / max($usageStats['total_requests'] ?? 1, 1), 4) }} per request
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cost Trend -->
                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                    <div class="flex items-center">
                        @php $trend = $this->getCostTrendDirection(); @endphp
                        @if($trend === 'increasing')
                            <flux:icon.arrow-trending-up class="h-5 w-5 text-red-500 mr-3" />
                            <div class="text-red-900 dark:text-red-100">
                                <div class="font-medium">Costs Increasing</div>
                                <div class="text-sm text-red-700 dark:text-red-300">Usage costs are trending upward</div>
                            </div>
                        @elseif($trend === 'decreasing')
                            <flux:icon.arrow-trending-down class="h-5 w-5 text-green-500 mr-3" />
                            <div class="text-green-900 dark:text-green-100">
                                <div class="font-medium">Costs Decreasing</div>
                                <div class="text-sm text-green-700 dark:text-green-300">Usage costs are trending downward</div>
                            </div>
                        @else
                            <flux:icon.minus class="h-5 w-5 text-purple-500 mr-3" />
                            <div class="text-purple-900 dark:text-purple-100">
                                <div class="font-medium">Stable Usage</div>
                                <div class="text-sm text-purple-700 dark:text-purple-300">Usage costs are stable</div>
                            </div>
                        @endif
                    </div>
                </div>

                @if(($usageStats['total_requests'] ?? 0) === 0)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <div class="flex items-center">
                            <flux:icon.information-circle class="h-5 w-5 text-gray-500 mr-3" />
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    No Usage Data
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    No AI requests made in the selected time period
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($loading)
        <div class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                <span class="text-gray-900 dark:text-white">Loading usage data...</span>
            </div>
        </div>
    @endif
</div>