<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use App\Services\ImageContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new ImageContextService;
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
});

describe('ImageContextService', function (): void {
    test('returns null when project has no images', function (): void {
        $context = $this->service->getContextForProject($this->project->id);

        expect($context)->toBeNull();
    });

    test('returns null when project only has pending images', function (): void {
        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'River inspiration',
            'processing_status' => 'pending',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)->toBeNull();
    });

    test('returns null when project images have no titles', function (): void {
        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => null,
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)->toBeNull();
    });

    test('formats single image with title only', function (): void {
        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'River inspiration',
            'description' => null,
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)
            ->toContain('IMPORTANT VISUAL INSPIRATION')
            ->toContain('The user has carefully selected 1 inspiration image that should STRONGLY influence')
            ->toContain('• River inspiration')
            ->toContain('You MUST incorporate themes, imagery, and concepts from these inspirations');
    });

    test('formats single image with title and description', function (): void {
        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'River inspiration',
            'description' => 'A flowing river through mountains',
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)
            ->toContain('IMPORTANT VISUAL INSPIRATION')
            ->toContain('• River inspiration: A flowing river through mountains')
            ->toContain('You MUST incorporate themes, imagery, and concepts');
    });

    test('formats multiple images with titles and descriptions', function (): void {
        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'River inspiration',
            'description' => 'A flowing river with smooth stones',
            'processing_status' => 'completed',
        ]);

        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Mountain theme',
            'description' => 'Rocky peaks with snow',
            'processing_status' => 'completed',
        ]);

        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Forest vibes',
            'description' => null,
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)
            ->toContain('3 inspiration images that should STRONGLY influence')
            ->toContain('• River inspiration: A flowing river with smooth stones')
            ->toContain('• Mountain theme: Rocky peaks with snow')
            ->toContain('• Forest vibes')
            ->not->toContain('Forest vibes:') // No colon when no description
            ->toContain('You MUST incorporate themes, imagery, and concepts');
    });

    test('orders images by most recent first', function (): void {
        $oldImage = ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Old inspiration',
            'processing_status' => 'completed',
            'created_at' => now()->subDays(5),
        ]);

        $newImage = ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'New inspiration',
            'processing_status' => 'completed',
            'created_at' => now(),
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        // New image should appear before old image in the formatted string
        $newPosition = strpos($context, 'New inspiration');
        $oldPosition = strpos($context, 'Old inspiration');

        expect($newPosition)->toBeLessThan($oldPosition);
    });

    test('ignores images from other projects', function (): void {
        $otherProject = Project::factory()->create(['user_id' => $this->user->id]);

        ProjectImage::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $this->user->id,
            'title' => 'Other project image',
            'processing_status' => 'completed',
        ]);

        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'My project image',
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)
            ->toContain('My project image')
            ->not->toContain('Other project image');
    });

    test('getContextForProjectModel works with Project instance', function (): void {
        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Test image',
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProjectModel($this->project);

        expect($context)
            ->toContain('Test image')
            ->toContain('IMPORTANT VISUAL INSPIRATION');
    });

    test('uses correct plural for multiple images', function (): void {
        ProjectImage::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Test image',
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)->toContain('2 inspiration images that should STRONGLY influence');
    });

    test('handles long descriptions gracefully', function (): void {
        $longDescription = str_repeat('This is a very long description. ', 20);

        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Complex image',
            'description' => $longDescription,
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)
            ->toContain('Complex image')
            ->toContain($longDescription);
    });
});
