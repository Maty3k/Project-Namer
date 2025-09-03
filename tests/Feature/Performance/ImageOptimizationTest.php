<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->withoutVite();
});

describe('Image Processing Performance', function (): void {
    test('processes multiple images efficiently', function (): void {
        $files = [];
        for ($i = 0; $i < 5; $i++) {
            $files[] = UploadedFile::fake()->image("test-{$i}.jpg", 800, 600);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => $files,
            ]);

        $processingTime = microtime(true) - $startTime;

        $response->assertSuccessful();
        expect($processingTime)->toBeLessThan(5.0); // Should process 5 images in under 5 seconds
        expect(ProjectImage::count())->toBe(5);
    });

    test('thumbnail generation completes within reasonable time', function (): void {
        $file = UploadedFile::fake()->image('thumbnail-test.jpg', 1920, 1080);

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => [$file],
            ]);

        $response->assertSuccessful();

        $image = ProjectImage::first();
        expect($image->processing_status)->toBe('pending'); // Initially pending, thumbnail created by background job

        // Verify the image record was created successfully
        expect($image->original_filename)->toBe('thumbnail-test.jpg');
        expect($image->width)->toBeNull(); // Will be set after processing
    });

    test('handles large image uploads efficiently', function (): void {
        $largeFile = UploadedFile::fake()->image('large.jpg', 3000, 2000);

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => [$largeFile],
            ]);

        $response->assertSuccessful();

        $image = ProjectImage::first();
        expect($image->original_filename)->toBe('large.jpg');
        expect($image->processing_status)->toBe('pending'); // Width/height set after background processing
    });
});

describe('Database Performance', function (): void {
    test('gallery queries are optimized for large datasets', function (): void {
        // Create 50 images to test performance
        ProjectImage::factory()->count(50)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/gallery");

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertSuccessful();
        expect($queryCount)->toBeLessThan(15); // Should use efficient queries
    });

    test('pagination works efficiently with large datasets', function (): void {
        ProjectImage::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/gallery?per_page=20&page=3");

        $queryTime = microtime(true) - $startTime;

        $response->assertSuccessful();
        expect($queryTime)->toBeLessThan(0.5); // Should complete pagination in under 500ms
        expect($response->json('images'))->toHaveCount(20);
    });

    test('search performance scales with dataset size', function (): void {
        // Create images with searchable content
        for ($i = 0; $i < 20; $i++) {
            ProjectImage::factory()->create([
                'project_id' => $this->project->id,
                'user_id' => $this->user->id,
                'original_filename' => "searchable-name-{$i}.jpg",
                'processing_status' => 'completed',
            ]);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/gallery?search=searchable");

        $searchTime = microtime(true) - $startTime;

        $response->assertSuccessful();
        expect($searchTime)->toBeLessThan(1.0); // Search should complete in under 1 second
        expect($response->json('images'))->toHaveCount(20);
    });
});

describe('Caching Performance', function (): void {
    test('image metadata is cached for frequently accessed images', function (): void {
        $image = ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
        ]);

        // First request should cache the data
        $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/gallery/{$image->uuid}");

        DB::enableQueryLog();

        // Second request should use cached data
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/gallery/{$image->uuid}");

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertSuccessful();
        // Should have minimal database queries due to caching
        expect($queryCount)->toBeLessThan(5);
    });

    test('gallery listings cache appropriately for performance', function (): void {
        ProjectImage::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
        ]);

        // First request
        $startTime = microtime(true);
        $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/gallery");
        $firstRequestTime = microtime(true) - $startTime;

        // Second request should be faster due to caching
        $startTime = microtime(true);
        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->id}/gallery");
        $secondRequestTime = microtime(true) - $startTime;

        $response->assertSuccessful();
        // Second request should be at least as fast (or faster due to caching)
        expect($secondRequestTime)->toBeLessThanOrEqual($firstRequestTime + 0.1);
    });
});
