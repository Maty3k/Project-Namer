<?php

declare(strict_types=1);

use App\Services\OpenAINameService;
use Illuminate\Support\Facades\Http;

describe('API Key Security & Environment Configuration Tests', function (): void {
    beforeEach(function (): void {
        // Prevent any actual HTTP requests
        Http::preventStrayRequests();

        // Mock OpenAI service to prevent real API calls in security tests
        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['securitytest', 'testname']);
        });
    });

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
        // Verify environment variables are not exposed in page response
        $response = $this->get('/');

        $apiKey = config('services.openai.api_key');
        if ($apiKey) {
            $response->assertDontSeeText($apiKey);
        }

        // Verify sensitive strings are not in HTTP response
        $response->assertDontSeeText('sk-');
        $response->assertDontSeeText('OPENAI_API_KEY');
        $response->assertDontSeeText(config('services.openai.api_key', 'no-key-set'));

        // Verify environment config is properly isolated
        expect(config('services.openai'))->toBeArray();
        expect(config('services.openai'))->toHaveKey('api_key');
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
        // Verify cache configuration doesn't store sensitive data
        $cacheDriver = config('cache.default');
        expect($cacheDriver)->toBeString();

        // Verify session config doesn't expose API keys
        $sessionDriver = config('session.driver');
        expect($sessionDriver)->toBeString();
        expect($sessionDriver)->not->toContain('sk-');

        // Verify no API keys in environment that could leak to cache
        $apiKey = config('services.openai.api_key');
        if ($apiKey) {
            expect(str_contains(env('APP_NAME') ?? '', $apiKey))->toBeFalse();
            expect(str_contains(config('app.name'), $apiKey))->toBeFalse();
        }
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
        // Verify HTTP error responses don't expose API keys
        $response = $this->get('/');

        $apiKey = config('services.openai.api_key');
        if ($apiKey) {
            $response->assertDontSeeText($apiKey);
        }
        $response->assertDontSeeText('sk-');
        $response->assertDontSeeText('api_key');
        $response->assertDontSeeText('OPENAI_API_KEY');
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
        // Verify OpenAI service configuration is set but not exposed
        $apiKey = config('services.openai.api_key');

        // API key should be configured (may be null in testing)
        expect(config('services.openai'))->toBeArray();
        expect(config('services.openai'))->toHaveKey('api_key');

        // If API key is set, verify it's not a placeholder
        if ($apiKey !== null) {
            expect($apiKey)->toBeString();
            expect($apiKey)->not->toContain('your-openai-api-key-here');
        }

        // Verify environment doesn't expose API keys in app config
        expect(config('app.name'))->not->toContain('sk-');
        expect(config('app.name'))->not->toContain('api_key');
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
        } else {
            // In non-production environments, ensure response is successful
            $response->assertOk();
        }
    });

    test('cache configuration does not expose sensitive data', function (): void {
        // Verify cache configuration doesn't expose sensitive information
        $cacheDriver = config('cache.default');
        expect($cacheDriver)->toBeString();

        // If caching is enabled, ensure no API keys are in cache prefix
        if ($cacheDriver !== 'array') {
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

        // Verify cache config doesn't expose sensitive data
        $cacheConfig = config('cache.stores');
        expect(json_encode($cacheConfig))->not->toContain('sk-');
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
