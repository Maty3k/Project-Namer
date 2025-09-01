<?php

declare(strict_types=1);

use App\Models\NameSuggestion;
use App\Models\Project;

test('a name suggestion belongs to a project', function () {
    $project = Project::factory()->create();
    $suggestion = NameSuggestion::factory()->for($project)->create();

    expect($suggestion->project)->toBeInstanceOf(Project::class);
    expect($suggestion->project->id)->toBe($project->id);
});

test('name suggestion has correct fillable attributes', function () {
    $fillable = ['project_id', 'name', 'domains', 'logos', 'is_hidden', 'generation_metadata'];
    $suggestion = new NameSuggestion;

    expect($suggestion->getFillable())->toBe($fillable);
});

test('name suggestion casts attributes correctly', function () {
    $suggestion = new NameSuggestion;
    $casts = $suggestion->getCasts();

    expect($casts)->toHaveKey('domains');
    expect($casts['domains'])->toBe('array');
    expect($casts)->toHaveKey('logos');
    expect($casts['logos'])->toBe('array');
    expect($casts)->toHaveKey('is_hidden');
    expect($casts['is_hidden'])->toBe('boolean');
    expect($casts)->toHaveKey('generation_metadata');
    expect($casts['generation_metadata'])->toBe('array');
});

test('visible scope filters hidden suggestions', function () {
    $project = Project::factory()->create();

    NameSuggestion::factory()->for($project)->count(2)->create(['is_hidden' => false]);
    NameSuggestion::factory()->for($project)->count(3)->create(['is_hidden' => true]);

    $visible = NameSuggestion::visible()->where('project_id', $project->id)->get();

    expect($visible)->toHaveCount(2);
    expect($visible->every(fn ($s) => $s->is_hidden === false))->toBeTrue();
});

test('hidden scope filters visible suggestions', function () {
    $project = Project::factory()->create();

    NameSuggestion::factory()->for($project)->count(2)->create(['is_hidden' => false]);
    NameSuggestion::factory()->for($project)->count(3)->create(['is_hidden' => true]);

    $hidden = NameSuggestion::hidden()->where('project_id', $project->id)->get();

    expect($hidden)->toHaveCount(3);
    expect($hidden->every(fn ($s) => $s->is_hidden === true))->toBeTrue();
});

test('domains are cast to array from json', function () {
    $domains = [
        ['extension' => '.com', 'available' => true],
        ['extension' => '.io', 'available' => false],
    ];

    $suggestion = NameSuggestion::factory()->create(['domains' => $domains]);

    expect($suggestion->domains)->toBeArray();
    expect($suggestion->domains)->toBe($domains);
});

test('logos are cast to array from json', function () {
    $logos = [
        ['url' => 'https://example.com/logo1.png', 'style' => 'modern'],
        ['url' => 'https://example.com/logo2.svg', 'style' => 'minimalist'],
    ];

    $suggestion = NameSuggestion::factory()->create(['logos' => $logos]);

    expect($suggestion->logos)->toBeArray();
    expect($suggestion->logos)->toBe($logos);
});

test('generation metadata is cast to array from json', function () {
    $metadata = [
        'ai_model' => 'gpt-4',
        'temperature' => 0.7,
        'generated_at' => '2025-09-01T10:00:00Z',
    ];

    $suggestion = NameSuggestion::factory()->create(['generation_metadata' => $metadata]);

    expect($suggestion->generation_metadata)->toBeArray();
    expect($suggestion->generation_metadata)->toBe($metadata);
});

test('is_hidden defaults to false', function () {
    $project = Project::factory()->create();
    $suggestion = NameSuggestion::factory()->for($project)->create();

    expect($suggestion->is_hidden)->toBeFalse();
});
