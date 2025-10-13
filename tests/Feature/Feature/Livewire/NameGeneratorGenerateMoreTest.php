<?php

declare(strict_types=1);

use App\Livewire\NameGeneratorDashboard;
use App\Models\User;
use App\Services\DNSLookupService;
use App\Services\OpenAINameService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Prevent external HTTP calls
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response(['available' => true], 200)]);

    // Fake queue to prevent job dispatching delays
    Queue::fake();

    // Mock DNS service to prevent real DNS lookups
    $this->mock(DNSLookupService::class, function ($mock): void {
        $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        $mock->shouldReceive('getDNSRecords')->andReturn([
            'A' => [],
            'AAAA' => [],
            'CNAME' => [],
            'MX' => [],
        ]);
    });
});

test('Dashboard shows generate more button when results exist', function (): void {
    // Mock OpenAI service
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->once()
        ->andReturn(['BusinessName1', 'BusinessName2', 'BusinessName3']);
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A coffee shop')
        ->set('generationMode', 'creative')
        ->call('generateNames');

    // Verify generate more button exists in results tab
    $component->assertSee('Generate More Names');
});

test('Dashboard can generate more names and append to existing', function (): void {
    // Mock OpenAI service for two generations
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->twice()
        ->andReturn(
            ['Name1', 'Name2', 'Name3'],
            ['Name4', 'Name5', 'Name6']
        );
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A tech startup')
        ->set('generationMode', 'brandable')
        ->call('generateNames');

    // Should have 3 names initially
    expect($component->get('generatedNames'))->toHaveCount(3);

    // Click generate more - automatically generates and appends
    $component->call('generateMoreNames');

    // Should now have 6 names total
    expect($component->get('generatedNames'))->toHaveCount(6);

    // Should contain all names
    expect($component->get('generatedNames'))->toContain('Name1', 'Name2', 'Name3', 'Name4', 'Name5', 'Name6');

    // Append flag should be reset after generation
    $component->assertSet('appendToExisting', false);

    // Should stay on results tab
    $component->assertSet('activeTab', 'results');
});

test('Dashboard removes duplicate names when appending', function (): void {
    // Mock OpenAI service returning some duplicate names
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->twice()
        ->andReturn(
            ['Name1', 'Name2', 'Name3'],
            ['Name3', 'Name4', 'Name5'] // Name3 is duplicate
        );
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A restaurant')
        ->set('generationMode', 'professional')
        ->call('generateNames');

    // Click generate more - automatically generates and deduplicates
    $component->call('generateMoreNames');

    // Should have 5 unique names (not 6)
    expect($component->get('generatedNames'))->toHaveCount(5);

    // Should contain all unique names
    expect($component->get('generatedNames'))->toContain('Name1', 'Name2', 'Name3', 'Name4', 'Name5');
});

test('Dashboard preserves settings when generating more', function (): void {
    // Mock OpenAI service for two generations
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->twice()
        ->andReturn(
            ['Name1', 'Name2', 'Name3'],
            ['Name4', 'Name5', 'Name6']
        );
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A fitness app')
        ->set('generationMode', 'tech-focused')
        ->call('generateNames');

    // Click generate more - automatically uses same settings
    $component->call('generateMoreNames');

    // Settings should be preserved
    $component->assertSet('businessIdea', 'A fitness app');
    $component->assertSet('generationMode', 'tech-focused');

    // Should have appended names
    expect($component->get('generatedNames'))->toHaveCount(6);
});

test('Dashboard resets append flag on generation failure', function (): void {
    // Mock OpenAI service to throw exception
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->once()
        ->andReturn(['Name1', 'Name2']);

    $mockService->shouldReceive('generateNames')
        ->once()
        ->andThrow(new Exception('API Error'));

    $this->app->instance(OpenAINameService::class, $mockService);

    // Mock fallback service
    $mockFallback = Mockery::mock(\App\Services\FallbackNameService::class);
    $mockFallback->shouldReceive('generateNames')
        ->once()
        ->andReturn(['FallbackName1', 'FallbackName2']);
    $this->app->instance(\App\Services\FallbackNameService::class, $mockFallback);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A business')
        ->set('generationMode', 'creative')
        ->call('generateNames');

    // Click generate more - automatically tries to generate but fails (uses fallback)
    $component->call('generateMoreNames');

    // Append flag should be reset even on failure
    $component->assertSet('appendToExisting', false);
});

