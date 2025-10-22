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

describe('Logo Generations Search', function (): void {
    test('it filters logo generations by business name', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'TechCorp Solutions',
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Creative Design Studio',
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Blue Ocean Ventures',
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('search', 'tech')
            ->assertSee('TechCorp Solutions')
            ->assertDontSee('Creative Design Studio')
            ->assertDontSee('Blue Ocean Ventures');
    });

    test('it performs case-insensitive search', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'TechCorp Solutions',
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('search', 'TECHCORP')
            ->assertSee('TechCorp Solutions');
    });

    test('it shows all results when search is empty', function (): void {
        LogoGeneration::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('search', '')
            ->assertViewHas('logoGenerations', function ($logoGenerations): bool {
                return $logoGenerations->count() === 3;
            });
    });

    test('it shows no results when search matches nothing', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'TechCorp Solutions',
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('search', 'nonexistent')
            ->assertSee('No logo generations yet');
    });
});

describe('Logo Generations Filters', function (): void {
    test('it shows newest first by default', function (): void {
        $oldest = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Oldest Company',
            'created_at' => now()->subDays(3),
        ]);

        $middle = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Middle Company',
            'created_at' => now()->subDays(2),
        ]);

        $newest = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Newest Company',
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(LogoGenerations::class)
            ->assertViewHas('logoGenerations', function ($logoGenerations) use ($newest, $middle, $oldest): bool {
                return $logoGenerations->first()->id === $newest->id
                    && $logoGenerations->last()->id === $oldest->id;
            });
    });

    test('it filters by newest when filter is set to newest', function (): void {
        $oldest = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Oldest Company',
            'created_at' => now()->subDays(3),
        ]);

        $newest = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Newest Company',
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('filterBy', 'newest')
            ->assertViewHas('logoGenerations', function ($logoGenerations) use ($newest, $oldest): bool {
                return $logoGenerations->first()->id === $newest->id
                    && $logoGenerations->last()->id === $oldest->id;
            });
    });

    test('it filters by oldest when filter is set to oldest', function (): void {
        $oldest = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Oldest Company',
            'created_at' => now()->subDays(3),
        ]);

        $newest = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Newest Company',
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('filterBy', 'oldest')
            ->assertViewHas('logoGenerations', function ($logoGenerations) use ($newest, $oldest): bool {
                return $logoGenerations->first()->id === $oldest->id
                    && $logoGenerations->last()->id === $newest->id;
            });
    });

    test('it filters alphabetically when filter is set to alphabetical', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Zebra Company',
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Alpha Company',
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Beta Company',
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('filterBy', 'alphabetical')
            ->assertSeeInOrder(['Alpha Company', 'Beta Company', 'Zebra Company']);
    });

    test('it filters by favorited when filter is set to favorited', function (): void {
        $notSaved = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Not Saved Company',
            'is_saved' => false,
            'created_at' => now()->subDay(),
        ]);

        $saved = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Saved Company',
            'is_saved' => true,
            'created_at' => now()->subDays(2),
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('filterBy', 'favorited')
            ->assertViewHas('logoGenerations', function ($logoGenerations) use ($saved, $notSaved): bool {
                // Favorited should come first
                return $logoGenerations->first()->id === $saved->id
                    && $logoGenerations->last()->id === $notSaved->id;
            });
    });
});

describe('Combined Search and Filters', function (): void {
    test('it combines search and filter correctly', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Tech Alpha',
            'created_at' => now()->subDays(3),
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Tech Zulu',
            'created_at' => now()->subDays(2),
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Design Studio',
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('search', 'tech')
            ->set('filterBy', 'alphabetical')
            ->assertSeeInOrder(['Tech Alpha', 'Tech Zulu'])
            ->assertDontSee('Design Studio');
    });

    test('it combines search with favorited filter', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Tech Solutions',
            'is_saved' => true,
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Tech Corp',
            'is_saved' => false,
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Design Studio',
            'is_saved' => true,
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('search', 'tech')
            ->set('filterBy', 'favorited')
            ->assertSee('Tech Solutions')
            ->assertSee('Tech Corp')
            ->assertDontSee('Design Studio')
            ->assertViewHas('logoGenerations', function ($logoGenerations): bool {
                // Favorited "Tech Solutions" should be first
                return $logoGenerations->first()->business_name === 'Tech Solutions';
            });
    });
});

describe('Filter Reset and State Management', function (): void {
    test('it maintains filter state when navigating', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Company',
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('search', 'test')
            ->set('filterBy', 'alphabetical')
            ->assertSet('search', 'test')
            ->assertSet('filterBy', 'alphabetical');
    });

    test('it shows empty state when no results match filters', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Company',
            'is_saved' => false,
        ]);

        Livewire::test(LogoGenerations::class)
            ->set('search', 'nonexistent')
            ->assertSee('No logo generations yet');
    });
});
