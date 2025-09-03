<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI model performance tracking model.
 * 
 * Tracks performance metrics for individual AI models per user,
 * including success rates, response times, and cost analytics.
 *
 * @property int $id
 * @property int $user_id
 * @property string $model_name
 * @property int $total_requests
 * @property int $successful_requests
 * @property int $failed_requests
 * @property int $average_response_time_ms
 * @property int $total_tokens_used
 * @property int $total_cost_cents
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property array<array-key, mixed>|null $performance_metrics
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\AIModelPerformanceFactory factory($count = null, $state = [])
 * @method static Builder<static>|AIModelPerformance forModel(string $modelName)
 * @method static Builder<static>|AIModelPerformance forUser(int $userId)
 * @method static Builder<static>|AIModelPerformance newModelQuery()
 * @method static Builder<static>|AIModelPerformance newQuery()
 * @method static Builder<static>|AIModelPerformance query()
 * @method static Builder<static>|AIModelPerformance recentlyUsed()
 * @method static Builder<static>|AIModelPerformance whereAverageResponseTimeMs($value)
 * @method static Builder<static>|AIModelPerformance whereCreatedAt($value)
 * @method static Builder<static>|AIModelPerformance whereFailedRequests($value)
 * @method static Builder<static>|AIModelPerformance whereId($value)
 * @method static Builder<static>|AIModelPerformance whereLastUsedAt($value)
 * @method static Builder<static>|AIModelPerformance whereModelName($value)
 * @method static Builder<static>|AIModelPerformance wherePerformanceMetrics($value)
 * @method static Builder<static>|AIModelPerformance whereSuccessfulRequests($value)
 * @method static Builder<static>|AIModelPerformance whereTotalCostCents($value)
 * @method static Builder<static>|AIModelPerformance whereTotalRequests($value)
 * @method static Builder<static>|AIModelPerformance whereTotalTokensUsed($value)
 * @method static Builder<static>|AIModelPerformance whereUpdatedAt($value)
 * @method static Builder<static>|AIModelPerformance whereUserId($value)
 * @mixin \Eloquent
 */
final class AIModelPerformance extends Model
{
    /** @use HasFactory<\Database\Factories\AIModelPerformanceFactory> */
    use HasFactory;

    protected $table = 'ai_model_performance';

    protected $fillable = [
        'user_id',
        'model_name',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'average_response_time_ms',
        'total_tokens_used',
        'total_cost_cents',
        'last_used_at',
        'performance_metrics',
    ];

    protected function casts(): array
    {
        return [
            'total_requests' => 'integer',
            'successful_requests' => 'integer',
            'failed_requests' => 'integer',
            'average_response_time_ms' => 'integer',
            'total_tokens_used' => 'integer',
            'total_cost_cents' => 'integer',
            'last_used_at' => 'datetime',
            'performance_metrics' => 'array',
        ];
    }

    /**
     * Get the user that owns the performance record.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get performance records for a specific model.
     *
     * @param  Builder<AIModelPerformance>  $query
     * @return Builder<AIModelPerformance>
     */
    protected function scopeForModel(Builder $query, string $modelName): Builder
    {
        return $query->where('model_name', $modelName);
    }

    /**
     * Scope to get recently used model performance records.
     *
     * @param  Builder<AIModelPerformance>  $query
     * @return Builder<AIModelPerformance>
     */
    protected function scopeRecentlyUsed(Builder $query): Builder
    {
        return $query->where('last_used_at', '>=', now()->subWeek());
    }

    /**
     * Scope to get performance records for a specific user.
     *
     * @param  Builder<AIModelPerformance>  $query
     * @return Builder<AIModelPerformance>
     */
    protected function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Find or create performance record for user and model.
     */
    public static function findOrCreateForUser(int $userId, string $modelName): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'model_name' => $modelName],
            [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'average_response_time_ms' => 0,
                'total_tokens_used' => 0,
                'total_cost_cents' => 0,
                'performance_metrics' => [],
            ]
        );
    }

    /**
     * Calculate success rate as percentage.
     */
    public function getSuccessRate(): float
    {
        if ($this->total_requests === 0) {
            return 0.0;
        }

        return round(($this->successful_requests / $this->total_requests) * 100, 1);
    }

    /**
     * Calculate failure rate as percentage.
     */
    public function getFailureRate(): float
    {
        if ($this->total_requests === 0) {
            return 0.0;
        }

        return round(($this->failed_requests / $this->total_requests) * 100, 1);
    }

    /**
     * Calculate cost per request in cents.
     */
    public function getCostPerRequest(): float
    {
        if ($this->total_requests === 0) {
            return 0.0;
        }

        return round($this->total_cost_cents / $this->total_requests, 1);
    }

    /**
     * Update metrics with new request data.
     */
    public function updateMetrics(
        int $responseTime,
        int $tokensUsed,
        int $costCents,
        bool $wasSuccessful
    ): void {
        $oldTotalTime = $this->average_response_time_ms * $this->total_requests;
        $newTotalTime = $oldTotalTime + $responseTime;
        $newTotalRequests = $this->total_requests + 1;

        $this->update([
            'total_requests' => $newTotalRequests,
            'successful_requests' => $this->successful_requests + ($wasSuccessful ? 1 : 0),
            'failed_requests' => $this->failed_requests + ($wasSuccessful ? 0 : 1),
            'average_response_time_ms' => (int) round($newTotalTime / $newTotalRequests),
            'total_tokens_used' => $this->total_tokens_used + ($wasSuccessful ? $tokensUsed : 0),
            'total_cost_cents' => $this->total_cost_cents + ($wasSuccessful ? $costCents : 0),
            'last_used_at' => now(),
        ]);
    }

    /**
     * Get performance summary.
     *
     * @return array<string, mixed>
     */
    public function getPerformanceSummary(): array
    {
        return [
            'model_name' => $this->model_name,
            'total_requests' => $this->total_requests,
            'success_rate' => $this->getSuccessRate(),
            'failure_rate' => $this->getFailureRate(),
            'average_response_time_ms' => $this->average_response_time_ms,
            'cost_per_request_cents' => $this->getCostPerRequest(),
            'total_cost_cents' => $this->total_cost_cents,
            'total_tokens_used' => $this->total_tokens_used,
            'last_used_at' => $this->last_used_at?->toISOString(),
        ];
    }
}
