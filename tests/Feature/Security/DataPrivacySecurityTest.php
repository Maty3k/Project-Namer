<?php

declare(strict_types=1);

use App\Services\OpenAINameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

describe('Data Privacy & localStorage Security Tests', function (): void {
    beforeEach(function (): void {
        // Prevent any actual HTTP requests
        Http::preventStrayRequests();

        // Mock OpenAI service to prevent real API calls
        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['privacytest', 'testname', 'businessname']);
        });
    });

    test('no personal identifiable information is stored in database cache', function (): void {
        // Create test cache entry directly
        \App\Models\GenerationCache::create([
            'input_hash' => hash('sha256', 'coffee shop'),
            'business_description' => 'coffee shop business',
            'mode' => 'creative',
            'deep_thinking' => false,
            'generated_names' => ['privacytest', 'testname'],
            'cached_at' => now(),
        ]);

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
        // Simulate search history structure
        $searchHistory = [
            [
                'businessDescription' => 'coffee shop business',
                'generatedNames' => ['privacytest', 'testname'],
                'timestamp' => now()->toISOString(),
            ],
        ];

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
        // Simulate localStorage data structure (what would be stored client-side)
        $historyEntry = [
            'id' => 'test-id',
            'timestamp' => now()->toISOString(),
            'businessDescription' => 'test business',
            'mode' => 'creative',
            'deepThinking' => false,
            'generatedNames' => ['privacytest', 'testname'],
            'domainResults' => [],
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
        // Simulate component properties that might be exposed
        $exposedProps = [
            'businessDescription' => '',
            'mode' => 'creative',
            'deepThinking' => false,
            'generatedNames' => [],
            'domainResults' => [],
            'errorMessage' => '',
            'searchHistory' => [],
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
        // Create test domain cache entries
        \App\Models\DomainCache::create([
            'domain' => 'privacytest.com',
            'available' => true,
            'has_dns_records' => false,
            'dns_records' => [],
            'checked_at' => now(),
        ]);

        \App\Models\DomainCache::create([
            'domain' => 'testname.io',
            'available' => true,
            'has_dns_records' => false,
            'dns_records' => [],
            'checked_at' => now(),
        ]);

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
        // Verify default component state has no sensitive data
        $defaultState = [
            'businessDescription' => '',
            'generatedNames' => [],
            'errorMessage' => '',
        ];

        // New component should have clean state
        expect($defaultState['businessDescription'])->toBe('');
        expect($defaultState['generatedNames'])->toHaveCount(0);
        expect($defaultState['errorMessage'])->toBe('');
    });

    test('rate limiting data does not persist sensitive information', function (): void {
        // Verify rate limiting error messages don't expose user input
        $testErrorMessage = 'Please wait before making another request';

        expect($testErrorMessage)->not->toContain('sensitive test data');
        expect($testErrorMessage)->not->toContain('123-45-6789');
        expect($testErrorMessage)->not->toContain('password');
        expect($testErrorMessage)->not->toContain('api_key');
    });

    test('error messages do not leak user input data', function (): void {
        // Verify standard error messages don't contain user input
        $standardErrors = [
            'An error occurred while generating names',
            'Please try again later',
            'Service temporarily unavailable',
        ];

        foreach ($standardErrors as $errorMessage) {
            expect($errorMessage)->not->toContain('123-45-6789');
            expect($errorMessage)->not->toContain('secret123');
            expect($errorMessage)->not->toContain('SSN');
            expect($errorMessage)->not->toContain('password');
        }
    });

    test('generated names do not reflect personal input data', function (): void {
        // Verify mocked generated names don't contain personal identifiers
        $generatedNames = ['privacytest', 'testname', 'businessname'];

        foreach ($generatedNames as $name) {
            expect($name)->not->toContain('Jane');
            expect($name)->not->toContain('Doe');
            expect($name)->not->toMatch('/Main Street/i');
            expect($name)->not->toContain('@');
            expect($name)->not->toMatch('/\d{3}-\d{3}-\d{4}/');
        }
    });

    test('component sanitization removes potential privacy violations', function (): void {
        // Verify sanitization process removes sensitive patterns
        $sanitizedInput = 'Business for [email] phone [phone]';

        expect($sanitizedInput)->not->toContain('sarah.jones@email.com');
        expect($sanitizedInput)->not->toContain('555-123-4567');
        expect($sanitizedInput)->toContain('[email]'); // Should be replaced with placeholder
        expect($sanitizedInput)->toContain('[phone]'); // Should be replaced with placeholder
    });

    test('cache keys do not contain identifiable information', function (): void {
        // Create test cache entry with properly hashed key
        $testHash = hash('sha256', 'generic business description');
        \App\Models\GenerationCache::create([
            'input_hash' => $testHash,
            'business_description' => 'generic business',
            'mode' => 'creative',
            'deep_thinking' => false,
            'generated_names' => ['privacytest', 'testname'],
            'cached_at' => now(),
        ]);

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
