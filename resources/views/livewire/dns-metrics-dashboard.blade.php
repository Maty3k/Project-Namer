<div class="p-6 space-y-6"
     x-data="{
         autoRefresh: @entangle('autoRefresh'),
         refreshInterval: @entangle('refreshInterval'),
         intervalId: null
     }"
     x-init="
         if (autoRefresh) {
             intervalId = setInterval(() => {
                 if (autoRefresh) {
                     $dispatch('auto-refresh');
                 }
             }, refreshInterval * 1000);
         }
         $watch('autoRefresh', value => {
             if (value && !intervalId) {
                 intervalId = setInterval(() => {
                     if (autoRefresh) {
                         $dispatch('auto-refresh');
                     }
                 }, refreshInterval * 1000);
             } else if (!value && intervalId) {
                 clearInterval(intervalId);
                 intervalId = null;
             }
         });
     "
     x-on:data-refreshed="console.log('DNS metrics refreshed')"
     x-on:cache-optimized="alert($event.detail.message)"
     x-on:domains-preloaded="alert($event.detail.message)"
     x-on:circuit-breaker-reset="alert($event.detail.message)"
     x-on:optimization-failed="alert('Error: ' + $event.detail.message)"
     x-on:preload-failed="alert('Error: ' + $event.detail.message)"
     x-on:reset-failed="alert('Error: ' + $event.detail.message)">

    {{-- Dashboard Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="mb-2">DNS Metrics Dashboard</flux:heading>
            <p class="text-gray-600 dark:text-gray-400">
                Monitor DNS lookup performance, cache efficiency, and system health
            </p>
        </div>

        <div class="flex items-center space-x-4">
            <flux:button
                wire:click="refreshData"
                variant="ghost"
                size="sm"
                wire:loading.attr="disabled">
                <span wire:loading.remove>🔄 Refresh</span>
                <span wire:loading>⏳ Refreshing...</span>
            </flux:button>

            <flux:switch
                wire:model.live="autoRefresh"
                class="ml-2">
                Auto-refresh ({{ $refreshInterval }}s)
            </flux:switch>
        </div>
    </div>

    {{-- System Health Overview --}}
    <flux:card class="mb-6">
        <div class="mb-4">
            <flux:heading size="lg">System Health</flux:heading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="text-center p-4 rounded-lg {{ $systemHealth['overall_health'] === 'healthy' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20' }}">
                <div class="text-2xl mb-2">
                    {{ $systemHealth['overall_health'] === 'healthy' ? '✅' : '⚠️' }}
                </div>
                <flux:text size="sm" class="font-medium">Overall Health</flux:text>
                <flux:text size="xs" class="text-gray-600 dark:text-gray-400">
                    {{ ucfirst($systemHealth['overall_health']) }}
                </flux:text>
            </div>

            <div class="text-center p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($systemHealth['health_score']) }}
                </div>
                <flux:text size="sm" class="font-medium">Health Score</flux:text>
                <flux:text size="xs" class="text-gray-600 dark:text-gray-400">out of 100</flux:text>
            </div>

            <div class="text-center p-4 rounded-lg bg-purple-50 dark:bg-purple-900/20">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ number_format($systemHealth['cache_hit_rate'], 1) }}%
                </div>
                <flux:text size="sm" class="font-medium">Cache Hit Rate</flux:text>
                <flux:text size="xs" class="text-gray-600 dark:text-gray-400">last 24 hours</flux:text>
            </div>

            <div class="text-center p-4 rounded-lg {{ $systemHealth['circuit_breaker_healthy'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                <div class="text-2xl mb-2">
                    {{ $systemHealth['circuit_breaker_healthy'] ? '🟢' : '🔴' }}
                </div>
                <flux:text size="sm" class="font-medium">Circuit Breaker</flux:text>
                <flux:text size="xs" class="text-gray-600 dark:text-gray-400">
                    {{ $systemHealth['circuit_breaker_healthy'] ? 'Healthy' : 'Open' }}
                </flux:text>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Cache Statistics --}}
        <flux:card>
            <div class="mb-4">
                <flux:heading size="lg">Cache Statistics</flux:heading>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Total Entries</flux:text>
                        <flux:text size="lg" class="font-semibold">{{ number_format($cacheStats['total_entries']) }}</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Valid Entries</flux:text>
                        <flux:text size="lg" class="font-semibold text-green-600 dark:text-green-400">{{ number_format($cacheStats['valid_entries']) }}</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Expired Entries</flux:text>
                        <flux:text size="lg" class="font-semibold text-red-600 dark:text-red-400">{{ number_format($cacheStats['expired_entries']) }}</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Memory Usage</flux:text>
                        <flux:text size="lg" class="font-semibold">{{ number_format($systemHealth['memory_usage_mb'], 2) }} MB</flux:text>
                    </div>
                </div>

                @if(!empty($cacheStats['top_tlds']))
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mb-2">Top TLDs</flux:text>
                        <div class="space-y-1">
                            @foreach(array_slice($cacheStats['top_tlds'], 0, 5) as $tld)
                                <div class="flex justify-between items-center">
                                    <flux:text size="sm">.{{ $tld['tld'] }}</flux:text>
                                    <flux:badge>{{ number_format($tld['count']) }}</flux:badge>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Hit Analysis --}}
        <flux:card>
            <div class="mb-4 flex justify-between items-center">
                <flux:heading size="lg">Hit Analysis</flux:heading>
                <div class="flex items-center space-x-2">
                    <flux:text size="sm">Days:</flux:text>
                    <flux:select wire:model.live="analysisFromDays" class="w-20">
                        <option value="1">1</option>
                        <option value="3">3</option>
                        <option value="7">7</option>
                        <option value="14">14</option>
                        <option value="30">30</option>
                    </flux:select>
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Overall Hit Rate</flux:text>
                        <flux:text size="lg" class="font-semibold">{{ number_format($hitAnalysis['overall_hit_rate'], 2) }}%</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Cache Efficiency</flux:text>
                        <flux:text size="lg" class="font-semibold">{{ number_format($hitAnalysis['cache_efficiency'], 2) }}%</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Total Cache Hits</flux:text>
                        <flux:text size="lg" class="font-semibold text-green-600 dark:text-green-400">{{ number_format($hitAnalysis['total_cache_hits']) }}</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Total Lookups</flux:text>
                        <flux:text size="lg" class="font-semibold">{{ number_format($hitAnalysis['total_lookups']) }}</flux:text>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Circuit Breaker Status --}}
    <flux:card>
        <div class="mb-4">
            <flux:heading size="lg">Circuit Breaker Status</flux:heading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Service</flux:text>
                <flux:text size="lg" class="font-semibold">{{ $circuitBreaker['service_name'] ?? 'N/A' }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">State</flux:text>
                <flux:badge variant="{{ ($circuitBreaker['state'] ?? 'unknown') === 'closed' ? '' : (($circuitBreaker['state'] ?? 'unknown') === 'open' ? '' : '') }}" class="{{ ($circuitBreaker['state'] ?? 'unknown') === 'closed' ? 'bg-green-100 text-green-800' : (($circuitBreaker['state'] ?? 'unknown') === 'open' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    {{ ucfirst($circuitBreaker['state'] ?? 'unknown') }}
                </flux:badge>
            </div>
            <div>
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Failure Count</flux:text>
                <flux:text size="lg" class="font-semibold">{{ $circuitBreaker['failure_count'] ?? 0 }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Success Count</flux:text>
                <flux:text size="lg" class="font-semibold">{{ $circuitBreaker['success_count'] ?? 0 }}</flux:text>
            </div>
        </div>

        @if(($circuitBreaker['state'] ?? 'unknown') === 'open')
            <div class="mt-4">
                <flux:button
                    wire:click="resetCircuitBreaker"
                    variant="danger"
                    size="sm"
                    wire:loading.attr="disabled">
                    Reset Circuit Breaker
                </flux:button>
            </div>
        @endif
    </flux:card>

    {{-- Optimization Suggestions --}}
    <flux:card>
        <div class="mb-4">
            <flux:heading size="lg">Optimization Suggestions</flux:heading>
            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                Score: {{ number_format($suggestions['optimization_score'], 1) }}/100
            </flux:text>
        </div>

        @if(empty($suggestions['suggestions']))
            <div class="text-center py-8">
                <div class="text-4xl mb-4">✅</div>
                <flux:heading size="md" class="text-green-600 dark:text-green-400 mb-2">
                    Excellent Performance!
                </flux:heading>
                <flux:text class="text-gray-600 dark:text-gray-400">
                    No optimization suggestions at this time. Your DNS cache is performing well.
                </flux:text>
            </div>
        @else
            <div class="space-y-3">
                @foreach($suggestions['suggestions'] as $suggestion)
                    <div class="flex items-start space-x-3 p-3 rounded-lg border dark:border-gray-700
                                {{ $suggestion['priority'] === 'high' ? 'border-red-200 bg-red-50 dark:bg-red-900/20' :
                                   ($suggestion['priority'] === 'medium' ? 'border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20' :
                                   'border-green-200 bg-green-50 dark:bg-green-900/20') }}">
                        <div class="text-lg">
                            {{ $suggestion['priority'] === 'high' ? '🔴' : ($suggestion['priority'] === 'medium' ? '🟡' : '🟢') }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-1">
                                <flux:badge class="{{ $suggestion['priority'] === 'high' ? 'bg-red-100 text-red-800' : ($suggestion['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                    {{ ucfirst($suggestion['priority']) }}
                                </flux:badge>
                                <flux:text size="sm" class="font-medium">{{ ucfirst(str_replace('_', ' ', $suggestion['type'])) }}</flux:text>
                            </div>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ $suggestion['suggestion'] }}
                            </flux:text>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- Actions Panel --}}
    <flux:card>
        <div class="mb-4">
            <flux:heading size="lg">Quick Actions</flux:heading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:button
                wire:click="optimizeCache"
                variant="primary"
                class="w-full"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="optimizeCache">🧹 Optimize Cache</span>
                <span wire:loading wire:target="optimizeCache">⏳ Optimizing...</span>
            </flux:button>

            <flux:button
                wire:click="preloadPopularDomains(100)"
                variant="filled"
                class="w-full"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="preloadPopularDomains">🔄 Preload Domains</span>
                <span wire:loading wire:target="preloadPopularDomains">⏳ Preloading...</span>
            </flux:button>

            <flux:button
                wire:click="refreshData"
                variant="ghost"
                class="w-full"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="refreshData">📊 Refresh Data</span>
                <span wire:loading wire:target="refreshData">⏳ Refreshing...</span>
            </flux:button>
        </div>
    </flux:card>
</div>
