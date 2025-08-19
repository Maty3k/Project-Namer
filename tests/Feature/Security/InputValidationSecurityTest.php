<?php

declare(strict_types=1);

use Livewire\Volt\Volt;

describe('Input Validation & XSS Prevention Security Tests', function (): void {
    test('business description validates required field', function (): void {
        $component = Volt::test('name-generator');

        $component->set('businessDescription', '')
            ->call('generateNames');

        $component->assertHasErrors(['businessDescription']);
        expect($component->get('generatedNames'))->toHaveCount(0);
    });

    test('business description validates maximum length', function (): void {
        $component = Volt::test('name-generator');
        $longDescription = str_repeat('x', 2001);

        $component->set('businessDescription', $longDescription)
            ->call('generateNames');

        $component->assertHasErrors(['businessDescription']);
        expect($component->get('generatedNames'))->toHaveCount(0);
    });

    test('business description accepts valid input within limits', function (): void {
        $component = Volt::test('name-generator');
        $validDescription = str_repeat('x', 2000);

        $component->set('businessDescription', $validDescription)
            ->call('generateNames');

        $component->assertHasNoErrors(['businessDescription']);
    });

    test('mode validates against allowed values only', function (): void {
        $component = Volt::test('name-generator');

        $component->set('businessDescription', 'valid business')
            ->set('mode', 'invalid-mode')
            ->call('generateNames');

        $component->assertHasErrors(['mode']);
        expect($component->get('generatedNames'))->toHaveCount(0);
    });

    test('mode accepts all valid generation modes', function (): void {
        $component = Volt::test('name-generator');
        $validModes = ['creative', 'professional', 'brandable', 'tech-focused'];

        foreach ($validModes as $mode) {
            $component->set('businessDescription', 'test business')
                ->set('mode', $mode)
                ->call('generateNames');

            $component->assertHasNoErrors(['mode']);
        }
    });

    test('input sanitizes HTML script tags to prevent XSS', function (): void {
        $component = Volt::test('name-generator');
        $maliciousInput = 'coffee shop<script>alert("xss")</script>';

        $component->set('businessDescription', $maliciousInput)
            ->call('generateNames');

        // Verify the script tag is not executed or stored
        $storedDescription = $component->get('businessDescription');
        expect($storedDescription)->not->toContain('<script>');
        expect($storedDescription)->not->toContain('alert(');
    });

    test('input sanitizes HTML img tags with onerror attribute', function (): void {
        $component = Volt::test('name-generator');
        $maliciousInput = 'business<img src="x" onerror="alert(\'xss\')">';

        $component->set('businessDescription', $maliciousInput)
            ->call('generateNames');

        $storedDescription = $component->get('businessDescription');
        expect($storedDescription)->not->toContain('onerror');
        expect($storedDescription)->not->toContain('alert(');
    });

    test('input prevents JavaScript protocol injection', function (): void {
        $component = Volt::test('name-generator');
        $maliciousInput = 'business javascript:alert("xss")';

        $component->set('businessDescription', $maliciousInput)
            ->call('generateNames');

        $storedDescription = $component->get('businessDescription');
        expect($storedDescription)->not->toContain('javascript:');
    });

    test('input handles SQL injection attempts safely', function (): void {
        $component = Volt::test('name-generator');
        $maliciousInput = "'; DROP TABLE users; --";

        $component->set('businessDescription', $maliciousInput)
            ->call('generateNames');

        // Should not cause database errors and should be treated as normal text
        $component->assertHasNoErrors(['businessDescription']);
    });

    test('input sanitizes iframe tags to prevent clickjacking', function (): void {
        $component = Volt::test('name-generator');
        $maliciousInput = 'business<iframe src="malicious.com"></iframe>';

        $component->set('businessDescription', $maliciousInput)
            ->call('generateNames');

        $storedDescription = $component->get('businessDescription');
        expect($storedDescription)->not->toContain('<iframe');
        expect($storedDescription)->not->toContain('malicious.com');
    });

    test('input handles null byte injection attempts', function (): void {
        $component = Volt::test('name-generator');
        $maliciousInput = "business\0<script>alert('xss')</script>";

        $component->set('businessDescription', $maliciousInput)
            ->call('generateNames');

        $storedDescription = $component->get('businessDescription');
        expect($storedDescription)->not->toContain("\0");
        expect($storedDescription)->not->toContain('<script>');
    });

    test('deep thinking parameter only accepts boolean values', function (): void {
        $component = Volt::test('name-generator');

        // Test valid boolean values
        $component->set('deepThinking', true);
        expect($component->get('deepThinking'))->toBeTrue();

        $component->set('deepThinking', false);
        expect($component->get('deepThinking'))->toBeFalse();
    });

    test('component properties cannot be manipulated to bypass validation', function (): void {
        $component = Volt::test('name-generator');

        // Try to bypass validation by setting rate limit time directly
        $component->set('lastApiCallTime', 0);
        $component->set('businessDescription', '');

        $component->call('generateNames');

        // Should still fail validation despite manipulation attempt
        $component->assertHasErrors(['businessDescription']);
    });

    test('error messages do not leak sensitive information', function (): void {
        $component = Volt::test('name-generator');

        // Trigger various error conditions
        $component->set('businessDescription', str_repeat('x', 3000))
            ->call('generateNames');

        $errorMessage = $component->get('errorMessage');

        // Verify error messages don't contain system paths, internal details, etc.
        expect($errorMessage)->not->toContain('/var/');
        expect($errorMessage)->not->toContain('/home/');
        expect($errorMessage)->not->toContain('database');
        expect($errorMessage)->not->toContain('mysql');
        expect($errorMessage)->not->toContain('api_key');
    });

    test('component does not expose internal state through public properties', function (): void {
        $component = Volt::test('name-generator');

        // Verify sensitive properties are not directly accessible
        $component->assertDontSeeText('OpenAINameService');
        $component->assertDontSeeText('DomainCheckService');
        $component->assertDontSeeText('api_key');
    });

    test('form prevents CSRF attacks through Laravel protection', function (): void {
        // This is handled by Laravel's built-in CSRF protection
        // Test that forms include CSRF tokens
        $this->get('/')->assertSee('csrf-token');
    });

    test('input validation prevents ReDoS attacks through pattern complexity limits', function (): void {
        $component = Volt::test('name-generator');

        // Attempt ReDoS with catastrophic backtracking pattern
        $redosInput = str_repeat('a', 1000).str_repeat('b', 1000).'c';

        $startTime = microtime(true);
        $component->set('businessDescription', $redosInput)
            ->call('generateNames');
        $endTime = microtime(true);

        // Should complete quickly without getting stuck in regex backtracking
        expect($endTime - $startTime)->toBeLessThan(1.0); // Less than 1 second
    });

    test('unicode and special characters are handled safely', function (): void {
        $component = Volt::test('name-generator');

        $unicodeInput = 'café 北京 🚀 ñoño';
        $component->set('businessDescription', $unicodeInput)
            ->call('generateNames');

        expect($component->get('businessDescription'))->toBe($unicodeInput);
        $component->assertHasNoErrors(['businessDescription']);
    });

    test('input length calculations account for multibyte characters', function (): void {
        // Test that Laravel validation correctly handles multibyte characters
        $validator = validator(['text' => str_repeat('北', 2000)], ['text' => 'max:2000']);
        expect($validator->fails())->toBeFalse();

        $validator = validator(['text' => str_repeat('北', 2001)], ['text' => 'max:2000']);
        expect($validator->fails())->toBeTrue();

        // Test that the component uses the same validation
        $component = Volt::test('name-generator');
        $component->set('businessDescription', str_repeat('北', 2001))
            ->call('generateNames');

        // Verify validation was triggered by checking generated names remain empty
        expect($component->get('generatedNames'))->toHaveCount(0);
    });
})->group('Security', 'InputValidation', 'XSS');
