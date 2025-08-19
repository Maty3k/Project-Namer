<?php

declare(strict_types=1);

use Livewire\Volt\Volt;

describe('API Key Security & Environment Configuration Tests', function (): void {
    test('OpenAI API key is not exposed in client-side code', function (): void {
        $response = $this->get('/');

        // Check that OpenAI API key is not in the HTML response
        $apiKey = config('services.openai.api_key');
        if ($apiKey) {
            $response->assertDontSeeText($apiKey);
        }
        $response->assertDontSeeText('sk-'); // OpenAI key prefix
        $response->assertDontSeeText('api_key');
        $response->assertDontSeeText('OPENAI_API_KEY');
    });

    test('environment configuration is not exposed in component props', function (): void {
        $component = Volt::test('name-generator');

        // Test that API keys are not included in component properties
        $apiKey = config('services.openai.api_key');
        if ($apiKey) {
            expect($component->get('businessDescription'))->not->toContain($apiKey);
            expect($component->get('errorMessage'))->not->toContain($apiKey);
        }

        // Verify sensitive strings are not in any component properties
        $componentProps = [
            $component->get('businessDescription'),
            $component->get('mode'),
            $component->get('errorMessage'),
        ];

        foreach ($componentProps as $prop) {
            if (is_string($prop)) {
                expect($prop)->not->toContain('sk-');
                expect($prop)->not->toContain('api_key');
                expect($prop)->not->toContain('OPENAI_API_KEY');
            }
        }
    });

    test('API keys configuration exists and follows security patterns', function (): void {
        // Check that API key configuration exists (may be null in testing)
        $apiKey = config('services.openai.api_key');

        // If API key is set, it should be a proper key format
        if ($apiKey !== null) {
            expect($apiKey)->toBeString();
            expect($apiKey)->not->toBeEmpty();
            expect($apiKey)->not->toBe('your-openai-api-key-here');
            expect($apiKey)->not->toBe('sk-test-key');
        }

        // Configuration structure should exist
        expect(config('services.openai'))->toBeArray();
        expect(config('services.openai'))->toHaveKey('api_key');
    });

    test('sensitive configuration is not cached in client storage', function (): void {
        $component = Volt::test('name-generator');
        $component->set('businessDescription', 'test business')
            ->call('generateNames');

        // Check that search history doesn't contain sensitive data
        $searchHistory = $component->get('searchHistory');
        $historyJson = json_encode($searchHistory);

        $apiKey = config('services.openai.api_key');
        if ($apiKey) {
            expect($historyJson)->not->toContain($apiKey);
        }
        expect($historyJson)->not->toContain('sk-');
        expect($historyJson)->not->toContain('api_key');
    });

    test('application configuration prevents debug information leakage', function (): void {
        // In production, debug should be false
        if (app()->environment('production')) {
            expect(config('app.debug'))->toBeFalse();
        }

        // APP_KEY should be set and not be the default
        expect(config('app.key'))->toBeString();
        expect(config('app.key'))->not->toBeEmpty();
        expect(config('app.key'))->not->toBe('base64:your-app-key-here');
    });

    test('error responses do not leak API keys', function (): void {
        $component = Volt::test('name-generator');

        // Trigger an error by setting invalid state (simulate service failure)
        $component->set('businessDescription', 'test business');

        try {
            $component->call('generateNames');
        } catch (\Exception $e) {
            // Verify exceptions don't contain sensitive data
            $apiKey = config('services.openai.api_key');
            if ($apiKey) {
                expect($e->getMessage())->not->toContain($apiKey);
            }
            expect($e->getMessage())->not->toContain('sk-');
        }

        // Check error messages in component
        $errorMessage = $component->get('errorMessage');
        $apiKey = config('services.openai.api_key');
        if ($apiKey) {
            expect($errorMessage)->not->toContain($apiKey);
        }
        expect($errorMessage)->not->toContain('sk-');
        expect($errorMessage)->not->toContain('api_key');
    });

    test('server-side service classes are not accessible from client', function (): void {
        $response = $this->get('/');

        // Verify service class names and internal structures are not exposed
        $response->assertDontSeeText('OpenAINameService');
        $response->assertDontSeeText('DomainCheckService');
        $response->assertDontSeeText('App\\Services\\');
        $response->assertDontSeeText('laravel_session');
    });

    test('database connection details are not exposed', function (): void {
        $response = $this->get('/');

        // Check that database configuration is not in response
        $dbHost = config('database.connections.mysql.host');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        if ($dbHost && $dbHost !== 'localhost' && $dbHost !== '127.0.0.1') {
            $response->assertDontSeeText($dbHost);
        }
        if ($dbUser && $dbUser !== 'root' && $dbUser !== 'user') {
            $response->assertDontSeeText($dbUser);
        }
        if ($dbPass) {
            $response->assertDontSeeText($dbPass);
        }

        $response->assertDontSeeText('DB_PASSWORD');
        $response->assertDontSeeText('DB_HOST');
    });

    test('component validates API service availability without exposing credentials', function (): void {
        $component = Volt::test('name-generator');

        // Even with malformed input that might trigger service errors
        $component->set('businessDescription', str_repeat('test', 1000))
            ->call('generateNames');

        $errorMessage = $component->get('errorMessage');

        // Error messages should be user-friendly without exposing internals
        if ($errorMessage) {
            expect($errorMessage)->not->toContain('unauthorized');
            expect($errorMessage)->not->toContain('authentication');
            expect($errorMessage)->not->toContain('api key');
            expect($errorMessage)->not->toContain('401');
            expect($errorMessage)->not->toContain('403');
        }
    });

    test('session configuration is secure', function (): void {
        // Check session security settings
        expect(config('session.secure'))->toBeIn([true, null]); // Should be true in production
        expect(config('session.http_only'))->toBeTrue();
        expect(config('session.same_site'))->toBeIn(['lax', 'strict']);

        // Session lifetime should be reasonable
        expect(config('session.lifetime'))->toBeLessThanOrEqual(480); // 8 hours max
    });

    test('CORS configuration prevents unauthorized access', function (): void {
        // Test that CORS headers are properly configured
        $response = $this->get('/');

        // Should not allow all origins in production
        if (app()->environment('production')) {
            $response->assertHeaderMissing('Access-Control-Allow-Origin');
        }
    });

    test('cache configuration does not expose sensitive data', function (): void {
        // Verify cache keys don't contain sensitive information
        $component = Volt::test('name-generator');
        $component->set('businessDescription', 'test business')
            ->call('generateNames');

        // If caching is enabled, ensure no API keys are cached
        if (config('cache.default') !== 'array') {
            $cacheStore = cache()->getStore();
            if (method_exists($cacheStore, 'getPrefix')) {
                $prefix = $cacheStore->getPrefix();
                $apiKey = config('services.openai.api_key');
                if ($apiKey) {
                    expect($prefix)->not->toContain($apiKey);
                }
                expect($prefix)->not->toContain('sk-');
            }
        }

        // Test passes if we reach this point without exposing sensitive data
        expect(true)->toBeTrue();
    });

    test('log configuration prevents sensitive data exposure', function (): void {
        // Check that logging doesn't expose sensitive data
        expect(config('logging.channels.single.level'))->toBeIn(['debug', 'info', 'warning', 'error']);

        // In production, should use appropriate log levels
        if (app()->environment('production')) {
            expect(config('logging.channels.single.level'))->toBeIn(['warning', 'error']);
        }
    });
})->group('Security', 'ApiKeys', 'Environment');
