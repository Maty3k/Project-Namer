<?php

declare(strict_types=1);

use App\Livewire\ShareManagement;
use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->logoGeneration = LogoGeneration::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

describe('ShareManagement Component', function (): void {
    it('renders successfully', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->assertStatus(200);
    });

    it('displays list of user shares', function (): void {
        Share::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class);

        expect($component->get('shares')->count())->toBe(3);
    });

    it('only shows authenticated user shares', function (): void {
        $otherUser = User::factory()->create();

        Share::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        Share::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class);

        expect($component->get('shares')->count())->toBe(3);
    });

    it('displays share analytics', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'view_count' => 10,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->assertSee('10');
    });

    it('deletes a share', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->call('deleteShare', $share->id)
            ->assertDispatched('share-deleted');

        expect(Share::find($share->id))->toBeNull();
    });

    it('prevents deleting other users shares', function (): void {
        $otherUser = User::factory()->create();
        $share = Share::factory()->create([
            'user_id' => $otherUser->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->call('deleteShare', $share->id)
            ->assertForbidden();

        expect(Share::find($share->id))->not->toBeNull();
    });

    it('filters by active shares', function (): void {
        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'is_active' => true,
        ]);

        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'is_active' => false,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->set('filterStatus', 'active');

        expect($component->get('shares')->count())->toBe(1);
        expect($component->get('shares')->first()->is_active)->toBeTrue();
    });

    it('filters by inactive shares', function (): void {
        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'is_active' => true,
        ]);

        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'is_active' => false,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->set('filterStatus', 'inactive');

        expect($component->get('shares')->count())->toBe(1);
        expect($component->get('shares')->first()->is_active)->toBeFalse();
    });

    it('filters by expired shares', function (): void {
        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
        ]);

        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->addDay(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->set('filterStatus', 'expired');

        expect($component->get('shares')->count())->toBe(1);
        expect($component->get('shares')->first()->isExpired())->toBeTrue();
    });

    it('shows all shares when no filter applied', function (): void {
        Share::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->set('filterStatus', 'all');

        expect($component->get('shares')->count())->toBe(5);
    });

    it('sorts by date descending', function (): void {
        $share1 = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'created_at' => now()->subDays(2),
        ]);

        $share2 = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'created_at' => now()->subDay(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'desc');

        $shares = $component->get('shares');
        expect($shares->first()->id)->toBe($share2->id);
        expect($shares->last()->id)->toBe($share1->id);
    });

    it('sorts by view count descending', function (): void {
        $share1 = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'view_count' => 5,
        ]);

        $share2 = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'view_count' => 15,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->set('sortField', 'view_count')
            ->set('sortDirection', 'desc');

        $shares = $component->get('shares');
        expect($shares->first()->id)->toBe($share2->id);
        expect($shares->last()->id)->toBe($share1->id);
    });

    it('paginates results', function (): void {
        Share::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class);

        expect($component->get('shares')->count())->toBeLessThanOrEqual(15);
    });

    it('loads next page of results', function (): void {
        Share::factory()->count(20)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        // Test first page
        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class);

        // Verify first page has exactly 15 items (pagination limit)
        expect($component->get('shares')->count())->toBe(15);

        // Test navigation to page 2
        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->call('gotoPage', 2, 'page');

        // Verify we have the remaining shares on page 2
        expect($component->get('shares')->count())->toBe(5);
    });

    it('copies share URL to clipboard', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->call('copyShareUrl', $share->id)
            ->assertDispatched('url-copied');
    });

    it('toggles share active status', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->call('toggleShareStatus', $share->id);

        $share->refresh();
        expect($share->is_active)->toBeFalse();
    });

    it('prevents toggling other users share status', function (): void {
        $otherUser = User::factory()->create();
        $share = Share::factory()->create([
            'user_id' => $otherUser->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->call('toggleShareStatus', $share->id)
            ->assertForbidden();

        $share->refresh();
        expect($share->is_active)->toBeTrue();
    });

    it('searches shares by title', function (): void {
        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Amazing Project',
        ]);

        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Another Project',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->set('search', 'Amazing');

        expect($component->get('shares')->count())->toBe(1);
        expect($component->get('shares')->first()->title)->toBe('Amazing Project');
    });

    it('displays empty state when no shares', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->assertSee('No shares found');
    });

    it('displays share type badge', function (): void {
        Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        Share::factory()->passwordProtected()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->assertSee('Public')
            ->assertSee('Password Protected');
    });

    it('displays expiration status', function (): void {
        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->addDays(7),
        ]);

        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->assertSee('days')
            ->assertSee('Never');
    });

    it('resets filters', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareManagement::class)
            ->set('filterStatus', 'active')
            ->set('search', 'test')
            ->call('resetFilters')
            ->assertSet('filterStatus', 'all')
            ->assertSet('search', '');
    });
});
