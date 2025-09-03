<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->withoutVite();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
});

test('can upload single image to project', function (): void {
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $response = $this->actingAs($this->user)
        ->post("/api/projects/{$this->project->id}/images", [
            'images' => [$file],
        ]);

    $response->assertSuccessful();

    expect(ProjectImage::where('project_id', $this->project->id)->count())->toBe(1);

    $image = ProjectImage::first();
    expect($image->original_filename)->toBe('test-image.jpg');
    expect($image->user_id)->toBe($this->user->id);
    expect($image->project_id)->toBe($this->project->id);
    expect($image->processing_status)->toBe('pending');
});

test('can upload multiple images to project', function (): void {
    $files = [
        UploadedFile::fake()->image('image1.jpg', 800, 600),
        UploadedFile::fake()->image('image2.png', 1200, 800),
        UploadedFile::fake()->image('image3.webp', 600, 400),
    ];

    $response = $this->actingAs($this->user)
        ->post("/api/projects/{$this->project->id}/images", [
            'images' => $files,
        ]);

    $response->assertSuccessful();

    expect(ProjectImage::where('project_id', $this->project->id)->count())->toBe(3);

    $images = ProjectImage::where('project_id', $this->project->id)->get();
    expect($images->pluck('original_filename')->toArray())
        ->toContain('image1.jpg', 'image2.png', 'image3.webp');
});

test('validates file types during upload', function (): void {
    $invalidFile = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/images", [
            'images' => [$invalidFile],
        ]);

    $response->assertUnprocessable();
    expect(ProjectImage::count())->toBe(0);
});

test('validates file size limits', function (): void {
    $largeFile = UploadedFile::fake()->create('huge-image.jpg', 60000); // 60MB

    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/images", [
            'images' => [$largeFile],
        ]);

    $response->assertUnprocessable();
    expect(ProjectImage::count())->toBe(0);
});

test('requires authentication for image upload', function (): void {
    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson("/api/projects/{$this->project->id}/images", [
        'images' => [$file],
    ]);

    $response->assertUnauthorized();
});

test('validates user can only upload to their own projects', function (): void {
    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$otherProject->id}/images", [
            'images' => [$file],
        ]);

    $response->assertForbidden();
    expect(ProjectImage::count())->toBe(0);
});

test('stores image metadata correctly', function (): void {
    $file = UploadedFile::fake()->image('test-image.jpg', 1200, 800);

    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/images", [
            'images' => [$file],
            'title' => 'Test Image Title',
            'description' => 'Test description for image',
            'tags' => ['inspiration', 'branding'],
        ]);

    $response->assertSuccessful();

    $image = ProjectImage::first();
    expect($image->title)->toBe('Test Image Title');
    expect($image->description)->toBe('Test description for image');
    expect($image->tags)->toBe(['inspiration', 'branding']);
    // Width/height will be null until background job processes the image
    expect($image->width)->toBeNull();
    expect($image->height)->toBeNull();
});

test('generates unique uuid for each image', function (): void {
    $files = [
        UploadedFile::fake()->image('image1.jpg'),
        UploadedFile::fake()->image('image2.jpg'),
    ];

    $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/images", [
            'images' => $files,
        ]);

    $images = ProjectImage::all();
    expect($images->count())->toBe(2);
    expect($images->pluck('uuid')->unique()->count())->toBe(2);
    expect($images->first()->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('returns proper error for missing images array', function (): void {
    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/images", [
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['images']);
});

test('processes image upload with background job', function (): void {
    Queue::fake();

    $file = UploadedFile::fake()->image('test.jpg', 800, 600);

    $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/images", [
            'images' => [$file],
        ]);

    Queue::assertPushed(\App\Jobs\ProcessUploadedImageJob::class);

    $image = ProjectImage::first();
    expect($image->processing_status)->toBe('pending');
});
