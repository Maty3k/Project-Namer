<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

/**
 * SVG color processor service.
 *
 * Handles parsing SVG files, detecting colors, and replacing them
 * with new color palettes while preserving structure and opacity.
 */
final class SvgColorProcessor
{
    /**
     * Parsed SVG document.
     */
    private ?DOMDocument $document = null;

    /**
     * Collection of errors encountered during processing.
     *
     * @var array<string>
     */
    private array $errors = [];

    /**
     * Detected colors from the SVG.
     *
     * @var array<string>
     */
    private array $detectedColors = [];

    /**
     * CSS color name to hex mapping.
     *
     * @var array<string, string>
     */
    private const CSS_COLORS = [
        'red' => '#FF0000',
        'green' => '#008000',
        'blue' => '#0000FF',
        'black' => '#000000',
        'white' => '#FFFFFF',
        'yellow' => '#FFFF00',
        'cyan' => '#00FFFF',
        'magenta' => '#FF00FF',
        'silver' => '#C0C0C0',
        'gray' => '#808080',
        'maroon' => '#800000',
        'olive' => '#808000',
        'lime' => '#00FF00',
        'aqua' => '#00FFFF',
        'teal' => '#008080',
        'navy' => '#000080',
        'fuchsia' => '#FF00FF',
        'purple' => '#800080',
        'orange' => '#FFA500',
        'brown' => '#A52A2A',
        'pink' => '#FFC0CB',
    ];

    /**
     * Parse an SVG string.
     */
    public function parseSvg(string $svg): bool
    {
        $this->reset();

        // Suppress warnings from malformed SVG
        $previousUseErrors = libxml_use_internal_errors(true);

        $this->document = new DOMDocument();
        $result = $this->document->loadXML($svg);

        // Get any errors that occurred
        $xmlErrors = libxml_get_errors();
        foreach ($xmlErrors as $error) {
            $this->errors[] = "XML Error: {$error->message}";
        }
        libxml_clear_errors();

        // Restore previous error handling
        libxml_use_internal_errors($previousUseErrors);

        if (! $result) {
            return false;
        }

        // Validate SVG root element
        $root = $this->document->documentElement;
        if ($root === null || $root->nodeName !== 'svg') {
            $this->errors[] = 'Invalid SVG: root element must be svg';
            return false;
        }

        // Detect colors immediately after successful parsing
        $this->detectColorsInternal();

        return true;
    }

    /**
     * Detect all colors in the parsed SVG.
     *
     * @return array<string>
     */
    public function detectColors(): array
    {
        return $this->detectedColors;
    }

    /**
     * Internal method to detect colors from the parsed document.
     */
    private function detectColorsInternal(): void
    {
        if ($this->document === null) {
            return;
        }

        $xpath = new DOMXPath($this->document);
        $elements = $xpath->query('//*[@fill or @stroke or @style or @stop-color]');

        if ($elements === false) {
            return;
        }

        $colors = [];

        foreach ($elements as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            // Check fill attribute
            if ($element->hasAttribute('fill')) {
                $fill = $element->getAttribute('fill');
                $color = $this->normalizeColor($fill);
                if ($color !== null) {
                    $colors[$color] = true;
                }
            }

            // Check stroke attribute
            if ($element->hasAttribute('stroke')) {
                $stroke = $element->getAttribute('stroke');
                $color = $this->normalizeColor($stroke);
                if ($color !== null) {
                    $colors[$color] = true;
                }
            }

            // Check stop-color attribute (for gradients)
            if ($element->hasAttribute('stop-color')) {
                $stopColor = $element->getAttribute('stop-color');
                $color = $this->normalizeColor($stopColor);
                if ($color !== null) {
                    $colors[$color] = true;
                }
            }

            // Check style attribute
            if ($element->hasAttribute('style')) {
                $style = $element->getAttribute('style');
                $styleColors = $this->extractColorsFromStyle($style);
                foreach ($styleColors as $color) {
                    $colors[$color] = true;
                }
            }
        }

        $this->detectedColors = array_keys($colors);
    }

