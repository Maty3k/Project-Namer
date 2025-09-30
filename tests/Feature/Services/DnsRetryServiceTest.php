<?php

declare(strict_types=1);

use App\Services\DnsRetryService;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    Log::spy();
    config([
        'dns.retry.enabled' => true,
        'dns.retry.max_attempts' => 3,
        'dns.retry.base_delay_ms' => 100,
        'dns.retry.max_delay_ms' => 5000,
        'dns.retry.exponential_backoff' => true,
        'dns.retry.jitter_enabled' => true,
    ]);
});

test('DNS retry service executes operations successfully on first attempt', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    $result = $retryService->execute('test-operation', function () use (&$callCount) {
        $callCount++;

        return 'success';
    });

    expect($result)->toBe('success')
        ->and($callCount)->toBe(1);
});

test('DNS retry service retries failed operations with exponential backoff', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    $result = $retryService->execute('test-operation', function () use (&$callCount) {
        $callCount++;
        if ($callCount < 3) {
            throw new Exception('DNS timeout');
        }

        return 'success';
    });

    expect($result)->toBe('success')
        ->and($callCount)->toBe(3);
});

test('DNS retry service respects max attempts configuration', function (): void {
    config(['dns.retry.max_attempts' => 2]);
    $retryService = new DnsRetryService;
    $callCount = 0;

    expect(function () use ($retryService, &$callCount): void {
        $retryService->execute('test-operation', function () use (&$callCount): void {
            $callCount++;
            throw new Exception('Persistent failure');
        });
    })->toThrow(Exception::class, 'Persistent failure');

    expect($callCount)->toBe(2);
});

test('DNS retry service uses exponential backoff with jitter', function (): void {
    $retryService = new DnsRetryService;
    $delays = [];

    // Mock the sleep function to capture delays
    $retryService->setSleepFunction(function ($milliseconds) use (&$delays): void {
        $delays[] = $milliseconds;
    });

    $callCount = 0;
    expect(function () use ($retryService, &$callCount): void {
        $retryService->execute('test-operation', function () use (&$callCount): void {
            $callCount++;
            throw new Exception('Always fails');
        });
    })->toThrow(Exception::class);

    expect($delays)->toHaveCount(2) // 3 attempts = 2 delays
        ->and($delays[0])->toBeGreaterThan(0)
        ->and($delays[1])->toBeGreaterThan($delays[0]); // Second delay should be longer
});

test('DNS retry service differentiates retry policies by error type', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    // Network errors should be retried
    $result = $retryService->execute('test-operation', function () use (&$callCount) {
        $callCount++;
        if ($callCount < 2) {
            throw new Exception('Network timeout');
        }

        return 'success';
    });

    expect($result)->toBe('success')
        ->and($callCount)->toBe(2);
});

test('DNS retry service does not retry non-retryable errors', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    expect(function () use ($retryService, &$callCount): void {
        $retryService->execute('test-operation', function () use (&$callCount): void {
            $callCount++;
            throw new InvalidArgumentException('Invalid domain format');
        });
    })->toThrow(InvalidArgumentException::class);

    expect($callCount)->toBe(1); // Should not retry validation errors
});

test('DNS retry service logs retry attempts', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    $retryService->execute('test-operation', function () use (&$callCount) {
        $callCount++;
        if ($callCount < 2) {
            throw new Exception('Temporary failure');
        }

        return 'success';
    });

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn ($message, $context) => $message === 'DNS operation retry attempt' &&
               $context['operation'] === 'test-operation' &&
               $context['attempt'] === 2);
});

test('DNS retry service respects disabled configuration', function (): void {
    config(['dns.retry.enabled' => false]);
    $retryService = new DnsRetryService;
    $callCount = 0;

    expect(function () use ($retryService, &$callCount): void {
        $retryService->execute('test-operation', function () use (&$callCount): void {
            $callCount++;
            throw new Exception('Should not retry');
        });
    })->toThrow(Exception::class);

    expect($callCount)->toBe(1); // No retries when disabled
});

test('DNS retry service handles circuit breaker integration', function (): void {
    $retryService = new DnsRetryService;

    // Mock circuit breaker that blocks after failures
    $circuitBreakerBlocked = false;
    $retryService->setCircuitBreakerCheck(function () use (&$circuitBreakerBlocked) {
        return ! $circuitBreakerBlocked;
    });

    $callCount = 0;
    expect(function () use ($retryService, &$callCount, &$circuitBreakerBlocked): void {
        $retryService->execute('test-operation', function () use (&$callCount, &$circuitBreakerBlocked): void {
            $callCount++;
            if ($callCount >= 2) {
                $circuitBreakerBlocked = true; // Circuit breaker opens
            }
            throw new Exception('Service failure');
        });
    })->toThrow(Exception::class);

    expect($callCount)->toBe(2); // Should stop when circuit breaker opens
});

test('DNS retry service provides retry metrics', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    $retryService->execute('test-operation', function () use (&$callCount) {
        $callCount++;
        if ($callCount < 3) {
            throw new Exception('Temporary failure');
        }

        return 'success';
    });

    $metrics = $retryService->getRetryMetrics();
    expect($metrics['total_operations'])->toBeGreaterThan(0)
        ->and($metrics['successful_operations'])->toBeGreaterThan(0)
        ->and($metrics['retry_attempts'])->toBeGreaterThan(0);
});

test('DNS retry service handles async operations with retry', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    $result = $retryService->executeAsync('async-operation', function () use (&$callCount) {
        $callCount++;
        if ($callCount < 2) {
            throw new Exception('Async failure');
        }

        return 'async-success';
    });

    expect($result)->toBe('async-success')
        ->and($callCount)->toBe(2);
});

test('DNS retry service respects per-operation retry configuration', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    $result = $retryService->execute('critical-operation', function () use (&$callCount) {
        $callCount++;
        if ($callCount < 5) {
            throw new Exception('Critical failure');
        }

        return 'success';
    }, [
        'max_attempts' => 5,
        'base_delay_ms' => 50,
    ]);

    expect($result)->toBe('success')
        ->and($callCount)->toBe(5);
});

test('DNS retry service handles timeout errors with specific strategy', function (): void {
    $retryService = new DnsRetryService;
    $callCount = 0;

    $result = $retryService->execute('timeout-operation', function () use (&$callCount) {
        $callCount++;
        if ($callCount < 3) {
            throw new Exception('Operation timed out');
        }

        return 'recovered';
    });

    expect($result)->toBe('recovered')
        ->and($callCount)->toBe(3);
});

test('DNS retry service provides detailed failure analysis', function (): void {
    config(['dns.retry.max_attempts' => 3]);
    $retryService = new DnsRetryService;

    try {
        $retryService->execute('failing-operation', function (): void {
            throw new Exception('Persistent DNS failure');
        });
    } catch (Exception) {
        // Expected to fail
    }

    $analysis = $retryService->getLastFailureAnalysis();
    expect($analysis['operation'])->toBe('failing-operation')
        ->and($analysis['total_attempts'])->toBe(3)
        ->and($analysis['error_type'])->toBe('Exception')
        ->and($analysis['retry_strategy'])->toBe('exponential_backoff');
});
