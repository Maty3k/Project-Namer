<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Share;
use App\Models\ShareAccess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ShareMonitoringService handles monitoring and logging of share access patterns.
 *
 * Provides functionality for detecting suspicious access patterns,
 * logging security events, and generating monitoring alerts.
 */
final class ShareMonitoringService
{
    private const SUSPICIOUS_ACCESS_THRESHOLD = 10;

    private const RATE_LIMIT_WINDOW = 300; // 5 minutes

    private const HIGH_VOLUME_THRESHOLD = 100;

    /**
     * Monitor and log share access with security checks.
     *
     * @param  array<string, mixed>  $accessData
     */
    public function monitorAccess(Share $share, array $accessData): void
    {
        $ipAddress = $accessData['ip_address'] ?? 'unknown';
        $userAgent = $accessData['user_agent'] ?? 'unknown';

        // Log the access
        $this->logAccess($share, $accessData);

        // Check for suspicious patterns
        $this->checkSuspiciousActivity($share, $ipAddress, $userAgent);

        // Monitor for high-volume access
        $this->monitorHighVolumeAccess($share);

        // Track access patterns
        $this->trackAccessPatterns($share, $accessData);
    }

    /**
     * Log access attempt with contextual information.
     *
     * @param  array<string, mixed>  $accessData
     */
    private function logAccess(Share $share, array $accessData): void
    {
        Log::info('Share access recorded', [
            'share_uuid' => $share->uuid,
            'share_type' => $share->share_type,
            'user_id' => $share->user_id,
            'ip_address' => $accessData['ip_address'] ?? null,
            'user_agent' => $accessData['user_agent'] ?? null,
            'referrer' => $accessData['referrer'] ?? null,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check for suspicious access patterns from IP addresses.
     */
    private function checkSuspiciousActivity(Share $share, string $ipAddress, string $userAgent): void
    {
        $cacheKey = "suspicious_access:{$ipAddress}:{$share->uuid}";
        $accessCount = Cache::get($cacheKey, 0);

        // Increment access count
        Cache::put($cacheKey, $accessCount + 1, now()->addMinutes(self::RATE_LIMIT_WINDOW / 60));

        // Check for suspicious activity
        if ($accessCount >= self::SUSPICIOUS_ACCESS_THRESHOLD) {
            $this->logSuspiciousActivity($share, $ipAddress, $userAgent, $accessCount);
        }

        // Additional checks for automated access
        if ($this->detectAutomatedAccess($userAgent)) {
            $this->logAutomatedAccess($share, $ipAddress, $userAgent);
        }
    }

    /**
     * Monitor for high-volume access to shares.
     */
    private function monitorHighVolumeAccess(Share $share): void
    {
        $cacheKey = "high_volume_access:{$share->uuid}";
        $hourlyCount = Cache::get($cacheKey, 0);

        // Increment hourly count
        Cache::put($cacheKey, $hourlyCount + 1, now()->addHour());

        // Check for high volume
        if ($hourlyCount >= self::HIGH_VOLUME_THRESHOLD) {
            $this->logHighVolumeAccess($share, $hourlyCount);
        }
    }

    /**
     * Track and analyze access patterns for insights.
     *
     * @param  array<string, mixed>  $accessData
     */
    private function trackAccessPatterns(Share $share, array $accessData): void
    {
        $patterns = [
            'geographic' => $this->analyzeGeographicPattern($accessData['ip_address'] ?? null),
            'temporal' => $this->analyzeTemporalPattern(),
            'referrer' => $this->analyzeReferrerPattern($accessData['referrer'] ?? null),
            'user_agent' => $this->analyzeUserAgentPattern($accessData['user_agent'] ?? null),
        ];

        // Store patterns for analysis
        Cache::put("access_patterns:{$share->uuid}:".date('Y-m-d-H'), $patterns, now()->addDay());
    }

    /**
     * Log suspicious activity detected.
     */
    private function logSuspiciousActivity(Share $share, string $ipAddress, string $userAgent, int $accessCount): void
    {
        Log::warning('Suspicious share access detected', [
            'event' => 'suspicious_access',
            'share_uuid' => $share->uuid,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'access_count' => $accessCount,
            'threshold' => self::SUSPICIOUS_ACCESS_THRESHOLD,
            'window_minutes' => self::RATE_LIMIT_WINDOW / 60,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log automated access attempt.
     */
    private function logAutomatedAccess(Share $share, string $ipAddress, string $userAgent): void
    {
        Log::warning('Automated share access detected', [
            'event' => 'automated_access',
            'share_uuid' => $share->uuid,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log high-volume access to share.
     */
    private function logHighVolumeAccess(Share $share, int $hourlyCount): void
    {
        Log::notice('High-volume share access detected', [
            'event' => 'high_volume_access',
            'share_uuid' => $share->uuid,
            'hourly_count' => $hourlyCount,
            'threshold' => self::HIGH_VOLUME_THRESHOLD,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Detect if access appears to be from automated tools.
     */
    private function detectAutomatedAccess(string $userAgent): bool
    {
        $botIndicators = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python-requests', 'axios', 'http', 'postman',
        ];

        $userAgentLower = strtolower($userAgent);

        foreach ($botIndicators as $indicator) {
            if (str_contains($userAgentLower, $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze geographic access pattern.
     *
     * @return array<string, mixed>
     */
    private function analyzeGeographicPattern(?string $ipAddress): array
    {
        if (! $ipAddress) {
            return ['country' => 'unknown', 'region' => 'unknown'];
        }

        // Simple IP analysis - in production you'd use a GeoIP service
        $isLocal = str_starts_with($ipAddress, '192.168.') ||
                   str_starts_with($ipAddress, '10.') ||
                   str_starts_with($ipAddress, '172.') ||
                   $ipAddress === '127.0.0.1';

        return [
            'is_local' => $isLocal,
            'ip_classification' => $isLocal ? 'private' : 'public',
        ];
    }

    /**
     * Analyze temporal access pattern.
     *
     * @return array<string, mixed>
     */
    private function analyzeTemporalPattern(): array
    {
        $now = now();

        return [
            'hour' => $now->hour,
            'day_of_week' => $now->dayOfWeek,
            'is_weekend' => $now->isWeekend(),
            'timezone' => $now->timezoneName,
        ];
    }

    /**
     * Analyze referrer pattern.
     *
     * @return array<string, mixed>
     */
    private function analyzeReferrerPattern(?string $referrer): array
    {
        if (! $referrer) {
            return ['type' => 'direct', 'domain' => null];
        }

        $parsedUrl = parse_url($referrer);
        $domain = $parsedUrl['host'] ?? null;

        $socialDomains = ['facebook.com', 'twitter.com', 'linkedin.com', 'instagram.com'];
        $searchDomains = ['google.com', 'bing.com', 'yahoo.com', 'duckduckgo.com'];

        $type = 'other';
        if ($domain && in_array($domain, $socialDomains)) {
            $type = 'social';
        } elseif ($domain && in_array($domain, $searchDomains)) {
            $type = 'search';
        }

        return [
            'type' => $type,
            'domain' => $domain,
            'full_referrer' => $referrer,
        ];
    }

    /**
     * Analyze user agent pattern.
     *
     * @return array<string, mixed>
     */
    private function analyzeUserAgentPattern(?string $userAgent): array
    {
        if (! $userAgent) {
            return ['browser' => 'unknown', 'os' => 'unknown', 'is_mobile' => false];
        }

        $isMobile = str_contains(strtolower($userAgent), 'mobile') ||
                   str_contains(strtolower($userAgent), 'android') ||
                   str_contains(strtolower($userAgent), 'iphone');

        // Simple browser detection
        $browser = 'unknown';
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'edge';
        }

        return [
            'browser' => $browser,
            'is_mobile' => $isMobile,
            'user_agent' => $userAgent,
        ];
    }

    /**
     * Get monitoring summary for a share.
     *
     * @return array<string, mixed>
     */
    public function getMonitoringSummary(Share $share): array
    {
        $recentAccesses = ShareAccess::where('share_id', $share->id)
            ->where('accessed_at', '>=', now()->subDays(7))
            ->orderBy('accessed_at', 'desc')
            ->limit(100)
            ->get();

        return [
            'total_accesses' => $share->view_count,
            'recent_accesses' => $recentAccesses->count(),
            'unique_ips' => $recentAccesses->pluck('ip_address')->unique()->count(),
            'top_referrers' => $this->getTopReferrers($recentAccesses),
            'hourly_distribution' => $this->getHourlyDistribution($recentAccesses),
            'suspicious_activity_detected' => $this->hasSuspiciousActivity($share),
        ];
    }

    /**
     * Get top referrers from recent accesses.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, ShareAccess>  $accesses
     * @return array<string, int>
     */
    private function getTopReferrers($accesses): array
    {
        return $accesses->whereNotNull('referrer')
            ->groupBy('referrer')
            ->map->count()
            ->sortDesc()
            ->take(5)
            ->toArray();
    }

    /**
     * Get hourly access distribution.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, ShareAccess>  $accesses
     * @return array<int, int>
     */
    private function getHourlyDistribution($accesses): array
    {
        $distribution = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $distribution[$hour] = 0;
        }

        foreach ($accesses as $access) {
            $hour = $access->accessed_at->hour;
            $distribution[$hour]++;
        }

        return $distribution;
    }

    /**
     * Check if share has suspicious activity.
     */
    private function hasSuspiciousActivity(Share $share): bool
    {
        return Cache::has("suspicious_access:*:{$share->uuid}");
    }
}
