<?php

declare(strict_types=1);

use App\Livewire\ShareModal;
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

describe('ShareModal Component', function (): void {
    it('renders successfully', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->assertStatus(200);
    });

    it('has required public properties', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration]);

        expect($component->get('title'))->toBe('');
        expect($component->get('description'))->toBe('');
        expect($component->get('shareType'))->toBe('public');
        expect($component->get('password'))->toBe('');
        expect($component->get('expiresInDays'))->toBeNull();
    });

    it('initializes with default values', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->assertSet('shareType', 'public')
            ->assertSet('password', '')
            ->assertSet('expiresInDays', null)
            ->assertSet('shareUrl', null)
            ->assertSet('isLoading', false);
    });

    it('creates a public share', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'My Amazing Project')
            ->set('description', 'Check out these cool logos')
            ->set('shareType', 'public')
            ->call('createShare')
            ->assertSet('isLoading', false);

        expect($component->get('shareUrl'))->not->toBeNull();
        expect(Share::count())->toBe(1);
        $share = Share::first();
        expect($share->title)->toBe('My Amazing Project');
        expect($share->share_type)->toBe('public');
        expect($share->user_id)->toBe($this->user->id);
    });

    it('creates a password-protected share', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Protected Project')
            ->set('shareType', 'password_protected')
            ->set('password', 'secret123')
            ->call('createShare')
            ->assertSet('isLoading', false);

        expect($component->get('shareUrl'))->not->toBeNull();
        expect(Share::count())->toBe(1);
        $share = Share::first();
        expect($share->share_type)->toBe('password_protected');
        expect($share->validatePassword('secret123'))->toBeTrue();
    });

    it('creates share with expiration date', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Expiring Share')
            ->set('expiresInDays', 7)
            ->call('createShare');

        expect($component->get('shareUrl'))->not->toBeNull();
        $share = Share::first();
        expect($share->expires_at)->not->toBeNull();
        expect($share->expires_at->isFuture())->toBeTrue();
        expect($share->expires_at->greaterThan(now()->addDays(6)))->toBeTrue();
        expect($share->expires_at->lessThan(now()->addDays(8)))->toBeTrue();
    });

    it('validates required password for password-protected shares', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('shareType', 'password_protected')
            ->set('password', '')
            ->call('createShare')
            ->assertHasErrors(['password']);
    });

    it('validates minimum password length', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('shareType', 'password_protected')
            ->set('password', '12345')
            ->call('createShare')
            ->assertHasErrors(['password' => 'min']);
    });

    it('does not require password for public shares', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('shareType', 'public')
            ->set('password', '')
            ->call('createShare')
            ->assertHasNoErrors(['password']);
    });

    it('generates unique share URL', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Test Share')
            ->call('createShare');

        $shareUrl = $component->get('shareUrl');
        expect($shareUrl)->not->toBeNull();
        expect($shareUrl)->toContain('/share/');
    });

    it('dispatches success event after share creation', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Test Share')
            ->call('createShare')
            ->assertDispatched('share-created');
    });

    it('copies share URL to clipboard', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('shareUrl', $share->getShareUrl())
            ->call('copyToClipboard')
            ->assertDispatched('url-copied');
    });

    it('resets form after successful share creation', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Test Share')
            ->set('description', 'Test Description')
            ->call('createShare')
            ->call('resetForm')
            ->assertSet('title', '')
            ->assertSet('description', '')
            ->assertSet('shareType', 'public')
            ->assertSet('password', '')
            ->assertSet('shareUrl', null);
    });

    it('shows loading state during share creation', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Test Share')
            ->call('createShare');

        // Loading state should be set then unset
        // This is tested by ensuring the final state is false after creation
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Test Share')
            ->call('createShare');

        expect($component->get('isLoading'))->toBeFalse();
    });

    it('displays social media share URLs', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('shareUrl', $share->getShareUrl())
            ->call('getSocialMediaUrls');

        $urls = $component->get('socialMediaUrls');
        expect($urls)->toHaveKeys(['twitter', 'linkedin', 'facebook', 'reddit', 'whatsapp']);
    });

    it('prevents unauthorized users from creating shares', function (): void {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Unauthorized Share')
            ->call('createShare')
            ->assertForbidden();
    });

    it('validates title length', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', str_repeat('a', 256))
            ->call('createShare')
            ->assertHasErrors(['title' => 'max']);
    });

    it('validates description length', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('description', str_repeat('a', 1001))
            ->call('createShare')
            ->assertHasErrors(['description' => 'max']);
    });

    it('validates expiration days is positive', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('expiresInDays', -1)
            ->call('createShare')
            ->assertHasErrors(['expiresInDays' => 'min']);
    });

    it('opens modal', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->call('openModal')
            ->assertSet('showModal', true);
    });

    it('closes modal', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->call('openModal')
            ->call('closeModal')
            ->assertSet('showModal', false);
    });

    it('resets form when modal closes', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('title', 'Test')
            ->set('description', 'Test Desc')
            ->call('closeModal')
            ->assertSet('title', '')
            ->assertSet('description', '');
    });
});
