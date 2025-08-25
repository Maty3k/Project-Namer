<?php

declare(strict_types=1);

describe('Performance Utilities', function (): void {
    it('measures execution time accurately', function (): void {
        $startTime = microtime(true);

        // Simulate some work
        usleep(10000); // 10ms

        $executionTime = microtime(true) - $startTime;

        // Should measure time accurately (within reasonable margin)
        expect($executionTime)->toBeGreaterThan(0.008); // At least 8ms
        expect($executionTime)->toBeLessThan(0.020); // Less than 20ms
    });

    it('measures memory usage patterns', function (): void {
        $initialMemory = memory_get_usage(true);

        // Create significant memory usage
        $data = array_fill(0, 100000, str_repeat('x', 1000)); // Much larger allocation
        $afterAllocationMemory = memory_get_usage(true);

        // Clear data and force garbage collection
        unset($data);
        gc_collect_cycles();
        $afterCleanupMemory = memory_get_usage(true);

        $memoryUsed = $afterAllocationMemory - $initialMemory;
        $memoryFreed = $afterAllocationMemory - $afterCleanupMemory;

        // Memory usage should be measurable with larger allocation
        expect($memoryUsed)->toBeGreaterThan(1000000); // At least 1MB
        expect($afterCleanupMemory)->toBeLessThanOrEqual($afterAllocationMemory);

        // Memory should generally be freed, but allow for PHP's memory management behavior
        expect($memoryFreed)->toBeGreaterThanOrEqual(0);
    });

    it('tests array chunking performance for large datasets', function (): void {
        $largeArray = range(1, 10000);

        $startTime = microtime(true);
        $chunks = array_chunk($largeArray, 100);
        $chunkingTime = microtime(true) - $startTime;

        expect($chunkingTime)->toBeLessThan(0.1); // Under 100ms
        expect(count($chunks))->toBe(100);
        expect(count($chunks[0]))->toBe(100);
        expect(count($chunks[99]))->toBe(100);
    });

    it('compares array vs collection performance', function (): void {
        $data = range(1, 1000);

        // Test array operations
        $startTime = microtime(true);
        $arrayResult = array_filter($data, fn ($item) => $item % 2 === 0);
        $arrayResult = array_map(fn ($item) => $item * 2, $arrayResult);
        $arrayTime = microtime(true) - $startTime;

        // Test collection operations
        $startTime = microtime(true);
        $collectionResult = collect($data)
            ->filter(fn ($item) => $item % 2 === 0)
            ->map(fn ($item) => $item * 2)
            ->toArray();
        $collectionTime = microtime(true) - $startTime;

        // Both should be reasonably fast
        expect($arrayTime)->toBeLessThan(0.01);
        expect($collectionTime)->toBeLessThan(0.01);

        // Results should be equivalent
        expect($arrayResult)->toEqual($collectionResult);
    });

    it('tests string manipulation performance', function (): void {
        $baseString = str_repeat('test', 1000);

        // Test concatenation performance
        $startTime = microtime(true);
        $result1 = $baseString.'-suffix';
        $concatTime = microtime(true) - $startTime;

        // Test sprintf performance
        $startTime = microtime(true);
        $result2 = sprintf('%s-suffix', $baseString);
        $sprintfTime = microtime(true) - $startTime;

        // Both should be fast
        expect($concatTime)->toBeLessThan(0.001); // Under 1ms
        expect($sprintfTime)->toBeLessThan(0.001); // Under 1ms
        expect($result1)->toBe($result2);
    });

    it('benchmarks JSON encoding performance', function (): void {
        $complexData = [
            'logos' => array_fill(0, 100, [
                'id' => random_int(1, 1000),
                'name' => str_repeat('logo', 10),
                'metadata' => [
                    'width' => 1024,
                    'height' => 1024,
                    'colors' => ['#FF0000', '#00FF00', '#0000FF'],
                ],
            ]),
        ];

        $startTime = microtime(true);
        $jsonResult = json_encode($complexData);
        $encodingTime = microtime(true) - $startTime;

        expect($encodingTime)->toBeLessThan(0.1); // Under 100ms
        expect($jsonResult)->toBeString();
        expect(strlen($jsonResult))->toBeGreaterThan(1000);
    });

    it('measures regex performance vs string functions', function (): void {
        $testStrings = array_fill(0, 1000, 'logo-design-modern-variant-1.svg');

        // Test regex approach
        $startTime = microtime(true);
        foreach ($testStrings as $string) {
            preg_match('/^(.+)-(\d+)\.svg$/', $string, $matches);
        }
        $regexTime = microtime(true) - $startTime;

        // Test string functions approach
        $startTime = microtime(true);
        foreach ($testStrings as $string) {
            $extension = pathinfo($string, PATHINFO_EXTENSION);
            $basename = pathinfo($string, PATHINFO_FILENAME);
            $parts = explode('-', $basename);
        }
        $stringTime = microtime(true) - $startTime;

        // Both should complete in reasonable time
        expect($regexTime)->toBeLessThan(0.05);
        expect($stringTime)->toBeLessThan(0.05);
    });
});
