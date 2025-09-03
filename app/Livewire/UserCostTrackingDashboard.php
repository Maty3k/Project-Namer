<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\AI\AICostTrackingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * User Cost Tracking Dashboard.
 *
 * Displays user's AI usage statistics, costs, and remaining limits.
 */
class UserCostTrackingDashboard extends Component
{
    /** @var array<string, mixed> */
    public array $usageStats = [];

    /** @var array<string, mixed> */
    public array $usageLimits = [];

    /** @var array<string, mixed> */
    /** @var array<int, array<string, mixed>> */
    public array $costTrends = [];

    public string $selectedPeriod = 'day';

    public bool $loading = false;

    /** @var array<string, string> */
    protected $listeners = [
        'refreshStats' => 'loadUsageData',
        'periodChanged' => 'handlePeriodChange',
    ];

    /**
     * Component initialization.
     */
    public function mount(): void
    {
        $this->loadUsageData();
    }

    /**
     * Handle period change.
     */
    public function updatedSelectedPeriod(): void
    {
        $this->loadUsageData();
    }

    /**
     * Load usage data from the cost tracking service.
     */
    public function loadUsageData(): void
    {
        $this->loading = true;

        try {
            $user = Auth::user();

            if (! $user) {
                $this->usageStats = [];
                $this->usageLimits = [];
                $this->costTrends = [];

                return;
            }

            $costService = app(AICostTrackingService::class);

            // Get usage statistics for selected period
            $this->usageStats = $costService->getUserUsageStats($user, $this->selectedPeriod);

            // Get current usage limits
            $this->usageLimits = $costService->checkUserLimits($user);

            // Get cost trends
            $this->costTrends = $costService->getCostTrends($this->selectedPeriod);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to load usage data: '.$e->getMessage(),
                'type' => 'error',
            ]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Get usage percentage class for styling.
     */
    public function getUsagePercentageClass(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'bg-red-500';
        } elseif ($percentage >= 75) {
            return 'bg-yellow-500';
        } elseif ($percentage >= 50) {
            return 'bg-blue-500';
        } else {
            return 'bg-green-500';
        }
    }

    /**
     * Get cost trend direction.
     */
    public function getCostTrendDirection(): string
    {
        if (count($this->costTrends) < 2) {
            return 'stable';
        }

        $current = end($this->costTrends)['cost'];
        $previous = prev($this->costTrends)['cost'];

        if ($current > $previous * 1.1) {
            return 'increasing';
        } elseif ($current < $previous * 0.9) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Get cost efficiency rating.
     */
    public function getCostEfficiencyRating(): string
    {
        $totalCost = $this->usageStats['total_cost'] ?? 0;
        $totalRequests = $this->usageStats['total_requests'] ?? 0;

        if ($totalRequests === 0) {
            return 'unknown';
        }

        $costPerRequest = $totalCost / $totalRequests;

        if ($costPerRequest < 0.01) { // Less than 1 cent per request
            return 'excellent';
        } elseif ($costPerRequest < 0.05) { // Less than 5 cents
            return 'good';
        } elseif ($costPerRequest < 0.10) { // Less than 10 cents
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Get most used model.
     *
     * @return array<string, mixed>|null
     */
    public function getMostUsedModel(): ?array
    {
        $modelBreakdown = $this->usageStats['model_breakdown'] ?? [];

        if (empty($modelBreakdown)) {
            return null;
        }

        $maxRequests = 0;
        $topModel = null;

        foreach ($modelBreakdown as $modelId => $stats) {
            if ($stats['requests'] > $maxRequests) {
                $maxRequests = $stats['requests'];
                $topModel = [
                    'id' => $modelId,
                    'requests' => $stats['requests'],
                    'cost' => $stats['cost'],
                    'tokens' => $stats['tokens'],
                ];
            }
        }

        return $topModel;
    }

    /**
     * Export usage data.
     */
    public function exportUsageData(string $format = 'csv'): void
    {
        try {
            $user = Auth::user();

            if (! $user) {
                $this->dispatch('show-toast', [
                    'message' => 'You must be logged in to export data',
                    'type' => 'error',
                ]);

                return;
            }

            $exportData = [
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'period' => $this->selectedPeriod,
                'usage_stats' => $this->usageStats,
                'usage_limits' => $this->usageLimits,
                'cost_trends' => $this->costTrends,
                'generated_at' => now()->toISOString(),
            ];

            match ($format) {
                'csv' => $this->exportToCsv($exportData),
                'json' => $this->exportToJson($exportData),
                default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
            };

            $this->dispatch('show-toast', [
                'message' => "Usage data exported successfully as {$format}",
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
     * Handle period change event.
     */
    public function handlePeriodChange(string $newPeriod): void
    {
        $this->selectedPeriod = $newPeriod;
        $this->loadUsageData();
    }

    /**
     * Export to CSV format.
     *
     * @param  array<string, mixed>  $data
     */
    protected function exportToCsv(array $data): void
    {
        $filename = 'ai_usage_'.$this->selectedPeriod.'_'.now()->format('Y-m-d_H-i-s').'.csv';

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
        $filename = 'ai_usage_'.$this->selectedPeriod.'_'.now()->format('Y-m-d_H-i-s').'.json';

        $this->dispatch('download-json', [
            'data' => json_encode($data, JSON_PRETTY_PRINT),
            'filename' => $filename,
        ]);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.user-cost-tracking-dashboard');
    }
}
