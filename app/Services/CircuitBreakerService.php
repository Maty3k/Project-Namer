<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class CircuitBreakerService
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    public function __construct(
        private readonly string $serviceName,
        private readonly int $failureThreshold = 5,
        private readonly int $timeoutMinutes = 5,
        private readonly int $successThreshold = 3
    ) {}

    public function call(callable $operation): mixed
    {
        $state = $this->getState();

        switch ($state) {
            case self::STATE_OPEN:
                if ($this->shouldAttemptReset()) {
                    $this->setState(self::STATE_HALF_OPEN);
                    return $this->executeWithMonitoring($operation, true);
                } else {
                    $this->logCircuitBreakerAction('blocked', 'Circuit breaker is OPEN, blocking request');
                    throw new Exception("Circuit breaker is OPEN for service: {$this->serviceName}");
                }

            case self::STATE_HALF_OPEN:
                return $this->executeWithMonitoring($operation, true);

            case self::STATE_CLOSED:
            default:
                return $this->executeWithMonitoring($operation, false);
        }
    }

    public function getState(): string
    {
        return Cache::get($this->getStateKey(), self::STATE_CLOSED);
    }

    public function getFailureCount(): int
    {
        return Cache::get($this->getFailureCountKey(), 0);
    }

    public function getSuccessCount(): int
    {
        return Cache::get($this->getSuccessCountKey(), 0);
    }

    public function reset(): void
    {
        Cache::forget($this->getStateKey());
        Cache::forget($this->getFailureCountKey());
        Cache::forget($this->getSuccessCountKey());
        Cache::forget($this->getLastFailureTimeKey());

        $this->logCircuitBreakerAction('reset', 'Circuit breaker manually reset');
    }

    public function getStats(): array
    {
        return [
            'service_name' => $this->serviceName,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'success_count' => $this->getSuccessCount(),
            'failure_threshold' => $this->failureThreshold,
            'timeout_minutes' => $this->timeoutMinutes,
            'success_threshold' => $this->successThreshold,
            'last_failure_time' => Cache::get($this->getLastFailureTimeKey()),
        ];
    }

    private function executeWithMonitoring(callable $operation, bool $isHalfOpen): mixed
    {
        try {
            $result = $operation();
            $this->recordSuccess($isHalfOpen);
            return $result;
        } catch (Exception $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    private function recordSuccess(bool $isHalfOpen): void
    {
        if ($isHalfOpen) {
            $successCount = $this->incrementSuccessCount();

            if ($successCount >= $this->successThreshold) {
                $this->setState(self::STATE_CLOSED);
                $this->resetCounters();
                $this->logCircuitBreakerAction('closed', 'Circuit breaker CLOSED after successful recovery');
            }
        } else {
            // Reset failure count on successful operation in CLOSED state
            Cache::forget($this->getFailureCountKey());
        }
    }

    private function recordFailure(): void
    {
        $failureCount = $this->incrementFailureCount();
        Cache::put($this->getLastFailureTimeKey(), now(), now()->addHours(24));

        if ($failureCount >= $this->failureThreshold) {
            $this->setState(self::STATE_OPEN);
            $this->logCircuitBreakerAction('opened', "Circuit breaker OPENED after {$failureCount} failures");
        }
    }

    private function shouldAttemptReset(): bool
    {
        $lastFailureTime = Cache::get($this->getLastFailureTimeKey());

        if (!$lastFailureTime) {
            return true;
        }

        if ($lastFailureTime instanceof Carbon) {
            return $lastFailureTime->addMinutes($this->timeoutMinutes)->isPast();
        }

        // Handle string timestamps
        return Carbon::parse($lastFailureTime)->addMinutes($this->timeoutMinutes)->isPast();
    }

    private function setState(string $state): void
    {
        Cache::put($this->getStateKey(), $state, now()->addHours(24));
    }

    private function incrementFailureCount(): int
    {
        $key = $this->getFailureCountKey();
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addHours(24));
        return $count;
    }

    private function incrementSuccessCount(): int
    {
        $key = $this->getSuccessCountKey();
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addHours(24));
        return $count;
    }

    private function resetCounters(): void
    {
        Cache::forget($this->getFailureCountKey());
        Cache::forget($this->getSuccessCountKey());
    }

    private function getStateKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:state";
    }

    private function getFailureCountKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failure_count";
    }

    private function getSuccessCountKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:success_count";
    }

    private function getLastFailureTimeKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:last_failure_time";
    }

    private function logCircuitBreakerAction(string $action, string $message): void
    {
        Log::info("Circuit Breaker [{$this->serviceName}]: {$message}", [
            'service' => $this->serviceName,
            'action' => $action,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'success_count' => $this->getSuccessCount(),
        ]);
    }
}