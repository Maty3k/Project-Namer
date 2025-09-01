<?php

declare(strict_types=1);

use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

test('a project belongs to a user', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    expect($project->user)->toBeInstanceOf(User::class);
    expect($project->user->id)->toBe($user->id);
});

test('a project has many name suggestions', function (): void {
    $project = Project::factory()->create();
    $suggestions = NameSuggestion::factory()->count(3)->for($project)->create();

    expect($project->nameSuggestions)->toHaveCount(3);
    expect($project->nameSuggestions->first())->toBeInstanceOf(NameSuggestion::class);
});

test('a project can have a selected name', function (): void {
    $project = Project::factory()->create();
    $suggestion = NameSuggestion::factory()->for($project)->create();

    $project->selected_name_id = $suggestion->id;
    $project->save();

    expect($project->selectedName)->toBeInstanceOf(NameSuggestion::class);
    expect($project->selectedName->id)->toBe($suggestion->id);
});

test('a project has visible name suggestions scope', function (): void {
    $project = Project::factory()->create();

    NameSuggestion::factory()->for($project)->count(2)->create(['is_hidden' => false]);
    NameSuggestion::factory()->for($project)->count(1)->create(['is_hidden' => true]);

    expect($project->visibleNameSuggestions)->toHaveCount(2);
    expect($project->nameSuggestions)->toHaveCount(3);
});

test('uuid is automatically generated on creation', function (): void {
    $project = Project::factory()->create(['uuid' => null]);

    expect($project->uuid)->not()->toBeNull();
    expect(Str::isUuid($project->uuid))->toBeTrue();
});

test('project has correct fillable attributes', function (): void {
    $fillable = ['uuid', 'name', 'description', 'user_id', 'selected_name_id'];
    $project = new Project;

    expect($project->getFillable())->toBe($fillable);
});

test('project casts uuid to string', function (): void {
    $project = new Project;
    $casts = $project->getCasts();

    expect($casts)->toHaveKey('uuid');
    expect($casts['uuid'])->toBe('string');
});

test('project generates default name if not provided', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => null]);

    expect($project->name)->not()->toBeNull();
    expect($project->name)->toContain('Project');
});