    /**
     * Extract colors from a style attribute.
     *
     * @return array<string>
     */
    private function extractColorsFromStyle(string $style): array
    {
        $colors = [];

        // Match fill: #hex or fill: rgb() or fill: colorname
        if (preg_match_all('/(?:fill|stroke|stop-color)\s*:\s*([^;]+)/i', $style, $matches)) {
            foreach ($matches[1] as $colorValue) {
                $color = $this->normalizeColor(trim($colorValue));
                if ($color !== null) {
                    $colors[] = $color;
                }
            }
        }

        return $colors;
    }

    /**
     * Normalize a color value to uppercase hex format.
     */
    private function normalizeColor(string $color): ?string
    {
        $color = trim($color);

        // Ignore special values
        if (in_array(strtolower($color), ['none', 'transparent', 'inherit', 'currentcolor'], true)) {
            return null;
        }

        // Skip url() references (gradients, patterns)
        if (str_starts_with($color, 'url(')) {
            return null;
        }

        // Handle hex colors
        if (preg_match('/^#([0-9A-Fa-f]{6})$/i', $color, $matches)) {
            return '#' . strtoupper($matches[1]);
        }

        // Handle RGB format
        if (preg_match('/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i', $color, $matches)) {
            $r = (int) $matches[1];
            $g = (int) $matches[2];
            $b = (int) $matches[3];
            return sprintf('#%02X%02X%02X', $r, $g, $b);
        }

        // Handle CSS color names
        $lowerColor = strtolower($color);
        if (isset(self::CSS_COLORS[$lowerColor])) {
            return self::CSS_COLORS[$lowerColor];
        }

        return null;
    }

    /**
     * Replace colors in the SVG with a new palette.
     *
     * @param array<string, string> $palette
     */
    public function replaceColors(array $palette): string
    {
        if ($this->document === null) {
            return '';
        }

        // Create color mapping
        $colorMapping = $this->createColorMapping($palette);

        // Clone document to preserve original
        $newDocument = clone $this->document;

        // Replace colors in the cloned document
        $this->replaceColorsInDocument($newDocument, $colorMapping);

        return $newDocument->saveXML() ?: '';
    }

    /**
     * Create intelligent color mapping based on luminance.
     *
     * @param array<string, string> $palette
     * @return array<string, string>
     */
    public function createColorMapping(array $palette): array
    {
        $mapping = [];

        // Sort detected colors by luminance (dark to light)
        $colorsWithLuminance = [];
        foreach ($this->detectedColors as $color) {
            $colorsWithLuminance[$color] = $this->calculateLuminance($color);
        }
        asort($colorsWithLuminance);

        // Get palette colors sorted by luminance (dark to light)
        $paletteColors = [
            $palette['primary'],   // darkest
            $palette['secondary'], // medium-dark
            $palette['accent'],    // medium-light
            $palette['neutral'],   // lightest
        ];

        // Sort palette by actual luminance to ensure correct ordering
        usort($paletteColors, fn($a, $b) => $this->calculateLuminance($a) <=> $this->calculateLuminance($b));

        // Map detected colors to palette colors based on luminance order
        $detectedColorsSorted = array_keys($colorsWithLuminance);
        $colorCount = count($detectedColorsSorted);
        $paletteCount = count($paletteColors);

        if ($colorCount === 1) {
            // Single color maps to primary
            $mapping[$detectedColorsSorted[0]] = $paletteColors[0];
        } else {
            for ($i = 0; $i < $colorCount; $i++) {
                if ($i === 0) {
                    // Darkest color maps to darkest palette color
                    $mapping[$detectedColorsSorted[$i]] = $paletteColors[0];
                } elseif ($i === $colorCount - 1) {
                    // Lightest color maps to lightest palette color
                    $mapping[$detectedColorsSorted[$i]] = $paletteColors[$paletteCount - 1];
                } else {
                    // Middle colors map proportionally
                    $paletteIndex = min(
                        (int) round($i * ($paletteCount - 1) / ($colorCount - 1)),
                        $paletteCount - 1
                    );
                    $mapping[$detectedColorsSorted[$i]] = $paletteColors[$paletteIndex];
                }
            }
        }

        return $mapping;
    }

