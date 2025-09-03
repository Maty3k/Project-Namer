<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NamingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionService
{
    /**
     * Create a new session for the user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSession(User $user, array $data): NamingSession
    {
        return $user->namingSessions()->create($data);
    }

    /**
     * Load an existing session by ID.
     */
    public function loadSession(User $user, string $sessionId): ?NamingSession
    {
        $session = $user->namingSessions()->find($sessionId);

        if ($session) {
            $session->markAccessed();
        }

        return $session;
    }

    /**
     * Save updates to an existing session.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveSession(User $user, string $sessionId, array $data): ?NamingSession
    {
        $session = $user->namingSessions()->find($sessionId);

        if (! $session) {
            return null;
        }

        // Auto-update title if business description changed
        if (isset($data['business_description']) && $data['business_description'] !== $session->business_description) {
            $data['title'] = Str::limit($data['business_description'], 50, '...');
        }

        $session->update($data);

        return $session->fresh();
    }

    /**
     * Delete a session and all its results.
     */
    public function deleteSession(User $user, string $sessionId): bool
    {
        $session = $user->namingSessions()->find($sessionId);

        if (! $session) {
            return false;
        }

        return $session->delete();
    }

    /**
     * Search sessions by title and business description using FTS5.
     *
     * @return Collection<int, NamingSession>
     */
    public function searchSessions(User $user, string $query): Collection
    {
        if (empty(trim($query))) {
            return $user->namingSessions()
                ->with(['results' => function ($query): void {
                    $query->select(['id', 'session_id', 'generated_names', 'generation_timestamp'])
                        ->orderBy('generation_timestamp', 'desc');
                }])
                ->recent()
                ->get();
        }

        try {
            // Try FTS5 search first
            $escapedQuery = '"'.str_replace('"', '""', trim($query)).'"';

            $matchingIds = DB::select(
                'SELECT id FROM naming_sessions_fts WHERE naming_sessions_fts MATCH ? ORDER BY rank',
                [$escapedQuery]
            );

            if (! empty($matchingIds)) {
                $sessionIds = array_column($matchingIds, 'id');

                return $user->namingSessions()
                    ->with(['results' => function ($query): void {
                        $query->select(['id', 'session_id', 'generated_names', 'generation_timestamp'])
                            ->orderBy('generation_timestamp', 'desc');
                    }])
                    ->whereIn('id', $sessionIds)
                    ->orderByRaw('CASE '.implode(' ', array_map(
                        fn ($id, $index) => "WHEN id = '{$id}' THEN {$index}",
                        $sessionIds,
                        array_keys($sessionIds)
                    )).' END')
                    ->get();
            }
        } catch (\Exception) {
            // Fall back to LIKE search if FTS5 fails
        }

        // Fallback to LIKE-based search (case-insensitive)
        $trimmedQuery = trim($query);

        return $user->namingSessions()
            ->with(['results' => function ($query): void {
                $query->select(['id', 'session_id', 'generated_names', 'generation_timestamp'])
                    ->orderBy('generation_timestamp', 'desc');
            }])
            ->where(function ($q) use ($trimmedQuery): void {
                $q->whereRaw('LOWER(title) LIKE LOWER(?)', ["%{$trimmedQuery}%"])
                    ->orWhereRaw('LOWER(business_description) LIKE LOWER(?)', ["%{$trimmedQuery}%"]);
            })
            ->recent()
            ->get();
    }

    /**
     * Filter sessions by criteria.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, NamingSession>
     */
    public function filterSessions(User $user, array $filters): Collection
    {
        $query = $user->namingSessions()
            ->with(['results' => function ($query): void {
                $query->select(['id', 'session_id', 'generated_names', 'generation_timestamp'])
                    ->orderBy('generation_timestamp', 'desc');
            }]);

        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        return $query->recent()->get();
    }

    /**
     * Duplicate an existing session.
     */
    public function duplicateSession(User $user, string $sessionId): ?NamingSession
    {
        $session = $user->namingSessions()->with('results')->find($sessionId);

        if (! $session) {
            return null;
        }

        return $session->duplicate();
    }

    /**
     * Get all sessions for a user with pagination.
     *
     * @return Collection<int, NamingSession>
     */
    public function getUserSessions(User $user, int $limit = 20, int $offset = 0): Collection
    {
        return $user->namingSessions()
            ->with(['results' => function ($query): void {
                $query->select(['id', 'session_id', 'generated_names', 'generation_timestamp'])
                    ->orderBy('generation_timestamp', 'desc')
                    ->limit(3); // Only load latest 3 results for preview
            }])
            ->recent()
            ->skip($offset)
            ->take($limit)
            ->get();
    }
}
