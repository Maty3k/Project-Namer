<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * AI Accessibility Service.
 *
 * Provides utilities and helpers for improving accessibility in AI interfaces,
 * including screen reader announcements, keyboard navigation, and WCAG compliance.
 */
class AIAccessibilityService
{
    /**
     * Generate accessible progress announcement.
     */
    public function generateProgressAnnouncement(string $status, ?int $percentage = null, ?string $modelName = null): string
    {
        $model = $modelName ? "using {$modelName}" : '';

        return match ($status) {
            'starting' => "AI name generation started {$model}. Please wait.",
            'processing' => $percentage !== null
                ? "AI generation in progress {$model}. {$percentage}% complete."
                : "AI generation in progress {$model}.",
            'completed' => "AI name generation completed successfully {$model}. Results are now available.",
            'error' => "AI name generation failed {$model}. Please check the error message and try again.",
            'cancelled' => "AI name generation was cancelled {$model}.",
            default => "AI generation status: {$status} {$model}.",
        };
    }

    /**
     * Generate accessible model description for screen readers.
     *
     * @param  array<string, mixed>  $model
     */
    public function generateModelDescription(array $model): string
    {
        $description = "{$model['name']} by {$model['provider']}. ";
        $description .= $model['description'].'. ';

        if (isset($model['cost_per_1k_tokens'])) {
            $cost = number_format($model['cost_per_1k_tokens'], 4);
            $description .= "Cost: {$cost} dollars per thousand tokens. ";
        }

        if (isset($model['max_tokens'])) {
            $description .= "Maximum tokens: {$model['max_tokens']}. ";
        }

        $status = $this->getModelAccessibilityStatus($model);
        $description .= "Status: {$status}.";

        return trim($description);
    }

    /**
     * Generate accessible form validation messages.
     *
     * @param  array<int, string>  $errors
     */
    public function generateValidationMessage(string $field, array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        $fieldLabel = $this->getFieldLabel($field);
        $errorCount = count($errors);

        if ($errorCount === 1) {
            return "{$fieldLabel} has an error: {$errors[0]}";
        }

        $errorList = implode(', ', array_slice($errors, 0, -1)).', and '.end($errors);

        return "{$fieldLabel} has {$errorCount} errors: {$errorList}";
    }

    /**
     * Generate accessible character count message.
     */
    public function generateCharacterCountMessage(int $current, int $limit): string
    {
        $remaining = $limit - $current;

        if ($remaining < 0) {
            $exceeded = abs($remaining);

            return "Character limit exceeded by {$exceeded} characters. Please shorten your input.";
        } elseif ($remaining === 0) {
            return 'Character limit reached. No more characters allowed.';
        } elseif ($remaining <= 10) {
            return "{$remaining} characters remaining.";
        } else {
            return "{$current} of {$limit} characters used.";
        }
    }

    /**
     * Generate accessible results summary.
     *
     * @param  array<int, array<string, mixed>>  $results
     */
    public function generateResultsSummary(array $results): string
    {
        $totalResults = count($results);

        if ($totalResults === 0) {
            return 'No name suggestions were generated. Please try again with different input.';
        }

        $availableCount = 0;
        $unavailableCount = 0;

        foreach ($results as $result) {
            if (isset($result['domain_available']) && $result['domain_available']) {
                $availableCount++;
            } else {
                $unavailableCount++;
            }
        }

        $summary = "Generated {$totalResults} name suggestions. ";

        if ($availableCount > 0) {
            $summary .= "{$availableCount} names have available domains. ";
        }

        if ($unavailableCount > 0) {
            $summary .= "{$unavailableCount} names have unavailable domains. ";
        }

        $summary .= 'Use arrow keys or tab to navigate through the results.';

        return $summary;
    }

    /**
     * Generate accessible keyboard navigation instructions.
     */
    public function generateKeyboardInstructions(string $context): string
    {
        return match ($context) {
            'model_selection' => 'Use arrow keys or tab to navigate between AI models. Press space or enter to select a model. Press escape to return to the input field.',
            'generation_modes' => 'Use arrow keys or tab to navigate between generation modes. Press space or enter to select a mode.',
            'results' => 'Use arrow keys or tab to navigate through name suggestions. Press enter on a name to copy it to clipboard. Press space to view domain details.',
            'form' => 'Use tab to move between form fields. Use shift+tab to move backwards. Press enter to submit the form.',
            default => 'Use tab to navigate interactive elements. Press space or enter to activate buttons and links.',
        };
    }

    /**
     * Generate accessible error descriptions.
     */
    public function generateErrorDescription(string $errorType, ?string $details = null): string
    {
        $baseMessage = match ($errorType) {
            'network' => 'Network connection error occurred.',
            'api_limit' => 'API usage limit has been reached.',
            'invalid_input' => 'The provided input is not valid.',
            'server_error' => 'A server error occurred while processing your request.',
            'timeout' => 'The request timed out. The AI service may be experiencing high load.',
            'model_unavailable' => 'The selected AI model is currently unavailable.',
            'authentication' => 'Authentication error. Please log in and try again.',
            default => 'An unexpected error occurred.',
        };

        if ($details) {
            $baseMessage .= " Details: {$details}";
        }

        $baseMessage .= ' Please try again or contact support if the problem persists.';

        return $baseMessage;
    }

