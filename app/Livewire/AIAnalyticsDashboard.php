<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\AI\AIAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * AI Analytics Dashboard Livewire component.
 *
 * Provides comprehensive analytics and reporting for AI generation usage,
 * performance metrics, cost analysis, and user behavior insights.
 */
class AIAnalyticsDashboard extends Component
{
    public string $period = 'month';

    public bool $isAdmin = false;

    /** @var array<string, mixed> */
    public array $analytics = [];

    /** @var array<string, mixed> */
    public array $realTimeMetrics = [];

    public bool $loading = false;

    /** @var array<string, string> */
    protected $listeners = [
        'refreshAnalytics' => 'loadAnalytics',
        'periodChanged' => 'handlePeriodChange',
    ];

    /**
     * Component initialization.
     */
    public function mount(): void
    {
        $this->isAdmin = Auth::user()?->isAdmin() ?? false;
        $this->loadAnalytics();
        $this->loadRealTimeMetrics();
    }

    /**
     * Handle period change.
     */
    public function updatedPeriod(): void
    {
        $this->loadAnalytics();
    }

    /**
     * Load analytics data.
     */
    public function loadAnalytics(): void
    {
        $this->loading = true;

        try {
            $analyticsService = app(AIAnalyticsService::class);
            $user = Auth::user();

            if ($this->isAdmin) {
                $this->analytics = $analyticsService->getSystemAnalytics($this->period);
            } elseif ($user) {
                $this->analytics = $analyticsService->getUserAnalytics($user, $this->period);
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to load analytics: '.$e->getMessage(),
                'type' => 'error',
            ]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Load real-time metrics.
     */
    public function loadRealTimeMetrics(): void
    {
        if (! $this->isAdmin) {
            return;
        }

        try {
            $analyticsService = app(AIAnalyticsService::class);
            $this->realTimeMetrics = $analyticsService->getRealTimeMetrics();
            $this->analytics['realtime_metrics'] = $this->realTimeMetrics;
        } catch (\Exception) {
            // Silently fail for real-time metrics
        }
    }

    /**
     * Refresh all analytics data.
     */
    public function refreshAnalytics(): void
    {
        $this->loadAnalytics();
        if ($this->isAdmin) {
            $this->loadRealTimeMetrics();
        }

        $this->dispatch('show-toast', [
            'message' => 'Analytics data refreshed successfully',
            'type' => 'success',
        ]);
    }

    /**
     * Export analytics data.
     */
    public function exportAnalytics(string $format = 'csv'): void
    {
        try {
            $data = $this->prepareExportData();

            match ($format) {
                'csv' => $this->exportToCsv($data),
                'json' => $this->exportToJson($data),
                'pdf' => $this->exportToPdf($data),
                default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
            };

            $this->dispatch('show-toast', [
                'message' => "Analytics exported successfully as {$format}",
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Export failed: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Get model performance comparison.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModelComparison(): array
    {
        if (! isset($this->analytics['model_usage']['model_performance'])) {
            return [];
        }

        $modelPerformance = $this->analytics['model_usage']['model_performance'];
        $comparison = [];

        foreach ($modelPerformance as $modelId => $metrics) {
            $comparison[] = [
                'model' => $modelId,
                'success_rate' => $metrics['success_rate'],
                'avg_response_time' => $metrics['average_response_time'],
                'total_cost' => $metrics['total_cost'],
                'usage_count' => $metrics['usage_count'],
                'efficiency_score' => $this->calculateEfficiencyScore($metrics),
            ];
        }

        // Sort by efficiency score
        usort($comparison, fn ($a, $b) => $b['efficiency_score'] <=> $a['efficiency_score']);

        return $comparison;
    }

    /**
     * Get usage trend direction.
     */
    public function getUsageTrendDirection(): string
    {
        if (! isset($this->analytics['generation_trends']['growth_rate'])) {
            return 'stable';
        }

        $growthRate = $this->analytics['generation_trends']['growth_rate'];

        if ($growthRate > 10) {
            return 'growing';
        } elseif ($growthRate < -10) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Get cost efficiency rating.
     */
    public function getCostEfficiencyRating(): string
    {
        if (! isset($this->analytics['cost_analysis']['cost_per_generation'])) {
            return 'unknown';
        }

        $costPerGeneration = $this->analytics['cost_analysis']['cost_per_generation'];

        if ($costPerGeneration < 1) { // Less than 1 cent
            return 'excellent';
        } elseif ($costPerGeneration < 5) { // Less than 5 cents
            return 'good';
        } elseif ($costPerGeneration < 10) { // Less than 10 cents
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Get system health status.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealthStatus(): array
    {
        if (! $this->isAdmin || ! isset($this->realTimeMetrics)) {
            return ['status' => 'unknown', 'message' => 'Health data not available'];
        }

        $successRate = $this->realTimeMetrics['current_success_rate'] ?? 0;
        $errorRate = $this->realTimeMetrics['error_rate_last_hour'] ?? 0;
        $queueLength = $this->realTimeMetrics['queue_length'] ?? 0;

        if ($successRate >= 95 && $errorRate <= 5 && $queueLength <= 10) {
            return ['status' => 'healthy', 'message' => 'All systems operational'];
        } elseif ($successRate >= 80 && $errorRate <= 15 && $queueLength <= 50) {
            return ['status' => 'degraded', 'message' => 'Performance issues detected'];
        } else {
            return ['status' => 'unhealthy', 'message' => 'System experiencing problems'];
        }
    }

    /**
     * Handle period change event.
     */
    public function handlePeriodChange(string $newPeriod): void
    {
        $this->period = $newPeriod;
        $this->loadAnalytics();
    }

    /**
     * Calculate efficiency score for a model.
     *
     * @param  array<string, mixed>  $metrics
     */
    protected function calculateEfficiencyScore(array $metrics): float
    {
        $successRate = $metrics['success_rate'] ?? 0;
        $responseTime = $metrics['average_response_time'] ?? 10000; // Default to 10s
        $cost = $metrics['total_cost'] ?? 1000; // Default to $10

        // Normalize response time (lower is better, max 30s)
        $timeScore = max(0, (30000 - min($responseTime, 30000)) / 30000) * 100;

        // Normalize cost (lower is better, max $1)
        $costScore = max(0, (100 - min($cost / 100, 100)) / 100) * 100;

        // Weighted average: success rate (50%), response time (30%), cost (20%)
        return ($successRate * 0.5) + ($timeScore * 0.3) + ($costScore * 0.2);
    }

    /**
     * Prepare data for export.
     *
     * @return array<string, mixed>
     */
    protected function prepareExportData(): array
    {
        return [
            'period' => $this->period,
            'generated_at' => now()->toISOString(),
            'user_type' => $this->isAdmin ? 'admin' : 'user',
            'analytics' => $this->analytics,
            'real_time_metrics' => $this->realTimeMetrics,
            'summary' => [
                'usage_trend' => $this->getUsageTrendDirection(),
                'cost_efficiency' => $this->getCostEfficiencyRating(),
                'model_comparison' => $this->getModelComparison(),
                'system_health' => $this->getSystemHealthStatus(),
            ],
        ];
    }

    /**
     * Export to CSV format.
     *
     * @param  array<string, mixed>  $data
     */
    protected function exportToCsv(array $data): void
    {
        $filename = 'ai_analytics_'.$this->period.'_'.now()->format('Y-m-d_H-i-s').'.csv';

        // This would be implemented to generate CSV
        // For now, we'll just trigger a download
        $this->dispatch('download-csv', [
            'data' => $data,
            'filename' => $filename,
        ]);
    }

    /**
     * Export to JSON format.
     *
     * @param  array<string, mixed>  $data
     */
    protected function exportToJson(array $data): void
    {
        $filename = 'ai_analytics_'.$this->period.'_'.now()->format('Y-m-d_H-i-s').'.json';

        $this->dispatch('download-json', [
            'data' => json_encode($data, JSON_PRETTY_PRINT),
            'filename' => $filename,
        ]);
    }

    /**
     * Export to PDF format.
     *
     * @param  array<string, mixed>  $data
     */
    protected function exportToPdf(array $data): void
    {
        $filename = 'ai_analytics_'.$this->period.'_'.now()->format('Y-m-d_H-i-s').'.pdf';

        // This would be implemented to generate PDF
        $this->dispatch('download-pdf', [
            'data' => $data,
            'filename' => $filename,
        ]);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('components.ai-analytics-dashboard');
    }
}
