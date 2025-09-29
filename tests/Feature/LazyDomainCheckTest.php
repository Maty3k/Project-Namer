<?php

declare(strict_types=1);

use App\Livewire\ProjectPage;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('lazy domain checking only triggers when dropdown is expanded', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    // Create a name suggestion with placeholder domains
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestBusiness',
        'domains' => [
            'testbusiness.com' => ['available' => null, 'status' => 'pending'],
            'testbusiness.io' => ['available' => null, 'status' => 'pending'],
        ],
    ]);

    // Mock the HTTP responses for domain checking
    Http::fake([
        '*' => Http::response(['available' => true], 200),
    ]);

    $this->actingAs($user);

    // Verify domains have placeholder data initially (no availability checking yet)
    expect($suggestion->domains)->not->toBeEmpty();
    expect($suggestion->domains['testbusiness.com']['available'])->toBeNull();

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('checkDomainsForSuggestion', $suggestion->id)
        ->assertDispatched('show-toast', [
            'message' => 'Domain availability updated',
            'type' => 'success',
        ]);

    // Verify the suggestion was updated with actual availability data
    $suggestion->refresh();
    expect($suggestion->domains)->not->toBeEmpty();
});

test('domain checking does not repeat if already checked', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    // Create a name suggestion with already checked domains
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestBusiness',
        'domains' => [
            'testbusiness.com' => ['available' => true, 'last_checked' => now()],
            'testbusiness.io' => ['available' => false, 'last_checked' => now()],
        ],
    ]);

    // No HTTP calls should be made since domains are already checked
    Http::fake();

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('checkDomainsForSuggestion', $suggestion->id);
    // No toast should be dispatched since no checking occurred

    // Verify no HTTP calls were made
    Http::assertNothingSent();
});

test('domain checking handles non-existent suggestion gracefully', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('checkDomainsForSuggestion', 999999)
        ->assertDispatched('show-toast', [
            'message' => 'Name suggestion not found',
            'type' => 'error',
        ]);
});
