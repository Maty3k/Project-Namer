<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to add response time header to API responses.
 */
final class ResponseTime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        $response->headers->set('X-Response-Time', (string) $responseTime);

        return $response;
    }
}
