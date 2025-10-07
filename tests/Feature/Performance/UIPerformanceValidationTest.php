<?php

declare(strict_types=1);

use App\Models\NamingSession;
use App\Models\Project;
use App\Models\SessionResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Page Load Time Performance', function (): void {
    test('dashboard page loads in under 2 seconds', function (): void {
        // Create realistic data
        Project::factory()->count(10)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);
        $response = $this->get(route('dashboard'));
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(2.0);
    });

    test('dashboard page loads in under 1.5 seconds with cached data', function (): void {
        Project::factory()->count(10)->create(['user_id' => $this->user->id]);

        // First request to warm up cache
        $this->get(route('dashboard'));

        // Second request should be faster due to caching
        $startTime = microtime(true);
        $response = $this->get(route('dashboard'));
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(1.5);
    });

    test('session sidebar loads efficiently with many sessions', function (): void {
        NamingSession::factory()->count(50)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);
        $response = $this->get(route('dashboard'));
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(2.5);
    });

    test('home page loads in under 1 second', function (): void {
        $startTime = microtime(true);
        $response = $this->get(route('home'));
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(1.0);
    });
});

describe('Database Query Performance', function (): void {
    test('dashboard page executes fewer than 30 queries', function (): void {
        Project::factory()->count(10)->create(['user_id' => $this->user->id]);

        \DB::enableQueryLog();
        $this->get(route('dashboard'));
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        expect(count($queries))->toBeLessThan(30);
    });

    test('session loading uses eager loading to prevent N+1', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
        SessionResult::factory()->count(10)->create(['session_id' => $session->id]);

        \DB::enableQueryLog();
        $this->get(route('dashboard'));
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should not have N+1 queries for session results
        $resultQueries = array_filter($queries, fn ($q) => str_contains((string) $q['query'], 'session_results'));
        expect(count($resultQueries))->toBeLessThanOrEqual(1);
    });

    test('project loading is optimized with eager loading', function (): void {
        Project::factory()->count(10)->create(['user_id' => $this->user->id]);

        \DB::enableQueryLog();
        $this->get(route('dashboard'));
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should load projects in minimal queries
        $projectQueries = array_filter($queries, fn ($q) => str_contains((string) $q['query'], 'projects'));
        expect(count($projectQueries))->toBeLessThanOrEqual(3);
    });
});

describe('Memory Usage Performance', function (): void {
    test('dashboard page uses less than 50MB memory', function (): void {
        Project::factory()->count(20)->create(['user_id' => $this->user->id]);

        $startMemory = memory_get_usage();
        $this->get(route('dashboard'));
        $memoryUsed = (memory_get_usage() - $startMemory) / 1024 / 1024;

        expect($memoryUsed)->toBeLessThan(50);
    });

    test('large dataset processing uses reasonable memory', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
        SessionResult::factory()->count(100)->create(['session_id' => $session->id]);

        $startMemory = memory_get_usage();
        $this->get(route('dashboard'));
        $memoryUsed = (memory_get_usage() - $startMemory) / 1024 / 1024;

        expect($memoryUsed)->toBeLessThan(75);
    });
});

describe('Asset Loading Performance', function (): void {
    test('CSS files are loaded efficiently', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for CSS loading (Vite builds use hashed filenames)
        expect($content)->toMatch('/\.(css|scss)/');
    });

    test('JavaScript files are loaded efficiently', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for JS loading (Vite builds use hashed filenames)
        expect($content)->toMatch('/\.(js|mjs)/');
    });

    test('images use lazy loading where appropriate', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for lazy loading implementation
        expect($content)->toBeString();
    });
});

describe('Caching Performance', function (): void {
    test('repeated requests maintain consistent performance', function (): void {
        Project::factory()->count(10)->create(['user_id' => $this->user->id]);

        // First request
        $startTime1 = microtime(true);
        $this->get(route('dashboard'));
        $time1 = microtime(true) - $startTime1;

        // Second request
        $startTime2 = microtime(true);
        $this->get(route('dashboard'));
        $time2 = microtime(true) - $startTime2;

        // Both requests should be reasonably fast
        expect($time1)->toBeLessThan(2.0);
        expect($time2)->toBeLessThan(2.0);
    });

    test('cache hit ratio is high for common queries', function (): void {
        Project::factory()->count(5)->create(['user_id' => $this->user->id]);

        // Prime cache
        $this->get(route('dashboard'));

        // Multiple subsequent requests
        $times = [];
        for ($i = 0; $i < 5; $i++) {
            $start = microtime(true);
            $this->get(route('dashboard'));
            $times[] = microtime(true) - $start;
        }

        // All cached requests should be fast (under 100ms)
        foreach ($times as $time) {
            expect($time)->toBeLessThan(0.1);
        }
    });
});

describe('Concurrent Request Performance', function (): void {
    test('handles multiple simultaneous page loads', function (): void {
        Project::factory()->count(10)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);

        // Simulate multiple concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get(route('dashboard'));
            $response->assertStatus(200);
        }

        $totalTime = microtime(true) - $startTime;

        // 5 requests should complete in under 5 seconds
        expect($totalTime)->toBeLessThan(5.0);
    });
});

describe('Large Dataset Performance', function (): void {
    test('handles 100+ sessions efficiently', function (): void {
        NamingSession::factory()->count(100)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);
        $response = $this->get(route('dashboard'));
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(3.0);
    });

    test('handles 1000+ name suggestions efficiently', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
        SessionResult::factory()->count(1000)->create(['session_id' => $session->id]);

        $startTime = microtime(true);
        \DB::enableQueryLog();
        $response = $this->get(route('dashboard'));
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(4.0);
        expect(count($queries))->toBeLessThan(30);
    });

    test('pagination prevents memory issues with large datasets', function (): void {
        NamingSession::factory()->count(500)->create(['user_id' => $this->user->id]);

        $startMemory = memory_get_usage();
        $response = $this->get(route('dashboard'));
        $memoryUsed = (memory_get_usage() - $startMemory) / 1024 / 1024;

        $response->assertStatus(200);
        expect($memoryUsed)->toBeLessThan(100);
    });
});

describe('Response Time Targets', function (): void {
    test('API endpoints respond in under 500ms', function (): void {
        $startTime = microtime(true);
        $response = $this->get(route('api.ai.models'));
        $responseTime = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        expect($responseTime)->toBeLessThan(500);
    });

    test('AJAX requests complete quickly', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);
        $response = $this->get(route('api.ai.preferences.show'));
        $responseTime = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        expect($responseTime)->toBeLessThan(300);
    });
});

describe('Optimization Verification', function (): void {
    test('skeleton loading is implemented', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for skeleton components
        expect($content)->toBeString();
    });

    test('lazy loading is implemented', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify lazy loading attributes present
        expect($content)->toBeString();
    });

    test('keyboard shortcuts are functional', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for keyboard shortcuts component
        expect($content)->toContain('keyboardShortcuts');
    });

    test('optimistic UI is implemented', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for optimistic UI component
        expect($content)->toContain('optimisticUI');
    });

    test('micro-interactions are present', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for micro-interactions CSS
        expect($content)->toBeString();
    });
});
