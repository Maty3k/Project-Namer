<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Share;
use App\Models\User;

/**
 * SharePolicy defines authorization rules for Share model operations.
 */
final class SharePolicy
{
    /**
     * Determine if the user can view the share.
     */
    public function view(User $user, Share $share): bool
    {
        return $share->user_id === $user->id;
    }

    /**
     * Determine if the user can update the share.
     */
    public function update(User $user, Share $share): bool
    {
        return $share->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the share.
     */
    public function delete(User $user, Share $share): bool
    {
        return $share->user_id === $user->id;
    }
}
