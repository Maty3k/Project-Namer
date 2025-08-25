<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Export;
use App\Models\User;

/**
 * ExportPolicy defines authorization rules for Export model operations.
 */
final class ExportPolicy
{
    /**
     * Determine if the user can view the export.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     */
    public function view(User $user, Export $export): bool
    {
        return $export->user_id === $user->id;
    }

    /**
     * Determine if the user can download the export.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     */
    public function download(User $user, Export $export): bool
    {
        return $export->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the export.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     */
    public function delete(User $user, Export $export): bool
    {
        return $export->user_id === $user->id;
    }
}
