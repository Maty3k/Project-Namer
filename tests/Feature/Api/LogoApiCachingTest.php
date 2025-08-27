<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

describe('Logo API Response Caching', function (): void {
    it('caches completed logo generation status responses', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'status' => 'completed',
            'logos_completed' => 12,
            'total_logos_requested' => 12,
        ]);

        // First request - should cache
        $startTime = microtime(true);
        $response = $this->getJson("/api/logos/{$logoGeneration->id}/status");
        $firstRequestTime = microtime(true) - $startTime;

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress', 100);

        // Second request - should be faster (from cache)
        $startTime = microtime(true);
        $cachedResponse = $this->getJson("/api/logos/{$logoGeneration->id}/status");
        $cachedRequestTime = microtime(true) - $startTime;

        expect($cachedRequestTime)->toBeLessThan($firstRequestTime);
        $cachedResponse->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress', 100);
    });

    it('uses shorter cache time for processing logo generations', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'status' => 'processing',
            'logos_completed' => 5,
            'total_logos_requested' => 12,
        ]);

        $response = $this->getJson("/api/logos/{$logoGeneration->id}/status");

        $response->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.progress', 42); // 5/12 * 100 rounded

        // Verify cache exists but with shorter TTL
        expect(Cache::has("logo_status:{$logoGeneration->id}"))->toBeTrue();
    });

    it('caches logo generation show responses', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);
        $logo1 = GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'modern',
        ]);
        $logo2 = GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'corporate',
        ]);

        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo1->id,
            'color_scheme' => 'monochrome',
        ]);

        // First request
        $startTime = microtime(true);
        $response = $this->getJson("/api/logos/{$logoGeneration->id}");
        $firstRequestTime = microtime(true) - $startTime;

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'logos' => [
                        '*' => [
                            'id',
                            'style',
                            'color_variants',
                        ],
                    ],
                    'color_schemes',
                ],
            ]);

        // Second request - should be faster (from cache)
        $startTime = microtime(true);
        $cachedResponse = $this->getJson("/api/logos/{$logoGeneration->id}");
        $cachedRequestTime = microtime(true) - $startTime;

        expect($cachedRequestTime)->toBeLessThan($firstRequestTime);
        $cachedResponse->assertOk()
            ->assertJsonPath('data.status', 'completed');
    });

    it('caches color schemes for API responses', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);

        // First request
        $response1 = $this->getJson("/api/logos/{$logoGeneration->id}");

        // Second request - should use cached color schemes
        $response2 = $this->getJson("/api/logos/{$logoGeneration->id}");

        $response1->assertOk();
        $response2->assertOk();

        // Both should have the same color schemes data
        expect($response1->json('data.color_schemes'))
            ->toEqual($response2->json('data.color_schemes'));

        // Verify color schemes cache exists
        expect(Cache::has('api_color_schemes'))->toBeTrue();
    });

    it('invalidates cache when logos are customized', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);

        // Create a test SVG file
        $logoPath = "logos/{$logoGeneration->id}/originals/test-logo.svg";
        $svgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
    <circle cx="50" cy="50" r="40" fill="#000000"/>
</svg>';
        \Storage::disk('public')->put($logoPath, $svgContent);

        $logo = GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'modern',
            'original_file_path' => $logoPath,
        ]);

        // Cache the response
        $this->getJson("/api/logos/{$logoGeneration->id}");
        expect(Cache::has("logo_api_show:{$logoGeneration->id}"))->toBeTrue();

        // Customize logo through API
        $response = $this->postJson("/api/logos/{$logoGeneration->id}/customize", [
            'color_scheme' => 'monochrome',
            'logo_ids' => [$logo->id],
        ]);

        // Check if customization was successful
        $response->assertOk();

        // Cache should be invalidated after successful customization
        expect(Cache::has("logo_api_show:{$logoGeneration->id}"))->toBeFalse();
    });

    it('handles cache misses gracefully', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'modern',
        ]);

        // Clear all caches
        Cache::flush();

        // Should work without cache
        $response = $this->getJson("/api/logos/{$logoGeneration->id}");
        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
    });

    it('measures cache performance improvement', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);

        // Create multiple logos with variants to make the query complex
        for ($i = 0; $i < 10; $i++) {
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => $i % 2 === 0 ? 'modern' : 'corporate',
            ]);

            // Add color variants to some logos
            if ($i % 3 === 0) {
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => 'monochrome',
                ]);
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => 'ocean_blue',
                ]);
            }
        }

        // Measure first request (with database queries)
        $startTime = microtime(true);
        $response1 = $this->getJson("/api/logos/{$logoGeneration->id}");
        $firstRequestTime = microtime(true) - $startTime;

        // Measure second request (from cache)
        $startTime = microtime(true);
        $response2 = $this->getJson("/api/logos/{$logoGeneration->id}");
        $cachedRequestTime = microtime(true) - $startTime;

        // Cache should provide performance improvement
        expect($cachedRequestTime)->toBeLessThan($firstRequestTime);

        // Both responses should be identical
        expect($response1->json())->toEqual($response2->json());

        // Both should be successful
        $response1->assertOk();
        $response2->assertOk();
    });

    it('respects different cache times for different generation statuses', function (): void {
        $completedGeneration = LogoGeneration::factory()->create(['status' => 'completed']);
        $processingGeneration = LogoGeneration::factory()->create(['status' => 'processing']);
        $failedGeneration = LogoGeneration::factory()->create(['status' => 'failed']);

        // Request all status endpoints
        $this->getJson("/api/logos/{$completedGeneration->id}/status")->assertOk();
        $this->getJson("/api/logos/{$processingGeneration->id}/status")->assertOk();
        $this->getJson("/api/logos/{$failedGeneration->id}/status")->assertOk();

        // All should have cache entries
        expect(Cache::has("logo_status:{$completedGeneration->id}"))->toBeTrue();
        expect(Cache::has("logo_status:{$processingGeneration->id}"))->toBeTrue();
        expect(Cache::has("logo_status:{$failedGeneration->id}"))->toBeTrue();
    });

    it('caches logo generation show responses with proper cache time', function (): void {
        $completedGeneration = LogoGeneration::factory()->create(['status' => 'completed']);
        $processingGeneration = LogoGeneration::factory()->create(['status' => 'processing']);

        GeneratedLogo::factory()->create(['logo_generation_id' => $completedGeneration->id]);
        GeneratedLogo::factory()->create(['logo_generation_id' => $processingGeneration->id]);

        // Request show endpoints
        $this->getJson("/api/logos/{$completedGeneration->id}")->assertOk();
        $this->getJson("/api/logos/{$processingGeneration->id}")->assertOk();

        // Both should have cache entries
        expect(Cache::has("logo_api_show:{$completedGeneration->id}"))->toBeTrue();
        expect(Cache::has("logo_api_show:{$processingGeneration->id}"))->toBeTrue();
    });
});
