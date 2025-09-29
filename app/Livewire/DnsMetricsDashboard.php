<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\DnsCacheOptimizationService;
use App\Services\DnsCircuitBreakerService;
use App\Services\DnsPerformanceMonitorService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class DnsMetricsDashboard extends Component
{
    public int $refreshInterval = 30; // seconds
    public int $analysisFromDays = 7;
    public bool $autoRefresh = true;

    public function mount(): void
    {
        // Initialize component
    }

    #[Computed]
    public function cacheStatistics(): array
    {
        $optimizationService = app(DnsCacheOptimizationService::class);
        return $optimizationService->getCacheStatistics();
    }

    #[Computed]
    public function hitAnalysis(): array
    {
        $optimizationService = app(DnsCacheOptimizationService::class);
        return $optimizationService->getCacheHitAnalysis($this->analysisFromDays);
    }

    #[Computed]
    public function optimizationSuggestions(): array
    {
        $optimizationService = app(DnsCacheOptimizationService::class);
        return $optimizationService->suggestCacheImprovements();
    }

    #[Computed]
    public function circuitBreakerStatus(): array
    {
        try {
            $circuitBreakerService = app(DnsCircuitBreakerService::class);
            return $circuitBreakerService->getCircuitBreakerStats();
        } catch (\Exception $e) {
            return [
                'service_name' => 'dns_lookup',
                'state' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    #[Computed]
    public function systemHealth(): array
    {
        $stats = $this->cacheStatistics;
        $suggestions = $this->optimizationSuggestions;
        $circuitBreaker = $this->circuitBreakerStatus;

        $healthScore = $suggestions['optimization_score'] ?? 0;
        $isHealthy = $healthScore >= 70;
        $isCircuitBreakerHealthy = ($circuitBreaker['state'] ?? 'unknown') !== 'open';

        return [
            'overall_health' => $isHealthy && $isCircuitBreakerHealthy ? 'healthy' : 'warning',
            'health_score' => $healthScore,
            'cache_hit_rate' => $stats['cache_hit_rate'] ?? 0,
            'circuit_breaker_healthy' => $isCircuitBreakerHealthy,
            'total_cache_entries' => $stats['total_entries'] ?? 0,
            'memory_usage_mb' => round(($stats['memory_usage_estimate'] ?? 0) / (1024 * 1024), 2),
        ];
    }

    public function refreshData(): void
    {
        // Clear computed properties to force refresh
        unset($this->cacheStatistics);
        unset($this->hitAnalysis);
        unset($this->optimizationSuggestions);
        unset($this->circuitBreakerStatus);
        unset($this->systemHealth);

        $this->dispatch('data-refreshed');
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function updateAnalysisPeriod(int $days): void
    {
        $this->analysisFromDays = max(1, min(30, $days));
        $this->refreshData();
    }

    public function optimizeCache(): void
    {
        try {
            $optimizationService = app(DnsCacheOptimizationService::class);
            $result = $optimizationService->optimizeCache();

            $this->dispatch('cache-optimized', [
                'message' => 'Cache optimization completed successfully!',
                'result' => $result,
            ]);

            $this->refreshData();
        } catch (\Exception $e) {
            $this->dispatch('optimization-failed', [
                'message' => 'Cache optimization failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function preloadPopularDomains(int $count = 100): void
    {
        try {
            $optimizationService = app(DnsCacheOptimizationService::class);
            $result = $optimizationService->preloadPopularDomains($count);

            $this->dispatch('domains-preloaded', [
                'message' => "Preloaded {$result['preloaded_count']} domains successfully!",
                'result' => $result,
            ]);

            $this->refreshData();
        } catch (\Exception $e) {
            $this->dispatch('preload-failed', [
                'message' => 'Domain preloading failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function resetCircuitBreaker(): void
    {
        try {
            $circuitBreakerService = app(DnsCircuitBreakerService::class);
            $circuitBreakerService->resetCircuitBreaker();

            $this->dispatch('circuit-breaker-reset', [
                'message' => 'Circuit breaker has been reset successfully!',
            ]);

            $this->refreshData();
        } catch (\Exception $e) {
            $this->dispatch('reset-failed', [
                'message' => 'Circuit breaker reset failed: ' . $e->getMessage(),
            ]);
        }
    }

    #[On('auto-refresh')]
    public function handleAutoRefresh(): void
    {
        if ($this->autoRefresh) {
            $this->refreshData();
        }
    }

    public function render()
    {
        return view('livewire.dns-metrics-dashboard', [
            'cacheStats' => $this->cacheStatistics,
            'hitAnalysis' => $this->hitAnalysis,
            'suggestions' => $this->optimizationSuggestions,
            'circuitBreaker' => $this->circuitBreakerStatus,
            'systemHealth' => $this->systemHealth,
        ]);
    }
}
