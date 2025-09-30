<?php

declare(strict_types=1);

use App\Livewire\NameResultCard;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user);
});

describe('NameResultCard DNS Integration', function (): void {
    it('displays DNS status indicators when DNS has been checked', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'example.com',
            'dns_checked' => true,
            'dns_has_records' => false, // Available
            'dns_checked_at' => now(),
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->get('dnsStatus')['checked'])->toBeTrue()
            ->and($component->get('dnsStatus')['appears_available'])->toBeTrue()
            ->and($component->get('dnsStatus')['has_records'])->toBeFalse();
    });

    it('displays pending DNS check status when DNS has not been checked', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'pending.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->get('dnsStatus')['checked'])->toBeFalse()
            ->and($component->get('dnsStatus')['appears_available'])->toBeFalse()
            ->and($component->get('dnsStatus')['has_records'])->toBeNull();
    });

    it('displays taken status when DNS has records', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'taken.com',
            'dns_checked' => true,
            'dns_has_records' => true, // Taken
            'dns_checked_at' => now(),
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->get('dnsStatus')['checked'])->toBeTrue()
            ->and($component->get('dnsStatus')['appears_available'])->toBeFalse()
            ->and($component->get('dnsStatus')['has_records'])->toBeTrue();
    });

    it('can trigger manual DNS check for unchecked suggestions', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'manual.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('triggerDnsCheck');

        // Verify a DNS check was queued (we'll implement this method)
        $component->assertDispatched('dns-check-triggered', ['suggestionId' => $suggestion->id]);
    });

    it('displays DNS check loading state during processing', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'loading.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        // Set loading state manually for testing
        $component->set('dnsCheckLoading', true);

        expect($component->get('dnsCheckLoading'))->toBeTrue();
    });

    it('shows DNS availability in domain summary', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'summary.com',
            'dns_checked' => true,
            'dns_has_records' => false,
            'domains' => [
                'summary.com' => ['available' => true],
                'summary.io' => ['available' => false],
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->get('hasDomains'))->toBeTrue()
            ->and($component->get('availableDomainsCount'))->toBe(1)
            ->and($component->get('totalDomainsCount'))->toBe(2)
            ->and($component->get('dnsFilteredCount'))->toBe(1); // We'll implement this
    });

    it('displays appropriate icons for DNS status', function (): void {
        // Test available domain
        $availableSuggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'available.com',
            'dns_checked' => true,
            'dns_has_records' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $availableSuggestion]);
        $component->assertSee('text-green-600'); // Success icon class

        // Test taken domain
        $takenSuggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'taken.com',
            'dns_checked' => true,
            'dns_has_records' => true,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $takenSuggestion]);
        $component->assertSee('text-red-600'); // Error icon class
    });

    it('provides DNS status in metadata section', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'metadata.com',
            'dns_checked' => true,
            'dns_has_records' => false,
            'dns_checked_at' => now(),
            'generation_metadata' => [
                'ai_model' => 'gpt-4',
                'generation_mode' => 'creative',
            ],
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        // DNS metadata should be included in the expandable section
        $component->assertSee('DNS Status');
        $component->assertSee('Available'); // DNS appears available
    });

    it('updates DNS status when suggestion data changes', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'updating.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->get('dnsStatus')['checked'])->toBeFalse();

        // Simulate DNS check completion
        $suggestion->update([
            'dns_checked' => true,
            'dns_has_records' => true,
            'dns_checked_at' => now(),
        ]);

        // Refresh component data
        $component->call('refreshSuggestion');

        expect($component->get('dnsStatus')['checked'])->toBeTrue()
            ->and($component->get('dnsStatus')['has_records'])->toBeTrue();
    });

    it('hides suggestions with DNS records when DNS filtering is enabled', function (): void {
        $availableSuggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'available.com',
            'dns_checked' => true,
            'dns_has_records' => false,
        ]);

        $takenSuggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'taken.com',
            'dns_checked' => true,
            'dns_has_records' => true,
        ]);

        // Test with DNS filtering enabled
        $component = Livewire::test(NameResultCard::class, [
            'suggestion' => $takenSuggestion,
            'dnsFilteringEnabled' => true,
        ]);

        expect($component->get('shouldHideForDns'))->toBeTrue();

        // Test with DNS filtering disabled
        $component = Livewire::test(NameResultCard::class, [
            'suggestion' => $takenSuggestion,
            'dnsFilteringEnabled' => false,
        ]);

        expect($component->get('shouldHideForDns'))->toBeFalse();
    });
});