    /**
     * Generate accessible loading state messages.
     */
    public function generateLoadingMessage(string $stage): string
    {
        return match ($stage) {
            'initializing' => 'Initializing AI name generation. Please wait.',
            'connecting' => 'Connecting to AI service. This may take a moment.',
            'processing' => 'AI is analyzing your input and generating name suggestions.',
            'validating' => 'Validating generated names and checking domain availability.',
            'finalizing' => 'Finalizing results. Almost done.',
            default => 'Processing your request. Please wait.',
        };
    }

    /**
     * Get accessibility attributes for interactive elements.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, string>
     */
    public function getElementAttributes(string $elementType, array $options = []): array
    {
        $attributes = [];

        switch ($elementType) {
            case 'model_card':
                $attributes = [
                    'role' => 'button',
                    'tabindex' => '0',
                    'aria-pressed' => isset($options['selected']) ? ($options['selected'] ? 'true' : 'false') : 'false',
                    'aria-describedby' => $options['description_id'] ?? null,
                ];
                break;

            case 'generation_button':
                $attributes = [
                    'type' => 'button',
                    'aria-describedby' => $options['description_id'] ?? null,
                    'aria-live' => 'polite',
                ];
                if (isset($options['loading']) && $options['loading']) {
                    $attributes['aria-busy'] = 'true';
                    $attributes['aria-disabled'] = 'true';
                }
                break;

            case 'progress_bar':
                $attributes = [
                    'role' => 'progressbar',
                    'aria-valuemin' => '0',
                    'aria-valuemax' => '100',
                    'aria-valuenow' => $options['progress'] ?? '0',
                    'aria-label' => $options['label'] ?? 'Generation progress',
                ];
                break;

            case 'results_list':
                $attributes = [
                    'role' => 'list',
                    'aria-label' => $options['label'] ?? 'Generated name suggestions',
                ];
                break;

            case 'result_item':
                $attributes = [
                    'role' => 'listitem',
                    'tabindex' => '0',
                    'aria-label' => $options['label'] ?? null,
                    'aria-describedby' => $options['description_id'] ?? null,
                ];
                break;

            case 'error_message':
                $attributes = [
                    'role' => 'alert',
                    'aria-live' => 'assertive',
                    'aria-atomic' => 'true',
                ];
                break;

            case 'status_message':
                $attributes = [
                    'role' => 'status',
                    'aria-live' => 'polite',
                    'aria-atomic' => 'true',
                ];
                break;
        }

        return array_filter($attributes, fn ($value) => $value !== null);
    }

    /**
     * Generate skip link navigation.
     *
     * @param  array<string, string>  $sections
     * @return array<int, array<string, string>>
     */
    public function generateSkipLinks(array $sections): array
    {
        $skipLinks = [];

        foreach ($sections as $id => $label) {
            $skipLinks[] = [
                'href' => "#{$id}",
                'label' => "Skip to {$label}",
                'class' => 'ai-skip-link',
            ];
        }

        return $skipLinks;
    }

    /**
     * Validate WCAG color contrast ratio.
     *
     * @return array<string, mixed>
     */
    public function validateColorContrast(string $foreground, string $background): array
    {
        $foregroundRgb = $this->hexToRgb($foreground);
        $backgroundRgb = $this->hexToRgb($background);

        $ratio = $this->calculateContrastRatio($foregroundRgb, $backgroundRgb);

        return [
            'ratio' => round($ratio, 2),
            'aa_normal' => $ratio >= 4.5,
            'aa_large' => $ratio >= 3.0,
            'aaa_normal' => $ratio >= 7.0,
            'aaa_large' => $ratio >= 4.5,
            'recommendation' => $this->getContrastRecommendation($ratio),
        ];
    }

    /**
     * Get model accessibility status.
     *
     * @param  array<string, mixed>  $model
     */
    protected function getModelAccessibilityStatus(array $model): string
    {
        if (! ($model['enabled'] ?? true)) {
            return 'disabled';
        }

        if (! ($model['is_available'] ?? true)) {
            return 'unavailable';
        }

        if (isset($model['maintenance_mode']) && $model['maintenance_mode']) {
            return 'maintenance mode';
        }

        return 'available and ready';
    }

    /**
     * Get field label for validation messages.
     */
    protected function getFieldLabel(string $field): string
    {
        return match ($field) {
            'business_description' => 'Business description',
            'industry' => 'Industry',
            'target_audience' => 'Target audience',
            'style_preferences' => 'Style preferences',
            'keywords' => 'Keywords',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    /**
     * Convert hex color to RGB.
     *
     * @return array<string, int>
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Calculate relative luminance.
     *
     * @param  array<string, int>  $rgb
     */
    protected function getRelativeLuminance(array $rgb): float
    {
        $normalize = function ($value) {
            $value /= 255;

            return $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        };

        $r = $normalize($rgb['r']);
        $g = $normalize($rgb['g']);
        $b = $normalize($rgb['b']);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Calculate contrast ratio between two colors.
     *
     * @param  array<string, int>  $rgb1
     * @param  array<string, int>  $rgb2
     */
    protected function calculateContrastRatio(array $rgb1, array $rgb2): float
    {
        $l1 = $this->getRelativeLuminance($rgb1);
        $l2 = $this->getRelativeLuminance($rgb2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Get contrast recommendation.
     */
    protected function getContrastRecommendation(float $ratio): string
    {
        if ($ratio >= 7.0) {
            return 'Excellent contrast (AAA)';
        } elseif ($ratio >= 4.5) {
            return 'Good contrast (AA)';
        } elseif ($ratio >= 3.0) {
            return 'Acceptable for large text only';
        } else {
            return 'Poor contrast - needs improvement';
        }
    }
}
