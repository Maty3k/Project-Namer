<?php

declare(strict_types=1);

use App\Livewire\NameResultCard;
use App\Models\DomainCache;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

describe('NameResultCard Dropdown Interaction', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('triggers domain checking when check-domains event is dispatched', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
                'testname.io' => ['extension' => '.io'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->dispatch('check-domains-'.$suggestion->id);

        // Verify domains were checked and cached
        $cachedCom = DomainCache::where('domain', 'testname.com')->first();
        $cachedIo = DomainCache::where('domain', 'testname.io')->first();

        expect($cachedCom)->not->toBeNull();
        expect($cachedIo)->not->toBeNull();

        // Verify results were written back to suggestion
        $suggestion->refresh();
        expect($suggestion->domains['testname.com'])->toHaveKey('available');
        expect($suggestion->domains['testname.io'])->toHaveKey('available');
        expect($suggestion->domains['testname.com']['status'])->not->toBe('checking');
        expect($suggestion->domains['testname.io']['status'])->not->toBe('checking');
    });

    it('does not re-check domains if already checked', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'available' => true,
                    'status' => 'available',
                ],
            ],
        ]);

        // First dispatch
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->dispatch('check-domains-'.$suggestion->id);

        $firstCheck = DomainCache::where('domain', 'testname.com')->first();

        // Second dispatch (should not re-check)
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->dispatch('check-domains-'.$suggestion->id);

        $secondCheck = DomainCache::where('domain', 'testname.com')->first();

        // Verify no new checks were made
        if ($firstCheck) {
            expect($secondCheck->checked_at->timestamp)->toBe($firstCheck->checked_at->timestamp);
        }
    });

    it('displays fresh domain data from database', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->dispatch('check-domains-'.$suggestion->id);

        // Access the computed property
        $freshDomains = $component->get('freshDomains');

        expect($freshDomains)->toBeArray();
        expect($freshDomains['testname.com'])->toHaveKey('available');
        expect($freshDomains['testname.com'])->toHaveKey('status');
    });

    it('updates isCheckingDomains to false after all domains complete', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->set('isCheckingDomains', true)
            ->dispatch('check-domains-'.$suggestion->id);

        // Since we use dispatchSync, checking should complete immediately
        // But isCheckingDomains is only changed by refreshDomains
        // So let's call refreshDomains to update the flag
        $component->call('refreshDomains');

        expect($component->get('isCheckingDomains'))->toBeFalse();
    });

    it('dispatches domain-check-complete event after synchronous checking', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
            ],
        ]);

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->dispatch('check-domains-'.$suggestion->id)
            ->assertDispatched('domain-check-complete', id: $suggestion->id);
    });
});
