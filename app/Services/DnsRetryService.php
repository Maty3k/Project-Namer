<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Comprehensive DNS retry service with exponential backoff, jitter, and error-specific strategies.
 *
 * Provides intelligent retry mechanisms for DNS operations with configurable
 * backoff strategies, circuit breaker integration, and detailed metrics.
 */
final class DnsRetryService
{
    private readonly bool $enabled;

    private readonly int $maxAttempts;

    private readonly int $baseDelayMs;

    private readonly int $maxDelayMs;

    private readonly bool $exponentialBackoff;

    private readonly bool $jitterEnabled;

    private readonly float $jitterFactor;

    /**
     * @var array{total_operations: int, successful_operations: int, failed_operations: int, retry_attempts: int, total_delay_ms: float}
     */
    private array $retryMetrics = [
        'total_operations' => 0,
        'successful_operations' => 0,
        'failed_operations' => 0,
        'retry_attempts' => 0,
        'total_delay_ms' => 0,
    ];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $lastFailureAnalysis = null;

    /**
     * @var callable|null
     */
    private $sleepFunction = null;

    /**
     * @var callable|null
     */
    private $circuitBreakerCheck = null;

    public function __construct()
    {
        $this->enabled = config('dns.retry.enabled', true);
        $this->maxAttempts = config('dns.retry.max_attempts', 3);
        $this->baseDelayMs = config('dns.retry.base_delay_ms', 100);
        $this->maxDelayMs = config('dns.retry.max_delay_ms', 5000);
        $this->exponentialBackoff = config('dns.retry.exponential_backoff', true);
        $this->jitterEnabled = config('dns.retry.jitter_enabled', true);
        $this->jitterFactor = config('dns.retry.jitter_factor', 0.1);
    }

    /**
     * Execute an operation with retry logic.
     */
    /**
     * @param  array<string, mixed>  $options
     */
    public function execute(string $operation, callable $callback, array $options = []): mixed
    {
        $this->retryMetrics['total_operations']++;

        if (! $this->enabled) {
            return $this->executeOnce($operation, $callback);
        }

        $maxAttempts = $options['max_attempts'] ?? $this->maxAttempts;
        $baseDelay = $options['base_delay_ms'] ?? $this->baseDelayMs;

        $attempt = 1;
        $lastException = null;

        while ($attempt <= $maxAttempts) {
            // Check circuit breaker before each attempt
            if ($this->circuitBreakerCheck && ! ($this->circuitBreakerCheck)()) {
                Log::warning('DNS retry blocked by circuit breaker', [
                    'operation' => $operation,
                    'attempt' => $attempt,
                ]);
                break;
            }

            try {
                $result = $callback();
                $this->retryMetrics['successful_operations']++;

                if ($attempt > 1) {
                    Log::info('DNS operation succeeded after retry', [
                        'operation' => $operation,
                        'total_attempts' => $attempt,
                        'strategy' => 'exponential_backoff',
                    ]);
                }

                return $result;

            } catch (Exception $exception) {
                $lastException = $exception;

                // Don't retry non-retryable errors
                if (! $this->shouldRetry($exception)) {
                    Log::debug('DNS operation not retryable', [
                        'operation' => $operation,
                        'error' => $exception->getMessage(),
                        'error_type' => $exception::class,
                    ]);
                    break;
                }

                if ($attempt >= $maxAttempts) {
                    break;
                }

                $this->retryMetrics['retry_attempts']++;

                Log::warning('DNS operation retry attempt', [
                    'operation' => $operation,
                    'attempt' => $attempt + 1,
                    'max_attempts' => $maxAttempts,
                    'error' => $exception->getMessage(),
                    'error_type' => $exception::class,
                ]);

                $delay = $this->calculateDelay($attempt, $baseDelay);
                $this->retryMetrics['total_delay_ms'] += $delay;

                $this->sleep($delay);
            }

            $attempt++;
        }

        // All attempts exhausted
        $this->retryMetrics['failed_operations']++;
        $this->recordFailureAnalysis($operation, $attempt, $lastException);

        throw $lastException;
    }

    /**
     * Execute an async operation with retry logic.
     */
    /**
     * @param  array<string, mixed>  $options
     */
    public function executeAsync(string $operation, callable $callback, array $options = []): mixed
    {
        // For now, async operations use the same retry logic
        // In a real implementation, this could use promises or async/await
        return $this->execute($operation, $callback, $options);
    }

