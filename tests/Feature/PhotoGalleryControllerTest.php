<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    // Create test images for gallery
    $this->images = ProjectImage::factory()->count(3)->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'processing_status' => 'completed',
    ]);

    $this->withoutVite();
});

test('can fetch project gallery images', function (): void {
    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/gallery");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'images' => [
                '*' => [
                    'id',
                    'uuid',
                    'original_filename',
                    'file_path',
                    'thumbnail_path',
                    'width',
                    'height',
                    'file_size',
                    'title',
                    'description',
                    'tags',
                    'dominant_colors',
                    'created_at',
                ],
            ],
            'meta' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
            ],
        ]);

    expect($response->json('images'))->toHaveCount(3);
});

test('can filter gallery images by tags', function (): void {
    // Create images with specific tags
    $taggedImage = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'tags' => ['inspiration', 'branding'],
        'processing_status' => 'completed',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/gallery?tags=inspiration");

    $response->assertSuccessful();
    expect($response->json('images'))->toHaveCount(1);
    expect($response->json('images.0.id'))->toBe($taggedImage->id);
});

test('can search gallery images by filename', function (): void {
    $searchableImage = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'original_filename' => 'unique-search-name.jpg',
        'processing_status' => 'completed',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/gallery?search=unique-search");

    $response->assertSuccessful();
    expect($response->json('images'))->toHaveCount(1);
    expect($response->json('images.0.id'))->toBe($searchableImage->id);
});

test('can filter images by processing status', function (): void {
    $pendingImage = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'processing_status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/gallery?status=pending");

    $response->assertSuccessful();
    expect($response->json('images'))->toHaveCount(1);
    expect($response->json('images.0.id'))->toBe($pendingImage->id);
});

test('can sort gallery images by date and filename', function (): void {
    // Test sorting by created_at descending (newest first)
    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/gallery?sort=date_desc");

    $response->assertSuccessful();
    $images = collect($response->json('images'));
    expect($images->first()['created_at'])->toBeGreaterThanOrEqual($images->last()['created_at']);

    // Test sorting by filename ascending
    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/gallery?sort=name_asc");

    $response->assertSuccessful();
});

test('paginates gallery results correctly', function (): void {
    // Create 15 images to test pagination
    ProjectImage::factory()->count(12)->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'processing_status' => 'completed',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/gallery?per_page=10");

    $response->assertSuccessful();
    expect($response->json('images'))->toHaveCount(10);
    expect($response->json('meta.total'))->toBe(15); // 3 from beforeEach + 12 new
    expect($response->json('meta.last_page'))->toBe(2);
});

test('requires authentication for gallery access', function (): void {
    $response = $this->getJson("/api/projects/{$this->project->id}/gallery");

    $response->assertUnauthorized();
});

test('validates user can only access their own project gallery', function (): void {
    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$otherProject->id}/gallery");

    $response->assertForbidden();
});

test('handles empty gallery gracefully', function (): void {
    $emptyProject = Project::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$emptyProject->id}/gallery");

    $response->assertSuccessful();
    expect($response->json('images'))->toHaveCount(0);
    expect($response->json('meta.total'))->toBe(0);
});

test('can get image details by uuid', function (): void {
    $image = $this->images->first();

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/gallery/{$image->uuid}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'image' => [
                'id',
                'uuid',
                'original_filename',
                'stored_filename',
                'file_path',
                'thumbnail_path',
                'file_size',
                'mime_type',
                'width',
                'height',
                'aspect_ratio',
                'title',
                'description',
                'tags',
                'dominant_colors',
                'processing_status',
                'is_public',
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response->json('image.id'))->toBe($image->id);
});

test('can update image metadata', function (): void {
    $image = $this->images->first();

    $response = $this->actingAs($this->user)
        ->putJson("/api/projects/{$this->project->id}/gallery/{$image->uuid}", [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'tags' => ['updated', 'metadata'],
            'is_public' => true,
        ]);

    $response->assertSuccessful();

    $image->refresh();
    expect($image->title)->toBe('Updated Title');
    expect($image->description)->toBe('Updated description');
    expect($image->tags)->toBe(['updated', 'metadata']);
    expect($image->is_public)->toBeTrue();
});

test('validates metadata updates', function (): void {
    $image = $this->images->first();

    $response = $this->actingAs($this->user)
        ->putJson("/api/projects/{$this->project->id}/gallery/{$image->uuid}", [
            'title' => str_repeat('a', 256), // Too long
            'tags' => ['valid', str_repeat('b', 51)], // Tag too long
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'tags.1']);
});

test('can bulk delete images', function (): void {
    $imageIds = $this->images->pluck('uuid')->toArray();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/projects/{$this->project->id}/gallery/bulk", [
            'image_uuids' => [$imageIds[0], $imageIds[1]],
            'action' => 'delete',
        ]);

    $response->assertSuccessful();

    expect(ProjectImage::whereIn('uuid', [$imageIds[0], $imageIds[1]])->count())->toBe(0);
    expect(ProjectImage::where('uuid', $imageIds[2])->count())->toBe(1); // Third image remains
});

test('can bulk update image tags', function (): void {
    $imageIds = $this->images->pluck('uuid')->take(2)->toArray();

    $response = $this->actingAs($this->user)
        ->putJson("/api/projects/{$this->project->id}/gallery/bulk", [
            'image_uuids' => $imageIds,
            'action' => 'add_tags',
            'tags' => ['bulk-added', 'gallery'],
        ]);

    $response->assertSuccessful();

    foreach ($imageIds as $uuid) {
        $image = ProjectImage::where('uuid', $uuid)->first();
        expect($image->tags)->toContain('bulk-added', 'gallery');
    }
});
