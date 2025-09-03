<?php

declare(strict_types=1);

use App\Services\AI\AIAccessibilityService;

it('can generate progress announcements', function (): void {
    $service = new AIAccessibilityService;

    $startingMessage = $service->generateProgressAnnouncement('starting', null, 'GPT-4');
    expect($startingMessage)->toContain('AI name generation started')
        ->and($startingMessage)->toContain('GPT-4')
        ->and($startingMessage)->toContain('Please wait');

    $processingMessage = $service->generateProgressAnnouncement('processing', 50, 'GPT-4');
    expect($processingMessage)->toContain('50% complete');

    $completedMessage = $service->generateProgressAnnouncement('completed', null, 'GPT-4');
    expect($completedMessage)->toContain('completed successfully')
        ->and($completedMessage)->toContain('Results are now available');

    $errorMessage = $service->generateProgressAnnouncement('error');
    expect($errorMessage)->toContain('failed')
        ->and($errorMessage)->toContain('try again');
});

it('can generate model descriptions for screen readers', function (): void {
    $service = new AIAccessibilityService;

    $model = [
        'name' => 'GPT-4',
        'provider' => 'OpenAI',
        'description' => 'Most capable GPT model',
        'cost_per_1k_tokens' => 0.03,
        'max_tokens' => 150,
        'enabled' => true,
        'is_available' => true,
    ];

    $description = $service->generateModelDescription($model);

    expect($description)->toContain('GPT-4 by OpenAI')
        ->and($description)->toContain('Most capable GPT model')
        ->and($description)->toContain('Cost: 0.0300 dollars per thousand tokens')
        ->and($description)->toContain('Maximum tokens: 150')
        ->and($description)->toContain('Status: available and ready');
});

it('can generate validation messages', function (): void {
    $service = new AIAccessibilityService;

    // Single error
    $singleError = $service->generateValidationMessage('business_description', ['This field is required']);
    expect($singleError)->toBe('Business description has an error: This field is required');

    // Multiple errors
    $multipleErrors = $service->generateValidationMessage('keywords', [
        'At least 3 keywords required',
        'Keywords must be comma separated',
        'Each keyword must be under 50 characters',
    ]);
    expect($multipleErrors)->toContain('has 3 errors')
        ->and($multipleErrors)->toContain('At least 3 keywords required, Keywords must be comma separated, and Each keyword must be under 50 characters');

    // No errors
    $noErrors = $service->generateValidationMessage('business_description', []);
    expect($noErrors)->toBe('');
});

it('can generate character count messages', function (): void {
    $service = new AIAccessibilityService;

    // Within limit
    $withinLimit = $service->generateCharacterCountMessage(100, 500);
    expect($withinLimit)->toBe('100 of 500 characters used.');

    // Near limit
    $nearLimit = $service->generateCharacterCountMessage(495, 500);
    expect($nearLimit)->toBe('5 characters remaining.');

    // At limit
    $atLimit = $service->generateCharacterCountMessage(500, 500);
    expect($atLimit)->toBe('Character limit reached. No more characters allowed.');

    // Exceeded limit
    $exceeded = $service->generateCharacterCountMessage(510, 500);
    expect($exceeded)->toContain('Character limit exceeded by 10 characters')
        ->and($exceeded)->toContain('Please shorten your input');
});

it('can generate results summary', function (): void {
    $service = new AIAccessibilityService;

    // No results
    $noResults = $service->generateResultsSummary([]);
    expect($noResults)->toContain('No name suggestions were generated')
        ->and($noResults)->toContain('try again');

    // Results with domain availability
    $results = [
        ['name' => 'TechFlow', 'domain_available' => true],
        ['name' => 'DataStream', 'domain_available' => false],
        ['name' => 'CloudSync', 'domain_available' => true],
    ];

    $summary = $service->generateResultsSummary($results);
    expect($summary)->toContain('Generated 3 name suggestions')
        ->and($summary)->toContain('2 names have available domains')
        ->and($summary)->toContain('1 names have unavailable domains')
        ->and($summary)->toContain('arrow keys or tab to navigate');
});

it('can generate keyboard navigation instructions', function (): void {
    $service = new AIAccessibilityService;

    $modelInstructions = $service->generateKeyboardInstructions('model_selection');
    expect($modelInstructions)->toContain('arrow keys or tab')
        ->and($modelInstructions)->toContain('space or enter to select')
        ->and($modelInstructions)->toContain('escape to return');

    $resultsInstructions = $service->generateKeyboardInstructions('results');
    expect($resultsInstructions)->toContain('navigate through name suggestions')
        ->and($resultsInstructions)->toContain('enter on a name to copy');

    $defaultInstructions = $service->generateKeyboardInstructions('unknown');
    expect($defaultInstructions)->toContain('tab to navigate')
        ->and($defaultInstructions)->toContain('space or enter to activate');
});

it('can generate error descriptions', function (): void {
    $service = new AIAccessibilityService;

    $networkError = $service->generateErrorDescription('network', 'Connection timeout');
    expect($networkError)->toContain('Network connection error')
        ->and($networkError)->toContain('Details: Connection timeout')
        ->and($networkError)->toContain('try again or contact support');

    $apiLimitError = $service->generateErrorDescription('api_limit');
    expect($apiLimitError)->toContain('API usage limit has been reached')
        ->and($apiLimitError)->toContain('try again or contact support');

    $unknownError = $service->generateErrorDescription('unknown_type');
    expect($unknownError)->toContain('An unexpected error occurred');
});

