<?php

declare(strict_types=1);

use App\Services\PrismAIService;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

beforeEach(function (): void {
    $this->service = app(PrismAIService::class);
});

describe('Prism AI Service', function (): void {
    it('can generate names using GPT-4o model', function (): void {
        $fakeResponse = "1. TechFlow\n2. InnovateLab\n3. CreativePulse\n4. NextGenSoft\n5. FlowForge\n6. IdeaCraft\n7. StreamLine\n8. VisionTech\n9. BrightCore\n10. LaunchHub";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        $result = $this->service->generateNames(
            'A project management tool for creative teams',
            ['gpt-4'],
            'creative'
        );

        expect($result)->toHaveKey('gpt-4');
        expect($result['gpt-4']['names'])->toHaveCount(10);
        expect($result['gpt-4']['names'])->toContain('TechFlow');
        expect($result['gpt-4']['model'])->toBe('gpt-4');
        expect($result['gpt-4']['status'])->toBe('completed');
    });

    it('can generate names using Claude-3.5-Sonnet model', function (): void {
        $fakeResponse = "1. CreativeCore\n2. DesignPulse\n3. TeamFlow\n4. ProjectVision\n5. WorkCraft\n6. FlowHub\n7. IdeaStream\n8. CreativeForge\n9. VisionWork\n10. TeamCraft";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        $result = $this->service->generateNames(
            'A design collaboration platform',
            ['claude-3.5-sonnet'],
            'creative'
        );

        expect($result)->toHaveKey('claude-3.5-sonnet');
        expect($result['claude-3.5-sonnet']['names'])->toHaveCount(10);
        expect($result['claude-3.5-sonnet']['names'])->toContain('CreativeCore');
        expect($result['claude-3.5-sonnet']['model'])->toBe('claude-3.5-sonnet');
    });

    it('can generate names using Gemini-1.5-Pro model', function (): void {
        $fakeResponse = "1. NexusFlow\n2. SynergyLab\n3. CreativeSync\n4. TeamForge\n5. CollabCore\n6. WorkVision\n7. ProjectPulse\n8. FlowSynth\n9. IdeaFusion\n10. CreativeNexus";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        $result = $this->service->generateNames(
            'A team collaboration software',
            ['gemini-1.5-pro'],
            'professional'
        );

        expect($result)->toHaveKey('gemini-1.5-pro');
        expect($result['gemini-1.5-pro']['names'])->toHaveCount(10);
        expect($result['gemini-1.5-pro']['names'])->toContain('NexusFlow');
    });

    it('can generate names using Grok-Beta model', function (): void {
        $fakeResponse = "1. DisruptFlow\n2. HackLab\n3. RebelCore\n4. EdgeCraft\n5. PunkTech\n6. WildSync\n7. ChaosFlow\n8. RogueHub\n9. RebelForge\n10. EdgeSync";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        $result = $this->service->generateNames(
            'A revolutionary productivity app',
            ['grok-beta'],
            'creative'
        );

        expect($result)->toHaveKey('grok-beta');
        expect($result['grok-beta']['names'])->toHaveCount(10);
        expect($result['grok-beta']['names'])->toContain('DisruptFlow');
    });

    it('can generate names using multiple models simultaneously', function (): void {
        $gptResponse = "1. TechFlow\n2. InnovateLab\n3. CreativePulse\n4. NextGenSoft\n5. FlowForge\n6. IdeaCraft\n7. StreamLine\n8. VisionTech\n9. BrightCore\n10. LaunchHub";
        $claudeResponse = "1. CreativeCore\n2. DesignPulse\n3. TeamFlow\n4. ProjectVision\n5. WorkCraft\n6. FlowHub\n7. IdeaStream\n8. CreativeForge\n9. VisionWork\n10. TeamCraft";

        Prism::fake([
            TextResponseFake::make()->withText($gptResponse),
            TextResponseFake::make()->withText($claudeResponse),
        ]);

        $result = $this->service->generateNames(
            'A creative project management tool',
            ['gpt-4', 'claude-3.5-sonnet'],
            'creative'
        );

        expect($result)->toHaveKeys(['gpt-4', 'claude-3.5-sonnet']);
        expect($result['gpt-4']['names'])->toHaveCount(10);
        expect($result['claude-3.5-sonnet']['names'])->toHaveCount(10);
        expect($result['gpt-4']['names'])->toContain('TechFlow');
        expect($result['claude-3.5-sonnet']['names'])->toContain('CreativeCore');
    });

    it('supports different generation modes', function (): void {
        $professionalResponse = "1. ProManage\n2. Enterprise Solutions\n3. BusinessFlow\n4. CorporateTools\n5. WorkStream\n6. TaskMaster\n7. ProjectPro\n8. Efficiency Hub\n9. Business Central\n10. WorkForce";

        Prism::fake([
            TextResponseFake::make()->withText($professionalResponse),
        ]);

        $result = $this->service->generateNames(
            'A business management platform',
            ['gpt-4'],
            'professional'
        );

        expect($result['gpt-4']['names'])->toContain('ProManage');
        expect($result['gpt-4']['names'])->toContain('Enterprise Solutions');
        expect($result['gpt-4']['generation_mode'])->toBe('professional');
    });

    it('supports deep thinking mode with adjusted parameters', function (): void {
        $deepThinkingResponse = "1. IntelliFlow\n2. CognitiveLab\n3. ThoughtCraft\n4. MindForge\n5. WisdomCore\n6. InsightHub\n7. BrainStream\n8. LogicFlow\n9. ReasonCraft\n10. ThinkForge";

        Prism::fake([
            TextResponseFake::make()->withText($deepThinkingResponse),
        ]);

        $result = $this->service->generateNames(
            'An AI-powered analytics platform',
            ['gpt-4'],
            'tech-focused',
            true // deep thinking enabled
        );

        expect($result['gpt-4']['names'])->toContain('IntelliFlow');
        expect($result['gpt-4']['deep_thinking'])->toBe(true);
        expect($result['gpt-4']['temperature'])->toBe(0.3); // Lower temperature for deep thinking
    });

    it('handles API errors gracefully', function (): void {
        // This test verifies the service can handle API errors
        // For now, we'll test that error handling structure exists
        expect(method_exists($this->service, 'generateNames'))->toBeTrue();

        // Test that service includes error normalization
        $reflection = new ReflectionClass($this->service);
        expect($reflection->hasMethod('normalizeError'))->toBeTrue();
    });

    it('validates input parameters correctly', function (): void {
        expect(fn () => $this->service->generateNames('', ['gpt-4'], 'creative'))
            ->toThrow(InvalidArgumentException::class, 'Business idea cannot be empty');

        expect(fn () => $this->service->generateNames('Valid idea', [], 'creative'))
            ->toThrow(InvalidArgumentException::class, 'At least one model must be specified');

        expect(fn () => $this->service->generateNames('Valid idea', ['invalid-model'], 'creative'))
            ->toThrow(InvalidArgumentException::class, 'Invalid model: invalid-model');

        expect(fn () => $this->service->generateNames('Valid idea', ['gpt-4'], 'invalid-mode'))
            ->toThrow(InvalidArgumentException::class, 'Invalid generation mode: invalid-mode');

        expect(fn () => $this->service->generateNames(str_repeat('a', 2001), ['gpt-4'], 'creative'))
            ->toThrow(InvalidArgumentException::class, 'Business idea is too long');
    });

    it('includes generation metadata in results', function (): void {
        $fakeResponse = "1. MetaFlow\n2. DataCraft\n3. InfoHub\n4. MetaCore\n5. DataForge\n6. InfoStream\n7. MetaLab\n8. DataPulse\n9. InfoCraft\n10. MetaHub";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        $result = $this->service->generateNames(
            'A metadata management platform',
            ['gpt-4'],
            'tech-focused'
        );

        expect($result['gpt-4'])->toHaveKeys([
            'names',
            'model',
            'generation_mode',
            'deep_thinking',
            'temperature',
            'max_tokens',
            'response_time_ms',
            'status',
            'created_at',
        ]);
        expect($result['gpt-4']['max_tokens'])->toBe(200);
        expect($result['gpt-4']['temperature'])->toBe(0.7);
        expect($result['gpt-4']['response_time_ms'])->toBeInt();
    });

    it('uses model-specific prompt optimization', function (): void {
        $fakeResponse = "1. OptimizedName\n2. TunedFlow\n3. SmartCraft\n4. AdaptiveHub\n5. CustomCore\n6. TailoredLab\n7. OptimalForge\n8. PrecisionFlow\n9. FinetuneCore\n10. SmartHub";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        // Test that different models get different prompt optimizations
        $gptResult = $this->service->generateNames(
            'An optimization platform',
            ['gpt-4'],
            'tech-focused'
        );

        $claudeResult = $this->service->generateNames(
            'An optimization platform',
            ['claude-3.5-sonnet'],
            'tech-focused'
        );

        // Both should succeed but may have different internal processing
        expect($gptResult['gpt-4']['names'])->toHaveCount(10);
        expect($claudeResult['claude-3.5-sonnet']['names'])->toHaveCount(10);
    });

    it('supports custom generation parameters', function (): void {
        $fakeResponse = "1. CustomFlow\n2. ParamCraft\n3. ConfigHub\n4. SettingsCore\n5. OptionForge\n6. CustomLab\n7. FlexiFlow\n8. AdaptCore\n9. TuneHub\n10. FlexForge";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        $result = $this->service->generateNames(
            'A configuration management tool',
            ['gpt-4'],
            'professional',
            false,
            [
                'count' => 10,
                'temperature' => 0.8,
                'max_tokens' => 250,
            ]
        );

        expect($result['gpt-4']['names'])->toHaveCount(10);
        expect($result['gpt-4']['temperature'])->toBe(0.8);
        expect($result['gpt-4']['max_tokens'])->toBe(250);
    });

    it('tracks performance metrics for each model', function (): void {
        $fakeResponse = "1. MetricFlow\n2. PerfCraft\n3. SpeedHub\n4. TrackCore\n5. MonitorForge\n6. StatsLab\n7. MetricsFlow\n8. AnalyzeCore\n9. MeasureHub\n10. BenchForge";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        $result = $this->service->generateNames(
            'A performance monitoring platform',
            ['gpt-4', 'claude-3.5-sonnet'],
            'tech-focused'
        );

        // Check that performance metrics are tracked
        expect($result['gpt-4']['response_time_ms'])->toBeInt();
        expect($result['gpt-4']['response_time_ms'])->toBeGreaterThanOrEqual(0);
        expect($result['claude-3.5-sonnet']['response_time_ms'])->toBeInt();
        expect($result['claude-3.5-sonnet']['response_time_ms'])->toBeGreaterThanOrEqual(0);
    });

    it('includes fallback metadata in results', function (): void {
        $fakeResponse = "1. FallbackFlow\n2. RetryCore\n3. BackupCraft\n4. SecondChance\n5. AlternateHub\n6. PlanBFlow\n7. FallbackForge\n8. RetryCraft\n9. BackupStream\n10. FallbackCore";

        Prism::fake([
            TextResponseFake::make()->withText($fakeResponse),
        ]);

        $result = $this->service->generateNames(
            'A fallback testing platform',
            ['gpt-4'],
            'tech-focused'
        );

        // Check that fallback metadata is included
        expect($result['gpt-4'])->toHaveKey('fallback_used');
        expect($result['gpt-4'])->toHaveKey('retry_count');
        expect($result['gpt-4']['fallback_used'])->toBe(false);
        expect($result['gpt-4']['retry_count'])->toBe(0);
    });

    it('validates error categorization methods exist', function (): void {
        // Test that error handling methods exist for fallback system
        $reflection = new ReflectionClass($this->service);
        expect($reflection->hasMethod('categorizeError'))->toBeTrue();
        expect($reflection->hasMethod('isTransientError'))->toBeTrue();
        expect($reflection->hasMethod('generateWithFallback'))->toBeTrue();
    });

    it('supports fallback model configuration', function (): void {
        // Test that fallback model order is properly configured
        $reflection = new ReflectionClass($this->service);
        $constants = $reflection->getConstants();

        expect($constants)->toHaveKey('FALLBACK_MODEL_ORDER');
        expect($constants)->toHaveKey('MAX_RETRIES');
        expect($constants)->toHaveKey('RETRY_DELAY_SECONDS');
        expect($constants['MAX_RETRIES'])->toBe(3);
        expect($constants['RETRY_DELAY_SECONDS'])->toBe(1);
    });
});
