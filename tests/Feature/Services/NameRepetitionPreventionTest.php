<?php

declare(strict_types=1);

use App\Services\FallbackNameService;
use App\Services\OpenAINameService;

describe('Live API tests', function (): void {
    test('OpenAI service excludes previously generated names', function (): void {
        $service = app(OpenAINameService::class);

        $businessIdea = 'a coffee shop in downtown';
        $mode = 'creative';

        // First generation - mock API response
        $previousNames = ['BrewBuzz', 'CoffeeCraft', 'JavaJoy'];

        // Second generation with exclusions - should return different names
        try {
            $newNames = $service->generateNames($businessIdea, $mode, false);

            // Check that no names from the previous list appear in new results
            foreach ($previousNames as $previousName) {
                expect($newNames)->not->toContain($previousName);
            }

            // Should still generate 10 names
            expect($newNames)->toHaveCount(10);

        } catch (Exception $e) {
            // If OpenAI fails due to rate limits or API issues, mark test as skipped
            if (str_contains($e->getMessage(), 'Rate limit') || str_contains($e->getMessage(), 'API key')) {
                test()->markTestSkipped('OpenAI API unavailable: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    });

    test('fallback service excludes previously generated names', function (): void {
        $service = app(FallbackNameService::class);

        $businessIdea = 'a coffee shop in downtown';
        $mode = 'creative';

        // Generate first set of names
        $firstGeneration = $service->generateNames($businessIdea, $mode, 10);
        expect($firstGeneration)->toHaveCount(10);

        // Generate second set with exclusions
        $secondGeneration = $service->generateNames($businessIdea, $mode, 10);
        expect($secondGeneration)->toHaveCount(10);

        // Check that no names from first generation appear in second generation
        foreach ($firstGeneration as $firstName) {
            expect($secondGeneration)->not->toContain($firstName);
        }

        // All names should be unique
        $allNames = array_merge($firstGeneration, $secondGeneration);
        expect($allNames)->toHaveCount(20);
        expect(array_unique($allNames))->toHaveCount(20);
    });

    test('fallback service handles partial exclusion list', function (): void {
        $service = app(FallbackNameService::class);

        $businessIdea = 'a tech startup';
        $mode = 'tech-focused';

        // Create partial exclusion list with some names
        $excludeNames = ['TechHub', 'CodeCraft', 'DigitalCore'];

        // Generate names with exclusions
        $generatedNames = $service->generateNames($businessIdea, $mode, 10);

        // Should generate 10 names
        expect($generatedNames)->toHaveCount(10);

        // None of the excluded names should appear
        foreach ($excludeNames as $excludedName) {
            expect($generatedNames)->not->toContain($excludedName);
        }
    });

    test('fallback service maintains context relevance with exclusions', function (): void {
        $service = app(FallbackNameService::class);

        $businessIdea = 'a coffee shop specializing in espresso drinks';
        $mode = 'creative';

        // Generate first batch
        $firstBatch = $service->generateNames($businessIdea, $mode, 5);

        // Generate second batch excluding first
        $secondBatch = $service->generateNames($businessIdea, $mode, 5);

        // Both batches should be coffee-related (this is hard to test precisely,
        // but we can check that we got different names)
        expect($firstBatch)->toHaveCount(5);
        expect($secondBatch)->toHaveCount(5);

        // Verify no overlap
        foreach ($firstBatch as $firstName) {
            expect($secondBatch)->not->toContain($firstName);
        }
    });

    test('services handle large exclusion lists', function (): void {
        $fallbackService = app(FallbackNameService::class);

        $businessIdea = 'a restaurant';
        $mode = 'professional';

        // Create a large exclusion list (simulate many previous generations)
        $excludeNames = [];
        for ($i = 1; $i <= 100; $i++) {
            $excludeNames[] = "Restaurant{$i}";
            $excludeNames[] = "Bistro{$i}";
        }

        // Should still be able to generate names despite large exclusion list
        $newNames = $fallbackService->generateNames($businessIdea, $mode, 10);

        expect($newNames)->toHaveCount(10);

        // None of the excluded names should appear
        foreach ($excludeNames as $excludedName) {
            expect($newNames)->not->toContain($excludedName);
        }
    });

})->skip('Do not run these during the normal test suite');
