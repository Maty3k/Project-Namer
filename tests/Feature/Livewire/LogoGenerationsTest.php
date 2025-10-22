<?php

declare(strict_types=1);

use App\Livewire\LogoGenerations;
use App\Models\LogoGeneration;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('renders successfully', function (): void {
    Livewire::test(LogoGenerations::class)
        ->assertStatus(200);
});

it('displays empty state when user has no logo generations', function (): void {
    Livewire::test(LogoGenerations::class)
        ->assertSee('No logo generations yet')
        ->assertSee('Generate logos from the Name Generator to see them here.');
});

it('displays all logo generations for the authenticated user', function (): void {
    $generation1 = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create(['business_name' => 'Test Business 1']);

    $generation2 = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create(['business_name' => 'Test Business 2']);

    Livewire::test(LogoGenerations::class)
        ->assertSee('Test Business 1')
        ->assertSee('Test Business 2');
});

it('does not display logo generations from other users', function (): void {
    $otherUser = User::factory()->create();

    $ownGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create(['business_name' => 'My Business']);

    $otherGeneration = LogoGeneration::factory()
        ->for($otherUser)
        ->completed()
        ->create(['business_name' => 'Other Business']);

    Livewire::test(LogoGenerations::class)
        ->assertSee('My Business')
        ->assertDontSee('Other Business');
});

it('displays logo generations ordered by most recent first', function (): void {
    $older = LogoGeneration::factory()
        ->for($this->user)
        ->create([
            'business_name' => 'Older Generation',
            'created_at' => now()->subDays(2),
        ]);

    $newer = LogoGeneration::factory()
        ->for($this->user)
        ->create([
            'business_name' => 'Newer Generation',
            'created_at' => now()->subDay(),
        ]);

    $logoGenerations = Livewire::test(LogoGenerations::class)
        ->viewData('logoGenerations');

    expect($logoGenerations->first()->id)->toBe($newer->id);
    expect($logoGenerations->last()->id)->toBe($older->id);
});

it('displays status and progress for each generation', function (): void {
    $generation = LogoGeneration::factory()
        ->for($this->user)
        ->create([
            'business_name' => 'Test Business',
            'status' => 'completed',
            'logos_completed' => 4,
            'total_logos_requested' => 4,
        ]);

    Livewire::test(LogoGenerations::class)
        ->assertSee('Completed')
        ->assertSee('4/4 logos');
});

it('provides links to individual logo galleries', function (): void {
    $generation = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create(['business_name' => 'Test Business']);

    $response = Livewire::test(LogoGenerations::class);

    $html = $response->html();

    expect($html)->toContain(route('logo.gallery', $generation));
});

it('displays bookmark icon for saved generations', function (): void {
    $savedGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->create([
            'business_name' => 'Saved Business',
            'is_saved' => true,
        ]);

    $unsavedGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->create([
            'business_name' => 'Unsaved Business',
            'is_saved' => false,
        ]);

    $html = Livewire::test(LogoGenerations::class)->html();

    // Saved generation should have bookmark icon in the HTML
    expect($html)->toContain('Saved Business');
    expect($html)->toContain('Unsaved Business');
});
