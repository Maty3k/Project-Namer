<?php

declare(strict_types=1);

use App\Livewire\NameGeneratorDashboard;
use App\Models\User;
use App\Services\DomainCheckService;
use App\Services\OpenAINameService;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create a mock domain check service using an anonymous class
    $mockDomainService = new class
    {
        public function checkBusinessName(string $name): array
        {
            return [
                strtolower(str_replace(' ', '', $name)).'.com' => ['available' => true, 'status' => 'available'],
                strtolower(str_replace(' ', '', $name)).'.io' => ['available' => true, 'status' => 'available'],
                strtolower(str_replace(' ', '', $name)).'.co' => ['available' => true, 'status' => 'available'],
                strtolower(str_replace(' ', '', $name)).'.net' => ['available' => true, 'status' => 'available'],
            ];
        }
    };

    $this->app->instance(DomainCheckService::class, $mockDomainService);
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

    // Click generate more - sets append flag and switches to generate tab
    $component->call('generateMoreNames');

    // Should switch to generate tab
    $component->assertSet('activeTab', 'generate');
    $component->assertSet('appendToExisting', true);

    // User can now modify settings, then generate more names
    $component->call('generateNames');

    // Should now have 6 names total
    expect($component->get('generatedNames'))->toHaveCount(6);

    // Should contain all names
    expect($component->get('generatedNames'))->toContain('Name1', 'Name2', 'Name3', 'Name4', 'Name5', 'Name6');

    // Append flag should be reset after generation
    $component->assertSet('appendToExisting', false);
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

    // Click generate more, then generate
    $component->call('generateMoreNames')
        ->call('generateNames');

    // Should have 5 unique names (not 6)
    expect($component->get('generatedNames'))->toHaveCount(5);

    // Should contain all unique names
    expect($component->get('generatedNames'))->toContain('Name1', 'Name2', 'Name3', 'Name4', 'Name5');
});

test('Dashboard preserves existing business idea when generating more', function (): void {
    // Mock OpenAI service
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->once()
        ->andReturn(['Name1', 'Name2', 'Name3']);
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A fitness app')
        ->set('generationMode', 'tech-focused')
        ->call('generateNames');

    // Click generate more - switches to generate tab
    $component->call('generateMoreNames');

    // Business idea should still be populated
    $component->assertSet('businessIdea', 'A fitness app');
    $component->assertSet('generationMode', 'tech-focused');
    $component->assertSet('activeTab', 'generate');
    $component->assertSet('appendToExisting', true);
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

    // Click generate more to set append flag
    $component->call('generateMoreNames');
    $component->assertSet('appendToExisting', true);

    // Try to generate more but it fails (uses fallback)
    $component->call('generateNames');

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

    // Click generate more - sets append flag and switches to generate tab
    $component->call('generateMoreNames');
    $component->assertSet('activeTab', 'generate');
    $component->assertSet('appendToExisting', true);

    // User can modify settings, then generate more with AI
    $component->call('generateNamesWithAI');

    // Should now have 6 names total
    expect($component->get('generatedNames'))->toHaveCount(6);

    // Should contain all names
    expect($component->get('generatedNames'))->toContain('AIName1', 'AIName2', 'AIName3', 'AIName4', 'AIName5', 'AIName6');

    // Append flag should be reset
    $component->assertSet('appendToExisting', false);
});

test('Dashboard allows modifying business idea before generating more', function (): void {
    // Mock OpenAI service
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->twice()
        ->andReturn(
            ['Coffee1', 'Coffee2', 'Coffee3'],
            ['Tea1', 'Tea2', 'Tea3']
        );
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A coffee shop')
        ->set('generationMode', 'creative')
        ->call('generateNames');

    // Click generate more
    $component->call('generateMoreNames');
    $component->assertSet('appendToExisting', true);

    // User modifies business idea
    $component->set('businessIdea', 'A tea shop');

    // Generate more with modified idea
    $component->call('generateNames');

    // Should have 6 names total
    expect($component->get('generatedNames'))->toHaveCount(6);

    // Should contain names from both generations
    expect($component->get('generatedNames'))->toContain('Coffee1', 'Tea1');
});

test('Dashboard can cancel append mode', function (): void {
    // Mock OpenAI service
    $mockService = Mockery::mock(OpenAINameService::class);
    $mockService->shouldReceive('generateNames')
        ->once()
        ->andReturn(['Name1', 'Name2', 'Name3']);
    $this->app->instance(OpenAINameService::class, $mockService);

    // Generate initial names
    $component = Livewire::test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'A startup')
        ->set('generationMode', 'brandable')
        ->call('generateNames');

    // Click generate more
    $component->call('generateMoreNames');
    $component->assertSet('appendToExisting', true);

    // User cancels append mode
    $component->set('appendToExisting', false);
    $component->assertSet('appendToExisting', false);
});
