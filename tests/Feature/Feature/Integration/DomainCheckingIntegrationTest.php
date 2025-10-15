<?php

declare(strict_types=1);

use App\Livewire\NameResultCard;
use App\Models\DomainCache;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

describe('Domain Checking Integration', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('completes full domain checking workflow with queue processing', function (): void {
        // Don't fake the queue - we want real processing
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
                'testname.io' => ['extension' => '.io'],
            ],
        ]);

        // Step 1: Dispatch jobs
        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('checkDomains');

        // Verify domains are marked as checking
        $suggestion->refresh();
        expect($suggestion->domains['testname.com']['status'])->toBe('checking');
        expect($suggestion->domains['testname.io']['status'])->toBe('checking');
        expect($component->get('isCheckingDomains'))->toBeTrue();

        // Step 2: Process the queued jobs (simulating queue worker)
        $this->artisan('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
        ]);

        // Step 3: Verify DomainCache was populated
        $cachedCom = DomainCache::where('domain', 'testname.com')->first();
        $cachedIo = DomainCache::where('domain', 'testname.io')->first();

        expect($cachedCom)->not->toBeNull();
        expect($cachedIo)->not->toBeNull();
        expect($cachedCom->available)->not->toBeNull();
        expect($cachedIo->available)->not->toBeNull();

        // Step 4: Call refreshDomains to pull results from cache
        $component->call('refreshDomains');

        // Step 5: Verify domains were updated with results
        $suggestion->refresh();
        expect($suggestion->domains['testname.com']['status'])->not->toBe('checking');
        expect($suggestion->domains['testname.io']['status'])->not->toBe('checking');
        expect($suggestion->domains['testname.com']['available'])->not->toBeNull();
        expect($suggestion->domains['testname.io']['available'])->not->toBeNull();

        // Step 6: Verify isCheckingDomains was set to false
        expect($component->get('isCheckingDomains'))->toBeFalse();
    })->skip('Requires actual DNS lookups which may be slow or flaky in CI');

    it('handles synchronous queue processing', function (): void {
        // Set queue to sync for this test
        config(['queue.default' => 'sync']);

        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestBusiness',
            'domains' => [
                'testbusiness.com' => ['extension' => '.com'],
            ],
        ]);

        // Dispatch job - it should run immediately
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('checkDomains');

        // Job should have run immediately, so cache should be populated
        $cached = DomainCache::where('domain', 'testbusiness.com')->first();
        expect($cached)->not->toBeNull();

        // Refresh domains should pick up the cached result
        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('refreshDomains');

        $suggestion->refresh();
        expect($suggestion->domains['testbusiness.com']['status'])->not->toBe('checking');
        expect($component->get('isCheckingDomains'))->toBeFalse();
    })->skip('Requires actual DNS lookups which may be slow or flaky in CI');
});
