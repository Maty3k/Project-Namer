<?php

declare(strict_types=1);

use App\Models\GenerationSession;
use App\Models\ImageGenerationContext;
use App\Models\ProjectImage;

test('can create image generation context with required attributes', function (): void {
    $projectImage = ProjectImage::factory()->create();
    $generationSession = GenerationSession::factory()->create();

    $context = ImageGenerationContext::factory()->create([
        'project_image_id' => $projectImage->id,
        'generation_session_id' => $generationSession->id,
        'generation_type' => 'name',
        'vision_analysis' => [
            'colors' => ['red', 'blue'],
            'style' => 'modern',
            'objects' => ['logo'],
        ],
        'influence_score' => 0.85,
    ]);

    expect($context->project_image_id)->toBe($projectImage->id);
    expect($context->generation_session_id)->toBe($generationSession->id);
    expect($context->generation_type)->toBe('name');
    expect($context->vision_analysis)->toBe([
        'colors' => ['red', 'blue'],
        'style' => 'modern',
        'objects' => ['logo'],
    ]);
    expect($context->influence_score)->toBe('0.85'); // Stored as decimal string
});

test('belongs to project image relationship works', function (): void {
    $projectImage = ProjectImage::factory()->create();
    $context = ImageGenerationContext::factory()->create(['project_image_id' => $projectImage->id]);

    expect($context->projectImage)->toBeInstanceOf(ProjectImage::class);
    expect($context->projectImage->id)->toBe($projectImage->id);
});

test('belongs to generation session relationship works', function (): void {
    $generationSession = GenerationSession::factory()->create();
    $context = ImageGenerationContext::factory()->create(['generation_session_id' => $generationSession->id]);

    expect($context->generationSession)->toBeInstanceOf(GenerationSession::class);
    expect($context->generationSession->id)->toBe($generationSession->id);
});

test('scope for session filters correctly', function (): void {
    $session1 = GenerationSession::factory()->create();
    $session2 = GenerationSession::factory()->create();
    $context1 = ImageGenerationContext::factory()->create(['generation_session_id' => $session1->id]);
    $context2 = ImageGenerationContext::factory()->create(['generation_session_id' => $session2->id]);

    $results = ImageGenerationContext::forSession($session1->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($context1->id);
});

test('scope for generation type filters correctly', function (): void {
    $nameContext = ImageGenerationContext::factory()->create(['generation_type' => 'name']);
    $logoContext = ImageGenerationContext::factory()->create(['generation_type' => 'logo']);

    $results = ImageGenerationContext::forGenerationType('name')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($nameContext->id);
});

test('scope high influence filters correctly', function (): void {
    $highContext = ImageGenerationContext::factory()->create(['influence_score' => 0.85]);
    $lowContext = ImageGenerationContext::factory()->create(['influence_score' => 0.3]);

    $results = ImageGenerationContext::highInfluence()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($highContext->id);
});

test('is high influence method works correctly', function (): void {
    $highContext = ImageGenerationContext::factory()->create(['influence_score' => 0.85]);
    $lowContext = ImageGenerationContext::factory()->create(['influence_score' => 0.3]);

    expect($highContext->isHighInfluence())->toBeTrue();
    expect($lowContext->isHighInfluence())->toBeFalse();
});

test('get analysis attribute method works', function (): void {
    $context = ImageGenerationContext::factory()->create([
        'vision_analysis' => [
            'colors' => ['red', 'blue'],
            'style' => 'modern',
        ],
    ]);

    expect($context->getAnalysisAttribute('colors'))->toBe(['red', 'blue']);
    expect($context->getAnalysisAttribute('style'))->toBe('modern');
    expect($context->getAnalysisAttribute('missing'))->toBeNull();
});

test('set influence score method works', function (): void {
    $context = ImageGenerationContext::factory()->create(['influence_score' => 0.5]);

    $context->setInfluenceScore(0.85);

    expect($context->refresh()->influence_score)->toBe('0.85'); // Decimal stored as string
});

test('set influence score clamps values', function (): void {
    $context = ImageGenerationContext::factory()->create(['influence_score' => 0.5]);

    $context->setInfluenceScore(1.5); // Should clamp to 1.0
    expect($context->refresh()->influence_score)->toBe('1.00'); // Decimal stored as string

    $context->setInfluenceScore(-0.5); // Should clamp to 0.0
    expect($context->refresh()->influence_score)->toBe('0.00'); // Decimal stored as string
});
