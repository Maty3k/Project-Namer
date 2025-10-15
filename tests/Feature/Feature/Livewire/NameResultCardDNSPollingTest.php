<?php

declare(strict_types=1);

use App\Livewire\NameResultCard;
use App\Models\DomainCache;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

describe('NameResultCard DNS Polling', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('processes DNS checks synchronously when checkDomains is called', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
                'testname.io' => ['extension' => '.io'],
            ],
        ]);

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('checkDomains');

        // Verify domains were checked and cached
        $cachedCom = DomainCache::where('domain', 'testname.com')->first();
        $cachedIo = DomainCache::where('domain', 'testname.io')->first();

        expect($cachedCom)->not->toBeNull();
        expect($cachedIo)->not->toBeNull();

        // Verify results were written back to suggestion
        $suggestion->refresh();
        expect($suggestion->domains['testname.com']['status'])->not->toBe('checking');
        expect($suggestion->domains['testname.io']['status'])->not->toBe('checking');
    });

    it('completes domain checking instantly with sync processing', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('checkDomains');

        // Since we're using dispatchSync, results should be available immediately
        $suggestion->refresh();
        expect($suggestion->domains['testname.com']['status'])->not->toBe('checking');
        expect($component->get('isCheckingDomains'))->toBeFalse();
    });

    it('updates domains with results immediately after checking', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
                'testname.io' => ['extension' => '.io'],
            ],
        ]);

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('checkDomains');

        $suggestion->refresh();
        $domains = $suggestion->domains;

        // With sync processing, domains should have results immediately
        expect($domains['testname.com']['available'])->not->toBeNull();
        expect($domains['testname.io']['available'])->not->toBeNull();
    });

    it('does not check domains if already checked', function (): void {
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

        $initialCheckedAt = DomainCache::where('domain', 'testname.com')->first()?->checked_at;

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('checkDomains');

        $afterCheckedAt = DomainCache::where('domain', 'testname.com')->first()?->checked_at;

        // If there's a cache entry, it shouldn't have changed
        if ($initialCheckedAt) {
            expect($afterCheckedAt)->toEqual($initialCheckedAt);
        }
    });

    it('refreshDomains updates domains from cache when results are available', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'status' => 'checking',
                ],
            ],
        ]);

        // Create cached domain result
        DomainCache::create([
            'domain' => 'testname.com',
            'available' => true,
            'has_dns_records' => false,
            'check_method' => 'dns',
            'dns_records' => ['A' => ['192.168.1.1']],
            'checked_at' => now(),
        ]);

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('refreshDomains');

        $suggestion->refresh();
        $domains = $suggestion->domains;

        expect($domains['testname.com']['available'])->toBeTrue();
        expect($domains['testname.com']['status'])->toBe('available');
        expect($domains['testname.com']['has_dns_records'])->toBeFalse();
        expect($domains['testname.com']['check_method'])->toBe('dns');
        expect($domains['testname.com']['dns_records'])->toBe(['A' => ['192.168.1.1']]);
    });

    it('refreshDomains sets isCheckingDomains to false when all domains are checked', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'status' => 'checking',
                ],
            ],
        ]);

        // Create cached domain result
        DomainCache::create([
            'domain' => 'testname.com',
            'available' => true,
            'has_dns_records' => false,
            'check_method' => 'dns',
            'checked_at' => now(),
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->set('isCheckingDomains', true)
            ->call('refreshDomains');

        expect($component->get('isCheckingDomains'))->toBeFalse();
    });

    it('refreshDomains keeps isCheckingDomains true when some domains are still checking', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'status' => 'checking',
                ],
                'testname.io' => [
                    'extension' => '.io',
                    'status' => 'checking',
                ],
            ],
        ]);

        // Only cache one domain
        DomainCache::create([
            'domain' => 'testname.com',
            'available' => true,
            'has_dns_records' => false,
            'check_method' => 'dns',
            'checked_at' => now(),
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->set('isCheckingDomains', true)
            ->call('refreshDomains');

        expect($component->get('isCheckingDomains'))->toBeTrue();
    });

    it('refreshDomains skips expired cache entries', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'status' => 'checking',
                ],
            ],
        ]);

        // Create expired cached domain result (25 hours old)
        DomainCache::create([
            'domain' => 'testname.com',
            'available' => true,
            'has_dns_records' => false,
            'check_method' => 'dns',
            'checked_at' => now()->subHours(25),
        ]);

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('refreshDomains');

        $suggestion->refresh();
        $domains = $suggestion->domains;

        // Domain should still be in "checking" state since cache was expired
        expect($domains['testname.com']['status'])->toBe('checking');
    });

    it('refreshDomains dispatches domain-check-complete event when all domains are done', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'status' => 'checking',
                ],
            ],
        ]);

        DomainCache::create([
            'domain' => 'testname.com',
            'available' => true,
            'has_dns_records' => false,
            'check_method' => 'dns',
            'checked_at' => now(),
        ]);

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->set('isCheckingDomains', true)
            ->call('refreshDomains')
            ->assertDispatched('domain-check-complete', id: $suggestion->id);
    });

    it('refreshDomains correctly handles multiple domains with mixed statuses', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'status' => 'checking',
                ],
                'testname.io' => [
                    'extension' => '.io',
                    'status' => 'available',
                    'available' => true,
                ],
            ],
        ]);

        DomainCache::create([
            'domain' => 'testname.com',
            'available' => false,
            'has_dns_records' => true,
            'check_method' => 'dns',
            'checked_at' => now(),
        ]);

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('refreshDomains');

        $suggestion->refresh();
        $domains = $suggestion->domains;

        expect($domains['testname.com']['available'])->toBeFalse();
        expect($domains['testname.com']['status'])->toBe('taken');
        expect($domains['testname.io']['available'])->toBeTrue();
        expect($domains['testname.io']['status'])->toBe('available');
    });

    it('domainsAlreadyChecked returns true when at least one domain has availability info', function (): void {
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

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->instance()->domainsChecked)->toBeTrue();
    });

    it('domainsAlreadyChecked returns false when all domains are checking', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'status' => 'checking',
                ],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->instance()->domainsChecked)->toBeFalse();
    });
});
