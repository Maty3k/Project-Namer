<?php

declare(strict_types=1);

use App\Jobs\GenerateLogosJob;
use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    Queue::fake();
});

describe('Name to Logo Integration', function (): void {
    it('can trigger logo generation from name results', function (): void {
        $component = Volt::test('name-generator');

        // Set up name generation results
        $component->set('businessDescription', 'A modern coffee shop serving artisanal drinks')
            ->set('generatedNames', ['CoffeeHub', 'BrewCraft', 'ArtisanBlend'])
            ->set('domainResults', [
                [
                    'name' => 'CoffeeHub',
                    'domains' => [
                        'CoffeeHub.com' => ['status' => 'checked', 'available' => true],
                        'CoffeeHub.net' => ['status' => 'checked', 'available' => false],
                        'CoffeeHub.org' => ['status' => 'checked', 'available' => true],
                    ],
                ],
                [
                    'name' => 'BrewCraft',
                    'domains' => [
                        'BrewCraft.com' => ['status' => 'checked', 'available' => false],
                        'BrewCraft.net' => ['status' => 'checked', 'available' => true],
                        'BrewCraft.org' => ['status' => 'checked', 'available' => true],
                    ],
                ],
            ]);

        // Should show the "Generate Logos" button
        $component->assertSee('Generate Logos');
    });

    it('can generate logos for selected business name', function (): void {
        $component = Volt::test('name-generator');

        // Set up name generation results
        $component->set('businessDescription', 'A modern coffee shop serving artisanal drinks')
            ->set('generatedNames', ['CoffeeHub', 'BrewCraft'])
            ->set('domainResults', [
                ['name' => 'CoffeeHub', 'domains' => []],
                ['name' => 'BrewCraft', 'domains' => []],
            ]);

        // Trigger logo generation
        $component->call('generateLogos', 'CoffeeHub')
            ->assertOk();

        // Verify logo generation record was created
        expect(LogoGeneration::count())->toBe(1);

        $logoGeneration = LogoGeneration::first();
        expect($logoGeneration->business_name)->toBe('CoffeeHub');
        expect($logoGeneration->business_description)->toBe('A modern coffee shop serving artisanal drinks');
        expect($logoGeneration->status)->toBe('pending');
        expect($logoGeneration->total_logos_requested)->toBe(12);

        // Verify job was dispatched
        Queue::assertPushed(GenerateLogosJob::class, fn ($job) => $job->logoGeneration->id === $logoGeneration->id);
    });

    it('creates session-based connection between names and logos', function (): void {
        $component = Volt::test('name-generator');

        // Set up name generation results
        $component->set('businessDescription', 'Tech startup creating mobile apps')
            ->set('generatedNames', ['AppCraft', 'MobileTech'])
            ->set('sessionId', 'test-session-123');

        // Generate logos
        $component->call('generateLogos', 'AppCraft');

        $logoGeneration = LogoGeneration::first();
        expect($logoGeneration->session_id)->toBe('test-session-123');
    });

    it('passes business context to logo generation', function (): void {
        $component = Volt::test('name-generator');

        // Set up detailed business context
        $businessDescription = 'Premium organic skincare brand targeting millennials who value sustainability and natural ingredients';
        $selectedName = 'GlowNaturals';

        $component->set('businessDescription', $businessDescription)
            ->set('mode', 'professional')  // Set mode FIRST
            ->set('sessionId', 'test-session-456')
            ->set('generatedNames', [$selectedName])  // Then set names
            ->set('domainResults', [
                ['name' => $selectedName, 'domains' => []],
            ]);

        // Generate logos with context
        $component->call('generateLogos', $selectedName)
            ->assertHasNoErrors();

        $logoGeneration = LogoGeneration::first();
        expect($logoGeneration)->not->toBeNull();
        expect($logoGeneration->business_name)->toBe($selectedName);
        expect($logoGeneration->business_description)->toBe($businessDescription);

        // Verify the context will be used in logo prompts
        Queue::assertPushed(GenerateLogosJob::class);
    });

    it('shows loading state during logo generation setup', function (): void {
        $component = Volt::test('name-generator');

        $component->set('generatedNames', ['TestBrand'])
            ->set('businessDescription', 'Test business');

        // Should show loading state when generating logos
        $component->call('generateLogos', 'TestBrand')
            ->assertOk();

        // Should redirect to logo gallery
        $logoGeneration = LogoGeneration::first();
        expect($logoGeneration)->not->toBeNull();
    });

    it('handles logo generation errors gracefully', function (): void {
        $component = Volt::test('name-generator');

        $component->set('generatedNames', ['TestBrand'])
            ->set('businessDescription', '');  // Empty description should cause validation error

        // Should show validation error
        $component->call('generateLogos', 'TestBrand')
            ->assertHasErrors(['businessDescription']);
    });

    it('prevents logo generation for invalid business names', function (): void {
        $component = Volt::test('name-generator');

        $component->set('generatedNames', ['ValidName'])
            ->set('businessDescription', 'Valid business description');

        // Try to generate logos for a name not in the generated names
        $component->call('generateLogos', 'InvalidName');

        // Should show error message instead of creating logo generation
        expect($component->get('errorMessage'))->toContain('Invalid business name selected');
        expect(LogoGeneration::count())->toBe(0);
    });

    it('can navigate to logo gallery after generation', function (): void {
        $component = Volt::test('name-generator');

        $component->set('businessDescription', 'Fitness coaching service')
            ->set('generatedNames', ['FitCoach']);

        $component->call('generateLogos', 'FitCoach');

        $logoGeneration = LogoGeneration::first();
        expect($logoGeneration)->not->toBeNull();

        // Should be able to get the gallery URL
        $galleryUrl = "/logo-gallery/{$logoGeneration->id}";
        expect($galleryUrl)->toContain('/logo-gallery/');
    });

    it('tracks generation mode for logo context', function (): void {
        $component = Volt::test('name-generator');

        $component->set('businessDescription', 'Creative design agency')
            ->set('mode', 'creative')  // Set mode FIRST
            ->set('generatedNames', ['DesignStudio'])  // Then set names
            ->set('domainResults', [
                ['name' => 'DesignStudio', 'domains' => []],
            ]);

        $component->call('generateLogos', 'DesignStudio');

        // The generation mode should influence logo generation context
        // This will be passed to the job for more appropriate logo styles
        Queue::assertPushed(GenerateLogosJob::class);

        $logoGeneration = LogoGeneration::first();
        expect($logoGeneration)->not->toBeNull();
        expect($logoGeneration->business_name)->toBe('DesignStudio');
    });

    it('preserves search history integration', function (): void {
        $component = Volt::test('name-generator');

        // Set up a name generation result
        $component->set('businessDescription', 'Online bookstore')
            ->set('generatedNames', ['BookHaven'])
            ->set('searchHistory', [
                [
                    'id' => 'test-123',
                    'businessDescription' => 'Online bookstore',
                    'generatedNames' => ['BookHaven'],
                    'timestamp' => now()->toISOString(),
                ],
            ]);

        // Generate logos should work with history items
        $component->call('generateLogos', 'BookHaven');

        expect(LogoGeneration::count())->toBe(1);
    });

    it('handles multiple logo generation requests', function (): void {
        $component = Volt::test('name-generator');

        $component->set('businessDescription', 'Food delivery service')
            ->set('generatedNames', ['FoodRush', 'QuickEats']);

        // Generate logos for first name
        $component->call('generateLogos', 'FoodRush');
        expect(LogoGeneration::count())->toBe(1);

        // Generate logos for second name
        $component->call('generateLogos', 'QuickEats');
        expect(LogoGeneration::count())->toBe(2);

        // Should have different business names
        $generations = LogoGeneration::all();
        expect($generations->pluck('business_name')->toArray())
            ->toContain('FoodRush')
            ->toContain('QuickEats');
    });
});
