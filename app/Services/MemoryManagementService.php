<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Memory management service for large file processing operations.
 *
 * Provides memory monitoring, cleanup, and optimization for logo generation
 * and processing tasks to prevent memory exhaustion and improve performance.
 */
final class MemoryManagementService
{
    private const MEMORY_WARNING_THRESHOLD = 80; // 80% of memory limit

    private const MEMORY_CRITICAL_THRESHOLD = 95; // 95% of memory limit

    private const LARGE_FILE_THRESHOLD = 1048576; // 1MB

    private const STREAMING_CHUNK_SIZE = 8192; // 8KB chunks for streaming

    /** @var array<string, array<string, mixed>> */
    private array $memoryTrackingSessions = [];

    /**
     * Get current memory usage information.
     *
     * @return array<string, mixed>
     */
    public function getMemoryUsage(): array
    {
        $current = memory_get_usage();
        $peak = memory_get_peak_usage();
        $limit = $this->getMemoryLimit();

        return [
            'current' => $current,
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak' => $peak,
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'limit' => $limit,
            'limit_mb' => $limit > 0 ? round($limit / 1024 / 1024, 2) : 'unlimited',
            'usage_percentage' => $limit > 0 ? round(($current / $limit) * 100, 2) : 0,
        ];
    }

    /**
     * Get detailed memory information including warnings.
     *
     * @return array<string, mixed>
     */
    public function getMemoryInfo(): array
    {
        $usage = $this->getMemoryUsage();
        $usagePercentage = $usage['usage_percentage'];

        return [
            'usage_percentage' => $usagePercentage,
            'available_mb' => $usage['limit_mb'] !== 'unlimited' ?
                max(0, $usage['limit_mb'] - $usage['current_mb']) : 'unlimited',
            'warning_threshold_reached' => $usagePercentage >= self::MEMORY_WARNING_THRESHOLD,
            'critical_threshold_reached' => $usagePercentage >= self::MEMORY_CRITICAL_THRESHOLD,
            'recommendations' => $this->getMemoryRecommendations($usagePercentage),
        ];
    }

    /**
     * Check if memory usage is near the limit.
     */
    public function isNearMemoryLimit(): bool
    {
        $usage = $this->getMemoryUsage();

        return $usage['usage_percentage'] >= self::MEMORY_WARNING_THRESHOLD;
    }

    /**
     * Process large file batch with memory management.
     *
     * @param  array<array<string, mixed>>  $files
     * @return array<string, mixed>
     */
    public function processLargeFileBatch(int $generationId, array $files): array
    {
        $startMemory = memory_get_usage();
        $processedFiles = [];

        foreach ($files as $fileData) {
            // Check memory before processing each file
            if ($this->isNearMemoryLimit()) {
                $this->cleanupMemory();
            }

            $beforeFileMemory = memory_get_usage();

            // Process file based on size
            $fileSize = strlen((string) $fileData['content']);
            if ($fileSize > self::LARGE_FILE_THRESHOLD) {
                $result = $this->processLargeFileStreaming($fileData);
            } else {
                $result = $this->processFileInMemory($fileData);
            }

            $afterFileMemory = memory_get_usage();

            $processedFiles[] = [
                'filename' => $fileData['filename'],
                'size' => $fileSize,
                'memory_used' => $afterFileMemory - $beforeFileMemory,
                'processing_method' => $fileSize > self::LARGE_FILE_THRESHOLD ? 'streaming' : 'in_memory',
                'result' => $result,
            ];

            // Force cleanup after large files
            if ($fileSize > self::LARGE_FILE_THRESHOLD) {
                unset($fileData, $result);
                $this->forceGarbageCollection();
            }
        }

        return [
            'generation_id' => $generationId,
            'processed_files' => $processedFiles,
            'total_memory_used' => memory_get_usage() - $startMemory,
            'files_processed' => count($files),
        ];
    }

    /**
     * Generate color variants with memory management.
     *
     * @param  array<string>  $colorSchemes
     * @return array<array<string, mixed>>
     */
    public function generateColorVariantsWithMemoryManagement(int $logoId, array $colorSchemes): array
    {
        $variants = [];

        foreach ($colorSchemes as $colorScheme) {
            $startMemory = memory_get_usage();
            $startTime = microtime(true);

            // Check memory before processing
            if ($this->isNearMemoryLimit()) {
                $this->cleanupMemory();
            }

            // Simulate color variant generation (in real app this would call AI service)
            $variantData = $this->generateSingleColorVariant($logoId, $colorScheme);

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            $variants[] = [
                'logo_id' => $logoId,
                'color_scheme' => $colorScheme,
                'memory_used' => $endMemory - $startMemory,
                'processing_time' => ($endTime - $startTime) * 1000, // Convert to milliseconds
                'data' => $variantData,
            ];

            // Cleanup after each variant
            unset($variantData);
        }

        // Final cleanup
        $this->cleanupMemory();

        return $variants;
    }

