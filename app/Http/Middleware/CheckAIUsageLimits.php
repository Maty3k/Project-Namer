<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AI\AICostTrackingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseInterface;

/**
 * Check AI Usage Limits Middleware.
 *
 * Validates that the user hasn't exceeded their hourly or daily
 * AI generation limits before allowing API requests to proceed.
 */
class CheckAIUsageLimits
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): ResponseInterface
    {
        $user = Auth::user();

        // Allow requests for non-authenticated users (they have different limits)
        if (! $user) {
            return $next($request);
        }

        $costTracker = app(AICostTrackingService::class);
        $limits = $costTracker->checkUserLimits($user);

        // Check hourly limit
        if ($limits['hourly']['exceeded']) {
            return response()->json([
                'error' => 'Hourly usage limit exceeded',
                'message' => 'You have reached your hourly limit of '.$limits['hourly']['limit'].' AI generations. Please try again in the next hour.',
                'limits' => $limits,
                'retry_after' => $this->calculateRetryAfter('hour'),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Check daily limit
        if ($limits['daily']['exceeded']) {
            return response()->json([
                'error' => 'Daily usage limit exceeded',
                'message' => 'You have reached your daily limit of '.$limits['daily']['limit'].' AI generations. Please try again tomorrow.',
                'limits' => $limits,
                'retry_after' => $this->calculateRetryAfter('day'),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Add usage limit information to response headers
        $response = $next($request);

        $response->headers->set('X-RateLimit-Hourly-Limit', $limits['hourly']['limit']);
        $response->headers->set('X-RateLimit-Hourly-Remaining', $limits['hourly']['remaining']);
        $response->headers->set('X-RateLimit-Daily-Limit', $limits['daily']['limit']);
        $response->headers->set('X-RateLimit-Daily-Remaining', $limits['daily']['remaining']);

        return $response;
    }

    /**
     * Calculate retry after time in seconds.
     */
    protected function calculateRetryAfter(string $period): int
    {
        return match ($period) {
            'hour' => now()->addHour()->startOfHour()->diffInSeconds(),
            'day' => now()->addDay()->startOfDay()->diffInSeconds(),
            default => 3600, // 1 hour default
        };
    }
}
