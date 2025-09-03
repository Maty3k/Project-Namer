<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\AI\AICostTrackingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * AI Cost Monitor Livewire Component.
 *
 * Admin interface for monitoring AI costs, usage trends, budget limits,
 * and user spending patterns.
 */
class AICostMonitor extends Component
{
    /** @var array<string, mixed> */
    public array $systemStats = [];

    /** @var array<string, mixed> */
    public array $budgetLimits = [];

    /** @var array<int, array<string, mixed>> */
    public array $topUsers = [];

    /** @var array<int, array<string, mixed>> */
    public array $costTrends = [];

    public string $selectedPeriod = 'day';

    public bool $loading = false;

    public bool $isAdmin = false;

    /** @var array<string, string> */
    protected $listeners = [
        'refreshCostData' => 'loadCostData',
        'periodChanged' => 'handlePeriodChange',
    ];

    /**
     * Component initialization.
     */
    public function mount(): void
    {
        $this->isAdmin = Auth::user()?->isAdmin() ?? false;

        if (! $this->isAdmin) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $this->loadCostData();
    }

    /**
     * Load all cost monitoring data.
     */
    public function loadCostData(): void
    {
        $this->loading = true;

        try {
            $costTracker = app(AICostTrackingService::class);

            $this->systemStats = $costTracker->getSystemCostStats($this->selectedPeriod);
            $this->budgetLimits = $costTracker->checkSystemBudgetLimits();
            $this->topUsers = $costTracker->getTopSpendingUsers($this->selectedPeriod);
            $this->costTrends = $costTracker->getCostTrends('week');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to load cost data: '.$e->getMessage(),
                'type' => 'error',
            ]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Handle period change.
     */
    public function updatedSelectedPeriod(): void
    {
        $this->loadCostData();
    }

    /**
     * Clean up old usage logs.
     */
    public function cleanupOldLogs(): void
    {
        try {
            $costTracker = app(AICostTrackingService::class);
            $deletedCount = $costTracker->cleanupOldLogs(90);

            $this->dispatch('show-toast', [
                'message' => "Cleaned up {$deletedCount} old usage logs",
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to cleanup logs: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Export cost data.
     */
    public function exportCostData(string $format = 'csv'): void
    {
        try {
            $data = $this->prepareCostExportData();

            match ($format) {
                'csv' => $this->exportToCsv($data),
                'json' => $this->exportToJson($data),
                default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
            };

            $this->dispatch('show-toast', [
                'message' => "Cost data exported as {$format}",
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
     * Get budget status class.
     *
     * @param  array<string, mixed>  $budget
     */
    public function getBudgetStatusClass(array $budget): string
    {
        if ($budget['exceeded']) {
            return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20';
        } elseif ($budget['alert_needed']) {
            return 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20';
        } else {
            return 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20';
        }
    }

    /**
     * Get budget status text.
     *
     * @param  array<string, mixed>  $budget
     */
    public function getBudgetStatusText(array $budget): string
    {
        if ($budget['exceeded']) {
            return 'Exceeded';
        } elseif ($budget['alert_needed']) {
            return 'Warning';
        } else {
            return 'On Track';
        }
    }

    /**
     * Format currency amount.
     */
    public function formatCurrency(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }

    /**
     * Format percentage.
     */
    public function formatPercentage(float $percentage): string
    {
        return number_format($percentage, 1).'%';
    }

    /**
     * Format large numbers.
     */
    public function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1).'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 1).'K';
        } else {
            return number_format($number);
        }
    }

    /**
     * Get trend direction icon.
     *
     * @param  array<int, array<string, mixed>>  $trends
     */
    public function getTrendIcon(array $trends): string
    {
        if (count($trends) < 2) {
            return 'minus';
        }

        $recent = end($trends)['cost'];
        $previous = prev($trends)['cost'];

        if ($recent > $previous) {
            return 'arrow-trending-up';
        } elseif ($recent < $previous) {
            return 'arrow-trending-down';
        } else {
            return 'minus';
        }
    }

    /**
     * Get trend direction class.
     *
     * @param  array<int, array<string, mixed>>  $trends
     */
    public function getTrendClass(array $trends): string
    {
        if (count($trends) < 2) {
            return 'text-gray-500';
        }

        $recent = end($trends)['cost'];
        $previous = prev($trends)['cost'];

        if ($recent > $previous) {
            return 'text-red-500';
        } elseif ($recent < $previous) {
            return 'text-green-500';
        } else {
            return 'text-gray-500';
        }
    }

    /**
     * Prepare cost data for export.
     *
     * @return array<string, mixed>
     */
    protected function prepareCostExportData(): array
    {
        return [
            'export_date' => now()->toISOString(),
            'period' => $this->selectedPeriod,
            'system_stats' => $this->systemStats,
            'budget_limits' => $this->budgetLimits,
            'top_users' => $this->topUsers,
            'cost_trends' => $this->costTrends,
        ];
    }

    /**
     * Export to CSV format.
     *
     * @param  array<string, mixed>  $data
     */
    protected function exportToCsv(array $data): void
    {
        $filename = 'ai_cost_report_'.$this->selectedPeriod.'_'.now()->format('Y-m-d').'.csv';

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
        $filename = 'ai_cost_report_'.$this->selectedPeriod.'_'.now()->format('Y-m-d').'.json';

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
        return view('livewire.admin.ai-cost-monitor');
    }
}
