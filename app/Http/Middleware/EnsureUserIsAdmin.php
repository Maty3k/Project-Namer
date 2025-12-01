<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        \Log::info('Admin middleware check', [
            'has_user' => $user !== null,
            'user_id' => $user?->id,
            'is_admin' => $user?->is_admin,
            'is_admin_type' => $user ? gettype($user->is_admin) : null,
        ]);

        if (! $user || ! $user->is_admin) {
            abort(403, 'Unauthorized access to admin panel');
        }

        return $next($request);
    }
}
