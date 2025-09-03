<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware for API routes
        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\ResponseTime::class,
        ]);

        $middleware->alias([
            'throttle.logos' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
            'throttle.downloads' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':30,1',
            'throttle.shares' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':10,1',
            'throttle.exports' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':15,1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle custom logo generation exceptions
        $exceptions->render(function (\App\Exceptions\LogoGenerationException $e, $request) {
            if ($request->expectsJson() || str_contains((string) $request->path(), 'api/')) {
                $response = $e->toArray();

                // Add request context for debugging
                if (app()->environment(['local', 'testing'])) {
                    $response['debug'] = [
                        'path' => $request->path(),
                        'method' => $request->method(),
                        'user_agent' => $request->userAgent(),
                    ];
                }

                return response()->json($response, $e->getCode());
            }
        });

        // Handle HTTP client connection exceptions
        $exceptions->render(function (\Illuminate\Http\Client\ConnectionException $e, $request) {
            if ($request->expectsJson() && str_contains((string) $request->path(), 'api/logos')) {
                return response()->json([
                    'message' => 'Logo generation service is temporarily unavailable. Please try again later.',
                    'retry_after' => 60,
                ], 503);
            }
        });

        // Handle validation exceptions with user-friendly messages
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() && str_contains((string) $request->path(), 'api/logos')) {
                $errors = [];
                $errorCount = 0;

                foreach ($e->errors() as $field => $messages) {
                    $message = $messages[0];
                    $errors[$field] = [
                        'message' => $message,
                        'aria_label' => 'Error: '.ucfirst(str_replace('_', ' ', $field)).' field '.strtolower($message),
                        'field_id' => $field,
                    ];
                    $errorCount++;
                }

                return response()->json([
                    'message' => 'Please correct the errors below',
                    'keyboard_hint' => 'Press Tab to navigate to the first error field',
                    'error_count' => $errorCount,
                    'errors' => $errors,
                ], 422);
            }
        });

        // Handle model not found exceptions
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The requested resource was not found.',
                ], 404);
            }
        });
    })->create();
