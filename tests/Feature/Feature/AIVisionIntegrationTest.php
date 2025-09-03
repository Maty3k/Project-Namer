<?php

declare(strict_types=1);

use App\Models\GenerationSession;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use App\Services\AIGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    Storage::fake('public');
});

test('name generation incorporates image analysis context', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'test/office.jpg',
        'ai_analysis' => [
            'description' => 'Modern tech office with open workspace',
            'mood' => 'innovative, collaborative, energetic',
            'colors' => ['blue', 'white', 'green'],
            'objects' => ['computers', 'whiteboards', 'plants'],
            'style' => 'contemporary',
            'business_relevance' => 'technology, startups, software development',
        ],
    ]);

    $session = GenerationSession::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'image_context_ids' => [$image->id],
    ]);

    $gptResponse = "1. TechFlow Solutions\n2. InnovateLab\n3. CodeSpace Pro\n4. DevHub Central\n5. WorkFlow Tech";

    Prism::fake([
        TextResponseFake::make()->withText($gptResponse),
    ]);

    $aiService = app(AIGenerationService::class);
    $result = $aiService->generateNamesWithContext('business software platform', $session);

    expect($result)->toBeArray()
        ->and($result['results'])->toBeArray()
        ->and($result['results']['gpt-4']['names'])->toHaveCount(5)
        ->and($result['results']['gpt-4']['names'])->toContain('TechFlow Solutions');
});

test('multiple images provide richer context for name generation', function (): void {
    $images = [
        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'ai_analysis' => [
                'description' => 'Elegant restaurant interior',
                'business_relevance' => 'hospitality, dining, luxury',
            ],
        ]),
        ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'ai_analysis' => [
                'description' => 'Artisanal food preparation',
                'business_relevance' => 'culinary, craftsmanship, quality',
            ],
        ]),
    ];

    $session = GenerationSession::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'image_context_ids' => collect($images)->pluck('id')->toArray(),
    ]);

    Prism::fake([
        TextResponseFake::make()->withText("1. Artisan Table\n2. Craft Kitchen\n3. Elegant Eats"),
    ]);

    $aiService = app(AIGenerationService::class);
    $result = $aiService->generateNamesWithContext('restaurant concept', $session);

    expect($result['results']['gpt-4']['names'])->toContain('Artisan Table');
});

test('image context can be cleared and updated in session', function (): void {
    $image1 = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'ai_analysis' => ['business_relevance' => 'technology'],
    ]);

    $image2 = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'ai_analysis' => ['business_relevance' => 'hospitality'],
    ]);

    $session = GenerationSession::factory()->create([
        'project_id' => $this->project->id,
        'image_context_ids' => [$image1->id],
    ]);

    // Update context to use different image
    $session->update(['image_context_ids' => [$image2->id]]);

    expect($session->fresh()->image_context_ids)->toBe([$image2->id])
        ->and($session->getImageContexts())->toHaveCount(1)
        ->and($session->getImageContexts()->first()->id)->toBe($image2->id);
});

test('handles missing image analysis gracefully', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'ai_analysis' => null, // No analysis yet
    ]);

    $session = GenerationSession::factory()->create([
        'project_id' => $this->project->id,
        'image_context_ids' => [$image->id],
    ]);

    Prism::fake([
        TextResponseFake::make()->withText("1. Generic Name\n2. Standard Business\n3. Default Company"),
    ]);

    $aiService = app(AIGenerationService::class);
    $result = $aiService->generateNamesWithContext('business idea', $session);

    // Should still work but without image context
    expect($result['results']['gpt-4']['names'])->toContain('Generic Name');
});

test('vision analysis results influence name creativity and relevance', function (): void {
    $creativeImage = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'ai_analysis' => [
            'description' => 'Vibrant street art mural',
            'mood' => 'creative, bold, artistic',
            'style' => 'urban, expressive',
            'business_relevance' => 'creative services, design, art',
        ],
    ]);

    $professionalImage = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'ai_analysis' => [
            'description' => 'Corporate boardroom meeting',
            'mood' => 'serious, professional, formal',
            'style' => 'traditional, corporate',
            'business_relevance' => 'consulting, finance, corporate services',
        ],
    ]);

    // Test creative context
    $creativeSession = GenerationSession::factory()->create([
        'project_id' => $this->project->id,
        'image_context_ids' => [$creativeImage->id],
    ]);

    // Test professional context
    $professionalSession = GenerationSession::factory()->create([
        'project_id' => $this->project->id,
        'image_context_ids' => [$professionalImage->id],
    ]);

    Prism::fake([
        TextResponseFake::make()->withText("1. Creative Agency Name\n2. Artistic Studio\n3. Design Lab"),
        TextResponseFake::make()->withText("1. Professional Consulting Name\n2. Corporate Solutions\n3. Business Advisory"),
    ]);

    $aiService = app(AIGenerationService::class);

    // Generate with creative context
    $creativeResult = $aiService->generateNamesWithContext('design agency', $creativeSession);

    // Generate with professional context
    $professionalResult = $aiService->generateNamesWithContext('consulting firm', $professionalSession);

    // Verify both generations worked
    expect($creativeResult['results']['gpt-4']['names'])->toContain('Creative Agency Name');
    expect($professionalResult['results']['gpt-4']['names'])->toContain('Professional Consulting Name');
});
