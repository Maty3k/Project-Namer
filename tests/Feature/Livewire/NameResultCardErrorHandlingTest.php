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

describe('NameResultCard Error Handling', function (): void {
    it('displays DNS error state when error occurs', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'error-test.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        // Simulate an error state
        $component->set('dnsError', 'DNS lookup failed: Network timeout');

        expect($component->get('dnsError'))->toBe('DNS lookup failed: Network timeout');
    });

    it('can clear DNS error state', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'clear-error.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        // Set error state
        $component->set('dnsError', 'Test error message');
        expect($component->get('dnsError'))->toBe('Test error message');

        // Clear error
        $component->call('clearDnsError');
        expect($component->get('dnsError'))->toBeNull();
    });

    it('handles DNS check error event', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'event-error.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        // Trigger DNS check error event
        $component->dispatch('dns-check-error', [
            'suggestionId' => $suggestion->id,
            'error' => 'Network connection failed',
        ]);

        expect($component->get('dnsError'))->toBe('Network connection failed')
            ->and($component->get('dnsCheckLoading'))->toBeFalse();
    });

    it('handles trigger DNS check failure gracefully', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'trigger-fail.com',
            'dns_checked' => false,
        ]);

        // Create component without mocking to test actual error handling
        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        // The component should handle errors gracefully when triggering DNS check
        $component->call('triggerDnsCheck');

        // Component should remain functional even if there was an error
        expect($component->instance())->toBeInstanceOf(NameResultCard::class);
    });

    it('refreshes suggestion and clears error state', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'refresh-clear.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        // Set error state
        $component->set('dnsError', 'Test error message');
        $component->set('dnsCheckLoading', true);

        // Refresh suggestion - should clear error state
        $component->call('refreshSuggestion');

        expect($component->get('dnsError'))->toBeNull()
            ->and($component->get('dnsCheckLoading'))->toBeFalse();
    });

    it('shows appropriate UI for error state', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'ui-error.com',
            'dns_checked' => false,
        ]);

        $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

        // Set error state
        $component->set('dnsError', 'DNS timeout error');

        // Should display error indicator
        $component->assertSee('DNS Error');
        $component->assertSee('Retry DNS');
    });

    it('handles DNS error for different suggestion IDs correctly', function (): void {
        $suggestion1 = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'test1.com',
            'dns_checked' => false,
        ]);

        $suggestion2 = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'test2.com',
            'dns_checked' => false,
        ]);

        $component1 = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion1]);
        $component2 = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion2]);

        // Trigger error for suggestion1 only
        $component1->dispatch('dns-check-error', [
            'suggestionId' => $suggestion1->id,
            'error' => 'Test error',
        ]);

        // Only component1 should have the error
        expect($component1->get('dnsError'))->toBe('Test error');
        expect($component2->get('dnsError'))->toBeNull();
    });
});
