<?php

declare(strict_types=1);

use App\Livewire\ProjectPage;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Project',
        'description' => 'A tech startup focused on project management solutions',
    ]);
    $this->actingAs($this->user);
});

describe('AI Model Uniqueness', function (): void {
    test('removes duplicate names across different AI models', function (): void {
        // Mock Prism responses with overlapping names (order matches selectedAIModels)
        Prism::fake([
            TextResponseFake::make()->withText("1. StartupSpark\n2. VentureHub\n3. BusinessLaunch\n4. TechForge\n5. InnovateLab"),
            TextResponseFake::make()->withText("1. VentureHub\n2. ProjectPilot\n3. StartupSpark\n4. BuilderBase\n5. LaunchPad"),
            TextResponseFake::make()->withText("1. CreativeCore\n2. TechForge\n3. ProjectPilot\n4. MarketMaven\n5. VentureHub"),
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'])
            ->set('generationMode', 'creative')
            ->call('generateMoreNames');

        // Verify we have unique names only (not 15 total, but fewer due to deduplication)
        $suggestions = NameSuggestion::where('project_id', $this->project->id)->get();

        // Collect all names to verify uniqueness
        $allNames = $suggestions->pluck('name')->toArray();
        $uniqueNames = array_unique($allNames);

        // Should have unique names only
        expect(count($allNames))->toBe(count($uniqueNames));

        // Verify specific expected unique names are present
        $expectedUniqueNames = [
            'StartupSpark', 'VentureHub', 'BusinessLaunch', 'TechForge', 'InnovateLab',
            'ProjectPilot', 'BuilderBase', 'LaunchPad', 'CreativeCore', 'MarketMaven',
        ];

        foreach ($expectedUniqueNames as $expectedName) {
            expect($allNames)->toContain($expectedName);
        }

        // Should have exactly 10 unique names (no duplicates)
        expect(count($allNames))->toBe(10);
    });

    test('preserves original model attribution for first occurrence', function (): void {
        // Mock Prism responses with overlapping names (order matches selectedAIModels)
        Prism::fake([
            TextResponseFake::make()->withText("1. StartupSpark\n2. VentureHub"),
            TextResponseFake::make()->withText("1. VentureHub\n2. StartupSpark"), // Same names, different order
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('generationMode', 'creative')
            ->call('generateMoreNames');

        $suggestions = NameSuggestion::where('project_id', $this->project->id)->get();

        // Should have only 2 unique names
        expect($suggestions)->toHaveCount(2);

        // Verify that gpt-4 gets attribution since it's processed first
        $startupSpark = $suggestions->where('name', 'StartupSpark')->first();
        $ventureHub = $suggestions->where('name', 'VentureHub')->first();

        expect($startupSpark->generation_metadata['ai_model'])->toBe('gpt-4');
        expect($ventureHub->generation_metadata['ai_model'])->toBe('gpt-4');

        // Verify deduplication metadata is present
        expect($startupSpark->generation_metadata['deduplication_info']['unique_across_models'])->toBeTrue();
        expect($startupSpark->generation_metadata['deduplication_info']['first_generated_by'])->toBe('gpt-4');
    });

    test('handles case insensitive deduplication', function (): void {
        // Mock Prism responses with same names in different cases (order matches selectedAIModels)
        Prism::fake([
            TextResponseFake::make()->withText("1. StartupSpark\n2. VENTUREHUB"),
            TextResponseFake::make()->withText("1. startupspark\n2. VentureHub"),
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('generationMode', 'creative')
            ->call('generateMoreNames');

        $suggestions = NameSuggestion::where('project_id', $this->project->id)->get();

        // Should have only 2 unique names (case insensitive deduplication)
        expect($suggestions)->toHaveCount(2);

        $names = $suggestions->pluck('name')->toArray();
        expect($names)->toContain('StartupSpark');
        expect($names)->toContain('VENTUREHUB');
    });

    test('handles special character normalization in deduplication', function (): void {
        // Mock Prism responses with same names using different separators (order matches selectedAIModels)
        Prism::fake([
            TextResponseFake::make()->withText("1. Startup-Spark\n2. Venture.Hub"),
            TextResponseFake::make()->withText("1. StartupSpark\n2. Venture Hub"),
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('generationMode', 'creative')
            ->call('generateMoreNames');

        $suggestions = NameSuggestion::where('project_id', $this->project->id)->get();

        // Should have only 2 unique names (normalized deduplication)
        expect($suggestions)->toHaveCount(2);

        $names = $suggestions->pluck('name')->toArray();
        expect($names)->toContain('Startup-Spark');
        expect($names)->toContain('Venture.Hub');
    });

    test('maintains domain generation for unique names only', function (): void {
        // Mock Prism responses with duplicate names (order matches selectedAIModels)
        Prism::fake([
            TextResponseFake::make()->withText("1. StartupSpark\n2. VentureHub"),
            TextResponseFake::make()->withText("1. StartupSpark\n2. ProjectPilot"), // StartupSpark is duplicate
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('generationMode', 'creative')
            ->call('generateMoreNames');

        $suggestions = NameSuggestion::where('project_id', $this->project->id)->get();

        // Should have 3 unique names
        expect($suggestions)->toHaveCount(3);

        // Verify each unique name has domains generated
        foreach ($suggestions as $suggestion) {
            expect($suggestion->domains)->toBeArray();
            expect($suggestion->domains)->not->toBeEmpty();

            // Verify domain structure
            foreach ($suggestion->domains as $domainData) {
                expect($domainData)->toHaveKey('extension');
                expect($domainData)->toHaveKey('available');
                expect($domainData)->toHaveKey('status');
            }
        }
    });

    test('works with single model (no deduplication needed)', function (): void {
        // Mock single model response
        Prism::fake([
            TextResponseFake::make()->withText("1. StartupSpark\n2. VentureHub\n3. ProjectPilot"),
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('generationMode', 'creative')
            ->call('generateMoreNames');

        $suggestions = NameSuggestion::where('project_id', $this->project->id)->get();

        // Should have all 3 names
        expect($suggestions)->toHaveCount(3);

        // All should be attributed to gpt-4
        foreach ($suggestions as $suggestion) {
            expect($suggestion->generation_metadata['ai_model'])->toBe('gpt-4');
            expect($suggestion->generation_metadata['deduplication_info']['unique_across_models'])->toBeTrue();
            expect($suggestion->generation_metadata['deduplication_info']['first_generated_by'])->toBe('gpt-4');
        }
    });
});
