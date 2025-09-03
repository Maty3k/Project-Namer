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
        'vision_analysis' => ['colors' => ['blue', 'white'], 'style' => 'modern'],
        'influence_score' => 0.85,
    ]);

    expect($context->project_image_id)->toBe($projectImage->id);
    expect($context->generation_session_id)->toBe($generationSession->id);
    expect($context->generation_type)->toBe('name');
    expect($context->vision_analysis)->toBe(['colors' => ['blue', 'white'], 'style' => 'modern']);
    expect((float) $context->influence_score)->toBe(0.85);
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
    $highInfluenceContext = ImageGenerationContext::factory()->create(['influence_score' => 0.8]);
    $lowInfluenceContext = ImageGenerationContext::factory()->create(['influence_score' => 0.5]);

    $results = ImageGenerationContext::highInfluence()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($highInfluenceContext->id);
});

test('scope high influence with custom threshold works', function (): void {
    $context1 = ImageGenerationContext::factory()->create(['influence_score' => 0.9]);
    $context2 = ImageGenerationContext::factory()->create(['influence_score' => 0.8]);
    $context3 = ImageGenerationContext::factory()->create(['influence_score' => 0.7]);

    $results = ImageGenerationContext::highInfluence(0.85)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($context1->id);
});

test('is high influence method returns correct boolean', function (): void {
    $highInfluenceContext = ImageGenerationContext::factory()->create(['influence_score' => 0.8]);
    $lowInfluenceContext = ImageGenerationContext::factory()->create(['influence_score' => 0.6]);

    expect($highInfluenceContext->isHighInfluence())->toBeTrue();
    expect($lowInfluenceContext->isHighInfluence())->toBeFalse();
});

test('get analysis attribute returns correct values', function (): void {
    $context = ImageGenerationContext::factory()->create([
        'vision_analysis' => [
            'colors' => ['blue', 'white'],
            'style' => 'modern',
            'objects' => ['logo', 'text'],
        ],
    ]);

    expect($context->getAnalysisAttribute('colors'))->toBe(['blue', 'white']);
    expect($context->getAnalysisAttribute('style'))->toBe('modern');
    expect($context->getAnalysisAttribute('missing_key'))->toBeNull();
});

test('set influence score clamps values between 0 and 1', function (): void {
    $context = ImageGenerationContext::factory()->create(['influence_score' => 0.5]);

    $context->setInfluenceScore(1.5); // Should clamp to 1.0
    expect((float) $context->refresh()->influence_score)->toBe(1.0);

    $context->setInfluenceScore(-0.2); // Should clamp to 0.0
    expect((float) $context->refresh()->influence_score)->toBe(0.0);

    $context->setInfluenceScore(0.75); // Should stay as is
    expect((float) $context->refresh()->influence_score)->toBe(0.75);
});