    /**
     * Calculate luminance of a hex color.
     */
    private function calculateLuminance(string $hexColor): float
    {
        // Remove # if present
        $hex = ltrim($hexColor, '#');

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // Calculate relative luminance using ITU-R BT.709
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Replace colors in a DOM document.
     *
     * @param array<string, string> $colorMapping
     */
    private function replaceColorsInDocument(DOMDocument $document, array $colorMapping): void
    {
        $xpath = new DOMXPath($document);
        $elements = $xpath->query('//*[@fill or @stroke or @style or @stop-color]');

        if ($elements === false) {
            return;
        }

        foreach ($elements as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            // Replace fill attribute
            if ($element->hasAttribute('fill')) {
                $fill = $element->getAttribute('fill');
                $normalizedColor = $this->normalizeColor($fill);
                if ($normalizedColor !== null && isset($colorMapping[$normalizedColor])) {
                    $element->setAttribute('fill', $colorMapping[$normalizedColor]);
                }
            }

            // Replace stroke attribute
            if ($element->hasAttribute('stroke')) {
                $stroke = $element->getAttribute('stroke');
                $normalizedColor = $this->normalizeColor($stroke);
                if ($normalizedColor !== null && isset($colorMapping[$normalizedColor])) {
                    $element->setAttribute('stroke', $colorMapping[$normalizedColor]);
                }
            }

            // Replace stop-color attribute
            if ($element->hasAttribute('stop-color')) {
                $stopColor = $element->getAttribute('stop-color');
                $normalizedColor = $this->normalizeColor($stopColor);
                if ($normalizedColor !== null && isset($colorMapping[$normalizedColor])) {
                    $element->setAttribute('stop-color', $colorMapping[$normalizedColor]);
                }
            }

            // Replace colors in style attribute
            if ($element->hasAttribute('style')) {
                $style = $element->getAttribute('style');
                $newStyle = $this->replaceColorsInStyle($style, $colorMapping);
                $element->setAttribute('style', $newStyle);
            }
        }
    }

    /**
     * Replace colors in a style attribute.
     *
     * @param array<string, string> $colorMapping
     */
    private function replaceColorsInStyle(string $style, array $colorMapping): string
    {
        // Replace fill, stroke, and stop-color properties
        $newStyle = preg_replace_callback(
            '/(?:fill|stroke|stop-color)\s*:\s*([^;]+)/i',
            function ($matches) use ($colorMapping) {
                $property = strtolower(trim(explode(':', $matches[0])[0]));
                $colorValue = trim($matches[1]);
                $normalizedColor = $this->normalizeColor($colorValue);

                if ($normalizedColor !== null && isset($colorMapping[$normalizedColor])) {
                    return $property . ': ' . $colorMapping[$normalizedColor];
                }

                return $matches[0];
            },
            $style
        );

        return $newStyle;
    }

    /**
     * Process SVG with a new color palette.
     *
     * @param array<string, string> $palette
     * @return array<string, mixed>
     */
    public function processSvg(string $svg, array $palette): array
    {
        $success = $this->parseSvg($svg);

        if (! $success) {
            return [
                'success' => false,
                'errors' => $this->errors,
            ];
        }

        $newSvg = $this->replaceColors($palette);

        return [
            'success' => true,
            'svg' => $newSvg,
        ];
    }

    /**
     * Get errors encountered during processing.
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Reset the processor state.
     */
    private function reset(): void
    {
        $this->document = null;
        $this->errors = [];
        $this->detectedColors = [];
    }
}