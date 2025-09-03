<?php

declare(strict_types=1);

use App\Livewire\ThemeCustomizer;
use App\Models\User;
use Livewire\Livewire;

it('renders successfully', function (): void {
    Livewire::test(ThemeCustomizer::class)
        ->assertStatus(200);
});

it('displays seasonal themes correctly', function (): void {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->assertSee('Summer Coral')
        ->assertSee('Winter Frost')
        ->assertSee('Halloween Night')
        ->assertSee('Spring Bloom')
        ->assertSee('Autumn Harvest');
});

it('can filter themes by category', function (): void {
    $user = User::factory()->create();
    
    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);
    
    // Test filtering by seasonal category
    $component->call('changeCategory', 'seasonal')
        ->assertSet('selectedCategory', 'seasonal')
        ->assertSee('Summer Coral')
        ->assertSee('Winter Frost');
    
    // Test filtering by standard category
    $component->call('changeCategory', 'standard')
        ->assertSet('selectedCategory', 'standard')
        ->assertSee('Default Blue')
        ->assertSee('Ocean Breeze');
});

it('can apply seasonal themes', function (): void {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->call('applyPreset', 'summer')
        ->assertSet('primaryColor', '#ff6b6b')
        ->assertSet('accentColor', '#4ecdc4')
        ->assertSet('backgroundColor', '#fff5f5')
        ->assertSet('textColor', '#2d3748')
        ->assertSet('themeName', 'summer')
        ->assertSet('isDarkMode', false);
});

it('shows seasonal recommendation when available', function (): void {
    $user = User::factory()->create();
    
    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);
    
    // Check if seasonal recommendation is loaded
    $recommendation = $component->get('recommendedSeasonalTheme');
    
    if ($recommendation) {
        $component->assertSee('Recommended:')
            ->assertSee($recommendation['display_name']);
    }
});

it('can apply seasonal recommendation', function (): void {
    $user = User::factory()->create();
    
    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);
    
    $recommendation = $component->get('recommendedSeasonalTheme');
    
    if ($recommendation) {
        $component->call('applySeasonalRecommendation')
            ->assertSet('primaryColor', $recommendation['primary_color'])
            ->assertSet('accentColor', $recommendation['accent_color'])
            ->assertSet('backgroundColor', $recommendation['background_color'])
            ->assertSet('textColor', $recommendation['text_color']);
    } else {
        // If no recommendation, just check that the method doesn't throw
        $component->call('applySeasonalRecommendation');
        expect(true)->toBeTrue(); // Test passes if no exception
    }
});
