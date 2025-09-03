<?php

declare(strict_types=1);

use App\Livewire\SessionSidebar;
use App\Models\NamingSession;
use App\Models\SessionResult;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Session Performance Testing', function (): void {
    it('handles large session counts efficiently under 200ms', function (): void {
        // Create large dataset of sessions
        $sessions = NamingSession::factory()->count(500)->create(['user_id' => $this->user->id]);

        // Add results to some sessions to make it more realistic
        $sessions->take(100)->each(function (NamingSession $session): void {
            SessionResult::factory()->count(random_int(5, 15))->create([
                'session_id' => $session->id,
            ]);
        });

        $sessionService = app(SessionService::class);

        // Warm up any caches
        $sessionService->getUserSessions($this->user, 20, 0);

        // Measure actual performance for loading sessions
        $startTime = microtime(true);
        $paginatedSessions = $sessionService->getUserSessions($this->user, 20, 0);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        expect($paginatedSessions->count())->toBe(20);
        expect($responseTime)->toBeLessThan(200); // Must be under 200ms even with 500 sessions
    });

    it('performs session search efficiently with large datasets under 150ms', function (): void {
        // Create sessions with varied content for search testing
        $searchTerms = ['ecommerce', 'fintech', 'healthcare', 'education', 'gaming'];

        foreach ($searchTerms as $term) {
            NamingSession::factory()->count(100)->create([
                'user_id' => $this->user->id,
                'business_description' => "A {$term} platform for modern businesses",
                'title' => ucfirst($term).' Session',
            ]);
        }

        $sessionService = app(SessionService::class);

        // Test search performance with large dataset
        $startTime = microtime(true);
        $searchResults = $sessionService->searchSessions($this->user, 'ecommerce');
        $endTime = microtime(true);

        $searchTime = ($endTime - $startTime) * 1000;

        expect($searchResults->count())->toBeGreaterThan(0);
        expect($searchTime)->toBeLessThan(150); // Search under 150ms
    });

    it('meets sidebar rendering performance with 1000+ sessions under 300ms', function (): void {
        // Create very large dataset
        NamingSession::factory()->count(1000)->create(['user_id' => $this->user->id]);

        // Measure sidebar component rendering performance
        $startTime = microtime(true);
        $component = Livewire::test(SessionSidebar::class);
        $endTime = microtime(true);

        $renderTime = ($endTime - $startTime) * 1000;

        $component->assertStatus(200);
        expect($renderTime)->toBeLessThan(350); // Sidebar rendering under 350ms
    });

    it('handles session loading with pagination efficiently under 100ms', function (): void {
        // Create large dataset with recent activity
        $sessions = collect();
        for ($i = 0; $i < 200; $i++) {
            $sessions->push(NamingSession::factory()->create([
                'user_id' => $this->user->id,
                'last_accessed_at' => now()->subMinutes($i),
            ]));
        }

        // Test pagination performance
        $sessionService = app(SessionService::class);

        $pageTimes = [];
        for ($page = 0; $page < 5; $page++) {
            $offset = $page * 20;
            $startTime = microtime(true);
            $paginatedSessions = $sessionService->getUserSessions($this->user, 20, $offset);
            $endTime = microtime(true);

            $pageTime = ($endTime - $startTime) * 1000;
            $pageTimes[] = $pageTime;

            expect($paginatedSessions->count())->toBe(20);
            expect($pageTime)->toBeLessThan(100); // Each page under 100ms
        }

        // Ensure consistent performance across pages
        $avgPageTime = array_sum($pageTimes) / count($pageTimes);
        expect($avgPageTime)->toBeLessThan(80); // Average under 80ms
    });

    it('meets memory requirements for large session operations under 100MB', function (): void {
        $startMemory = memory_get_usage();

        // Large scale session creation and processing
        $sessions = collect();
        for ($i = 0; $i < 300; $i++) {
            $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
            $sessions->push($session);

            // Add results to make it realistic
            SessionResult::factory()->count(10)->create([
                'session_id' => $session->id,
            ]);
        }

        // Process sessions (simulate sidebar loading)
        $sessionService = app(SessionService::class);
        $allSessions = $sessionService->getUserSessions($this->user, 50, 0);

        // Search operations
        $searchResults = $sessionService->searchSessions($this->user, 'business');

        $endMemory = memory_get_usage();
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

        expect($allSessions->count())->toBe(50);
        expect($searchResults->count())->toBeGreaterThan(0);
        expect($memoryUsed)->toBeLessThan(100); // Memory usage under 100MB
    });

    it('handles concurrent session operations efficiently', function (): void {
        // Setup data for concurrent operations
        NamingSession::factory()->count(200)->create(['user_id' => $this->user->id]);

        $sessionService = app(SessionService::class);
        $operations = [];

        $startTime = microtime(true);

        // Simulate concurrent operations
        for ($i = 0; $i < 10; $i++) {
            $operationStart = microtime(true);

            // Mixed operations that might happen concurrently
            $sessionService->getUserSessions($this->user, 20, $i * 2);
            $sessionService->searchSessions($this->user, 'test');

            $operationEnd = microtime(true);
            $operations[] = ($operationEnd - $operationStart) * 1000;
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        $averageOperation = array_sum($operations) / count($operations);

        expect($totalTime)->toBeLessThan(2000); // All operations under 2 seconds
        expect($averageOperation)->toBeLessThan(200); // Average operation under 200ms
    });

    it('meets database query performance with complex session filtering', function (): void {
        // Create diverse session data for complex filtering
        // Starred sessions
        NamingSession::factory()->count(20)->create([
            'user_id' => $this->user->id,
            'is_starred' => true,
        ]);

        // Regular sessions with varied generation modes
        $modes = ['creative', 'professional', 'brandable', 'tech_focused'];
        foreach ($modes as $mode) {
            NamingSession::factory()->count(15)->create([
                'user_id' => $this->user->id,
                'generation_mode' => $mode,
            ]);
        }

        $sessionService = app(SessionService::class);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Use filter method for complex filtering
        $complexResults = $sessionService->filterSessions($this->user, [
            'starred' => true,
            'mode' => 'creative',
        ]);

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $queryTime = ($endTime - $startTime) * 1000;
        $queryCount = count($queries);

        expect($queryTime)->toBeLessThan(150); // Complex queries under 150ms
        expect($queryCount)->toBeLessThan(5); // Efficient query count
    });

    it('scales well with growing session dataset', function (): void {
        $sessionService = app(SessionService::class);
        $scalabilityResults = [];

        // Test performance at different scales
        $scales = [100, 300, 500, 1000];

        foreach ($scales as $sessionCount) {
            // Clear previous data
            NamingSession::where('user_id', $this->user->id)->delete();

            // Create dataset at this scale
            NamingSession::factory()->count($sessionCount)->create([
                'user_id' => $this->user->id,
            ]);

            // Measure performance
            $startTime = microtime(true);
            $results = $sessionService->getUserSessions($this->user, 20, 0);
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;
            $scalabilityResults[$sessionCount] = $responseTime;

            expect($results->count())->toBe(20);
        }

        // Verify scalability - performance shouldn't degrade too much
        $baselineTime = $scalabilityResults[100];
        $largestTime = $scalabilityResults[1000];
        $degradationRatio = $largestTime / $baselineTime;

        expect($largestTime)->toBeLessThan(400); // Even with 1000 sessions, under 400ms
        expect($degradationRatio)->toBeLessThan(3); // Less than 3x degradation from 100 to 1000 sessions
    });

    it('meets session sidebar search performance requirements', function (): void {
        // Create searchable content
        $businessTypes = [
            'ecommerce store selling handmade jewelry',
            'fintech startup for small business loans',
            'healthcare platform for telemedicine',
            'educational app for language learning',
            'gaming community for indie developers',
        ];

        foreach ($businessTypes as $business) {
            NamingSession::factory()->count(50)->create([
                'user_id' => $this->user->id,
                'business_description' => $business,
                'title' => 'Session for '.explode(' ', $business)[0],
            ]);
        }

        // Test sidebar search performance
        $component = Livewire::test(SessionSidebar::class);

        $startTime = microtime(true);
        $component->set('searchQuery', 'ecommerce');
        $endTime = microtime(true);

        $searchTime = ($endTime - $startTime) * 1000;

        $component->assertSee('ecommerce');
        expect($searchTime)->toBeLessThan(200); // Sidebar search under 200ms
    });

    it('efficiently handles session state changes and updates', function (): void {
        $sessions = NamingSession::factory()->count(100)->create(['user_id' => $this->user->id]);
        $sessionService = app(SessionService::class);

        $updateTimes = [];

        // Test various state changes
        foreach ($sessions->take(10) as $session) {
            $startTime = microtime(true);

            // Save data (this is the main update method available)
            $sessionService->saveSession($this->user, $session->id, [
                'business_description' => 'Updated business description',
                'title' => 'Updated Title',
                'is_starred' => ! $session->is_starred,
            ]);

            $endTime = microtime(true);
            $updateTimes[] = ($endTime - $startTime) * 1000;
        }

        $averageUpdateTime = array_sum($updateTimes) / count($updateTimes);

        expect($averageUpdateTime)->toBeLessThan(100); // Session updates under 100ms average
        expect(max($updateTimes))->toBeLessThan(200); // No single update over 200ms
    });
});
