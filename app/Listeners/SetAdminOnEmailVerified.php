<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

/**
 * Automatically sets admin status for the owner email when verified.
 */
class SetAdminOnEmailVerified
{
    /**
     * Owner email that should automatically get admin access.
     */
    private const OWNER_EMAIL = '03matei@gmail.com';

    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        $authenticatable = $event->user;

        // Type check: ensure it's actually a User model
        if (! $authenticatable instanceof User) {
            return;
        }

        $user = $authenticatable;

        // If this is the owner's email, automatically set admin status
        if ($user->email === self::OWNER_EMAIL) {
            $user->is_admin = true;
            $user->save();

            Log::info('Admin access granted to owner email', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }
    }
}
