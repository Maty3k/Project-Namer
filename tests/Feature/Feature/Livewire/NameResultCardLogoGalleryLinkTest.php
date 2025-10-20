<?php

declare(strict_types=1);

use App\Livewire\NameResultCard;
use App\Models\LogoGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->suggestion = NameSuggestion::factory()->create([
        'project_id' => $this->project->id,
        'name' => 'TestBusiness',
    ]);
});

describe('NameResultCard Logo Gallery Link', function (): void {
    it('returns null for logoGenerationId when no logos exist', function (): void {
        $component = Livewire::test(NameResultCard::class, ['suggestion' => $this->suggestion]);

        expect($component->logoGenerationId)->toBeNull();
    });

    it('returns correct logoGenerationId when logos exist', function (): void {
        // Create a completed LogoGeneration for this name
        $logoGeneration = LogoGeneration::factory()->create([
            'business_name' => $this->suggestion->name,
            'status' => 'completed',
            'logos_completed' => 4,
        ]);

        // Update suggestion with logos data
        $this->suggestion->update([
            'logos' => [
                ['id' => 1, 'style' => 'minimalist', 'url' => 'https://example.com/logo1.png'],
                ['id' => 2, 'style' => 'modern', 'url' => 'https://example.com/logo2.png'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $this->suggestion]);

        expect($component->logoGenerationId)->toBe($logoGeneration->id);
    });

    it('returns the most recent logoGenerationId when multiple exist', function (): void {
        // Create two LogoGenerations
        $oldGeneration = LogoGeneration::factory()->create([
            'business_name' => $this->suggestion->name,
            'status' => 'completed',
            'created_at' => now()->subHour(),
        ]);

        $newGeneration = LogoGeneration::factory()->create([
            'business_name' => $this->suggestion->name,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        // Update suggestion with logos
        $this->suggestion->update([
            'logos' => [
                ['id' => 1, 'style' => 'minimalist', 'url' => 'https://example.com/logo1.png'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $this->suggestion]);

        expect($component->logoGenerationId)->toBe($newGeneration->id);
    });

    it('ignores non-completed logo generations', function (): void {
        // Create a processing LogoGeneration
        LogoGeneration::factory()->create([
            'business_name' => $this->suggestion->name,
            'status' => 'processing',
        ]);

        // Update suggestion with logos
        $this->suggestion->update([
            'logos' => [
                ['id' => 1, 'style' => 'minimalist', 'url' => 'https://example.com/logo1.png'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $this->suggestion]);

        expect($component->logoGenerationId)->toBeNull();
    });

    it('displays View in Gallery link when logos exist', function (): void {
        // Create a completed LogoGeneration
        $logoGeneration = LogoGeneration::factory()->create([
            'business_name' => $this->suggestion->name,
            'status' => 'completed',
        ]);

        // Update suggestion with logos
        $this->suggestion->update([
            'logos' => [
                ['id' => 1, 'style' => 'minimalist', 'url' => 'https://example.com/logo1.png'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $this->suggestion]);

        $component->assertSee('View in Gallery');
    });

    it('links to the correct logo gallery route', function (): void {
        // Create a completed LogoGeneration
        $logoGeneration = LogoGeneration::factory()->create([
            'business_name' => $this->suggestion->name,
            'status' => 'completed',
        ]);

        // Update suggestion with logos
        $this->suggestion->update([
            'logos' => [
                ['id' => 1, 'style' => 'minimalist', 'url' => 'https://example.com/logo1.png'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $this->suggestion]);

        $expectedUrl = route('logo-gallery', $logoGeneration->id);
        $component->assertSee($expectedUrl, false); // false = don't escape HTML
    });

    it('does not display View in Gallery link when no logos exist', function (): void {
        $component = Livewire::test(NameResultCard::class, ['suggestion' => $this->suggestion]);

        $component->assertDontSee('View in Gallery');
    });

    it('does not display View in Gallery link when logos exist but no completed generation', function (): void {
        // Create a failed LogoGeneration
        LogoGeneration::factory()->create([
            'business_name' => $this->suggestion->name,
            'status' => 'failed',
        ]);

        // Update suggestion with logos (edge case - logos exist but generation failed)
        $this->suggestion->update([
            'logos' => [
                ['id' => 1, 'style' => 'minimalist', 'url' => 'https://example.com/logo1.png'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $this->suggestion]);

        $component->assertDontSee('View in Gallery');
    });
});