    /**
     * Stream process large file with minimal memory usage.
     *
     * @return array<string, mixed>
     */
    public function streamProcessLargeFile(string $filePath): array
    {
        $startMemory = memory_get_usage();
        $fileSize = filesize($filePath);
        $processed = false;
        $chunks = 0;

        if ($fileSize === false || ! file_exists($filePath)) {
            return [
                'processed' => false,
                'error' => 'File not found or inaccessible',
                'memory_efficient' => false,
            ];
        }

        try {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                throw new \Exception('Unable to open file for reading');
            }

            while (! feof($handle)) {
                $chunk = fread($handle, self::STREAMING_CHUNK_SIZE);
                if ($chunk === false) {
                    break;
                }

                // Process chunk (simulate processing)
                $this->processFileChunk($chunk);
                $chunks++;

                // Check memory periodically and cleanup if needed
                if ($chunks % 100 === 0 && $this->isNearMemoryLimit()) {
                    $this->cleanupMemory();
                }
            }

            fclose($handle);
            $processed = true;

        } catch (\Exception $e) {
            Log::error('Stream processing failed', ['error' => $e->getMessage()]);
        }

        $endMemory = memory_get_usage();
        $memoryIncrease = $endMemory - $startMemory;

        return [
            'processed' => $processed,
            'file_size' => $fileSize,
            'memory_used' => $memoryIncrease,
            'memory_efficient' => $memoryIncrease < ($fileSize * 0.1), // Used less than 10% of file size
            'chunks_processed' => $chunks,
        ];
    }

    /**
     * Force garbage collection and return statistics.
     *
     * @return array<string, mixed>
     */
    public function forceGarbageCollection(): array
    {
        $beforeMemory = memory_get_usage();

        // Force garbage collection
        $cycles = gc_collect_cycles();
        gc_mem_caches();

        $afterMemory = memory_get_usage();
        $memoryFreed = max(0, $beforeMemory - $afterMemory);

        return [
            'memory_freed' => $memoryFreed,
            'memory_freed_mb' => round($memoryFreed / 1024 / 1024, 2),
            'cycles_collected' => $cycles,
            'before_memory_mb' => round($beforeMemory / 1024 / 1024, 2),
            'after_memory_mb' => round($afterMemory / 1024 / 1024, 2),
        ];
    }

    /**
     * Cleanup memory and temporary resources.
     */
    public function cleanupMemory(): void
    {
        // Clear any temporary caches
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        // Force garbage collection
        $this->forceGarbageCollection();

        // Clear variable caches
        clearstatcache();
    }

    /**
     * Start memory tracking for a generation session.
     */
    public function startMemoryTracking(int $generationId): void
    {
        $this->memoryTrackingSessions[$generationId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
            'peak_memory' => memory_get_usage(),
            'memory_snapshots' => [],
        ];
    }

    /**
     * Stop memory tracking and return report.
     *
     * @return array<string, mixed>
     */
    public function stopMemoryTracking(int $generationId): array
    {
        if (! isset($this->memoryTrackingSessions[$generationId])) {
            return [
                'error' => 'Memory tracking session not found',
                'generation_id' => $generationId,
            ];
        }

        $session = $this->memoryTrackingSessions[$generationId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        $duration = ($endTime - $session['start_time']) * 1000; // milliseconds
        $memoryUsed = $endMemory - $session['start_memory'];
        $averageMemory = ! empty($session['memory_snapshots'])
            ? array_sum($session['memory_snapshots']) / count($session['memory_snapshots'])
            : $endMemory;

        if (isset($this->memoryTrackingSessions[$generationId])) {
            /** @phpstan-ignore-next-line - Unsetting array element is valid for session cleanup */
            unset($this->memoryTrackingSessions[$generationId]);
        }

        return [
            'generation_id' => $generationId,
            'total_duration_ms' => round($duration, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'average_memory_mb' => round($averageMemory / 1024 / 1024, 2),
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'memory_efficient' => $memoryUsed < (50 * 1024 * 1024), // Less than 50MB used
            'snapshots_count' => count($session['memory_snapshots']),
        ];
    }

    /**
     * Process logos with size-based optimization strategy.
     *
     * @param  array<array<string, mixed>>  $logos
     * @return array<string, mixed>
     */
    public function processLogosWithSizeOptimization(array $logos): array
    {
        $totalSize = array_sum(array_column($logos, 'size'));
        $strategy = $totalSize > self::LARGE_FILE_THRESHOLD * 2 ? 'streaming' : 'in_memory';

        $startMemory = memory_get_usage();
        $processedCount = 0;

        foreach ($logos as $logo) {
            if ($strategy === 'streaming') {
                $this->processLargeFileStreaming(['content' => $logo['content']]);
            } else {
                $this->processFileInMemory(['content' => $logo['content']]);
            }
            $processedCount++;

            // Cleanup periodically for streaming strategy
            if ($strategy === 'streaming' && $processedCount % 5 === 0) {
                $this->cleanupMemory();
            }
        }

        $endMemory = memory_get_usage();

        return [
            'strategy' => $strategy,
            'total_size' => $totalSize,
            'logos_processed' => $processedCount,
            'memory_used' => $endMemory - $startMemory,
            'memory_used_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
        ];
    }

    /**
     * Get memory limit from PHP configuration.
     */
    private function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') {
            return 0; // Unlimited
        }

        // Convert to bytes
        $value = (int) $memoryLimit;
        $unit = strtolower(substr($memoryLimit, -1));

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Get memory usage recommendations based on current usage.
     *
     * @return array<string>
     */
    private function getMemoryRecommendations(float $usagePercentage): array
    {
        $recommendations = [];

        if ($usagePercentage >= self::MEMORY_CRITICAL_THRESHOLD) {
            $recommendations[] = 'Critical: Memory usage is very high. Consider processing fewer files concurrently.';
            $recommendations[] = 'Enable streaming mode for large files.';
            $recommendations[] = 'Increase PHP memory limit if possible.';
        } elseif ($usagePercentage >= self::MEMORY_WARNING_THRESHOLD) {
            $recommendations[] = 'Warning: Memory usage is high. Monitor closely.';
            $recommendations[] = 'Consider enabling garbage collection.';
        } else {
            $recommendations[] = 'Memory usage is within normal range.';
        }

        return $recommendations;
    }

    /**
     * Process file in memory for small files.
     *
     * @param  array<string, mixed>  $fileData
     * @return array<string, mixed>
     */
    private function processFileInMemory(array $fileData): array
    {
        $content = $fileData['content'];

        // Simulate file processing (e.g., SVG manipulation, compression)
        $processedContent = strtoupper((string) $content); // Simple transformation

        return [
            'original_size' => strlen((string) $content),
            'processed_size' => strlen($processedContent),
            'method' => 'in_memory',
        ];
    }

    /**
     * Process large file using streaming approach.
     *
     * @param  array<string, mixed>  $fileData
     * @return array<string, mixed>
     */
    private function processLargeFileStreaming(array $fileData): array
    {
        $content = $fileData['content'];
        $originalSize = strlen((string) $content);

        // Process in chunks to minimize memory usage
        $chunks = str_split((string) $content, self::STREAMING_CHUNK_SIZE);
        $processedSize = 0;

        foreach ($chunks as $chunk) {
            $processed = $this->processFileChunk($chunk);
            $processedSize += strlen($processed);

            // Cleanup chunk from memory
            unset($chunk, $processed);
        }

        return [
            'original_size' => $originalSize,
            'processed_size' => $processedSize,
            'method' => 'streaming',
            'chunks' => count($chunks),
        ];
    }

    /**
     * Process a single file chunk.
     */
    private function processFileChunk(string $chunk): string
    {
        // Simulate chunk processing (e.g., color replacement, optimization)
        return strtoupper($chunk);
    }

    /**
     * Generate single color variant (mock implementation).
     *
     * @return array<string, mixed>
     */
    private function generateSingleColorVariant(int $logoId, string $colorScheme): array
    {
        // Simulate memory-intensive operation
        $dummyData = array_fill(0, 1000, "variant data for {$colorScheme}");

        $variant = [
            'logo_id' => $logoId,
            'color_scheme' => $colorScheme,
            'file_path' => "variants/{$logoId}/{$colorScheme}.svg",
            'processed_at' => now()->toISOString(),
        ];

        // Cleanup dummy data
        unset($dummyData);

        return $variant;
    }
}
