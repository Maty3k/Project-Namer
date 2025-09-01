<?php

declare(strict_types=1);

use App\Services\AIGenerationService;
use App\Services\PrismAIService;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

beforeEach(function (): void {
    $this->service = app(AIGenerationService::class);
});

describe('AI Generation Service', function (): void {
    it('can coordinate parallel name generation across multiple models', function (): void {
        $gptResponse = "1. ParallelFlow\n2. SyncCraft\n3. CoordHub\n4. MultiCore\n5. AsyncLab\n6. ConcurrentForge\n7. ParallelStream\n8. SyncFlow\n9. CoordCraft\n10. MultiHub";
        $claudeResponse = "1. OrchestrateLab\n2. ConductorCore\n3. HarmonyFlow\n4. SymphonyHub\n5. EnsembleForge\n6. ConcertoCraft\n7. MaestroStream\n8. BatonFlow\n9. ComposerCore\n10. TuningHub";

        Prism::fake([
            TextResponseFake::make()->withText($gptResponse),
            TextResponseFake::make()->withText($claudeResponse),
        ]);

        $result = $this->service->generateNamesParallel(
            'A workflow orchestration platform',
            ['gpt-4o', 'claude-3.5-sonnet'],
            'tech-focused'
        );

        expect($result)->toHaveKeys(['results', 'execution_metadata']);
        expect($result['results'])->toHaveKeys(['gpt-4o', 'claude-3.5-sonnet']);
        expect($result['execution_metadata']['total_models_requested'])->toBe(2);
        expect($result['execution_metadata']['successful_models'])->toBe(2);
        expect($result['execution_metadata']['failed_models'])->toBe(0);
        expect($result['execution_metadata'])->toHaveKey('total_execution_time_ms');
        expect($result['execution_metadata'])->toHaveKey('execution_strategy');
    });

    it('provides quick generation with optimized model selection', function (): void {
        $quickResponse = "1. QuickFlow\n2. RapidCraft\n3. SpeedHub\n4. FastCore\n5. SwiftLab\n6. TurboForge\n7. QuickStream\n8. RapidFlow\n9. SpeedCraft\n10. FastHub";

        Prism::fake([
            TextResponseFake::make()->withText($quickResponse),
            TextResponseFake::make()->withText($quickResponse),
        ]);

        $result = $this->service->generateNamesQuick(
            'A rapid development platform',
            'tech-focused'
        );

        expect($result['execution_metadata']['total_models_requested'])->toBe(2);
        expect($result['results'])->toHaveKeys(['gpt-4o', 'claude-3.5-sonnet']);
        expect($result['execution_metadata']['execution_strategy'])->toBe('sequential_with_fallback');
    });

    it('provides comprehensive generation with all models and deep thinking', function (): void {
        $response1 = "1. ComprehensiveFlow\n2. FullStackCraft\n3. CompleteHub\n4. TotalCore\n5. UltimateLab\n6. MasterForge\n7. ComprehensiveStream\n8. FullFlow\n9. CompleteCraft\n10. TotalHub";

        Prism::fake([
            TextResponseFake::make()->withText($response1),
            TextResponseFake::make()->withText($response1),
            TextResponseFake::make()->withText($response1),
            TextResponseFake::make()->withText($response1),
        ]);

        $result = $this->service->generateNamesComprehensive(
            'A complete business solution platform',
            'professional'
        );

        expect($result['execution_metadata']['total_models_requested'])->toBe(4);
        expect($result['results'])->toHaveKeys(['gpt-4o', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta']);

        // Verify deep thinking was enabled
        foreach ($result['results'] as $modelResult) {
            expect($modelResult['deep_thinking'])->toBe(true);
        }
    });

    it('supports custom model selection and parameters', function (): void {
        $customResponse = "1. CustomFlow\n2. TailoredCraft\n3. PersonalHub\n4. BespokeCore\n5. CustomLab\n6. TailoredForge\n7. PersonalStream\n8. CustomCraft\n9. BespokeHub\n10. TailoredFlow";

        Prism::fake([
            TextResponseFake::make()->withText($customResponse),
            TextResponseFake::make()->withText($customResponse),
        ]);

        $result = $this->service->generateNamesCustom(
            'A custom solution platform',
            ['gpt-4o', 'gemini-1.5-pro'],
            [
                'mode' => 'brandable',
                'deep_thinking' => true,
                'params' => ['temperature' => 0.9, 'count' => 10],
            ]
        );

        expect($result['execution_metadata']['total_models_requested'])->toBe(2);
        expect($result['results'])->toHaveKeys(['gpt-4o', 'gemini-1.5-pro']);

        foreach ($result['results'] as $modelResult) {
            expect($modelResult['deep_thinking'])->toBe(true);
            expect($modelResult['generation_mode'])->toBe('brandable');
            expect($modelResult['temperature'])->toBe(0.9);
        }
    });

    it('generates execution statistics and performance metrics', function (): void {
        $response = "1. MetricsFlow\n2. StatsCraft\n3. AnalyticsHub\n4. PerfCore\n5. MetricsLab\n6. StatsForge\n7. AnalyticsStream\n8. PerfFlow\n9. MetricsCraft\n10. StatsHub";

        Prism::fake([
            TextResponseFake::make()->withText($response),
            TextResponseFake::make()->withText($response),
        ]);

        $generationResult = $this->service->generateNamesParallel(
            'A performance analytics platform',
            ['gpt-4o', 'claude-3.5-sonnet'],
            'tech-focused'
        );

        $stats = $this->service->getExecutionStats($generationResult);

        expect($stats)->toHaveKeys(['performance', 'reliability', 'recommendations']);
        expect($stats['performance'])->toHaveKeys(['success_rate', 'average_response_time', 'total_execution_time', 'cache_hit_rate']);
        expect($stats['reliability'])->toHaveKeys(['models_with_fallback', 'fallback_rate', 'failed_models']);
        expect($stats['recommendations'])->toBeArray();
        expect($stats['performance']['success_rate'])->toBe(100.0);
    });

    it('provides intelligent recommendations based on execution results', function (): void {
        $response = "1. RecommendFlow\n2. AdvisoryCraft\n3. GuidanceHub\n4. SuggestCore\n5. RecommendLab\n6. AdvisoryForge\n7. GuidanceStream\n8. SuggestFlow\n9. RecommendCraft\n10. AdvisoryHub";

        Prism::fake([
            TextResponseFake::make()->withText($response),
            TextResponseFake::make()->withText($response),
        ]);

        $generationResult = $this->service->generateNamesParallel(
            'An advisory platform',
            ['gpt-4o', 'claude-3.5-sonnet'],
            'professional'
        );

        $stats = $this->service->getExecutionStats($generationResult);

        expect($stats['recommendations'])->toBeArray();
        expect($stats['performance']['success_rate'])->toBe(100.0);
        expect($stats['reliability']['fallback_rate'])->toBe(0.0);
    });

    it('tracks execution metadata correctly', function (): void {
        $response = "1. TrackingFlow\n2. MonitorCraft\n3. WatchHub\n4. ObserveCore\n5. TrackLab\n6. MonitorForge\n7. WatchStream\n8. ObserveFlow\n9. TrackCraft\n10. MonitorHub";

        Prism::fake([
            TextResponseFake::make()->withText($response),
        ]);

        $result = $this->service->generateNamesParallel(
            'A monitoring platform',
            ['gpt-4o'],
            'tech-focused'
        );

        $metadata = $result['execution_metadata'];

        expect($metadata)->toHaveKeys([
            'total_models_requested',
            'successful_models',
            'failed_models',
            'total_execution_time_ms',
            'average_response_time_ms',
            'models_with_fallback',
            'cached_results',
            'execution_strategy',
            'executed_at',
        ]);

        expect($metadata['total_models_requested'])->toBe(1);
        expect($metadata['successful_models'])->toBe(1);
        expect($metadata['failed_models'])->toBe(0);
        expect($metadata['execution_strategy'])->toBe('sequential_with_fallback');
        expect($metadata['total_execution_time_ms'])->toBeInt();
        expect($metadata['average_response_time_ms'])->toBeInt();
    });

    it('provides available generation strategies information', function (): void {
        $strategies = $this->service->getAvailableStrategies();

        expect($strategies)->toHaveKeys(['quick', 'comprehensive', 'custom']);

        foreach ($strategies as $strategy) {
            expect($strategy)->toHaveKeys(['name', 'description', 'models', 'estimated_time', 'best_for']);
        }

        expect($strategies['quick']['models'])->toBe(['gpt-4o', 'claude-3.5-sonnet']);
        expect($strategies['comprehensive']['models'])->toBe(['gpt-4o', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta']);
        expect($strategies['custom']['models'])->toBe('User-defined');
    });

    it('validates input parameters correctly', function (): void {
        expect(fn () => $this->service->generateNamesParallel('Valid idea', [], 'creative'))
            ->toThrow(InvalidArgumentException::class, 'At least one model must be specified');
    });

    it('handles execution statistics validation', function (): void {
        $invalidResult = ['invalid' => 'format'];

        expect(fn () => $this->service->getExecutionStats($invalidResult))
            ->toThrow(InvalidArgumentException::class, 'Invalid generation result format');
    });

    it('integrates properly with PrismAIService fallback system', function (): void {
        $fallbackResponse = "1. FallbackFlow\n2. BackupCraft\n3. SecondaryHub\n4. AlternateCore\n5. FallbackLab\n6. BackupForge\n7. SecondaryStream\n8. AlternateFlow\n9. FallbackCraft\n10. BackupHub";

        Prism::fake([
            TextResponseFake::make()->withText($fallbackResponse),
        ]);

        $result = $this->service->generateNamesParallel(
            'A backup system platform',
            ['grok-beta'],
            'tech-focused'
        );

        // Should include fallback metadata from PrismAIService
        $modelResult = $result['results']['grok-beta'];
        expect($modelResult)->toHaveKey('fallback_used');
        expect($modelResult)->toHaveKey('retry_count');
        expect($modelResult['fallback_used'])->toBe(false);
        expect($modelResult['retry_count'])->toBe(0);
    });
});
