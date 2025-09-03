<?php

declare(strict_types=1);

use App\Livewire\MoodBoardCanvas;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('renders successfully', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(MoodBoardCanvas::class, ['project' => $project])
        ->assertStatus(200);
});

it('can create and load mood boards', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(MoodBoardCanvas::class, ['project' => $project])
        ->set('newMoodBoardName', 'Test Board')
        ->set('newMoodBoardDescription', 'Test Description')
        ->set('newMoodBoardLayout', 'grid')
        ->call('createMoodBoard')
        ->assertHasNoErrors()
        ->assertSet('activeMoodBoard.name', 'Test Board');
});