it('can generate loading messages', function (): void {
    $service = new AIAccessibilityService;

    $initMessage = $service->generateLoadingMessage('initializing');
    expect($initMessage)->toContain('Initializing AI name generation');

    $connectingMessage = $service->generateLoadingMessage('connecting');
    expect($connectingMessage)->toContain('Connecting to AI service');

    $processingMessage = $service->generateLoadingMessage('processing');
    expect($processingMessage)->toContain('AI is analyzing your input');

    $defaultMessage = $service->generateLoadingMessage('unknown');
    expect($defaultMessage)->toContain('Processing your request');
});

it('can get element attributes for accessibility', function (): void {
    $service = new AIAccessibilityService;

    // Model card attributes
    $modelCardAttrs = $service->getElementAttributes('model_card', [
        'selected' => true,
        'description_id' => 'model-desc-1',
    ]);
    expect($modelCardAttrs)->toHaveKey('role', 'button')
        ->and($modelCardAttrs)->toHaveKey('tabindex', '0')
        ->and($modelCardAttrs)->toHaveKey('aria-pressed', 'true')
        ->and($modelCardAttrs)->toHaveKey('aria-describedby', 'model-desc-1');

    // Generation button attributes
    $buttonAttrs = $service->getElementAttributes('generation_button', [
        'loading' => true,
        'description_id' => 'button-help',
    ]);
    expect($buttonAttrs)->toHaveKey('type', 'button')
        ->and($buttonAttrs)->toHaveKey('aria-busy', 'true')
        ->and($buttonAttrs)->toHaveKey('aria-disabled', 'true')
        ->and($buttonAttrs)->toHaveKey('aria-describedby', 'button-help');

    // Progress bar attributes
    $progressAttrs = $service->getElementAttributes('progress_bar', [
        'progress' => 75,
        'label' => 'Name generation progress',
    ]);
    expect($progressAttrs)->toHaveKey('role', 'progressbar')
        ->and($progressAttrs)->toHaveKey('aria-valuemin', '0')
        ->and($progressAttrs)->toHaveKey('aria-valuemax', '100')
        ->and($progressAttrs)->toHaveKey('aria-valuenow', '75')
        ->and($progressAttrs)->toHaveKey('aria-label', 'Name generation progress');

    // Error message attributes
    $errorAttrs = $service->getElementAttributes('error_message');
    expect($errorAttrs)->toHaveKey('role', 'alert')
        ->and($errorAttrs)->toHaveKey('aria-live', 'assertive')
        ->and($errorAttrs)->toHaveKey('aria-atomic', 'true');
});

it('can generate skip links', function (): void {
    $service = new AIAccessibilityService;

    $sections = [
        'main-content' => 'main content',
        'ai-input' => 'name generator input',
        'ai-results' => 'generated results',
    ];

    $skipLinks = $service->generateSkipLinks($sections);

    expect($skipLinks)->toHaveCount(3);
    expect($skipLinks[0])->toHaveKey('href', '#main-content')
        ->and($skipLinks[0])->toHaveKey('label', 'Skip to main content')
        ->and($skipLinks[0])->toHaveKey('class', 'ai-skip-link');
});

it('can validate color contrast ratios', function (): void {
    $service = new AIAccessibilityService;

    // Good contrast (white text on dark background)
    $goodContrast = $service->validateColorContrast('#ffffff', '#000000');
    expect($goodContrast['ratio'])->toBeGreaterThan(7.0)
        ->and($goodContrast['aa_normal'])->toBeTrue()
        ->and($goodContrast['aaa_normal'])->toBeTrue()
        ->and($goodContrast['recommendation'])->toContain('Excellent');

    // Poor contrast (light gray on white)
    $poorContrast = $service->validateColorContrast('#cccccc', '#ffffff');
    expect($poorContrast['ratio'])->toBeLessThan(3.0)
        ->and($poorContrast['aa_normal'])->toBeFalse()
        ->and($poorContrast['recommendation'])->toContain('Poor contrast');

    // Acceptable contrast (dark gray on white)
    $acceptableContrast = $service->validateColorContrast('#666666', '#ffffff');
    expect($acceptableContrast['aa_normal'])->toBeTrue()
        ->and($acceptableContrast['recommendation'])->toContain('Good contrast');
});

it('can handle hex color formats', function (): void {
    $service = new AIAccessibilityService;

    // Test 3-character hex
    $shortHex = $service->validateColorContrast('#000', '#fff');
    expect($shortHex['ratio'])->toBeGreaterThan(7.0);

    // Test 6-character hex with #
    $longHex = $service->validateColorContrast('#000000', '#ffffff');
    expect($longHex['ratio'])->toBeGreaterThan(7.0);

    // Both should give same result
    expect($shortHex['ratio'])->toBe($longHex['ratio']);
});

it('filters out null values from element attributes', function (): void {
    $service = new AIAccessibilityService;

    $attrs = $service->getElementAttributes('model_card', [
        'selected' => false,
        'description_id' => null, // This should be filtered out
    ]);

    expect($attrs)->not->toHaveKey('aria-describedby')
        ->and($attrs['aria-pressed'])->toBe('false');
});
