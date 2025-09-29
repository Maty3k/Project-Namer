<?php

declare(strict_types=1);

use App\Services\CircuitBreakerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('Circuit Breaker Service', function (): void {
    beforeEach(function (): void {
        Cache::flush();
        $this->circuitBreaker = new CircuitBreakerService('test_service', 3, 2, 2);
    });

    it('starts in closed state', function (): void {
        expect($this->circuitBreaker->getState())->toBe('closed')
            ->and($this->circuitBreaker->getFailureCount())->toBe(0)
            ->and($this->circuitBreaker->getSuccessCount())->toBe(0);
    });

    it('executes successful operations in closed state', function (): void {
        $result = $this->circuitBreaker->call(function () {
            return 'success';
        });

        expect($result)->toBe('success')
            ->and($this->circuitBreaker->getState())->toBe('closed')
            ->and($this->circuitBreaker->getFailureCount())->toBe(0);
    });

    it('tracks failures and opens circuit after threshold', function (): void {
        // Execute 3 failed operations (threshold)
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new Exception('Test failure');
                });
            } catch (Exception $e) {
                // Expected to fail
            }
        }

        expect($this->circuitBreaker->getState())->toBe('open')
            ->and($this->circuitBreaker->getFailureCount())->toBe(3);
    });

    it('blocks operations when circuit is open', function (): void {
        // Force circuit to open
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new Exception('Test failure');
                });
            } catch (Exception $e) {
                // Expected to fail
            }
        }

        // Now try to execute an operation - should be blocked
        expect(fn () => $this->circuitBreaker->call(function () {
            return 'should not execute';
        }))->toThrow(Exception::class, 'Circuit breaker is OPEN');
    });

    it('transitions to half-open after timeout', function (): void {
        // Force circuit to open
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new Exception('Test failure');
                });
            } catch (Exception $e) {
                // Expected to fail
            }
        }

        expect($this->circuitBreaker->getState())->toBe('open');

        // Simulate time passing by manipulating cache
        $lastFailureKey = "circuit_breaker:test_service:last_failure_time";
        Cache::put($lastFailureKey, now()->subMinutes(5), now()->addHours(24));

        // Next call should transition to half-open and then succeed
        $result = $this->circuitBreaker->call(function () {
            return 'success after timeout';
        });

        expect($result)->toBe('success after timeout');
        // After one successful call in half-open, it should still be half-open
        expect($this->circuitBreaker->getState())->toBe('half_open');
    });

    it('closes circuit after successful operations in half-open state', function (): void {
        // Force circuit to open first
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new Exception('Test failure');
                });
            } catch (Exception $e) {
                // Expected to fail
            }
        }

        // Simulate time passing to allow transition to half-open
        $lastFailureKey = "circuit_breaker:test_service:last_failure_time";
        Cache::put($lastFailureKey, now()->subMinutes(5), now()->addHours(24));

        // Execute successful operations (need 2 successes based on threshold)
        for ($i = 0; $i < 2; $i++) {
            $result = $this->circuitBreaker->call(function () {
                return 'success';
            });
            expect($result)->toBe('success');
        }

        expect($this->circuitBreaker->getState())->toBe('closed')
            ->and($this->circuitBreaker->getFailureCount())->toBe(0)
            ->and($this->circuitBreaker->getSuccessCount())->toBe(0);
    });

    it('can be manually reset', function (): void {
        // Force circuit to open
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new Exception('Test failure');
                });
            } catch (Exception $e) {
                // Expected to fail
            }
        }

        expect($this->circuitBreaker->getState())->toBe('open');

        // Reset manually
        $this->circuitBreaker->reset();

        expect($this->circuitBreaker->getState())->toBe('closed')
            ->and($this->circuitBreaker->getFailureCount())->toBe(0)
            ->and($this->circuitBreaker->getSuccessCount())->toBe(0);
    });

    it('provides detailed statistics', function (): void {
        $stats = $this->circuitBreaker->getStats();

        expect($stats)->toHaveKeys([
            'service_name',
            'state',
            'failure_count',
            'success_count',
            'failure_threshold',
            'timeout_minutes',
            'success_threshold',
            'last_failure_time'
        ])
            ->and($stats['service_name'])->toBe('test_service')
            ->and($stats['failure_threshold'])->toBe(3)
            ->and($stats['timeout_minutes'])->toBe(2)
            ->and($stats['success_threshold'])->toBe(2);
    });

    it('resets failure count on successful operation in closed state', function (): void {
        // Have some failures but not enough to open circuit
        for ($i = 0; $i < 2; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new Exception('Test failure');
                });
            } catch (Exception $e) {
                // Expected to fail
            }
        }

        expect($this->circuitBreaker->getFailureCount())->toBe(2);

        // Successful operation should reset failure count
        $this->circuitBreaker->call(function () {
            return 'success';
        });

        expect($this->circuitBreaker->getFailureCount())->toBe(0)
            ->and($this->circuitBreaker->getState())->toBe('closed');
    });

    it('handles mixed success and failure patterns correctly', function (): void {
        // Alternate between success and failure
        $this->circuitBreaker->call(function () {
            return 'success';
        });

        try {
            $this->circuitBreaker->call(function () {
                throw new Exception('failure');
            });
        } catch (Exception $e) {
            // Expected
        }

        expect($this->circuitBreaker->getState())->toBe('closed')
            ->and($this->circuitBreaker->getFailureCount())->toBe(1);

        $this->circuitBreaker->call(function () {
            return 'success';
        });

        expect($this->circuitBreaker->getFailureCount())->toBe(0);
    });
});