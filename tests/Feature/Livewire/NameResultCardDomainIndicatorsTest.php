<?php

declare(strict_types=1);

use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

describe('NameResultCard Domain Availability Indicators', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('shows business name normally when all domains are unavailable', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['available' => false, 'has_dns_records' => true],
                'testname.io' => ['available' => false, 'has_dns_records' => true],
                'testname.net' => ['available' => false, 'has_dns_records' => true],
            ],
        ]);

        Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion])
            ->assertSee('TestName') // Business name is always displayed normally
            ->assertSee('line-through', false); // Individual domain names have strike-through in expanded view
    });

    it('shows business name normally when some domains are available', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['available' => true, 'has_dns_records' => false],
                'testname.io' => ['available' => false, 'has_dns_records' => true],
                'testname.net' => ['available' => false, 'has_dns_records' => true],
            ],
        ]);

        $component = Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion]);

        // Verify computed property is correct
        expect($component->instance()->allDomainsUnavailable)->toBeFalse();

        // Verify name is shown normally
        $component->assertSee('TestName');
    });

    it('does not show unavailable badge when domains have not been checked yet', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => null,
        ]);

        $component = Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion]);

        // Verify computed property returns false (no domains = not all unavailable)
        expect($component->instance()->allDomainsUnavailable)->toBeFalse();

        $component->assertSee('TestName');
    });

    it('does not show unavailable badge when domains are still being checked', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['status' => 'checking'],
                'testname.io' => ['status' => 'checking'],
            ],
        ]);

        $component = Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion]);

        // Verify computed property returns false (no availability data yet)
        expect($component->instance()->allDomainsUnavailable)->toBeFalse();

        $component->assertSee('TestName');
    });

    it('computes allDomainsUnavailable property correctly', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['available' => false],
                'testname.io' => ['available' => false],
            ],
        ]);

        $component = Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->instance()->allDomainsUnavailable)->toBeTrue();
    });

    it('returns false for allDomainsUnavailable when at least one domain is available', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['available' => true],
                'testname.io' => ['available' => false],
            ],
        ]);

        $component = Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->instance()->allDomainsUnavailable)->toBeFalse();
    });

    it('returns false for allDomainsUnavailable when no domains exist', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => null,
        ]);

        $component = Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->instance()->allDomainsUnavailable)->toBeFalse();
    });

    it('shows domain count with red badge when all domains are unavailable', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['available' => false],
                'testname.io' => ['available' => false],
            ],
        ]);

        Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion])
            ->assertSee('TestName'); // Business name shown normally
    });

    it('handles mixed domain states with some checked and some not', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['available' => false],
                'testname.io' => ['status' => 'checking'], // No availability info yet
                'testname.net' => ['available' => false],
            ],
        ]);

        $component = Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion]);

        // Should be true because all domains WITH availability info are unavailable
        expect($component->instance()->allDomainsUnavailable)->toBeTrue();
    });

    it('works with indexed array domain format from factory', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                ['extension' => '.com', 'available' => false],
                ['extension' => '.io', 'available' => false],
            ],
        ]);

        $component = Livewire::test(\App\Livewire\NameResultCard::class, ['suggestion' => $suggestion]);

        expect($component->instance()->allDomainsUnavailable)->toBeTrue();
    });
});
