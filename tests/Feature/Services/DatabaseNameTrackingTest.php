<?php

declare(strict_types=1);

use App\Models\GenerationCache;
use App\Services\FallbackNameService;

test('database tracks generated names across multiple requests', function (): void {
    // Clear any existing cache
    GenerationCache::truncate();

    $service = app(FallbackNameService::class);
    $businessIdea = 'a coffee shop in downtown';
    $mode = 'creative';

    // Generate first set of names
    $firstGeneration = $service->generateNames($businessIdea, $mode, 5);
    expect($firstGeneration)->toHaveCount(5);

    // Manually cache the first generation (simulating what the dashboard does)
    $inputHash = GenerationCache::generateHash($businessIdea, $mode, false);
    GenerationCache::updateOrCreate(
        ['input_hash' => $inputHash],
        [
            'business_description' => $businessIdea,
            'mode' => $mode,
            'deep_thinking' => false,
            'generated_names' => $firstGeneration,
            'cached_at' => now(),
        ]
    );

    // Get recent cached names (simulating dashboard loading previous names)
    $recentCaches = GenerationCache::where('cached_at', '>=', now()->subDay())
        ->orderBy('cached_at', 'desc')
        ->take(50)
        ->get();

    $previouslyGeneratedNames = [];
    foreach ($recentCaches as $cache) {
        $names = $cache->generated_names ?? [];
        if (is_array($names)) {
            $previouslyGeneratedNames = array_merge($previouslyGeneratedNames, $names);
        }
    }

    expect($previouslyGeneratedNames)->toHaveCount(5);
    expect($previouslyGeneratedNames)->toBe($firstGeneration);

    // Generate second set with exclusions
    $secondGeneration = $service->generateNames($businessIdea, $mode, 5);
    expect($secondGeneration)->toHaveCount(5);

    // Verify at least some names are different (allowing for potential random duplicates)
    $uniqueNames = array_unique(array_merge($firstGeneration, $secondGeneration));
    expect(count($uniqueNames))->toBeGreaterThan(5); // Should have more than 5 unique names total
});

test('database approach handles multiple cached generations', function (): void {
    // Clear any existing cache
    GenerationCache::truncate();

    $service = app(FallbackNameService::class);

    // Create multiple cache entries for different business ideas
    $businessIdeas = [
        'coffee shop downtown',
        'tech startup',
        'restaurant italian',
    ];

    $allGeneratedNames = [];

    foreach ($businessIdeas as $idea) {
        $names = $service->generateNames($idea, 'creative', 3);

        // Cache each generation
        $inputHash = GenerationCache::generateHash($idea, 'creative', false);
        GenerationCache::updateOrCreate(
            ['input_hash' => $inputHash],
            [
                'business_description' => $idea,
                'mode' => 'creative',
                'deep_thinking' => false,
                'generated_names' => $names,
                'cached_at' => now(),
            ]
        );

        $allGeneratedNames = array_merge($allGeneratedNames, $names);
    }

    // Verify all names are tracked
    expect($allGeneratedNames)->toHaveCount(9);

    // Load previously generated names from database
    $recentCaches = GenerationCache::where('cached_at', '>=', now()->subDay())
        ->orderBy('cached_at', 'desc')
        ->take(50)
        ->get();

    $previouslyGeneratedNames = [];
    foreach ($recentCaches as $cache) {
        $names = $cache->generated_names ?? [];
        if (is_array($names)) {
            $previouslyGeneratedNames = array_merge($previouslyGeneratedNames, $names);
        }
    }

    $previouslyGeneratedNames = array_unique($previouslyGeneratedNames);

    // Should contain at least 8 names (some duplicates are possible with fallback service)
    expect($previouslyGeneratedNames)->toBeGreaterThanOrEqual(8);

    // Generate new names that should mostly be different from previous
    $newNames = $service->generateNames('new business idea', 'creative', 5);
    expect($newNames)->toHaveCount(5);

    // Verify limited overlaps (fallback service can generate duplicates since it's random)
    $overlaps = array_intersect($newNames, $allGeneratedNames);
    // Should have at least 3 unique names out of 5 (allowing some random duplicates)
    expect(count($newNames) - count($overlaps))->toBeGreaterThanOrEqual(3);
});

test('database approach respects time limits', function (): void {
    // Clear any existing cache
    GenerationCache::truncate();

    $service = app(FallbackNameService::class);

    // Create an old cache entry (2 days ago)
    $oldNames = ['OldName1', 'OldName2', 'OldName3'];
    GenerationCache::create([
        'input_hash' => 'old_hash_123',
        'business_description' => 'old business',
        'mode' => 'creative',
        'deep_thinking' => false,
        'generated_names' => $oldNames,
        'cached_at' => now()->subDays(2),
    ]);

    // Create a recent cache entry
    $recentNames = ['RecentName1', 'RecentName2', 'RecentName3'];
    GenerationCache::create([
        'input_hash' => 'recent_hash_123',
        'business_description' => 'recent business',
        'mode' => 'creative',
        'deep_thinking' => false,
        'generated_names' => $recentNames,
        'cached_at' => now()->subHours(1),
    ]);

    // Load previously generated names (should only include recent ones)
    $recentCaches = GenerationCache::where('cached_at', '>=', now()->subDay())
        ->orderBy('cached_at', 'desc')
        ->take(50)
        ->get();

    $previouslyGeneratedNames = [];
    foreach ($recentCaches as $cache) {
        $names = $cache->generated_names ?? [];
        if (is_array($names)) {
            $previouslyGeneratedNames = array_merge($previouslyGeneratedNames, $names);
        }
    }

    // Should only contain recent names, not old ones
    expect($previouslyGeneratedNames)->toHaveCount(3);
    expect($previouslyGeneratedNames)->toBe($recentNames);

    foreach ($oldNames as $oldName) {
        expect($previouslyGeneratedNames)->not->toContain($oldName);
    }
});
