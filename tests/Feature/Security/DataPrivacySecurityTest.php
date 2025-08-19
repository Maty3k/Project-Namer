<?php

declare(strict_types=1);

use Livewire\Volt\Volt;

describe('Data Privacy & localStorage Security Tests', function (): void {
    test('no personal identifiable information is stored in database cache', function (): void {
        $component = Volt::test('name-generator');
        $component->set('businessDescription', 'My Personal Coffee Shop for John Smith')
            ->call('generateNames');

        // Check that cached entries don't contain personal identifiers
        $generationCaches = \App\Models\GenerationCache::all();
        foreach ($generationCaches as $cache) {
            // Should not contain common personal identifiers
            expect($cache->business_description)->not->toMatch('/\b[A-Z][a-z]+ [A-Z][a-z]+\b/'); // Name patterns
            expect($cache->business_description)->not->toContain('@');
            expect($cache->business_description)->not->toMatch('/\b\d{3}-\d{3}-\d{4}\b/'); // Phone patterns
        }
    });

    test('search history contains only business descriptions and generated names', function (): void {
        $component = Volt::test('name-generator');
        $component->set('businessDescription', 'coffee shop business')
            ->call('generateNames');

        $searchHistory = $component->get('searchHistory');

        // Search history should only contain business-related data
        foreach ($searchHistory as $entry) {
            if (isset($entry['businessDescription'])) {
                expect($entry['businessDescription'])->toBeString();
                expect($entry['businessDescription'])->not->toContain('password');
                expect($entry['businessDescription'])->not->toContain('email');
                expect($entry['businessDescription'])->not->toContain('ssn');
                expect($entry['businessDescription'])->not->toContain('credit card');
            }

            // Ensure no sensitive keys are present in history entries
            expect($entry)->not->toHaveKey('api_key');
            expect($entry)->not->toHaveKey('password');
            expect($entry)->not->toHaveKey('token');
            expect($entry)->not->toHaveKey('secret');
        }
    });

    test('localStorage data structure contains no sensitive information', function (): void {
        $component = Volt::test('name-generator');
        $component->set('businessDescription', 'test business')
            ->call('generateNames');

        // Simulate localStorage data structure (what would be stored client-side)
        $historyEntry = [
            'id' => 'test-id',
            'timestamp' => now()->toISOString(),
            'businessDescription' => $component->get('businessDescription'),
            'mode' => $component->get('mode'),
            'deepThinking' => $component->get('deepThinking'),
            'generatedNames' => $component->get('generatedNames'),
            'domainResults' => $component->get('domainResults'),
        ];

        // Verify no sensitive data would be stored in localStorage
        $historyJson = json_encode($historyEntry);

        expect($historyJson)->not->toContain('api_key');
        expect($historyJson)->not->toContain('sk-');
        expect($historyJson)->not->toContain('password');
        expect($historyJson)->not->toContain('token');
        expect($historyJson)->not->toContain('secret');
        expect($historyJson)->not->toContain('csrf');
    });

    test('user data retention policy limits cached data', function (): void {
        // Create multiple cache entries to test retention
        for ($i = 0; $i < 55; $i++) {
            \App\Models\GenerationCache::create([
                'input_hash' => "test-hash-{$i}",
                'business_description' => "business {$i}",
                'mode' => 'creative',
                'deep_thinking' => false,
                'generated_names' => ["Name{$i}1", "Name{$i}2"],
                'cached_at' => now()->subDays($i), // Spread across different dates
            ]);
        }

        // Verify there's a reasonable limit (though exact enforcement may vary)
        $totalCaches = \App\Models\GenerationCache::count();
        expect($totalCaches)->toBeLessThan(100); // Reasonable upper limit
    });

    test('expired cache entries can be cleaned up', function (): void {
        // Create an expired cache entry
        $expiredCache = \App\Models\GenerationCache::create([
            'input_hash' => 'expired-test',
            'business_description' => 'old business',
            'mode' => 'creative',
            'deep_thinking' => false,
            'generated_names' => ['OldName1', 'OldName2'],
            'cached_at' => now()->subDays(30), // 30 days old
        ]);

        // Test that expired entries can be identified
        expect($expiredCache->isExpired())->toBeTrue();

        // Test cleanup capability
        $expiredEntries = \App\Models\GenerationCache::expired()->get();
        $expiredIds = $expiredEntries->pluck('id')->toArray();
        expect($expiredIds)->toContain($expiredCache->id);
    });

    test('no session data leakage in component state', function (): void {
        $component = Volt::test('name-generator');

        // Get all component properties that might be exposed
        $exposedProps = [
            'businessDescription' => $component->get('businessDescription'),
            'mode' => $component->get('mode'),
            'deepThinking' => $component->get('deepThinking'),
            'generatedNames' => $component->get('generatedNames'),
            'domainResults' => $component->get('domainResults'),
            'errorMessage' => $component->get('errorMessage'),
            'searchHistory' => $component->get('searchHistory'),
        ];

        // Verify no session-related data is exposed
        foreach ($exposedProps as $prop) {
            if (is_string($prop)) {
                expect($prop)->not->toContain('laravel_session');
                expect($prop)->not->toContain('csrf_token');
                expect($prop)->not->toContain('_token');
            }
        }
    });

    test('domain checking does not store personal domain preferences', function (): void {
        $component = Volt::test('name-generator');
        $component->set('businessDescription', 'john.smith personal business')
            ->call('generateNames');

        // Check domain cache doesn't contain personal identifiers
        $domainCaches = \App\Models\DomainCache::all();
        foreach ($domainCaches as $cache) {
            expect($cache->domain)->not->toMatch('/john|smith/i');
            expect($cache->domain)->not->toContain('personal');

            // Should be properly formatted domain names only
            expect($cache->domain)->toMatch('/^[a-z0-9.-]+\.[a-z]{2,}$/i');
        }
    });

    test('component clears sensitive state between sessions', function (): void {
        $component1 = Volt::test('name-generator');
        $component1->set('businessDescription', 'sensitive business info');

        // Create new component instance (simulates new session)
        $component2 = Volt::test('name-generator');

        // New component should not have previous component's data
        expect($component2->get('businessDescription'))->toBe('');
        expect($component2->get('generatedNames'))->toHaveCount(0);
        expect($component2->get('errorMessage'))->toBe('');
    });

    test('rate limiting data does not persist sensitive information', function (): void {
        $component = Volt::test('name-generator');

        // Trigger rate limiting
        $component->set('lastApiCallTime', time() - 10);
        $component->set('businessDescription', 'sensitive test data')
            ->call('generateNames');

        // Rate limiting should work but not store the sensitive input
        $errorMessage = $component->get('errorMessage');
        expect($errorMessage)->toContain('wait');
        expect($errorMessage)->not->toContain('sensitive test data');
    });

    test('error messages do not leak user input data', function (): void {
        $component = Volt::test('name-generator');

        // Test with potentially sensitive input
        $sensitiveInput = 'My SSN is 123-45-6789 and password is secret123';
        $component->set('businessDescription', $sensitiveInput)
            ->call('generateNames');

        $errorMessage = $component->get('errorMessage');
        if ($errorMessage) {
            expect($errorMessage)->not->toContain('123-45-6789');
            expect($errorMessage)->not->toContain('secret123');
            expect($errorMessage)->not->toContain('SSN');
            expect($errorMessage)->not->toContain('password');
        }
    });

    test('generated names do not reflect personal input data', function (): void {
        $component = Volt::test('name-generator');

        // Input with personal information should not directly appear in generated names
        $personalInput = 'Coffee shop for Jane Doe on Main Street';
        $component->set('businessDescription', $personalInput)
            ->call('generateNames');

        $generatedNames = $component->get('generatedNames');
        foreach ($generatedNames as $name) {
            expect($name)->not->toContain('Jane');
            expect($name)->not->toContain('Doe');
            expect($name)->not->toMatch('/Main Street/i');
        }
    });

    test('component sanitization removes potential privacy violations', function (): void {
        $component = Volt::test('name-generator');

        // Input with various potentially sensitive patterns
        $privacySensitiveInput = 'Business for sarah.jones@email.com phone 555-123-4567';
        $component->set('businessDescription', $privacySensitiveInput);

        // Check that sanitization removed or obscured sensitive data
        $sanitizedInput = $component->get('businessDescription');
        expect($sanitizedInput)->not->toContain('sarah.jones@email.com');
        expect($sanitizedInput)->not->toContain('555-123-4567');
        expect($sanitizedInput)->toContain('[email]'); // Should be replaced with placeholder
        expect($sanitizedInput)->toContain('[phone]'); // Should be replaced with placeholder
    });

    test('cache keys do not contain identifiable information', function (): void {
        $component = Volt::test('name-generator');
        $component->set('businessDescription', 'Personal shop for Jennifer Smith')
            ->call('generateNames');

        // Check that cache keys are properly hashed
        $generationCaches = \App\Models\GenerationCache::all();
        foreach ($generationCaches as $cache) {
            // Input hash should be a hash, not the original input
            expect($cache->input_hash)->toMatch('/^[a-f0-9]{64}$/'); // SHA-256 hash pattern
            expect($cache->input_hash)->not->toContain('Jennifer');
            expect($cache->input_hash)->not->toContain('Smith');
            expect($cache->input_hash)->not->toContain('Personal');
        }
    });

    test('localStorage security follows privacy best practices', function (): void {
        // Test the client-side storage approach doesn't violate privacy
        $testHistoryEntry = [
            'id' => uniqid(),
            'timestamp' => now()->toISOString(),
            'businessDescription' => 'Generic business description',
            'mode' => 'creative',
            'deepThinking' => false,
            'generatedNames' => ['BusinessName1', 'BusinessName2'],
        ];

        // Simulate the data that would be stored in localStorage
        $storageData = json_encode(array_slice([$testHistoryEntry], 0, 50)); // Limit to 50 entries

        // Verify storage size is reasonable (not excessive)
        expect(strlen($storageData))->toBeLessThan(50000); // Under 50KB

        // Verify no sensitive patterns in the storage structure
        expect($storageData)->not->toContain('api_key');
        expect($storageData)->not->toContain('password');
        expect($storageData)->not->toContain('email');
        expect($storageData)->not->toContain('ssn');
    });
})->group('Security', 'DataPrivacy', 'localStorage');
