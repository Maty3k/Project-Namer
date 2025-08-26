<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ComponentMappingService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ComponentAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'flux:audit {--format=table : Output format (table, json, markdown)}';

    /**
     * The console command description.
     */
    protected $description = 'Audit FluxUI components in the application and show upgrade priorities';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $service = new ComponentMappingService();
        $format = $this->option('format');
        
        $this->info('🔍 Auditing FluxUI components...');
        $this->newLine();
        
        $auditResults = $service->auditApplicationComponents();
        
        // Display summary
        $this->displaySummary($auditResults['summary']);
        
        switch ($format) {
            case 'json':
                $this->displayJson($auditResults);
                break;
            case 'markdown':
                $this->displayMarkdown($auditResults);
                break;
            default:
                $this->displayTable($auditResults);
                break;
        }
        
        // Show upgrade recommendations
        $this->newLine();
        $this->displayUpgradeRecommendations($auditResults['components']);
        
        return Command::SUCCESS;
    }

    /**
     * Display audit summary
     */
    private function displaySummary(array $summary): void
    {
        $this->info('📊 <fg=cyan>Audit Summary</fg=cyan>');
        $this->line("Total Components Found: <fg=green>{$summary['total_components']}</fg=green>");
        $this->line("Unique Component Types: <fg=green>{$summary['unique_component_types']}</fg=green>");
        $this->line("Files Scanned: <fg=green>{$summary['files_scanned']}</fg=green>");
        $this->line("High Priority Components: <fg=red>{$summary['high_priority_count']}</fg=red>");
        $this->line("Medium Priority Components: <fg=yellow>{$summary['medium_priority_count']}</fg=yellow>");
        $this->line("Low Priority Components: <fg=green>{$summary['low_priority_count']}</fg=green>");
        $this->newLine();
    }

    /**
     * Display results as a table
     */
    private function displayTable(array $auditResults): void
    {
        $this->info('📋 <fg=cyan>Component Usage Details</fg=cyan>');
        
        $tableData = [];
        foreach ($auditResults['components'] as $name => $data) {
            $tableData[] = [
                'Component' => $name,
                'Usage Count' => $data['count'],
                'Files' => count($data['locations']),
                'Priority' => $this->colorPriority($data['upgrade_priority']),
                'Upgrade Notes' => Str::limit($data['upgrade_notes'], 50),
            ];
        }
        
        $this->table([
            'Component',
            'Usage Count', 
            'Files',
            'Priority',
            'Upgrade Notes'
        ], $tableData);
    }

    /**
     * Display results as JSON
     */
    private function displayJson(array $auditResults): void
    {
        $this->line(json_encode($auditResults, JSON_PRETTY_PRINT));
    }

    /**
     * Display results as Markdown
     */
    private function displayMarkdown(array $auditResults): void
    {
        $this->info('📝 <fg=cyan>Markdown Report</fg=cyan>');
        $this->newLine();
        
        $this->line('# FluxUI Component Audit Report');
        $this->line('');
        
        // Summary
        $summary = $auditResults['summary'];
        $this->line('## Summary');
        $this->line('');
        $this->line("- **Total Components:** {$summary['total_components']}");
        $this->line("- **Unique Types:** {$summary['unique_component_types']}");
        $this->line("- **Files Scanned:** {$summary['files_scanned']}");
        $this->line("- **High Priority:** {$summary['high_priority_count']}");
        $this->line("- **Medium Priority:** {$summary['medium_priority_count']}");
        $this->line("- **Low Priority:** {$summary['low_priority_count']}");
        $this->line('');
        
        // Component details
        $this->line('## Component Details');
        $this->line('');
        
        foreach ($auditResults['components'] as $name => $data) {
            $this->line("### {$name}");
            $this->line('');
            $this->line("- **Usage Count:** {$data['count']}");
            $this->line("- **Files:** " . count($data['locations']));
            $this->line("- **Priority:** {$data['upgrade_priority']}");
            $this->line("- **Notes:** {$data['upgrade_notes']}");
            $this->line('');
        }
    }

    /**
     * Display upgrade recommendations
     */
    private function displayUpgradeRecommendations(array $components): void
    {
        $this->info('🚀 <fg=cyan>Upgrade Recommendations</fg=cyan>');
        
        // Filter and sort by priority
        $highPriority = array_filter($components, fn($c) => $c['upgrade_priority'] === 'high');
        $mediumPriority = array_filter($components, fn($c) => $c['upgrade_priority'] === 'medium');
        
        if (!empty($highPriority)) {
            $this->warn('⚡ High Priority Upgrades:');
            foreach ($highPriority as $name => $data) {
                $this->line("  • <fg=red>{$name}</fg=red> ({$data['count']} usages) - {$data['upgrade_notes']}");
            }
            $this->newLine();
        }
        
        if (!empty($mediumPriority)) {
            $this->warn('⚠️  Medium Priority Upgrades:');
            foreach ($mediumPriority as $name => $data) {
                $this->line("  • <fg=yellow>{$name}</fg=yellow> ({$data['count']} usages) - {$data['upgrade_notes']}");
            }
            $this->newLine();
        }
        
        $totalHighMediumUsage = array_sum(array_map(fn($c) => $c['count'], 
            array_merge($highPriority, $mediumPriority)));
        
        if ($totalHighMediumUsage > 0) {
            $this->info("💡 <fg=green>Focus on upgrading {$totalHighMediumUsage} high/medium priority component usages first.</fg=green>");
        } else {
            $this->info('✅ <fg=green>No high or medium priority upgrades needed!</fg=green>');
        }
    }

    /**
     * Color code priority levels
     */
    private function colorPriority(string $priority): string
    {
        return match ($priority) {
            'high' => "<fg=red>{$priority}</fg=red>",
            'medium' => "<fg=yellow>{$priority}</fg=yellow>",
            'low' => "<fg=green>{$priority}</fg=green>",
            default => $priority,
        };
    }
}