test('Dashboard generate more works with AI generation enabled', function (): void {
    // Mock AI generation service
    $mockAIService = Mockery::mock(\App\Services\AI\AIGenerationService::class);
    $mockAIService->shouldReceive('generateWithModels')
        ->twice()
        ->andReturn(
            ['gpt-4' => ['AIName1', 'AIName2', 'AIName3']],
            ['gpt-4' => ['AIName4', 'AIName5', 'AIName6']]
        );
    $this->app->instance(\App\Services\AI\AIGenerationService::class, $mockAIService);

    // Generate initial names with AI
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'An AI startup')
        ->set('generationMode', 'tech-focused')
        ->set('useAIGeneration', true)
        ->set('selectedAIModels', ['gpt-4'])
        ->call('generateNamesWithAI');

    // Should have 3 names initially
    expect($component->get('generatedNames'))->toHaveCount(3);

    // Click generate more - automatically generates with AI
    $component->call('generateMoreNames');

    // Should now have 6 names total
    expect($component->get('generatedNames'))->toHaveCount(6);

    // Should contain all names
    expect($component->get('generatedNames'))->toContain('AIName1', 'AIName2', 'AIName3', 'AIName4', 'AIName5', 'AIName6');

    // Append flag should be reset
    $component->assertSet('appendToExisting', false);

    // Should stay on results tab
    $component->assertSet('activeTab', 'results');
});

test('Dashboard generates more with same business idea automatically', function (): void {
    // Mock OpenAI service
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->twice()
        ->andReturn(
            ['Coffee1', 'Coffee2', 'Coffee3'],
            ['Coffee4', 'Coffee5', 'Coffee6']
        );
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A coffee shop')
        ->set('generationMode', 'creative')
        ->call('generateNames');

    // Click generate more - automatically generates with same idea
    $component->call('generateMoreNames');

    // Should have 6 names total
    expect($component->get('generatedNames'))->toHaveCount(6);

    // Business idea should be unchanged
    $component->assertSet('businessIdea', 'A coffee shop');

    // Should contain names from both generations
    expect($component->get('generatedNames'))->toContain('Coffee1', 'Coffee4');
});

test('Dashboard generates immediately when clicking generate more button', function (): void {
    // Mock OpenAI service
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->twice()
        ->andReturn(
            ['Name1', 'Name2', 'Name3'],
            ['Name4', 'Name5', 'Name6']
        );
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A startup')
        ->set('generationMode', 'brandable')
        ->call('generateNames');

    // Should have 3 names initially
    expect($component->get('generatedNames'))->toHaveCount(3);

    // Click generate more - should immediately generate without user action
    $component->call('generateMoreNames');

    // Should have 6 names now (no need for additional generateNames call)
    expect($component->get('generatedNames'))->toHaveCount(6);
    $component->assertSet('appendToExisting', false);
});

test('Dashboard has default generation mode on mount', function (): void {
    $component = Livewire::test(NameGeneratorDashboard::class);

    // Should have creative as default generation mode
    $component->assertSet('generationMode', 'creative');
});

test('Dashboard can generate more names infinitely', function (): void {
    // Mock OpenAI service for 5 consecutive generations
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->times(5)
        ->andReturn(
            ['Set1Name1', 'Set1Name2'],
            ['Set2Name1', 'Set2Name2'],
            ['Set3Name1', 'Set3Name2'],
            ['Set4Name1', 'Set4Name2'],
            ['Set5Name1', 'Set5Name2']
        );
    $this->app->instance(OpenAINameService::class, $mockService);

    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A tech company')
        ->set('generationMode', 'tech-focused');

    // First generation
    $component->call('generateNames');
    expect($component->get('generatedNames'))->toHaveCount(2);
    $component->assertSet('appendToExisting', false);
    $component->assertSet('showResults', true);
    $component->assertSet('activeTab', 'results');

    // Second generation (append) - automatically generates
    $component->call('generateMoreNames');
    expect($component->get('generatedNames'))->toHaveCount(4);
    $component->assertSet('appendToExisting', false);
    $component->assertSet('showResults', true);
    $component->assertSet('activeTab', 'results');

    // Third generation (append) - automatically generates
    $component->call('generateMoreNames');
    expect($component->get('generatedNames'))->toHaveCount(6);
    $component->assertSet('appendToExisting', false);
    $component->assertSet('showResults', true);
    $component->assertSet('activeTab', 'results');

    // Fourth generation (append) - automatically generates
    $component->call('generateMoreNames');
    expect($component->get('generatedNames'))->toHaveCount(8);
    $component->assertSet('appendToExisting', false);
    $component->assertSet('showResults', true);
    $component->assertSet('activeTab', 'results');

    // Fifth generation (append) - automatically generates
    $component->call('generateMoreNames');
    expect($component->get('generatedNames'))->toHaveCount(10);
    $component->assertSet('appendToExisting', false);
    $component->assertSet('showResults', true);
    $component->assertSet('activeTab', 'results');

    // Verify all names from all generations are present
    expect($component->get('generatedNames'))->toContain(
        'Set1Name1',
        'Set2Name1',
        'Set3Name1',
        'Set4Name1',
        'Set5Name1'
    );
});
