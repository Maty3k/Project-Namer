<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use App\Services\OpenAILogoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
    actingAs($this->user);
});

describe('Logo Persistence to NameSuggestion', function (): void {
    test('it updates NameSuggestion.logos when all logos are completed', function (): void {
        // Create a NameSuggestion with a business name
        $nameSuggestion = NameSuggestion::factory()
            ->for($this->project)
            ->create([
                'name' => 'TechStart',
                'logos' => null,
            ]);

        // Create a LogoGeneration with matching business name
        $logoGeneration = LogoGeneration::factory()
            ->for($this->user)
            ->create([
                'business_name' => 'TechStart',
                'status' => 'processing',
                'total_logos_requested' => 4,
                'logos_completed' => 3,
            ]);

        // Create 3 completed logos
        GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->completed()
            ->style('minimalist')
            ->create();

        GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->completed()
            ->style('modern')
            ->create();

        GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->completed()
            ->style('playful')
            ->create();

        // Create the 4th logo to trigger completion
        $fourthLogo = GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->create([
                'style' => 'corporate',
                'status' => 'processing',
            ]);

        // Simulate the service completing the 4th logo
        $fourthLogo->update([
            'file_path' => 'logos/test/corporate.png',
            'status' => 'completed',
        ]);

        $logoGeneration->increment('logos_completed');
        $logoGeneration->markAsCompleted();

        // Call the service method directly
        $service = app(OpenAILogoService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('updateNameSuggestionWithLogos');
        $method->setAccessible(true);
        $method->invoke($service, $logoGeneration);

        // Verify NameSuggestion was updated
        $nameSuggestion->refresh();
        expect($nameSuggestion->logos)->not->toBeNull();
        expect($nameSuggestion->logos)->toBeArray();
        expect($nameSuggestion->logos)->toHaveCount(4);

        // Verify each logo has style and url
        foreach ($nameSuggestion->logos as $logo) {
            expect($logo)->toHaveKey('style');
            expect($logo)->toHaveKey('url');
            expect($logo['style'])->toBeIn(['minimalist', 'modern', 'playful', 'corporate']);
        }
    });

    test('it only includes completed logos in NameSuggestion', function (): void {
        $nameSuggestion = NameSuggestion::factory()
            ->for($this->project)
            ->create([
                'name' => 'FailTest',
                'logos' => null,
            ]);

        $logoGeneration = LogoGeneration::factory()
            ->for($this->user)
            ->create([
                'business_name' => 'FailTest',
                'status' => 'completed',
                'total_logos_requested' => 4,
                'logos_completed' => 3,
            ]);

        // Create 2 completed logos
        GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->completed()
            ->style('minimalist')
            ->create();

        GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->completed()
            ->style('modern')
            ->create();

        // Create 1 failed logo
        GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->create([
                'style' => 'playful',
                'status' => 'failed',
                'error_message' => 'API error',
            ]);

        // Call the service method
        $service = app(OpenAILogoService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('updateNameSuggestionWithLogos');
        $method->setAccessible(true);
        $method->invoke($service, $logoGeneration);

        // Verify only completed logos are included
        $nameSuggestion->refresh();
        expect($nameSuggestion->logos)->toHaveCount(2);
        expect(collect($nameSuggestion->logos)->pluck('style')->toArray())
            ->toBe(['minimalist', 'modern']);
    });

    test('it logs warning when NameSuggestion is not found', function (): void {
        Log::shouldReceive('warning')
            ->once()
            ->with('No NameSuggestion found for business name', [
                'business_name' => 'NonExistent',
                'logo_generation_id' => 1,
            ]);

        $logoGeneration = LogoGeneration::factory()
            ->for($this->user)
            ->create([
                'id' => 1,
                'business_name' => 'NonExistent',
                'status' => 'completed',
            ]);

        $service = app(OpenAILogoService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('updateNameSuggestionWithLogos');
        $method->setAccessible(true);
        $method->invoke($service, $logoGeneration);
    });

    test('it logs info when NameSuggestion is successfully updated', function (): void {
        $nameSuggestion = NameSuggestion::factory()
            ->for($this->project)
            ->create([
                'name' => 'LogTest',
                'logos' => null,
            ]);

        $logoGeneration = LogoGeneration::factory()
            ->for($this->user)
            ->create([
                'business_name' => 'LogTest',
                'status' => 'completed',
            ]);

        GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->completed()
            ->style('minimalist')
            ->create();

        Log::shouldReceive('info')
            ->once()
            ->with('Updated NameSuggestion with logos', \Mockery::on(function ($context) use ($nameSuggestion) {
                return $context['name_suggestion_id'] === $nameSuggestion->id
                    && $context['business_name'] === 'LogTest'
                    && $context['logos_count'] === 1;
            }));

        Log::shouldReceive('debug')
            ->zeroOrMoreTimes();

        $service = app(OpenAILogoService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('updateNameSuggestionWithLogos');
        $method->setAccessible(true);
        $method->invoke($service, $logoGeneration);
    });

    test('it handles multiple NameSuggestions with same name correctly', function (): void {
        // Create two suggestions with the same name (different projects)
        $suggestion1 = NameSuggestion::factory()
            ->for($this->project)
            ->create([
                'name' => 'Duplicate',
                'logos' => null,
            ]);

        $otherProject = Project::factory()->for($this->user)->create();
        $suggestion2 = NameSuggestion::factory()
            ->for($otherProject)
            ->create([
                'name' => 'Duplicate',
                'logos' => null,
            ]);

        $logoGeneration = LogoGeneration::factory()
            ->for($this->user)
            ->create([
                'business_name' => 'Duplicate',
                'status' => 'completed',
            ]);

        GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->completed()
            ->style('minimalist')
            ->create();

        $service = app(OpenAILogoService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('updateNameSuggestionWithLogos');
        $method->setAccessible(true);
        $method->invoke($service, $logoGeneration);

        // Only the first suggestion should be updated (first() query)
        $suggestion1->refresh();
        expect($suggestion1->logos)->not->toBeNull();
        expect($suggestion1->logos)->toHaveCount(1);
    });
});

describe('Logo URL Generation', function (): void {
    test('it generates correct URLs for logos', function (): void {
        $nameSuggestion = NameSuggestion::factory()
            ->for($this->project)
            ->create([
                'name' => 'UrlTest',
                'logos' => null,
            ]);

        $logoGeneration = LogoGeneration::factory()
            ->for($this->user)
            ->create([
                'business_name' => 'UrlTest',
                'status' => 'completed',
            ]);

        $logo = GeneratedLogo::factory()
            ->for($logoGeneration, 'logoGeneration')
            ->completed()
            ->style('minimalist')
            ->create([
                'file_path' => 'logos/123/minimalist-abc123.png',
            ]);

        // Create a fake file so the URL accessor works
        Storage::disk('public')->put('logos/123/minimalist-abc123.png', 'fake content');

        $service = app(OpenAILogoService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('updateNameSuggestionWithLogos');
        $method->setAccessible(true);
        $method->invoke($service, $logoGeneration);

        $nameSuggestion->refresh();
        expect($nameSuggestion->logos[0]['url'])->toContain('storage/logos/123/minimalist-abc123.png');
    });
});
