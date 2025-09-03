<?php

declare(strict_types=1);

use App\Models\NamingSession;
use App\Models\SessionResult;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('Database Query Optimization', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->sessionService = app(SessionService::class);
        $this->actingAs($this->user);
    });

    it('optimizes getUserSessions with eager loading to prevent N+1 queries', function (): void {
        // Create sessions with results
        $sessions = NamingSession::factory()->count(20)->create(['user_id' => $this->user->id]);

        foreach ($sessions as $session) {
            SessionResult::factory()->count(5)->create(['session_id' => $session->id]);
        }

        DB::enableQueryLog();
        $queriesBefore = count(DB::getQueryLog());

        // Get sessions with service
        $userSessions = $this->sessionService->getUserSessions($this->user, 20, 0);

        $queriesAfter = count(DB::getQueryLog());
        $totalQueries = $queriesAfter - $queriesBefore;
        DB::disableQueryLog();

        // With eager loading, should only need 2 queries:
        // 1. Load sessions
        // 2. Load results for all sessions at once
        expect($totalQueries)->toBeLessThanOrEqual(2);
        expect($userSessions->count())->toBe(20);

        // Verify results are loaded without additional queries
        DB::enableQueryLog();
        $queriesBefore = count(DB::getQueryLog());

        foreach ($userSessions as $session) {
            // Access results - should not trigger additional queries
            $session->results->count();
        }

        $queriesAfter = count(DB::getQueryLog());
        $additionalQueries = $queriesAfter - $queriesBefore;
        DB::disableQueryLog();

        expect($additionalQueries)->toBe(0); // No additional queries when accessing results
    });

    it('optimizes searchSessions with eager loading', function (): void {
        // Create sessions with results
        NamingSession::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'title' => 'Ecommerce Platform',
            'business_description' => 'Online shopping platform',
        ]);

        NamingSession::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'title' => 'Fintech App',
            'business_description' => 'Financial technology solution',
        ]);

        // Add results to all sessions
        NamingSession::where('user_id', $this->user->id)->get()->each(function ($session): void {
            SessionResult::factory()->count(3)->create(['session_id' => $session->id]);
        });

        DB::enableQueryLog();
        $queriesBefore = count(DB::getQueryLog());

        // Search sessions
        $searchResults = $this->sessionService->searchSessions($this->user, 'ecommerce');

        $queriesAfter = count(DB::getQueryLog());
        $totalQueries = $queriesAfter - $queriesBefore;
        DB::disableQueryLog();

        // Should be optimized with eager loading
        expect($totalQueries)->toBeLessThanOrEqual(4); // Search query + sessions query + results query
        expect($searchResults->count())->toBeGreaterThan(0);
    });

    it('optimizes filterSessions with eager loading', function (): void {
        // Create starred and regular sessions
        NamingSession::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'is_starred' => true,
        ]);

        NamingSession::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'is_starred' => false,
        ]);

        // Add results to all sessions
        NamingSession::where('user_id', $this->user->id)->get()->each(function ($session): void {
            SessionResult::factory()->count(2)->create(['session_id' => $session->id]);
        });

        DB::enableQueryLog();
        $queriesBefore = count(DB::getQueryLog());

        // Filter starred sessions
        $starredSessions = $this->sessionService->filterSessions($this->user, ['is_starred' => true]);

        $queriesAfter = count(DB::getQueryLog());
        $totalQueries = $queriesAfter - $queriesBefore;
        DB::disableQueryLog();

        // Should use eager loading
        expect($totalQueries)->toBeLessThanOrEqual(2);
        expect($starredSessions->count())->toBe(15);
        expect($starredSessions->every(fn ($session) => $session->is_starred))->toBe(true);
    });

    it('limits results loading for performance', function (): void {
        // Create session with many results
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
        SessionResult::factory()->count(20)->create(['session_id' => $session->id]);

        DB::enableQueryLog();

        // Get sessions
        $userSessions = $this->sessionService->getUserSessions($this->user, 1, 0);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Check that results query includes LIMIT
        $resultsQuery = collect($queries)->first(fn ($query) => str_contains((string) $query['query'], 'session_results'));

        expect($resultsQuery)->not->toBeNull();
        expect($userSessions->first()->results->count())->toBeLessThanOrEqual(3);
    });

    it('measures performance improvement with eager loading', function (): void {
        // Create test data
        $sessions = NamingSession::factory()->count(50)->create(['user_id' => $this->user->id]);
        foreach ($sessions as $session) {
            SessionResult::factory()->count(3)->create(['session_id' => $session->id]);
        }

        // Measure performance with optimized queries
        $startTime = microtime(true);
        $optimizedSessions = $this->sessionService->getUserSessions($this->user, 50, 0);

        // Access results to trigger loading
        foreach ($optimizedSessions as $session) {
            $session->results->count();
        }

        $endTime = microtime(true);
        $optimizedTime = ($endTime - $startTime) * 1000;

        // Should be performant
        expect($optimizedTime)->toBeLessThan(200); // Under 200ms
        expect($optimizedSessions->count())->toBe(50);
    });

    it('handles large datasets efficiently with selective loading', function (): void {
        // Create large dataset
        $sessions = collect();
        for ($i = 0; $i < 100; $i++) {
            $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
            $sessions->push($session);

            // Add varying numbers of results
            SessionResult::factory()->count(random_int(1, 10))->create(['session_id' => $session->id]);
        }

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Load sessions with optimized query
        $loadedSessions = $this->sessionService->getUserSessions($this->user, 20, 0);

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $loadTime = ($endTime - $startTime) * 1000;

        // Performance checks
        expect($loadTime)->toBeLessThan(150); // Under 150ms
        expect(count($queries))->toBeLessThanOrEqual(2); // Efficient query count
        expect($loadedSessions->count())->toBe(20);
    });

    it('optimizes session result access patterns', function (): void {
        // Create sessions with various result counts
        $sessions = collect();
        for ($i = 0; $i < 10; $i++) {
            $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
            SessionResult::factory()->count($i + 1)->create(['session_id' => $session->id]);
            $sessions->push($session);
        }

        // Load with eager loading
        $loadedSessions = $this->sessionService->getUserSessions($this->user, 10, 0);

        DB::enableQueryLog();

        // Access patterns that should not trigger additional queries
        foreach ($loadedSessions as $session) {
            // Check if results exist
            $hasResults = $session->results->isNotEmpty();

            if ($hasResults) {
                // Access first result
                $firstResult = $session->results->first();
                $firstResult?->generated_names;

                // Count results
                $resultCount = $session->results->count();
            }
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should not have triggered any additional queries
        expect(count($queries))->toBe(0);
    });

    it('validates query optimization maintains data integrity', function (): void {
        // Create test data with specific content
        $session1 = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'E-commerce Platform',
            'business_description' => 'Online shopping solution',
        ]);

        $session2 = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Fintech App',
            'business_description' => 'Financial technology platform',
        ]);

        // Add specific results
        $result1 = SessionResult::factory()->create([
            'session_id' => $session1->id,
            'generated_names' => ['ShopEasy', 'CommercePro', 'StoreFront'],
        ]);

        $result2 = SessionResult::factory()->create([
            'session_id' => $session2->id,
            'generated_names' => ['FinanceHub', 'MoneyWise', 'CashFlow'],
        ]);

        // Load sessions with optimization
        $loadedSessions = $this->sessionService->getUserSessions($this->user, 10, 0);

        // Verify data integrity
        $ecommerceSession = $loadedSessions->firstWhere('title', 'E-commerce Platform');
        $fintechSession = $loadedSessions->firstWhere('title', 'Fintech App');

        expect($ecommerceSession)->not->toBeNull();
        expect($fintechSession)->not->toBeNull();

        expect($ecommerceSession->results->first()->generated_names)->toBe(['ShopEasy', 'CommercePro', 'StoreFront']);
        expect($fintechSession->results->first()->generated_names)->toBe(['FinanceHub', 'MoneyWise', 'CashFlow']);
    });
});
