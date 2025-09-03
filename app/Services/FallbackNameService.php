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
        'creative' => ['Pixel', 'Nova', 'Spark', 'Flow', 'Wave', 'Echo', 'Zen', 'Flux', 'Aura', 'Vibe'],
        'professional' => ['Prime', 'Elite', 'Core', 'Pro', 'Summit', 'Peak', 'Alpha', 'Omega', 'Strategic', 'Vision'],
        'brandable' => ['Zeph', 'Axio', 'Lumi', 'Velo', 'Koda', 'Nyx', 'Orb', 'Sync', 'Loop', 'Grid'],
        'tech-focused' => ['Byte', 'Code', 'Data', 'Logic', 'Binary', 'Neural', 'Quantum', 'Digital', 'Cyber', 'Tech'],
    ];

    private const SUFFIXES = [
        'creative' => ['Studio', 'Lab', 'Works', 'Forge', 'Craft', 'House', 'Space', 'Hub', 'Zone', 'Base'],
        'professional' => ['Solutions', 'Consulting', 'Partners', 'Group', 'Associates', 'Corp', 'Ventures', 'Capital', 'Holdings', 'Systems'],
        'brandable' => ['ly', 'fy', 'io', 'co', 'go', 'me', 'up', 'it', 'ai', 'x'],
        'tech-focused' => ['Tech', 'Labs', 'Systems', 'Logic', 'Core', 'Net', 'Web', 'Cloud', 'Apps', 'Dev'],
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
        $patterns = [
            // Prefix + Suffix
            fn () => $prefixes[array_rand($prefixes)].$suffixes[array_rand($suffixes)],

            // Keyword + Suffix
            fn () => (! empty($keywords) ? ucfirst((string) $keywords[array_rand($keywords)]) : $prefixes[array_rand($prefixes)]).$suffixes[array_rand($suffixes)],

            // Prefix + Keyword
            fn () => $prefixes[array_rand($prefixes)].(! empty($keywords) ? ucfirst((string) $keywords[array_rand($keywords)]) : $suffixes[array_rand($suffixes)]),

            // Compound words
            fn () => $prefixes[array_rand($prefixes)].self::CONNECTORS[array_rand(self::CONNECTORS)].$prefixes[array_rand($prefixes)],

            // Modified keywords
            fn () => ! empty($keywords) ? $this->modifyKeyword($keywords[array_rand($keywords)], $mode) : $prefixes[array_rand($prefixes)].$suffixes[array_rand($suffixes)],
        ];

        $pattern = $patterns[array_rand($patterns)];

        return $pattern();
    }

    /**
     * Extract meaningful keywords from business idea.
     *
     * @return array<string>
     */
    private function extractKeywords(string $idea): array
    {
        // Remove common words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can'];

        $words = array_filter(
            array_map('trim', explode(' ', strtolower($idea))),
            fn ($word) => strlen($word) > 2 && ! in_array($word, $stopWords) && ! is_numeric($word)
        );

        return array_values($words);
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
