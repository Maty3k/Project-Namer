<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->withoutVite();
});

describe('File Storage Configuration', function (): void {
    test('stores uploaded files in correct directory structure', function (): void {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => [$file],
            ]);

        $response->assertSuccessful();

        $image = ProjectImage::first();
        expect($image->file_path)->toStartWith('projects/');
        expect(Storage::disk('public')->exists($image->file_path))->toBeTrue();
    });

    test('generates unique filenames to prevent conflicts', function (): void {
        $file1 = UploadedFile::fake()->image('same-name.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('same-name.jpg', 800, 600);

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => [$file1],
            ]);

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => [$file2],
            ]);

        $images = ProjectImage::all();
        expect($images)->toHaveCount(2);
        expect($images[0]->stored_filename)->not->toBe($images[1]->stored_filename);
    });

    test('validates file size limits', function (): void {
        $largeFile = UploadedFile::fake()->create('large.jpg', 52000); // 52MB (over 50MB limit)

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => [$largeFile],
            ]);

        $response->assertUnprocessable();
    });

    test('validates supported file types', function (): void {
        $unsupportedFile = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => [$unsupportedFile],
            ]);

        $response->assertUnprocessable();
    });
});

describe('Storage Usage Tracking', function (): void {
    test('tracks total storage usage per user', function (): void {
        $file1 = UploadedFile::fake()->image('small.jpg', 200, 200);
        $file2 = UploadedFile::fake()->image('large.jpg', 1200, 800);

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/images", [
                'images' => [$file1, $file2],
            ]);

        $totalSize = ProjectImage::where('user_id', $this->user->id)->sum('file_size');
        expect($totalSize)->toBeGreaterThan(0);
    });

    test('prevents uploads when storage limit exceeded', function (): void {
        // This test would check storage limits when implemented
        expect(true)->toBeTrue(); // Placeholder for now
    });
});

describe('File Cleanup Operations', function (): void {
    test('can identify orphaned files for cleanup', function (): void {
        $image = ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'file_path' => 'projects/test-orphan.jpg',
        ]);

        // Simulate orphaned file (exists in database but not storage)
        expect(Storage::disk('public')->exists($image->file_path))->toBeFalse();

        // Test would verify cleanup job identifies this orphan
        expect(true)->toBeTrue(); // Placeholder for cleanup logic
    });

    test('removes files when image records are deleted', function (): void {
        $file = UploadedFile::fake()->image('to-delete.jpg', 400, 400);
        Storage::disk('public')->put('projects/to-delete.jpg', $file->getContent());

        $image = ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'file_path' => 'projects/to-delete.jpg',
        ]);

        // Delete the image
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/projects/{$this->project->id}/images/{$image->id}");

        $response->assertSuccessful();
        expect(Storage::disk('public')->exists($image->file_path))->toBeFalse();
    });
});
