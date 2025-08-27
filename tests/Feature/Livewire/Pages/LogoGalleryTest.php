<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('can render', function (): void {
    // Create a logo generation to pass to the component
    $logoGeneration = LogoGeneration::factory()->create();

    // Test the component with the required parameter
    $component = Volt::test('pages.logo-gallery', [
        'logoGenerationId' => $logoGeneration->id,
    ]);

    $component->assertStatus(200);
});
