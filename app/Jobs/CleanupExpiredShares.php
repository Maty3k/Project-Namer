<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Share;
use App\Models\ShareAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CleanupExpiredShares implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $batchSize = 100
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting expired shares cleanup job');

        $cleanedUpCount = 0;

        // Process shares in batches to avoid memory issues
        Share::where('expires_at', '<', now())
            ->where('is_active', true)
            ->chunkById($this->batchSize, function ($shares) use (&$cleanedUpCount): void {
                foreach ($shares as $share) {
                    $this->cleanupShare($share);
                    $cleanedUpCount++;
                }
            });

        // Also cleanup old inactive shares (older than 30 days)
        $oldInactiveCount = $this->cleanupOldInactiveShares();

        // Clean up orphaned share access records
        $orphanedAccessCount = $this->cleanupOrphanedShareAccesses();

        // Clear related cache entries
        $this->clearExpiredShareCaches();

        Log::info('Expired shares cleanup completed', [
            'expired_shares_deactivated' => $cleanedUpCount,
            'old_inactive_shares_removed' => $oldInactiveCount,
            'orphaned_accesses_cleaned' => $orphanedAccessCount,
        ]);
    }

    /**
     * Cleanup a single expired share.
     */
    private function cleanupShare(Share $share): void
    {
        try {
            // Deactivate the share instead of deleting it for audit trail
            $share->update(['is_active' => false]);

            // Clear cache entries for this share
            Cache::forget("share_access:{$share->uuid}");
            Cache::forget("share_metadata:{$share->uuid}");

            Log::debug('Share deactivated due to expiration', [
                'share_uuid' => $share->uuid,
                'expired_at' => $share->expires_at?->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired share', [
                'share_uuid' => $share->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cleanup old inactive shares (complete removal after 30 days).
     */
    private function cleanupOldInactiveShares(): int
    {
        $cutoffDate = now()->subDays(30);

        $sharesToDelete = Share::where('is_active', false)
            ->where('updated_at', '<', $cutoffDate)
            ->get();

        $deletedCount = 0;

        foreach ($sharesToDelete as $share) {
            try {
                // Delete related access records first
                ShareAccess::where('share_id', $share->id)->delete();

                // Clear any remaining cache
                Cache::forget("share_access:{$share->uuid}");
                Cache::forget("share_metadata:{$share->uuid}");

                // Delete the share
                $share->delete();

                $deletedCount++;

                Log::debug('Old inactive share permanently deleted', [
                    'share_uuid' => $share->uuid,
                    'deactivated_at' => $share->updated_at->toISOString(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to delete old inactive share', [
                    'share_uuid' => $share->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $deletedCount;
    }

    /**
     * Cleanup orphaned share access records.
     */
    private function cleanupOrphanedShareAccesses(): int
    {
        $cutoffDate = now()->subDays(90);

        // Delete access records for shares that no longer exist
        $orphanedAccessCount = DB::table('share_accesses')
            ->leftJoin('shares', 'share_accesses.share_id', '=', 'shares.id')
            ->whereNull('shares.id')
            ->delete();

        // Also cleanup very old access records to keep the table manageable
        $oldAccessCount = ShareAccess::where('accessed_at', '<', $cutoffDate)->delete();

        return $orphanedAccessCount + $oldAccessCount;
    }

    /**
     * Clear cache entries for expired shares.
     */
    private function clearExpiredShareCaches(): void
    {
        // Since we can't easily iterate all cache keys, we'll use cache tags if available
        // or implement a more sophisticated cache clearing strategy in production

        // For now, we'll let the cache entries expire naturally
        // In a production system, you might want to:
        // 1. Use cache tags for easier bulk clearing
        // 2. Store a list of active share UUIDs for targeted cache clearing
        // 3. Implement a separate job for cache maintenance

        Log::debug('Expired share cache clearing completed');
    }

    /**
     * Get the tags associated with the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['cleanup', 'shares', 'maintenance'];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Expired shares cleanup job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
