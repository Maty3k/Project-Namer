@props([
    'analytics' => [],
    'period' => 'month',
    'isAdmin' => false,
])

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    {{-- Analytics Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $isAdmin ? 'System AI Analytics' : 'Your AI Usage Analytics' }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Insights and performance metrics for AI name generation
                </p>
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="period" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="day">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                    <option value="all">All Time</option>
                </select>
                <flux:button variant="ghost" size="sm" wire:click="refreshAnalytics">
                    <flux:icon.arrow-path class="size-4" />
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Overview Stats Cards --}}
    @if(isset($analytics['overview']))
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Generations</p>
                        <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-1">
                            {{ number_format($analytics['overview']['total_generations'] ?? 0) }}
                        </p>
                    </div>
                    <flux:icon.cpu-chip class="size-8 text-blue-500" />
                </div>
                @if(isset($analytics['overview']['average_names_per_generation']))
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-2">
                        {{ round($analytics['overview']['average_names_per_generation'], 1) }} names per generation
                    </p>
                @endif
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">Names Generated</p>
                        <p class="text-3xl font-bold text-green-900 dark:text-green-100 mt-1">
                            {{ number_format($analytics['overview']['total_names_generated'] ?? 0) }}
                        </p>
                    </div>
                    <flux:icon.sparkles class="size-8 text-green-500" />
                </div>
                @if(isset($analytics['overview']['successful_generations'], $analytics['overview']['total_generations']) && $analytics['overview']['total_generations'] > 0)
                    <p class="text-xs text-green-700 dark:text-green-300 mt-2">
                        {{ round(($analytics['overview']['successful_generations'] / $analytics['overview']['total_generations']) * 100, 1) }}% success rate
                    </p>
                @endif
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Favorite Model</p>
                        <p class="text-lg font-bold text-purple-900 dark:text-purple-100 mt-1">
                            {{ $analytics['overview']['most_used_model'] ?? 'None' }}
                        </p>
                    </div>
                    <flux:icon.star class="size-8 text-purple-500" />
                </div>
                @if(isset($analytics['overview']['favorite_generation_mode']))
                    <p class="text-xs text-purple-700 dark:text-purple-300 mt-2">
                        Prefers {{ ucfirst($analytics['overview']['favorite_generation_mode']) }} mode
                    </p>
                @endif
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-lg p-6 border border-orange-200 dark:border-orange-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-orange-600 dark:text-orange-400">Processing Time</p>
                        <p class="text-2xl font-bold text-orange-900 dark:text-orange-100 mt-1">
                            @if(isset($analytics['overview']['total_processing_time']))
                                @php
                                    $totalSeconds = $analytics['overview']['total_processing_time'];
                                    $minutes = intval($totalSeconds / 60);
                                    $seconds = $totalSeconds % 60;
                                @endphp
                                {{ $minutes }}m {{ $seconds }}s
                            @else
                                0s
                            @endif
                        </p>
                    </div>
                    <flux:icon.clock class="size-8 text-orange-500" />
                </div>
                @if(isset($analytics['performance_metrics']['average_response_time']))
                    <p class="text-xs text-orange-700 dark:text-orange-300 mt-2">
                        {{ round($analytics['performance_metrics']['average_response_time'] / 1000, 1) }}s avg response time
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- Model Usage Analysis --}}
    @if(isset($analytics['model_usage']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <flux:icon.cpu-chip class="size-5 text-blue-500" />
                AI Model Usage Analysis
            </h3>

            @if(isset($analytics['model_usage']['model_usage_counts']) && !empty($analytics['model_usage']['model_usage_counts']))
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Usage Distribution --}}
                    <div>
                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Usage Distribution</h4>
                        <div class="space-y-3">
                            @php
                                $modelNames = [
                                    'gpt-4' => 'GPT-4',
                                    'claude-3.5-sonnet' => 'Claude 3.5',
                                    'gemini-1.5-pro' => 'Gemini Pro',
                                    'grok-beta' => 'Grok'
                                ];
                                $total = array_sum($analytics['model_usage']['model_usage_counts']);
                            @endphp
                            @foreach($analytics['model_usage']['model_usage_counts'] as $modelId => $count)
                                @php
                                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                                    $modelName = $modelNames[$modelId] ?? ucfirst(str_replace('-', ' ', $modelId));
                                @endphp
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-4 h-4 rounded bg-blue-{{ 400 + (array_search($modelId, array_keys($analytics['model_usage']['model_usage_counts'])) * 100) }}"></div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $modelName }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $count }}</span>
                                        <span class="text-xs text-gray-500 ml-2">{{ round($percentage, 1) }}%</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-blue-{{ 400 + (array_search($modelId, array_keys($analytics['model_usage']['model_usage_counts'])) * 100) }} h-2 rounded-full transition-all duration-500" 
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Performance Metrics --}}
                    <div>
                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Model Performance</h4>
                        <div class="space-y-4">
                            @foreach($analytics['model_usage']['model_performance'] as $modelId => $performance)
                                @php
                                    $modelName = $modelNames[$modelId] ?? ucfirst(str_replace('-', ' ', $modelId));
                                @endphp
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $modelName }}</span>
                                        <div class="flex items-center gap-2">
                                            @if($performance['success_rate'] >= 95)
                                                <flux:icon.check-circle class="size-4 text-green-500" />
                                            @elseif($performance['success_rate'] >= 80)
                                                <flux:icon.exclamation-triangle class="size-4 text-yellow-500" />
                                            @else
                                                <flux:icon.x-circle class="size-4 text-red-500" />
                                            @endif
                                            <span class="text-xs font-medium {{ $performance['success_rate'] >= 95 ? 'text-green-600' : ($performance['success_rate'] >= 80 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ round($performance['success_rate'], 1) }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-xs">
                                        <div>
                                            <span class="text-gray-500">Response</span>
                                            <div class="font-medium">{{ round($performance['average_response_time'] / 1000, 1) }}s</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Cost</span>
                                            <div class="font-medium">${{ number_format($performance['total_cost'] / 100, 2) }}</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Rating</span>
                                            <div class="font-medium">{{ round($performance['average_rating'], 1) }}/5</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <flux:icon.chart-bar class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No model usage data available for this period.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Generation Trends Chart --}}
    @if(isset($analytics['generation_trends']['daily_trends']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <flux:icon.chart-bar class="size-5 text-blue-500" />
                    Generation Trends
                </h3>
                @if(isset($analytics['generation_trends']['growth_rate']))
                    <div class="flex items-center gap-2">
                        @if($analytics['generation_trends']['growth_rate'] > 0)
                            <flux:icon.arrow-trending-up class="size-4 text-green-500" />
                            <span class="text-sm font-medium text-green-600">+{{ number_format($analytics['generation_trends']['growth_rate'], 1) }}%</span>
                        @elseif($analytics['generation_trends']['growth_rate'] < 0)
                            <flux:icon.arrow-trending-down class="size-4 text-red-500" />
                            <span class="text-sm font-medium text-red-600">{{ number_format($analytics['generation_trends']['growth_rate'], 1) }}%</span>
                        @else
                            <flux:icon.minus class="size-4 text-gray-500" />
                            <span class="text-sm font-medium text-gray-500">0%</span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="h-64 flex items-end justify-between gap-1 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                @foreach($analytics['generation_trends']['daily_trends'] as $day)
                    @php
                        $maxGenerations = max(array_column($analytics['generation_trends']['daily_trends'], 'generations'));
                        $height = $maxGenerations > 0 ? ($day['generations'] / $maxGenerations) * 100 : 0;
                    @endphp
                    <div class="flex flex-col items-center group relative flex-1">
                        <div class="bg-blue-500 hover:bg-blue-600 transition-colors rounded-t w-full transition-all duration-300"
                             style="height: {{ $height }}%"
                             title="{{ $day['generations'] }} generations on {{ $day['date'] }}">
                        </div>
                        <span class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-left">
                            {{ date('M j', strtotime($day['date'])) }}
                        </span>
                        
                        {{-- Tooltip --}}
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                            {{ $day['generations'] }} generations<br>
                            {{ round($day['average_names'], 1) }} avg names
                        </div>
                    </div>
                @endforeach
            </div>

            @if(isset($analytics['generation_trends']['peak_usage_day']))
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    Peak usage: {{ $analytics['generation_trends']['peak_usage_day'] }}
                    @if(isset($analytics['generation_trends']['usage_consistency']))
                        • Consistency score: {{ round($analytics['generation_trends']['usage_consistency'], 1) }}%
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Cost Analysis --}}
    @if(isset($analytics['cost_analysis']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <flux:icon.currency-dollar class="size-5 text-green-500" />
                Cost Analysis
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4">
                    <div class="text-sm font-medium text-green-600 dark:text-green-400">Total Cost</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                        ${{ number_format(($analytics['cost_analysis']['total_cost_cents'] ?? 0) / 100, 2) }}
                    </div>
                    @if(isset($analytics['cost_analysis']['cost_per_generation']))
                        <div class="text-xs text-green-700 dark:text-green-300">
                            ${{ number_format($analytics['cost_analysis']['cost_per_generation'] / 100, 4) }} per generation
                        </div>
                    @endif
                </div>

                <div class="col-span-2">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Cost by Model</div>
                    @if(isset($analytics['cost_analysis']['cost_by_model']) && !empty($analytics['cost_analysis']['cost_by_model']))
                        <div class="space-y-2">
                            @foreach($analytics['cost_analysis']['cost_by_model'] as $model => $cost)
                                @php
                                    $modelName = $modelNames[$model] ?? ucfirst(str_replace('-', ' ', $model));
                                    $percentage = $analytics['cost_analysis']['total_cost_cents'] > 0 ? 
                                                 ($cost / $analytics['cost_analysis']['total_cost_cents']) * 100 : 0;
                                @endphp
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $modelName }}</span>
                                    <div class="text-right">
                                        <span class="text-sm font-medium">${{ number_format($cost / 100, 2) }}</span>
                                        <span class="text-xs text-gray-500 ml-2">{{ round($percentage, 1) }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No cost data available.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Real-time Metrics (Admin Only) --}}
    @if($isAdmin && isset($analytics['realtime_metrics']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <flux:icon.signal class="size-5 text-blue-500" />
                Real-time System Metrics
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Active Generations</div>
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                        {{ $analytics['realtime_metrics']['active_generations'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="text-sm font-medium text-green-600 dark:text-green-400">Generations/Hour</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                        {{ $analytics['realtime_metrics']['generations_last_hour'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Success Rate</div>
                    <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                        {{ round($analytics['realtime_metrics']['current_success_rate'] ?? 0, 1) }}%
                    </div>
                </div>

                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                    <div class="text-sm font-medium text-orange-600 dark:text-orange-400">Queue Length</div>
                    <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                        {{ $analytics['realtime_metrics']['queue_length'] ?? 0 }}
                    </div>
                </div>
            </div>

            {{-- Model Status Indicators --}}
            @if(isset($analytics['realtime_metrics']['models_status']))
                <div class="mt-6">
                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Model Status</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($analytics['realtime_metrics']['models_status'] as $modelId => $status)
                            @php
                                $modelName = $modelNames[$modelId] ?? ucfirst(str_replace('-', ' ', $modelId));
                                $statusColor = match($status['status']) {
                                    'healthy' => 'green',
                                    'degraded' => 'yellow',
                                    'unhealthy' => 'red',
                                    default => 'gray'
                                };
                            @endphp
                            <div class="bg-{{ $statusColor }}-50 dark:bg-{{ $statusColor }}-900/20 border border-{{ $statusColor }}-200 dark:border-{{ $statusColor }}-700 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-{{ $statusColor }}-900 dark:text-{{ $statusColor }}-100">{{ $modelName }}</span>
                                    <div class="w-2 h-2 bg-{{ $statusColor }}-500 rounded-full"></div>
                                </div>
                                <div class="text-xs text-{{ $statusColor }}-700 dark:text-{{ $statusColor }}-300 mt-1">
                                    {{ $status['success_rate'] }}% • {{ $status['recent_requests'] }} req
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>