    /**
     * Get retry metrics for monitoring.
     */
    /**
     * @return array{total_operations: int, successful_operations: int, failed_operations: int, retry_attempts: int, total_delay_ms: float}
     */
    public function getRetryMetrics(): array
    {
        return [
            ...$this->retryMetrics,
            'success_rate' => $this->retryMetrics['total_operations'] > 0
                ? round(($this->retryMetrics['successful_operations'] / $this->retryMetrics['total_operations']) * 100, 2)
                : 0,
            'avg_delay_per_retry' => $this->retryMetrics['retry_attempts'] > 0
                ? round($this->retryMetrics['total_delay_ms'] / $this->retryMetrics['retry_attempts'], 2)
                : 0,
        ];
    }

    /**
     * Get detailed analysis of the last failure.
     */
    /**
     * @return array<string, mixed>|null
     */
    public function getLastFailureAnalysis(): ?array
    {
        return $this->lastFailureAnalysis;
    }

    /**
     * Reset retry metrics (useful for testing).
     */
    public function resetMetrics(): void
    {
        $this->retryMetrics = [
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'retry_attempts' => 0,
            'total_delay_ms' => 0,
        ];
        $this->lastFailureAnalysis = null;
    }

    /**
     * Set a custom sleep function (useful for testing).
     */
    public function setSleepFunction(callable $sleepFunction): void
    {
        $this->sleepFunction = $sleepFunction;
    }

    /**
     * Set a circuit breaker check function.
     */
    public function setCircuitBreakerCheck(callable $circuitBreakerCheck): void
    {
        $this->circuitBreakerCheck = $circuitBreakerCheck;
    }

    /**
     * Execute operation once without retry.
     */
    private function executeOnce(string $operation, callable $callback): mixed
    {
        try {
            $result = $callback();
            $this->retryMetrics['successful_operations']++;

            return $result;
        } catch (Exception $exception) {
            $this->retryMetrics['failed_operations']++;
            throw $exception;
        }
    }

    /**
     * Determine if an exception should trigger a retry.
     */
    private function shouldRetry(Exception $exception): bool
    {
        $errorMessage = strtolower($exception->getMessage());
        $errorType = $exception::class;

        // Don't retry validation errors
        if ($exception instanceof InvalidArgumentException) {
            return false;
        }

        // Don't retry format errors
        if (str_contains($errorMessage, 'invalid') &&
            (str_contains($errorMessage, 'format') || str_contains($errorMessage, 'domain'))) {
            return false;
        }

        // Retry network-related errors
        $retryableErrors = [
            'timeout',
            'connection',
            'network',
            'unreachable',
            'refused',
            'dns server',
            'resolver',
            'temporary failure',
            'try again',
        ];

        foreach ($retryableErrors as $retryableError) {
            if (str_contains($errorMessage, $retryableError)) {
                return true;
            }
        }

        // Default: retry most exceptions except validation errors
        return true;
    }

    /**
     * Calculate delay for next retry attempt.
     */
    private function calculateDelay(int $attempt, int $baseDelay): int
    {
        if (! $this->exponentialBackoff) {
            return $baseDelay;
        }

        // Exponential backoff: base * (2 ^ (attempt - 1))
        $delay = $baseDelay * 2 ** ($attempt - 1);

        // Apply jitter if enabled
        if ($this->jitterEnabled) {
            $jitter = $delay * $this->jitterFactor * (mt_rand() / mt_getrandmax());
            $delay += $jitter;
        }

        // Cap at maximum delay
        return min((int) $delay, $this->maxDelayMs);
    }

    /**
     * Sleep for the specified number of milliseconds.
     */
    private function sleep(int $milliseconds): void
    {
        if ($this->sleepFunction) {
            ($this->sleepFunction)($milliseconds);
        } else {
            usleep($milliseconds * 1000); // Convert to microseconds
        }
    }

    /**
     * Record detailed failure analysis.
     */
    private function recordFailureAnalysis(string $operation, int $attempts, ?Exception $exception): void
    {
        $this->lastFailureAnalysis = [
            'operation' => $operation,
            'total_attempts' => $attempts,
            'error_type' => $exception ? $exception::class : 'Unknown',
            'error_message' => $exception ? $exception->getMessage() : 'Unknown error',
            'retry_strategy' => $this->exponentialBackoff ? 'exponential_backoff' : 'fixed_delay',
            'jitter_enabled' => $this->jitterEnabled,
            'failed_at' => Carbon::now()->toISOString(),
            'config' => [
                'max_attempts' => $this->maxAttempts,
                'base_delay_ms' => $this->baseDelayMs,
                'max_delay_ms' => $this->maxDelayMs,
            ],
        ];

        Log::error('DNS operation failed after all retry attempts', $this->lastFailureAnalysis);
    }
}
