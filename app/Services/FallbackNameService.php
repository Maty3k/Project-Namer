<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Fallback name generation service for when AI services are unavailable.
 * Provides creative name suggestions using pattern-based generation.
 */
class FallbackNameService
{
    private const PREFIXES = [
        'creative' => ['Spark', 'Bright', 'Echo', 'Flow', 'Wild', 'Bold', 'Nova', 'Zen', 'Arc', 'Flux', 'Edge', 'Glow', 'Rise', 'Bloom', 'Wave'],
        'professional' => ['Prime', 'Atlas', 'Meridian', 'Sterling', 'Pinnacle', 'Vertex', 'Apex', 'Compass', 'Cardinal', 'Cornerstone', 'Foundation', 'Keystone'],
        'brandable' => ['Zeno', 'Axiom', 'Nexus', 'Vibe', 'Flux', 'Echo', 'Sync', 'Pulse', 'Shift', 'Spark', 'Orbit', 'Link', 'Mint', 'Dash', 'Edge'],
        'tech-focused' => ['Code', 'Pixel', 'Logic', 'Neural', 'Quantum', 'Vector', 'Matrix', 'Cyber', 'Binary', 'Forge', 'Stack', 'Node', 'Hash', 'Sync'],
    ];

    private const SUFFIXES = [
        'creative' => ['Studio', 'Works', 'House', 'Collective', 'Atelier', 'Workshop', 'Gallery', 'Haven', 'Nest', 'Space'],
        'professional' => ['Group', 'Partners', 'Associates', 'Consulting', 'Advisory', 'Capital', 'Ventures', 'Holdings', 'Corporation', 'Enterprises'],
        'brandable' => ['ly', 'fy', 'io', 'co', 'go', 'me', 'up', 'it', 'ai', 'ex', 'ix', 'ox', 'us', 'em', 'en'],
        'tech-focused' => ['Labs', 'Core', 'Hub', 'Cloud', 'Stack', 'Forge', 'Works', 'Engine', 'Platform', 'Network'],
    ];

    private const CONNECTORS = ['', '-', '.', ''];

    /**
     * Generate creative business names using pattern-based approach.
     *
     * @return array<string>
     */
    public function generateNames(string $idea, string $mode = 'creative', int $count = 10): array
    {
        $names = [];
        $prefixes = self::PREFIXES[$mode] ?? self::PREFIXES['creative'];
        $suffixes = self::SUFFIXES[$mode] ?? self::SUFFIXES['creative'];

        // Extract keywords from the idea
        $keywords = $this->extractKeywords($idea);

        // Generate names using different patterns
        while (count($names) < $count) {
            $name = $this->generateName($prefixes, $suffixes, $keywords, $mode);
            if (! in_array($name, $names)) {
                $names[] = $name;
            }
        }

        return array_slice($names, 0, $count);
    }

    /**
     * Generate a single name using various patterns.
     *
     * @param  array<string>  $prefixes
     * @param  array<string>  $suffixes
     * @param  array<string>  $keywords
     */
    private function generateName(array $prefixes, array $suffixes, array $keywords, string $mode): string
    {
        $patterns = [];

        // If we have keywords, use them more frequently
        if (! empty($keywords)) {
            $patterns = [
                // Pure keyword
                fn () => ucfirst($keywords[array_rand($keywords)]),

                // Keyword + Suffix
                fn () => ucfirst($keywords[array_rand($keywords)]).$suffixes[array_rand($suffixes)],

                // Prefix + Keyword
                fn () => $prefixes[array_rand($prefixes)].ucfirst($keywords[array_rand($keywords)]),

                // Modified keyword
                fn () => $this->modifyKeyword($keywords[array_rand($keywords)], $mode),

                // Keyword combinations
                fn () => count($keywords) > 1 ? ucfirst($keywords[0]).ucfirst($keywords[1]) : ucfirst($keywords[0]).$suffixes[array_rand($suffixes)],
            ];
        }

        // Add generic patterns as backup
        $patterns = array_merge($patterns, [
            // Prefix + Suffix
            fn () => $prefixes[array_rand($prefixes)].$suffixes[array_rand($suffixes)],

            // Compound words
            fn () => $prefixes[array_rand($prefixes)].self::CONNECTORS[array_rand(self::CONNECTORS)].$prefixes[array_rand($prefixes)],
        ]);

        $pattern = $patterns[array_rand($patterns)];
        $result = $pattern();

        // Ensure reasonable length
        return strlen($result) > 20 ? substr($result, 0, 15) : $result;
    }

    /**
     * Extract meaningful keywords from business idea.
     *
     * @return array<string>
     */
    private function extractKeywords(string $idea): array
    {
        // Remove common words and business jargon
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'service', 'services', 'company', 'business', 'platform', 'application', 'app', 'website', 'system', 'solution', 'tool', 'software'];

        // Clean and split the input
        $cleanIdea = preg_replace('/[^a-zA-Z\s]/', ' ', $idea);
        $words = array_filter(
            array_map('trim', explode(' ', strtolower((string) $cleanIdea))),
            fn ($word) => strlen($word) > 2 && ! in_array($word, $stopWords) && ! is_numeric($word)
        );

        // Prioritize longer, more meaningful words
        $keywords = array_values($words);
        usort($keywords, fn ($a, $b) => strlen($b) - strlen($a));

        return array_slice($keywords, 0, 3); // Take top 3 most meaningful keywords
    }

    /**
     * Modify a keyword to make it more brandable.
     */
    private function modifyKeyword(string $keyword, string $mode): string
    {
        $modifications = [
            // Capitalize
            fn ($w) => ucfirst((string) $w),

            // Add suffix
            fn ($w) => ucfirst((string) $w).(self::SUFFIXES[$mode] ?? self::SUFFIXES['creative'])[array_rand(self::SUFFIXES[$mode] ?? self::SUFFIXES['creative'])],

            // Remove vowels for tech feel
            fn ($w) => $mode === 'tech-focused' ? ucfirst((string) preg_replace('/[aeiou]/', '', (string) $w)).'r' : ucfirst((string) $w),

            // Add 'ly' ending
            fn ($w) => ucfirst((string) $w).'ly',

            // Truncate and add 'io'
            fn ($w) => ucfirst(substr((string) $w, 0, 4)).'io',
        ];

        $modifier = $modifications[array_rand($modifications)];
        $result = $modifier($keyword);

        // Ensure result is reasonable length
        return strlen($result) > 15 ? substr($result, 0, 12) : $result;
    }
}
