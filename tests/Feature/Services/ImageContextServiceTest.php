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
            ->toContain('CRITICAL: VISUAL INSPIRATION PROVIDED')
            ->toContain('MANDATORY REQUIREMENT: The user has uploaded 1 inspiration image')
            ->toContain('🔹 **River inspiration**')
            ->toContain('ALL generated names MUST be DIRECTLY inspired by these images')
            ->toContain('YOUR NAMING INSTRUCTIONS');
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
            ->toContain('CRITICAL: VISUAL INSPIRATION PROVIDED')
            ->toContain('🔹 **River inspiration**')
            ->toContain('Context: A flowing river through mountains')
            ->toContain('MANDATORY creative constraints');
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
            ->toContain('uploaded 3 inspiration images')
            ->toContain('🔹 **River inspiration**')
            ->toContain('Context: A flowing river with smooth stones')
            ->toContain('🔹 **Mountain theme**')
            ->toContain('Context: Rocky peaks with snow')
            ->toContain('🔹 **Forest vibes**')
            ->toContain('MANDATORY creative constraints');
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
        $newPosition = strpos($context, '**New inspiration**');
        $oldPosition = strpos($context, '**Old inspiration**');

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
            ->toContain('**My project image**')
            ->not->toContain('**Other project image**');
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
            ->toContain('**Test image**')
            ->toContain('CRITICAL: VISUAL INSPIRATION PROVIDED');
    });

    test('uses correct plural for multiple images', function (): void {
        ProjectImage::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Test image',
            'processing_status' => 'completed',
        ]);

        $context = $this->service->getContextForProject($this->project->id);

        expect($context)->toContain('uploaded 2 inspiration images');
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
            ->toContain('**Complex image**')
            ->toContain($longDescription);
    });
});
